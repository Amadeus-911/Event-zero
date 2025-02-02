<?php
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

// Check if event ID is provided
if (!isset($_GET['event_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Event ID is required'
    ]);
    exit();
}

$event_id = $_GET['event_id'];
$user_id = $result['data']['user']['user_id'];

$database = new Database();
$db = $database->getConnection();

$query = "SELECT e.*, COUNT(er.registration_id) as registered_attendees 
          FROM events e
          LEFT JOIN event_registrations er ON e.event_id = er.event_id
          WHERE e.event_id = :event_id AND e.user_id = :user_id
          GROUP BY e.event_id";

$stmt = $db->prepare($query);
$stmt->bindParam(":event_id", $event_id);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access to event'
    ]);
    exit();
}

$event = $stmt->fetch(PDO::FETCH_ASSOC);

// Get attendees
$query = "SELECT a.*, er.registration_date, er.status, er.registration_id
          FROM attendees a
          JOIN event_registrations er ON a.attendee_id = er.attendee_id
          WHERE er.event_id = :event_id
          ORDER BY er.registration_date DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(":event_id", $event_id);
$stmt->execute();

$attendees = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'event' => $event,
    'attendees' => $attendees
]);
?>