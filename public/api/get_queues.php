<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

header('Content-Type: application/json');

$servicePointId = $_GET['service_point_id'] ?? null;

if (!$servicePointId) {
    echo json_encode(['error' => 'Service point ID required']);
    exit;
}

// Verify access to service point
$hasAccess = false;
foreach ($_SESSION['service_points'] as $sp) {
    if ($sp['service_point_id'] == $servicePointId) {
        $hasAccess = true;
        break;
    }
}

if (!$hasAccess) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

try {
    $db = getDB();
    
    // Get current queue (being served)
    $stmt = $db->prepare("
        SELECT q.*, qt.type_name 
        FROM queues q
        LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
        WHERE q.current_service_point_id = ? 
        AND q.current_status IN ('called', 'processing')
        ORDER BY q.last_called_time DESC
        LIMIT 1
    ");
    $stmt->execute([$servicePointId]);
    $currentQueue = $stmt->fetch();
    
    // Get waiting queues
    $stmt = $db->prepare("
        SELECT q.*, qt.type_name
        FROM queues q
        LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
        WHERE q.current_service_point_id = ?
        AND q.current_status = 'waiting'
        ORDER BY q.priority_level DESC, q.creation_time ASC
    ");
    $stmt->execute([$servicePointId]);
    $waitingQueues = $stmt->fetchAll();
    
    // Get statistics
    $today = date('Y-m-d');
    
    // Waiting count
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM queues 
        WHERE current_service_point_id = ? 
        AND current_status = 'waiting'
    ");
    $stmt->execute([$servicePointId]);
    $waitingCount = $stmt->fetch()['count'];
    
    // Completed today count
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM service_flow_history sfh
        JOIN queues q ON sfh.queue_id = q.queue_id
        WHERE sfh.from_service_point_id = ? 
        AND sfh.action = 'completed'
        AND DATE(sfh.timestamp) = ?
    ");
    $stmt->execute([$servicePointId, $today]);
    $completedCount = $stmt->fetch()['count'];
    
    echo json_encode([
        'current' => $currentQueue,
        'waiting' => $waitingQueues,
        'stats' => [
            'waiting' => $waitingCount,
            'completed_today' => $completedCount
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
?>
