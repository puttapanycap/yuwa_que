<?php
require_once '../config/config.php';
requireLogin();

if (!hasPermission('manage_users')) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT al.*, su.full_name,
               DATE_FORMAT(al.timestamp, '%d/%m/%Y %H:%i') as timestamp
        FROM audit_logs al
        LEFT JOIN staff_users su ON al.staff_id = su.staff_id
        ORDER BY al.timestamp DESC
        LIMIT 10
    ");
    $stmt->execute();
    $activities = $stmt->fetchAll();
    
    echo json_encode($activities);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
?>
