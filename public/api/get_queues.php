<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

header('Content-Type: application/json');

$servicePointId = $_GET['service_point_id'] ?? null;

if (!$servicePointId) {
    echo json_encode(['error' => 'Service point ID required']);
    exit;
}

// Verify access to service point
$hasAccess = false;
foreach ($_SESSION['service_points'] as $sp) {
    if ($sp['service_point_id'] == $servicePointId) {
        $hasAccess = true;
        break;
    }
}

if (!$hasAccess) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

try {
    $db = getDB();
    ensureAppointmentTables();
    $graceMinutes = getAppointmentReadyGraceMinutes();

    // Get current queue (being served)
    $stmt = $db->prepare("
        SELECT q.*, qt.type_name, qt.ticket_template,
               TRIM(CONCAT(COALESCE(sp.point_label,''), CASE WHEN sp.point_label IS NOT NULL AND sp.point_label <> '' THEN ' ' ELSE '' END, sp.point_name)) AS service_point_name
        FROM queues q
        LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
        LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
        WHERE q.current_service_point_id = ?
        AND q.current_status IN ('called', 'processing')
        ORDER BY q.last_called_time DESC
        LIMIT 1
    ");
    $stmt->execute([$servicePointId]);
    $currentQueue = $stmt->fetch() ?: null;

    // Get waiting queues
    $stmt = $db->prepare("
        SELECT q.*, qt.type_name, qt.ticket_template,
               TRIM(CONCAT(COALESCE(sp.point_label,''), CASE WHEN sp.point_label IS NOT NULL AND sp.point_label <> '' THEN ' ' ELSE '' END, sp.point_name)) AS service_point_name
        FROM queues q
        LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
        LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
        WHERE q.current_service_point_id = ?
        AND q.current_status = 'waiting'
        AND (
            qt.ticket_template <> 'appointment_list'
            OR NOT EXISTS (
                SELECT 1 FROM appointment_queue_items aqi
                WHERE aqi.queue_id = q.queue_id
                  AND aqi.status = 'pending'
            )
            OR EXISTS (
                SELECT 1 FROM appointment_queue_items aqi
                WHERE aqi.queue_id = q.queue_id
                  AND aqi.status = 'pending'
                  AND aqi.display_order = (
                      SELECT MIN(display_order) FROM appointment_queue_items WHERE queue_id = q.queue_id AND status = 'pending'
                  )
                  AND (aqi.start_time IS NULL OR aqi.start_time <= DATE_ADD(NOW(), INTERVAL :grace MINUTE))
            )
        )
        ORDER BY q.priority_level DESC, q.creation_time ASC
    ");
    $stmt->bindValue(1, $servicePointId, PDO::PARAM_INT);
    $stmt->bindValue(':grace', $graceMinutes, PDO::PARAM_INT);
    $stmt->execute();
    $waitingQueues = $stmt->fetchAll();

    $queuesForContext = [];
    if ($currentQueue) {
        $queuesForContext[] =& $currentQueue;
    }
    foreach ($waitingQueues as &$queue) {
        $queuesForContext[] =& $queue;
    }
    if (!empty($queuesForContext)) {
        attachAppointmentContext($db, $queuesForContext);
    }
    unset($queue);

    // Get statistics
    $today = date('Y-m-d');

    // Completed today count
    $stmt = $db->prepare("
        SELECT COUNT(*) as count
        FROM service_flow_history sfh
        JOIN queues q ON sfh.queue_id = q.queue_id
        WHERE sfh.from_service_point_id = ? 
        AND sfh.action = 'completed'
        AND DATE(sfh.timestamp) = ?
    ");
    $stmt->execute([$servicePointId, $today]);
    $completedCount = $stmt->fetch()['count'];

    echo json_encode([
        'current' => $currentQueue,
        'waiting' => $waitingQueues,
        'stats' => [
            'waiting' => count($waitingQueues),
            'completed_today' => $completedCount
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
?>
