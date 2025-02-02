<?php
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');
header('Content-Type: application/json');

require_once '../config/Database.php';
require_once '../models/Contact.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->name) && !empty($data->email) && !empty($data->message)) {
    
    $database = new Database();
    $db = $database->getConnection();

    $contact = new Contact($db);

    $contact->name = $data->name;
    $contact->email = $data->email;
    $contact->message = $data->message;

    if($contact->create()) {
        http_response_code(201);
        echo json_encode(array(
            "success" => true,
            "message" => "Message sent successfully."
        ));
    } else {
        http_response_code(503);
        echo json_encode(array(
            "success" => false,
            "message" => "Unable to send message."
        ));
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Unable to send message. Data is incomplete."
    ));
}
?>