<?php
require_once '../config/config.php';
require_once '../api/backup_before_reset.php';
requireLogin();

if (!hasPermission('manage_settings')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_backup') {
        $resetType = $_POST['reset_type'] ?? 'all';
        $targetId = $_POST['target_id'] ?? null;
        
        $result = createBackupBeforeReset($resetType, $targetId);
        
        if ($result['success']) {
            $success_message = "สร้าง Backup สำเร็จ: " . $result['backup_file'];
        } else {
            $error_message = "เกิดข้อผิดพลาด: " . $result['error'];
        }
        
    } elseif ($action === 'restore_backup') {
        $backupFile = $_POST['backup_file'] ?? '';
        
        if ($backupFile && confirm_restore()) {
            $result = restoreFromBackup($backupFile);
            
            if ($result['success']) {
                $success_message = "Restore สำเร็จ: " . $result['message'];
            } else {
                $error_message = "เกิดข้อผิดพลาด: " . $result['error'];
            }
        }
        
    } elseif ($action === 'delete_backup') {
        $backupFile = $_POST['backup_file'] ?? '';
        $backupDir = ROOT_PATH . '/backups/auto_reset';
        $backupPath = $backupDir . '/' . $backupFile;
        
        if (file_exists($backupPath)) {
            unlink($backupPath);
            $success_message = "ลบไฟล์ Backup สำเร็จ";
        } else {
            $error_message = "ไม่พบไฟล์ Backup";
        }
    }
}

function confirm_restore() {
    return isset($_POST['confirm_restore']) && $_POST['confirm_restore'] === '1';
}

