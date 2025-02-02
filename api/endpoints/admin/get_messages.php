<?php
header('Content-Type: application/json');
require_once '../../config/Database.php';
require_once '../../models/Contact.php';
require_once '../../middleware/AuthMiddleware.php';

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

$database = new Database();
$db = $database->getConnection();

$contact = new Contact($db);

$stmt = $contact->getAllMessages();
$messages = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    array_push($messages, $row);
}

echo json_encode([
    'success' => true,
    'messages' => $messages
]);
?>