<?php
require_once '../config/config.php';
require_once 'notification_center.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // รับพารามิเตอร์
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $servicePointId = isset($_GET['service_point_id']) ? (int)$_GET['service_point_id'] : null;
    $types = isset($_GET['types']) ? explode(',', $_GET['types']) : ['queue_called', 'announcement', 'system_alert'];
    
    // จำกัดขนาดข้อมูล
    $limit = min($limit, 50);
    
    $db = getDB();
    
    // สร้าง query สำหรับ public notifications
    $whereConditions = [];
    $params = [];
    
    // เฉพาะ notification ที่เป็น public
    $whereConditions[] = "n.is_public = 1";
    
    // เฉพาะ notification ที่ยังไม่หมดอายุ
    $whereConditions[] = "(n.expires_at IS NULL OR n.expires_at > NOW())";
    
    // กรองตามประเภท
    if (!empty($types)) {
        $placeholders = str_repeat('?,', count($types) - 1) . '?';
        $whereConditions[] = "n.type IN ($placeholders)";
        $params = array_merge($params, $types);
    }
    
    // กรองตาม service point
    if ($servicePointId) {
        $whereConditions[] = "(n.service_point_id IS NULL OR n.service_point_id = ?)";
        $params[] = $servicePointId;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Query หลัก
    $sql = "
        SELECT 
            n.notification_id,
            n.type,
            n.title,
            n.message,
            n.priority,
            n.created_at,
            n.expires_at,
            n.metadata,
            nt.type_name,
            nt.icon,
            nt.color,
            sp.point_name as service_point_name
        FROM notifications n
        LEFT JOIN notification_types nt ON n.type = nt.type_code
        LEFT JOIN service_points sp ON n.service_point_id = sp.service_point_id
        WHERE $whereClause
        ORDER BY 
            CASE n.priority 
                WHEN 'urgent' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'normal' THEN 3 
                WHEN 'low' THEN 4 
                ELSE 5 
            END,
            n.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // นับจำนวนทั้งหมด
    $countSql = "
        SELECT COUNT(*) as total
        FROM notifications n
        WHERE $whereClause
    ";
    
    $countParams = array_slice($params, 0, -2); // ลบ limit และ offset
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($countParams);
    $total = $countStmt->fetch()['total'];
    
    // ประมวลผลข้อมูล
    foreach ($notifications as &$notification) {
        // แปลง metadata จาก JSON
        if ($notification['metadata']) {
            $notification['metadata'] = json_decode($notification['metadata'], true);
        }
        
        // จัดรูปแบบวันที่
        $notification['created_at_formatted'] = date('d/m/Y H:i', strtotime($notification['created_at']));
        $notification['expires_at_formatted'] = $notification['expires_at'] ? 
            date('d/m/Y H:i', strtotime($notification['expires_at'])) : null;
        
        // กำหนดสีและไอคอนเริ่มต้น
        if (!$notification['color']) {
            switch ($notification['priority']) {
                case 'urgent':
                    $notification['color'] = '#dc3545';
                    break;
                case 'high':
                    $notification['color'] = '#fd7e14';
                    break;
                case 'normal':
                    $notification['color'] = '#0d6efd';
                    break;
                default:
                    $notification['color'] = '#6c757d';
            }
        }
        
        if (!$notification['icon']) {
            switch ($notification['type']) {
                case 'queue_called':
                    $notification['icon'] = 'fas fa-bullhorn';
                    break;
                case 'announcement':
                    $notification['icon'] = 'fas fa-megaphone';
                    break;
                case 'system_alert':
                    $notification['icon'] = 'fas fa-exclamation-triangle';
                    break;
                default:
                    $notification['icon'] = 'fas fa-bell';
            }
        }
    }
    
    // ส่งผลลัพธ์
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'total' => (int)$total,
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total
        ],
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    error_log("Public notifications error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลการแจ้งเตือน',
        'error' => $e->getMessage()
    ]);
}
?>
