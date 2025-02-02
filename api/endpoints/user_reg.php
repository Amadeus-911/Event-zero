<?php
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config/Database.php';
include_once '../models/User.php';

// Only proceed if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents("php://input"));
    if(
        !empty($data->username) &&
        !empty($data->email) &&
        !empty($data->password) &&
        !empty($data->full_name)
    ) {

        $database = new Database();
        $db = $database->getConnection();

        $user = new User($db);

        $user->username = $data->username;
        $user->email = $data->email;
        $user->password = $data->password;
        $user->full_name = $data->full_name;

        if($user->create()) {
            http_response_code(201);
            echo json_encode(array(
                "success" => true,
                "message" => "User was created successfully."
            ));
        } else {
            http_response_code(503);
            echo json_encode(array(
                "success" => false,
                "message" => "Username or email already exists."
            ));
        }
    } else {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "message" => "Unable to create user. Data is incomplete."
        ));
    }
} else {
    http_response_code(405);
    echo json_encode(array(
        "success" => false,
        "message" => "Method not allowed"
    ));
}
?>