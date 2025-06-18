<?php
require_once '../config/config.php';

header('Content-Type: application/json');

try {
    $db = getDB();
    
    // ตรวจสอบโครงสร้างตาราง notifications
    $structureQuery = "DESCRIBE notifications";
    $structureStmt = $db->query($structureQuery);
    $structure = $structureStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ตรวจสอบข้อมูลตัวอย่าง
    $sampleQuery = "SELECT * FROM notifications ORDER BY created_at DESC LIMIT 3";
    $sampleStmt = $db->query($sampleQuery);
    $sample = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ตรวจสอบว่ามีตาราง notification_types หรือไม่
    $tablesQuery = "SHOW TABLES LIKE 'notification_types'";
    $tablesStmt = $db->query($tablesQuery);
    $hasNotificationTypes = $tablesStmt->rowCount() > 0;
    
    $notificationTypes = [];
    if ($hasNotificationTypes) {
        $typesQuery = "SELECT * FROM notification_types";
        $typesStmt = $db->query($typesQuery);
        $notificationTypes = $typesStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ตรวจสอบคอลัมน์ที่จำเป็น
    $requiredColumns = ['notification_id', 'notification_type', 'title', 'message', 'priority', 'created_at'];
    $optionalColumns = ['is_public', 'is_active', 'expires_at', 'auto_dismiss_after', 'service_point_id', 'metadata'];
    
    $existingColumns = array_column($structure, 'Field');
    $missingRequired = array_diff($requiredColumns, $existingColumns);
    $missingOptional = array_diff($optionalColumns, $existingColumns);
    
    echo json_encode([
        'success' => true,
        'table_structure' => $structure,
        'sample_data' => $sample,
        'has_notification_types_table' => $hasNotificationTypes,
        'notification_types' => $notificationTypes,
        'analysis' => [
            'existing_columns' => $existingColumns,
            'missing_required' => $missingRequired,
            'missing_optional' => $missingOptional,
            'structure_complete' => empty($missingRequired)
        ],
        'recommendations' => [
            'run_fix_script' => !empty($missingRequired) || !empty($missingOptional),
            'create_notification_types' => !$hasNotificationTypes,
            'update_existing_data' => count($sample) > 0
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
