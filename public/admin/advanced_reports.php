<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

if (!hasPermission('view_reports')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $db = getDB();
        
        switch ($action) {
            case 'generate_report':
                $templateId = $_POST['template_id'];
                $parameters = [
                    'start_date' => $_POST['start_date'],
                    'end_date' => $_POST['end_date'],
                    'queue_type_id' => $_POST['queue_type_id'] ?? null,
                    'service_point_id' => $_POST['service_point_id'] ?? null,
                    'format' => $_POST['format'] ?? 'html'
                ];
                
                // Generate report
                $reportData = generateReport($templateId, $parameters);
                
                if ($parameters['format'] === 'pdf') {
                    generatePDFReport($reportData, $templateId);
                    exit;
                } elseif ($parameters['format'] === 'excel') {
                    generateExcelReport($reportData, $templateId);
                    exit;
                }
                
                break;
                
            case 'save_template':
                $templateName = $_POST['template_name'];
                $templateDescription = $_POST['template_description'];
                $reportType = $_POST['report_type'];
                $templateConfig = json_encode($_POST['config']);
                
                $stmt = $db->prepare("
                    INSERT INTO report_templates 
                    (template_name, template_description, report_type, template_config, created_by) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$templateName, $templateDescription, $reportType, $templateConfig, $_SESSION['staff_id']]);
                
                logActivity("สร้าง Report Template: {$templateName}");
                
                $message = 'บันทึก Template สำเร็จ';
                $messageType = 'success';
                break;
                
            case 'schedule_report':
                $templateId = $_POST['template_id'];
                $scheduleName = $_POST['schedule_name'];
                $frequency = $_POST['frequency'];
                $scheduleTime = $_POST['schedule_time'];
                $recipients = json_encode(array_filter(explode(',', $_POST['recipients'])));
                
                $stmt = $db->prepare("
                    INSERT INTO scheduled_reports 
                    (template_id, schedule_name, schedule_frequency, schedule_time, recipients, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$templateId, $scheduleName, $frequency, $scheduleTime, $recipients, $_SESSION['staff_id']]);
                
                logActivity("สร้างตารางรายงาน: {$scheduleName}");
                
                $message = 'สร้างตารางรายงานสำเร็จ';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get data for dropdowns
try {
    $db = getDB();
    
    // Get report templates
    $stmt = $db->prepare("SELECT * FROM report_templates WHERE is_active = 1 ORDER BY template_name");
    $stmt->execute();
    $templates = $stmt->fetchAll();
    
    // Get queue types
    $stmt = $db->prepare("SELECT * FROM queue_types WHERE is_active = 1 ORDER BY type_name");
    $stmt->execute();
    $queueTypes = $stmt->fetchAll();
    
    // Get service points
    $stmt = $db->prepare("SELECT * FROM service_points WHERE is_active = 1 ORDER BY point_name");
    $stmt->execute();
    $servicePoints = $stmt->fetchAll();
    
    // Get recent reports
    $stmt = $db->prepare("
        SELECT rel.*, rt.template_name, u.full_name
        FROM report_execution_log rel
        JOIN report_templates rt ON rel.template_id = rt.template_id
        LEFT JOIN users u ON rel.executed_by = u.user_id
        ORDER BY rel.executed_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recentReports = $stmt->fetchAll();
    
} catch (Exception $e) {
    $templates = [];
    $queueTypes = [];
    $servicePoints = [];
    $recentReports = [];
}

function generateReport($templateId, $parameters) {
    $db = getDB();
    
    // Get template
    $stmt = $db->prepare("SELECT * FROM report_templates WHERE template_id = ?");
    $stmt->execute([$templateId]);
    $template = $stmt->fetch();
    
    if (!$template) {
        throw new Exception('ไม่พบ Template');
    }
    
    $config = json_decode($template['template_config'], true);
    $reportData = [];
    
    // Log execution start
    $stmt = $db->prepare("
        INSERT INTO report_execution_log 
        (template_id, execution_type, parameters, status, executed_by) 
        VALUES (?, 'manual', ?, 'running', ?)
    ");
    $stmt->execute([$templateId, json_encode($parameters), $_SESSION['staff_id']]);
    $executionId = $db->lastInsertId();
    
    try {
        $startTime = microtime(true);
        
        switch ($template['report_type']) {
            case 'queue_performance':
                $reportData = generateQueuePerformanceReport($parameters, $config);
                break;
            case 'service_point_analysis':
                $reportData = generateServicePointAnalysisReport($parameters, $config);
                break;
            case 'staff_productivity':
                $reportData = generateStaffProductivityReport($parameters, $config);
                break;
            case 'patient_flow':
                $reportData = generatePatientFlowReport($parameters, $config);
                break;
        }
        
        $executionTime = microtime(true) - $startTime;
        
        // Update execution log
        $stmt = $db->prepare("
            UPDATE report_execution_log 
            SET status = 'completed', execution_time_seconds = ?, completed_at = NOW() 
            WHERE execution_id = ?
        ");
        $stmt->execute([$executionTime, $executionId]);
        
    } catch (Exception $e) {
        // Update execution log with error
        $stmt = $db->prepare("
            UPDATE report_execution_log 
            SET status = 'failed', error_message = ?, completed_at = NOW() 
            WHERE execution_id = ?
        ");
        $stmt->execute([$e->getMessage(), $executionId]);
        throw $e;
    }
    
    return $reportData;
}

function generateQueuePerformanceReport($parameters, $config) {
    $db = getDB();
    
    $whereConditions = ["DATE(q.created_at) BETWEEN ? AND ?"];
    $params = [$parameters['start_date'], $parameters['end_date']];
    
    if ($parameters['queue_type_id']) {
        $whereConditions[] = "q.queue_type_id = ?";
        $params[] = $parameters['queue_type_id'];
    }
    
    if ($parameters['service_point_id']) {
        $whereConditions[] = "q.current_service_point_id = ?";
        $params[] = $parameters['service_point_id'];
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $sql = "
        SELECT 
            DATE(q.created_at) as report_date,
            qt.type_name,
            COUNT(*) as total_queues,
            SUM(CASE WHEN q.status = 'completed' THEN 1 ELSE 0 END) as completed_queues,
            SUM(CASE WHEN q.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_queues,
            AVG(CASE WHEN q.status = 'completed' THEN 
                TIMESTAMPDIFF(MINUTE, q.created_at, q.completed_at) 
            END) as avg_total_time,
            AVG(CASE WHEN q.status = 'completed' THEN 
                TIMESTAMPDIFF(MINUTE, q.called_at, q.completed_at) 
            END) as avg_service_time
        FROM queues q
        JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
        WHERE {$whereClause}
        GROUP BY DATE(q.created_at), q.queue_type_id
        ORDER BY report_date DESC, qt.type_name
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll();
}

function generateServicePointAnalysisReport($parameters, $config) {
    $db = getDB();
    
    $whereConditions = ["DATE(qh.created_at) BETWEEN ? AND ?"];
    $params = [$parameters['start_date'], $parameters['end_date']];
    
    if ($parameters['service_point_id']) {
        $whereConditions[] = "qh.service_point_id = ?";
        $params[] = $parameters['service_point_id'];
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $sql = "
        SELECT 
            sp.point_name,
            COUNT(*) as total_visits,
            AVG(TIMESTAMPDIFF(MINUTE, qh.created_at, qh.completed_at)) as avg_service_time,
            COUNT(DISTINCT qh.queue_id) as unique_queues,
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
    
    return $stmt->fetchAll();
}

function generateStaffProductivityReport($parameters, $config) {
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

function generatePatientFlowReport($parameters, $config) {
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

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานขั้นสูง - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 0;
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 1rem 1.5rem;
            border-radius: 0;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .report-template-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .report-template-card:hover {
            border-color: #007bff;
            box-shadow: 0 4px 12px rgba(0,123,255,0.1);
        }
        
        .report-template-card.selected {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
        
        .metric-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .metric-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            margin: 2rem 0;
        }
        
        .report-filters {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .execution-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-completed { background-color: #d4edda; color: #155724; }
        .status-running { background-color: #fff3cd; color: #856404; }
        .status-failed { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="p-3">
                    <h5 class="text-white mb-4">
                        <i class="fas fa-chart-line me-2"></i>
                        รายงานขั้นสูง
                    </h5>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="#generate-report" data-bs-toggle="pill">
                            <i class="fas fa-file-alt"></i>สร้างรายงาน
                        </a>
                        <a class="nav-link" href="#templates" data-bs-toggle="pill">
                            <i class="fas fa-template"></i>จัดการ Template
                        </a>
                        <a class="nav-link" href="#scheduled" data-bs-toggle="pill">
                            <i class="fas fa-clock"></i>รายงานตามกำหนด
                        </a>
                        <a class="nav-link" href="#history" data-bs-toggle="pill">
                            <i class="fas fa-history"></i>ประวัติรายงาน
                        </a>
                        <hr class="my-3" style="border-color: rgba(255,255,255,0.3);">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-arrow-left"></i>กลับแดชบอร์ด
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="tab-content">
                        <!-- Generate Report Tab -->
                        <div class="tab-pane fade show active" id="generate-report">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h2>สร้างรายงาน</h2>
                                    <p class="text-muted">เลือก Template และกำหนดพารามิเตอร์เพื่อสร้างรายงาน</p>
                                </div>
                            </div>
                            
                            <form method="POST" id="generateReportForm">
                                <input type="hidden" name="action" value="generate_report">
                                
                                <!-- Template Selection -->
                                <div class="content-card">
                                    <h5 class="mb-4">เลือก Report Template</h5>
                                    
                                    <div class="row">
                                        <?php foreach ($templates as $template): ?>
                                            <div class="col-md-6 col-lg-4">
                                                <div class="report-template-card" onclick="selectTemplate(<?php echo $template['template_id']; ?>)">
                                                    <input type="radio" name="template_id" value="<?php echo $template['template_id']; ?>" style="display: none;">
                                                    <h6><?php echo htmlspecialchars($template['template_name']); ?></h6>
                                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($template['template_description']); ?></p>
                                                    <span class="badge bg-primary"><?php echo $template['report_type']; ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Report Parameters -->
                                <div class="content-card">
                                    <h5 class="mb-4">พารามิเตอร์รายงาน</h5>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label">วันที่เริ่มต้น</label>
                                            <input type="date" class="form-control" name="start_date" 
                                                   value="<?php echo date('Y-m-01'); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">วันที่สิ้นสุด</label>
                                            <input type="date" class="form-control" name="end_date" 
                                                   value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <label class="form-label">ประเภทคิว (ทั้งหมด)</label>
                                            <select class="form-select" name="queue_type_id">
                                                <option value="">-- ทั้งหมด --</option>
                                                <?php foreach ($queueTypes as $qt): ?>
                                                    <option value="<?php echo $qt['queue_type_id']; ?>">
                                                        <?php echo htmlspecialchars($qt['type_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">จุดบริการ (ทั้งหมด)</label>
                                            <select class="form-select" name="service_point_id">
                                                <option value="">-- ทั้งหมด --</option>
                                                <?php foreach ($servicePoints as $sp): ?>
                                                    <option value="<?php echo $sp['service_point_id']; ?>">
                                                        <?php echo htmlspecialchars($sp['point_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <label class="form-label">รูปแบบไฟล์</label>
                                            <select class="form-select" name="format">
                                                <option value="html">HTML (ดูในหน้าเว็บ)</option>
                                                <option value="pdf">PDF</option>
                                                <option value="excel">Excel</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-chart-bar me-2"></i>สร้างรายงาน
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Templates Tab -->
                        <div class="tab-pane fade" id="templates">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h2>จัดการ Report Templates</h2>
                                    <p class="text-muted">สร้างและจัดการ Template สำหรับรายงาน</p>
                                </div>
                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newTemplateModal">
                                    <i class="fas fa-plus me-2"></i>สร้าง Template ใหม่
                                </button>
                            </div>
                            
                            <div class="content-card">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ชื่อ Template</th>
                                                <th>ประเภท</th>
                                                <th>คำอธิบาย</th>
                                                <th>สร้างเมื่อ</th>
                                                <th>การจัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($templates as $template): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($template['template_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-info">
                                                            <?php echo $template['report_type']; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($template['template_description']); ?></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($template['created_at'])); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary me-1" 
                                                                onclick="editTemplate(<?php echo $template['template_id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" 
                                                                onclick="deleteTemplate(<?php echo $template['template_id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Scheduled Reports Tab -->
                        <div class="tab-pane fade" id="scheduled">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h2>รายงานตามกำหนดเวลา</h2>
                                    <p class="text-muted">ตั้งค่าให้ระบบส่งรายงานอัตโนมัติ</p>
                                </div>
                                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#scheduleReportModal">
                                    <i class="fas fa-clock me-2"></i>สร้างตารางใหม่
                                </button>
                            </div>
                            
                            <div class="content-card">
                                <p class="text-center text-muted">ฟีเจอร์นี้จะพร้อมใช้งานในเร็วๆ นี้</p>
                            </div>
                        </div>
                        
                        <!-- History Tab -->
                        <div class="tab-pane fade" id="history">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <h2>ประวัติการสร้างรายงาน</h2>
                                    <p class="text-muted">ดูประวัติรายงานที่สร้างไว้</p>
                                </div>
                            </div>
                            
                            <div class="content-card">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Template</th>
                                                <th>สถานะ</th>
                                                <th>ผู้สร้าง</th>
                                                <th>เวลาที่ใช้</th>
                                                <th>สร้างเมื่อ</th>
                                                <th>การจัดการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentReports as $report): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($report['template_name']); ?></td>
                                                    <td>
                                                        <span class="execution-status status-<?php echo $report['status']; ?>">
                                                            <?php 
                                                            switch($report['status']) {
                                                                case 'completed': echo 'สำเร็จ'; break;
                                                                case 'running': echo 'กำลังดำเนินการ'; break;
                                                                case 'failed': echo 'ล้มเหลว'; break;
                                                            }
                                                            ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($report['full_name'] ?? 'ระบบ'); ?></td>
                                                    <td>
                                                        <?php if ($report['execution_time_seconds']): ?>
                                                            <?php echo number_format($report['execution_time_seconds'], 2); ?> วินาที
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('d/m/Y H:i:s', strtotime($report['executed_at'])); ?></td>
                                                    <td>
                                                        <?php if ($report['status'] === 'completed' && $report['file_path']): ?>
                                                            <a href="<?php echo $report['file_path']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        function selectTemplate(templateId) {
            // Remove previous selection
            $('.report-template-card').removeClass('selected');
            
            // Select current template
            $(`input[value="${templateId}"]`).prop('checked', true);
            $(`input[value="${templateId}"]`).closest('.report-template-card').addClass('selected');
        }
        
        function editTemplate(templateId) {
            // Implementation for editing template
            alert('ฟีเจอร์แก้ไข Template จะพร้อมใช้งานในเร็วๆ นี้');
        }
        
        function deleteTemplate(templateId) {
            if (confirm('คุณต้องการลบ Template นี้หรือไม่?')) {
                // Implementation for deleting template
                alert('ฟีเจอร์ลบ Template จะพร้อมใช้งานในเร็วๆ นี้');
            }
        }
        
        // Form validation
        $('#generateReportForm').on('submit', function(e) {
            if (!$('input[name="template_id"]:checked').val()) {
                e.preventDefault();
                alert('กรุณาเลือก Report Template');
                return false;
            }
        });
    </script>
</body>
</html>
