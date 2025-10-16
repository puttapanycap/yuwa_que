<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once 'notification_center.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$queueId = isset($_POST['queue_id']) ? (int)$_POST['queue_id'] : null;
$servicePointId = isset($_POST['service_point_id']) ? (int)$_POST['service_point_id'] : null;
$missingFiles = isset($_POST['missing_files']) ? json_decode($_POST['missing_files'], true) : [];

if (empty($missingFiles)) {
    echo json_encode(['success' => false, 'message' => 'No missing files reported']);
    exit;
}

try {
    $fileList = is_array($missingFiles) ? implode(', ', $missingFiles) : strval($missingFiles);
    $title = 'ปัญหาเสียงเรียกคิว';
    $message = 'ไม่สามารถโหลดไฟล์เสียงต่อไปนี้ได้: ' . $fileList;
    if ($queueId) {
        $message .= " (Queue ID: {$queueId})";
    }
    if ($servicePointId) {
        $stmt = getDB()->prepare("SELECT TRIM(CONCAT(COALESCE(point_label,''),' ', point_name)) FROM service_points WHERE service_point_id = ?");
        $stmt->execute([$servicePointId]);
        $spName = $stmt->fetchColumn();
        $message .= " (Service Point: " . ($spName ?: $servicePointId) . ")";
    }

    $result = createNotification('system_alert', $title, $message, [
        'priority' => 'urgent'
    ]);

    echo json_encode(['success' => $result['success'], 'message' => $result['message']]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error reporting audio issue', 'error' => $e->getMessage()]);
}
?>
