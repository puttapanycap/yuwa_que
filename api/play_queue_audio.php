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
                q.current_service_point_id,
                sp.point_name as service_point_name
            FROM queues q
            LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
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
        
        // Get call format from settings
        $callFormat = getSetting('tts_call_format', 'ขอเชิญหมายเลข {queue_number} ที่ {service_point} ครับ');
        
        // Process service point name for speech (e.g., "Room1" -> "Room 1")
        $servicePointName = $queueData['service_point_name'] ?? 'จุดบริการ';
        $processedServicePointName = preg_replace('/([^\d])(\d)/', '$1 $2', $servicePointName);

        // Generate message from template
        $message = str_replace(
            [
                '{queue_number}',
                '{service_point}',
            ],
            [
                $queueData['queue_number'],
                $processedServicePointName,
            ],
            $callFormat
        );
        
        // แปลงหมายเลขคิวให้อ่านแยกตัว
        $message = processQueueNumberForSpeech($message, $queueData['queue_number']);
        
    } else {
        $message = $customMessage;
        $queueData = null;
    }
    
    $audioRepeatCount = intval(getSetting('audio_repeat_count', '1'));
    $soundNotificationBefore = getSetting('sound_notification_before', '1');

    // Build audio file sequence from uploaded files
    $audioFiles = [];

    // Helper to fetch audio file path
    $getFile = function($type, $name) use ($db) {
        $stmt = $db->prepare("SELECT file_path FROM audio_files WHERE audio_type = ? AND display_name = ? LIMIT 1");
        $stmt->execute([$type, $name]);
        $row = $stmt->fetch();
        return $row['file_path'] ?? null;
    };

    // Prefix "หมายเลข"
    $audioFiles[] = $getFile('message', 'หมายเลข');
    // Queue number characters
    if ($queueData && isset($queueData['queue_number'])) {
        foreach (preg_split('//u', $queueData['queue_number'], -1, PREG_SPLIT_NO_EMPTY) as $char) {
            $audioFiles[] = $getFile('queue_number', $char);
        }
    }
    // Middle phrase "เชิญที่"
    $audioFiles[] = $getFile('message', 'เชิญที่');
    // Service point name
    if ($servicePointName) {
        $audioFiles[] = $getFile('service_point', $servicePointName);
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
