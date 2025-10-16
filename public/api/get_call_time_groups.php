<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

header('Content-Type: application/json');

$date = $_GET['date'] ?? date('Y-m-d');
$servicePointId = $_GET['service_point_id'] ?? null;

try {
    $db = getDB();

    $start = new DateTime($date . ' 08:30:00');
    $end = new DateTime($date . ' 17:30:00');
    $interval = new DateInterval('PT1H');

    $groups = [];
    $current = clone $start;

    while ($current < $end) {
        $slotStart = clone $current;
        $slotEnd = clone $current;
        $slotEnd->add($interval);

        $sql = "SELECT COUNT(*) FROM audio_call_history WHERE call_time >= ? AND call_time < ?";
        $params = [
            $slotStart->format('Y-m-d H:i:s'),
            $slotEnd->format('Y-m-d H:i:s')
        ];
        if ($servicePointId) {
            $sql .= " AND service_point_id = ?";
            $params[] = $servicePointId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $count = (int) $stmt->fetchColumn();

        $groups[] = [
            'start' => $slotStart->format('H:i'),
            'end' => $slotEnd->format('H:i'),
            'count' => $count
        ];

        $current = $slotEnd;
    }

    echo json_encode(['success' => true, 'groups' => $groups]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
