<?php
/**
 * Helper functions for working with custom TTS API services.
 */

require_once dirname(__DIR__, 2) . '/config/config.php';

if (!function_exists('getTtsService')) {
    /**
     * Fetch a TTS service by id or the active one when id is null.
     *
     * @param int|null $serviceId
     * @return array|null
     */
    function getTtsService(?int $serviceId = null): ?array
    {
        $db = getDB();

        if ($serviceId !== null) {
            $stmt = $db->prepare('SELECT * FROM tts_api_services WHERE service_id = ?');
            $stmt->execute([$serviceId]);
            $service = $stmt->fetch();
            return $service ?: null;
        }

        $stmt = $db->query('SELECT * FROM tts_api_services WHERE is_active = 1 ORDER BY updated_at DESC LIMIT 1');
        $service = $stmt->fetch();
        return $service ?: null;
    }
}

if (!function_exists('synthesizeTtsAudio')) {
    /**
     * Generate audio using the configured TTS API service.
     *
     * @param string   $text       Text to synthesize
     * @param int|null $serviceId  Optional specific service to use
     *
     * @throws Exception When service configuration is missing or command fails
     *
     * @return array{path:string, bytes:int, service_id:int}
     */
    function synthesizeTtsAudio(string $text, ?int $serviceId = null): array
    {
        $service = getTtsService($serviceId);
        if (!$service) {
            throw new Exception('ยังไม่ได้ตั้งค่า API Service สำหรับเสียงเรียกคิว');
        }

        $commandTemplate = (string)($service['curl_command'] ?? '');
        if ($commandTemplate === '') {
            throw new Exception('คำสั่งเรียก API ของบริการเสียงว่างเปล่า');
        }

        $placeholder = '{{_TEXT_TO_SPECH_}}';
        $altPlaceholder = '{{_TEXT_TO_SPEECH_}}';
        if (mb_strpos($commandTemplate, $placeholder) === false && mb_strpos($commandTemplate, $altPlaceholder) === false) {
            throw new Exception('คำสั่งต้องมีตัวแปร {{_TEXT_TO_SPECH_}} สำหรับแทนข้อความที่จะสังเคราะห์เสียง');
        }

        // Use cURL directly for better error handling and performance
        $ch = curl_init();
        
        // Extract URL from the original command template, handling optional quotes
        preg_match('/[\'"]?(https?:\/\/[^\s\'"]+)[\'"]?/', $commandTemplate, $matches);
        $url = $matches[1] ?? '';

        if (empty($url)) {
            throw new Exception('ไม่พบ URL ในคำสั่ง cURL');
        }

        // Extract headers from the original command template
        preg_match_all('/-H [\'"]([^\'"]+)[\'"]/', $commandTemplate, $headerMatches);
        $headers = $headerMatches[1] ?? [];

        // Build the JSON payload directly for the FastAPI service to ensure it's always valid.
        $payload = [
            'text' => $text,
            'lang' => 'th',
            'slow' => false,
            'tld' => 'com'
        ];

        $postData = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($postData === false) {
            throw new Exception('ไม่สามารถสร้าง JSON payload สำหรับส่งไปยัง TTS API ได้');
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 seconds timeout
        curl_setopt($ch, CURLOPT_FAILONERROR, true); // Fail on HTTP status code >= 400

        $audioData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        curl_close($ch);

        if ($audioData === false) {
            throw new Exception("cURL Error ({$httpCode}): {$curlError}");
        }
        $trimmedAudio = trim($audioData);
        if ($trimmedAudio !== '' && ($trimmedAudio[0] === '{' || $trimmedAudio[0] === '[')) {
            $decoded = json_decode($trimmedAudio, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                if (!empty($decoded['audio_base64'])) {
                    $audioData = base64_decode((string)$decoded['audio_base64']);
                } elseif (!empty($decoded['audio_data'])) {
                    $audioData = base64_decode((string)$decoded['audio_data']);
                } elseif (!empty($decoded['data']['audio_base64'])) {
                    $audioData = base64_decode((string)$decoded['data']['audio_base64']);
                } elseif (!empty($decoded['audio_url'])) {
                    $audioUrl = (string)$decoded['audio_url'];
                    $audioData = @file_get_contents($audioUrl);
                    if ($audioData === false) {
                        throw new Exception('ไม่สามารถดาวน์โหลดไฟล์เสียงจาก URL ที่ได้รับ: ' . $audioUrl);
                    }
                } elseif (!empty($decoded['success']) && !empty($decoded['message'])) {
                    throw new Exception((string)$decoded['message']);
                }
            }
        }

        if ($audioData === '' || $audioData === false) {
            $errorMessage = trim($stderr);
            if ($errorMessage === '' && $exitCode !== 0) {
                $errorMessage = 'คำสั่ง API คืนค่ารหัสสถานะ ' . $exitCode;
            }
            throw new Exception($errorMessage !== '' ? $errorMessage : 'API Service ไม่ได้ส่งข้อมูลเสียงกลับมา');
        }

        $outputDir = ROOT_PATH . '/public/storage/tts';
        if (!is_dir($outputDir)) {
            @mkdir($outputDir, 0777, true);
        }

        $hash = md5($service['service_id'] . '|' . $text);
        $fileName = 'tts_service_' . $service['service_id'] . '_' . $hash . '.mp3';
        $absolutePath = $outputDir . '/' . $fileName;
        $relativePath = 'storage/tts/' . $fileName;

        if (@file_put_contents($absolutePath, $audioData) === false) {
            throw new Exception('ไม่สามารถบันทึกไฟล์เสียงที่ได้จาก API');
        }

        return [
            'path' => $relativePath,
            'bytes' => strlen($audioData),
            'service_id' => (int)$service['service_id']
        ];
    }
}
