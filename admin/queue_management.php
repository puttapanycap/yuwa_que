<?php
require_once '../config/config.php';
requireLogin();

if (!hasPermission('manage_queues')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

try {
    $db = getDB();
    
    // ดึงข้อมูลประเภทคิว
    $stmt = $db->prepare("
        SELECT qt.*, 
               COUNT(q.queue_id) as active_queues,
               MAX(q.queue_number) as last_queue_number
        FROM queue_types qt
        LEFT JOIN queues q ON qt.queue_type_id = q.queue_type_id 
                           AND DATE(q.creation_time) = CURDATE()
                           AND q.current_status IN ('waiting', 'called', 'processing')
        WHERE qt.is_active = 1
        GROUP BY qt.queue_type_id
        ORDER BY qt.type_name
    ");
    $stmt->execute();
    $queueTypes = $stmt->fetchAll();
    
    // ดึงข้อมูลจุดบริการ
    $stmt = $db->prepare("
        SELECT sp.*,
               COUNT(DISTINCT qt.queue_type_id) as queue_types_count,
               COUNT(q.queue_id) as active_queues
        FROM service_points sp
        LEFT JOIN queue_type_service_points qtsp ON sp.service_point_id = qtsp.service_point_id
        LEFT JOIN queue_types qt ON qtsp.queue_type_id = qt.queue_type_id AND qt.is_active = 1
        LEFT JOIN queues q ON qt.queue_type_id = q.queue_type_id 
                           AND DATE(q.creation_time) = CURDATE()
                           AND q.current_status IN ('waiting', 'called', 'processing')
        WHERE sp.is_active = 1
        GROUP BY sp.service_point_id
        ORDER BY sp.display_order, sp.point_name
    ");
    $stmt->execute();
    $servicePoints = $stmt->fetchAll();
    
} catch (Exception $e) {
    $queueTypes = [];
    $servicePoints = [];
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคิว - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    
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
        
        .queue-type-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            border-left: 4px solid #007bff;
        }
        
        .queue-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
        }
        
        .reset-btn {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s;
        }
        
        .reset-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255,107,107,0.3);
            color: white;
        }
        
        .reset-all-btn {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .reset-all-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231,76,60,0.4);
            color: white;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .confirmation-modal .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .confirmation-modal .modal-header {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            border-radius: 15px 15px 0 0;
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
                        <a class="nav-link active" href="queue_management.php">
                            <i class="fas fa-tasks"></i>จัดการคิว
                        </a>
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog"></i>การตั้งค่า
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
                            <h2>จัดการคิว</h2>
                            <p class="text-muted">จัดการหมายเลขคิวและ Reset ระบบ</p>
                        </div>
                        <button class="btn reset-all-btn" onclick="showResetAllModal()">
                            <i class="fas fa-redo me-2"></i>Reset คิวทั้งหมด
                        </button>
                    </div>
                    
                    <!-- Statistics -->
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count($queueTypes); ?></div>
                            <div>ประเภทคิว</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo count($servicePoints); ?></div>
                            <div>จุดบริการ</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo array_sum(array_column($queueTypes, 'active_queues')); ?></div>
                            <div>คิวที่รอ</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo array_sum(array_column($queueTypes, 'current_number')); ?></div>
                            <div>หมายเลขล่าสุด</div>
                        </div>
                    </div>
                    
                    <!-- Queue Types Management -->
                    <div class="content-card">
                        <h5 class="mb-4">
                            <i class="fas fa-list me-2"></i>จัดการประเภทคิว
                        </h5>
                        
                        <div class="row">
                            <?php foreach ($queueTypes as $type): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="queue-type-card">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($type['type_name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($type['prefix']); ?></small>
                                            </div>
                                            <span class="status-badge <?php echo $type['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $type['is_active'] ? 'เปิดใช้งาน' : 'ปิดใช้งาน'; ?>
                                            </span>
                                        </div>
                                        
                                        <div class="row text-center mb-3">
                                            <div class="col-6">
                                                <div class="queue-number"><?php echo str_pad($type['current_number'], 3, '0', STR_PAD_LEFT); ?></div>
                                                <small class="text-muted">หมายเลขปัจจุบัน</small>
                                            </div>
                                            <div class="col-6">
                                                <div class="queue-number text-warning"><?php echo $type['active_queues']; ?></div>
                                                <small class="text-muted">คิวที่รอ</small>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center">
                                            <button class="btn reset-btn btn-sm" onclick="resetQueueType(<?php echo $type['queue_type_id']; ?>, '<?php echo htmlspecialchars($type['type_name']); ?>')">
                                                <i class="fas fa-redo me-1"></i>Reset
                                            </button>
                                        </div>
                                        
                                        <?php if ($type['last_reset_date']): ?>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    Reset ล่าสุด: <?php echo date('d/m/Y H:i', strtotime($type['last_reset_date'])); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Service Points Management -->
                    <div class="content-card">
                        <h5 class="mb-4">
                            <i class="fas fa-map-marker-alt me-2"></i>จัดการตามจุดบริการ
                        </h5>
                        
                        <div class="row">
                            <?php foreach ($servicePoints as $point): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="queue-type-card" style="border-left-color: #28a745;">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($point['point_name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($point['description'] ?? ''); ?></small>
                                            </div>
                                            <span class="status-badge <?php echo $point['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $point['is_active'] ? 'เปิดใช้งาน' : 'ปิดใช้งาน'; ?>
                                            </span>
                                        </div>
                                        
                                        <div class="row text-center mb-3">
                                            <div class="col-6">
                                                <div class="queue-number text-success"><?php echo $point['queue_types_count']; ?></div>
                                                <small class="text-muted">ประเภทคิว</small>
                                            </div>
                                            <div class="col-6">
                                                <div class="queue-number text-warning"><?php echo $point['active_queues']; ?></div>
                                                <small class="text-muted">คิวที่รอ</small>
                                            </div>
                                        </div>
                                        
                                        <div class="text-center">
                                            <button class="btn reset-btn btn-sm" onclick="resetByServicePoint(<?php echo $point['service_point_id']; ?>, '<?php echo htmlspecialchars($point['point_name']); ?>')">
                                                <i class="fas fa-redo me-1"></i>Reset คิวจุดนี้
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Confirmation Modal -->
    <div class="modal fade confirmation-modal" id="resetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>ยืนยันการ Reset
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-redo fa-3x text-danger mb-3"></i>
                        <h6 id="resetMessage">คุณต้องการ Reset หมายเลขคิวหรือไม่?</h6>
                        <p class="text-muted" id="resetDetail">การดำเนินการนี้จะทำให้หมายเลขคิวกลับไปเป็น 001</p>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>คำเตือน:</strong> การ Reset จะส่งผลต่อการออกคิวใหม่ กรุณาตรวจสอบให้แน่ใจ
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-danger" id="confirmResetBtn">
                        <i class="fas fa-redo me-2"></i>ยืนยัน Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        let currentResetAction = null;
        
        function showResetAllModal() {
            currentResetAction = {
                type: 'all',
                message: 'Reset หมายเลขคิวทุกประเภท',
                detail: 'การดำเนินการนี้จะทำให้หมายเลขคิวทุกประเภทกลับไปเป็น 001'
            };
            
            $('#resetMessage').text(currentResetAction.message);
            $('#resetDetail').text(currentResetAction.detail);
            $('#resetModal').modal('show');
        }
        
        function resetQueueType(queueTypeId, typeName) {
            currentResetAction = {
                type: 'by_type',
                queue_type_id: queueTypeId,
                message: `Reset หมายเลขคิว "${typeName}"`,
                detail: `การดำเนินการนี้จะทำให้หมายเลขคิว "${typeName}" กลับไปเป็น 001`
            };
            
            $('#resetMessage').text(currentResetAction.message);
            $('#resetDetail').text(currentResetAction.detail);
            $('#resetModal').modal('show');
        }
        
        function resetByServicePoint(servicePointId, pointName) {
            currentResetAction = {
                type: 'by_service_point',
                service_point_id: servicePointId,
                message: `Reset หมายเลขคิวของ "${pointName}"`,
                detail: `การดำเนินการนี้จะทำให้หมายเลขคิวทุกประเภทที่ใช้จุดบริการ "${pointName}" กลับไปเป็น 001`
            };
            
            $('#resetMessage').text(currentResetAction.message);
            $('#resetDetail').text(currentResetAction.detail);
            $('#resetModal').modal('show');
        }
        
        $('#confirmResetBtn').click(function() {
            if (!currentResetAction) return;
            
            const button = $(this);
            const originalText = button.html();
            
            button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>กำลัง Reset...');
            
            $.ajax({
                url: '../api/reset_queue_numbers.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(currentResetAction),
                success: function(response) {
                    if (response.success) {
                        $('#resetModal').modal('hide');
                        
                        // แสดงข้อความสำเร็จ
                        showAlert('success', response.message);
                        
                        // รีโหลดหน้าหลังจาก 2 วินาที
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        showAlert('danger', response.message);
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON || {};
                    showAlert('danger', response.message || 'เกิดข้อผิดพลาดในการ Reset');
                },
                complete: function() {
                    button.prop('disabled', false).html(originalText);
                }
            });
        });
        
        function showAlert(type, message) {
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // แทรกที่ด้านบนของ content
            $('.p-4').prepend(alertHtml);
            
            // ลบ alert หลังจาก 5 วินาที
            setTimeout(() => {
                $('.alert').fadeOut();
            }, 5000);
        }
        
        // Auto refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
