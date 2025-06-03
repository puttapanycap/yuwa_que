<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? null;
$queueId = $_POST['queue_id'] ?? null;

if (!$action || !$queueId) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Get current queue info
    $stmt = $db->prepare("SELECT * FROM queues WHERE queue_id = ?");
    $stmt->execute([$queueId]);
    $queue = $stmt->fetch();
    
    if (!$queue) {
        throw new Exception('ไม่พบคิว');
    }
    
    switch ($action) {
        case 'hold':
            $stmt = $db->prepare("UPDATE queues SET current_status = 'waiting' WHERE queue_id = ?");
            $stmt->execute([$queueId]);
            
            $stmt = $db->prepare("
                INSERT INTO service_flow_history (queue_id, from_service_point_id, to_service_point_id, staff_id, action, notes)
                VALUES (?, ?, ?, ?, 'hold', 'พักคิวชั่วคราว')
            ");
            $stmt->execute([$queueId, $queue['current_service_point_id'], $queue['current_service_point_id'], $_SESSION['staff_id']]);
            
            logActivity("พักคิว {$queue['queue_number']}");
            break;
            
        case 'complete':
            $nextServicePointId = $_POST['next_service_point_id'] ?? null;
            $notes = $_POST['notes'] ?? '';
            
            if ($nextServicePointId) {
                // Forward to next service point
                $stmt = $db->prepare("
                    UPDATE queues 
                    SET current_status = 'waiting', 
                        current_service_point_id = ?
                    WHERE queue_id = ?
                ");
                $stmt->execute([$nextServicePointId, $queueId]);
                
                $stmt = $db->prepare("
                    INSERT INTO service_flow_history (queue_id, from_service_point_id, to_service_point_id, staff_id, action, notes)
                    VALUES (?, ?, ?, ?, 'forwarded', ?)
                ");
                $stmt->execute([$queueId, $queue['current_service_point_id'], $nextServicePointId, $_SESSION['staff_id'], $notes]);
                
                logActivity("ส่งต่อคิว {$queue['queue_number']} ไปยังจุดบริการ ID: {$nextServicePointId}");
            } else {
                // Complete the queue
                $stmt = $db->prepare("UPDATE queues SET current_status = 'completed' WHERE queue_id = ?");
                $stmt->execute([$queueId]);
                
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
            
            $stmt = $db->prepare("UPDATE queues SET current_status = 'cancelled' WHERE queue_id = ?");
            $stmt->execute([$queueId]);
            
            $stmt = $db->prepare("
                INSERT INTO service_flow_history (queue_id, from_service_point_id, to_service_point_id, staff_id, action, notes)
                VALUES (?, ?, NULL, ?, 'cancelled', ?)
            ");
            $stmt->execute([$queueId, $queue['current_service_point_id'], $_SESSION['staff_id'], $cancelNote]);
            
            logActivity("ยกเลิกคิว {$queue['queue_number']} - {$reason}");
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
