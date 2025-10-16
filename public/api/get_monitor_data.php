<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json');

$servicePointId = $_GET['service_point_id'] ?? null;

try {
    $db = getDB();
    
    if ($servicePointId) {
        // Get data for specific service point
        
        // Current queue
        $stmt = $db->prepare("
            SELECT q.*, qt.type_name, TRIM(CONCAT(COALESCE(sp.point_label,''),' ', sp.point_name)) as service_point_name
            FROM queues q
            LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
            LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
            WHERE q.current_service_point_id = ? 
            AND q.current_status IN ('called', 'processing')
            ORDER BY q.last_called_time DESC
            LIMIT 1
        ");
        $stmt->execute([$servicePointId]);
        $currentQueue = $stmt->fetch();
        $currents = [];
        if ($currentQueue) { $currents[] = $currentQueue; }
        
        // Waiting queues
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
        
    } else {
        // Get data for all service points
        // Current queues being called (all)
        $stmt = $db->prepare("\n            SELECT q.*, qt.type_name, TRIM(CONCAT(COALESCE(sp.point_label,''),' ', sp.point_name)) as service_point_name\n            FROM queues q\n            LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id\n            LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id\n            WHERE q.current_status IN ('called', 'processing')\n            ORDER BY q.last_called_time DESC\n        ");
        $stmt->execute();
        $currents = $stmt->fetchAll();
        $currentQueue = $currents[0] ?? null;
        
        // All waiting queues
        $stmt = $db->prepare("
            SELECT q.*, qt.type_name, TRIM(CONCAT(COALESCE(sp.point_label,''),' ', sp.point_name)) as service_point_name
            FROM queues q
            LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
            LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
            WHERE q.current_status = 'waiting'
            ORDER BY q.priority_level DESC, q.creation_time ASC
            LIMIT 20
        ");
        $stmt->execute();
        $waitingQueues = $stmt->fetchAll();
    }
    
    echo json_encode([
        'current' => $currentQueue,
        'currents' => $currents,
        'waiting' => $waitingQueues,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'current' => null,
        'waiting' => [],
        'error' => 'Database error'
    ]);
}
?>
