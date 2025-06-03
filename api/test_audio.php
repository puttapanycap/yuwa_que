<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

try {
    // Get TTS settings
    $ttsEnabled = getSetting('tts_enabled', '0');
    $ttsApiUrl = getSetting('tts_api_url', '');
    $ttsProvider = getSetting('tts_provider', 'google');
    $queueCallTemplate = getSetting('queue_call_template', 'หมายเลข {queue_number} เชิญที่ {service_point_name}');
    
    if ($ttsEnabled != '1') {
        throw new Exception('ระบบเสียงถูกปิดใช้งาน');
    }
    
    // Create test message
    $testMessage = str_replace(
        ['{queue_number}', '{service_point_name}', '{patient_name}'],
        ['A001', 'ห้องตรวจ 1', 'คุณทดสอบ'],
        $queueCallTemplate
    );
    
    // Log test audio call
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO audio_call_history (staff_id, message, tts_used, audio_status)
        VALUES (?, ?, 1, 'played')
    ");
    $stmt->execute([$_SESSION['staff_id'], 'ทดสอบระบบเสียง: ' . $testMessage]);
    
    logActivity('ทดสอบระบบเสียงเรียกคิว');
    
    echo json_encode([
        'success' => true,
        'message' => $testMessage,
        'tts_provider' => $ttsProvider,
        'tts_api_url' => $ttsApiUrl
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
