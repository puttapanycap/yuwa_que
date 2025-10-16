<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

if (!hasPermission('manage_users')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

// Get filter parameters
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$staffId = $_GET['staff_id'] ?? '';
$searchTerm = $_GET['search'] ?? '';

try {
    $db = getDB();
    
    // Build WHERE clause
    $whereConditions = [];
    $params = [];
    
    // Date range
    $whereConditions[] = "DATE(al.timestamp) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    
    // Staff filter
    if ($staffId) {
        $whereConditions[] = "al.staff_id = ?";
        $params[] = $staffId;
    }
    
    // Search term
    if ($searchTerm) {
        $whereConditions[] = "al.action_description LIKE ?";
        $params[] = '%' . $searchTerm . '%';
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get audit logs
    $stmt = $db->prepare("
        SELECT al.*, su.full_name,
               DATE_FORMAT(al.timestamp, '%d/%m/%Y %H:%i:%s') as formatted_timestamp
        FROM audit_logs al
        LEFT JOIN staff_users su ON al.staff_id = su.staff_id
        WHERE {$whereClause}
        ORDER BY al.timestamp DESC
        LIMIT 1000
    ");
    $stmt->execute($params);
    $auditLogs = $stmt->fetchAll();
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="audit_logs_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output CSV
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // CSV headers
    fputcsv($output, [
        'รหัส',
        'วันที่เวลา',
        'กิจกรรม',
        'ผู้ใช้',
        'IP Address',
        'User Agent'
    ]);
    
    // CSV data
    foreach ($auditLogs as $log) {
        fputcsv($output, [
            $log['log_id'],
            $log['formatted_timestamp'],
            $log['action_description'],
            $log['full_name'] ?: 'ระบบ',
            $log['ip_address'],
            $log['user_agent']
        ]);
    }
    
    fclose($output);
    
} catch (Exception $e) {
    die('เกิดข้อผิดพลาดในการส่งออกข้อมูล');
}
?>
