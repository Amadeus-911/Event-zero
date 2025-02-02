<?php
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');
header('Content-Type: application/json');

require_once '../config/Database.php';
require_once '../models/Event.php';
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

if (!isset($data->event_id)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Event ID is required'
    ]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$event = new Event($db);

$currentEvent = $event->getEventById($data->event_id);

if (!$currentEvent) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Event not found'
    ]);
    exit();
}

if ($currentEvent['user_id'] != $result['data']['user']['user_id']) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'You are not authorized to delete this event'
    ]);
    exit();
}


if ($event->deleteEvent($data->event_id)) {
    echo json_encode([
        'success' => true,
        'message' => 'Event deleted successfully'
    ]);
} else {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to delete event'
    ]);
}
?>