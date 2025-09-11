<?php
require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $callId = $_POST['call_id'] ?? null;
    $status = $_POST['status'] ?? null;
    
    if (!$callId || !$status) {
        throw new Exception('ข้อมูลไม่ครบถ้วน');
    }
    
    $allowedStatuses = ['pending', 'played', 'failed'];
    if (!in_array($status, $allowedStatuses)) {
        throw new Exception('สถานะไม่ถูกต้อง');
    }
    
    $db = getDB();
    $stmt = $db->prepare("UPDATE audio_call_history SET audio_status = ? WHERE call_id = ?");
    $stmt->execute([$status, $callId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'อัพเดทสถานะเรียบร้อยแล้ว'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
