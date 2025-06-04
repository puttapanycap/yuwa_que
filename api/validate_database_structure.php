<?php
require_once '../config/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDB();
    $issues = [];
    $fixes = [];
    
    // ตรวจสอบโครงสร้างตาราง service_points
    $stmt = $db->query("DESCRIBE service_points");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = [
        'service_point_id',
        'point_name', 
        'point_description',
        'position_key',
        'is_active',
        'display_order',
        'created_at'
    ];
    
    foreach ($requiredColumns as $column) {
        if (!in_array($column, $columns)) {
            $issues[] = "Missing column: service_points.$column";
            
            switch ($column) {
                case 'display_order':
                    $fixes[] = "ALTER TABLE service_points ADD COLUMN display_order INT DEFAULT 0";
                    break;
                case 'queue_type_id':
                    $fixes[] = "ALTER TABLE service_points ADD COLUMN queue_type_id INT NULL";
                    break;
            }
        }
    }
    
    // ตรวจสอบข้อมูล display_order
    $stmt = $db->query("SELECT COUNT(*) FROM service_points WHERE display_order = 0");
    $zeroOrderCount = $stmt->fetchColumn();
    
    if ($zeroOrderCount > 0) {
        $issues[] = "Found $zeroOrderCount service points with display_order = 0";
        $fixes[] = "UPDATE service_points SET display_order = service_point_id WHERE display_order = 0";
    }
    
    // ตรวจสอบตาราง service_flow_history
    $stmt = $db->query("DESCRIBE service_flow_history");
    $flowColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredFlowColumns = [
        'flow_id',
        'queue_id',
        'from_service_point_id',
        'to_service_point_id', 
        'staff_id',
        'action',
        'notes',
        'timestamp'
    ];
    
    foreach ($requiredFlowColumns as $column) {
        if (!in_array($column, $flowColumns)) {
            $issues[] = "Missing column: service_flow_history.$column";
        }
    }
    
    echo json_encode([
        'success' => empty($issues),
        'issues' => $issues,
        'fixes' => $fixes,
        'message' => empty($issues) ? 'Database structure is valid' : 'Found ' . count($issues) . ' issues'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