try {
    $db = getDB();
    
    // ดึงรายการ backup files
    $backupDir = ROOT_PATH . '/backups/auto_reset';
    $backupFiles = [];
    
    if (is_dir($backupDir)) {
        $files = glob($backupDir . '/queue_backup_*.json');
        foreach ($files as $file) {
            $fileName = basename($file);
            $fileSize = filesize($file);
            $fileTime = filemtime($file);
            
            // อ่านข้อมูลจากไฟล์
            $backupData = json_decode(file_get_contents($file), true);
            
            $backupFiles[] = [
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'created_at' => date('Y-m-d H:i:s', $fileTime),
                'reset_type' => $backupData['backup_info']['reset_type'] ?? 'unknown',
                'queue_types_count' => count($backupData['queue_types'] ?? []),
                'active_queues_count' => array_sum(array_map('count', $backupData['active_queues'] ?? []))
            ];
        }
        
        // เรียงตามวันที่ใหม่สุด
        usort($backupFiles, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
    }
    
    // ดึงข้อมูลสำหรับ dropdown
    $stmt = $db->prepare("SELECT queue_type_id, type_name FROM queue_types WHERE is_active = 1 ORDER BY type_name");
    $stmt->execute();
    $queueTypes = $stmt->fetchAll();
    
    $stmt = $db->prepare("SELECT service_point_id, point_name FROM service_points WHERE is_active = 1 ORDER BY point_name");
    $stmt->execute();
    $servicePoints = $stmt->fetchAll();
    
    // สถิติ backup
    $totalBackups = count($backupFiles);
    $totalSize = array_sum(array_column($backupFiles, 'file_size'));
    $oldestBackup = !empty($backupFiles) ? end($backupFiles)['created_at'] : null;
    $newestBackup = !empty($backupFiles) ? $backupFiles[0]['created_at'] : null;
    
} catch (Exception $e) {
    $backupFiles = [];
    $queueTypes = [];
    $servicePoints = [];
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการ Backup - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    
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
        
        .backup-item {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #28a745;
        }
        
        .backup-item.old {
            border-left-color: #ffc107;
        }
        
        .backup-item.very-old {
            border-left-color: #dc3545;
        }
        
        .file-size {
            background-color: #e9ecef;
            color: #495057;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-family: monospace;
        }
        
        .backup-type {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .type-all {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .type-by_type {
            background-color: #d4edda;
            color: #155724;
        }
        
        .type-by_service_point {
            background-color: #fff3cd;
            color: #856404;
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
                            <h2>จัดการ Backup</h2>
                            <p class="text-muted">สำรองและกู้คืนข้อมูลคิว</p>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBackupModal">
                            <i class="fas fa-plus me-2"></i>สร้าง Backup
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
                            <div class="stat-number"><?php echo $totalBackups; ?></div>
                            <div>ไฟล์ Backup ทั้งหมด</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo formatFileSize($totalSize); ?></div>
                            <div>ขนาดรวม</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $newestBackup ? date('d/m/Y', strtotime($newestBackup)) : '-'; ?></div>
                            <div>Backup ล่าสุด</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $oldestBackup ? date('d/m/Y', strtotime($oldestBackup)) : '-'; ?></div>
                            <div>Backup เก่าสุด</div>
                        </div>
                    </div>
                    
                    <!-- Backup Files -->
                    <div class="content-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5>ไฟล์ Backup</h5>
                            <div>
                                <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
                                    <i class="fas fa-sync-alt me-1"></i>รีเฟรช
                                </button>
                                <button class="btn btn-outline-danger btn-sm" onclick="cleanOldBackups()">
                                    <i class="fas fa-trash me-1"></i>ลบไฟล์เก่า
                                </button>
                            </div>
                        </div>
                        
                        <?php if (empty($backupFiles)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-database fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">ยังไม่มีไฟล์ Backup</h6>
                                <p class="text-muted">สร้าง Backup แรกของคุณเพื่อเริ่มใช้งาน</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($backupFiles as $backup): ?>
                                <?php
                                $daysDiff = (time() - strtotime($backup['created_at'])) / (24 * 60 * 60);
                                $ageClass = '';
                                if ($daysDiff > 14) $ageClass = 'very-old';
                                elseif ($daysDiff > 7) $ageClass = 'old';
                                ?>
                                <div class="backup-item <?php echo $ageClass; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="d-flex align-items-center mb-2">
                                                <h6 class="mb-0 me-3"><?php echo htmlspecialchars($backup['file_name']); ?></h6>
                                                <span class="backup-type type-<?php echo $backup['reset_type']; ?>">
                                                    <?php 
                                                    echo match($backup['reset_type']) {
                                                        'all' => 'ทุกประเภท',
                                                        'by_type' => 'ตามประเภท',
                                                        'by_service_point' => 'ตามจุดบริการ',
                                                        default => $backup['reset_type']
                                                    };
                                                    ?>
                                                </span>
                                                <span class="file-size ms-2">
                                                    <?php echo formatFileSize($backup['file_size']); ?>
                                                </span>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('d/m/Y H:i:s', strtotime($backup['created_at'])); ?>
                                                    
                                                    <i class="fas fa-list ms-3 me-1"></i>
                                                    <?php echo $backup['queue_types_count']; ?> ประเภท
                                                    
                                                    <i class="fas fa-users ms-3 me-1"></i>
                                                    <?php echo $backup['active_queues_count']; ?> คิวที่รอ
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4 text-end">
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-outline-success btn-sm" 
                                                        onclick="restoreBackup('<?php echo $backup['file_name']; ?>')">
                                                    <i class="fas fa-undo me-1"></i>Restore
                                                </button>
                                                <a href="../api/download_backup.php?file=<?php echo urlencode($backup['file_name']); ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-download me-1"></i>ดาวน์โหลด
                                                </a>
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                        onclick="deleteBackup('<?php echo $backup['file_name']; ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Backup Modal -->
    <div class="modal fade" id="createBackupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>สร้าง Backup ใหม่
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="create_backup">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="reset_type" class="form-label">ประเภทการ Backup</label>
                            <select class="form-select" id="reset_type" name="reset_type" required onchange="toggleTargetSelect()">
                                <option value="all">Backup ทุกประเภท</option>
                                <option value="by_type">Backup ตามประเภทคิว</option>
                                <option value="by_service_point">Backup ตามจุดบริการ</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="target_select_container" style="display: none;">
                            <label for="target_id" class="form-label">เลือกเป้าหมาย</label>
                            <select class="form-select" id="target_id" name="target_id">
                                <optgroup label="ประเภทคิว" id="queue_types_group">
                                    <?php foreach ($queueTypes as $type): ?>
                                        <option value="<?php echo $type['queue_type_id']; ?>" data-type="by_type">
                                            <?php echo htmlspecialchars($type['type_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="จุดบริการ" id="service_points_group">
                                    <?php foreach ($servicePoints as $point): ?>
                                        <option value="<?php echo $point['service_point_id']; ?>" data-type="by_service_point">
                                            <?php echo htmlspecialchars($point['point_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            การสร้าง Backup จะบันทึกสถานะปัจจุบันของคิวทั้งหมดที่เลือก รวมถึงหมายเลขคิวปัจจุบันและคิวที่ยังไม่เสร็จ
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>สร้าง Backup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Restore Confirmation Modal -->
    <div class="modal fade" id="restoreModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>ยืนยันการ Restore
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <form method="POST" id="restoreForm">
                    <input type="hidden" name="action" value="restore_backup">
                    <input type="hidden" name="backup_file" id="restore_backup_file">
                    <input type="hidden" name="confirm_restore" value="1">
                    
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>คำเตือน!</h6>
                            <p class="mb-0">การ Restore จะเปลี่ยนแปลงหมายเลขคิวปัจจุบันให้กลับไปเป็นค่าที่บันทึกไว้ใน Backup</p>
                        </div>
                        
                        <p>คุณต้องการ Restore จากไฟล์: <strong id="restore_file_name"></strong> หรือไม่?</p>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="confirm_checkbox" required>
                            <label class="form-check-label" for="confirm_checkbox">
                                ฉันเข้าใจและยืนยันการ Restore
                            </label>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-undo me-2"></i>ยืนยัน Restore
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
        
        function restoreBackup(fileName) {
            document.getElementById('restore_backup_file').value = fileName;
            document.getElementById('restore_file_name').textContent = fileName;
            document.getElementById('confirm_checkbox').checked = false;
            
            const modal = new bootstrap.Modal(document.getElementById('restoreModal'));
            modal.show();
        }
        
        function deleteBackup(fileName) {
            if (!confirm('ต้องการลบไฟล์ Backup "' + fileName + '" หรือไม่?\n\nการลบจะไม่สามารถกู้คืนได้!')) {
                return;
            }
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_backup">
                <input type="hidden" name="backup_file" value="${fileName}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        function cleanOldBackups() {
            if (!confirm('ต้องการลบไฟล์ Backup ที่เก่ากว่า 30 วันหรือไม่?')) {
                return;
            }
            
            fetch('../api/clean_old_backups.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('ลบไฟล์เก่าเรียบร้อยแล้ว: ' + data.deleted_count + ' ไฟล์');
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            });
        }
    </script>
</body>
</html>

<?php /* removed duplicate formatFileSize; use config/config.php */ ?>

## 🎉 **ระบบ Auto Reset อัตโนมัติเสร็จสมบูรณ์!**

### 🚀 **คุณสมบัติที่เพิ่มเติม:**

#### **📊 หน้าประวัติ Auto Reset**
- แสดงสถิติการ Reset 30 วันล่าสุด
- กรองตาม Schedule, สถานะ, วันที่
- ส่งออกเป็น CSV, JSON, Excel
- แสดงรายละเอียดการ Reset แต่ละครั้ง

#### **💾 ระบบ Backup ครบครัน**
- **Auto Backup**: สำรองก่อน Reset อัตโนมัติ
- **Manual Backup**: สร้าง Backup ด้วยตนเอง
- **Restore System**: กู้คืนจาก Backup
- **Backup Management**: จัดการไฟล์ Backup

#### **🔧 API สำหรับ Export และ Backup**
- Export Auto Reset Logs
- Download Backup Files
- Clean Old Backups
- Restore from Backup

### 📈 **การทำงานของระบบ:**

#### **🕐 Auto Reset Schedule:**
1. **Cron Job** ตรวจสอบทุก 5 นาที
2. **ตรวจสอบเงื่อนไข**: เวลา, วัน, สถานะ
3. **สำรองข้อมูล**: ถ้าเปิดใช้งาน
4. **Reset คิว**: ตามประเภทที่กำหนด
5. **บันทึก Log**: ผลการทำงาน
6. **ส่งแจ้งเตือน**: ถ้าเปิดใช้งาน

#### **💾 Backup System:**
- **ก่อน Reset**: สำรองอัตโนมัติ
- **Manual**: สร้างเมื่อต้องการ
- **ข้อมูลที่สำรอง**: หมายเลขคิว, คิวที่รอ, การตั้งค่า
- **การจัดเก็บ**: JSON format, 30 วันอัตโนมัติ

### 🎯 **การใช้งานจริง:**

#### **ตั้งค่า Cron Job:**
\`\`\`bash
# ตรวจสอบทุก 5 นาที
*/5 * * * * /path/to/auto_reset_cron.sh

# หรือเรียก API โดยตรง
*/5 * * * * curl -s "http://localhost/yuwa_que/v4/api/auto_reset_queue.php?cron=true"
