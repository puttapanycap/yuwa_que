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
require_once '../includes/tts_helpers.php';

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
                TRIM(CONCAT(COALESCE(sp.point_label,''),' ', sp.point_name)) AS service_point_name,
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

        // Ensure service point placeholder exists
        if (strpos($templateText, '{service_point_name}') === false && strpos($templateText, '{service_point}') === false) {
            $templateText = rtrim($templateText) . ' ที่ {service_point_name}';
        }

        // Process service point name for speech (e.g., "Room1" -> "Room 1")
        $servicePointName = $queueData['service_point_name'] ?? '';
        if (!$servicePointName && $servicePointId) {
            $stmt = $db->prepare("SELECT TRIM(CONCAT(COALESCE(point_label,''),' ', point_name)) FROM service_points WHERE service_point_id = ?");
            $stmt->execute([$servicePointId]);
            $servicePointName = $stmt->fetchColumn() ?: '';
        }
        if (!$servicePointName) {
            $servicePointName = 'จุดบริการ ' . ($servicePointId ?? '');
        }
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
        $templateText = (string) $customMessage;
    }
    
    $ttsUsed = 0;
    $audioFiles = [];
    $missingWords = [];

    $ttsResult = synthesizeTtsAudio($message);
    if (!empty($ttsResult['path'])) {
        $audioFiles[] = $ttsResult['path'];
        $ttsUsed = 1;
    }

    // Log audio call
    $stmt = $db->prepare(
        "INSERT INTO audio_call_history (queue_id, service_point_id, staff_id, message, tts_used, audio_status)
        VALUES (?, ?, ?, ?, ?, 'pending')"
    );
    $stmt->execute([
        $queueId,
        $servicePointId,
        $_SESSION['staff_id'] ?? null,
        $message,
        $ttsUsed
    ]);
    
    $callId = $db->lastInsertId();
    
    // Prepare response
    $response = [
        'success' => true,
        'call_id' => $callId,
        'audio_files' => $audioFiles,
        'repeat_count' => 1,
        'notification_before' => false,
        'queue_data' => $queueData,
        'missing_words' => $missingWords
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
    // Split the queue number into individual characters, supporting multi-byte (Unicode) characters
    $characters = preg_split('//u', $queueNumber, -1, PREG_SPLIT_NO_EMPTY);
    $spacedNumber = implode(',', $characters);

    // Replace the queue number in the message
    return str_replace($queueNumber, $spacedNumber, $message);
}

?>
