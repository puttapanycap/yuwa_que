<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

header('Content-Type: application/json');

try {
    $db = getDB();
    $today = date('Y-m-d');
    
    // Total queues today
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM queues WHERE DATE(creation_time) = ?");
    $stmt->execute([$today]);
    $totalQueues = $stmt->fetch()['count'];
    
    // Completed queues today
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM queues WHERE DATE(creation_time) = ? AND current_status = 'completed'");
    $stmt->execute([$today]);
    $completedQueues = $stmt->fetch()['count'];
    
    // Cancelled queues today
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM queues WHERE DATE(creation_time) = ? AND current_status = 'cancelled'");
    $stmt->execute([$today]);
    $cancelledQueues = $stmt->fetch()['count'];
    
    echo json_encode([
        'total_queues' => $totalQueues,
        'completed_queues' => $completedQueues,
        'cancelled_queues' => $cancelledQueues
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
?>
