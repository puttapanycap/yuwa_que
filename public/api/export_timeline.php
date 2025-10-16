<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

$queueId = $_GET['queue_id'] ?? null;
$format = $_GET['format'] ?? 'json'; // json, csv, pdf

if (!$queueId) {
    http_response_code(400);
    echo json_encode(['error' => 'Queue ID is required']);
    exit;
}

try {
    $db = getDB();
    
    // Get queue info
    $stmt = $db->prepare("
        SELECT q.*, qt.type_name, sp.point_name as current_service_point_name
        FROM queues q
        LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
        LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
        WHERE q.queue_id = ?
    ");
    $stmt->execute([$queueId]);
    $queue = $stmt->fetch();
    
    if (!$queue) {
        http_response_code(404);
        echo json_encode(['error' => 'Queue not found']);
        exit;
    }
    
    // Get service flow history
    $stmt = $db->prepare("
        SELECT sfh.*, sp_from.point_name as from_point_name, sp_to.point_name as to_point_name
        FROM service_flow_history sfh
        LEFT JOIN service_points sp_from ON sfh.from_service_point_id = sp_from.service_point_id
        LEFT JOIN service_points sp_to ON sfh.to_service_point_id = sp_to.service_point_id
        WHERE sfh.queue_id = ?
        ORDER BY sfh.timestamp ASC
    ");
    $stmt->execute([$queueId]);
    $flowHistory = $stmt->fetchAll();
    
    // Get all service points for timeline
    $stmt = $db->prepare("
        SELECT sp.service_point_id, sp.point_name, sp.sequence_order
        FROM service_points sp
        WHERE sp.is_active = 1 
        AND (sp.queue_type_id = ? OR sp.queue_type_id IS NULL)
        ORDER BY sp.sequence_order ASC
    ");
    $stmt->execute([$queue['queue_type_id']]);
    $allServicePoints = $stmt->fetchAll();
    
    // Build timeline data
    $timelineData = [
        'queue_info' => [
            'queue_id' => $queue['queue_id'],
            'queue_number' => $queue['queue_number'],
            'type_name' => $queue['type_name'],
            'current_status' => $queue['current_status'],
            'creation_time' => $queue['creation_time'],
            'current_service_point' => $queue['current_service_point_name']
        ],
        'timeline_steps' => [],
        'flow_history' => $flowHistory,
        'export_info' => [
            'exported_at' => date('Y-m-d H:i:s'),
            'exported_by' => $_SESSION['staff_id'] ?? 'system',
            'format' => $format
        ]
    ];
    
    // Build timeline steps
    $completedSteps = [];
    $currentStep = null;
    
    foreach ($flowHistory as $history) {
        if ($history['to_service_point_id']) {
            $completedSteps[] = $history['to_service_point_id'];
            if ($history['action'] != 'completed') {
                $currentStep = $history['to_service_point_id'];
            }
        }
    }
    
    foreach ($allServicePoints as $sp) {
        $status = 'pending';
        $timestamp = null;
        
        if (in_array($sp['service_point_id'], $completedSteps)) {
            if ($sp['service_point_id'] == $currentStep) {
                $status = 'current';
            } else {
                $status = 'completed';
            }
            
            foreach ($flowHistory as $history) {
                if ($history['to_service_point_id'] == $sp['service_point_id']) {
                    $timestamp = $history['timestamp'];
                    break;
                }
            }
        }
        
        $timelineData['timeline_steps'][] = [
            'service_point_id' => $sp['service_point_id'],
            'point_name' => $sp['point_name'],
            'status' => $status,
            'timestamp' => $timestamp,
            'sequence_order' => $sp['sequence_order']
        ];
    }
    
    // Handle different export formats
    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="queue_timeline_' . $queue['queue_number'] . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($output, ['Queue Number', 'Service Point', 'Status', 'Timestamp', 'Sequence']);
            
            // CSV Data
            foreach ($timelineData['timeline_steps'] as $step) {
                fputcsv($output, [
                    $queue['queue_number'],
                    $step['point_name'],
                    $step['status'],
                    $step['timestamp'] ?? 'N/A',
                    $step['sequence_order']
                ]);
            }
            
            fclose($output);
            break;
            
        case 'html':
            header('Content-Type: text/html; charset=utf-8');
            ?>
            <!DOCTYPE html>
            <html lang="th">
            <head>
                <meta charset="UTF-8">
                <title>Timeline Export - <?php echo htmlspecialchars($queue['queue_number']); ?></title>
                <style>
                    body { font-family: 'Sarabun', sans-serif; margin: 20px; }
                    .header { text-align: center; margin-bottom: 30px; }
                    .timeline-table { width: 100%; border-collapse: collapse; }
                    .timeline-table th, .timeline-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                    .timeline-table th { background-color: #f2f2f2; }
                    .status-completed { color: #28a745; font-weight: bold; }
                    .status-current { color: #ffc107; font-weight: bold; }
                    .status-pending { color: #6c757d; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>Timeline Report</h1>
                    <h2>คิวหมายเลข: <?php echo htmlspecialchars($queue['queue_number']); ?></h2>
                    <p>ประเภท: <?php echo htmlspecialchars($queue['type_name']); ?></p>
                    <p>สร้างเมื่อ: <?php echo date('d/m/Y H:i:s', strtotime($queue['creation_time'])); ?></p>
                </div>
                
                <table class="timeline-table">
                    <thead>
                        <tr>
                            <th>ลำดับ</th>
                            <th>จุดบริการ</th>
                            <th>สถานะ</th>
                            <th>เวลา</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timelineData['timeline_steps'] as $step): ?>
                        <tr>
                            <td><?php echo $step['sequence_order']; ?></td>
                            <td><?php echo htmlspecialchars($step['point_name']); ?></td>
                            <td class="status-<?php echo $step['status']; ?>">
                                <?php
                                switch ($step['status']) {
                                    case 'completed': echo 'เสร็จสิ้น'; break;
                                    case 'current': echo 'กำลังดำเนินการ'; break;
                                    case 'pending': echo 'รอดำเนินการ'; break;
                                }
                                ?>
                            </td>
                            <td><?php echo $step['timestamp'] ? date('d/m/Y H:i:s', strtotime($step['timestamp'])) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 30px; text-align: center; color: #6c757d;">
                    <p>รายงานสร้างเมื่อ: <?php echo date('d/m/Y H:i:s'); ?></p>
                </div>
            </body>
            </html>
            <?php
            break;
            
        default: // json
            header('Content-Type: application/json');
            echo json_encode($timelineData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Export failed',
        'message' => $e->getMessage()
    ]);
}
?>
