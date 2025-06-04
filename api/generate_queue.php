<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$queueTypeId = $_POST['queue_type_id'] ?? null;
$idCardNumber = $_POST['id_card_number'] ?? null;

if (!$queueTypeId || !$idCardNumber) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

// Validate ID card number
if (!preg_match('/^\d{13}$/', $idCardNumber)) {
    echo json_encode(['success' => false, 'message' => 'เลขบัตรประจำตัวประชาชนไม่ถูกต้อง']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Get queue type info
    $stmt = $db->prepare("SELECT type_name, prefix_char FROM queue_types WHERE queue_type_id = ? AND is_active = 1");
    $stmt->execute([$queueTypeId]);
    $queueType = $stmt->fetch();
    
    if (!$queueType) {
        throw new Exception('ประเภทคิวไม่ถูกต้อง');
    }
    
    // Generate queue number
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM queues WHERE DATE(creation_time) = ? AND queue_type_id = ?");
    $stmt->execute([$today, $queueTypeId]);
    $count = $stmt->fetch()['count'];
    
    $queueNumber = $queueType['prefix_char'] . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    
    // Get first service point (screening)
    $stmt = $db->prepare("SELECT service_point_id FROM service_points WHERE position_key = 'SCREENING_01' AND is_active = 1");
    $stmt->execute();
    $firstServicePoint = $stmt->fetch();
    
    if (!$firstServicePoint) {
        throw new Exception('ไม่พบจุดบริการเริ่มต้น');
    }
    
    // Insert/update patient info first
    $stmt = $db->prepare("INSERT INTO patients (id_card_number) VALUES (?) ON DUPLICATE KEY UPDATE id_card_number = id_card_number");
    $stmt->execute([$idCardNumber]);
    
    // Insert queue
    $stmt = $db->prepare("INSERT INTO queues (queue_number, queue_type_id, patient_id_card_number, current_service_point_id, kiosk_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$queueNumber, $queueTypeId, $idCardNumber, $firstServicePoint['service_point_id'], 'KIOSK_01']);
    
    $queueId = $db->lastInsertId();
    
    // Log queue creation in service flow history
    // Note: staff_id is NULL for kiosk-generated queues
    $stmt = $db->prepare("
        INSERT INTO service_flow_history 
        (queue_id, from_service_point_id, to_service_point_id, staff_id, action, notes, timestamp) 
        VALUES (?, NULL, ?, NULL, 'created', 'สร้างคิวจาก Kiosk', NOW())
    ");
    $result = $stmt->execute([$queueId, $firstServicePoint['service_point_id']]);
    
    if (!$result) {
        throw new Exception('ไม่สามารถบันทึก Service Flow History ได้');
    }
    
    // Log activity in audit logs
    $stmt = $db->prepare("
        INSERT INTO audit_logs (staff_id, action_description, ip_address, user_agent, timestamp) 
        VALUES (NULL, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        "สร้างคิว {$queueNumber} จาก Kiosk - บัตรประชาชน: " . substr($idCardNumber, 0, 4) . "****" . substr($idCardNumber, -4),
        $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'queue' => [
            'queue_id' => $queueId,
            'queue_number' => $queueNumber,
            'queue_type_id' => $queueTypeId,
            'service_point_name' => 'จุดคัดกรอง',
            'creation_time' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    
    // Log error for debugging
    error_log("Generate Queue Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'debug' => [
            'queue_type_id' => $queueTypeId,
            'id_card_number' => substr($idCardNumber, 0, 4) . "****" . substr($idCardNumber, -4),
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>
