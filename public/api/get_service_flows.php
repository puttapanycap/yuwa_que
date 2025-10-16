<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

header('Content-Type: application/json');

$queueTypeId = $_GET['queue_type_id'] ?? null;

if (!$queueTypeId) {
    echo json_encode([]);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT sf.*, 
               sp_from.point_name as from_service_point_name,
               sp_to.point_name as to_service_point_name
        FROM service_flows sf
        LEFT JOIN service_points sp_from ON sf.from_service_point_id = sp_from.service_point_id
        LEFT JOIN service_points sp_to ON sf.to_service_point_id = sp_to.service_point_id
        WHERE sf.queue_type_id = ?
        ORDER BY sf.sequence_order, sf.flow_id
    ");
    
    $stmt->execute([$queueTypeId]);
    $flows = $stmt->fetchAll();
    
    echo json_encode($flows);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>
