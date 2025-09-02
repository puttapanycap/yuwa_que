<?php
require_once '../config/config.php';

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
        
        // Current queues being called
        $stmt = $db->prepare("
            SELECT q.*, qt.type_name, TRIM(CONCAT(COALESCE(sp.point_label,''),' ', sp.point_name)) as service_point_name
            FROM queues q
            LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
            LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
            WHERE q.current_status IN ('called', 'processing')
            ORDER BY q.last_called_time DESC
            LIMIT 1
        ");
        $stmt->execute();
        $currentQueue = $stmt->fetch();
        
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
