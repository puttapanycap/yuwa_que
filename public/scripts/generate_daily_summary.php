<?php
// Script to generate daily performance summaries
// Run this script daily via cron job

require_once dirname(__DIR__, 2) . '/config/config.php';

try {
    $db = getDB();
    
    // Get yesterday's date
    $summaryDate = date('Y-m-d', strtotime('-1 day'));
    
    echo "Generating daily summary for: {$summaryDate}\n";
    
    // Get all queue types and service points
    $stmt = $db->prepare("SELECT queue_type_id FROM queue_types WHERE is_active = 1");
    $stmt->execute();
    $queueTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt = $db->prepare("SELECT service_point_id FROM service_points WHERE is_active = 1");
    $stmt->execute();
    $servicePoints = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $db->beginTransaction();
    
    // Generate summary for each combination
    foreach ($queueTypes as $queueTypeId) {
        foreach ($servicePoints as $servicePointId) {
            generateDailySummary($db, $summaryDate, $queueTypeId, $servicePointId);
        }
        
        // Also generate queue type summary (all service points)
        generateDailySummary($db, $summaryDate, $queueTypeId, null);
    }
    
    // Generate overall summary (all queue types, all service points)
    generateDailySummary($db, $summaryDate, null, null);
    
    $db->commit();
    
    echo "Daily summary generation completed successfully\n";
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}

function generateDailySummary($db, $summaryDate, $queueTypeId, $servicePointId) {
    $whereConditions = ["DATE(q.created_at) = ?"];
    $params = [$summaryDate];
    
    if ($queueTypeId) {
        $whereConditions[] = "q.queue_type_id = ?";
        $params[] = $queueTypeId;
    }
    
    if ($servicePointId) {
        $whereConditions[] = "q.current_service_point_id = ?";
        $params[] = $servicePointId;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $sql = "
        SELECT 
            COUNT(*) as total_queues,
            SUM(CASE WHEN q.status = 'completed' THEN 1 ELSE 0 END) as completed_queues,
            SUM(CASE WHEN q.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_queues,
            AVG(CASE WHEN q.status = 'completed' AND q.called_at IS NOT NULL THEN 
                TIMESTAMPDIFF(MINUTE, q.created_at, q.called_at) 
            END) as avg_wait_time,
            AVG(CASE WHEN q.status = 'completed' AND q.called_at IS NOT NULL THEN 
                TIMESTAMPDIFF(MINUTE, q.called_at, q.completed_at) 
            END) as avg_service_time,
            MAX(CASE WHEN q.status = 'completed' AND q.called_at IS NOT NULL THEN 
                TIMESTAMPDIFF(MINUTE, q.created_at, q.called_at) 
            END) as max_wait_time
        FROM queues q
        WHERE {$whereClause}
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $summary = $stmt->fetch();
    
    // Get peak hour
    $peakSql = "
        SELECT 
            HOUR(q.created_at) as peak_hour,
            COUNT(*) as hourly_count
        FROM queues q
        WHERE {$whereClause}
        GROUP BY HOUR(q.created_at)
        ORDER BY hourly_count DESC
        LIMIT 1
    ";
    
    $stmt = $db->prepare($peakSql);
    $stmt->execute($params);
    $peakHour = $stmt->fetch();
    
    // Insert or update summary
    $stmt = $db->prepare("
        INSERT INTO daily_performance_summary 
        (summary_date, queue_type_id, service_point_id, total_queues, completed_queues, 
         cancelled_queues, avg_wait_time_minutes, avg_service_time_minutes, max_wait_time_minutes,
         peak_hour_start, peak_hour_end, peak_hour_queue_count)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        total_queues = VALUES(total_queues),
        completed_queues = VALUES(completed_queues),
        cancelled_queues = VALUES(cancelled_queues),
        avg_wait_time_minutes = VALUES(avg_wait_time_minutes),
        avg_service_time_minutes = VALUES(avg_service_time_minutes),
        max_wait_time_minutes = VALUES(max_wait_time_minutes),
        peak_hour_start = VALUES(peak_hour_start),
        peak_hour_end = VALUES(peak_hour_end),
        peak_hour_queue_count = VALUES(peak_hour_queue_count),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    $peakHourStart = $peakHour ? sprintf('%02d:00:00', $peakHour['peak_hour']) : null;
    $peakHourEnd = $peakHour ? sprintf('%02d:59:59', $peakHour['peak_hour']) : null;
    $peakHourCount = $peakHour ? $peakHour['hourly_count'] : 0;
    
    $stmt->execute([
        $summaryDate,
        $queueTypeId,
        $servicePointId,
        $summary['total_queues'] ?? 0,
        $summary['completed_queues'] ?? 0,
        $summary['cancelled_queues'] ?? 0,
        $summary['avg_wait_time'] ?? 0,
        $summary['avg_service_time'] ?? 0,
        $summary['max_wait_time'] ?? 0,
        $peakHourStart,
        $peakHourEnd,
        $peakHourCount
    ]);
    
    echo "Summary generated for date: {$summaryDate}, queue_type: " . ($queueTypeId ?? 'ALL') . 
         ", service_point: " . ($servicePointId ?? 'ALL') . "\n";
}
?>
