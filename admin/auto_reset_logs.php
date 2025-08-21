<?php
require_once '../config/config.php';
requireLogin();

if (!hasPermission('view_reports')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

// Get filter parameters
$scheduleId = $_GET['schedule_id'] ?? '';
$status = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

try {
    $db = getDB();
    
    // Get schedules for filter
    $stmt = $db->prepare("SELECT schedule_id, schedule_name FROM auto_reset_schedules ORDER BY schedule_name");
    $stmt->execute();
    $schedules = $stmt->fetchAll();
    
    // Build WHERE clause
    $whereConditions = [];
    $params = [];
    
    // Date range
    $whereConditions[] = "DATE(arl.executed_at) BETWEEN ? AND ?";
    $params[] = $dateFrom;
    $params[] = $dateTo;
    
    // Schedule filter
    if ($scheduleId) {
        $whereConditions[] = "arl.schedule_id = ?";
        $params[] = $scheduleId;
    }
    
    // Status filter
    if ($status) {
        $whereConditions[] = "arl.status = ?";
        $params[] = $status;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get total count
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM auto_reset_logs arl
        WHERE {$whereClause}
    ");
    $stmt->execute($params);
    $totalRecords = $stmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $perPage);
    
    // Get logs
    $stmt = $db->prepare("
        SELECT 
            arl.*,
            ars.schedule_name,
            CASE 
                WHEN arl.reset_type = 'by_type' THEN qt.type_name
                WHEN arl.reset_type = 'by_service_point' THEN sp.point_name
                ELSE 'ทุกประเภท'
            END as target_name,
            DATE_FORMAT(arl.executed_at, '%d/%m/%Y %H:%i:%s') as formatted_time
        FROM auto_reset_logs arl
        LEFT JOIN auto_reset_schedules ars ON arl.schedule_id = ars.schedule_id
        LEFT JOIN queue_types qt ON arl.target_id = qt.queue_type_id AND arl.reset_type = 'by_type'
        LEFT JOIN service_points sp ON arl.target_id = sp.service_point_id AND arl.reset_type = 'by_service_point'
        WHERE {$whereClause}
        ORDER BY arl.executed_at DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // Get summary statistics
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_executions,
            SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
            SUM(reset_count) as total_resets,
            AVG(execution_time) as avg_time,
            MAX(execution_time) as max_time
        FROM auto_reset_logs arl
        WHERE {$whereClause}
    ");
    $stmt->execute($params);
    $summary = $stmt->fetch();
    
} catch (Exception $e) {
    $logs = [];
    $schedules = [];
    $summary = ['total_executions' => 0, 'successful' => 0, 'failed' => 0, 'total_resets' => 0, 'avg_time' => 0, 'max_time' => 0];
    $totalRecords = 0;
    $totalPages = 0;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติ Auto Reset - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
        
        .filter-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .summary-item {
            text-align: center;
            padding: 1.5rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .summary-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .summary-success .summary-number { color: #28a745; }
        .summary-failed .summary-number { color: #dc3545; }
        .summary-total .summary-number { color: #007bff; }
        .summary-time .summary-number { color: #6f42c1; }
        
        .log-item {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
        }
        
        .log-item.success {
            border-left-color: #28a745;
        }
        
        .log-item.failed {
            border-left-color: #dc3545;
        }
        
        .log-item.skipped {
            border-left-color: #ffc107;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-skipped {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .execution-time {
            background-color: #e9ecef;
            color: #495057;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-family: monospace;
        }
        
        .affected-types {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 0.5rem;
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }
        
        .pagination-wrapper {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-top: 2rem;
        }
        
        @media (max-width: 768px) {
            .summary-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .log-item {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="p-3">
                    <h5 class="text-white mb-4">
                        <i class="fas fa-cogs me-2"></i>
                        จัดการระบบ
                    </h5>
                    
                    <?php include 'nav.php'; ?>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2>ประวัติ Auto Reset</h2>
                            <p class="text-muted">ติดตามประวัติการ Reset คิวอัตโนมัติ</p>
                        </div>
                        <div>
                            <a href="auto_reset_settings.php" class="btn btn-outline-primary">
                                <i class="fas fa-cog me-2"></i>ตั้งค่า Auto Reset
                            </a>
                        </div>
                    </div>
                    
                    <!-- Summary Statistics -->
                    <div class="summary-grid">
                        <div class="summary-item summary-total">
                            <div class="summary-number"><?php echo number_format($summary['total_executions'] ?? 0); ?></div>
                            <div>การรันทั้งหมด</div>
                        </div>
                        <div class="summary-item summary-success">
                            <div class="summary-number"><?php echo number_format($summary['successful'] ?? 0); ?></div>
                            <div>สำเร็จ</div>
                        </div>
                        <div class="summary-item summary-failed">
                            <div class="summary-number"><?php echo number_format($summary['failed'] ?? 0); ?></div>
                            <div>ล้มเหลว</div>
                        </div>
                        <div class="summary-item summary-total">
                            <div class="summary-number"><?php echo number_format($summary['total_resets'] ?? 0); ?></div>
                            <div>คิวที่ Reset</div>
                        </div>
                        <div class="summary-item summary-time">
                            <div class="summary-number"><?php echo number_format($summary['avg_time'] ?? 0, 3); ?>s</div>
                            <div>เวลาเฉลี่ย</div>
                        </div>
                        <div class="summary-item summary-time">
                            <div class="summary-number"><?php echo number_format($summary['max_time'] ?? 0, 3); ?>s</div>
                            <div>เวลาสูงสุด</div>
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="filter-card">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">วันที่เริ่มต้น</label>
                                <input type="date" class="form-control" name="date_from" value="<?php echo $dateFrom; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">วันที่สิ้นสุด</label>
                                <input type="date" class="form-control" name="date_to" value="<?php echo $dateTo; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Schedule</label>
                                <select class="form-select" name="schedule_id">
                                    <option value="">ทุก Schedule</option>
                                    <?php foreach ($schedules as $schedule): ?>
                                        <option value="<?php echo $schedule['schedule_id']; ?>" 
                                                <?php echo $scheduleId == $schedule['schedule_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($schedule['schedule_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">สถานะ</label>
                                <div class="input-group">
                                    <select class="form-select" name="status">
                                        <option value="">ทุกสถานะ</option>
                                        <option value="success" <?php echo $status === 'success' ? 'selected' : ''; ?>>สำเร็จ</option>
                                        <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>ล้มเหลว</option>
                                        <option value="skipped" <?php echo $status === 'skipped' ? 'selected' : ''; ?>>ข้าม</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Logs -->
                    <div class="content-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5>ประวัติการ Reset</h5>
                            <div>
                                <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
                                    <i class="fas fa-sync-alt me-1"></i>รีเฟรช
                                </button>
                                <button class="btn btn-outline-success btn-sm" onclick="exportLogs()">
                                    <i class="fas fa-download me-1"></i>ส่งออก
                                </button>
                            </div>
                        </div>
                        
                        <?php if (empty($logs)): ?>
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-history fa-3x mb-3"></i>
                                <h6>ไม่พบประวัติการ Reset</h6>
                                <p>ในช่วงเวลาที่เลือก</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <div class="log-item <?php echo $log['status']; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="d-flex align-items-center mb-2">
                                                <h6 class="mb-0 me-3"><?php echo htmlspecialchars($log['schedule_name'] ?: 'Manual Reset'); ?></h6>
                                                <span class="status-badge status-<?php echo $log['status']; ?>">
                                                    <?php 
                                                    echo match($log['status']) {
                                                        'success' => 'สำเร็จ',
                                                        'failed' => 'ล้มเหลว',
                                                        'skipped' => 'ข้าม',
                                                        default => $log['status']
                                                    };
                                                    ?>
                                                </span>
                                                <span class="execution-time ms-2">
                                                    <?php echo number_format($log['execution_time'], 3); ?>s
                                                </span>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-target me-1"></i>
                                                    <?php echo htmlspecialchars($log['target_name']); ?>
                                                    
                                                    <i class="fas fa-list-ol ms-3 me-1"></i>
                                                    Reset <?php echo number_format($log['reset_count']); ?> ประเภท
                                                </small>
                                            </div>
                                            
                                            <?php if ($log['affected_types']): ?>
                                                <?php $affectedTypes = json_decode($log['affected_types'], true); ?>
                                                <?php if (!empty($affectedTypes)): ?>
                                                    <div class="affected-types">
                                                        <small class="text-muted">ประเภทที่ Reset:</small>
                                                        <div class="mt-1">
                                                            <?php foreach ($affectedTypes as $type): ?>
                                                                <span class="badge bg-light text-dark me-1"><?php echo htmlspecialchars($type); ?></span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <?php if ($log['error_message']): ?>
                                                <div class="mt-2">
                                                    <small class="text-danger">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                                        <?php echo htmlspecialchars($log['error_message']); ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-md-4 text-end">
                                            <div class="fw-bold text-primary mb-1">
                                                <?php echo $log['formatted_time']; ?>
                                            </div>
                                            <small class="text-muted">
                                                ID: <?php echo $log['log_id']; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination-wrapper">
                                <div>
                                    แสดง <?php echo number_format(($page - 1) * $perPage + 1); ?> - 
                                    <?php echo number_format(min($page * $perPage, $totalRecords)); ?> 
                                    จาก <?php echo number_format($totalRecords); ?> รายการ
                                </div>
                                
                                <nav>
                                    <ul class="pagination mb-0">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $startPage = max(1, $page - 2);
                                        $endPage = min($totalPages, $page + 2);
                                        
                                        for ($i = $startPage; $i <= $endPage; $i++):
                                        ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportLogs() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', '1');
            window.open('../api/export_auto_reset_logs.php?' + params.toString(), '_blank');
        }
        
        // Auto-refresh every 60 seconds
        setInterval(function() {
            if (!document.hidden) {
                location.reload();
            }
        }, 60000);
    </script>
</body>
</html>
