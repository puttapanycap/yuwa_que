<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? null;
$servicePointId = $_POST['service_point_id'] ?? null;
$queueId = $_POST['queue_id'] ?? null;

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
            // Get next queue in line
            $stmt = $db->prepare("
                SELECT queue_id 
                FROM queues 
                WHERE current_service_point_id = ? 
                AND current_status = 'waiting'
                ORDER BY priority_level DESC, creation_time ASC
                LIMIT 1
            ");
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
            break;
            
        case 'recall':
            if (!$queueId) {
                throw new Exception('Queue ID required');
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    // Update queue status
    $stmt = $db->prepare("
        UPDATE queues 
        SET current_status = 'called', 
            last_called_time = CURRENT_TIMESTAMP,
            called_count = called_count + 1
        WHERE queue_id = ?
    ");
    $stmt->execute([$queueId]);
    
    // Log the action
    $stmt = $db->prepare("
        INSERT INTO service_flow_history (queue_id, from_service_point_id, to_service_point_id, staff_id, action)
        VALUES (?, ?, ?, ?, 'called')
    ");
    $stmt->execute([$queueId, $servicePointId, $servicePointId, $_SESSION['staff_id']]);
    
    // Get queue info for response
    $stmt = $db->prepare("SELECT queue_number FROM queues WHERE queue_id = ?");
    $stmt->execute([$queueId]);
    $queue = $stmt->fetch();
    
    $db->commit();
    
    logActivity("เรียกคิว {$queue['queue_number']} ที่จุดบริการ ID: {$servicePointId}");
    
    echo json_encode([
        'success' => true,
        'message' => 'เรียกคิวสำเร็จ',
        'queue_number' => $queue['queue_number']
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
