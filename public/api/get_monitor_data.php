<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json');

$servicePointId = $_GET['service_point_id'] ?? null;

try {
    $db = getDB();
    ensureAppointmentTables();
    $graceMinutes = getAppointmentReadyGraceMinutes();

    if ($servicePointId) {
        // Get data for specific service point

        // Current queue
        $stmt = $db->prepare("
            SELECT q.*, qt.type_name, qt.ticket_template,
                   TRIM(CONCAT(COALESCE(sp.point_label,''),' ', sp.point_name)) as service_point_name
            FROM queues q
            LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
            LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
            WHERE q.current_service_point_id = ?
            AND q.current_status IN ('called', 'processing')
            ORDER BY q.last_called_time DESC
            LIMIT 1
        ");
        $stmt->execute([$servicePointId]);
        $currentQueue = $stmt->fetch();
        $currents = [];
        if ($currentQueue) { $currents[] = $currentQueue; }

        // Waiting queues
        $stmt = $db->prepare("
            SELECT q.*, qt.type_name, qt.ticket_template,
                   TRIM(CONCAT(COALESCE(sp.point_label,''),' ', sp.point_name)) as service_point_name
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

    } else {
        // Get data for all service points
        // Current queues being called (all)
        $stmt = $db->prepare("\n            SELECT q.*, qt.type_name, qt.ticket_template, TRIM(CONCAT(COALESCE(sp.point_label,''),' ', sp.point_name)) as service_point_name\n            FROM queues q\n            LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id\n            LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id\n            WHERE q.current_status IN ('called', 'processing')\n            ORDER BY q.last_called_time DESC\n        ");
        $stmt->execute();
        $currents = $stmt->fetchAll();
        $currentQueue = $currents[0] ?? null;

        // All waiting queues
        $stmt = $db->prepare("
            SELECT q.*, qt.type_name, qt.ticket_template, TRIM(CONCAT(COALESCE(sp.point_label,''),' ', sp.point_name)) as service_point_name
            FROM queues q
            LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
            LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
            WHERE q.current_status = 'waiting'
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
            LIMIT 20
        ");
        $stmt->bindValue(':grace', $graceMinutes, PDO::PARAM_INT);
        $stmt->execute();
        $waitingQueues = $stmt->fetchAll();
    }

    $queuesForContext = [];
    if (!empty($currents)) {
        foreach ($currents as &$queue) {
            $queuesForContext[] =& $queue;
        }
    }
    if (!empty($waitingQueues)) {
        foreach ($waitingQueues as &$queue) {
            $queuesForContext[] =& $queue;
        }
    }
    if (!empty($queuesForContext)) {
        attachAppointmentContext($db, $queuesForContext);
    }
    unset($queue);

    $currentQueue = $currents[0] ?? null;

    echo json_encode([
        'current' => $currentQueue,
        'currents' => $currents,
        'waiting' => $waitingQueues,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'current' => null,
        'waiting' => [],
        'error' => 'Database error'
    ]);
}
?>
