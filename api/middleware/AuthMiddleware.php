<?php
require_once __DIR__ . '/../config/JwtHandler.php';

class AuthMiddleware {
    private $jwt;

    public function __construct() {
        $this->jwt = new JwtHandler();
    }

    public function validateRequest() {
        $headers = $this->getAuthorizationHeader();
        
        if (!$headers) {
            return [
                'success' => false,
                'message' => 'Authorization header not found'
            ];
        }

        $token = $this->getBearerToken($headers);
        $token = trim($token, '"');
        if (!$token) {
            return [
                'success' => false,
                'message' => 'Token not found'
            ];
        }

        return $this->jwt->validateToken($token);
    }

    private function getAuthorizationHeader() {
        $headers = null;
        
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } else if (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        
        return $headers;
    }

    private function getBearerToken($headers) {
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }
}
?>