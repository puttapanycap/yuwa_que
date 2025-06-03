<?php
require_once '../config/config.php';
requireLogin();

if (!hasPermission('admin')) {
    redirectTo(BASE_URL . '/staff/dashboard.php');
}

$diagnostics = [];
$errors = [];

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
            'queues', 'queue_types', 'service_points', 'staff', 
            'service_flow_history', 'settings', 'audit_logs'
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
                       COUNT(CASE WHEN current_status = 'waiting' THEN 1 END) as waiting,
                       COUNT(CASE WHEN current_status = 'processing' THEN 1 END) as processing,
                       COUNT(CASE WHEN current_status = 'completed' THEN 1 END) as completed
                FROM queues 
                WHERE DATE(creation_time) = CURDATE()
            ");
            $queueStats = $stmt->fetch();
            
            $diagnostics['queue_integrity'] = [
                'name' => 'ความสมบูรณ์ข้อมูลคิว',
                'status' => 'success',
                'message' => sprintf(
                    'วันนี้: %d คิว (รอ: %d, กำลังให้บริการ: %d, เสร็จ: %d)',
                    $queueStats['total_queues'],
                    $queueStats['waiting'],
                    $queueStats['processing'],
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
        
        // Test 4: Check foreign key relationships
        try {
            $stmt = $db->query("
                SELECT COUNT(*) as orphaned_queues
                FROM queues q
                LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
                WHERE qt.queue_type_id IS NULL
            ");
            $orphanedQueues = $stmt->fetchColumn();
            
            $diagnostics['foreign_keys'] = [
                'name' => 'ความสัมพันธ์ข้อมูล',
                'status' => $orphanedQueues > 0 ? 'warning' : 'success',
                'message' => $orphanedQueues > 0 ? 
                    "พบคิวที่ไม่มีประเภท: $orphanedQueues คิว" : 
                    'ความสัมพันธ์ข้อมูลปกติ'
            ];
        } catch (Exception $e) {
            $diagnostics['foreign_keys'] = [
                'name' => 'ความสัมพันธ์ข้อมูล',
                'status' => 'error',
                'message' => 'ไม่สามารถตรวจสอบได้: ' . $e->getMessage()
            ];
        }
        
        // Test 5: Check settings
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM settings");
            $settingsCount = $stmt->fetchColumn();
            
            $diagnostics['settings'] = [
                'name' => 'การตั้งค่าระบบ',
                'status' => $settingsCount > 0 ? 'success' : 'warning',
                'message' => $settingsCount > 0 ? 
                    "พบการตั้งค่า $settingsCount รายการ" : 
                    'ไม่พบการตั้งค่าระบบ'
            ];
        } catch (Exception $e) {
            $diagnostics['settings'] = [
                'name' => 'การตั้งค่าระบบ',
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
    $fixResults = [];
    
    try {
        // Fix 1: Create missing settings
        $defaultSettings = [
            ['hospital_name', 'โรงพยาบาลยุวประสาทไวทโยปถัมภ์', 'ชื่อโรงพยาบาล'],
            ['queue_reset_time', '00:00', 'เวลารีเซ็ตคิวอัตโนมัติ'],
            ['tts_enabled', '0', 'เปิดใช้งานเสียงเรียกคิว'],
            ['notification_enabled', '1', 'เปิดใช้งานการแจ้งเตือน']
        ];
        
        foreach ($defaultSettings as $setting) {
            $stmt = $db->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?)");
            $stmt->execute($setting);
        }
        
        $fixResults[] = 'เพิ่มการตั้งค่าเริ่มต้นแล้ว';
        
        // Fix 2: Update sequence for service points without sequence
        $stmt = $db->prepare("UPDATE service_points SET sequence_order = service_point_id WHERE sequence_order IS NULL OR sequence_order = 0");
        $updated = $stmt->execute();
        if ($updated) {
            $fixResults[] = 'อัปเดตลำดับจุดบริการแล้ว';
        }
        
    } catch (Exception $e) {
        $fixResults[] = 'ข้อผิดพลาดในการแก้ไข: ' . $e->getMessage();
    }
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

                <?php if (isset($fixResults)): ?>
                <div class="alert alert-info">
                    <h5><i class="fas fa-tools me-2"></i>ผลการแก้ไขอัตโนมัติ:</h5>
                    <ul class="mb-0">
                        <?php foreach ($fixResults as $result): ?>
                            <li><?php echo htmlspecialchars($result); ?></li>
                        <?php endforeach; ?>
                    </ul>
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
                                    onclick="return confirm('คุณต้องการให้ระบบแก้ไขปัญหาอัตโนมัติหรือไม่?')">
                                <i class="fas fa-magic me-2"></i>แก้ไขอัตโนมัติ
                            </button>
                        </form>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>การแก้ไขอัตโนมัติจะทำการ:</strong><br>
                                • เพิ่มการตั้งค่าเริ่มต้นที่ขาดหายไป<br>
                                • อัปเดตลำดับจุดบริการ<br>
                                • แก้ไขข้อมูลที่ไม่สมบูรณ์
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
