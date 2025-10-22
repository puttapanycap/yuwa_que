<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

function fetchPatientHnByIdCard(string $idCardNumber): ?string
{
    if (!preg_match('/^\d{13}$/', $idCardNumber)) {
        return null;
    }

    $endpoint = env('HN_LOOKUP_ENDPOINT');
    $apiKey = env('HN_LOOKUP_API_KEY');

    $endpoint = is_string($endpoint) ? trim($endpoint) : '';
    $apiKey = is_string($apiKey) ? trim($apiKey) : '';

    if ($endpoint === '') {
        error_log('HN lookup skipped: endpoint is not configured.');
        return null;
    }

    if ($apiKey === '') {
        error_log('HN lookup skipped: API key is not configured.');
        return null;
    }

    if (!function_exists('curl_init')) {
        error_log('HN lookup skipped: cURL extension is not available.');
        return null;
    }

    $payload = json_encode(['idcard' => $idCardNumber]);
    if ($payload === false) {
        return null;
    }

    $ch = curl_init($endpoint);
    if ($ch === false) {
        return null;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-Key: ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    $responseBody = curl_exec($ch);
    if ($responseBody === false) {
        error_log('HN lookup request failed: ' . curl_error($ch));
        curl_close($ch);
        return null;
    }

    $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($statusCode < 200 || $statusCode >= 300) {
        error_log(sprintf('HN lookup returned HTTP %d: %s', $statusCode, $responseBody));
        return null;
    }

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded)) {
        error_log('HN lookup returned invalid JSON.');
        return null;
    }

    $hn = extractHnFromResponse($decoded);
    if ($hn === null) {
        error_log('HN lookup response did not include HN value.');
    }

    return $hn;
}

function extractHnFromResponse($data, int $depth = 0): ?string
{
    if ($depth > 5 || !is_array($data)) {
        return null;
    }

    foreach (['hn', 'HN', 'patient_hn'] as $key) {
        if (!array_key_exists($key, $data)) {
            continue;
        }

        $value = trim((string) $data[$key]);
        if ($value !== '') {
            return $value;
        }
    }

    foreach ($data as $value) {
        if (is_array($value)) {
            $hn = extractHnFromResponse($value, $depth + 1);
            if ($hn !== null && $hn !== '') {
                return $hn;
            }
        }
    }

    return null;
}

ensureKioskDevicesTableExists();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$queueTypeId = $_POST['queue_type_id'] ?? null;
$idCardNumber = $_POST['id_card_number'] ?? null;

$kiosk = getActiveKioskFromRequest();
if (!$kiosk) {
    echo json_encode([
        'success' => false,
        'message' => 'เครื่อง Kiosk นี้ยังไม่ได้รับอนุญาตให้ใช้งาน กรุณาติดต่อผู้ดูแลระบบ',
    ]);
    exit;
}

if (!$queueTypeId || !$idCardNumber) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

