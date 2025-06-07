<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

$widgetId = $_GET['widget_id'] ?? null;

if (!$widgetId) {
    echo json_encode(['error' => 'Widget ID required']);
    exit;
}

try {
    $db = getDB();
    
    // Get widget configuration
    $stmt = $db->prepare("SELECT * FROM dashboard_widgets WHERE widget_id = ? AND is_active = 1");
    $stmt->execute([$widgetId]);
    $widget = $stmt->fetch();
    
    if (!$widget) {
        echo json_encode(['error' => 'Widget not found']);
        exit;
    }
    
    $config = json_decode($widget['widget_config'], true);
    $query = $config['query'] ?? '';
    
    $data = [];
    
    switch ($query) {
        case 'waiting_queues':
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM queues WHERE current_status = 'waiting'");
            $stmt->execute();
            $data['value'] = $stmt->fetch()['count'];
            break;
            
        case 'completed_today':
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM queues WHERE current_status = 'completed' AND DATE(creation_time) = CURDATE()");
            $stmt->execute();
            $data['value'] = $stmt->fetch()['count'];
            break;
            
        case 'avg_wait_time':
            $stmt = $db->prepare("
                SELECT AVG(TIMESTAMPDIFF(MINUTE, creation_time, COALESCE(first_called_time, NOW()))) as avg_time
                FROM queues 
                WHERE DATE(creation_time) = CURDATE()
                AND current_status IN ('waiting', 'called', 'processing', 'completed')
            ");
            $stmt->execute();
            $result = $stmt->fetch();
            $data['value'] = round($result['avg_time'] ?? 0, 1);
            $data['unit'] = ' นาที';
            break;
            
        case 'active_service_points':
            $stmt = $db->prepare("
                SELECT COUNT(DISTINCT sp.service_point_id) as count
                FROM service_points sp
                LEFT JOIN queues q ON sp.service_point_id = q.current_service_point_id 
                    AND q.current_status IN ('called', 'processing')
                WHERE sp.is_active = 1 AND q.queue_id IS NOT NULL
            ");
            $stmt->execute();
            $data['value'] = $stmt->fetch()['count'];
            break;
            
        case 'hourly_queues':
            $stmt = $db->prepare("
                SELECT 
                    HOUR(creation_time) as hour,
                    COUNT(*) as count
                FROM queues 
                WHERE DATE(creation_time) = CURDATE()
                GROUP BY HOUR(creation_time)
                ORDER BY hour
            ");
            $stmt->execute();
            $hourlyData = $stmt->fetchAll();
            
            $labels = [];
            $values = [];
            for ($i = 0; $i < 24; $i++) {
                $labels[] = sprintf('%02d:00', $i);
                $values[] = 0;
            }
            
            foreach ($hourlyData as $row) {
                $values[$row['hour']] = $row['count'];
            }
            
            $data['chartData'] = [
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'จำนวนคิว',
                    'data' => $values,
                    'borderColor' => 'rgb(102, 126, 234)',
                    'backgroundColor' => 'rgba(102, 126, 234, 0.1)',
                    'tension' => 0.4
                ]]
            ];
            break;
            
        case 'service_point_status':
            $stmt = $db->prepare("
                SELECT 
                    sp.point_name,
                    CASE 
                        WHEN q.queue_id IS NOT NULL THEN 'ใช้งาน'
                        ELSE 'ว่าง'
                    END as status,
                    q.queue_number,
                    q.patient_name
                FROM service_points sp
                LEFT JOIN queues q ON sp.service_point_id = q.current_service_point_id 
                    AND q.current_status IN ('called', 'processing')
                WHERE sp.is_active = 1
                ORDER BY sp.display_order
            ");
            $stmt->execute();
            $data['rows'] = $stmt->fetchAll();
            break;
            
        case 'queue_type_distribution':
            $stmt = $db->prepare("
                SELECT 
                    qt.type_name as label,
                    COUNT(q.queue_id) as count
                FROM queue_types qt
                LEFT JOIN queues q ON qt.queue_type_id = q.queue_type_id 
                    AND DATE(q.creation_time) = CURDATE()
                WHERE qt.is_active = 1
                GROUP BY qt.queue_type_id, qt.type_name
                ORDER BY count DESC
            ");
            $stmt->execute();
            $typeData = $stmt->fetchAll();
            
            $labels = [];
            $values = [];
            $colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'];
            
            foreach ($typeData as $i => $row) {
                $labels[] = $row['label'];
                $values[] = $row['count'];
            }
            
            $data['chartData'] = [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $values,
                    'backgroundColor' => array_slice($colors, 0, count($values))
                ]]
            ];
            break;
            
        case 'recent_queues':
            $stmt = $db->prepare("
                SELECT 
                    q.queue_number,
                    qt.type_name,
                    q.patient_name,
                    q.current_status,
                    TIME_FORMAT(q.creation_time, '%H:%i') as created_time
                FROM queues q
                LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
                WHERE DATE(q.creation_time) = CURDATE()
                ORDER BY q.creation_time DESC
                LIMIT 10
            ");
            $stmt->execute();
            $data['rows'] = $stmt->fetchAll();
            break;
            
        default:
            $data['error'] = 'Unknown query type';
    }
    
    // Store metrics for analytics
    if (!isset($data['error'])) {
        $stmt = $db->prepare("INSERT INTO real_time_metrics (metric_name, metric_value, metric_data) VALUES (?, ?, ?)");
        $stmt->execute([
            $widget['widget_name'],
            $data['value'] ?? 0,
            json_encode($data)
        ]);
    }
    
    echo json_encode($data);
    
} catch (Exception $e) {
    Logger::error("Failed to get widget data: " . $e->getMessage(), ['widget_id' => $widgetId]);
    echo json_encode(['error' => 'Failed to load widget data']);
}
?>
