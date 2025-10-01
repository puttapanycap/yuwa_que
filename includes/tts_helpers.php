<?php
/**
 * Helper functions for working with custom TTS API services.
 */

require_once __DIR__ . '/../config/config.php';

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

        $jsonEncoded = json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonEncoded === false) {
            throw new Exception('ไม่สามารถเตรียมข้อความสำหรับส่งไปยัง API ได้');
        }
        $jsonWithoutQuotes = substr($jsonEncoded, 1, -1); // remove surrounding quotes

        $command = $commandTemplate;

        $envExport = 'TTS_TEXT_JSON_SAFE=' . escapeshellarg($jsonWithoutQuotes)
            . ' TTS_TEXT_RAW=' . escapeshellarg($text);

        $placeholders = [$placeholder, $altPlaceholder];

        foreach ($placeholders as $ph) {
            if ($ph === '') {
                continue;
            }

            // Handle placeholders that live inside single quoted strings
            $command = preg_replace_callback(
                "/'([^']*?)" . preg_quote($ph, '/') . "([^']*?)'/",
                function (array $matches): string {
                    $segments = [];

                    if ($matches[1] !== '') {
                        $segments[] = "'" . $matches[1] . "'";
                    }

                    $segments[] = '"$TTS_TEXT_JSON_SAFE"';

                    if ($matches[2] !== '') {
                        $segments[] = "'" . $matches[2] . "'";
                    }

                    $filtered = [];
                    foreach ($segments as $segment) {
                        if ($segment !== "''") {
                            $filtered[] = $segment;
                        }
                    }

                    return implode('', $filtered);
                },
                $command
            );

            // Handle placeholders wrapped in double quotes
            $command = preg_replace(
                '/"' . preg_quote($ph, '/') . '"/',
                '"$TTS_TEXT_JSON_SAFE"',
                $command
            );

            // Replace any remaining occurrences with a shell-safe default
            $command = str_replace($ph, '"$TTS_TEXT_JSON_SAFE"', $command);
        }

        if (strpos($command, $placeholder) !== false || strpos($command, $altPlaceholder) !== false) {
            throw new Exception('ไม่สามารถเตรียมคำสั่งเรียก API ได้: พบตัวแปร {{_TEXT_TO_SPECH_}} ที่ไม่ได้ถูกแทนค่า');
        }

        $command = $envExport . ' ' . $command;

        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $process = @proc_open($command, $descriptorspec, $pipes, ROOT_PATH);
        if (!is_resource($process)) {
            throw new Exception('ไม่สามารถเรียกใช้คำสั่งทดสอบ API ได้');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($stdout === false) {
            $stdout = '';
        }
        if ($stderr === false) {
            $stderr = '';
        }

        $audioData = $stdout;
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

        $outputDir = ROOT_PATH . '/storage/tts';
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
