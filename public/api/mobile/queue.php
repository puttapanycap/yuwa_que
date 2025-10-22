<?php
/**
 * Mobile API - Queue Management
 * API สำหรับการจัดการคิวผ่าน Mobile App
 */

require_once 'middleware.php';

// Authenticate request
$session = authenticateMobileAPI();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];

switch ($method) {
    case 'GET':
        handleGetRequest();
        break;
    case 'POST':
        handlePostRequest($input);
        break;
    case 'PUT':
        handlePutRequest($input);
        break;
    case 'DELETE':
        handleDeleteRequest($input);
        break;
    default:
        sendApiError('Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

/**
 * Handle GET requests
 */
function handleGetRequest() {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            getQueueList();
            break;
        case 'status':
            getQueueStatus();
            break;
        case 'types':
            getQueueTypes();
            break;
        case 'service_points':
            getServicePoints();
            break;
        case 'my_queues':
            getMyQueues();
            break;
        case 'statistics':
            getQueueStatistics();
            break;
        case 'waiting_time':
            getEstimatedWaitingTime();
            break;
        default:
            sendApiError('Invalid action', 'INVALID_ACTION');
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($input) {
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'create':
            createQueue($input);
            break;
        case 'cancel':
            cancelQueue($input);
            break;
        case 'check_in':
            checkInQueue($input);
            break;
        case 'rate_service':
            rateService($input);
            break;
        default:
            sendApiError('Invalid action', 'INVALID_ACTION');
    }
}

/**
 * Handle PUT requests
 */
function handlePutRequest($input) {
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'update_patient_info':
            updatePatientInfo($input);
            break;
        case 'reschedule':
            rescheduleQueue($input);
            break;
        default:
            sendApiError('Invalid action', 'INVALID_ACTION');
    }
}

/**
 * Handle DELETE requests
 */
function handleDeleteRequest($input) {
    $queueId = $input['queue_id'] ?? null;
    
    if (!$queueId) {
        sendApiError('Missing queue_id', 'MISSING_PARAMS');
    }
    
    deleteQueue($queueId);
}

/**
 * Get queue list with advanced filtering
 */
function getQueueList() {
    try {
        $db = getDB();
        
        // Parameters
        $servicePointId = $_GET['service_point_id'] ?? null;
        $queueTypeId = $_GET['queue_type_id'] ?? null;
        $status = $_GET['status'] ?? null;
        $date = $_GET['date'] ?? date('Y-m-d');
        $limit = min((int)($_GET['limit'] ?? 50), 100);
        $offset = (int)($_GET['offset'] ?? 0);
        $sortBy = $_GET['sort_by'] ?? 'creation_time';
        $sortOrder = $_GET['sort_order'] ?? 'DESC';
        
        // Validate sort parameters
        $allowedSortFields = ['creation_time', 'queue_number', 'priority_level', 'current_status'];
        $allowedSortOrders = ['ASC', 'DESC'];
        
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'creation_time';
        }
        
        if (!in_array($sortOrder, $allowedSortOrders)) {
            $sortOrder = 'DESC';
        }
        
        $whereClause = "WHERE DATE(q.creation_time) = ?";
        $params = [$date];
        
        if ($servicePointId) {
            $whereClause .= " AND q.current_service_point_id = ?";
            $params[] = $servicePointId;
        }
        
        if ($queueTypeId) {
            $whereClause .= " AND q.queue_type_id = ?";
            $params[] = $queueTypeId;
        }
        
        if ($status) {
            $whereClause .= " AND q.current_status = ?";
            $params[] = $status;
        }
        
        $stmt = $db->prepare("
            SELECT 
                q.*,
                qt.type_name,
                qt.prefix_char,
                qt.color_code,
                sp.point_name as service_point_name,
                sp.position_key,
                p.name as patient_name,
                p.phone as patient_phone,
                CASE 
                    WHEN q.current_status = 'waiting' THEN (
                        SELECT COUNT(*) 
                        FROM queues q2 
                        WHERE q2.current_service_point_id = q.current_service_point_id 
                        AND q2.current_status = 'waiting'
                        AND (q2.priority_level > q.priority_level OR 
                             (q2.priority_level = q.priority_level AND q2.creation_time < q.creation_time))
                    )
                    ELSE NULL
                END as position_in_queue,
                TIMESTAMPDIFF(MINUTE, q.creation_time, NOW()) as waiting_minutes
            FROM queues q
            LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
            LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
            LEFT JOIN patients p ON q.patient_id_card_number = p.id_card_number
            $whereClause
            ORDER BY q.$sortBy $sortOrder
            LIMIT ? OFFSET ?
        ");
        
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        $queues = $stmt->fetchAll();
        
        // Get total count
        $countStmt = $db->prepare("
            SELECT COUNT(*) as total
            FROM queues q
            $whereClause
        ");
        $countStmt->execute(array_slice($params, 0, -2));
        $total = $countStmt->fetch()['total'];
        
        // Add estimated wait time for waiting queues
        foreach ($queues as &$queue) {
            if ($queue['current_status'] === 'waiting' && $queue['position_in_queue'] !== null) {
                $queue['estimated_wait_time'] = calculateEstimatedWaitTime($queue);
            }
        }
        
        sendApiResponse([
            'success' => true,
            'data' => [
                'queues' => $queues,
                'pagination' => [
                    'total' => (int)$total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $total,
                    'current_page' => floor($offset / $limit) + 1,
                    'total_pages' => ceil($total / $limit)
                ],
                'filters' => [
                    'date' => $date,
                    'service_point_id' => $servicePointId,
                    'queue_type_id' => $queueTypeId,
                    'status' => $status
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Get queue list error: " . $e->getMessage());
        sendApiError('Failed to get queue list', 'DATABASE_ERROR', 500);
    }
}

/**
 * Get detailed queue status
 */
function getQueueStatus() {
    try {
        $queueId = $_GET['queue_id'] ?? null;
        $queueNumber = $_GET['queue_number'] ?? null;
        
        if (!$queueId && !$queueNumber) {
            sendApiError('Missing queue_id or queue_number', 'MISSING_PARAMS');
        }
        
        $db = getDB();
        
        if ($queueId) {
            $stmt = $db->prepare("
                SELECT 
                    q.*,
                    qt.type_name,
                    qt.prefix_char,
                    qt.color_code,
                    qt.description as queue_type_description,
                    sp.point_name as service_point_name,
                    sp.position_key,
                    sp.description as service_point_description,
                    p.name as patient_name,
                    p.phone as patient_phone,
                    TIMESTAMPDIFF(MINUTE, q.creation_time, NOW()) as total_waiting_minutes
                FROM queues q
                LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
                LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
                LEFT JOIN patients p ON q.patient_id_card_number = p.id_card_number
                WHERE q.queue_id = ?
            ");
            $stmt->execute([$queueId]);
        } else {
            $stmt = $db->prepare("
                SELECT 
                    q.*,
                    qt.type_name,
                    qt.prefix_char,
                    qt.color_code,
                    qt.description as queue_type_description,
                    sp.point_name as service_point_name,
                    sp.position_key,
                    sp.description as service_point_description,
                    p.name as patient_name,
                    p.phone as patient_phone,
                    TIMESTAMPDIFF(MINUTE, q.creation_time, NOW()) as total_waiting_minutes
                FROM queues q
                LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
                LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
                LEFT JOIN patients p ON q.patient_id_card_number = p.id_card_number
                WHERE q.queue_number = ? AND DATE(q.creation_time) = CURDATE()
            ");
            $stmt->execute([$queueNumber]);
        }
        
        $queue = $stmt->fetch();
        
        if (!$queue) {
            sendApiError('Queue not found', 'QUEUE_NOT_FOUND', 404);
        }
        
        // Get queue position and estimated wait time
        $position = null;
        $estimatedWaitTime = null;
        $queuesAhead = 0;
        
        if ($queue['current_status'] === 'waiting') {
            $stmt = $db->prepare("
                SELECT COUNT(*) as ahead_count
                FROM queues
                WHERE current_service_point_id = ? 
                AND current_status = 'waiting'
                AND (priority_level > ? OR (priority_level = ? AND creation_time < ?))
            ");
            $stmt->execute([
                $queue['current_service_point_id'],
                $queue['priority_level'],
                $queue['priority_level'],
                $queue['creation_time']
            ]);
            $result = $stmt->fetch();
            $queuesAhead = $result['ahead_count'];
            $position = $queuesAhead + 1;
            $estimatedWaitTime = calculateEstimatedWaitTime($queue);
        }
        
        // Get service flow history
        $stmt = $db->prepare("
            SELECT 
                sfh.*,
                sp_from.point_name as from_service_point_name,
                sp_to.point_name as to_service_point_name,
                su.full_name as staff_name
            FROM service_flow_history sfh
            LEFT JOIN service_points sp_from ON sfh.from_service_point_id = sp_from.service_point_id
            LEFT JOIN service_points sp_to ON sfh.to_service_point_id = sp_to.service_point_id
            LEFT JOIN staff_users su ON sfh.staff_id = su.staff_id
            WHERE sfh.queue_id = ?
            ORDER BY sfh.timestamp ASC
        ");
        $stmt->execute([$queue['queue_id']]);
        $history = $stmt->fetchAll();
        
        // Get next service points in flow
        $stmt = $db->prepare("
            SELECT 
                sp.service_point_id,
                sp.point_name,
                sp.description,
                sf.sequence_order
            FROM service_flows sf
            JOIN service_points sp ON sf.to_service_point_id = sp.service_point_id
            WHERE sf.queue_type_id = ? 
            AND sf.from_service_point_id = ?
            AND sf.is_active = 1
            ORDER BY sf.sequence_order
        ");
        $stmt->execute([$queue['queue_type_id'], $queue['current_service_point_id']]);
        $nextServicePoints = $stmt->fetchAll();
        
        sendApiResponse([
            'success' => true,
            'data' => [
                'queue' => $queue,
                'position_in_queue' => $position,
                'queues_ahead' => $queuesAhead,
                'estimated_wait_time' => $estimatedWaitTime,
                'history' => $history,
                'next_service_points' => $nextServicePoints,
                'can_cancel' => in_array($queue['current_status'], ['waiting', 'called']),
                'can_reschedule' => $queue['current_status'] === 'waiting'
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Get queue status error: " . $e->getMessage());
        sendApiError('Failed to get queue status', 'DATABASE_ERROR', 500);
    }
}

/**
 * Get queue types
 */
function getQueueTypes() {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM queue_types 
            WHERE is_active = 1 
            ORDER BY type_name
        ");
        $stmt->execute();
        $types = $stmt->fetchAll();
        
        sendApiResponse([
            'success' => true,
            'data' => $types
        ]);
        
    } catch (Exception $e) {
        error_log("Get queue types error: " . $e->getMessage());
        sendApiError('Failed to get queue types', 'DATABASE_ERROR', 500);
    }
}

/**
 * Get service points
 */
function getServicePoints() {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT 
                sp.*,
                COUNT(q.queue_id) as current_queue_count
            FROM service_points sp
            LEFT JOIN queues q ON sp.service_point_id = q.current_service_point_id 
                AND q.current_status IN ('waiting', 'called', 'processing')
            WHERE sp.is_active = 1
            GROUP BY sp.service_point_id
            ORDER BY sp.display_order, sp.point_name
        ");
        $stmt->execute();
        $servicePoints = $stmt->fetchAll();
        
        sendApiResponse([
            'success' => true,
            'data' => $servicePoints
        ]);
        
    } catch (Exception $e) {
        error_log("Get service points error: " . $e->getMessage());
        sendApiError('Failed to get service points', 'DATABASE_ERROR', 500);
    }
}

/**
 * Get my queues for current user
 */
function getMyQueues() {
    try {
        $session = getCurrentMobileSession();
        $deviceId = $session['device_id'] ?? null;
        
        if (!$deviceId) {
            sendApiError('Device not identified', 'DEVICE_NOT_FOUND');
        }
        
        $db = getDB();
        $limit = min((int)($_GET['limit'] ?? 10), 50);
        $status = $_GET['status'] ?? null;
        
        $whereClause = "WHERE q.kiosk_id LIKE ?";
        $params = ['mobile_app_%' . $deviceId . '%'];
        
        if ($status) {
            $whereClause .= " AND q.current_status = ?";
            $params[] = $status;
        }
        
        $stmt = $db->prepare("
            SELECT 
                q.*,
                qt.type_name,
                qt.prefix_char,
                sp.point_name as service_point_name,
                CASE 
                    WHEN q.current_status = 'waiting' THEN (
                        SELECT COUNT(*) 
                        FROM queues q2 
                        WHERE q2.current_service_point_id = q.current_service_point_id 
                        AND q2.current_status = 'waiting'
                        AND (q2.priority_level > q.priority_level OR 
                             (q2.priority_level = q.priority_level AND q2.creation_time < q.creation_time))
                    )
                    ELSE NULL
                END as position_in_queue
            FROM queues q
            LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
            LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
            $whereClause
            ORDER BY q.creation_time DESC
            LIMIT ?
        ");
        
        $params[] = $limit;
        $stmt->execute($params);
        $queues = $stmt->fetchAll();
        
        sendApiResponse([
            'success' => true,
            'data' => $queues
        ]);
        
    } catch (Exception $e) {
        error_log("Get my queues error: " . $e->getMessage());
        sendApiError('Failed to get user queues', 'DATABASE_ERROR', 500);
    }
}

/**
 * Create new queue with enhanced validation
 */
function createQueue($input) {
    try {
        $queueTypeId = $input['queue_type_id'] ?? null;
        $patientIdCard = $input['patient_id_card'] ?? null;
        $patientName = $input['patient_name'] ?? null;
        $patientPhone = $input['patient_phone'] ?? null;
        $priorityLevel = (int)($input['priority_level'] ?? 0);
        $appointmentTime = $input['appointment_time'] ?? null;
        $notes = $input['notes'] ?? null;
        
        if (!$queueTypeId) {
            sendApiError('Missing queue_type_id', 'MISSING_PARAMS');
        }
        
        $db = getDB();
        $db->beginTransaction();
        ensureQueuePatientHnColumnExists();
        
        // Validate queue type
        $stmt = $db->prepare("SELECT * FROM queue_types WHERE queue_type_id = ? AND is_active = 1");
        $stmt->execute([$queueTypeId]);
        $queueType = $stmt->fetch();
        
        if (!$queueType) {
            $db->rollBack();
            sendApiError('Invalid queue type', 'INVALID_QUEUE_TYPE');
        }

        $ticketTemplate = $queueType['ticket_template'] ?? 'standard';
        $patientHn = null;
        
        // Check working hours
        if (!isWithinWorkingHours()) {
            $workingStart = getWorkingHoursStart();
            $workingEnd = getWorkingHoursEnd();
            $db->rollBack();
            sendApiError("Service hours: {$workingStart} - {$workingEnd}", 'OUTSIDE_WORKING_HOURS');
        }
        
        // Check daily limit
        $maxQueuePerDay = (int)getSetting('max_queue_per_day', '999');
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM queues WHERE DATE(creation_time) = CURDATE()");
        $stmt->execute();
        $todayCount = $stmt->fetch()['count'];
        
        if ($todayCount >= $maxQueuePerDay) {
            $db->rollBack();
            sendApiError('Daily queue limit reached', 'QUEUE_LIMIT_REACHED');
        }
        
        // Check if patient already has active queue
        if ($patientIdCard) {
            $stmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM queues 
                WHERE patient_id_card_number = ? 
                AND current_status IN ('waiting', 'called', 'processing')
                AND DATE(creation_time) = CURDATE()
            ");
            $stmt->execute([$patientIdCard]);
            $activeCount = $stmt->fetch()['count'];
            
            if ($activeCount > 0) {
                $db->rollBack();
                sendApiError('Patient already has active queue', 'DUPLICATE_QUEUE');
            }
        }
        
        // Generate queue number
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM queues 
            WHERE queue_type_id = ? AND DATE(creation_time) = CURDATE()
        ");
        $stmt->execute([$queueTypeId]);
        $typeCount = $stmt->fetch()['count'] + 1;
        
        $queueNumber = $queueType['prefix_char'] . str_pad($typeCount, 3, '0', STR_PAD_LEFT);
        
        // Handle patient data
        $patientId = null;
        if ($patientIdCard) {
            $stmt = $db->prepare("SELECT patient_id FROM patients WHERE id_card_number = ?");
            $stmt->execute([$patientIdCard]);
            $existingPatient = $stmt->fetch();

            if ($existingPatient) {
                $patientId = $existingPatient['patient_id'];
                
                if ($patientName || $patientPhone) {
                    $stmt = $db->prepare("
                        UPDATE patients 
                        SET name = COALESCE(?, name), 
                            phone = COALESCE(?, phone),
                            updated_at = NOW()
                        WHERE patient_id = ?
                    ");
                    $stmt->execute([$patientName, $patientPhone, $patientId]);
                }
            } else {
                $stmt = $db->prepare("
                    INSERT INTO patients (id_card_number, name, phone)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$patientIdCard, $patientName, $patientPhone]);
                $patientId = $db->lastInsertId();
            }

            if ($ticketTemplate === 'standard') {
                try {
                    $patientHn = fetchPatientHnByIdCard($patientIdCard);
                } catch (\Throwable $lookupError) {
                    error_log('HN lookup error: ' . $lookupError->getMessage());
                    $patientHn = null;
                }
            }
        }
        
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
                WHERE sf.queue_type_id = ? AND sf.from_service_point_id IS NULL
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
            $db->rollBack();
            sendApiError('No active service points available', 'NO_SERVICE_POINTS');
        }
        
        // Create queue
        $session = getCurrentMobileSession();
        $kioskId = 'mobile_app_' . ($session['mobile_user_id'] ?? 'unknown');
        
        $stmt = $db->prepare("
            INSERT INTO queues
            (queue_number, queue_type_id, patient_id_card_number, patient_hn, kiosk_id,
             current_service_point_id, priority_level, appointment_time, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $queueNumber,
            $queueTypeId,
            $patientIdCard,
            $patientHn,
            $kioskId,
            $firstServicePoint['service_point_id'],
            $priorityLevel,
            $appointmentTime,
            $notes
        ]);
        
        $queueId = $db->lastInsertId();
        
        // Log queue creation
        $stmt = $db->prepare("
            INSERT INTO service_flow_history 
            (queue_id, to_service_point_id, action, notes)
            VALUES (?, ?, 'created', ?)
        ");
        $stmt->execute([
            $queueId, 
            $firstServicePoint['service_point_id'],
            'Created via Mobile API' . ($notes ? ': ' . $notes : '')
        ]);
        
        $db->commit();
        
        // Send notification if enabled
        if (isTelegramNotificationEnabled()) {
            $message = str_replace(
                ['{queue_number}', '{service_point}'],
                [$queueNumber, 'Mobile Registration'],
                getTelegramNotifyTemplate()
            );
            // Send telegram notification (implement as needed)
        }
        
        logActivity("สร้างคิว {$queueNumber} ผ่าน Mobile API");
        
        sendApiResponse([
            'success' => true,
            'data' => [
                'queue_id' => $queueId,
                'queue_number' => $queueNumber,
                'queue_type' => $queueType['type_name'],
                'service_point_id' => $firstServicePoint['service_point_id'],
                'status' => 'waiting',
                'created_at' => date('Y-m-d H:i:s'),
                'check_status_url' => BASE_URL . '/check_status.php?queue=' . $queueNumber,
                'patient_hn' => $patientHn,
            ]
        ], 201);
        
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Create queue error: " . $e->getMessage());
        sendApiError('Failed to create queue', 'DATABASE_ERROR', 500);
    }
}

/**
 * Cancel queue
 */
function cancelQueue($input) {
    try {
        $queueId = $input['queue_id'] ?? null;
        $reason = $input['reason'] ?? 'Cancelled via Mobile API';
        
        if (!$queueId) {
            sendApiError('Missing queue_id', 'MISSING_PARAMS');
        }
        
        $db = getDB();
        $db->beginTransaction();
        
        // Get queue info
        $stmt = $db->prepare("SELECT * FROM queues WHERE queue_id = ?");
        $stmt->execute([$queueId]);
        $queue = $stmt->fetch();
        
        if (!$queue) {
            sendApiError('Queue not found', 'QUEUE_NOT_FOUND', 404);
        }
        
        if ($queue['current_status'] === 'cancelled') {
            sendApiError('Queue already cancelled', 'ALREADY_CANCELLED');
        }
        
        if ($queue['current_status'] === 'completed') {
            sendApiError('Cannot cancel completed queue', 'CANNOT_CANCEL_COMPLETED');
        }
        
        // Update queue status
        $stmt = $db->prepare("UPDATE queues SET current_status = 'cancelled' WHERE queue_id = ?");
        $stmt->execute([$queueId]);
        
        // Log cancellation
        $stmt = $db->prepare("
            INSERT INTO service_flow_history 
            (queue_id, from_service_point_id, to_service_point_id, action, notes)
            VALUES (?, ?, ?, 'cancelled', ?)
        ");
        $stmt->execute([
            $queueId,
            $queue['current_service_point_id'],
            $queue['current_service_point_id'],
            $reason
        ]);
        
        $db->commit();
        
        logActivity("ยกเลิกคิว {$queue['queue_number']} ผ่าน Mobile API: {$reason}");
        
        sendApiResponse([
            'success' => true,
            'message' => 'Queue cancelled successfully'
        ]);
        
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Cancel queue error: " . $e->getMessage());
        sendApiError('Failed to cancel queue', 'DATABASE_ERROR', 500);
    }
}

/**
 * Calculate estimated wait time with improved algorithm
 */
function calculateEstimatedWaitTime($queue) {
    if ($queue['current_status'] !== 'waiting') {
        return 0;
    }
    
    try {
        $db = getDB();
        
        // Get average processing time for this service point in last 7 days
        $stmt = $db->prepare("
            SELECT AVG(TIMESTAMPDIFF(MINUTE, sfh1.timestamp, sfh2.timestamp)) as avg_processing_time
            FROM service_flow_history sfh1
            JOIN service_flow_history sfh2 ON sfh1.queue_id = sfh2.queue_id
            JOIN queues q ON sfh1.queue_id = q.queue_id
            WHERE sfh1.to_service_point_id = ?
            AND sfh1.action = 'called'
            AND sfh2.action IN ('completed', 'forwarded')
            AND sfh2.timestamp > sfh1.timestamp
            AND sfh1.timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND q.queue_type_id = ?
            AND TIMESTAMPDIFF(MINUTE, sfh1.timestamp, sfh2.timestamp) BETWEEN 1 AND 60
        ");
        $stmt->execute([$queue['current_service_point_id'], $queue['queue_type_id']]);
        $result = $stmt->fetch();
        
        $avgProcessingTime = $result['avg_processing_time'] ?? 8; // Default 8 minutes
        
        // Count queues ahead
        $stmt = $db->prepare("
            SELECT COUNT(*) as ahead_count
            FROM queues
            WHERE current_service_point_id = ? 
            AND current_status = 'waiting'
            AND (priority_level > ? OR (priority_level = ? AND creation_time < ?))
        ");
        $stmt->execute([
            $queue['current_service_point_id'],
            $queue['priority_level'],
            $queue['priority_level'],
            $queue['creation_time']
        ]);
        $aheadCount = $stmt->fetch()['ahead_count'];
        
        return max(1, round($aheadCount * $avgProcessingTime));
        
    } catch (Exception $e) {
        error_log("Calculate wait time error: " . $e->getMessage());
        return 15; // Default fallback
    }
}

/**
 * Get queue statistics
 */
function getQueueStatistics() {
    try {
        $db = getDB();
        $date = $_GET['date'] ?? date('Y-m-d');
        
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_queues,
                SUM(CASE WHEN current_status = 'waiting' THEN 1 ELSE 0 END) as waiting_queues,
                SUM(CASE WHEN current_status = 'called' THEN 1 ELSE 0 END) as called_queues,
                SUM(CASE WHEN current_status = 'processing' THEN 1 ELSE 0 END) as processing_queues,
                SUM(CASE WHEN current_status = 'completed' THEN 1 ELSE 0 END) as completed_queues,
                SUM(CASE WHEN current_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_queues,
                AVG(CASE WHEN current_status = 'completed' 
                    THEN TIMESTAMPDIFF(MINUTE, creation_time, last_updated) 
                    ELSE NULL END) as avg_completion_time
            FROM queues 
            WHERE DATE(creation_time) = ?
        ");
        $stmt->execute([$date]);
        $stats = $stmt->fetch();
        
        // Get hourly distribution
        $stmt = $db->prepare("
            SELECT 
                HOUR(creation_time) as hour,
                COUNT(*) as count
            FROM queues 
            WHERE DATE(creation_time) = ?
            GROUP BY HOUR(creation_time)
            ORDER BY hour
        ");
        $stmt->execute([$date]);
        $hourlyStats = $stmt->fetchAll();
        
        sendApiResponse([
            'success' => true,
            'data' => [
                'statistics' => $stats,
                'hourly_distribution' => $hourlyStats,
                'date' => $date
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Get statistics error: " . $e->getMessage());
        sendApiError('Failed to get statistics', 'DATABASE_ERROR', 500);
    }
}

/**
 * Additional helper functions would continue here...
 * Including: cancelQueue, checkInQueue, rateService, etc.
 */
?>
