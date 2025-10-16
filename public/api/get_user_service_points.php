<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

if (!hasPermission('manage_users')) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

$staffId = $_GET['staff_id'] ?? null;

if (!$staffId) {
    echo json_encode([]);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT service_point_id 
        FROM staff_service_point_access 
        WHERE staff_id = ?
    ");
    $stmt->execute([$staffId]);
    $servicePoints = $stmt->fetchAll();
    
    echo json_encode($servicePoints);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
?>
