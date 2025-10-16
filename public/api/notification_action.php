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

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// ตรวจสอบ CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

try {
    $staffId = $_SESSION['staff_id'];
    $action = $_POST['action'] ?? '';
    $notificationId = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
    
    switch ($action) {
        case 'mark_read':
            if ($notificationId) {
                $result = markNotificationAsRead($notificationId, $staffId);
            } else {
                $result = ['success' => false, 'message' => 'ไม่ระบุ ID การแจ้งเตือน'];
            }
            break;
            
        case 'mark_all_read':
            $result = markAllNotificationsAsRead($staffId);
            break;
            
        case 'dismiss':
            if ($notificationId) {
                $result = dismissNotification($notificationId, $staffId);
            } else {
                $result = ['success' => false, 'message' => 'ไม่ระบุ ID การแจ้งเตือน'];
            }
            break;
            
        case 'dismiss_all':
            $result = dismissAllNotifications($staffId);
            break;
            
        case 'save_preferences':
            $preferences = isset($_POST['preferences']) ? json_decode($_POST['preferences'], true) : [];
            if (!empty($preferences)) {
                $result = saveNotificationPreferences($staffId, $preferences);
            } else {
                $result = ['success' => false, 'message' => 'ไม่มีข้อมูลการตั้งค่า'];
            }
            break;
            
        case 'send_message':
            $toStaffId = isset($_POST['to_staff_id']) ? (int)$_POST['to_staff_id'] : 0;
            $message = $_POST['message'] ?? '';
            
            if ($toStaffId && $message) {
                $result = sendStaffMessage($staffId, $toStaffId, $message);
            } else {
                $result = ['success' => false, 'message' => 'ไม่ระบุผู้รับหรือข้อความ'];
            }
            break;
            
        default:
            $result = ['success' => false, 'message' => 'ไม่รู้จักการกระทำ'];
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
?>
