<?php
require_once '../config/config.php';
requireLogin();

if (!hasPermission('manage_service_points')) {
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
            case 'add_service_point':
                $pointName = sanitizeInput($_POST['point_name']);
                $pointDescription = sanitizeInput($_POST['point_description']);
                $positionKey = sanitizeInput($_POST['position_key']);
                $displayOrder = (int)$_POST['display_order'];
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($pointName) || empty($positionKey)) {
                    throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
                }
                
                // Check if position key exists
                $stmt = $db->prepare("SELECT service_point_id FROM service_points WHERE position_key = ?");
                $stmt->execute([$positionKey]);
                if ($stmt->fetch()) {
                    throw new Exception('รหัสตำแหน่งนี้มีอยู่แล้ว');
                }
                
                $stmt = $db->prepare("
                    INSERT INTO service_points (point_name, point_description, position_key, display_order, is_active) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$pointName, $pointDescription, $positionKey, $displayOrder, $isActive]);
                
                logActivity("เพิ่มจุดบริการใหม่: {$pointName}");
                
                $message = 'เพิ่มจุดบริการสำเร็จ';
                $messageType = 'success';
                break;
                
            case 'edit_service_point':
                $servicePointId = $_POST['service_point_id'];
                $pointName = sanitizeInput($_POST['point_name']);
                $pointDescription = sanitizeInput($_POST['point_description']);
                $positionKey = sanitizeInput($_POST['position_key']);
                $displayOrder = (int)$_POST['display_order'];
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($pointName) || empty($positionKey)) {
                    throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
                }
                
                // Check if position key exists for other service points
                $stmt = $db->prepare("SELECT service_point_id FROM service_points WHERE position_key = ? AND service_point_id != ?");
                $stmt->execute([$positionKey, $servicePointId]);
                if ($stmt->fetch()) {
                    throw new Exception('รหัสตำแหน่งนี้มีอยู่แล้ว');
                }
                
                $stmt = $db->prepare("
                    UPDATE service_points 
                    SET point_name = ?, point_description = ?, position_key = ?, display_order = ?, is_active = ? 
                    WHERE service_point_id = ?
                ");
                $stmt->execute([$pointName, $pointDescription, $positionKey, $displayOrder, $isActive, $servicePointId]);
                
                logActivity("แก้ไขจุดบริการ: {$pointName}");
                
                $message = 'แก้ไขจุดบริการสำเร็จ';
                $messageType = 'success';
                break;
                
            case 'delete_service_point':
                $servicePointId = $_POST['service_point_id'];
                
                // Check if service point is in use
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM queues WHERE current_service_point_id = ?");
                $stmt->execute([$servicePointId]);
                $count = $stmt->fetch()['count'];
                
                if ($count > 0) {
                    throw new Exception('ไม่สามารถลบจุดบริการนี้ได้เนื่องจากมีคิวที่ใช้จุดบริการนี้อยู่');
                }
                
                $stmt = $db->prepare("DELETE FROM service_points WHERE service_point_id = ?");
                $stmt->execute([$servicePointId]);
                
                logActivity("ลบจุดบริการ ID: {$servicePointId}");
                
                $message = 'ลบจุดบริการสำเร็จ';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get service points list
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT sp.*, 
               COUNT(DISTINCT q.queue_id) as active_queue_count
        FROM service_points sp
        LEFT JOIN queues q ON sp.service_point_id = q.current_service_point_id AND q.current_status IN ('waiting', 'called', 'processing')
        GROUP BY sp.service_point_id
        ORDER BY sp.display_order, sp.point_name
    ");
    $stmt->execute();
    $servicePoints = $stmt->fetchAll();
    
} catch (Exception $e) {
    $servicePoints = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการจุดบริการ - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    
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
        
        .service-point-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #28a745;
        }
        
        .service-point-card.inactive {
            opacity: 0.6;
            border-left-color: #6c757d;
        }
        
        .badge-status {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
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
                            <h2>จัดการจุดบริการ</h2>
                            <p class="text-muted">กำหนดจุดบริการสำหรับระบบเรียกคิว</p>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServicePointModal">
                            <i class="fas fa-plus me-2"></i>เพิ่มจุดบริการใหม่
                        </button>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Service Points List -->
                    <div class="content-card">
                        <h5 class="mb-4">รายการจุดบริการ (<?php echo count($servicePoints); ?> จุด)</h5>
                        
                        <?php if (empty($servicePoints)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-map-marker-alt fa-3x mb-3"></i>
                                <p>ไม่มีจุดบริการในระบบ</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($servicePoints as $sp): ?>
                                <div class="service-point-card <?php echo $sp['is_active'] ? '' : 'inactive'; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-4">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($sp['point_name']); ?>
                                                <?php if (!$sp['is_active']): ?>
                                                    <span class="badge bg-secondary ms-2">ปิดใช้งาน</span>
                                                <?php endif; ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($sp['point_description'] ?: 'ไม่มีคำอธิบาย'); ?>
                                            </small>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="d-block text-muted">รหัสตำแหน่ง</small>
                                            <code><?php echo htmlspecialchars($sp['position_key']); ?></code>
                                        </div>
                                        <div class="col-md-2">
                                            <small class="d-block text-muted">ลำดับการแสดงผล</small>
                                            <span><?php echo $sp['display_order']; ?></span>
                                        </div>
                                        <div class="col-md-3 text-end">
                                            <?php if ($sp['active_queue_count'] > 0): ?>
                                                <span class="badge bg-warning mb-2">มีคิวที่ใช้งานอยู่ <?php echo $sp['active_queue_count']; ?> คิว</span>
                                            <?php endif; ?>
                                            
                                            <div>
                                                <button class="btn btn-sm btn-outline-primary me-1" 
                                                        onclick="editServicePoint(<?php echo htmlspecialchars(json_encode($sp)); ?>)">
                                                    <i class="fas fa-edit"></i> แก้ไข
                                                </button>
                                                
                                                <?php if ($sp['active_queue_count'] == 0): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete_service_point">
                                                        <input type="hidden" name="service_point_id" value="<?php echo $sp['service_point_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                                onclick="return confirm('คุณต้องการลบจุดบริการนี้หรือไม่?')">
                                                            <i class="fas fa-trash"></i> ลบ
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Monitor Links -->
                    <div class="content-card">
                        <h5 class="mb-4">ลิงก์หน้าจอแสดงผล</h5>
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>จุดบริการ</th>
                                        <th>URL</th>
                                        <th>การดำเนินการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>ทุกจุดบริการ</td>
                                        <td><code><?php echo BASE_URL; ?>/monitor/display.php</code></td>
                                        <td>
                                            <a href="../monitor/display.php" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-external-link-alt me-1"></i>เปิด
                                            </a>
                                        </td>
                                    </tr>
                                    <?php foreach ($servicePoints as $sp): ?>
                                        <?php if ($sp['is_active']): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($sp['point_name']); ?></td>
                                                <td><code><?php echo BASE_URL; ?>/monitor/display.php?service_point=<?php echo $sp['service_point_id']; ?></code></td>
                                                <td>
                                                    <a href="../monitor/display.php?service_point=<?php echo $sp['service_point_id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-external-link-alt me-1"></i>เปิด
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Service Point Modal -->
    <div class="modal fade" id="addServicePointModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add_service_point">
                    <div class="modal-header">
                        <h5 class="modal-title">เพิ่มจุดบริการใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ชื่อจุดบริการ *</label>
                            <input type="text" class="form-control" name="point_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">คำอธิบาย</label>
                            <textarea class="form-control" name="point_description" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">รหัสตำแหน่ง *</label>
                            <input type="text" class="form-control" name="position_key" required
                                   pattern="[A-Z0-9_]+" title="ใช้ตัวอักษรภาษาอังกฤษพิมพ์ใหญ่ ตัวเลข และเครื่องหมาย _ เท่านั้น">
                            <div class="form-text">ใช้ตัวอักษรภาษาอังกฤษพิมพ์ใหญ่ ตัวเลข และเครื่องหมาย _ เท่านั้น เช่น PHARMACY_01</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">ลำดับการแสดงผล</label>
                            <input type="number" class="form-control" name="display_order" value="0" min="0">
                            <div class="form-text">ลำดับน้อยจะแสดงก่อน</div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="add_is_active" checked>
                            <label class="form-check-label" for="add_is_active">
                                เปิดใช้งาน
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">เพิ่มจุดบริการ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Service Point Modal -->
    <div class="modal fade" id="editServicePointModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit_service_point">
                    <input type="hidden" name="service_point_id" id="edit_service_point_id">
                    <div class="modal-header">
                        <h5 class="modal-title">แก้ไขจุดบริการ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ชื่อจุดบริการ *</label>
                            <input type="text" class="form-control" name="point_name" id="edit_point_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">คำอธิบาย</label>
                            <textarea class="form-control" name="point_description" id="edit_point_description" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">รหัสตำแหน่ง *</label>
                            <input type="text" class="form-control" name="position_key" id="edit_position_key" required
                                   pattern="[A-Z0-9_]+" title="ใช้ตัวอักษรภาษาอังกฤษพิมพ์ใหญ่ ตัวเลข และเครื่องหมาย _ เท่านั้น">
                            <div class="form-text">ใช้ตัวอักษรภาษาอังกฤษพิมพ์ใหญ่ ตัวเลข และเครื่องหมาย _ เท่านั้น เช่น PHARMACY_01</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">ลำดับการแสดงผล</label>
                            <input type="number" class="form-control" name="display_order" id="edit_display_order" min="0">
                            <div class="form-text">ลำดับน้อยจะแสดงก่อน</div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                            <label class="form-check-label" for="edit_is_active">
                                เปิดใช้งาน
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        function editServicePoint(servicePoint) {
            $('#edit_service_point_id').val(servicePoint.service_point_id);
            $('#edit_point_name').val(servicePoint.point_name);
            $('#edit_point_description').val(servicePoint.point_description);
            $('#edit_position_key').val(servicePoint.position_key);
            $('#edit_display_order').val(servicePoint.display_order);
            $('#edit_is_active').prop('checked', servicePoint.is_active == 1);
            
            $('#editServicePointModal').modal('show');
        }
    </script>
</body>
</html>
