<?php
require_once '../config/config.php';

// Ensure session is started for login check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireLogin();

if (!hasPermission('admin')) {
    redirectTo(BASE_URL . '/staff/dashboard.php');
}

$diagnostics = [];
$errors = [];
$fixResults = []; // Initialize fixResults array

try {
    $db = getDB();
    
    // Test 1: Database Connection
    $diagnostics['connection'] = [
        'name' => 'การเชื่อมต่อฐานข้อมูล',
        'status' => $db ? 'success' : 'error',
        'message' => $db ? 'เชื่อมต่อสำเร็จ' : 'ไม่สามารถเชื่อมต่อได้'
    ];
    
    if ($db) {
        // Test 2: Check required tables
        $requiredTables = [
            'users', 'roles', 'service_types', 'service_points', 'service_flows', 'user_service_points',
            'queues', 'queue_history', 'queue_flow_tracking',
            'audio_settings', 'audio_files', 'audio_call_history', 'tts_cache',
            'notification_types', 'notifications', 'notification_preferences', 'notification_deliveries',
            'auto_reset_schedules', 'auto_reset_logs',
            'report_templates', 'scheduled_reports', 'report_execution_logs', 'daily_performance_summary',
            'dashboard_widgets', 'dashboard_layouts', 'dashboard_preferences', 'dashboard_alerts',
            'api_access_tokens', 'mobile_app_sessions', 'api_request_logs',
            'security_logs', 'two_factor_auth', 'password_history', 'user_sessions', 'file_upload_logs',
            'audit_logs', 'system_settings'
        ];
        
        foreach ($requiredTables as $table) {
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM `$table`");
                $count = $stmt->fetchColumn();
                $diagnostics["table_$table"] = [
                    'name' => "ตาราง $table",
                    'status' => 'success',
                    'message' => "พบ $count แถว"
                ];
            } catch (Exception $e) {
                $diagnostics["table_$table"] = [
                    'name' => "ตาราง $table",
                    'status' => 'error',
                    'message' => 'ไม่พบตาราง: ' . $e->getMessage()
                ];
                $errors[] = "ตาราง $table ไม่พบ";
            }
        }
        
        // Test 3: Check queue data integrity
        try {
            $stmt = $db->query("
                SELECT COUNT(*) as total_queues,
                       COUNT(CASE WHEN status = 'waiting' THEN 1 END) as waiting,
                       COUNT(CASE WHEN status = 'serving' THEN 1 END) as serving,
                       COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed
                FROM queues 
                WHERE DATE(created_at) = CURDATE()
            ");
            $queueStats = $stmt->fetch();
            
            $diagnostics['queue_integrity'] = [
                'name' => 'ความสมบูรณ์ข้อมูลคิว',
                'status' => 'success',
                'message' => sprintf(
                    'วันนี้: %d คิว (รอ: %d, กำลังให้บริการ: %d, เสร็จ: %d)',
                    $queueStats['total_queues'],
                    $queueStats['waiting'],
                    $queueStats['serving'],
                    $queueStats['completed']
                )
            ];
        } catch (Exception $e) {
            $diagnostics['queue_integrity'] = [
                'name' => 'ความสมบูรณ์ข้อมูลคิว',
                'status' => 'error',
                'message' => 'ไม่สามารถตรวจสอบได้: ' . $e->getMessage()
            ];
        }
        
        // Test 4: Check foreign key relationships (example: queues to service_types)
        try {
            $stmt = $db->query("
                SELECT COUNT(*) as orphaned_queues
                FROM queues q
                LEFT JOIN service_types st ON q.service_type_id = st.service_type_id
                WHERE st.service_type_id IS NULL
            ");
            $orphanedQueues = $stmt->fetchColumn();
            
            $diagnostics['foreign_keys_queues_service_types'] = [
                'name' => 'ความสัมพันธ์ข้อมูล (คิว-ประเภทบริการ)',
                'status' => $orphanedQueues > 0 ? 'warning' : 'success',
                'message' => $orphanedQueues > 0 ? 
                    "พบคิวที่ไม่มีประเภทบริการ: $orphanedQueues คิว" : 
                    'ความสัมพันธ์ข้อมูลปกติ'
            ];
        } catch (Exception $e) {
            $diagnostics['foreign_keys_queues_service_types'] = [
                'name' => 'ความสัมพันธ์ข้อมูล (คิว-ประเภทบริการ)',
                'status' => 'error',
                'message' => 'ไม่สามารถตรวจสอบได้: ' . $e->getMessage()
            ];
        }
        
        // Test 5: Check system settings count
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM system_settings");
            $settingsCount = $stmt->fetchColumn();
            
            $diagnostics['system_settings_count'] = [
                'name' => 'การตั้งค่าระบบ',
                'status' => $settingsCount > 0 ? 'success' : 'warning',
                'message' => $settingsCount > 0 ? 
                    "พบการตั้งค่า $settingsCount รายการ" : 
                    'ไม่พบการตั้งค่าระบบ'
            ];
        } catch (Exception $e) {
            $diagnostics['system_settings_count'] = [
                'name' => 'การตั้งค่าระบบ',
                'status' => 'error',
                'message' => 'ไม่สามารถตรวจสอบได้: ' . $e->getMessage()
            ];
        }

        // Test 6: Check roles count
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM roles");
            $rolesCount = $stmt->fetchColumn();
            
            $diagnostics['roles_count'] = [
                'name' => 'จำนวนบทบาท',
                'status' => $rolesCount > 0 ? 'success' : 'warning',
                'message' => "พบ $rolesCount บทบาท"
            ];
        } catch (Exception $e) {
            $diagnostics['roles_count'] = [
                'name' => 'จำนวนบทบาท',
                'status' => 'error',
                'message' => 'ไม่สามารถตรวจสอบได้: ' . $e->getMessage()
            ];
        }

        // Test 7: Check users count
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM users");
            $usersCount = $stmt->fetchColumn();
            
            $diagnostics['users_count'] = [
                'name' => 'จำนวนผู้ใช้',
                'status' => $usersCount > 0 ? 'success' : 'warning',
                'message' => "พบ $usersCount ผู้ใช้"
            ];
        } catch (Exception $e) {
            $diagnostics['users_count'] = [
                'name' => 'จำนวนผู้ใช้',
                'status' => 'error',
                'message' => 'ไม่สามารถตรวจสอบได้: ' . $e->getMessage()
            ];
        }

    }
    
} catch (Exception $e) {
    $errors[] = 'ข้อผิดพลาดทั่วไป: ' . $e->getMessage();
}

