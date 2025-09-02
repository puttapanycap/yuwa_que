<?php
require_once '../config/config.php';
requireLogin();

if (!hasPermission('manage_settings')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_settings') {
        // บันทึกการตั้งค่าทั่วไป
        setSetting('auto_reset_enabled', $_POST['auto_reset_enabled'] ?? '0');
        setSetting('auto_reset_notification', $_POST['auto_reset_notification'] ?? '0');
        setSetting('auto_reset_backup_before', $_POST['auto_reset_backup_before'] ?? '0');
        setSetting('auto_reset_max_retries', $_POST['auto_reset_max_retries'] ?? '3');
        
        $success_message = "บันทึกการตั้งค่าเรียบร้อยแล้ว";
        
    } elseif ($action === 'add_schedule') {
        // เพิ่ม Schedule ใหม่
        try {
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO auto_reset_schedules 
                (schedule_name, reset_type, target_id, schedule_time, schedule_days, is_active, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $targetId = null;
            if ($_POST['reset_type'] !== 'all') {
                $targetId = $_POST['target_id'] ?? null;
            }
            
            $stmt->execute([
                $_POST['schedule_name'],
                $_POST['reset_type'],
                $targetId,
                $_POST['schedule_time'],
                implode(',', $_POST['schedule_days'] ?? []),
                $_POST['is_active'] ?? '1',
                $_SESSION['staff_id']
            ]);
            
            $success_message = "เพิ่ม Schedule เรียบร้อยแล้ว";
            
        } catch (Exception $e) {
            $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
        
    } elseif ($action === 'toggle_schedule') {
        // เปิด/ปิด Schedule
        try {
            $db = getDB();
            $stmt = $db->prepare("
                UPDATE auto_reset_schedules 
                SET is_active = NOT is_active 
                WHERE schedule_id = ?
            ");
            $stmt->execute([$_POST['schedule_id']]);
            
            $success_message = "อัปเดตสถานะ Schedule เรียบร้อยแล้ว";
            
        } catch (Exception $e) {
            $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
        
    } elseif ($action === 'delete_schedule') {
        // ลบ Schedule
        try {
            $db = getDB();
            $stmt = $db->prepare("DELETE FROM auto_reset_schedules WHERE schedule_id = ?");
            $stmt->execute([$_POST['schedule_id']]);
            
            $success_message = "ลบ Schedule เรียบร้อยแล้ว";
            
        } catch (Exception $e) {
            $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}

try {
    $db = getDB();
    
    // ดึงการตั้งค่าปัจจุบัน
    $settings = [
        'auto_reset_enabled' => getSetting('auto_reset_enabled', '0'),
        'auto_reset_notification' => getSetting('auto_reset_notification', '1'),
        'auto_reset_backup_before' => getSetting('auto_reset_backup_before', '1'),
        'auto_reset_max_retries' => getSetting('auto_reset_max_retries', '3')
    ];
    
    // ดึงรายการ Schedules
    $stmt = $db->prepare("
        SELECT ars.*, 
               CASE 
                   WHEN ars.reset_type = 'by_type' THEN qt.type_name
                   WHEN ars.reset_type = 'by_service_point' THEN sp.point_name
                   ELSE 'ทุกประเภท'
               END as target_name,
               su.full_name as created_by_name
        FROM auto_reset_schedules ars
        LEFT JOIN queue_types qt ON ars.target_id = qt.queue_type_id AND ars.reset_type = 'by_type'
        LEFT JOIN service_points sp ON ars.target_id = sp.service_point_id AND ars.reset_type = 'by_service_point'
        LEFT JOIN staff_users su ON ars.created_by = su.staff_id
        ORDER BY ars.schedule_time ASC
    ");
    $stmt->execute();
    $schedules = $stmt->fetchAll();
    
    // ดึงข้อมูลสำหรับ dropdown
    $stmt = $db->prepare("SELECT queue_type_id, type_name FROM queue_types WHERE is_active = 1 ORDER BY type_name");
    $stmt->execute();
    $queueTypes = $stmt->fetchAll();
    
    $stmt = $db->prepare("SELECT service_point_id, point_name FROM service_points WHERE is_active = 1 ORDER BY point_name");
    $stmt->execute();
    $servicePoints = $stmt->fetchAll();
    
    // ดึงสถิติการ Reset
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_runs,
            SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_runs,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_runs,
            AVG(execution_time) as avg_execution_time
        FROM auto_reset_logs 
        WHERE executed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $stats = $stmt->fetch();
    
} catch (Exception $e) {
    $schedules = [];
    $queueTypes = [];
    $servicePoints = [];
    $stats = ['total_runs' => 0, 'success_runs' => 0, 'failed_runs' => 0, 'avg_execution_time' => 0];
    $error_message = $e->getMessage();
}

$dayNames = [
    1 => 'จันทร์', 2 => 'อังคาร', 3 => 'พุธ', 4 => 'พฤหัสบดี',
    5 => 'ศุกร์', 6 => 'เสาร์', 7 => 'อาทิตย์'
];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การตั้งค่า Auto Reset - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    
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
        
        .schedule-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            border-left: 4px solid #28a745;
        }
        
        .schedule-card.inactive {
            border-left-color: #dc3545;
            opacity: 0.7;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .schedule-time {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
        }
        
        .schedule-days {
            display: flex;
            gap: 0.25rem;
            flex-wrap: wrap;
        }
        
        .day-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.75rem;
        }
        
        .day-badge.active {
            background-color: #007bff;
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
        
        .status-success {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .btn-toggle {
            background: none;
            border: 1px solid #dee2e6;
            color: #6c757d;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            transition: all 0.3s;
        }
        
        .btn-toggle:hover {
            background-color: #f8f9fa;
            border-color: #adb5bd;
        }
        
        .form-check-input:checked {
            background-color: #007bff;
            border-color: #007bff;
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
                            <h2>การตั้งค่า Auto Reset</h2>
                            <p class="text-muted">จัดการการ Reset คิวอัตโนมัติตามเวลาที่กำหนด</p>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                            <i class="fas fa-plus me-2"></i>เพิ่ม Schedule
                        </button>
                    </div>
                    
                    <!-- Alerts -->
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Statistics -->
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $stats['total_runs'] ?? 0; ?></div>
                            <div>การรันทั้งหมด (30 วัน)</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $stats['success_runs'] ?? 0; ?></div>
                            <div>สำเร็จ</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $stats['failed_runs'] ?? 0; ?></div>
                            <div>ล้มเหลว</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo number_format($stats['avg_execution_time'] ?? 0, 3); ?>s</div>
                            <div>เวลาเฉลี่ย</div>
                        </div>
                    </div>
                    
                    <!-- General Settings -->
                    <div class="content-card">
                        <h5 class="mb-4">
                            <i class="fas fa-cog me-2"></i>การตั้งค่าทั่วไป
                        </h5>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="save_settings">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="auto_reset_enabled" 
                                               name="auto_reset_enabled" value="1" 
                                               <?php echo $settings['auto_reset_enabled'] === '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="auto_reset_enabled">
                                            <strong>เปิดใช้งาน Auto Reset</strong>
                                        </label>
                                    </div>
                                    
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="auto_reset_notification" 
                                               name="auto_reset_notification" value="1" 
                                               <?php echo $settings['auto_reset_notification'] === '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="auto_reset_notification">
                                            ส่งการแจ้งเตือน
                                        </label>
                                    </div>
                                    
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="auto_reset_backup_before" 
                                               name="auto_reset_backup_before" value="1" 
                                               <?php echo $settings['auto_reset_backup_before'] === '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="auto_reset_backup_before">
                                            สำรองข้อมูลก่อน Reset
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="auto_reset_max_retries" class="form-label">จำนวนครั้งสูงสุดในการลองใหม่</label>
                                        <input type="number" class="form-control" id="auto_reset_max_retries" 
                                               name="auto_reset_max_retries" min="1" max="10" 
                                               value="<?php echo $settings['auto_reset_max_retries']; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>บันทึกการตั้งค่า
                            </button>
                        </form>
                    </div>
                    
                    <!-- Schedules List -->
                    <div class="content-card">
                        <h5 class="mb-4">
                            <i class="fas fa-calendar-alt me-2"></i>รายการ Schedule
                        </h5>
                        
                        <?php if (empty($schedules)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">ยังไม่มี Schedule</h6>
                                <p class="text-muted">เพิ่ม Schedule แรกของคุณเพื่อเริ่มใช้งาน Auto Reset</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($schedules as $schedule): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="schedule-card <?php echo $schedule['is_active'] ? '' : 'inactive'; ?>">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($schedule['schedule_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($schedule['target_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small>
                                                </div>
                                                <span class="status-badge <?php echo $schedule['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                                    <?php echo $schedule['is_active'] ? 'เปิดใช้งาน' : 'ปิดใช้งาน'; ?>
                                                </span>
                                            </div>
                                            
                                            <div class="text-center mb-3">
                                                <div class="schedule-time"><?php echo date('H:i', strtotime($schedule['schedule_time'])); ?></div>
                                                <small class="text-muted">เวลา Reset</small>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <small class="text-muted d-block mb-1">วันที่ทำงาน:</small>
                                                <div class="schedule-days">
                                                    <?php 
                                                    $activeDays = explode(',', $schedule['schedule_days']);
                                                    for ($i = 1; $i <= 7; $i++): 
                                                    ?>
                                                        <span class="day-badge <?php echo in_array($i, $activeDays) ? 'active' : ''; ?>">
                                                            <?php echo substr($dayNames[$i], 0, 1); ?>
                                                        </span>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            
                                            <?php if ($schedule['last_run_date']): ?>
                                                <div class="mb-3">
                                                    <small class="text-muted">รันล่าสุด: <?php echo date('d/m/Y', strtotime($schedule['last_run_date'])); ?></small>
                                                    <?php if ($schedule['last_run_status']): ?>
                                                        <span class="status-badge status-<?php echo $schedule['last_run_status']; ?> ms-2">
                                                            <?php echo $schedule['last_run_status'] === 'success' ? 'สำเร็จ' : 'ล้มเหลว'; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex gap-2">
                                                <form method="POST" class="flex-fill">
                                                    <input type="hidden" name="action" value="toggle_schedule">
                                                    <input type="hidden" name="schedule_id" value="<?php echo $schedule['schedule_id']; ?>">
                                                    <button type="submit" class="btn btn-toggle btn-sm w-100">
                                                        <i class="fas fa-power-off me-1"></i>
                                                        <?php echo $schedule['is_active'] ? 'ปิด' : 'เปิด'; ?>
                                                    </button>
                                                </form>
                                                
                                                <form method="POST" onsubmit="return confirm('ต้องการลบ Schedule นี้หรือไม่?')">
                                                    <input type="hidden" name="action" value="delete_schedule">
                                                    <input type="hidden" name="schedule_id" value="<?php echo $schedule['schedule_id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Schedule Modal -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>เพิ่ม Auto Reset Schedule
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="add_schedule">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="schedule_name" class="form-label">ชื่อ Schedule</label>
                                    <input type="text" class="form-control" id="schedule_name" name="schedule_name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="reset_type" class="form-label">ประเภทการ Reset</label>
                                    <select class="form-select" id="reset_type" name="reset_type" required onchange="toggleTargetSelect()">
                                        <option value="all">Reset ทุกประเภท</option>
                                        <option value="by_type">Reset ตามประเภทคิว</option>
                                        <option value="by_service_point">Reset ตามจุดบริการ</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3" id="target_select_container" style="display: none;">
                                    <label for="target_id" class="form-label">เลือกเป้าหมาย</label>
                                    <select class="form-select" id="target_id" name="target_id">
                                        <optgroup label="ประเภทคิว" id="queue_types_group">
                                            <?php foreach ($queueTypes as $type): ?>
                                                  <option value="<?php echo $type['queue_type_id']; ?>" data-type="by_type">
                                                      <?php echo htmlspecialchars($type['type_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                                  </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <optgroup label="จุดบริการ" id="service_points_group">
                                            <?php foreach ($servicePoints as $point): ?>
                                                  <option value="<?php echo $point['service_point_id']; ?>" data-type="by_service_point">
                                                      <?php echo htmlspecialchars($point['point_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                                  </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="schedule_time" class="form-label">เวลา Reset</label>
                                    <input type="time" class="form-control" id="schedule_time" name="schedule_time" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">วันที่ทำงาน</label>
                                    <div class="d-flex flex-column gap-2">
                                        <?php foreach ($dayNames as $dayNum => $dayName): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="day_<?php echo $dayNum; ?>" 
                                                       name="schedule_days[]" 
                                                       value="<?php echo $dayNum; ?>"
                                                       <?php echo in_array($dayNum, [1,2,3,4,5]) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="day_<?php echo $dayNum; ?>">
                                                    <?php echo $dayName; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                                    <label class="form-check-label" for="is_active">
                                        เปิดใช้งานทันที
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>บันทึก Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleTargetSelect() {
            const resetType = document.getElementById('reset_type').value;
            const container = document.getElementById('target_select_container');
            const targetSelect = document.getElementById('target_id');
            
            if (resetType === 'all') {
                container.style.display = 'none';
                targetSelect.required = false;
            } else {
                container.style.display = 'block';
                targetSelect.required = true;
                
                // แสดงเฉพาะ options ที่เกี่ยวข้อง
                const options = targetSelect.querySelectorAll('option');
                options.forEach(option => {
                    if (option.dataset.type) {
                        option.style.display = option.dataset.type === resetType ? 'block' : 'none';
                    }
                });
                
                // เลือก option แรกที่เหมาะสม
                const firstValidOption = targetSelect.querySelector(`option[data-type="${resetType}"]`);
                if (firstValidOption) {
                    targetSelect.value = firstValidOption.value;
                }
            }
        }
        
        // Test Auto Reset
        function testAutoReset() {
            if (!confirm('ต้องการทดสอบ Auto Reset หรือไม่?')) return;
            
            fetch('../api/auto_reset_queue.php', {
                method: 'GET'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('ทดสอบ Auto Reset สำเร็จ\n' + JSON.stringify(data.summary, null, 2));
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            });
        }
        
        // เพิ่มปุ่มทดสอบ
        document.addEventListener('DOMContentLoaded', function() {
            const header = document.querySelector('.d-flex.justify-content-between.align-items-center');
            const testBtn = document.createElement('button');
            testBtn.className = 'btn btn-outline-primary ms-2';
            testBtn.innerHTML = '<i class="fas fa-play me-2"></i>ทดสอบ';
            testBtn.onclick = testAutoReset;
            header.querySelector('div:last-child').appendChild(testBtn);
        });
    </script>
</body>
</html>
