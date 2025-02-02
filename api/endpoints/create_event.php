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
        'message' => $result['message']
    ]);
    exit();
}


$data = json_decode(file_get_contents("php://input"));

if(
    empty($data->title) ||
    empty($data->description) ||
    empty($data->event_date) ||
    empty($data->event_time) ||
    empty($data->venue) ||
    empty($data->capacity) ||
    empty($data->registration_deadline)
) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit();
}

$event_date = new DateTime($data->event_date);
$reg_deadline = new DateTime($data->registration_deadline);
$today = new DateTime();

if ($reg_deadline >= $event_date) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Registration deadline must be before event date'
    ]);
    exit();
}

if ($event_date <= $today) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Event date must be in the future'
    ]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$event = new Event($db);

$event->user_id = $result['data']['user']['user_id'];
$event->title = $data->title;
$event->description = $data->description;
$event->event_date = $data->event_date;
$event->event_time = $data->event_time;
$event->venue = $data->venue;
$event->capacity = $data->capacity;
$event->registration_deadline = $data->registration_deadline;

if($event->create()) {
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Event created successfully'
    ]);
} else {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to create event'
    ]);
}
?>