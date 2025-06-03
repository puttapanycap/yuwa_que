<?php
require_once '../config/config.php';

if (isLoggedIn()) {
    redirectTo(BASE_URL . '/staff/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                SELECT s.*, r.role_name 
                FROM staff_users s 
                LEFT JOIN roles r ON s.role_id = r.role_id 
                WHERE s.username = ? AND s.is_active = 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Get user permissions
                $stmt = $db->prepare("
                    SELECT p.permission_name 
                    FROM role_permissions rp 
                    JOIN permissions p ON rp.permission_id = p.permission_id 
                    WHERE rp.role_id = ?
                ");
                $stmt->execute([$user['role_id']]);
                $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Get accessible service points
                $stmt = $db->prepare("
                    SELECT sp.service_point_id, sp.point_name, sp.position_key
                    FROM staff_service_point_access sspa
                    JOIN service_points sp ON sspa.service_point_id = sp.service_point_id
                    WHERE sspa.staff_id = ? AND sp.is_active = 1
                ");
                $stmt->execute([$user['staff_id']]);
                $servicePoints = $stmt->fetchAll();
                
                // Set session
                $_SESSION['staff_id'] = $user['staff_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['permissions'] = $permissions;
                $_SESSION['service_points'] = $servicePoints;
                
                // Update last login
                $stmt = $db->prepare("UPDATE staff_users SET last_login = CURRENT_TIMESTAMP WHERE staff_id = ?");
                $stmt->execute([$user['staff_id']]);
                
                logActivity("เข้าสู่ระบบ", $user['staff_id']);
                
                redirectTo(BASE_URL . '/staff/dashboard.php');
            } else {
                $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            }
        } catch (Exception $e) {
            $error = 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบเรียกคิวโรงพยาบาล</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            max-width: 400px;
            width: 100%;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
        }
        
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
        
        .btn-login {
            border-radius: 10px;
            padding: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="login-container">
                    <div class="login-header">
                        <i class="fas fa-user-shield fa-3x text-primary mb-3"></i>
                        <h2 class="h4 text-primary">เข้าสู่ระบบเจ้าหน้าที่</h2>
                        <p class="text-muted">ระบบเรียกคิวโรงพยาบาลยุวประสาทไวทโยปถัมภ์</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label">ชื่อผู้ใช้</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">รหัสผ่าน</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-login w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
                        </button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <a href="../index.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-2"></i>กลับหน้าแรก
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
