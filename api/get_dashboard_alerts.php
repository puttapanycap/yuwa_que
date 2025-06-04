<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

try {
    $db = getDB();
    
    // Get active alerts
    $stmt = $db->prepare("
        SELECT * FROM dashboard_alerts 
        WHERE is_active = 1 
        AND (expires_at IS NULL OR expires_at > NOW())
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $alerts = $stmt->fetchAll();
    
    // Check for system alerts
    $systemAlerts = [];
    
    // Check for long waiting queues
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM queues 
        WHERE current_status = 'waiting' 
        AND TIMESTAMPDIFF(MINUTE, creation_time, NOW()) > 60
    ");
    $stmt->execute();
    $longWaitCount = $stmt->fetch()['count'];
    
    if ($longWaitCount > 0) {
        $systemAlerts[] = [
            'alert_type' => 'warning',
            'alert_title' => 'คิวรอนาน',
            'alert_message' => "มีคิวรอเกิน 60 นาที จำนวน {$longWaitCount} คิว",
            'alert_id' => 'long_wait_' . time()
        ];
    }
    
    // Check for inactive service points during business hours
    $currentHour = date('H');
    if ($currentHour >= 8 && $currentHour <= 17) {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM service_points sp
            LEFT JOIN queues q ON sp.service_point_id = q.current_service_point_id 
                AND q.current_status IN ('called', 'processing')
            WHERE sp.is_active = 1 AND q.queue_id IS NULL
        ");
        $stmt->execute();
        $inactiveCount = $stmt->fetch()['count'];
        
        if ($inactiveCount > 3) {
            $systemAlerts[] = [
                'alert_type' => 'info',
                'alert_title' => 'จุดบริการว่าง',
                'alert_message' => "มีจุดบริการว่าง {$inactiveCount} จุด ในช่วงเวลาทำการ",
                'alert_id' => 'inactive_points_' . time()
            ];
        }
    }
    
    echo json_encode(array_merge($alerts, $systemAlerts));
    
} catch (Exception $e) {
    Logger::error("Failed to get dashboard alerts: " . $e->getMessage());
    echo json_encode([]);
}
?>
