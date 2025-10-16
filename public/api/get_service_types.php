<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json');

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT queue_type_id, type_name, description, icon_class, prefix_char FROM queue_types WHERE is_active = 1 ORDER BY queue_type_id");
    $stmt->execute();
    $serviceTypes = $stmt->fetchAll();
    
    echo json_encode($serviceTypes);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>
