<?php
class JwtHandler {
    private $secret_key = "SECRETKEYISNULL"; 
    private $issuer = "evently_app";
    private $expiry = 86400; 

    // Generate JWT Token
    public function generateToken($user_data) {
        $header = $this->generateHeader();
        $payload = $this->generatePayload($user_data);
        
        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $payload_json = json_encode($payload);
        $base64UrlPayload = $this->base64UrlEncode($payload_json);
        
        $signature = $this->generateSignature($base64UrlHeader, $base64UrlPayload);
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $signature;
    }

    // Validate JWT Token
    public function validateToken($token) {
        try {
            $tokenParts = explode('.', trim($token, '"'));
            
            if (count($tokenParts) != 3) {
                return ['success' => false, 'message' => 'Invalid token format'];
            }

            [$headerEncoded, $payloadEncoded, $signatureProvided] = $tokenParts;

            // For debugging
            error_log("Token parts:");
            error_log("Header (encoded): " . $headerEncoded);
            error_log("Payload (encoded): " . $payloadEncoded);
            error_log("Signature (provided): " . $signatureProvided);

            $signatureCalculated = $this->generateSignature($headerEncoded, $payloadEncoded);

            error_log("Signature (calculated): " . $signatureCalculated);

            if ($signatureProvided !== $signatureCalculated) {
                return ['success' => false, 'message' => $headerEncoded];
            }

            $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);

            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return ['success' => false, 'message' => 'Token has expired'];
            }

            return [
                'success' => true,
                'data' => $payload
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function generateHeader() {
        return [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
    }

    private function generatePayload($user_data) {
        return [
            'iss' => $this->issuer,
            'iat' => time(),
            'exp' => time() + $this->expiry,
            'user' => $user_data
        ];
    }

    private function generateSignature($headerEncoded, $payloadEncoded) {
        $data = $headerEncoded . "." . $payloadEncoded;
        $hash = hash_hmac('sha256', $data, $this->secret_key, true);
        return $this->base64UrlEncode($hash);
    }

    private function base64UrlEncode($data) {
        $base64 = base64_encode($data);
        return str_replace(['+', '/', '='], ['-', '_', ''], $base64);
    }

    private function base64UrlDecode($data) {
        $base64 = str_replace(['-', '_'], ['+', '/'], $data);
        $padding = strlen($base64) % 4;
        if ($padding > 0) {
            $base64 .= str_repeat('=', 4 - $padding);
        }
        return base64_decode($base64);
    }
}
?>