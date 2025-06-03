<?php
require_once '../config/config.php';
require_once 'notification_center.php';

header('Content-Type: application/json');

// ตรวจสอบการเข้าสู่ระบบ
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'ไม่ได้เข้าสู่ระบบ']);
    exit;
}

try {
    $staffId = $_SESSION['staff_id'];
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
    
    $result = getUserNotifications($staffId, [
        'limit' => $limit,
        'offset' => $offset,
        'unread_only' => $unreadOnly
    ]);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
?>
