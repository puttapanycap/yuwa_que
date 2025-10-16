<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ตรวจสอบการเข้าสู่ระบบและสิทธิ์
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'ไม่ได้เข้าสู่ระบบ']);
    exit;
}

if (!hasPermission('view_reports')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ดูรายงาน']);
    exit;
}

try {
    $db = getDB();
    
    // รับพารามิเตอร์
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(10, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $scheduleId = $_GET['schedule_id'] ?? null;
    $status = $_GET['status'] ?? null;
    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    
    // สร้าง WHERE clause
    $whereConditions = [];
    $params = [];
    
    if ($scheduleId) {
        $whereConditions[] = "arl.schedule_id = ?";
        $params[] = $scheduleId;
    }
    
    if ($status) {
        $whereConditions[] = "arl.status = ?";
        $params[] = $status;
    }
    
    if ($dateFrom) {
        $whereConditions[] = "DATE(arl.executed_at) >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $whereConditions[] = "DATE(arl.executed_at) <= ?";
        $params[] = $dateTo;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // นับจำนวนรวม
    $countSql = "
        SELECT COUNT(*) as total
        FROM auto_reset_logs arl
        LEFT JOIN auto_reset_schedules ars ON arl.schedule_id = ars.schedule_id
        $whereClause
    ";
    
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // ดึงข้อมูล logs
    $sql = "
        SELECT 
            arl.*,
            ars.schedule_name,
            CASE 
                WHEN arl.reset_type = 'by_type' THEN qt.type_name
                WHEN arl.reset_type = 'by_service_point' THEN sp.point_name
                ELSE 'ทุกประเภท'
            END as target_name
        FROM auto_reset_logs arl
        LEFT JOIN auto_reset_schedules ars ON arl.schedule_id = ars.schedule_id
        LEFT JOIN queue_types qt ON arl.target_id = qt.queue_type_id AND arl.reset_type = 'by_type'
        LEFT JOIN service_points sp ON arl.target_id = sp.service_point_id AND arl.reset_type = 'by_service_point'
        $whereClause
        ORDER BY arl.executed_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // คำนวณ pagination
    $totalPages = ceil($total / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => $logs,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $total,
            'per_page' => $limit,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get auto reset logs error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ]);
}
?>
