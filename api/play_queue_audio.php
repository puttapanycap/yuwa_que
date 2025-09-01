<?php
/**
 * This file handles the audio playback for queue calls.
 *
 * @category Queue
 * @package  Yuwa_Queue
 * @author   Your Name <you@example.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/puttapanycap/yuwa_que
 */

require_once '../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $queueId = $_POST['queue_id'] ?? null;
    $servicePointId = $_POST['service_point_id'] ?? null;
    $customMessage = $_POST['custom_message'] ?? null;
    $templateId = $_POST['template_id'] ?? null;
    
    if (!$queueId && !$customMessage) {
        throw new Exception('ต้องระบุ queue_id หรือ custom_message');
    }
    
    $db = getDB();
    
    // Get queue and service point information
    if ($queueId) {
        $stmt = $db->prepare(
            "
            SELECT
                q.queue_number,
                p.name AS patient_name,
                q.current_service_point_id,
                sp.point_name AS service_point_name,
                sp.voice_template_id
            FROM queues q
            LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
            LEFT JOIN patients p ON q.patient_id_card_number = p.id_card_number
            WHERE q.queue_id = ?
            "
        );
        $stmt->execute([$queueId]);
        $queueData = $stmt->fetch();
        
        if (!$queueData) {
            throw new Exception('ไม่พบข้อมูลคิว');
        }

        if (!$servicePointId) {
            $servicePointId = $queueData['current_service_point_id'];
        }

        // Determine voice template
        if (!$templateId) {
            $templateId = $queueData['voice_template_id'] ?? null;
        }
        if (!$templateId && $servicePointId) {
            $stmt = $db->prepare("SELECT voice_template_id FROM service_points WHERE service_point_id = ?");
            $stmt->execute([$servicePointId]);
            $sp = $stmt->fetch();
            if ($sp && $sp['voice_template_id']) {
                $templateId = $sp['voice_template_id'];
            }
        }
        if (!$templateId) {
            $stmt = $db->query("SELECT template_id FROM voice_templates WHERE is_default = 1 LIMIT 1");
            $templateId = $stmt->fetchColumn();
        }

        $stmt = $db->prepare("SELECT template_text FROM voice_templates WHERE template_id = ?");
        $stmt->execute([$templateId]);
        $templateText = $stmt->fetchColumn();
        if (!$templateText) {
            $templateText = 'ขอเชิญหมายเลข {queue_number} ที่ {service_point_name} ครับ';
        }

        // Process service point name for speech (e.g., "Room1" -> "Room 1")
        $servicePointName = $queueData['service_point_name'] ?? 'จุดบริการ';
        $processedServicePointName = preg_replace('/([^\d])(\d)/', '$1 $2', $servicePointName);

        // Generate message from template text
        $placeholders = [
            '{queue_number}' => $queueData['queue_number'],
            '{service_point}' => $processedServicePointName,
            '{service_point_name}' => $processedServicePointName,
            '{patient_name}' => $queueData['patient_name'] ?? ''
        ];
        $message = str_replace(array_keys($placeholders), array_values($placeholders), $templateText);

        // แปลงหมายเลขคิวให้อ่านแยกตัว
        $message = processQueueNumberForSpeech($message, $queueData['queue_number']);
        
    } else {
        $message = $customMessage;
        $queueData = null;
        $servicePointName = '';
    }
    
    $audioRepeatCount = intval(getSetting('audio_repeat_count', '1'));
    $soundNotificationBefore = getSetting('sound_notification_before', '1');

    // Build audio file sequence from template
    $audioFiles = [];

    // Helper to fetch audio file path
    $getFile = function($type, $name) use ($db) {
        $stmt = $db->prepare("SELECT file_path FROM audio_files WHERE audio_type = ? AND display_name = ? LIMIT 1");
        $stmt->execute([$type, $name]);
        $row = $stmt->fetch();
        return $row['file_path'] ?? null;
    };

    $segments = preg_split('/({[^}]+})/', $templateText, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    foreach ($segments as $segment) {
        if (preg_match('/^{([^}]+)}$/', $segment, $m)) {
            switch ($m[1]) {
                case 'queue_number':
                    if ($queueData && isset($queueData['queue_number'])) {
                        foreach (preg_split('//u', $queueData['queue_number'], -1, PREG_SPLIT_NO_EMPTY) as $char) {
                            $audioFiles[] = $getFile('queue_number', $char);
                        }
                    }
                    break;
                case 'service_point':
                case 'service_point_name':
                    if ($servicePointName) {
                        $audioFiles[] = $getFile('service_point', $servicePointName);
                    }
                    break;
                case 'patient_name':
                    if ($queueData && !empty($queueData['patient_name'])) {
                        $audioFiles[] = $getFile('patient_name', $queueData['patient_name']);
                    }
                    break;
            }
        } else {
            foreach (preg_split('/\s+/u', trim($segment)) as $word) {
                if ($word !== '') {
                    $audioFiles[] = $getFile('message', $word);
                }
            }
        }
    }

    // Remove missing files
    $audioFiles = array_values(array_filter($audioFiles));

    // Log audio call
    $stmt = $db->prepare(
        "INSERT INTO audio_call_history (queue_id, service_point_id, staff_id, message, tts_used, audio_status)
        VALUES (?, ?, ?, ?, 0, 'pending')"
    );
    $stmt->execute([
        $queueId,
        $servicePointId,
        $_SESSION['staff_id'] ?? null,
        $message
    ]);
    
    $callId = $db->lastInsertId();
    
    // Prepare response
    $response = [
        'success' => true,
        'call_id' => $callId,
        'audio_files' => $audioFiles,
        'repeat_count' => $audioRepeatCount,
        'notification_before' => $soundNotificationBefore == '1',
        'queue_data' => $queueData
    ];
    
    logActivity("เล่นเสียงเรียกคิว: {$message}", $_SESSION['staff_id'] ?? null);
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(
        [
            'success' => false,
            'message' => $e->getMessage()
        ]
    );
}

/**
 * Processes a queue number for speech by separating all characters.
 *
 * @param string $message     The message to process.
 * @param string $queueNumber The queue number to process.
 *
 * @return string The processed message.
 */
function processQueueNumberForSpeech($message, $queueNumber)
{
    // Split the queue number into individual characters
    $characters = str_split($queueNumber);
    $spacedNumber = implode(' ', $characters);
    
    // Replace the queue number in the message
    return str_replace($queueNumber, $spacedNumber, $message);
}
?>
