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
        'queue_print_count' => getSetting('queue_print_count', '1'),
        'bixolon_enabled' => getSetting('bixolon_enabled', '0'),
        'bixolon_service_url' => getSetting('bixolon_service_url', 'http://127.0.0.1:18080'),
        'bixolon_service_path' => getSetting('bixolon_service_path', '/commands/print'),
        'bixolon_printer_interface' => getSetting('bixolon_printer_interface', 'network'),
        'bixolon_printer_target' => getSetting('bixolon_printer_target', ''),
        'bixolon_printer_port' => getSetting('bixolon_printer_port', '9100'),
        'bixolon_printer_model' => getSetting('bixolon_printer_model', ''),
        'bixolon_qr_module_size' => getSetting('bixolon_qr_module_size', '6'),
        'bixolon_cut_type' => getSetting('bixolon_cut_type', 'partial'),
        'bixolon_timeout' => getSetting('bixolon_timeout', '5000'),
        'bixolon_ticket_footer' => getSetting('bixolon_ticket_footer', 'สแกน QR Code เพื่อตรวจสอบสถานะคิว'),
        'bixolon_additional_note' => getSetting('bixolon_additional_note', 'กรุณารอเรียกคิวจากเจ้าหน้าที่'),
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
