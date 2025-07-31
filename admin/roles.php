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
            case 'add_role':
                $roleName = sanitizeInput($_POST['role_name']);
                $description = sanitizeInput($_POST['description']);
                $permissions = $_POST['permissions'] ?? [];
                
                if (empty($roleName)) {
                    throw new Exception('กรุณากรอกชื่อบทบาท');
                }
                
                $db->beginTransaction();
                
                // Check if role name exists
                $stmt = $db->prepare("SELECT role_id FROM roles WHERE role_name = ?");
                $stmt->execute([$roleName]);
                if ($stmt->fetch()) {
                    throw new Exception('ชื่อบทบาทนี้มีอยู่แล้ว');
                }
                
                // Insert role
                $stmt = $db->prepare("INSERT INTO roles (role_name, description) VALUES (?, ?)");
                $stmt->execute([$roleName, $description]);
                $roleId = $db->lastInsertId();
                
                // Insert permissions
                if (!empty($permissions)) {
                    $stmt = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                    foreach ($permissions as $permissionId) {
                        $stmt->execute([$roleId, $permissionId]);
                    }
                }
                
                $db->commit();
                logActivity("เพิ่มบทบาทใหม่: {$roleName}");
                
                $message = 'เพิ่มบทบาทสำเร็จ';
                $messageType = 'success';
                break;
                
            case 'edit_role':
                $roleId = $_POST['role_id'];
                $roleName = sanitizeInput($_POST['role_name']);
                $description = sanitizeInput($_POST['description']);
                $permissions = $_POST['permissions'] ?? [];
                
                if (empty($roleName)) {
                    throw new Exception('กรุณากรอกชื่อบทบาท');
                }
                
                $db->beginTransaction();
                
                // Check if role name exists for other roles
                $stmt = $db->prepare("SELECT role_id FROM roles WHERE role_name = ? AND role_id != ?");
                $stmt->execute([$roleName, $roleId]);
                if ($stmt->fetch()) {
                    throw new Exception('ชื่อบทบาทนี้มีอยู่แล้ว');
                }
                
                // Update role
                $stmt = $db->prepare("UPDATE roles SET role_name = ?, description = ? WHERE role_id = ?");
                $stmt->execute([$roleName, $description, $roleId]);
                
                // Update permissions
                $stmt = $db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
                $stmt->execute([$roleId]);
                
                if (!empty($permissions)) {
                    $stmt = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                    foreach ($permissions as $permissionId) {
                        $stmt->execute([$roleId, $permissionId]);
                    }
                }
                
                $db->commit();
                logActivity("แก้ไขบทบาท: {$roleName}");
                
                $message = 'แก้ไขบทบาทสำเร็จ';
                $messageType = 'success';
                break;
                
            case 'delete_role':
                $roleId = $_POST['role_id'];
                
                // Check if role is in use
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM staff_users WHERE role_id = ?");
                $stmt->execute([$roleId]);
                $count = $stmt->fetch()['count'];
                
                if ($count > 0) {
                    throw new Exception('ไม่สามารถลบบทบาทนี้ได้เนื่องจากมีผู้ใช้ที่ใช้บทบาทนี้อยู่');
                }
                
                $db->beginTransaction();
                
                // Delete role permissions
                $stmt = $db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
                $stmt->execute([$roleId]);
                
                // Delete role
                $stmt = $db->prepare("DELETE FROM roles WHERE role_id = ?");
                $stmt->execute([$roleId]);
                
                $db->commit();
                logActivity("ลบบทบาท ID: {$roleId}");
                
                $message = 'ลบบทบาทสำเร็จ';
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

