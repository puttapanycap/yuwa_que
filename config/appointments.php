<?php
// Appointment scheduling utilities for queue system

if (!function_exists('ensureAppointmentTables')) {
    function ensureAppointmentTables(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        try {
            $db = getDB();

            $db->exec(
                "CREATE TABLE IF NOT EXISTS appointment_queue_items (
                    item_id INT NOT NULL AUTO_INCREMENT,
                    queue_id INT NOT NULL,
                    external_id VARCHAR(100) DEFAULT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT DEFAULT NULL,
                    start_time DATETIME DEFAULT NULL,
                    end_time DATETIME DEFAULT NULL,
                    service_point_id INT DEFAULT NULL,
                    status ENUM('pending','active','completed','skipped') NOT NULL DEFAULT 'pending',
                    display_order INT NOT NULL DEFAULT 0,
                    raw_payload LONGTEXT DEFAULT NULL,
                    activated_at DATETIME DEFAULT NULL,
                    completed_at DATETIME DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (item_id),
                    UNIQUE KEY uq_queue_external (queue_id, external_id),
                    INDEX idx_queue_order (queue_id, display_order),
                    INDEX idx_queue_status (queue_id, status, display_order),
                    INDEX idx_start_time (start_time),
                    CONSTRAINT fk_appointment_queue_items_queue FOREIGN KEY (queue_id) REFERENCES queues(queue_id) ON DELETE CASCADE,
                    CONSTRAINT fk_appointment_queue_items_service_point FOREIGN KEY (service_point_id) REFERENCES service_points(service_point_id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
            );

            $db->exec(
                "CREATE TABLE IF NOT EXISTS appointment_service_point_mappings (
                    mapping_id INT NOT NULL AUTO_INCREMENT,
                    keyword VARCHAR(255) NOT NULL,
                    service_point_id INT NOT NULL,
                    priority INT NOT NULL DEFAULT 0,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (mapping_id),
                    INDEX idx_active_priority (is_active, priority),
                    CONSTRAINT fk_appointment_sp_mapping_service_point FOREIGN KEY (service_point_id) REFERENCES service_points(service_point_id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
            );
        } catch (Exception $e) {
            Logger::error('Failed to ensure appointment tables exist', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

if (!function_exists('getAppointmentReadyGraceMinutes')) {
    function getAppointmentReadyGraceMinutes(): int
    {
        static $grace = null;
        if ($grace === null) {
            $value = (int) getSetting('appointment_ready_grace_minutes', 0);
            $grace = max(0, min(180, $value));
        }
        return $grace;
    }
}

if (!function_exists('appointment_get_timezone')) {
    function appointment_get_timezone(): DateTimeZone
    {
        static $tz = null;
        if ($tz === null) {
            $tz = new DateTimeZone(env('APP_TIMEZONE', 'Asia/Bangkok'));
        }
        return $tz;
    }
}

if (!function_exists('fetchAppointmentsForIdCard')) {
    function fetchAppointmentsForIdCard(string $idCardNumber, ?PDO $db = null, ?DateTimeInterface $targetDate = null): array
    {
        $apiUrl = trim((string) getSetting('appointment_api_url', env('APPOINTMENT_API_URL', 'https://hosapi.ycap.go.th/api/appointment.php')));
        if ($apiUrl === '') {
            Logger::warning('Appointment API URL is not configured');
            return [];
        }

        $queryParam = str_contains($apiUrl, '?') ? '&' : '?';
        $requestUrl = $apiUrl . $queryParam . 'cid=' . urlencode($idCardNumber);

        $timeout = (int) getSetting('appointment_api_timeout', 6);
        $timeout = max(3, min(30, $timeout));

        $ch = curl_init($requestUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        $responseBody = curl_exec($ch);
        if ($responseBody === false) {
            $error = curl_error($ch);
            curl_close($ch);
            Logger::error('Appointment API request failed', [
                'url' => $requestUrl,
                'error' => $error,
            ]);
            return [];
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            Logger::error('Appointment API returned error response', [
                'url' => $requestUrl,
                'status' => $httpCode,
                'body' => $responseBody,
            ]);
            return [];
        }

        $decoded = json_decode($responseBody, true);
        if (!is_array($decoded)) {
            Logger::error('Appointment API returned invalid JSON', [
                'url' => $requestUrl,
                'body' => $responseBody,
            ]);
            return [];
        }

        $appointmentsRaw = [];
        if (isset($decoded['appointments']) && is_array($decoded['appointments'])) {
            $appointmentsRaw = $decoded['appointments'];
        } elseif (isset($decoded['data']) && is_array($decoded['data'])) {
            $appointmentsRaw = $decoded['data'];
        } elseif (isset($decoded['results']) && is_array($decoded['results'])) {
            $appointmentsRaw = $decoded['results'];
        } elseif (array_is_list($decoded)) {
            $appointmentsRaw = $decoded;
        }

        $timezone = appointment_get_timezone();
        $normalized = [];
        foreach ($appointmentsRaw as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $appointment = appointment_normalize_record($raw, $timezone);
            if ($appointment !== null) {
                $normalized[] = $appointment;
            }
        }

        if ($targetDate instanceof DateTimeInterface) {
            $normalized = appointment_filter_by_date($normalized, $targetDate, $timezone);
        }

        if ($db instanceof PDO) {
            $normalized = appointment_assign_service_points($db, $normalized);
        }

        usort($normalized, static function ($a, $b) {
            $aTime = $a['start_time'] ?? '';
            $bTime = $b['start_time'] ?? '';
            return strcmp($aTime, $bTime);
        });

        return $normalized;
    }
}

if (!function_exists('appointment_normalize_record')) {
    function appointment_normalize_record(array $raw, DateTimeZone $tz): ?array
    {
        $titleFields = ['title', 'clinic', 'department', 'service_name', 'appointment_title', 'description'];
        $title = '';
        foreach ($titleFields as $field) {
            if (!empty($raw[$field]) && is_string($raw[$field])) {
                $title = trim($raw[$field]);
                if ($title !== '') {
                    break;
                }
            }
        }
        if ($title === '') {
            $title = 'รายการนัดหมาย';
        }

        $descriptionParts = [];
        foreach (['doctor', 'notes', 'remark', 'location', 'room'] as $field) {
            if (!empty($raw[$field]) && is_string($raw[$field])) {
                $value = trim($raw[$field]);
                if ($value !== '') {
                    $descriptionParts[] = $value;
                }
            }
        }
        $description = !empty($descriptionParts) ? implode("\n", $descriptionParts) : null;

        [$start, $end] = appointment_parse_schedule($raw, $tz);
        if (!$start) {
            return null;
        }

        $externalId = null;
        foreach (['appointment_id', 'id', 'code', 'external_id', 'queue'] as $field) {
            if (!empty($raw[$field])) {
                $externalId = (string) $raw[$field];
                break;
            }
        }

        return [
            'external_id' => $externalId,
            'title' => $title,
            'description' => $description,
            'start_time' => $start->format('Y-m-d H:i:s'),
            'end_time' => $end ? $end->format('Y-m-d H:i:s') : null,
            'raw' => $raw,
        ];
    }
}

if (!function_exists('appointment_parse_schedule')) {
    function appointment_parse_schedule(array $raw, DateTimeZone $tz): array
    {
        $dateCandidates = ['appointment_date', 'date', 'visit_date', 'service_date', 'day', 'appoint_date'];
        $datePart = null;
        foreach ($dateCandidates as $field) {
            if (!empty($raw[$field]) && is_scalar($raw[$field])) {
                $candidate = trim((string) $raw[$field]);
                if ($candidate === '') {
                    continue;
                }
                $timestamp = strtotime($candidate);
                if ($timestamp !== false) {
                    $datePart = date('Y-m-d', $timestamp);
                    break;
                }
            }
        }
        if ($datePart === null) {
            $datePart = (new DateTime('now', $tz))->format('Y-m-d');
        }

        $range = appointment_extract_time_range($raw);

        $start = null;
        $startFields = ['start_time', 'start', 'start_datetime', 'appointment_time', 'datetime', 'time_start', 'begin_time', 'schedule_start'];
        foreach ($startFields as $field) {
            if (!empty($raw[$field])) {
                $candidate = appointment_parse_datetime_value($raw[$field], $datePart, $tz);
                if ($candidate instanceof DateTimeInterface) {
                    $start = $candidate;
                    break;
                }
            }
        }
        if (!$start && $range && isset($range[0])) {
            $candidate = appointment_parse_datetime_value($range[0], $datePart, $tz);
            if ($candidate instanceof DateTimeInterface) {
                $start = $candidate;
            }
        }

        if (!$start) {
            return [null, null];
        }

        $end = null;
        $endFields = ['end_time', 'end', 'finish_time', 'time_end', 'end_datetime', 'schedule_end'];
        foreach ($endFields as $field) {
            if (!empty($raw[$field])) {
                $candidate = appointment_parse_datetime_value($raw[$field], $datePart, $tz);
                if ($candidate instanceof DateTimeInterface) {
                    $end = $candidate;
                    break;
                }
            }
        }
        if (!$end && $range && isset($range[1])) {
            $candidate = appointment_parse_datetime_value($range[1], $datePart, $tz);
            if ($candidate instanceof DateTimeInterface) {
                $end = $candidate;
            }
        }
        if (!$end && !empty($raw['duration_minutes']) && is_numeric($raw['duration_minutes'])) {
            $durationMinutes = max(1, (int) $raw['duration_minutes']);
            $end = (clone $start)->modify('+' . $durationMinutes . ' minutes');
        }
        if ($end && $end <= $start) {
            $end = (clone $start)->modify('+1 hour');
        }

        return [$start, $end];
    }
}

if (!function_exists('appointment_extract_time_range')) {
    function appointment_extract_time_range(array $raw): ?array
    {
        $rangeFields = ['time_range', 'appointment_time', 'time', 'schedule', 'time_text'];
        foreach ($rangeFields as $field) {
            if (!empty($raw[$field]) && is_string($raw[$field])) {
                $value = trim($raw[$field]);
                if ($value === '') {
                    continue;
                }
                if (str_contains($value, 'T')) {
                    continue;
                }
                if (preg_match('/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/', $value, $matches)) {
                    return [$matches[1], $matches[2]];
                }
            }
        }
        return null;
    }
}

if (!function_exists('appointment_parse_datetime_value')) {
    function appointment_parse_datetime_value($value, string $datePart, DateTimeZone $tz): ?DateTimeInterface
    {
        if ($value === null) {
            return null;
        }
        if (is_numeric($value)) {
            $timestamp = (int) $value;
            return (new DateTime('@' . $timestamp))->setTimezone($tz);
        }
        if (!is_scalar($value)) {
            return null;
        }
        $string = trim((string) $value);
        if ($string === '') {
            return null;
        }

        if (preg_match('/^\d{1,2}:\d{2}$/', $string)) {
            $string = $datePart . ' ' . $string;
        }

        if (!str_contains($string, 'T') && preg_match('/^\d{4}-\d{2}-\d{2}$/', $string)) {
            $string .= ' 00:00:00';
        }

        $timestamp = strtotime($string);
        if ($timestamp === false) {
            return null;
        }

        return (new DateTime('@' . $timestamp))->setTimezone($tz);
    }
}

if (!function_exists('appointment_filter_by_date')) {
    function appointment_filter_by_date(array $appointments, DateTimeInterface $date, DateTimeZone $tz): array
    {
        $target = (new DateTime($date->format('Y-m-d'), $tz))->format('Y-m-d');
        return array_values(array_filter($appointments, static function ($appointment) use ($target) {
            if (empty($appointment['start_time'])) {
                return false;
            }
            return strncmp($appointment['start_time'], $target, 10) === 0;
        }));
    }
}

if (!function_exists('appointment_assign_service_points')) {
    function appointment_assign_service_points(PDO $db, array $appointments): array
    {
        $catalog = appointment_get_service_point_catalog($db);
        $mappings = appointment_get_service_point_mappings($db);

        foreach ($appointments as &$appointment) {
            $servicePointId = appointment_detect_service_point_id($appointment, $catalog, $mappings);
            if ($servicePointId !== null) {
                $servicePoint = $catalog['by_id'][$servicePointId] ?? null;
                if ($servicePoint) {
                    $appointment['service_point_id'] = (int) $servicePointId;
                    $appointment['service_point_name'] = appointment_format_service_point_name($servicePoint);
                } else {
                    $appointment['service_point_id'] = null;
                    $appointment['service_point_name'] = null;
                }
            } else {
                $appointment['service_point_id'] = $appointment['service_point_id'] ?? null;
                $appointment['service_point_name'] = $appointment['service_point_name'] ?? null;
            }
        }
        unset($appointment);

        return $appointments;
    }
}

if (!function_exists('appointment_format_service_point_name')) {
    function appointment_format_service_point_name(array $servicePoint): string
    {
        $label = trim((string) ($servicePoint['point_label'] ?? ''));
        $name = trim((string) ($servicePoint['point_name'] ?? ''));
        return trim($label . ' ' . $name);
    }
}

if (!function_exists('appointment_get_service_point_catalog')) {
    function appointment_get_service_point_catalog(PDO $db): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $stmt = $db->query('SELECT service_point_id, point_name, point_label, position_key FROM service_points WHERE is_active = 1');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $byId = [];
        $byPosition = [];
        $byName = [];
        foreach ($rows as $row) {
            $id = (int) $row['service_point_id'];
            $byId[$id] = $row;
            if (!empty($row['position_key'])) {
                $byPosition[mb_strtolower($row['position_key'])] = $row;
            }
            foreach (['point_name', 'point_label'] as $field) {
                if (!empty($row[$field])) {
                    $lower = mb_strtolower($row[$field]);
                    $byName[$lower] = $row;
                }
            }
        }

        $cache = [
            'by_id' => $byId,
            'by_position' => $byPosition,
            'by_name' => $byName,
        ];
        return $cache;
    }
}

if (!function_exists('appointment_get_service_point_mappings')) {
    function appointment_get_service_point_mappings(PDO $db): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $stmt = $db->prepare('SELECT keyword, service_point_id FROM appointment_service_point_mappings WHERE is_active = 1 ORDER BY priority DESC, mapping_id ASC');
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['keyword'] = mb_strtolower(trim((string) $row['keyword']));
            $row['service_point_id'] = (int) $row['service_point_id'];
        }
        unset($row);
        $cache = $rows;
        return $cache;
    }
}

if (!function_exists('appointment_detect_service_point_id')) {
    function appointment_detect_service_point_id(array $appointment, array $catalog, array $mappings): ?int
    {
        $raw = $appointment['raw'] ?? [];
        if (is_array($raw)) {
            foreach (['service_point_id', 'servicePointId'] as $field) {
                if (isset($raw[$field]) && is_numeric($raw[$field])) {
                    return (int) $raw[$field];
                }
            }
            foreach (['service_point_key', 'position_key', 'station'] as $field) {
                if (!empty($raw[$field]) && is_string($raw[$field])) {
                    $key = mb_strtolower(trim($raw[$field]));
                    if (isset($catalog['by_position'][$key])) {
                        return (int) $catalog['by_position'][$key]['service_point_id'];
                    }
                }
            }
            foreach (['service_point_name', 'location', 'room', 'clinic', 'department'] as $field) {
                if (!empty($raw[$field]) && is_string($raw[$field])) {
                    $lower = mb_strtolower(trim($raw[$field]));
                    foreach ($catalog['by_name'] as $name => $servicePoint) {
                        if ($name !== '' && (str_contains($lower, $name) || str_contains($name, $lower))) {
                            return (int) $servicePoint['service_point_id'];
                        }
                    }
                }
            }
        }

        $haystack = [];
        foreach (['title', 'description'] as $field) {
            if (!empty($appointment[$field]) && is_string($appointment[$field])) {
                $haystack[] = mb_strtolower($appointment[$field]);
            }
        }
        if (is_array($raw)) {
            foreach ($raw as $value) {
                if (is_string($value)) {
                    $haystack[] = mb_strtolower($value);
                }
            }
        }
        $combined = implode(' ', $haystack);

        foreach ($mappings as $mapping) {
            if ($mapping['keyword'] === '') {
                continue;
            }
            if (mb_strpos($combined, $mapping['keyword']) !== false) {
                return $mapping['service_point_id'];
            }
        }

        return null;
    }
}

if (!function_exists('saveQueueAppointments')) {
    function saveQueueAppointments(PDO $db, int $queueId, array $appointments): void
    {
        $stmt = $db->prepare('DELETE FROM appointment_queue_items WHERE queue_id = ?');
        $stmt->execute([$queueId]);

        if (empty($appointments)) {
            return;
        }

        $insert = $db->prepare('INSERT INTO appointment_queue_items (queue_id, external_id, title, description, start_time, end_time, service_point_id, status, display_order, raw_payload) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

        $order = 1;
        foreach ($appointments as $appointment) {
            $rawJson = null;
            if (isset($appointment['raw']) && is_array($appointment['raw'])) {
                $rawJson = json_encode($appointment['raw'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            $insert->execute([
                $queueId,
                $appointment['external_id'] ?? null,
                $appointment['title'] ?? 'รายการนัดหมาย',
                $appointment['description'] ?? null,
                $appointment['start_time'] ?? null,
                $appointment['end_time'] ?? null,
                $appointment['service_point_id'] ?? null,
                'pending',
                $order++,
                $rawJson,
            ]);
        }
    }
}

if (!function_exists('getQueueAppointments')) {
    function getQueueAppointments(PDO $db, int $queueId): array
    {
        $stmt = $db->prepare('SELECT aqi.*, sp.point_name, sp.point_label FROM appointment_queue_items aqi LEFT JOIN service_points sp ON aqi.service_point_id = sp.service_point_id WHERE aqi.queue_id = ? ORDER BY aqi.display_order ASC');
        $stmt->execute([$queueId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(static function ($row) {
            return appointment_format_row_for_response($row);
        }, $rows);
    }
}

if (!function_exists('getActiveOrNextAppointment')) {
    function getActiveOrNextAppointment(PDO $db, int $queueId): ?array
    {
        $stmt = $db->prepare("SELECT aqi.*, sp.point_name, sp.point_label FROM appointment_queue_items aqi LEFT JOIN service_points sp ON aqi.service_point_id = sp.service_point_id WHERE aqi.queue_id = ? ORDER BY CASE aqi.status WHEN 'active' THEN 0 WHEN 'pending' THEN 1 ELSE 2 END, aqi.display_order ASC LIMIT 1");
        $stmt->execute([$queueId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        return appointment_format_row_for_response($row);
    }
}

if (!function_exists('isQueueReadyForAppointment')) {
    function isQueueReadyForAppointment(PDO $db, int $queueId, ?int $graceMinutes = null): bool
    {
        $appointment = getActiveOrNextAppointment($db, $queueId);
        if ($appointment === null) {
            return true;
        }
        if ($appointment['status'] === 'active') {
            return true;
        }
        if (empty($appointment['start_time'])) {
            return true;
        }
        $grace = $graceMinutes ?? getAppointmentReadyGraceMinutes();
        $threshold = strtotime($appointment['start_time']);
        if ($threshold === false) {
            return true;
        }
        return $threshold <= (time() + ($grace * 60));
    }
}

if (!function_exists('markAppointmentAsActive')) {
    function markAppointmentAsActive(PDO $db, int $queueId): ?array
    {
        $appointment = getActiveOrNextAppointment($db, $queueId);
        if (!$appointment || $appointment['status'] === 'active') {
            return $appointment;
        }
        $stmt = $db->prepare("UPDATE appointment_queue_items SET status = 'active', activated_at = NOW() WHERE item_id = ? AND status = 'pending'");
        $stmt->execute([$appointment['item_id']]);
        return getActiveOrNextAppointment($db, $queueId);
    }
}

if (!function_exists('resetActiveAppointment')) {
    function resetActiveAppointment(PDO $db, int $queueId): void
    {
        $stmt = $db->prepare("UPDATE appointment_queue_items SET status = 'pending', activated_at = NULL WHERE queue_id = ? AND status = 'active'");
        $stmt->execute([$queueId]);
    }
}

if (!function_exists('completeCurrentAppointment')) {
    function completeCurrentAppointment(PDO $db, array $queue, ?string $notes, int $staffId, ?int $overrideNextServicePoint = null): array
    {
        $queueId = (int) $queue['queue_id'];
        $currentServicePointId = (int) ($queue['current_service_point_id'] ?? 0);

        $currentAppointment = getActiveOrNextAppointment($db, $queueId);
        if ($currentAppointment && $currentAppointment['status'] !== 'completed') {
            $stmt = $db->prepare("UPDATE appointment_queue_items SET status = 'completed', completed_at = NOW() WHERE item_id = ?");
            $stmt->execute([$currentAppointment['item_id']]);
        }

        $nextAppointment = appointment_get_next_pending($db, $queueId);
        if ($overrideNextServicePoint !== null) {
            $servicePointId = $overrideNextServicePoint;
        } elseif ($nextAppointment) {
            $servicePointId = (int) ($nextAppointment['service_point_id'] ?? 0);
        } else {
            $servicePointId = 0;
        }

        if ($nextAppointment && $servicePointId) {
            $stmt = $db->prepare("UPDATE queues SET current_status = 'waiting', current_service_point_id = ?, updated_at = NOW() WHERE queue_id = ?");
            $stmt->execute([$servicePointId, $queueId]);

            $historyStmt = $db->prepare("INSERT INTO service_flow_history (queue_id, from_service_point_id, to_service_point_id, staff_id, action, notes) VALUES (?, ?, ?, ?, 'forwarded', ?)");
            $historyStmt->execute([
                $queueId,
                $currentServicePointId ?: null,
                $servicePointId,
                $staffId,
                $notes,
            ]);

            return [
                'status' => 'forwarded',
                'next_appointment' => appointment_format_row_for_response($nextAppointment),
            ];
        }

        $stmt = $db->prepare("UPDATE queues SET current_status = 'completed', updated_at = NOW() WHERE queue_id = ?");
        $stmt->execute([$queueId]);

        $historyStmt = $db->prepare("INSERT INTO service_flow_history (queue_id, from_service_point_id, to_service_point_id, staff_id, action, notes) VALUES (?, ?, NULL, ?, 'completed', ?)");
        $historyStmt->execute([
            $queueId,
            $currentServicePointId ?: null,
            $staffId,
            $notes,
        ]);

        return [
            'status' => 'completed',
            'next_appointment' => null,
        ];
    }
}

if (!function_exists('appointment_get_next_pending')) {
    function appointment_get_next_pending(PDO $db, int $queueId): ?array
    {
        $stmt = $db->prepare("SELECT aqi.*, sp.point_name, sp.point_label FROM appointment_queue_items aqi LEFT JOIN service_points sp ON aqi.service_point_id = sp.service_point_id WHERE aqi.queue_id = ? AND aqi.status = 'pending' ORDER BY aqi.display_order ASC LIMIT 1");
        $stmt->execute([$queueId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        return appointment_format_row_for_response($row);
    }
}

if (!function_exists('cancelAppointmentQueue')) {
    function cancelAppointmentQueue(PDO $db, int $queueId): void
    {
        $stmt = $db->prepare("UPDATE appointment_queue_items SET status = 'skipped', completed_at = NOW() WHERE queue_id = ? AND status IN ('pending', 'active')");
        $stmt->execute([$queueId]);
    }
}

if (!function_exists('attachAppointmentContext')) {
    function attachAppointmentContext(PDO $db, array &$queues): void
    {
        if (empty($queues)) {
            return;
        }
        $ids = [];
        foreach ($queues as $queue) {
            if (isset($queue['queue_id'])) {
                $ids[(int) $queue['queue_id']] = true;
            }
        }
        if (empty($ids)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("SELECT aqi.queue_id, aqi.*, sp.point_name, sp.point_label FROM appointment_queue_items aqi LEFT JOIN service_points sp ON aqi.service_point_id = sp.service_point_id WHERE aqi.queue_id IN ($placeholders) AND aqi.status IN ('active','pending') ORDER BY aqi.queue_id, CASE aqi.status WHEN 'active' THEN 0 ELSE 1 END, aqi.display_order ASC");
        $stmt->execute(array_keys($ids));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $row) {
            $queueId = (int) $row['queue_id'];
            if (!isset($map[$queueId])) {
                $map[$queueId] = appointment_format_row_for_response($row);
            }
        }
        foreach ($queues as &$queue) {
            $qid = (int) ($queue['queue_id'] ?? 0);
            if (isset($map[$qid])) {
                $queue['appointment'] = $map[$qid];
            }
        }
        unset($queue);
    }
}

if (!function_exists('appointment_format_row_for_response')) {
    function appointment_format_row_for_response(array $row): array
    {
        $display = appointment_format_service_point_name($row);
        $startIso = null;
        if (!empty($row['start_time'])) {
            $startIso = date(DATE_ATOM, strtotime($row['start_time']));
        }
        $endIso = null;
        if (!empty($row['end_time'])) {
            $endIso = date(DATE_ATOM, strtotime($row['end_time']));
        }
        return [
            'item_id' => (int) ($row['item_id'] ?? 0),
            'queue_id' => (int) ($row['queue_id'] ?? 0),
            'title' => $row['title'] ?? 'รายการนัดหมาย',
            'description' => $row['description'] ?? null,
            'start_time' => $row['start_time'] ?? null,
            'end_time' => $row['end_time'] ?? null,
            'start_time_iso' => $startIso,
            'end_time_iso' => $endIso,
            'service_point_id' => isset($row['service_point_id']) ? (int) $row['service_point_id'] : null,
            'service_point_name' => $display,
            'status' => $row['status'] ?? 'pending',
            'display_order' => (int) ($row['display_order'] ?? 0),
            'time_window' => appointment_build_time_window($row),
        ];
    }
}

if (!function_exists('appointment_build_time_window')) {
    function appointment_build_time_window(array $row): string
    {
        $start = $row['start_time'] ?? null;
        $end = $row['end_time'] ?? null;
        if (!$start && !$end) {
            return '';
        }
        $fmtStart = $start ? date('H:i', strtotime($start)) : '';
        $fmtEnd = $end ? date('H:i', strtotime($end)) : '';
        if ($fmtStart && $fmtEnd) {
            return $fmtStart . ' - ' . $fmtEnd;
        }
        return $fmtStart ?: $fmtEnd;
    }
}

if (!function_exists('appointment_get_time_window_for_ticket')) {
    function appointment_get_time_window_for_ticket(array $appointment): string
    {
        $window = $appointment['time_window'] ?? '';
        if ($window !== '') {
            return $window;
        }
        return appointment_build_time_window($appointment);
    }
}

?>
