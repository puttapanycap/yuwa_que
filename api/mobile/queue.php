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
        default:
            sendApiError('Invalid action', 'INVALID_ACTION');
    }
}

/**
 * Get queue list
 */
function getQueueList() {
    try {
        $db = getDB();
        
        $servicePointId = $_GET['service_point_id'] ?? null;
        $status = $_GET['status'] ?? null;
        $limit = min((int)($_GET['limit'] ?? 50), 100); // Max 100
        $offset = (int)($_GET['offset'] ?? 0);
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if ($servicePointId) {
            $whereClause .= " AND q.current_service_point_id = ?";
            $params[] = $servicePointId;
        }
        
        if ($status) {
            $whereClause .= " AND q.current_status = ?";
            $params[] = $status;
        }
        
        // Add date filter for today
        $whereClause .= " AND DATE(q.creation_time) = CURDATE()";
        
        $stmt = $db->prepare("
            SELECT 
                q.*,
                qt.type_name,
                qt.prefix_char,
                sp.point_name as service_point_name,
                sp.position_key
            FROM queues q
            LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
            LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
            $whereClause
            ORDER BY q.creation_time DESC
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
        $countStmt->execute(array_slice($params, 0, -2)); // Remove limit and offset
        $total = $countStmt->fetch()['total'];
        
        sendApiResponse([
            'success' => true,
            'data' => [
                'queues' => $queues,
                'pagination' => [
                    'total' => (int)$total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $total
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Get queue list error: " . $e->getMessage());
        sendApiError('Failed to get queue list', 'DATABASE_ERROR', 500);
    }
}

/**
 * Get queue status by ID or number
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
                    sp.point_name as service_point_name,
                    sp.position_key
                FROM queues q
                LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
                LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
                WHERE q.queue_id = ?
            ");
            $stmt->execute([$queueId]);
        } else {
            $stmt = $db->prepare("
                SELECT 
                    q.*,
                    qt.type_name,
                    qt.prefix_char,
                    sp.point_name as service_point_name,
                    sp.position_key
                FROM queues q
                LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
                LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
                WHERE q.queue_number = ? AND DATE(q.creation_time) = CURDATE()
            ");
            $stmt->execute([$queueNumber]);
        }
        
        $queue = $stmt->fetch();
        
        if (!$queue) {
            sendApiError('Queue not found', 'QUEUE_NOT_FOUND', 404);
        }
        
        // Get queue position if waiting
        $position = null;
        if ($queue['current_status'] === 'waiting') {
            $stmt = $db->prepare("
                SELECT COUNT(*) + 1 as position
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
            $position = $stmt->fetch()['position'];
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
        
        sendApiResponse([
            'success' => true,
            'data' => [
                'queue' => $queue,
                'position' => $position,
                'history' => $history,
                'estimated_wait_time' => calculateEstimatedWaitTime($queue)
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
 * Create new queue
 */
function createQueue($input) {
    try {
        $queueTypeId = $input['queue_type_id'] ?? null;
        $patientIdCard = $input['patient_id_card'] ?? null;
        $patientName = $input['patient_name'] ?? null;
        $patientPhone = $input['patient_phone'] ?? null;
        $priorityLevel = (int)($input['priority_level'] ?? 0);
        
        if (!$queueTypeId) {
            sendApiError('Missing queue_type_id', 'MISSING_PARAMS');
        }
        
        $db = getDB();
        $db->beginTransaction();
        
        // Validate queue type
        $stmt = $db->prepare("SELECT * FROM queue_types WHERE queue_type_id = ? AND is_active = 1");
        $stmt->execute([$queueTypeId]);
        $queueType = $stmt->fetch();
        
        if (!$queueType) {
            sendApiError('Invalid queue type', 'INVALID_QUEUE_TYPE');
        }
        
        // Check daily limit
        $maxQueuePerDay = (int)getSetting('max_queue_per_day', '999');
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM queues WHERE DATE(creation_time) = CURDATE()");
        $stmt->execute();
        $todayCount = $stmt->fetch()['count'];
        
        if ($todayCount >= $maxQueuePerDay) {
            sendApiError('Daily queue limit reached', 'QUEUE_LIMIT_REACHED');
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
            // Check if patient exists
            $stmt = $db->prepare("SELECT patient_id FROM patients WHERE id_card_number = ?");
            $stmt->execute([$patientIdCard]);
            $existingPatient = $stmt->fetch();
            
            if ($existingPatient) {
                $patientId = $existingPatient['patient_id'];
                
                // Update patient info if provided
                if ($patientName || $patientPhone) {
                    $stmt = $db->prepare("
                        UPDATE patients 
                        SET name = COALESCE(?, name), phone = COALESCE(?, phone)
                        WHERE patient_id = ?
                    ");
                    $stmt->execute([$patientName, $patientPhone, $patientId]);
                }
            } else {
                // Create new patient
                $stmt = $db->prepare("
                    INSERT INTO patients (id_card_number, name, phone) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$patientIdCard, $patientName, $patientPhone]);
                $patientId = $db->lastInsertId();
            }
        }
        
        // Get first service point for this queue type
        $stmt = $db->prepare("
            SELECT sp.service_point_id
            FROM service_flows sf
            JOIN service_points sp ON sf.to_service_point_id = sp.service_point_id
            WHERE sf.queue_type_id = ? AND sf.from_service_point_id IS NULL
            AND sp.is_active = 1
            ORDER BY sf.sequence_order
            LIMIT 1
        ");
        $stmt->execute([$queueTypeId]);
        $firstServicePoint = $stmt->fetch();
        
        if (!$firstServicePoint) {
            // Fallback to first active service point
            $stmt = $db->prepare("
                SELECT service_point_id 
                FROM service_points 
                WHERE is_active = 1 
                ORDER BY display_order 
                LIMIT 1
            ");
            $stmt->execute();
            $firstServicePoint = $stmt->fetch();
        }
        
        if (!$firstServicePoint) {
            sendApiError('No active service points available', 'NO_SERVICE_POINTS');
        }
        
        // Create queue
        $session = getCurrentMobileSession();
        $kioskId = 'mobile_app_' . ($session['mobile_user_id'] ?? 'unknown');
        
        $stmt = $db->prepare("
            INSERT INTO queues 
            (queue_number, queue_type_id, patient_id_card_number, kiosk_id, current_service_point_id, priority_level)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $queueNumber,
            $queueTypeId,
            $patientIdCard,
            $kioskId,
            $firstServicePoint['service_point_id'],
            $priorityLevel
        ]);
        
        $queueId = $db->lastInsertId();
        
        // Log queue creation in service flow
        $stmt = $db->prepare("
            INSERT INTO service_flow_history 
            (queue_id, to_service_point_id, action, notes)
            VALUES (?, ?, 'created', 'Created via Mobile API')
        ");
        $stmt->execute([$queueId, $firstServicePoint['service_point_id']]);
        
        $db->commit();
        
        // Log activity
        logActivity("สร้างคิว {$queueNumber} ผ่าน Mobile API");
        
        sendApiResponse([
            'success' => true,
            'data' => [
                'queue_id' => $queueId,
                'queue_number' => $queueNumber,
                'queue_type' => $queueType['type_name'],
                'service_point_id' => $firstServicePoint['service_point_id'],
                'status' => 'waiting',
                'created_at' => date('Y-m-d H:i:s')
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
 * Calculate estimated wait time
 */
function calculateEstimatedWaitTime($queue) {
    if ($queue['current_status'] !== 'waiting') {
        return 0;
    }
    
    try {
        $db = getDB();
        
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
        
        // Average processing time (in minutes)
        $avgProcessingTime = 5; // Default 5 minutes
        
        return $aheadCount * $avgProcessingTime;
        
    } catch (Exception $e) {
        return 0;
    }
}
?>
