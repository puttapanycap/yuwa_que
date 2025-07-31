<?php
require_once '../config/config.php';
requireLogin();

if (!hasPermission('view_reports')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

// Get date range from URL parameters
$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$servicePointId = $_GET['service_point'] ?? '';

// Validate dates
if (strtotime($startDate) > strtotime($endDate)) {
    $temp = $startDate;
    $startDate = $endDate;
    $endDate = $temp;
}

try {
    $db = getDB();
    
    // Get service points for filter
    $stmt = $db->prepare("SELECT * FROM service_points WHERE is_active = 1 ORDER BY display_order, point_name");
    $stmt->execute();
    $servicePoints = $stmt->fetchAll();
    
    // Build WHERE clause for service point filter
    $servicePointWhere = '';
    $servicePointParams = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
    if ($servicePointId) {
        $servicePointWhere = ' AND q.current_service_point_id = ?';
        $servicePointParams[] = $servicePointId;
    }
    
    // Queue statistics
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_queues,
            SUM(CASE WHEN current_status = 'completed' THEN 1 ELSE 0 END) as completed_queues,
            SUM(CASE WHEN current_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_queues,
            SUM(CASE WHEN current_status IN ('waiting', 'called', 'processing') THEN 1 ELSE 0 END) as active_queues,
            AVG(called_count) as avg_call_count
        FROM queues q
        WHERE q.creation_time BETWEEN ? AND ?" . $servicePointWhere
    );
    $stmt->execute($servicePointParams);
    $queueStats = $stmt->fetch();
    
    // Queue by type
    $stmt = $db->prepare("
        SELECT qt.type_name, COUNT(*) as queue_count,
               SUM(CASE WHEN q.current_status = 'completed' THEN 1 ELSE 0 END) as completed_count
        FROM queues q
        JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
        WHERE q.creation_time BETWEEN ? AND ?" . $servicePointWhere . "
        GROUP BY qt.queue_type_id, qt.type_name
        ORDER BY queue_count DESC
    ");
    $stmt->execute($servicePointParams);
    $queueByType = $stmt->fetchAll();
    
    // Queue by service point
    $stmt = $db->prepare("
        SELECT sp.point_name, COUNT(*) as queue_count,
               SUM(CASE WHEN q.current_status = 'completed' THEN 1 ELSE 0 END) as completed_count,
               AVG(q.called_count) as avg_call_count
        FROM queues q
        JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
        WHERE q.creation_time BETWEEN ? AND ?" . ($servicePointId ? ' AND q.current_service_point_id = ?' : '') . "
        GROUP BY sp.service_point_id, sp.point_name
        ORDER BY queue_count DESC
    ");
    $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
    if ($servicePointId) $params[] = $servicePointId;
    $stmt->execute($params);
    $queueByServicePoint = $stmt->fetchAll();
    
    // Hourly distribution
    $stmt = $db->prepare("
        SELECT HOUR(creation_time) as hour, COUNT(*) as queue_count
        FROM queues q
        WHERE q.creation_time BETWEEN ? AND ?" . $servicePointWhere . "
        GROUP BY HOUR(creation_time)
        ORDER BY hour
    ");
    $stmt->execute($servicePointParams);
    $hourlyDistribution = $stmt->fetchAll();
    
    // Daily trend (last 7 days)
    $stmt = $db->prepare("
        SELECT DATE(creation_time) as queue_date, COUNT(*) as queue_count,
               SUM(CASE WHEN current_status = 'completed' THEN 1 ELSE 0 END) as completed_count
        FROM queues q
        WHERE q.creation_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)" . $servicePointWhere . "
        GROUP BY DATE(creation_time)
        ORDER BY queue_date
    ");
    $params = [];
    if ($servicePointId) $params[] = $servicePointId;
    $stmt->execute($params);
    $dailyTrend = $stmt->fetchAll();
    
} catch (Exception $e) {
    $queueStats = ['total_queues' => 0, 'completed_queues' => 0, 'cancelled_queues' => 0, 'active_queues' => 0, 'avg_call_count' => 0];
    $queueByType = [];
    $queueByServicePoint = [];
    $hourlyDistribution = [];
    $dailyTrend = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานและสถิติ - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    
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
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 1.5rem;
            border-left: 4px solid #007bff;
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin: 1rem 0;
        }
        
        .filter-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
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
                    
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>แดชบอร์ด
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i>จัดการผู้ใช้
                        </a>
                        <a class="nav-link" href="roles.php">
                            <i class="fas fa-user-tag"></i>บทบาทและสิทธิ์
                        </a>
                        <a class="nav-link" href="service_points.php">
                            <i class="fas fa-map-marker-alt"></i>จุดบริการ
                        </a>
                        <a class="nav-link" href="queue_types.php">
                            <i class="fas fa-list"></i>ประเภทคิว
                        </a>
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog"></i>การตั้งค่า
                        </a>
                        <a class="nav-link active" href="environment_settings.php">
                            <i class="fas fa-server"></i>Environment
                        </a>
                        <a class="nav-link active" href="reports.php">
                            <i class="fas fa-chart-bar"></i>รายงาน
                        </a>
                        <a class="nav-link" href="audit_logs.php">
                            <i class="fas fa-history"></i>บันทึกการใช้งาน
                        </a>
                        <hr class="my-3" style="border-color: rgba(255,255,255,0.3);">
                        <a class="nav-link" href="../staff/dashboard.php">
                            <i class="fas fa-arrow-left"></i>กลับหน้าเจ้าหน้าที่
                        </a>
                        <a class="nav-link" href="../staff/logout.php">
                            <i class="fas fa-sign-out-alt"></i>ออกจากระบบ
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2>รายงานและสถิติ</h2>
                            <p class="text-muted">สถิติการใช้งานระบบเรียกคิว</p>
                        </div>
                        <button class="btn btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>พิมพ์รายงาน
                        </button>
                    </div>
                    
                    <!-- Filters -->
                    <div class="filter-card">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">วันที่เริ่มต้น</label>
                                <input type="date" class="form-control" name="start_date" value="<?php echo $startDate; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">วันที่สิ้นสุด</label>
                                <input type="date" class="form-control" name="end_date" value="<?php echo $endDate; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">จุดบริการ</label>
                                <select class="form-select" name="service_point">
                                    <option value="">ทุกจุดบริการ</option>
                                    <?php foreach ($servicePoints as $sp): ?>
                                        <option value="<?php echo $sp['service_point_id']; ?>" 
                                                <?php echo $servicePointId == $sp['service_point_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($sp['point_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block w-100">
                                    <i class="fas fa-search me-2"></i>ค้นหา
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Statistics Overview -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number text-primary"><?php echo number_format($queueStats['total_queues'] ?? 0); ?></div>
                                <div class="text-muted">คิวทั้งหมด</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number text-success"><?php echo number_format($queueStats['completed_queues'] ?? 0); ?></div>
                                <div class="text-muted">เสร็จสิ้น</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number text-danger"><?php echo number_format($queueStats['cancelled_queues'] ?? 0); ?></div>
                                <div class="text-muted">ยกเลิก</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number text-warning"><?php echo number_format($queueStats['active_queues'] ?? 0); ?></div>
                                <div class="text-muted">กำลังดำเนินการ</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row">
                        <!-- Queue by Type -->
                        <div class="col-md-6">
                            <div class="content-card">
                                <h5 class="mb-4">คิวตามประเภท</h5>
                                <div class="chart-container">
                                    <canvas id="queueByTypeChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hourly Distribution -->
                        <div class="col-md-6">
                            <div class="content-card">
                                <h5 class="mb-4">การกระจายตัวตามชั่วโมง</h5>
                                <div class="chart-container">
                                    <canvas id="hourlyChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Daily Trend -->
                    <div class="content-card">
                        <h5 class="mb-4">แนวโน้มรายวัน (7 วันล่าสุด)</h5>
                        <div class="chart-container">
                            <canvas id="dailyTrendChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Tables Row -->
                    <div class="row">
                        <!-- Queue by Service Point -->
                        <div class="col-md-6">
                            <div class="content-card">
                                <h5 class="mb-4">สถิติตามจุดบริการ</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>จุดบริการ</th>
                                                <th class="text-center">จำนวนคิว</th>
                                                <th class="text-center">เสร็จสิ้น</th>
                                                <th class="text-center">เฉลี่ยการเรียก</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($queueByServicePoint as $row): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['point_name']); ?></td>
                                                    <td class="text-center"><?php echo number_format($row['queue_count'] ?? 0); ?></td>
                                                    <td class="text-center"><?php echo number_format($row['completed_count'] ?? 0); ?></td>
                                                    <td class="text-center"><?php echo number_format($row['avg_call_count'] ?? 0, 1); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Queue by Type Table -->
                        <div class="col-md-6">
                            <div class="content-card">
                                <h5 class="mb-4">สถิติตามประเภทคิว</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>ประเภทคิว</th>
                                                <th class="text-center">จำนวนคิว</th>
                                                <th class="text-center">เสร็จสิ้น</th>
                                                <th class="text-center">อัตราสำเร็จ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($queueByType as $row): ?>
                                                <?php $successRate = $row['queue_count'] > 0 ? ($row['completed_count'] / $row['queue_count']) * 100 : 0; ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['type_name']); ?></td>
                                                    <td class="text-center"><?php echo number_format($row['queue_count'] ?? 0); ?></td>
                                                    <td class="text-center"><?php echo number_format($row['completed_count'] ?? 0); ?></td>
                                                    <td class="text-center"><?php echo number_format($successRate, 1); ?>%</td>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Queue by Type Chart
        const queueByTypeData = <?php echo json_encode($queueByType); ?>;
        const queueByTypeChart = new Chart(document.getElementById('queueByTypeChart'), {
            type: 'doughnut',
            data: {
                labels: queueByTypeData.map(item => item.type_name),
                datasets: [{
                    data: queueByTypeData.map(item => item.queue_count),
                    backgroundColor: [
                        '#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Hourly Distribution Chart
        const hourlyData = <?php echo json_encode($hourlyDistribution); ?>;
        const hourlyLabels = [];
        const hourlyCounts = [];
        
        for (let i = 0; i < 24; i++) {
            hourlyLabels.push(i + ':00');
            const found = hourlyData.find(item => item.hour == i);
            hourlyCounts.push(found ? found.queue_count : 0);
        }
        
        const hourlyChart = new Chart(document.getElementById('hourlyChart'), {
            type: 'bar',
            data: {
                labels: hourlyLabels,
                datasets: [{
                    label: 'จำนวนคิว',
                    data: hourlyCounts,
                    backgroundColor: '#007bff',
                    borderColor: '#0056b3',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Daily Trend Chart
        const dailyData = <?php echo json_encode($dailyTrend); ?>;
        const dailyTrendChart = new Chart(document.getElementById('dailyTrendChart'), {
            type: 'line',
            data: {
                labels: dailyData.map(item => {
                    const date = new Date(item.queue_date);
                    return date.toLocaleDateString('th-TH', {day: '2-digit', month: '2-digit'});
                }),
                datasets: [{
                    label: 'คิวทั้งหมด',
                    data: dailyData.map(item => item.queue_count),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4
                }, {
                    label: 'เสร็จสิ้น',
                    data: dailyData.map(item => item.completed_count),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
