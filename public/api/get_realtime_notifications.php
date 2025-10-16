<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once 'notification_center.php';

header('Content-Type: application/json');

// ตรวจสอบการเข้าสู่ระบบ
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'ไม่ได้เข้าสู่ระบบ']);
    exit;
}

try {
    $notifications = getRealtimeNotifications();
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
?>
