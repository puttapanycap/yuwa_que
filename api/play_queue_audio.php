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
    
    $audioRepeatCount = intval(getSetting('audio_repeat_count', '1'));
    $soundNotificationBefore = getSetting('sound_notification_before', '1');
    $audioProvider = getSetting('audio_provider', 'files');
    $ttsUsed = 0;

    // Prepare audio container
    $audioFiles = [];
    $missingWords = [];

    // Try Google TTS provider first when selected
    if ($audioProvider === 'google_tts' || $audioProvider === 'gtts') {
        $engine = ($audioProvider === 'gtts') ? 'gtts' : 'google';
        $ttsResult = generateGoogleTTS($message, $engine);
        if (is_array($ttsResult) && !empty($ttsResult['path'])) {
            $audioFiles = [ $ttsResult['path'] ];
            $missingWords = [];
            $ttsUsed = 1;
        }
    }

    // Helper to fetch audio file path
    $getFile = function($type, $name) use ($db) {
        $stmt = $db->prepare("SELECT file_path FROM audio_files WHERE audio_type = ? AND display_name = ? LIMIT 1");
        $stmt->execute([$type, $name]);
        $row = $stmt->fetch();
        return $row['file_path'] ?? null;
    };

    // Build service point audio with fallback when files are missing
    $getServicePointAudio = function($name) use ($getFile, $servicePointId, &$missingWords) {
        $files = [];
        $missing = false;
        foreach (preg_split('/\s+/u', trim($name)) as $word) {
            if ($word === '') {
                continue;
            }
            if (preg_match('/^\d+$/u', $word)) {
                foreach (preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) as $char) {
                    $digitFile = $getFile('queue_number', $char);
                    if ($digitFile) {
                        $files[] = $digitFile;
                    } else {
                        $missing = true;
                        $missingWords[] = $char;
                    }
                }
            } else {
                $file = $getFile('message', $word);
                if ($file) {
                    $files[] = $file;
                } else {
                    $missing = true;
                    $missingWords[] = $word;
                }
            }
        }

        if ($missing) {
            $files = [];
            $generic = $getFile('message', 'จุดบริการ');
            if ($generic) {
                $files[] = $generic;
            }
            if ($servicePointId) {
                foreach (preg_split('//u', (string)$servicePointId, -1, PREG_SPLIT_NO_EMPTY) as $char) {
                    $digitFile = $getFile('queue_number', $char);
                    if ($digitFile) {
                        $files[] = $digitFile;
                    }
                }
            }
        }

        return $files;
    };

    if ($ttsUsed === 0) {
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
                            $audioFiles = array_merge($audioFiles, $getServicePointAudio($servicePointName));
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
        'repeat_count' => $audioRepeatCount,
        'notification_before' => in_array((string)$soundNotificationBefore, ['1','true','on','yes'], true),
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
    // Split the queue number into individual characters
    $characters = str_split($queueNumber);
    $spacedNumber = implode(' ', $characters);
    
    // Replace the queue number in the message
    return str_replace($queueNumber, $spacedNumber, $message);
}

/**
 * Generates a TTS audio file via Google TTS using the helper Python script.
 * Returns an associative array with 'path' (relative web path) on success, or null on failure.
 *
 * @param string $text The text to synthesize
 * @return array|null
 */
function generateGoogleTTS($text, $engine = 'google')
{
    try {
        $rootDir = realpath(__DIR__ . '/..');
        $relDir = 'storage/tts';
        $outDir = $rootDir . DIRECTORY_SEPARATOR . $relDir;
        if (!is_dir($outDir)) {
            @mkdir($outDir, 0777, true);
        }

        // Settings with sensible defaults
        $language = getSetting('tts_language', 'th-TH');
        $voiceName = getSetting('tts_voice', 'th-TH-Standard-A');
        $rate = getSetting('tts_rate', '1.0');
        $pitch = getSetting('tts_pitch', '0.0');
        $format = getSetting('tts_format', 'mp3');
        $python = getSetting('python_path', 'python');

        // Cached file name based on content and voice params
        $hash = md5($text . '|' . $language . '|' . $voiceName . '|' . $rate . '|' . $pitch . '|' . $format);
        $fileName = $hash . '.' . $format;
        $outAbs = $outDir . DIRECTORY_SEPARATOR . $fileName;
        $outRel = $relDir . '/' . $fileName;

        if (file_exists($outAbs) && filesize($outAbs) > 0) {
            return [ 'path' => $outRel ];
        }

        $script = $rootDir . DIRECTORY_SEPARATOR . 'voice.py';
        if (!file_exists($script)) {
            return null;
        }

        $cmd = escapeshellcmd($python) . ' ' .
            escapeshellarg($script) . ' --text ' . escapeshellarg($text) .
            ' --lang ' . escapeshellarg($language) .
            ' --voice ' . escapeshellarg($voiceName) .
            ' --rate ' . escapeshellarg((string)$rate) .
            ' --pitch ' . escapeshellarg((string)$pitch) .
            ' --audio-format ' . escapeshellarg($format) .
            ' --engine ' . escapeshellarg($engine) .
            ' --out ' . escapeshellarg($outAbs);

        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $process = @proc_open($cmd, $descriptorspec, $pipes, $rootDir);
        if (!is_resource($process)) {
            return null;
        }
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);

        // Parse JSON result
        $data = @json_decode($stdout, true);
        if ($status === 0 && is_array($data) && !empty($data['success']) && file_exists($outAbs)) {
            return [ 'path' => $outRel ];
        }

        // Fallback if file exists after non-zero exit
        if (file_exists($outAbs) && filesize($outAbs) > 0) {
            return [ 'path' => $outRel ];
        }

        return null;
    } catch (Exception $e) {
        return null;
    }
}
?>
