<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json');

$queueId = $_GET['queue_id'] ?? null;

if (!$queueId) {
    echo json_encode(['success' => false, 'message' => 'Missing queue_id parameter']);
    exit;
}

try {
    $db = getDB();
    
    // Get queue flow history with service point names
    $stmt = $db->prepare("
        SELECT 
            sfh.*,
            sp_from.point_name as from_service_point_name,
            sp_to.point_name as to_service_point_name,
            su.full_name as staff_name,
            q.queue_number
        FROM service_flow_history sfh
        LEFT JOIN service_points sp_from ON sfh.from_service_point_id = sp_from.service_point_id
        LEFT JOIN service_points sp_to ON sfh.to_service_point_id = sp_to.service_point_id
        LEFT JOIN staff_users su ON sfh.staff_id = su.staff_id
        LEFT JOIN queues q ON sfh.queue_id = q.queue_id
        WHERE sfh.queue_id = ?
        ORDER BY sfh.timestamp ASC
    ");
    
    $stmt->execute([$queueId]);
    $history = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'history' => $history
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
?>
