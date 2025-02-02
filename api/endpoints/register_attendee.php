<?php
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');
header('Content-Type: application/json');

require_once '../config/Database.php';
require_once '../models/Event.php';
require_once '../models/Attendee.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


$data = json_decode(file_get_contents("php://input"));

if (
    !isset($data->event_id) ||
    !isset($data->full_name) ||
    !isset($data->email) ||
    !isset($data->phone)
) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit();
}


$database = new Database();
$db = $database->getConnection();


$event = new Event($db);
$eventDetails = $event->getEventById($data->event_id);

if (!$eventDetails) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Event not found'
    ]);
    exit();
}

if ($eventDetails['registered_attendees'] >= $eventDetails['capacity']) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Event is full'
    ]);
    exit();
}


$attendee = new Attendee($db);


$attendee->full_name = $data->full_name;
$attendee->email = $data->email;
$attendee->phone = $data->phone;


if ($attendee->registerForEvent($data->event_id)) {
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful'
    ]);
} else {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to register. You might already be registered for this event.'
    ]);
}
?>