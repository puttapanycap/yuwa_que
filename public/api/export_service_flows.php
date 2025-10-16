<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

if (!hasPermission('view_reports')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

try {
    $db = getDB();
    
    // Get all service flows with details
    $stmt = $db->prepare("
        SELECT qt.type_name, qt.prefix_char,
               sf.sequence_order,
               sp_from.point_name as from_service_point_name,
               sp_to.point_name as to_service_point_name,
               sf.is_optional,
               sf.flow_id
        FROM service_flows sf
        JOIN queue_types qt ON sf.queue_type_id = qt.queue_type_id
        LEFT JOIN service_points sp_from ON sf.from_service_point_id = sp_from.service_point_id
        JOIN service_points sp_to ON sf.to_service_point_id = sp_to.service_point_id
        ORDER BY qt.type_name, sf.sequence_order
    ");
    
    $stmt->execute();
    $flows = $stmt->fetchAll();
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="service_flows_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Output CSV
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // CSV headers
    fputcsv($output, [
        'ประเภทคิว',
        'รหัสนำหน้า',
        'ลำดับ',
        'จากจุดบริการ',
        'ไปจุดบริการ',
        'ขั้นตอนเสริม',
        'Flow ID'
    ]);
    
    // CSV data
    foreach ($flows as $flow) {
        fputcsv($output, [
            $flow['type_name'],
            $flow['prefix_char'],
            $flow['sequence_order'],
            $flow['from_service_point_name'] ?: 'เริ่มต้น',
            $flow['to_service_point_name'],
            $flow['is_optional'] ? 'ใช่' : 'ไม่',
            $flow['flow_id']
        ]);
    }
    
    fclose($output);
    
} catch (Exception $e) {
    die('เกิดข้อผิดพลาด: ' . $e->getMessage());
}
?>
