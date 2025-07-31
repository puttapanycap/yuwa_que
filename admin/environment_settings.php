<?php
require_once '../config/config.php';
requireLogin();

if (!hasPermission('manage_settings')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $envFile = ROOT_PATH . '/.env';
        $envContent = '';
        
        // Read current .env file
        if (file_exists($envFile)) {
            $envContent = file_get_contents($envFile);
        }
        
        // Update environment variables
        foreach ($_POST['env'] as $key => $value) {
            // Escape special characters in value
            if (strpos($value, ' ') !== false || strpos($value, '#') !== false) {
                $value = '"' . addslashes($value) . '"';
            }
            
            $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
            $replacement = $key . '=' . $value;
            
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n" . $replacement;
            }
        }
        
        // Write back to .env file
        if (file_put_contents($envFile, $envContent) !== false) {
            // Reload environment variables
            EnvLoader::load($envFile);
            
            logActivity("อัปเดตการตั้งค่า Environment Variables");
            $message = 'บันทึกการตั้งค่าสำเร็จ กรุณารีเฟรชหน้าเว็บเพื่อให้การตั้งค่าใหม่มีผล';
            $messageType = 'success';
        } else {
            throw new Exception('ไม่สามารถเขียนไฟล์ .env ได้');
        }
        
    } catch (Exception $e) {
        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Environment variables that should be managed through environment only
$envGroups = [
    'Database' => [
        'DB_HOST' => 'Database Host',
        'DB_NAME' => 'Database Name',
        'DB_USERNAME' => 'Database Username',
        'DB_PASSWORD' => 'Database Password',
        'DB_CHARSET' => 'Database Charset'
    ],
    'Core Application' => [
        'BASE_URL' => 'Base URL',
        'APP_DEBUG' => 'Debug Mode',
        'APP_TIMEZONE' => 'Timezone'
    ],
    'Security' => [
        'JWT_SECRET' => 'JWT Secret Key',
        'CSRF_TOKEN_NAME' => 'CSRF Token Name',
        'SESSION_LIFETIME' => 'Session Lifetime (seconds)'
    ],
    'File Upload' => [
        'UPLOAD_PATH' => 'Upload Path',
        'MAX_FILE_SIZE' => 'Max File Size (bytes)',
        'ALLOWED_IMAGE_TYPES' => 'Allowed Image Types',
        'ALLOWED_AUDIO_TYPES' => 'Allowed Audio Types'
    ],
    'System Paths' => [
        'LOG_PATH' => 'Log Path',
        'CACHE_PATH' => 'Cache Path',
        'BACKUP_PATH' => 'Backup Path'
    ],
    'Mobile API' => [
        'MOBILE_API_ENABLED' => 'Mobile API Enabled',
        'API_RATE_LIMIT' => 'API Rate Limit',
        'API_RATE_LIMIT_WINDOW' => 'Rate Limit Window (seconds)'
    ],
    'Logging' => [
        'LOG_LEVEL' => 'Log Level',
        'LOG_MAX_FILES' => 'Max Log Files'
    ],
    'Cache' => [
        'CACHE_ENABLED' => 'Cache Enabled',
        'CACHE_DEFAULT_TTL' => 'Default Cache TTL (seconds)'
    ],
    'Development' => [
        'QUERY_LOG_ENABLED' => 'Query Log Enabled',
        'PERFORMANCE_MONITORING' => 'Performance Monitoring'
    ],
    'Multi-Hospital' => [
        'MULTI_HOSPITAL_ENABLED' => 'Multi-Hospital Support',
        'DEFAULT_HOSPITAL_ID' => 'Default Hospital ID'
    ]
];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่า Environment - <?php echo getAppName(); ?></title>
    
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
        
        .env-group {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .env-group h6 {
            color: #007bff;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .form-control[type="password"] {
            font-family: monospace;
        }
        
        .env-value {
            font-family: monospace;
            font-size: 0.9em;
        }
        
        .alert-info {
            border-left: 4px solid #17a2b8;
        }
        
        .alert-warning {
            border-left: 4px solid #ffc107;
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
                        <a class="nav-link" href="service_flows.php">
                            <i class="fas fa-route"></i>Service Flows
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
                            <h2>ตั้งค่า Environment Variables</h2>
                            <p class="text-muted">จัดการการตั้งค่าระบบระดับ Environment</p>
                        </div>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>หมายเหตุ:</strong> การตั้งค่าเหล่านี้เป็นการตั้งค่าระดับระบบ สำหรับการตั้งค่าอื่นๆ เช่น การตั้งค่าแอปพลิเคชัน, ระบบคิว, เสียง, อีเมล และ Telegram กรุณาใช้หน้า <a href="settings.php" class="alert-link">การตั้งค่าทั่วไป</a>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>คำเตือน:</strong> การเปลี่ยนแปลงการตั้งค่าเหล่านี้อาจส่งผลต่อการทำงานของระบบ กรุณาตรวจสอบให้แน่ใจก่อนบันทึก
                    </div>
                    
                    <form method="POST">
                        <?php foreach ($envGroups as $groupName => $variables): ?>
                            <div class="content-card">
                                <div class="env-group">
                                    <h6><i class="fas fa-cog me-2"></i><?php echo $groupName; ?></h6>
                                    
                                    <?php foreach ($variables as $key => $label): ?>
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $label; ?></label>
                                            <div class="input-group">
                                                <span class="input-group-text env-value"><?php echo $key; ?></span>
                                                <?php 
                                                $currentValue = env($key, '');
                                                $inputType = 'text';
                                                
                                                // Determine input type based on key name
                                                if (strpos($key, 'PASSWORD') !== false || strpos($key, 'SECRET') !== false || strpos($key, 'KEY') !== false) {
                                                    $inputType = 'password';
                                                } elseif (strpos($key, 'DEBUG') !== false || strpos($key, 'ENABLED') !== false) {
                                                    $inputType = 'select';
                                                } elseif (strpos($key, 'PORT') !== false || strpos($key, 'LENGTH') !== false || strpos($key, 'COUNT') !== false || strpos($key, 'DAYS') !== false || strpos($key, 'LIFETIME') !== false || strpos($key, 'SIZE') !== false || strpos($key, 'TTL') !== false || strpos($key, 'LIMIT') !== false || strpos($key, 'ID') !== false) {
                                                    $inputType = 'number';
                                                }
                                                ?>
                                                
                                                <?php if ($inputType === 'select'): ?>
                                                    <select class="form-select" name="env[<?php echo $key; ?>]">
                                                        <option value="true" <?php echo $currentValue == 'true' || $currentValue === true ? 'selected' : ''; ?>>True</option>
                                                        <option value="false" <?php echo $currentValue == 'false' || $currentValue === false ? 'selected' : ''; ?>>False</option>
                                                    </select>
                                                <?php else: ?>
                                                    <input type="<?php echo $inputType; ?>" 
                                                           class="form-control" 
                                                           name="env[<?php echo $key; ?>]" 
                                                           value="<?php echo htmlspecialchars($currentValue); ?>"
                                                           <?php echo $inputType === 'number' ? 'min="0"' : ''; ?>>
                                                <?php endif; ?>
                                                
                                                <?php if ($inputType === 'password'): ?>
                                                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword(this)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($key === 'JWT_SECRET' && (empty($currentValue) || $currentValue === 'your-super-secret-jwt-key-change-this-in-production')): ?>
                                                <div class="form-text text-danger">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    กรุณาเปลี่ยน JWT Secret เพื่อความปลอดภัย
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Save Button -->
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>บันทึกการตั้งค่า
                            </button>
                            <a href="settings.php" class="btn btn-secondary btn-lg ms-2">
                                <i class="fas fa-arrow-left me-2"></i>กลับ
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword(button) {
            const input = button.previousElementSibling;
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Warn before leaving if form has changes
        let formChanged = false;
        
        document.querySelectorAll('input, select').forEach(element => {
            element.addEventListener('change', () => {
                formChanged = true;
            });
        });
        
        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
        
        document.querySelector('form').addEventListener('submit', () => {
            formChanged = false;
        });
    </script>
</body>
</html>
