<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT service_point_id, point_name, position_key FROM service_points WHERE is_active = 1 ORDER BY display_order, point_name");
    $stmt->execute();
    $servicePoints = $stmt->fetchAll();
    
    echo json_encode($servicePoints);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
?>
