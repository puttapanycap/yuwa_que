<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

try {
    $db = getDB();
    
    // Get call history with related data
    $stmt = $db->prepare("
        SELECT 
            ach.*,
            q.queue_number,
            sp.point_name as service_point_name,
            su.full_name as staff_name
        FROM audio_call_history ach
        LEFT JOIN queues q ON ach.queue_id = q.queue_id
        LEFT JOIN service_points sp ON ach.service_point_id = sp.service_point_id
        LEFT JOIN staff_users su ON ach.staff_id = su.staff_id
        ORDER BY ach.call_time DESC
        LIMIT 100
    ");
    $stmt->execute();
    $history = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'history' => $history
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