// Validate ID card number
if (!preg_match('/^\d{13}$/', $idCardNumber)) {
    echo json_encode(['success' => false, 'message' => 'เลขบัตรประจำตัวประชาชนไม่ถูกต้อง']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Get queue type info
    $stmt = $db->prepare("SELECT type_name, prefix_char, ticket_template, default_service_point_id FROM queue_types WHERE queue_type_id = ? AND is_active = 1");
    $stmt->execute([$queueTypeId]);
    $queueType = $stmt->fetch();

    if (!$queueType) {
        throw new Exception('ประเภทคิวไม่ถูกต้อง');
    }
    
    // Generate queue number with MySQL named lock to prevent race conditions
    $lockKey = sprintf('gen_queue_%s_%s', (int)$queueTypeId, date('Ymd'));
    $stmt = $db->prepare('SELECT GET_LOCK(?, 5)');
    $stmt->execute([$lockKey]);
    $gotLock = (int)$stmt->fetchColumn() === 1;
    if (!$gotLock) {
        throw new Exception('ระบบกำลังยุ่ง กรุณาลองใหม่');
    }

    // Find last number for today for this type, then increment
    $stmt = $db->prepare("SELECT queue_number FROM queues WHERE queue_type_id = ? AND DATE(creation_time) = CURDATE() ORDER BY queue_id DESC LIMIT 1");
    $stmt->execute([$queueTypeId]);
    $lastQueueNumber = $stmt->fetchColumn();
    $nextNo = 1;
    if ($lastQueueNumber) {
        // Extract numeric tail after prefix
        $prefix = (string)$queueType['prefix_char'];
        $digits = preg_replace('/^' . preg_quote($prefix, '/') . '/u', '', $lastQueueNumber);
        $nextNo = max(1, ((int)preg_replace('/\D/', '', $digits)) + 1);
    }
    $queueNumber = $queueType['prefix_char'] . str_pad($nextNo, 3, '0', STR_PAD_LEFT);
    
    $firstServicePoint = null;

    if (!empty($queueType['default_service_point_id'])) {
        $stmt = $db->prepare("
            SELECT service_point_id,
                   TRIM(CONCAT(COALESCE(point_label, ''), CASE WHEN point_label IS NOT NULL AND point_label <> '' THEN ' ' ELSE '' END, point_name)) AS display_name
            FROM service_points
            WHERE service_point_id = ? AND is_active = 1
        ");
        $stmt->execute([$queueType['default_service_point_id']]);
        $firstServicePoint = $stmt->fetch();
    }

    if (!$firstServicePoint) {
        $stmt = $db->prepare("
            SELECT sp.service_point_id,
                   TRIM(CONCAT(COALESCE(sp.point_label, ''), CASE WHEN sp.point_label IS NOT NULL AND sp.point_label <> '' THEN ' ' ELSE '' END, sp.point_name)) AS display_name
            FROM service_flows sf
            JOIN service_points sp ON sf.to_service_point_id = sp.service_point_id
            WHERE sf.queue_type_id = ?
              AND sf.from_service_point_id IS NULL
              AND sp.is_active = 1
            ORDER BY sf.sequence_order
            LIMIT 1
        ");
        $stmt->execute([$queueTypeId]);
        $firstServicePoint = $stmt->fetch();
    }

    if (!$firstServicePoint) {
        $stmt = $db->prepare("
            SELECT service_point_id,
                   TRIM(CONCAT(COALESCE(point_label, ''), CASE WHEN point_label IS NOT NULL AND point_label <> '' THEN ' ' ELSE '' END, point_name)) AS display_name
            FROM service_points
            WHERE position_key = 'SCREENING_01' AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute();
        $firstServicePoint = $stmt->fetch();
    }

    if (!$firstServicePoint) {
        $stmt = $db->prepare("
            SELECT service_point_id,
                   TRIM(CONCAT(COALESCE(point_label, ''), CASE WHEN point_label IS NOT NULL AND point_label <> '' THEN ' ' ELSE '' END, point_name)) AS display_name
            FROM service_points
            WHERE is_active = 1
            ORDER BY display_order, point_name
            LIMIT 1
        ");
        $stmt->execute();
        $firstServicePoint = $stmt->fetch();
    }

    if (!$firstServicePoint) {
        throw new Exception('ไม่พบจุดบริการเริ่มต้น');
    }

    // Insert/update patient info first
    $stmt = $db->prepare("INSERT INTO patients (id_card_number) VALUES (?) ON DUPLICATE KEY UPDATE id_card_number = id_card_number");
    $stmt->execute([$idCardNumber]);
    
    // Insert queue
    $stmt = $db->prepare("INSERT INTO queues (queue_number, queue_type_id, patient_id_card_number, current_service_point_id, kiosk_id) VALUES (?, ?, ?, ?, ?)");
    $kioskIdentifier = $kiosk['identifier'] ?? ('KIOSK_' . str_pad((string) $kiosk['id'], 3, '0', STR_PAD_LEFT));
    $stmt->execute([$queueNumber, $queueTypeId, $idCardNumber, $firstServicePoint['service_point_id'], $kioskIdentifier]);

    $queueId = $db->lastInsertId();
    
    // Log queue creation in service flow history
    // Note: staff_id is NULL for kiosk-generated queues
    $stmt = $db->prepare("
        INSERT INTO service_flow_history 
        (queue_id, from_service_point_id, to_service_point_id, staff_id, action, notes, timestamp) 
        VALUES (?, NULL, ?, NULL, 'created', 'สร้างคิวจาก Kiosk', NOW())
    ");
    $result = $stmt->execute([$queueId, $firstServicePoint['service_point_id']]);
    
    if (!$result) {
        throw new Exception('ไม่สามารถบันทึก Service Flow History ได้');
    }
    
    // Log activity in audit logs
    $stmt = $db->prepare("
        INSERT INTO audit_logs (staff_id, action_description, ip_address, user_agent, timestamp) 
        VALUES (NULL, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        "สร้างคิว {$queueNumber} จาก Kiosk {$kiosk['kiosk_name']} - บัตรประชาชน: " . substr($idCardNumber, 0, 4) . "****" . substr($idCardNumber, -4),
        $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
    
    $db->commit();
    // Release named lock
    try {
        $stmt = $db->prepare('DO RELEASE_LOCK(?)');
        $stmt->execute([$lockKey]);
    } catch (Exception $e) { /* ignore */ }

    $ticketTemplate = $queueType['ticket_template'] ?? 'standard';
    $patientHn = null;

    if ($ticketTemplate === 'standard') {
        try {
            $patientHn = fetchPatientHnByIdCard($idCardNumber);
        } catch (Exception $e) {
            error_log('HN lookup error: ' . $e->getMessage());
            $patientHn = null;
        }
    }
    $appointmentLookup = [
        'ok' => false,
        'error' => null,
        'appointments' => [],
        'patient' => null,
    ];

    if ($ticketTemplate === 'appointment_list') {
        try {
            $appointmentLookup = fetchAppointmentsForIdCard($idCardNumber, date('Y-m-d'));
        } catch (Exception $e) {
            $appointmentLookup = [
                'ok' => false,
                'error' => $e->getMessage(),
                'appointments' => [],
                'patient' => null,
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'queue' => [
            'queue_id' => $queueId,
            'queue_number' => $queueNumber,
            'queue_type_id' => $queueTypeId,
            'service_point_name' => $firstServicePoint['display_name'] ?? 'จุดบริการ',
            'ticket_template' => $ticketTemplate,
            'default_service_point_id' => $queueType['default_service_point_id'] ?? null,
            'creation_time' => date('Y-m-d H:i:s'),
            'kiosk_identifier' => $kioskIdentifier,
            'kiosk_name' => $kiosk['kiosk_name'],
            'patient_hn' => $patientHn,
            'appointments' => $appointmentLookup['appointments'] ?? [],
            'appointment_patient' => $appointmentLookup['patient'] ?? null,
            'appointment_lookup_ok' => $appointmentLookup['ok'] ?? false,
            'appointment_lookup_message' => $appointmentLookup['error'] ?? null,
            'appointment_lookup_performed' => $ticketTemplate === 'appointment_list',
        ]
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    // Best-effort release lock on error
    if (isset($lockKey)) {
        try {
            $stmt = $db->prepare('DO RELEASE_LOCK(?)');
            $stmt->execute([$lockKey]);
        } catch (Exception $e2) { /* ignore */ }
    }
    
    // Log error for debugging
    error_log("Generate Queue Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'debug' => [
            'queue_type_id' => $queueTypeId,
            'id_card_number' => substr($idCardNumber, 0, 4) . "****" . substr($idCardNumber, -4),
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>