// Get roles list
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT r.*, 
               COUNT(DISTINCT su.staff_id) as user_count,
               COUNT(DISTINCT rp.permission_id) as permission_count
        FROM roles r
        LEFT JOIN staff_users su ON r.role_id = su.role_id
        LEFT JOIN role_permissions rp ON r.role_id = rp.role_id
        GROUP BY r.role_id
        ORDER BY r.role_name
    ");
    $stmt->execute();
    $roles = $stmt->fetchAll();
    
    // Get permissions
    $stmt = $db->prepare("SELECT * FROM permissions ORDER BY permission_name");
    $stmt->execute();
    $permissions = $stmt->fetchAll();
    
} catch (Exception $e) {
    $roles = [];
    $permissions = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการบทบาทและสิทธิ์ - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    
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
        
        .role-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
        }
        
        .permission-group {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .permission-group h6 {
            margin-bottom: 1rem;
            color: #007bff;
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
                        <a class="nav-link active" href="roles.php">
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
                            <h2>จัดการบทบาทและสิทธิ์</h2>
                            <p class="text-muted">กำหนดบทบาทและสิทธิ์การเข้าถึงระบบ</p>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                            <i class="fas fa-plus me-2"></i>เพิ่มบทบาทใหม่
                        </button>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Roles List -->
                    <div class="content-card">
                        <h5 class="mb-4">รายการบทบาท (<?php echo count($roles); ?> บทบาท)</h5>
                        
                        <?php if (empty($roles)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-user-tag fa-3x mb-3"></i>
                                <p>ไม่มีบทบาทในระบบ</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($roles as $role): ?>
                                <div class="role-card">
                                    <div class="row align-items-center">
                                        <div class="col-md-4">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($role['role_name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($role['description'] ?: 'ไม่มีคำอธิบาย'); ?>
                                            </small>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="d-flex">
                                                <div class="me-4">
                                                    <small class="d-block text-muted">ผู้ใช้</small>
                                                    <span class="badge bg-primary"><?php echo $role['user_count']; ?> คน</span>
                                                </div>
                                                <div>
                                                    <small class="d-block text-muted">สิทธิ์</small>
                                                    <span class="badge bg-info"><?php echo $role['permission_count']; ?> สิทธิ์</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 text-end">
                                            <button class="btn btn-sm btn-outline-primary me-1" 
                                                    onclick="editRole(<?php echo $role['role_id']; ?>)">
                                                <i class="fas fa-edit"></i> แก้ไข
                                            </button>
                                            
                                            <?php if ($role['user_count'] == 0): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete_role">
                                                    <input type="hidden" name="role_id" value="<?php echo $role['role_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('คุณต้องการลบบทบาทนี้หรือไม่?')">
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
                    
                    <!-- Permissions List -->
                    <div class="content-card">
                        <h5 class="mb-4">สิทธิ์ในระบบ</h5>
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>รหัส</th>
                                        <th>ชื่อสิทธิ์</th>
                                        <th>คำอธิบาย</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($permissions as $permission): ?>
                                        <tr>
                                            <td><?php echo $permission['permission_id']; ?></td>
                                            <td><?php echo htmlspecialchars($permission['permission_name']); ?></td>
                                            <td><?php echo htmlspecialchars($permission['description'] ?: '-'); ?></td>
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

    <!-- Add Role Modal -->
    <div class="modal fade" id="addRoleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add_role">
                    <div class="modal-header">
                        <h5 class="modal-title">เพิ่มบทบาทใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ชื่อบทบาท *</label>
                            <input type="text" class="form-control" name="role_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">คำอธิบาย</label>
                            <textarea class="form-control" name="description" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">สิทธิ์การเข้าถึง</label>
                            
                            <div class="permission-group">
                                <h6>การจัดการผู้ใช้และสิทธิ์</h6>
                                <div class="row">
                                    <?php foreach ($permissions as $permission): ?>
                                        <?php if (strpos($permission['permission_name'], 'manage_') === 0): ?>
                                            <div class="col-md-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="permissions[]" value="<?php echo $permission['permission_id']; ?>"
                                                           id="perm_add_<?php echo $permission['permission_id']; ?>">
                                                    <label class="form-check-label" for="perm_add_<?php echo $permission['permission_id']; ?>">
                                                        <?php echo htmlspecialchars($permission['description'] ?: $permission['permission_name']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="permission-group">
                                <h6>การจัดการคิว</h6>
                                <div class="row">
                                    <?php foreach ($permissions as $permission): ?>
                                        <?php if (strpos($permission['permission_name'], 'manage_') !== 0 && strpos($permission['permission_name'], 'view_') !== 0): ?>
                                            <div class="col-md-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="permissions[]" value="<?php echo $permission['permission_id']; ?>"
                                                           id="perm_add_<?php echo $permission['permission_id']; ?>">
                                                    <label class="form-check-label" for="perm_add_<?php echo $permission['permission_id']; ?>">
                                                        <?php echo htmlspecialchars($permission['description'] ?: $permission['permission_name']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="permission-group">
                                <h6>การดูรายงาน</h6>
                                <div class="row">
                                    <?php foreach ($permissions as $permission): ?>
                                        <?php if (strpos($permission['permission_name'], 'view_') === 0): ?>
                                            <div class="col-md-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="permissions[]" value="<?php echo $permission['permission_id']; ?>"
                                                           id="perm_add_<?php echo $permission['permission_id']; ?>">
                                                    <label class="form-check-label" for="perm_add_<?php echo $permission['permission_id']; ?>">
                                                        <?php echo htmlspecialchars($permission['description'] ?: $permission['permission_name']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">เพิ่มบทบาท</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Role Modal -->
    <div class="modal fade" id="editRoleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit_role">
                    <input type="hidden" name="role_id" id="edit_role_id">
                    <div class="modal-header">
                        <h5 class="modal-title">แก้ไขบทบาท</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">ชื่อบทบาท *</label>
                            <input type="text" class="form-control" name="role_name" id="edit_role_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">คำอธิบาย</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">สิทธิ์การเข้าถึง</label>
                            
                            <div class="permission-group">
                                <h6>การจัดการผู้ใช้และสิทธิ์</h6>
                                <div class="row">
                                    <?php foreach ($permissions as $permission): ?>
                                        <?php if (strpos($permission['permission_name'], 'manage_') === 0): ?>
                                            <div class="col-md-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="permissions[]" value="<?php echo $permission['permission_id']; ?>"
                                                           id="perm_edit_<?php echo $permission['permission_id']; ?>">
                                                    <label class="form-check-label" for="perm_edit_<?php echo $permission['permission_id']; ?>">
                                                        <?php echo htmlspecialchars($permission['description'] ?: $permission['permission_name']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="permission-group">
                                <h6>การจัดการคิว</h6>
                                <div class="row">
                                    <?php foreach ($permissions as $permission): ?>
                                        <?php if (strpos($permission['permission_name'], 'manage_') !== 0 && strpos($permission['permission_name'], 'view_') !== 0): ?>
                                            <div class="col-md-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="permissions[]" value="<?php echo $permission['permission_id']; ?>"
                                                           id="perm_edit_<?php echo $permission['permission_id']; ?>">
                                                    <label class="form-check-label" for="perm_edit_<?php echo $permission['permission_id']; ?>">
                                                        <?php echo htmlspecialchars($permission['description'] ?: $permission['permission_name']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="permission-group">
                                <h6>การดูรายงาน</h6>
                                <div class="row">
                                    <?php foreach ($permissions as $permission): ?>
                                        <?php if (strpos($permission['permission_name'], 'view_') === 0): ?>
                                            <div class="col-md-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="permissions[]" value="<?php echo $permission['permission_id']; ?>"
                                                           id="perm_edit_<?php echo $permission['permission_id']; ?>">
                                                    <label class="form-check-label" for="perm_edit_<?php echo $permission['permission_id']; ?>">
                                                        <?php echo htmlspecialchars($permission['description'] ?: $permission['permission_name']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
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
        function editRole(roleId) {
            // Clear all checkboxes first
            $('#editRoleModal input[type="checkbox"]').prop('checked', false);
            
            // Get role data
            $.get('../api/get_role.php', {role_id: roleId}, function(data) {
                $('#edit_role_id').val(data.role.role_id);
                $('#edit_role_name').val(data.role.role_name);
                $('#edit_description').val(data.role.description);
                
                // Set permissions
                data.permissions.forEach(function(permission) {
                    $('#perm_edit_' + permission.permission_id).prop('checked', true);
                });
                
                $('#editRoleModal').modal('show');
            });
        }
    </script>
</body>
</html>
