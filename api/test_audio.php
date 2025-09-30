<?php
require_once '../config/config.php';
require_once '../includes/tts_helpers.php';
requireLogin();

header('Content-Type: application/json');

try {
    $text = trim($_POST['text'] ?? 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ ห้องตรวจ 1');
    if ($text === '') {
        throw new Exception('กรุณาระบุข้อความสำหรับทดสอบเสียง');
    }

    $serviceId = isset($_POST['service_id']) ? (int)$_POST['service_id'] : null;
    $result = synthesizeTtsAudio($text, $serviceId);

    $db = getDB();
    $stmt = $db->prepare(
        "INSERT INTO audio_call_history (staff_id, message, tts_used, audio_status) VALUES (?, ?, 1, 'played')"
    );
    $stmt->execute([
        $_SESSION['staff_id'] ?? null,
        'ทดสอบระบบเสียง: ' . $text
    ]);

    logActivity('ทดสอบระบบเสียงเรียกคิว');

    echo json_encode([
        'success' => true,
        'message' => $text,
        'audio_path' => $result['path'],
        'bytes' => $result['bytes'],
        'service_id' => $result['service_id']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
