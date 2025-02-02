<?php

// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// // Log the request
// error_log("Event details endpoint hit");
// error_log("GET params: " . print_r($_GET, true));

header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');
header('Content-Type: application/json');

require_once '../config/Database.php';
require_once '../models/Event.php';
require_once '../middleware/AuthMiddleware.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Validate authentication
// $auth = new AuthMiddleware();
// $result = $auth->validateRequest();

// if (!$result['success']) {
//     http_response_code(401);
//     echo json_encode([
//         'success' => false,
//         'message' => 'Unauthorized'
//     ]);
//     exit();
// }

// Check if event ID is provided
if (!isset($_GET['id'])) {
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

$eventDetails = $event->getEventById($_GET['id']);

if ($eventDetails) {
    echo json_encode([
        'success' => true,
        'event' => $eventDetails
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Event not found'
    ]);
}
?>