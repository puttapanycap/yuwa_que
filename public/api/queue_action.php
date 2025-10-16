<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? null;
$queueId = $_POST['queue_id'] ?? null;

// CSRF protection
$csrf = $_POST[CSRF_TOKEN_NAME] ?? ($_POST['csrf_token'] ?? null);
if (!verifyCSRFToken($csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

if (!$action || !$queueId) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    ensureAppointmentTables();

    // Get current queue info together with queue type (lock the row to avoid races)
    $stmt = $db->prepare("\n        SELECT q.*, qt.ticket_template, qt.type_name\n        FROM queues q\n        JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id\n        WHERE q.queue_id = ?\n        FOR UPDATE\n    ");
    $stmt->execute([$queueId]);
    $queue = $stmt->fetch();

    if (!$queue) {
        throw new Exception('ไม่พบคิว');
    }

    $isAppointmentTemplate = ($queue['ticket_template'] ?? 'standard') === 'appointment_list';
    
switch ($action) {
        case 'start_processing':
            $servicePointId = $_POST['service_point_id'] ?? null;
            if (!$servicePointId) {
                throw new Exception('Missing service_point_id');
            }
            if ((int)$queue['current_service_point_id'] !== (int)$servicePointId) {
                throw new Exception('คิวนี้ไม่ได้อยู่ที่จุดบริการนี้');
            }
            $stmt = $db->prepare("UPDATE queues SET current_status = 'processing' WHERE queue_id = ? AND current_status = 'called'");
            $stmt->execute([$queueId]);
            if ($stmt->rowCount() === 0) {
                throw new Exception('ไม่สามารถเริ่มให้บริการได้ สถานะต้องเป็น เรียกแล้ว');
            }

            $stmt = $db->prepare("
                INSERT INTO service_flow_history (queue_id, from_service_point_id, to_service_point_id, staff_id, action, notes)
                VALUES (?, ?, ?, ?, 'called', 'เริ่มให้บริการ')
            ");
            $stmt->execute([$queueId, $queue['current_service_point_id'], $queue['current_service_point_id'], $_SESSION['staff_id']]);
            logActivity("เริ่มให้บริการคิว {$queue['queue_number']}");
            if ($isAppointmentTemplate) {
                markAppointmentAsActive($db, (int) $queueId);
            }
            break;

        case 'hold':
            $stmt = $db->prepare("UPDATE queues SET current_status = 'waiting' WHERE queue_id = ? AND current_status IN ('called','processing')");
            $stmt->execute([$queueId]);
            if ($stmt->rowCount() === 0) {
                throw new Exception('ไม่สามารถพักคิวได้ สถานะปัจจุบันไม่ถูกต้อง');
            }

            $stmt = $db->prepare("
                INSERT INTO service_flow_history (queue_id, from_service_point_id, to_service_point_id, staff_id, action, notes)
                VALUES (?, ?, ?, ?, 'hold', 'พักคิวชั่วคราว')
            ");
            $stmt->execute([$queueId, $queue['current_service_point_id'], $queue['current_service_point_id'], $_SESSION['staff_id']]);

            logActivity("พักคิว {$queue['queue_number']}");
            if ($isAppointmentTemplate) {
                resetActiveAppointment($db, (int) $queueId);
            }
            break;

        case 'complete':
            $nextServicePointId = $_POST['next_service_point_id'] ?? null;
            $notes = $_POST['notes'] ?? '';

            if ($isAppointmentTemplate) {
                $overrideNext = $nextServicePointId ? (int) $nextServicePointId : null;
                $result = completeCurrentAppointment($db, $queue, $notes, $_SESSION['staff_id'], $overrideNext);
                $db->commit();

                $response = [
                    'success' => true,
                    'message' => ($result['status'] === 'forwarded') ? 'ส่งต่อคิวสำเร็จ' : 'เสร็จสิ้นคิวแล้ว',
                    'status' => $result['status'],
                ];
                if (!empty($result['next_appointment'])) {
                    $response['next_appointment'] = $result['next_appointment'];
                    $response['next_service_point_id'] = $result['next_appointment']['service_point_id'] ?? null;
                }

                echo json_encode($response);
                return;
            }

            if ($nextServicePointId) {
                $stmt = $db->prepare("
                    UPDATE queues
                    SET current_status = 'waiting',
                        current_service_point_id = ?
                    WHERE queue_id = ? AND current_status IN ('called','processing')
                ");
                $stmt->execute([$nextServicePointId, $queueId]);
                if ($stmt->rowCount() === 0) {
                    throw new Exception('ไม่สามารถส่งต่อคิวได้ สถานะปัจจุบันไม่ถูกต้อง');
                }

                $stmt = $db->prepare("
                    INSERT INTO service_flow_history (queue_id, from_service_point_id, to_service_point_id, staff_id, action, notes)
                    VALUES (?, ?, ?, ?, 'forwarded', ?)
                ");
                $stmt->execute([$queueId, $queue['current_service_point_id'], $nextServicePointId, $_SESSION['staff_id'], $notes]);

                logActivity("ส่งต่อคิว {$queue['queue_number']} ไปยังจุดบริการ ID: {$nextServicePointId}");
            } else {
                $stmt = $db->prepare("UPDATE queues SET current_status = 'completed' WHERE queue_id = ? AND current_status IN ('called','processing','waiting')");
                $stmt->execute([$queueId]);
                if ($stmt->rowCount() === 0) {
                    throw new Exception('ไม่สามารถปิดคิวได้ สถานะปัจจุบันไม่ถูกต้อง');
                }

                $stmt = $db->prepare("
                    INSERT INTO service_flow_history (queue_id, from_service_point_id, to_service_point_id, staff_id, action, notes)
                    VALUES (?, ?, NULL, ?, 'completed', ?)
                ");
                $stmt->execute([$queueId, $queue['current_service_point_id'], $_SESSION['staff_id'], $notes]);

                logActivity("เสร็จสิ้นคิว {$queue['queue_number']}");
            }
            break;

        case 'cancel':
            $reason = $_POST['reason'] ?? '';
            $notes = $_POST['notes'] ?? '';
            $cancelNote = $reason . ($notes ? ' - ' . $notes : '');

            $stmt = $db->prepare("UPDATE queues SET current_status = 'cancelled' WHERE queue_id = ? AND current_status IN ('waiting','called','processing')");
            $stmt->execute([$queueId]);
            if ($stmt->rowCount() === 0) {
                throw new Exception('ไม่สามารถยกเลิกคิวได้ สถานะปัจจุบันไม่ถูกต้อง');
            }

            $stmt = $db->prepare("
                INSERT INTO service_flow_history (queue_id, from_service_point_id, to_service_point_id, staff_id, action, notes)
                VALUES (?, ?, NULL, ?, 'cancelled', ?)
            ");
            $stmt->execute([$queueId, $queue['current_service_point_id'], $_SESSION['staff_id'], $cancelNote]);

            logActivity("ยกเลิกคิว {$queue['queue_number']} - {$reason}");
            if ($isAppointmentTemplate) {
                cancelAppointmentQueue($db, (int) $queueId);
            }
            break;

        default:
            throw new Exception('Invalid action');
    }

    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'ดำเนินการสำเร็จ']);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
