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

$database = new Database();
$db = $database->getConnection();

$event = new Event($db);

$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$length = isset($_GET['length']) ? (int)$_GET['length'] : 10;
$page = ($start / $length) + 1;
$search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$dateFilter = isset($_GET['dateFilter']) ? $_GET['dateFilter'] : '';
$statusFilter = isset($_GET['statusFilter']) ? $_GET['statusFilter'] : '';


$orderColumn = 'event_date'; 
$orderDir = 'ASC'; 

if (isset($_GET['order']) && isset($_GET['order'][0])) {
    $columnIndex = $_GET['order'][0]['column'];
    if (isset($_GET['columns'][$columnIndex]['data'])) {
        $orderColumn = $_GET['columns'][$columnIndex]['data'];
        $orderDir = strtoupper($_GET['order'][0]['dir']);
    }
}

$allowedColumns = ['event_date', 'title', 'venue', 'capacity'];
if (!in_array($orderColumn, $allowedColumns)) {
    $orderColumn = 'event_date';
}
$orderDir = ($orderDir === 'DESC') ? 'DESC' : 'ASC';

if ($filter === 'my') {
    $events = $event->getUserEvents(
        $result['data']['user']['user_id'],
        $page,
        $length,
        $search,
        $orderColumn,
        $orderDir,
        $dateFilter,
        $statusFilter
    );
} else {
    $events = $event->getAllEvents(
        $page,
        $length,
        $search,
        $orderColumn,
        $orderDir,
        $dateFilter,
        $statusFilter
    );
}


if ($events) {
    echo json_encode([
        'draw' => isset($_GET['draw']) ? (int)$_GET['draw'] : 1,
        'recordsTotal' => $events['total_records'],
        'recordsFiltered' => $events['total_records'],
        'data' => $events['data'],
        // Debug information (optional, remove in production)
        'debug' => [
            'filter' => $filter,
            'dateFilter' => $dateFilter,
            'statusFilter' => $statusFilter,
            'search' => $search,
            'orderColumn' => $orderColumn,
            'orderDir' => $orderDir,
            'page' => $page,
            'length' => $length
        ]
    ]);
} else {
    echo json_encode([
        'draw' => isset($_GET['draw']) ? (int)$_GET['draw'] : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'No events found or error occurred'
    ]);
}
?>