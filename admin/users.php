<?php
require_once '../config/config.php';
requireLogin();

if (!hasPermission('manage_users')) {
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
            case 'add_user':
                $username = sanitizeInput($_POST['username']);
                $password = $_POST['password'];
                $fullName = sanitizeInput($_POST['full_name']);
                $roleId = $_POST['role_id'];
                $servicePoints = $_POST['service_points'] ?? [];
                
                if (empty($username) || empty($password) || empty($fullName) || empty($roleId)) {
                    throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
                }
                
                $db->beginTransaction();
                
                // Check if username exists
                $stmt = $db->prepare("SELECT staff_id FROM staff_users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    throw new Exception('ชื่อผู้ใช้นี้มีอยู่แล้ว');
                }
                
                // Insert user
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO staff_users (username, password_hash, full_name, role_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $passwordHash, $fullName, $roleId]);
                $staffId = $db->lastInsertId();
                
                // Insert service point access
                if (!empty($servicePoints)) {
                    $stmt = $db->prepare("INSERT INTO staff_service_point_access (staff_id, service_point_id) VALUES (?, ?)");
                    foreach ($servicePoints as $servicePointId) {
                        $stmt->execute([$staffId, $servicePointId]);
                    }
                }
                
                $db->commit();
                logActivity("เพิ่มผู้ใช้ใหม่: {$username}");
                
                $message = 'เพิ่มผู้ใช้สำเร็จ';
                $messageType = 'success';
                break;
                
            case 'edit_user':
                $staffId = $_POST['staff_id'];
                $username = sanitizeInput($_POST['username']);
                $fullName = sanitizeInput($_POST['full_name']);
                $roleId = $_POST['role_id'];
                $servicePoints = $_POST['service_points'] ?? [];
                $newPassword = $_POST['new_password'] ?? '';
                
                $db->beginTransaction();
                
                // Update user
                if (!empty($newPassword)) {
                    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE staff_users SET username = ?, password_hash = ?, full_name = ?, role_id = ? WHERE staff_id = ?");
                    $stmt->execute([$username, $passwordHash, $fullName, $roleId, $staffId]);
                } else {
                    $stmt = $db->prepare("UPDATE staff_users SET username = ?, full_name = ?, role_id = ? WHERE staff_id = ?");
                    $stmt->execute([$username, $fullName, $roleId, $staffId]);
                }
                
                // Update service point access
                $stmt = $db->prepare("DELETE FROM staff_service_point_access WHERE staff_id = ?");
                $stmt->execute([$staffId]);
                
                if (!empty($servicePoints)) {
                    $stmt = $db->prepare("INSERT INTO staff_service_point_access (staff_id, service_point_id) VALUES (?, ?)");
                    foreach ($servicePoints as $servicePointId) {
                        $stmt->execute([$staffId, $servicePointId]);
                    }
                }
                
                $db->commit();
                logActivity("แก้ไขผู้ใช้: {$username}");
                
                $message = 'แก้ไขผู้ใช้สำเร็จ';
                $messageType = 'success';
                break;
                
            case 'toggle_user':
                $staffId = $_POST['staff_id'];
                $isActive = $_POST['is_active'] == '1' ? 0 : 1;
                
                $stmt = $db->prepare("UPDATE staff_users SET is_active = ? WHERE staff_id = ?");
                $stmt->execute([$isActive, $staffId]);
                
                $action_text = $isActive ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
                logActivity("{$action_text}ผู้ใช้ ID: {$staffId}");
                
                $message = $action_text . 'ผู้ใช้สำเร็จ';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get users list
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT s.*, r.role_name,
               GROUP_CONCAT(sp.point_name SEPARATOR ', ') as service_points
        FROM staff_users s
        LEFT JOIN roles r ON s.role_id = r.role_id
        LEFT JOIN staff_service_point_access sspa ON s.staff_id = sspa.staff_id
        LEFT JOIN service_points sp ON sspa.service_point_id = sp.service_point_id
        GROUP BY s.staff_id
        ORDER BY s.full_name
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    // Get roles
    $stmt = $db->prepare("SELECT * FROM roles ORDER BY role_name");
    $stmt->execute();
    $roles = $stmt->fetchAll();
    
    // Get service points
    $stmt = $db->prepare("SELECT * FROM service_points WHERE is_active = 1 ORDER BY display_order, point_name");
    $stmt->execute();
    $servicePoints = $stmt->fetchAll();
    
} catch (Exception $e) {
    $users = [];
    $roles = [];
    $servicePoints = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    
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
        
        .user-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
        }
        
        .user-card.inactive {
            opacity: 0.6;
            border-left-color: #6c757d;
        }
        
        .badge-role {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }
        
        .btn-action {
            margin: 0.2rem;
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
                            <h2>จัดการผู้ใช้</h2>
                            <p class="text-muted">จัดการบัญชีผู้ใช้และสิทธิ์การเข้าถึง</p>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus me-2"></i>เพิ่มผู้ใช้ใหม่
                        </button>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Users List -->
                    <div class="content-card">
                        <h5 class="mb-4">รายการผู้ใช้ (<?php echo count($users); ?> คน)</h5>
                        
                        <?php if (empty($users)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-users fa-3x mb-3"></i>
                                <p>ไม่มีผู้ใช้ในระบบ</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <div class="user-card <?php echo $user['is_active'] ? '' : 'inactive'; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-4">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($user['full_name']); ?>
                                                <?php if (!$user['is_active']): ?>
                                                    <span class="badge bg-secondary ms-2">ปิดใช้งาน</span>
                                                <?php endif; ?>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </small>
                                        </div>
                                        <div class="col-md-3">
                                            <span class="badge badge-role bg-primary">
                                                <?php echo htmlspecialchars($user['role_name'] ?? 'ไม่ระบุ'); ?>
                                            </span>
                                        </div>
                                        <div class="col-md-3">
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo $user['service_points'] ? htmlspecialchars($user['service_points']) : 'ไม่มีจุดบริการ'; ?>
                                            </small>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <button class="btn btn-sm btn-outline-primary btn-action" 
                                                    onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_user">
                                                <input type="hidden" name="staff_id" value="<?php echo $user['staff_id']; ?>">
                                                <input type="hidden" name="is_active" value="<?php echo $user['is_active']; ?>">
                                                <button type="submit" class="btn btn-sm btn-action <?php echo $user['is_active'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>"
                                                        onclick="return confirm('คุณต้องการ<?php echo $user['is_active'] ? 'ปิด' : 'เปิด'; ?>ใช้งานผู้ใช้นี้หรือไม่?')">
                                                    <i class="fas fa-<?php echo $user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <?php if ($user['last_login']): ?>
                                        <div class="row mt-2">
                                            <div class="col-12">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    เข้าสู่ระบบล่าสุด: <?php echo date('d/m/Y H:i', strtotime($user['last_login'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add_user">
                    <div class="modal-header">
                        <h5 class="modal-title">เพิ่มผู้ใช้ใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ชื่อผู้ใช้ *</label>
                                    <input type="text" class="form-control" name="username" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">รหัสผ่าน *</label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">ชื่อ-นามสกุล *</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">บทบาท *</label>
                            <select class="form-select" name="role_id" required>
                                <option value="">เลือกบทบาท</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>">
                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">จุดบริการที่สามารถเข้าถึง</label>
                            <div class="row">
                                <?php foreach ($servicePoints as $sp): ?>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="service_points[]" value="<?php echo $sp['service_point_id']; ?>"
                                                   id="sp_add_<?php echo $sp['service_point_id']; ?>">
                                            <label class="form-check-label" for="sp_add_<?php echo $sp['service_point_id']; ?>">
                                                <?php echo htmlspecialchars($sp['point_name']); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">เพิ่มผู้ใช้</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="staff_id" id="edit_staff_id">
                    <div class="modal-header">
                        <h5 class="modal-title">แก้ไขผู้ใช้</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ชื่อผู้ใช้ *</label>
                                    <input type="text" class="form-control" name="username" id="edit_username" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">รหัสผ่านใหม่ (เว้นว่างหากไม่ต้องการเปลี่ยน)</label>
                                    <input type="password" class="form-control" name="new_password">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">ชื่อ-นามสกุล *</label>
                            <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">บทบาท *</label>
                            <select class="form-select" name="role_id" id="edit_role_id" required>
                                <option value="">เลือกบทบาท</option>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>">
                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">จุดบริการที่สามารถเข้าถึง</label>
                            <div class="row" id="edit_service_points">
                                <?php foreach ($servicePoints as $sp): ?>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" 
                                                   name="service_points[]" value="<?php echo $sp['service_point_id']; ?>"
                                                   id="sp_edit_<?php echo $sp['service_point_id']; ?>">
                                            <label class="form-check-label" for="sp_edit_<?php echo $sp['service_point_id']; ?>">
                                                <?php echo htmlspecialchars($sp['point_name']); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
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
        function editUser(user) {
            $('#edit_staff_id').val(user.staff_id);
            $('#edit_username').val(user.username);
            $('#edit_full_name').val(user.full_name);
            $('#edit_role_id').val(user.role_id);
            
            // Clear all checkboxes first
            $('#edit_service_points input[type="checkbox"]').prop('checked', false);
            
            // Get user's service points
            $.get('../api/get_user_service_points.php', {staff_id: user.staff_id}, function(data) {
                data.forEach(function(sp) {
                    $('#sp_edit_' + sp.service_point_id).prop('checked', true);
                });
            });
            
            $('#editUserModal').modal('show');
        }
    </script>
</body>
</html>
