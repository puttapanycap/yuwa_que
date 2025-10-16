<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
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
            case 'add_queue_type':
                $typeName = sanitizeInput($_POST['type_name']);
                $description = sanitizeInput($_POST['description']);
                $prefixChar = sanitizeInput($_POST['prefix_char']);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($typeName) || empty($prefixChar)) {
                    throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
                }
                
                if (strlen($prefixChar) != 1 || !preg_match('/[A-Z]/', $prefixChar)) {
                    throw new Exception('รหัสนำหน้าต้องเป็นตัวอักษรภาษาอังกฤษพิมพ์ใหญ่ 1 ตัวเท่านั้น');
                }
                
                // Check if prefix exists
                $stmt = $db->prepare("SELECT queue_type_id FROM queue_types WHERE prefix_char = ?");
                $stmt->execute([$prefixChar]);
                if ($stmt->fetch()) {
                    throw new Exception('รหัสนำหน้านี้มีอยู่แล้ว');
                }
                
                $stmt = $db->prepare("
                    INSERT INTO queue_types (type_name, description, prefix_char, is_active) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$typeName, $description, $prefixChar, $isActive]);
                
                logActivity("เพิ่มประเภทคิวใหม่: {$typeName}");
                
                $message = 'เพิ่มประเภทคิวสำเร็จ';
                $messageType = 'success';
                break;
                
            case 'edit_queue_type':
                $queueTypeId = $_POST['queue_type_id'];
                $typeName = sanitizeInput($_POST['type_name']);
                $description = sanitizeInput($_POST['description']);
                $prefixChar = sanitizeInput($_POST['prefix_char']);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($typeName) || empty($prefixChar)) {
                    throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
                }
                
                if (strlen($prefixChar) != 1 || !preg_match('/[A-Z]/', $prefixChar)) {
                    throw new Exception('รหัสนำหน้าต้องเป็นตัวอักษรภาษาอังกฤษพิมพ์ใหญ่ 1 ตัวเท่านั้น');
                }
                
                // Check if prefix exists for other queue types
                $stmt = $db->prepare("SELECT queue_type_id FROM queue_types WHERE prefix_char = ? AND queue_type_id != ?");
                $stmt->execute([$prefixChar, $queueTypeId]);
                if ($stmt->fetch()) {
                    throw new Exception('รหัสนำหน้านี้มีอยู่แล้ว');
                }
                
                $stmt = $db->prepare("
                    UPDATE queue_types 
                    SET type_name = ?, description = ?, prefix_char = ?, is_active = ? 
                    WHERE queue_type_id = ?
                ");
                $stmt->execute([$typeName, $description, $prefixChar, $isActive, $queueTypeId]);
                
                logActivity("แก้ไ��ประเภทคิว: {$typeName}");
                
                $message = 'แก้ไขประเภทคิวสำเร็จ';
                $messageType = 'success';
                break;
                
            case 'delete_queue_type':
                $queueTypeId = $_POST['queue_type_id'];
                
                // Check if queue type is in use
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM queues WHERE queue_type_id = ?");
                $stmt->execute([$queueTypeId]);
                $count = $stmt->fetch()['count'];
                
                if ($count > 0) {
                    throw new Exception('ไม่สามารถลบประเภทคิวนี้ได้เนื่องจากมีคิวที่ใช้ประเภทนี้อยู่');
                }
                
                $stmt = $db->prepare("DELETE FROM queue_types WHERE queue_type_id = ?");
                $stmt->execute([$queueTypeId]);
                
                logActivity("ลบประเภทคิว ID: {$queueTypeId}");
                
                $message = 'ลบประเภทคิวสำเร็จ';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get queue types list
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT qt.*, 
               COUNT(DISTINCT q.queue_id) as queue_count,
               COUNT(DISTINCT CASE WHEN q.current_status IN ('waiting', 'called', 'processing') THEN q.queue_id END) as active_queue_count
        FROM queue_types qt
        LEFT JOIN queues q ON qt.queue_type_id = q.queue_type_id
        GROUP BY qt.queue_type_id
        ORDER BY qt.queue_type_id
    ");
    $stmt->execute();
    $queueTypes = $stmt->fetchAll();
    
} catch (Exception $e) {
    $queueTypes = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการประเภทคิว - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    
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
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
        }
        
        .queue-type-card.inactive {
            opacity: 0.6;
            border-left-color: #6c757d;
        }
        
        .prefix-badge {
            font-size: 1.2rem;
            font-weight: bold;
            padding: 0.5rem 1rem;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
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
                            <h2>จัดการประเภทคิว</h2>
                            <p class="text-muted">กำหนดประเภทคิวและรหัสนำหน้า</p>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addQueueTypeModal">
                            <i class="fas fa-plus me-2"></i>เพิ่มประเภทคิวใหม่
                        </button>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Queue Types List -->
                    <div class="content-card">
                        <h5 class="mb-4">รายการประเภทคิว (<?php echo count($queueTypes); ?> ประเภท)</h5>
                        
                        <?php if (empty($queueTypes)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-list fa-3x mb-3"></i>
                                <p>ไม่มีประเภทคิวในระบบ</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($queueTypes as $qt): ?>
                                <div class="queue-type-card <?php echo $qt['is_active'] ? '' : 'inactive'; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-1">
                                            <div class="prefix-badge">
                                                <?php echo htmlspecialchars($qt['prefix_char']); ?>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($qt['type_name']); ?>
                                                <?php if (!$qt['is_active']): ?>
                                                    <span class="badge bg-secondary ms-2">ปิดใช้งาน</span>
                                                <?php endif; ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($qt['description'] ?: 'ไม่มีคำอธิบาย'); ?>
                                            </small>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="d-flex">
                                                <div class="me-4">
                                                    <small class="d-block text-muted">คิวทั้งหมด</small>
                                                    <span class="badge bg-primary"><?php echo number_format($qt['queue_count']); ?></span>
                                                </div>
                                                <div>
                                                    <small class="d-block text-muted">คิวที่ใช้งานอยู่</small>
                                                    <span class="badge bg-warning"><?php echo number_format($qt['active_queue_count']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 text-end">
                                            <button class="btn btn-sm btn-outline-primary me-1" 
                                                    onclick="editQueueType(<?php echo htmlspecialchars(json_encode($qt)); ?>)">
                                                <i class="fas fa-edit"></i> แก้ไข
                                            </button>
                                            
                                            <?php if ($qt['queue_count'] == 0): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_queue_type">
                                                    <input type="hidden" name="queue_type_id" value="<?php echo $qt['queue_type_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('คุณต้องการลบประเภทคิวนี้หรือไม่?')">
                                                        <i class="fas fa-trash"></i> ลบ
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Queue Number Preview -->
                    <div class="content-card">
                        <h5 class="mb-4">ตัวอย่างหมายเลขคิว</h5>
                        
                        <div class="row">
                            <?php foreach ($queueTypes as $qt): ?>
                                <?php if ($qt['is_active']): ?>
                                    <div class="col-md-3 mb-3">
                                        <div class="text-center p-3 border rounded">
                                            <div class="h4 text-primary mb-2"><?php echo $qt['prefix_char']; ?>001</div>
                                            <small class="text-muted"><?php echo htmlspecialchars($qt['type_name']); ?></small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Queue Type Modal -->
    <div class="modal fade" id="addQueueTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add_queue_type">
                    <div class="modal-header">
                        <h5 class="modal-title">เพิ่มประเภทคิวใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ชื่อประเภทคิว *</label>
                            <input type="text" class="form-control" name="type_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">คำอธิบาย</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">รหัสนำหน้า *</label>
                            <input type="text" class="form-control" name="prefix_char" required
                                   maxlength="1" pattern="[A-Z]" title="ใช้ตัวอักษรภาษาอังกฤษพิมพ์ใหญ�� 1 ตัวเท่านั้น"
                                   style="text-align: center; font-size: 1.5rem; font-weight: bold;">
                            <div class="form-text">ใช้ตัวอักษรภาษาอังกฤษพิมพ์ใหญ่ 1 ตัวเท่านั้น เช่น A, B, C</div>
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
                        <button type="submit" class="btn btn-primary">เพิ่มประเภทคิว</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Queue Type Modal -->
    <div class="modal fade" id="editQueueTypeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit_queue_type">
                    <input type="hidden" name="queue_type_id" id="edit_queue_type_id">
                    <div class="modal-header">
                        <h5 class="modal-title">แก้ไขประเภทคิว</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ชื่อประเภทคิว *</label>
                            <input type="text" class="form-control" name="type_name" id="edit_type_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">คำอธิบาย</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">รหัสนำหน้า *</label>
                            <input type="text" class="form-control" name="prefix_char" id="edit_prefix_char" required
                                   maxlength="1" pattern="[A-Z]" title="ใช้ตัวอักษรภาษาอังกฤษพิมพ์ใหญ่ 1 ตัวเท่านั้น"
                                   style="text-align: center; font-size: 1.5rem; font-weight: bold;">
                            <div class="form-text">ใช้ตัวอักษรภาษาอังกฤษพิมพ์ใหญ่ 1 ตัวเท่านั้น เช่น A, B, C</div>
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
        function editQueueType(queueType) {
            $('#edit_queue_type_id').val(queueType.queue_type_id);
            $('#edit_type_name').val(queueType.type_name);
            $('#edit_description').val(queueType.description);
            $('#edit_prefix_char').val(queueType.prefix_char);
            $('#edit_is_active').prop('checked', queueType.is_active == 1);
            
            $('#editQueueTypeModal').modal('show');
        }
        
        // Auto uppercase prefix input
        $('input[name="prefix_char"]').on('input', function() {
            this.value = this.value.toUpperCase();
        });
    </script>
</body>
</html>
