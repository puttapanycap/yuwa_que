<?php
// Simple SSE endpoint for queue events (opt-in)
require_once dirname(__DIR__, 2) . '/config/config.php';

// Allow public read (monitor screen)
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');
header('Access-Control-Allow-Origin: *');

ignore_user_abort(true);
set_time_limit(0);

$servicePointId = isset($_GET['service_point_id']) ? (int)$_GET['service_point_id'] : null;
$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : null;
if (isset($_SERVER['HTTP_LAST_EVENT_ID']) && is_numeric($_SERVER['HTTP_LAST_EVENT_ID'])) {
    $lastId = (int)$_SERVER['HTTP_LAST_EVENT_ID'];
}

function sse_send($event, $data, $id = null) {
    if ($id !== null) {
        echo "id: {$id}\n";
    }
    if ($event) {
        echo "event: {$event}\n";
    }
    echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    @ob_flush();
    @flush();
}

try {
    $db = getDB();
    $startedAt = time();
    $heartbeatAt = 0;
    while (!connection_aborted()) {
        // Stop after ~30s to allow reconnection
        if (time() - $startedAt > 30) {
            break;
        }

        // Heartbeat every 10s
        if (time() - $heartbeatAt > 10) {
            sse_send('heartbeat', ['ts' => time()]);
            $heartbeatAt = time();
        }

        // Fetch new notifications (queue_called)
        $sql = "SELECT n.notification_id, n.notification_type, n.title, n.message, n.created_at, n.service_point_id
                FROM notifications n
                WHERE n.notification_type = 'queue_called' ".
                ($servicePointId ? "AND (n.service_point_id IS NULL OR n.service_point_id = :spId) " : "").
                ($lastId ? "AND n.notification_id > :lastId " : "").
                "ORDER BY n.notification_id ASC LIMIT 50";
        $stmt = $db->prepare($sql);
        if ($servicePointId) $stmt->bindValue(':spId', $servicePointId, PDO::PARAM_INT);
        if ($lastId) $stmt->bindValue(':lastId', $lastId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $lastId = (int)$row['notification_id'];
            sse_send('queue_called', $row, $lastId);
        }

        sleep(1);
    }
} catch (Exception $e) {
    // Send error and close
    sse_send('error', ['message' => 'Server error', 'detail' => $e->getMessage()]);
}

// graceful end
echo ": close\n\n";
@ob_flush();
@flush();

?>

