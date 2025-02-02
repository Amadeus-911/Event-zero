<?php
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');
header('Content-Type: application/json');

require_once '../config/Database.php';
require_once '../middleware/AuthMiddleware.php';

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

if (!isset($data->registration_id) || !isset($data->status)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit();
}

$valid_statuses = ['pending', 'confirmed', 'cancelled'];
if (!in_array($data->status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status'
    ]);
    exit();
}

$database = new Database();
$db = $database->getConnection();


$query = "SELECT e.user_id 
          FROM events e
          JOIN event_registrations er ON e.event_id = er.event_id
          WHERE er.registration_id = :registration_id";

$stmt = $db->prepare($query);
$stmt->bindParam(":registration_id", $data->registration_id);
$stmt->execute();

$event = $stmt->fetch(PDO::FETCH_ASSOC);

// Allow access if user is either the event creator or an admin
if ($event['user_id'] != $result['data']['user']['user_id'] && !$result['data']['user']['is_admin']) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

// Update registration status
$query = "UPDATE event_registrations 
          SET status = :status 
          WHERE registration_id = :registration_id";

$stmt = $db->prepare($query);
$stmt->bindParam(":status", $data->status);
$stmt->bindParam(":registration_id", $data->registration_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Registration status updated successfully'
    ]);
} else {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to update registration status'
    ]);
}
?>