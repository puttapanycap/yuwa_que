<?php
require_once '../config/config.php';

// ตรวจสอบการเข้าสู่ระบบและสิทธิ์
if (!isLoggedIn()) {
    die('ไม่ได้เข้าสู่ระบบ');
}

if (!hasPermission('view_reports')) {
    die('ไม่มีสิทธิ์ดูรายงาน');
}

try {
    $db = getDB();
    
    // รับพารามิเตอร์
    $scheduleId = $_GET['schedule_id'] ?? '';
    $status = $_GET['status'] ?? '';
    $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    $format = $_GET['format'] ?? 'csv';
    
    // สร้าง WHERE clause
    $whereConditions = [];
    $params = [];
    
    $whereConditions[] = "DATE(arl.executed_at) BETWEEN ? AND ?";
    $params[] = $dateFrom;
    $params[] = $dateTo;
    
    if ($scheduleId) {
        $whereConditions[] = "arl.schedule_id = ?";
        $params[] = $scheduleId;
    }
    
    if ($status) {
        $whereConditions[] = "arl.status = ?";
        $params[] = $status;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // ดึงข้อมูล
    $stmt = $db->prepare("
        SELECT 
            arl.log_id,
            ars.schedule_name,
            arl.reset_type,
            CASE 
                WHEN arl.reset_type = 'by_type' THEN qt.type_name
                WHEN arl.reset_type = 'by_service_point' THEN sp.point_name
                ELSE 'ทุกประเภท'
            END as target_name,
            arl.reset_count,
            arl.affected_types,
            arl.status,
            arl.error_message,
            arl.execution_time,
            arl.executed_at
        FROM auto_reset_logs arl
        LEFT JOIN auto_reset_schedules ars ON arl.schedule_id = ars.schedule_id
        LEFT JOIN queue_types qt ON arl.target_id = qt.queue_type_id AND arl.reset_type = 'by_type'
        LEFT JOIN service_points sp ON arl.target_id = sp.service_point_id AND arl.reset_type = 'by_service_point'
        WHERE {$whereClause}
        ORDER BY arl.executed_at DESC
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // ส่งออกตามรูปแบบ
    switch ($format) {
        case 'json':
            exportJSON($logs);
            break;
        case 'excel':
            exportExcel($logs);
            break;
        default:
            exportCSV($logs);
            break;
    }
    
} catch (Exception $e) {
    die('เกิดข้อผิดพลาด: ' . $e->getMessage());
}

function exportCSV($logs) {
    $filename = 'auto_reset_logs_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    
    // เพิ่ม BOM สำหรับ UTF-8
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // Header
    fputcsv($output, [
        'ID',
        'Schedule',
        'ประเภทการ Reset',
        'เป้าหมาย',
        'จำนวนที่ Reset',
        'ประเภทที่ได้รับผลกระทบ',
        'สถานะ',
        'ข้อผิดพลาด',
        'เวลาที่ใช้ (วินาที)',
        'วันที่ทำการ'
    ]);
    
    // Data
    foreach ($logs as $log) {
        $affectedTypes = '';
        if ($log['affected_types']) {
            $types = json_decode($log['affected_types'], true);
            $affectedTypes = is_array($types) ? implode(', ', $types) : '';
        }
        
        $resetType = match($log['reset_type']) {
            'all' => 'ทุกประเภท',
            'by_type' => 'ตามประเภทคิว',
            'by_service_point' => 'ตามจุดบริการ',
            default => $log['reset_type']
        };
        
        $status = match($log['status']) {
            'success' => 'สำเร็จ',
            'failed' => 'ล้มเหลว',
            'skipped' => 'ข้าม',
            default => $log['status']
        };
        
        fputcsv($output, [
            $log['log_id'],
            $log['schedule_name'] ?: 'Manual',
            $resetType,
            $log['target_name'],
            $log['reset_count'],
            $affectedTypes,
            $status,
            $log['error_message'] ?: '',
            number_format($log['execution_time'], 3),
            date('d/m/Y H:i:s', strtotime($log['executed_at']))
        ]);
    }
    
    fclose($output);
}

function exportJSON($logs) {
    $filename = 'auto_reset_logs_' . date('Y-m-d_H-i-s') . '.json';
    
    header('Content-Type: application/json; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    
    // เตรียมข้อมูล
    $exportData = [
        'export_date' => date('Y-m-d H:i:s'),
        'total_records' => count($logs),
        'data' => []
    ];
    
    foreach ($logs as $log) {
        $exportData['data'][] = [
            'log_id' => (int)$log['log_id'],
            'schedule_name' => $log['schedule_name'],
            'reset_type' => $log['reset_type'],
            'target_name' => $log['target_name'],
            'reset_count' => (int)$log['reset_count'],
            'affected_types' => json_decode($log['affected_types'], true) ?: [],
            'status' => $log['status'],
            'error_message' => $log['error_message'],
            'execution_time' => (float)$log['execution_time'],
            'executed_at' => $log['executed_at']
        ];
    }
    
    echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function exportExcel($logs) {
    // สำหรับ Excel format - ใช้ CSV แต่เปลี่ยน extension
    $filename = 'auto_reset_logs_' . date('Y-m-d_H-i-s') . '.xls';
    
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    
    // เพิ่ม BOM สำหรับ UTF-8
    echo "\xEF\xBB\xBF";
    
    // HTML Table format สำหรับ Excel
    echo '<table border="1">';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Schedule</th>';
    echo '<th>ประเภทการ Reset</th>';
    echo '<th>เป้าหมาย</th>';
    echo '<th>จำนวนที่ Reset</th>';
    echo '<th>ประเภทที่ได้รับผลกระทบ</th>';
    echo '<th>สถานะ</th>';
    echo '<th>ข้อผิดพลาด</th>';
    echo '<th>เวลาที่ใช้ (วินาที)</th>';
    echo '<th>วันที่ทำการ</th>';
    echo '</tr>';
    
    foreach ($logs as $log) {
        $affectedTypes = '';
        if ($log['affected_types']) {
            $types = json_decode($log['affected_types'], true);
            $affectedTypes = is_array($types) ? implode(', ', $types) : '';
        }
        
        $resetType = match($log['reset_type']) {
            'all' => 'ทุกประเภท',
            'by_type' => 'ตามประเภทคิว',
            'by_service_point' => 'ตามจุดบริการ',
            default => $log['reset_type']
        };
        
        $status = match($log['status']) {
            'success' => 'สำเร็จ',
            'failed' => 'ล้มเหลว',
            'skipped' => 'ข้าม',
            default => $log['status']
        };
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($log['log_id']) . '</td>';
        echo '<td>' . htmlspecialchars($log['schedule_name'] ?: 'Manual') . '</td>';
        echo '<td>' . htmlspecialchars($resetType) . '</td>';
        echo '<td>' . htmlspecialchars($log['target_name']) . '</td>';
        echo '<td>' . htmlspecialchars($log['reset_count']) . '</td>';
        echo '<td>' . htmlspecialchars($affectedTypes) . '</td>';
        echo '<td>' . htmlspecialchars($status) . '</td>';
        echo '<td>' . htmlspecialchars($log['error_message'] ?: '') . '</td>';
        echo '<td>' . number_format($log['execution_time'], 3) . '</td>';
        echo '<td>' . date('d/m/Y H:i:s', strtotime($log['executed_at'])) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
}
?>
