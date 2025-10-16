<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

header('Content-Type: application/json');

try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT TRIM(CONCAT(COALESCE(sp.point_label,''),' ', sp.point_name)) AS point_name,
               CASE WHEN q.queue_id IS NOT NULL THEN 1 ELSE 0 END as has_active_queue
        FROM service_points sp
        LEFT JOIN queues q ON sp.service_point_id = q.current_service_point_id 
                           AND q.current_status IN ('waiting', 'called', 'processing')
        WHERE sp.is_active = 1
        ORDER BY sp.display_order, sp.point_name
    ");
    $stmt->execute();
    $servicePoints = $stmt->fetchAll();
    
    echo json_encode($servicePoints);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
?>
