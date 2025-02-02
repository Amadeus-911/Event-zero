<?php

header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');
header('Content-Type: application/json');

require_once '../../config/Database.php';
require_once '../../models/Contact.php';
require_once '../../middleware/AuthMiddleware.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$auth = new AuthMiddleware();
$result = $auth->validateRequest();

if (!$result['success']) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if(!empty($data->message_id) && !empty($data->status)) {
    
    $validStatuses = ['unread', 'read', 'replied'];
    if (!in_array($data->status, $validStatuses)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid status value'
        ]);
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();

    $contact = new Contact($db);

    if($contact->updateStatus($data->message_id, $data->status)) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Message status updated successfully'
        ]);
    } else {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'message' => 'Unable to update message status'
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required data'
    ]);
}
?>