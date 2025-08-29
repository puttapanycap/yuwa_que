<?php
require_once '../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Get all current settings
    $settings = [
        'hospital_name' => getSetting('hospital_name', 'โรงพยาบาลยุวประสาทไวทโยปถัมภ์'),
        'queue_call_template' => getSetting('queue_call_template', 'หมายเลข {queue_number} เชิญที่ {service_point_name}'),
        'auto_forward_enabled' => getSetting('auto_forward_enabled', '0'),
        'max_queue_per_day' => getSetting('max_queue_per_day', '999'),
        'queue_timeout_minutes' => getSetting('queue_timeout_minutes', '30'),
        'display_refresh_interval' => getSetting('display_refresh_interval', '3'),
        'enable_priority_queue' => getSetting('enable_priority_queue', '1'),
        'working_hours_start' => getSetting('working_hours_start', '08:00'),
        'working_hours_end' => getSetting('working_hours_end', '16:00'),
        'last_updated' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($settings);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to get settings',
        'message' => $e->getMessage()
    ]);
}
?>
