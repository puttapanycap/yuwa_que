<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$queueId = $_POST['queue_id'] ?? null;

if (!$queueId) {
    echo json_encode(['success' => false, 'message' => 'Missing queue_id parameter']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Get queue information
    $stmt = $db->prepare("
        SELECT q.*, qt.type_name, sp.point_name as service_point_name
        FROM queues q
        LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
        LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
        WHERE q.queue_id = ?
    ");
    $stmt->execute([$queueId]);
    $queue = $stmt->fetch();
    
    if (!$queue) {
        throw new Exception('ไม่พบข้อมูลคิว');
    }
    
    // Check if service flow history already exists
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM service_flow_history WHERE queue_id = ?");
    $stmt->execute([$queueId]);
    $existingCount = $stmt->fetch()['count'];
    
    if ($existingCount > 0) {
        throw new Exception('คิวนี้มี Service Flow History อยู่แล้ว');
    }
    
    // Create initial service flow history entry
    $stmt = $db->prepare("
        INSERT INTO service_flow_history 
        (queue_id, from_service_point_id, to_service_point_id, staff_id, action, notes, timestamp) 
        VALUES (?, NULL, ?, ?, 'created', ?, ?)
    ");
    $stmt->execute([
        $queueId,
        $queue['current_service_point_id'],
        $_SESSION['staff_id'],
        'แก้ไข Service Flow โดย ' . $_SESSION['full_name'],
        $queue['creation_time']
    ]);
    
    // Log the fix action
    logActivity("แก้ไข Service Flow สำหรับคิว {$queue['queue_number']}");
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'แก้ไข Service Flow สำเร็จ'
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
