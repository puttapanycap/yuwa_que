<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
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
    ensureAppointmentTables();
    $graceMinutes = getAppointmentReadyGraceMinutes();
    $activeAppointment = null;
    $queueRow = null;

    switch ($action) {
        case 'call_next':
            // Ensure no current queue is already being served at this service point
            $stmt = $db->prepare("SELECT queue_id FROM queues WHERE current_service_point_id = ? AND current_status IN ('called','processing') LIMIT 1");
            $stmt->execute([$servicePointId]);
            if ($stmt->fetch()) {
                throw new Exception('มีคิวที่กำลังให้บริการอยู่แล้ว');
            }
            // Get next queue in line with row lock to prevent race conditions
            $stmt = $db->prepare("\n                SELECT q.queue_id, q.queue_number, q.queue_type_id, q.current_service_point_id, q.current_status, qt.ticket_template\n                FROM queues q\n                JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id\n                WHERE q.current_service_point_id = ?\n                AND q.current_status = 'waiting'\n                AND (\n                    qt.ticket_template <> 'appointment_list'\n                    OR NOT EXISTS (\n                        SELECT 1 FROM appointment_queue_items aqi\n                        WHERE aqi.queue_id = q.queue_id\n                          AND aqi.status = 'pending'\n                    )\n                    OR EXISTS (\n                        SELECT 1 FROM appointment_queue_items aqi\n                        WHERE aqi.queue_id = q.queue_id\n                          AND aqi.status = 'pending'\n                          AND aqi.display_order = (\n                              SELECT MIN(display_order) FROM appointment_queue_items WHERE queue_id = q.queue_id AND status = 'pending'\n                          )\n                          AND (aqi.start_time IS NULL OR aqi.start_time <= DATE_ADD(NOW(), INTERVAL :grace MINUTE))\n                    )\n                )\n                ORDER BY q.priority_level DESC, q.creation_time ASC\n                LIMIT 1\n                FOR UPDATE\n            ");
            $stmt->bindValue(1, $servicePointId, PDO::PARAM_INT);
            $stmt->bindValue(':grace', $graceMinutes, PDO::PARAM_INT);
            $stmt->execute();
            $nextQueue = $stmt->fetch();

            if (!$nextQueue) {
                throw new Exception('ไม่มีคิวรอ');
            }

            $queueId = $nextQueue['queue_id'];
            $queueRow = $nextQueue;
            break;

        case 'call_specific':
            if (!$queueId) {
                throw new Exception('Queue ID required');
            }
            // Ensure no current queue is already being served at this service point
            $stmt = $db->prepare("SELECT queue_id FROM queues WHERE current_service_point_id = ? AND queue_id = ? AND current_status IN ('called','processing') LIMIT 1");
            $stmt->execute([$servicePointId, $queueId]);
            if ($stmt->fetch()) {
                throw new Exception('มีคิวที่กำลังให้บริการอยู่แล้ว');
            }
            // Validate queue belongs to this service point and is waiting
            $stmt = $db->prepare("SELECT q.queue_id, q.queue_number, q.queue_type_id, q.current_service_point_id, q.current_status, qt.ticket_template FROM queues q JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id WHERE q.queue_id = ? FOR UPDATE");
            $stmt->execute([$queueId]);
            $queueRow = $stmt->fetch();
            if (!$queueRow) {
                throw new Exception('ไม่พบคิว');
            }
            if ((int)$queueRow['current_service_point_id'] !== (int)$servicePointId) {
                throw new Exception('คิวนี้ไม่ได้อยู่ที่จุดบริการนี้');
            }
            if ($queueRow['current_status'] !== 'waiting') {
                throw new Exception('คิวนี้ไม่พร้อมสำหรับการเรียก');
            }
            if (($queueRow['ticket_template'] ?? 'standard') === 'appointment_list' && !isQueueReadyForAppointment($db, (int) $queueId, $graceMinutes)) {
                throw new Exception('ยังไม่ถึงเวลานัดหมาย');
            }
            break;

        case 'recall':
            if (!$queueId) {
                throw new Exception('Queue ID required');
            }
            // Validate queue belongs to this service point and is currently called/processing
            $stmt = $db->prepare("SELECT q.queue_id, q.queue_number, q.queue_type_id, q.current_service_point_id, q.current_status, qt.ticket_template FROM queues q JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id WHERE q.queue_id = ? FOR UPDATE");
            $stmt->execute([$queueId]);
            $queueRow = $stmt->fetch();
            if (!$queueRow) {
                throw new Exception('ไม่พบคิว');
            }
            if ((int)$queueRow['current_service_point_id'] !== (int)$servicePointId) {
                throw new Exception('คิวนี้ไม่ได้อยู่ที่จุดบริการนี้');
            }
            if (!in_array($queueRow['current_status'], ['called','processing'])) {
                throw new Exception('สามารถเรียกซ้ำได้เฉพาะคิวที่กำลังให้บริการ');
            }
            break;

        default:
            throw new Exception('Invalid action');
    }

    if (!$queueRow) {
        $stmt = $db->prepare("SELECT q.queue_id, q.queue_number, q.queue_type_id, q.current_service_point_id, q.current_status, qt.ticket_template FROM queues q JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id WHERE q.queue_id = ? FOR UPDATE");
        $stmt->execute([$queueId]);
        $queueRow = $stmt->fetch();
        if (!$queueRow) {
            throw new Exception('ไม่พบคิว');
        }
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

    if (($queueRow['ticket_template'] ?? 'standard') === 'appointment_list') {
        $activeAppointment = markAppointmentAsActive($db, (int) $queueId);
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
        'queue_number' => $queue['queue_number'],
        'appointment' => $activeAppointment
    ]);

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
