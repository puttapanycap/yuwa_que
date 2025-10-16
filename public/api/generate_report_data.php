<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

header('Content-Type: application/json');

if (!hasPermission('view_reports')) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

$templateId = $_POST['template_id'] ?? null;
$parameters = [
    'start_date' => $_POST['start_date'] ?? date('Y-m-01'),
    'end_date' => $_POST['end_date'] ?? date('Y-m-d'),
    'queue_type_id' => $_POST['queue_type_id'] ?? null,
    'service_point_id' => $_POST['service_point_id'] ?? null
];

if (!$templateId) {
    echo json_encode(['success' => false, 'message' => 'ไม่ได้ระบุ Template']);
    exit;
}

try {
    $db = getDB();
    
    // Get template
    $stmt = $db->prepare("SELECT * FROM report_templates WHERE template_id = ?");
    $stmt->execute([$templateId]);
    $template = $stmt->fetch();
    
    if (!$template) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบ Template']);
        exit;
    }
    
    $config = json_decode($template['template_config'], true);
    $reportData = [];
    
    switch ($template['report_type']) {
        case 'queue_performance':
            $reportData = generateQueuePerformanceData($parameters, $config);
            break;
        case 'service_point_analysis':
            $reportData = generateServicePointAnalysisData($parameters, $config);
            break;
        case 'staff_productivity':
            $reportData = generateStaffProductivityData($parameters, $config);
            break;
        case 'patient_flow':
            $reportData = generatePatientFlowData($parameters, $config);
            break;
    }
    
    echo json_encode([
        'success' => true,
        'template' => $template,
        'data' => $reportData,
        'parameters' => $parameters
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function generateQueuePerformanceData($parameters, $config) {
    $db = getDB();
    
    $whereConditions = ["DATE(q.created_at) BETWEEN ? AND ?"];
    $params = [$parameters['start_date'], $parameters['end_date']];
    
    if ($parameters['queue_type_id']) {
        $whereConditions[] = "q.queue_type_id = ?";
        $params[] = $parameters['queue_type_id'];
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Summary data
    $sql = "
        SELECT 
            COUNT(*) as total_queues,
            SUM(CASE WHEN q.status = 'completed' THEN 1 ELSE 0 END) as completed_queues,
            SUM(CASE WHEN q.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_queues,
            SUM(CASE WHEN q.status = 'waiting' THEN 1 ELSE 0 END) as waiting_queues,
            AVG(CASE WHEN q.status = 'completed' THEN 
                TIMESTAMPDIFF(MINUTE, q.created_at, q.completed_at) 
            END) as avg_total_time,
            AVG(CASE WHEN q.status = 'completed' THEN 
                TIMESTAMPDIFF(MINUTE, q.called_at, q.completed_at) 
            END) as avg_service_time
        FROM queues q
        WHERE {$whereClause}
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $summary = $stmt->fetch();
    
    // Daily breakdown
    $sql = "
        SELECT 
            DATE(q.created_at) as report_date,
            COUNT(*) as total_queues,
            SUM(CASE WHEN q.status = 'completed' THEN 1 ELSE 0 END) as completed_queues,
            AVG(CASE WHEN q.status = 'completed' THEN 
                TIMESTAMPDIFF(MINUTE, q.created_at, q.completed_at) 
            END) as avg_total_time
        FROM queues q
        WHERE {$whereClause}
        GROUP BY DATE(q.created_at)
        ORDER BY report_date
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $daily = $stmt->fetchAll();
    
    // Queue type breakdown
    $sql = "
        SELECT 
            qt.type_name,
            COUNT(*) as total_queues,
            SUM(CASE WHEN q.status = 'completed' THEN 1 ELSE 0 END) as completed_queues,
            AVG(CASE WHEN q.status = 'completed' THEN 
                TIMESTAMPDIFF(MINUTE, q.created_at, q.completed_at) 
            END) as avg_total_time
        FROM queues q
        JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
        WHERE {$whereClause}
        GROUP BY q.queue_type_id
        ORDER BY total_queues DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $byType = $stmt->fetchAll();
    
    return [
        'summary' => $summary,
        'daily' => $daily,
        'by_type' => $byType
    ];
}

function generateServicePointAnalysisData($parameters, $config) {
    $db = getDB();
    
    $whereConditions = ["DATE(qh.created_at) BETWEEN ? AND ?"];
    $params = [$parameters['start_date'], $parameters['end_date']];
    
    if ($parameters['service_point_id']) {
        $whereConditions[] = "qh.service_point_id = ?";
        $params[] = $parameters['service_point_id'];
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Service point utilization
    $sql = "
        SELECT 
            sp.point_name,
            COUNT(*) as total_visits,
            AVG(TIMESTAMPDIFF(MINUTE, qh.created_at, qh.completed_at)) as avg_service_time,
            COUNT(DISTINCT qh.queue_id) as unique_queues
        FROM queue_history qh
        JOIN service_points sp ON qh.service_point_id = sp.service_point_id
        WHERE {$whereClause} AND qh.action = 'served'
        GROUP BY qh.service_point_id
        ORDER BY total_visits DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $utilization = $stmt->fetchAll();
    
    // Hourly distribution
    $sql = "
        SELECT 
            sp.point_name,
            HOUR(qh.created_at) as hour_of_day,
            COUNT(*) as hourly_count
        FROM queue_history qh
        JOIN service_points sp ON qh.service_point_id = sp.service_point_id
        WHERE {$whereClause} AND qh.action = 'served'
        GROUP BY qh.service_point_id, HOUR(qh.created_at)
        ORDER BY sp.point_name, hour_of_day
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $hourly = $stmt->fetchAll();
    
    return [
        'utilization' => $utilization,
        'hourly' => $hourly
    ];
}

function generateStaffProductivityData($parameters, $config) {
    $db = getDB();
    
    $whereConditions = ["DATE(qh.created_at) BETWEEN ? AND ?"];
    $params = [$parameters['start_date'], $parameters['end_date']];
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $sql = "
        SELECT 
            u.full_name as staff_name,
            sp.point_name,
            COUNT(*) as queues_served,
            AVG(TIMESTAMPDIFF(MINUTE, qh.created_at, qh.completed_at)) as avg_service_time,
            COUNT(DISTINCT DATE(qh.created_at)) as working_days,
            COUNT(*) / COUNT(DISTINCT DATE(qh.created_at)) as avg_queues_per_day
        FROM queue_history qh
        JOIN users u ON qh.created_by = u.user_id
        JOIN service_points sp ON qh.service_point_id = sp.service_point_id
        WHERE {$whereClause} AND qh.action = 'served'
        GROUP BY qh.created_by, qh.service_point_id
        ORDER BY queues_served DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

function generatePatientFlowData($parameters, $config) {
    $db = getDB();
    
    $whereConditions = ["DATE(q.created_at) BETWEEN ? AND ?"];
    $params = [$parameters['start_date'], $parameters['end_date']];
    
    if ($parameters['queue_type_id']) {
        $whereConditions[] = "q.queue_type_id = ?";
        $params[] = $parameters['queue_type_id'];
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $sql = "
        SELECT 
            qt.type_name,
            COUNT(DISTINCT q.queue_id) as total_flows,
            AVG(TIMESTAMPDIFF(MINUTE, q.created_at, q.completed_at)) as avg_flow_time,
            COUNT(DISTINCT CASE WHEN q.status = 'completed' THEN q.queue_id END) as completed_flows,
            (COUNT(DISTINCT CASE WHEN q.status = 'completed' THEN q.queue_id END) * 100.0 / COUNT(DISTINCT q.queue_id)) as completion_rate
        FROM queues q
        JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
        WHERE {$whereClause}
        GROUP BY q.queue_type_id
        ORDER BY completion_rate DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}
?>
