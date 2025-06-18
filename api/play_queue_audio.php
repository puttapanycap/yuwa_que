<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $queueId = $_POST['queue_id'] ?? null;
    $servicePointId = $_POST['service_point_id'] ?? null;
    $customMessage = $_POST['custom_message'] ?? null;
    
    if (!$queueId && !$customMessage) {
        throw new Exception('ต้องระบุ queue_id หรือ custom_message');
    }
    
    $db = getDB();
    
    // Get queue and service point information
    if ($queueId) {
        $stmt = $db->prepare("
            SELECT 
                q.queue_number,
                q.patient_name,
                sp.point_name as service_point_name,
                sp.voice_template_id,
                vt.template_text
            FROM queues q
            LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
            LEFT JOIN voice_templates vt ON sp.voice_template_id = vt.template_id
            WHERE q.queue_id = ?
        ");
        $stmt->execute([$queueId]);
        $queueData = $stmt->fetch();
        
        if (!$queueData) {
            throw new Exception('ไม่พบข้อมูลคิว');
        }
        
        // Get default template if service point doesn't have one
        if (!$queueData['template_text']) {
            $stmt = $db->prepare("SELECT template_text FROM voice_templates WHERE is_default = 1 LIMIT 1");
            $stmt->execute();
            $defaultTemplate = $stmt->fetch();
            $queueData['template_text'] = $defaultTemplate['template_text'] ?? 'หมายเลข {queue_number} เชิญที่ {service_point_name}';
        }
        
        // Generate message from template
        $message = str_replace(
            ['{queue_number}', '{service_point_name}', '{patient_name}'],
            [
                $queueData['queue_number'],
                $queueData['service_point_name'] ?? 'จุดบริการ',
                $queueData['patient_name'] ?? ''
            ],
            $queueData['template_text']
        );
        
        // แปลงหมายเลขคิวให้อ่านแยกตัว
        $message = processQueueNumberForSpeech($message, $queueData['queue_number']);
        
    } else {
        $message = $customMessage;
        $queueData = null;
    }
    
    // Check if TTS is enabled
    $ttsEnabled = getSetting('tts_enabled', '0');
    $audioRepeatCount = intval(getSetting('audio_repeat_count', '1'));
    $soundNotificationBefore = getSetting('sound_notification_before', '1');
    
    // Log audio call
    $stmt = $db->prepare("
        INSERT INTO audio_call_history (queue_id, service_point_id, staff_id, message, tts_used, audio_status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([
        $queueId,
        $servicePointId,
        $_SESSION['staff_id'],
        $message,
        $ttsEnabled == '1' ? 1 : 0
    ]);
    
    $callId = $db->lastInsertId();
    
    // Prepare response
    $response = [
        'success' => true,
        'call_id' => $callId,
        'message' => $message,
        'tts_enabled' => $ttsEnabled == '1',
        'repeat_count' => $audioRepeatCount,
        'notification_before' => $soundNotificationBefore == '1',
        'queue_data' => $queueData
    ];
    
    // If TTS is enabled, provide TTS URL
    if ($ttsEnabled == '1') {
        $response['tts_url'] = BASE_URL . '/api/tts_service.php';
        $response['tts_params'] = [
            'text' => $message,
            'language' => getSetting('tts_language', 'th-TH'),
            'voice' => getSetting('tts_voice', 'th-TH-Standard-A'),
            'speed' => floatval(getSetting('tts_speed', '1.0')),
            'pitch' => floatval(getSetting('tts_pitch', '0'))
        ];
    }
    
    logActivity("เล่นเสียงเรียกคิว: {$message}");
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function processQueueNumberForSpeech($message, $queueNumber) {
    // แยกตัวอักษรและตัวเลขในหมายเลขคิว
    if (preg_match('/([A-Z]+)(\d+)/', $queueNumber, $matches)) {
        $letters = $matches[1];
        $numbers = $matches[2];
        
        // แยกตัวอักษร
        $letterArray = str_split($letters);
        $letterText = implode(' ', $letterArray);
        
        // แยกตัวเลข
        $numberArray = str_split($numbers);
        $numberText = implode(' ', $numberArray);
        
        $separatedQueueNumber = $letterText . ' ' . $numberText;
        
        // แทนที่หมายเลขคิวในข้อความ
        $message = str_replace($queueNumber, $separatedQueueNumber, $message);
    }
    
    return $message;
}
?>