// Auto-fix function
if (isset($_POST['auto_fix'])) {
    try {
        $db = getDB(); // Re-get DB connection if not already available or if it failed
        if (!$db) {
            $fixResults[] = 'ไม่สามารถเชื่อมต่อฐานข้อมูลเพื่อทำการแก้ไขอัตโนมัติได้';
        } else {
            // Fix 1: Create missing default system settings
            $defaultSettings = [
                ['hospital_name', 'โรงพยาบาลยุวประสาทไวทโยปถัมภ์', 'string', 'application', 'ชื่อโรงพยาบาล', 1, 1],
                ['queue_reset_time', '00:00', 'string', 'queue', 'เวลารีเซ็ตคิวอัตโนมัติ', 0, 1],
                ['tts_enabled', 'true', 'boolean', 'audio', 'เปิดใช้งานเสียงเรียกคิว', 1, 1],
                ['notification_enabled', 'true', 'boolean', 'notification', 'เปิดใช้งานการแจ้งเตือน', 0, 1],
                ['app_name', 'โรงพยาบาลยุวประสาทไวทโยปถัมภ์', 'string', 'application', 'ชื่อแอปพลิเคชัน', 1, 1],
                ['app_description', 'ระบบจัดการคิวโรงพยาบาล', 'string', 'application', 'คำอธิบายแอปพลิเคชัน', 1, 1],
                ['app_version', '2.0.0', 'string', 'application', 'เวอร์ชันแอปพลิเคชัน', 1, 0],
                ['app_timezone', 'Asia/Bangkok', 'string', 'application', 'เขตเวลา', 1, 1],
                ['app_language', 'th', 'string', 'application', 'ภาษาเริ่มต้น', 1, 1],
                ['queue_prefix_length', '1', 'integer', 'queue', 'ความยาว Prefix คิว', 1, 1],
                ['queue_number_length', '3', 'integer', 'queue', 'ความยาวหมายเลขคิว', 1, 1],
                ['max_queue_per_day', '999', 'integer', 'queue', 'จำนวนคิวสูงสุดต่อวัน', 1, 1],
                ['queue_timeout_minutes', '30', 'integer', 'queue', 'เวลาหมดอายุคิว (นาที)', 1, 1],
                ['display_refresh_interval', '3', 'integer', 'queue', 'ความถี่ในการรีเฟรชหน้าจอ (วินาที)', 1, 1],
                ['enable_priority_queue', 'true', 'boolean', 'queue', 'เปิดใช้งานคิวพิเศษ', 1, 1],
                ['auto_forward_enabled', 'false', 'boolean', 'queue', 'ส่งต่อคิวอัตโนมัติ', 1, 1],
                ['working_hours_start', '08:00', 'string', 'schedule', 'เวลาเปิดทำการ', 1, 1],
                ['working_hours_end', '16:00', 'string', 'schedule', 'เวลาปิดทำการ', 1, 1],
                ['tts_enabled', 'true', 'boolean', 'audio', 'เปิดใช้งานระบบเสียงเรียกคิว', 1, 1],
                ['tts_provider', 'browser', 'string', 'audio', 'ผู้ให้บริการ TTS', 1, 1],
                ['tts_language', 'th-TH', 'string', 'audio', 'ภาษา TTS', 1, 1],
                ['tts_speed', '1.0', 'string', 'audio', 'ความเร็วเสียง', 1, 1],
                ['audio_volume', '1.0', 'string', 'audio', 'ระดับเสียง', 1, 1],
                ['audio_repeat_count', '2', 'integer', 'audio', 'จำนวนครั้งที่เล่นซ้ำ', 1, 1],
                ['email_notifications', 'false', 'boolean', 'email', 'เปิดใช้งานการแจ้งเตือนทางอีเมล', 0, 1],
                ['mail_host', 'smtp.gmail.com', 'string', 'email', 'SMTP Host', 0, 1],
                ['mail_port', '587', 'integer', 'email', 'SMTP Port', 0, 1],
                ['mail_encryption', 'tls', 'string', 'email', 'การเข้ารหัส', 0, 1],
                ['mail_from_address', 'noreply@yuwaprasart.com', 'string', 'email', 'อีเมลผู้ส่ง', 0, 1],
                ['mail_from_name', 'Yuwaprasart Queue System', 'string', 'email', 'ชื่อผู้ส่ง', 0, 1],
                ['telegram_notifications', 'false', 'boolean', 'telegram', 'เปิดใช้งานการแจ้งเตือนทาง Telegram', 0, 1],
                ['telegram_notify_template', 'คิว {queue_number} กรุณามาที่จุดบริการ {service_point}', 'string', 'telegram', 'เทมเพลตข้อความ', 0, 1]
            ];
            
            foreach ($defaultSettings as $setting) {
                $stmt = $db->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, category, description, is_public, is_editable) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute($setting);
            }
            $fixResults[] = 'เพิ่มการตั้งค่าระบบเริ่มต้นที่ขาดหายไปแล้ว';

            // Fix 2: Create missing default roles
            $defaultRoles = [
                [1, 'admin', 'ผู้ดูแลระบบ', '{"all": true}', 1],
                [2, 'manager', 'ผู้จัดการ', '{"users": {"view": true, "create": true, "edit": true}, "queues": {"view": true, "manage": true}, "reports": {"view": true, "create": true}, "settings": {"view": true}}', 1],
                [3, 'staff', 'เจ้าหน้าที่', '{"queues": {"view": true, "manage": true}, "reports": {"view": true}}', 1],
                [4, 'viewer', 'ผู้ดูข้อมูล', '{"queues": {"view": true}, "reports": {"view": true}}', 1]
            ];
            foreach ($defaultRoles as $role) {
                $stmt = $db->prepare("INSERT IGNORE INTO roles (role_id, role_name, role_description, permissions, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute($role);
            }
            $fixResults[] = 'เพิ่มบทบาทเริ่มต้นที่ขาดหายไปแล้ว';

            // Fix 3: Create default admin user if not exists (username: admin, password: admin123)
            $adminPasswordHash = password_hash('admin123', PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT IGNORE INTO users (user_id, username, password, email, full_name, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([1, 'admin', $adminPasswordHash, 'admin@yuwaprasart.com', 'ผู้ดูแลระบบ', 1, 1]);
            $fixResults[] = 'เพิ่มผู้ใช้ admin เริ่มต้นแล้ว (หากยังไม่มี)';
            
            // Fix 4: Update display_order for service points without display_order
            $stmt = $db->prepare("UPDATE service_points SET display_order = service_point_id WHERE display_order IS NULL OR display_order = 0");
            $updated = $stmt->execute();
            if ($updated) {
                $fixResults[] = 'อัปเดตลำดับการแสดงผลจุดบริการแล้ว';
            }

            // Fix 5: Update display_order for service_types without display_order
            $stmt = $db->prepare("UPDATE service_types SET display_order = service_type_id WHERE display_order IS NULL OR display_order = 0");
            $updated = $stmt->execute();
            if ($updated) {
                $fixResults[] = 'อัปเดตลำดับการแสดงผลประเภทบริการแล้ว';
            }
            
        }
    } catch (Exception $e) {
        $fixResults[] = 'ข้อผิดพลาดในการแก้ไขอัตโนมัติ: ' . $e->getMessage();
    }
    // Re-run diagnostics after auto-fix attempt
    header("Location: " . BASE_URL . "/admin/database_diagnostic.php?fixed=true");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การวินิจฉัยระบบ - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
        }
        
        .diagnostic-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }
        
        .status-success {
            border-left: 5px solid #28a745;
        }
        
        .status-warning {
            border-left: 5px solid #ffc107;
        }
        
        .status-error {
            border-left: 5px solid #dc3545;
        }
        
        .status-icon {
            font-size: 1.5rem;
        }
        
        .status-icon.success {
            color: #28a745;
        }
        
        .status-icon.warning {
            color: #ffc107;
        }
        
        .status-icon.error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-hospital me-2"></i>
                โรงพยาบาลยุวประสาทไวทโยปถัมภ์
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-stethoscope me-2"></i>การวินิจฉัยระบบ</h2>
                    <div>
                        <button class="btn btn-outline-primary" onclick="location.reload()">
                            <i class="fas fa-sync-alt me-2"></i>ตรวจสอบใหม่
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>กลับ
                        </a>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i>ข้อผิดพลาดที่พบ:</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['fixed']) && $_GET['fixed'] == 'true'): ?>
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle me-2"></i>การแก้ไขอัตโนมัติเสร็จสมบูรณ์!</h5>
                    <p class="mb-0">ระบบได้พยายามแก้ไขปัญหาที่พบแล้ว กรุณาตรวจสอบสถานะอีกครั้ง</p>
                </div>
                <?php endif; ?>

                <div class="row">
                    <?php foreach ($diagnostics as $key => $diagnostic): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card diagnostic-card status-<?php echo $diagnostic['status']; ?>">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="status-icon <?php echo $diagnostic['status']; ?> me-3">
                                        <?php
                                        switch ($diagnostic['status']) {
                                            case 'success':
                                                echo '<i class="fas fa-check-circle"></i>';
                                                break;
                                            case 'warning':
                                                echo '<i class="fas fa-exclamation-triangle"></i>';
                                                break;
                                            case 'error':
                                                echo '<i class="fas fa-times-circle"></i>';
                                                break;
                                        }
                                        ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="card-title mb-1"><?php echo htmlspecialchars($diagnostic['name']); ?></h6>
                                        <p class="card-text text-muted mb-0">
                                            <?php echo htmlspecialchars($diagnostic['message']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-tools me-2"></i>เครื่องมือแก้ไข</h5>
                    </div>
                    <div class="card-body">
                        <p>หากพบปัญหา คุณสามารถใช้เครื่องมือแก้ไขอัตโนมัติเพื่อแก้ไขปัญหาเบื้องต้น</p>
                        
                        <form method="POST" class="d-inline">
                            <button type="submit" name="auto_fix" class="btn btn-warning" 
                                    onclick="return confirm('คุณต้องการให้ระบบแก้ไขปัญหาอัตโนมัติหรือไม่? การดำเนินการนี้อาจเพิ่มข้อมูลเริ่มต้นที่ขาดหายไป')">
                                <i class="fas fa-magic me-2"></i>แก้ไขอัตโนมัติ
                            </button>
                        </form>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>การแก้ไขอัตโนมัติจะทำการ:</strong><br>
                                • เพิ่มการตั้งค่าเริ่มต้นที่ขาดหายไป<br>
                                • เพิ่มบทบาทและผู้ใช้เริ่มต้น (admin, manager, staff) หากยังไม่มี<br>
                                • อัปเดตลำดับจุดบริการและประเภทบริการ
                            </small>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle me-2"></i>ข้อมูลระบบ</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>เวอร์ชัน PHP:</strong> <?php echo PHP_VERSION; ?><br>
                                <strong>เวลาเซิร์ฟเวอร์:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
                                <strong>Timezone:</strong> <?php echo date_default_timezone_get(); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?><br>
                                <strong>Max Execution Time:</strong> <?php echo ini_get('max_execution_time'); ?>s<br>
                                <strong>Upload Max Size:</strong> <?php echo ini_get('upload_max_filesize'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
