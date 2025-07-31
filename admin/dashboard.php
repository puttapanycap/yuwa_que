<?php
require_once '../config/config.php';
requireLogin();

if (!hasPermission('manage_users')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

// Get statistics
try {
    $db = getDB();
    
    // Total queues today
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM queues WHERE DATE(creation_time) = CURDATE()");
    $stmt->execute();
    $todayQueues = $stmt->fetch()['count'];
    
    // Active queues
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM queues WHERE current_status IN ('waiting', 'called', 'processing')");
    $stmt->execute();
    $activeQueues = $stmt->fetch()['count'];
    
    // Total staff
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM staff_users WHERE is_active = 1");
    $stmt->execute();
    $totalStaff = $stmt->fetch()['count'];
    
    // Service points
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM service_points WHERE is_active = 1");
    $stmt->execute();
    $totalServicePoints = $stmt->fetch()['count'];
    
} catch (Exception $e) {
    $todayQueues = $activeQueues = $totalStaff = $totalServicePoints = 0;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการระบบ - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    
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
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .content-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .quick-action {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            text-decoration: none;
            color: #495057;
            transition: all 0.3s;
            display: block;
            margin-bottom: 1rem;
        }
        
        .quick-action:hover {
            border-color: #007bff;
            color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.2);
        }
        
        .quick-action i {
            font-size: 2rem;
            margin-bottom: 1rem;
            display: block;
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
                        <a class="nav-link active" href="dashboard.php">
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
                        <a class="nav-link" href="reports.php">
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
                            <h2>แดชบอร์ดจัดการระบบ</h2>
                            <p class="text-muted">ภาพรวมระบบเรียกคิวโรงพยาบาลยุวประสาทไวทโยปถัมภ์</p>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">
                                เข้าสู่ระบบโดย: <?php echo htmlspecialchars($_SESSION['full_name']); ?><br>
                                <?php echo date('d/m/Y H:i:s'); ?>
                            </small>
                        </div>
                    </div>
                    
                    <!-- Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number text-primary"><?php echo $todayQueues; ?></div>
                                <div class="text-muted">คิววันนี้</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number text-warning"><?php echo $activeQueues; ?></div>
                                <div class="text-muted">คิวที่ใช้งานอยู่</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number text-success"><?php echo $totalStaff; ?></div>
                                <div class="text-muted">เจ้าหน้าที่</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stats-card">
                                <div class="stats-number text-info"><?php echo $totalServicePoints; ?></div>
                                <div class="text-muted">จุดบริการ</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="content-card">
                        <h5 class="mb-4">การจัดการด่วน</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <a href="users.php" class="quick-action">
                                    <i class="fas fa-user-plus text-primary"></i>
                                    <h6>เพิ่มผู้ใช้ใหม่</h6>
                                    <small class="text-muted">สร้างบัญชีเจ้าหน้าที่ใหม่</small>
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="service_points.php" class="quick-action">
                                    <i class="fas fa-plus-circle text-success"></i>
                                    <h6>เพิ่มจุดบริการ</h6>
                                    <small class="text-muted">สร้างจุดบริการใหม่</small>
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="settings.php" class="quick-action">
                                    <i class="fas fa-cogs text-warning"></i>
                                    <h6>ตั้งค่าระบบ</h6>
                                    <small class="text-muted">กำหนดค่าการทำงาน</small>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="content-card">
                        <h5 class="mb-4">กิจกรรมล่าสุด</h5>
                        <div id="recentActivity">
                            <div class="text-center">
                                <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Status -->
                    <div class="content-card">
                        <h5 class="mb-4">สถานะระบบ</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>จุดบริการที่เปิดใช้งาน</h6>
                                <div id="activeServicePoints">
                                    <div class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>สถิติการใช้งานวันนี้</h6>
                                <div id="todayStats">
                                    <div class="text-center">
                                        <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                                    </div>
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
        $(document).ready(function() {
            loadRecentActivity();
            loadActiveServicePoints();
            loadTodayStats();
            
            // Auto refresh every 30 seconds
            setInterval(function() {
                loadRecentActivity();
                loadActiveServicePoints();
                loadTodayStats();
            }, 30000);
        });
        
        function loadRecentActivity() {
            $.get('../api/get_recent_activity.php', function(data) {
                let html = '';
                if (data.length === 0) {
                    html = '<div class="text-muted text-center">ไม่มีกิจกรรมล่าสุด</div>';
                } else {
                    data.forEach(function(activity) {
                        html += `
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <small class="text-muted">${activity.timestamp}</small><br>
                                    <span>${activity.action_description}</span>
                                </div>
                                <small class="text-muted">${activity.full_name || 'ระบบ'}</small>
                            </div>
                        `;
                    });
                }
                $('#recentActivity').html(html);
            }).fail(function() {
                $('#recentActivity').html('<div class="text-danger text-center">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>');
            });
        }
        
        function loadActiveServicePoints() {
            $.get('../api/get_service_points_status.php', function(data) {
                let html = '';
                data.forEach(function(point) {
                    const statusClass = point.has_active_queue ? 'text-success' : 'text-muted';
                    const statusIcon = point.has_active_queue ? 'fa-circle' : 'fa-circle-o';
                    html += `
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span>${point.point_name}</span>
                            <span class="${statusClass}">
                                <i class="fas ${statusIcon} me-1"></i>
                                ${point.has_active_queue ? 'มีคิว' : 'ว่าง'}
                            </span>
                        </div>
                    `;
                });
                $('#activeServicePoints').html(html);
            }).fail(function() {
                $('#activeServicePoints').html('<div class="text-danger">เกิดข้อผิดพลาด</div>');
            });
        }
        
        function loadTodayStats() {
            $.get('../api/get_today_stats.php', function(data) {
                const html = `
                    <div class="row text-center">
                        <div class="col-4">
                            <h6 class="text-primary">${data.total_queues}</h6>
                            <small class="text-muted">รวมทั้งหมด</small>
                        </div>
                        <div class="col-4">
                            <h6 class="text-success">${data.completed_queues}</h6>
                            <small class="text-muted">เสร็จสิ้น</small>
                        </div>
                        <div class="col-4">
                            <h6 class="text-danger">${data.cancelled_queues}</h6>
                            <small class="text-muted">ยกเลิก</small>
                        </div>
                    </div>
                `;
                $('#todayStats').html(html);
            }).fail(function() {
                $('#todayStats').html('<div class="text-danger">เกิดข้อผิดพลาด</div>');
            });
        }
    </script>
</body>
</html>
