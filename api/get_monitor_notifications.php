<?php
require_once '../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $servicePointId = isset($_GET['service_point_id']) ? (int)$_GET['service_point_id'] : null;
    $lastCheck = isset($_GET['last_check']) ? $_GET['last_check'] : null;
    
    $db = getDB();
    
    // Query สำหรับ active notifications
    $sql = "
    SELECT 
        n.notification_id,
        n.notification_type as type,
        n.title,
        n.message,
        n.priority,
        n.created_at,
        n.metadata,
        n.expires_at,
        n.auto_dismiss_after,
        n.color,
        n.icon,
        sp.point_name as service_point_name
    FROM notifications n
    LEFT JOIN service_points sp ON n.service_point_id = sp.service_point_id
    WHERE n.notification_type IN ('queue_called', 'announcement', 'system_alert', 'emergency')
    AND (n.service_point_id IS NULL OR n.service_point_id = ? OR ? IS NULL)
    " . ($lastCheck ? "AND n.created_at > ?" : "") . "
    ORDER BY 
        CASE n.priority 
            WHEN 'urgent' THEN 1 
            WHEN 'high' THEN 2 
            WHEN 'normal' THEN 3 
            WHEN 'low' THEN 4 
            ELSE 5 
        END,
        n.created_at DESC
    LIMIT 20
";
    
    // ปรับ parameters
    $params = [$servicePointId, $servicePointId];
    if ($lastCheck) {
        $params[] = $lastCheck;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ประมวลผลข้อมูล
    foreach ($notifications as &$notification) {
        // แปลง metadata
        if ($notification['metadata']) {
            $notification['metadata'] = json_decode($notification['metadata'], true);
        }
        
        // คำนวณเวลาที่เหลือ
        if ($notification['expires_at']) {
            $expiresAt = new DateTime($notification['expires_at']);
            $now = new DateTime();
            $diff = $now->diff($expiresAt);
            
            if ($expiresAt > $now) {
                $notification['time_remaining'] = [
                    'total_seconds' => ($expiresAt->getTimestamp() - $now->getTimestamp()),
                    'formatted' => $diff->format('%H:%I:%S')
                ];
            } else {
                $notification['time_remaining'] = null;
            }
        }
        
        // กำหนดการแสดงผล
        $notification['display_duration'] = $notification['auto_dismiss_after'] ?: 5000; // 5 วินาที default
        
        // จัดรูปแบบข้อความ
        $notification['formatted_message'] = nl2br(htmlspecialchars($notification['message']));
        
        // กำหนดสีและไอคอน
        if (!$notification['color']) {
            switch ($notification['priority']) {
                case 'urgent':
                    $notification['color'] = '#dc3545';
                    $notification['bg_color'] = 'rgba(220, 53, 69, 0.1)';
                    break;
                case 'high':
                    $notification['color'] = '#fd7e14';
                    $notification['bg_color'] = 'rgba(253, 126, 20, 0.1)';
                    break;
                case 'normal':
                    $notification['color'] = '#0d6efd';
                    $notification['bg_color'] = 'rgba(13, 110, 253, 0.1)';
                    break;
                default:
                    $notification['color'] = '#6c757d';
                    $notification['bg_color'] = 'rgba(108, 117, 125, 0.1)';
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
                case 'emergency':
                    $notification['icon'] = 'fas fa-exclamation-circle';
                    break;
                default:
                    $notification['icon'] = 'fas fa-bell';
            }
        }
    }
    
    // ดึงข้อมูลสถิติเพิ่มเติม

    // นับ notification ที่ยังไม่หมดอายุ
    $statsSql = "
        SELECT 
            COUNT(*) as total_active,
            SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_count,
            SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_count
        FROM notifications 
        WHERE is_public = 1 
        AND is_active = 1 
        AND (expires_at IS NULL OR expires_at > NOW())
        " . ($servicePointId ? "AND (service_point_id IS NULL OR service_point_id = $servicePointId)" : "");
    
    $statsStmt = $db->query($statsSql);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'stats' => [
            'total_active' => (int)$stats['total_active'],
            'urgent_count' => (int)$stats['urgent_count'],
            'high_count' => (int)$stats['high_count']
        ],
        'service_point_id' => $servicePointId,
        'timestamp' => date('c'),
        'server_time' => time()
    ]);
    
} catch (Exception $e) {
    error_log("Monitor notifications error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลการแจ้งเตือน',
        'error' => $e->getMessage()
    ]);
}
?>
