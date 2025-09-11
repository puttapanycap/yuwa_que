<?php
require_once '../config/config.php';
requireLogin();
require_once __DIR__ . '/notification_center.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? null;
$servicePointId = $_POST['service_point_id'] ?? null;
$queueId = $_POST['queue_id'] ?? null;

// CSRF protection
$csrf = $_POST[CSRF_TOKEN_NAME] ?? ($_POST['csrf_token'] ?? null);
if (!verifyCSRFToken($csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

if (!$action || !$servicePointId) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Verify access to service point
$hasAccess = false;
foreach ($_SESSION['service_points'] as $sp) {
    if ($sp['service_point_id'] == $servicePointId) {
        $hasAccess = true;
        break;
    }
}

if (!$hasAccess) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();

    switch ($action) {
        case 'call_next':
            // Ensure no current queue is already being served at this service point
            $stmt = $db->prepare("SELECT queue_id FROM queues WHERE current_service_point_id = ? AND current_status IN ('called','processing') LIMIT 1");
            $stmt->execute([$servicePointId]);
            if ($stmt->fetch()) {
                throw new Exception('มีคิวที่กำลังให้บริการอยู่แล้ว');
            }
            // Get next queue in line with row lock to prevent race conditions
            $stmt = $db->prepare("\n                SELECT queue_id\n                FROM queues\n                WHERE current_service_point_id = ?\n                AND current_status = 'waiting'\n                ORDER BY priority_level DESC, creation_time ASC\n                LIMIT 1\n                FOR UPDATE\n            ");
            $stmt->execute([$servicePointId]);
            $nextQueue = $stmt->fetch();

            if (!$nextQueue) {
                throw new Exception('ไม่มีคิวรอ');
            }

            $queueId = $nextQueue['queue_id'];
            break;

        case 'call_specific':
            if (!$queueId) {
                throw new Exception('Queue ID required');
            }
            // Ensure no current queue is already being served at this service point
            $stmt = $db->prepare("SELECT queue_id FROM queues WHERE current_service_point_id = ? AND current_status IN ('called','processing') LIMIT 1");
            $stmt->execute([$servicePointId]);
            if ($stmt->fetch()) {
                throw new Exception('มีคิวที่กำลังให้บริการอยู่แล้ว');
            }
            // Validate queue belongs to this service point and is waiting
            $stmt = $db->prepare("SELECT current_status, current_service_point_id FROM queues WHERE queue_id = ? FOR UPDATE");
            $stmt->execute([$queueId]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new Exception('ไม่พบคิว');
            }
            if ((int)$row['current_service_point_id'] !== (int)$servicePointId) {
                throw new Exception('คิวนี้ไม่ได้อยู่ที่จุดบริการนี้');
            }
            if ($row['current_status'] !== 'waiting') {
                throw new Exception('คิวนี้ไม่พร้อมสำหรับการเรียก');
            }
            break;

        case 'recall':
            if (!$queueId) {
                throw new Exception('Queue ID required');
            }
            // Validate queue belongs to this service point and is currently called/processing
            $stmt = $db->prepare("SELECT current_status, current_service_point_id FROM queues WHERE queue_id = ? FOR UPDATE");
            $stmt->execute([$queueId]);
            $row = $stmt->fetch();
            if (!$row) {
                throw new Exception('ไม่พบคิว');
            }
            if ((int)$row['current_service_point_id'] !== (int)$servicePointId) {
                throw new Exception('คิวนี้ไม่ได้อยู่ที่จุดบริการนี้');
            }
            if (!in_array($row['current_status'], ['called','processing'])) {
                throw new Exception('สามารถเรียกซ้ำได้เฉพาะคิวที่กำลังให้บริการ');
            }
            break;

        default:
            throw new Exception('Invalid action');
    }

    // Update queue status
    if ($action === 'recall') {
        $stmt = $db->prepare("\n            UPDATE queues\n            SET current_status = 'called',\n                last_called_time = CURRENT_TIMESTAMP,\n                called_count = called_count + 1\n            WHERE queue_id = ? AND current_status IN ('called','processing')\n        ");
        $stmt->execute([$queueId]);
    } else {
        $stmt = $db->prepare("\n            UPDATE queues\n            SET current_status = 'called',\n                last_called_time = CURRENT_TIMESTAMP,\n                called_count = called_count + 1\n            WHERE queue_id = ? AND current_status = 'waiting'\n        ");
        $stmt->execute([$queueId]);
    }
    if ($stmt->rowCount() === 0) {
        throw new Exception('อัปเดตสถานะคิวไม่สำเร็จ อาจมีการเปลี่ยนแปลงแล้ว');
    }

    // Log the action
    $actionLog = ($action === 'recall') ? 'recalled' : 'called';
    $stmt = $db->prepare("\n        INSERT INTO service_flow_history (queue_id, from_service_point_id, to_service_point_id, staff_id, action)\n        VALUES (?, ?, ?, ?, ?)\n    ");
    $stmt->execute([$queueId, $servicePointId, $servicePointId, $_SESSION['staff_id'], $actionLog]);

    // Get queue info for response
    $stmt = $db->prepare("SELECT q.queue_number, TRIM(CONCAT(COALESCE(sp.point_label,''),' ', sp.point_name)) AS service_point_name FROM queues q LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id WHERE q.queue_id = ?");
    $stmt->execute([$queueId]);
    $queue = $stmt->fetch();

    $db->commit();

    // Create notification for monitors (best-effort)
    $notifTitle = 'เรียกคิวแล้ว';
    $notifMsg = 'หมายเลข ' . ($queue['queue_number'] ?? '') . ' เชิญที่ ' . ($queue['service_point_name'] ?? 'จุดบริการ');
    try {
        createNotification('queue_called', $notifTitle, $notifMsg, [
            'service_point_id' => (int)$servicePointId,
            'queue_id' => (int)$queueId,
            'priority' => 'high'
        ]);
    } catch (Exception $e) {
        // ignore notification failures
    }

    logActivity("เรียกคิว {$queue['queue_number']} ที่จุดบริการ ID: {$servicePointId}");

    echo json_encode([
        'success' => true,
        'message' => 'เรียกคิวสำเร็จ',
        'queue_number' => $queue['queue_number']
    ]);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
