<?php

header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');
header('Content-Type: application/json');

require_once '../config/Database.php';
require_once '../models/User.php';
require_once '../config/JwtHandler.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->identifier) && !empty($data->password)) {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    if($user->login($data->identifier, $data->password)) {
        $user_data = array(
            "user_id" => $user->user_id,
            "username" => $user->username,
            "email" => $user->email,
            "full_name" => $user->full_name,
            "is_admin" => $user->is_admin
        );

        // Generate JWT token
        $jwt = new JwtHandler();
        $token = $jwt->generateToken($user_data);

        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "message" => "Login successful",
            "token" => $token,
            "user" => $user_data
        ));
    } else {
        http_response_code(401);
        echo json_encode(array(
            "success" => false,
            "message" => "Invalid credentials"
        ));
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Unable to login. Data is incomplete."
    ));
}
?>