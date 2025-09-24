<?php
require_once '../config/config.php';
require_once '../api/notification_center.php';
requireLogin();

if (!hasPermission('manage_settings')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_notification') {
        $type = $_POST['notification_type'] ?? '';
        $title = $_POST['title'] ?? '';
        $message = $_POST['message'] ?? '';
        $priority = $_POST['priority'] ?? 'normal';
        $recipientType = $_POST['recipient_type'] ?? 'all';
        $recipientId = $_POST['recipient_id'] ?? null;
        $recipientRole = $_POST['recipient_role'] ?? null;
        
        $options = [
            'priority' => $priority,
            'icon' => $_POST['icon'] ?? 'bell',
            'link' => $_POST['link'] ?? null,
            'expires_at' => $_POST['expires_at'] ? date('Y-m-d H:i:s', strtotime($_POST['expires_at'])) : null
        ];
        
        if ($recipientType === 'user') {
            $options['recipient_id'] = $recipientId;
        } elseif ($recipientType === 'role') {
            $options['recipient_role'] = $recipientRole;
        }
        
        $result = createNotification($type, $title, $message, $options);
        
        if ($result['success']) {
            $success_message = "ส่งการแจ้งเตือนสำเร็จ: {$result['recipient_count']} คน";
        } else {
            $error_message = "เกิดข้อผิดพลาด: {$result['message']}";
        }
    } elseif ($action === 'save_notification_type') {
        try {
            $db = getDB();
            $typeCode = $_POST['type_code'] ?? '';
            $typeName = $_POST['type_name'] ?? '';
            $description = $_POST['description'] ?? '';
            $icon = $_POST['icon'] ?? 'bell';
            $defaultPriority = $_POST['default_priority'] ?? 'normal';
            $defaultSound = $_POST['default_sound'] ?? 'notification.wav';
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            // ตรวจสอบว่ามีประเภทนี้อยู่แล้วหรือไม่
            $stmt = $db->prepare("SELECT * FROM notification_types WHERE type_code = ?");
            $stmt->execute([$typeCode]);
            $existingType = $stmt->fetch();
            
            if ($existingType) {
                // อัปเดตประเภทที่มีอยู่
                $stmt = $db->prepare("
                    UPDATE notification_types
                    SET type_name = ?, description = ?, icon = ?, default_priority = ?, default_sound = ?, is_active = ?
                    WHERE type_code = ?
                ");
                $stmt->execute([$typeName, $description, $icon, $defaultPriority, $defaultSound, $isActive, $typeCode]);
                $success_message = "อัปเดตประเภทการแจ้งเตือนเรียบร้อยแล้ว";
            } else {
                // สร้างประเภทใหม่
                $stmt = $db->prepare("
                    INSERT INTO notification_types
                    (type_code, type_name, description, icon, default_priority, default_sound, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$typeCode, $typeName, $description, $icon, $defaultPriority, $defaultSound, $isActive]);
                $success_message = "สร้างประเภทการแจ้งเตือนใหม่เรียบร้อยแล้ว";
            }
        } catch (Exception $e) {
            $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    } elseif ($action === 'delete_notification_type') {
        try {
            $db = getDB();
            $typeCode = $_POST['type_code'] ?? '';
            
            // ตรวจสอบว่าเป็นประเภทระบบหรือไม่
            $stmt = $db->prepare("SELECT is_system FROM notification_types WHERE type_code = ?");
            $stmt->execute([$typeCode]);
            $type = $stmt->fetch();
            
            if ($type && $type['is_system']) {
                $error_message = "ไม่สามารถลบประเภทการแจ้งเตือนของระบบได้";
            } else {
                $stmt = $db->prepare("DELETE FROM notification_types WHERE type_code = ? AND is_system = 0");
                $stmt->execute([$typeCode]);
                
                if ($stmt->rowCount() > 0) {
                    $success_message = "ลบประเภทการแจ้งเตือนเรียบร้อยแล้ว";
                } else {
                    $error_message = "ไม่สามารถลบประเภทการแจ้งเตือนได้";
                }
            }
        } catch (Exception $e) {
            $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    } elseif ($action === 'clean_notifications') {
        try {
            $db = getDB();
            $days = (int)($_POST['days'] ?? 30);
            
            if ($days < 1) {
                $error_message = "จำนวนวันต้องมากกว่า 0";
            } else {
                $stmt = $db->prepare("
                    DELETE FROM notifications
                    WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                ");
                $stmt->execute([$days]);
                
                $count = $stmt->rowCount();
                $success_message = "ลบการแจ้งเตือนเก่ากว่า $days วันเรียบร้อยแล้ว ($count รายการ)";
            }
        } catch (Exception $e) {
            $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}

try {
    $db = getDB();
    
    // ดึงประเภทการแจ้งเตือน
    $stmt = $db->prepare("
        SELECT * FROM notification_types
        ORDER BY is_system DESC, type_name ASC
    ");
    $stmt->execute();
    $notificationTypes = $stmt->fetchAll();
    
    // ดึงบทบาทผู้ใช้
    $stmt = $db->prepare("
        SELECT * FROM roles
        ORDER BY role_name ASC
    ");
    $stmt->execute();
    $roles = $stmt->fetchAll();
    
    // ดึงผู้ใช้
    $stmt = $db->prepare("
        SELECT * FROM staff_users
        WHERE is_active = 1
        ORDER BY full_name ASC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    // ดึงสถิติการแจ้งเตือน
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_count,
            SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count,
            SUM(CASE WHEN is_dismissed = 1 THEN 1 ELSE 0 END) as dismissed_count,
            COUNT(DISTINCT recipient_id) as recipient_count
        FROM notifications
    ");
    $stmt->execute();
    $notificationStats = $stmt->fetch();
    
    // ดึงสถิติการส่งการแจ้งเตือน
    $stmt = $db->prepare("
        SELECT 
            channel,
            COUNT(*) as total_count,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_count,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
            SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count
        FROM notification_deliveries
        GROUP BY channel
    ");
    $stmt->execute();
    $deliveryStats = $stmt->fetchAll();
    
    // ดึงการแจ้งเตือนล่าสุด
    $stmt = $db->prepare("
        SELECT n.*, nt.type_name, su.full_name as recipient_name
        FROM notifications n
        LEFT JOIN notification_types nt ON n.notification_type = nt.type_code
        LEFT JOIN staff_users su ON n.recipient_id = su.staff_id
        ORDER BY n.created_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $recentNotifications = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
}

// Page title
$pageTitle = 'ศูนย์การแจ้งเตือน';
include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <h1 class="h3 mb-4">ศูนย์การแจ้งเตือน</h1>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">สถิติการแจ้งเตือน</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">การแจ้งเตือนทั้งหมด</h5>
                                    <h2 class="display-4"><?php echo number_format($notificationStats['total_count']); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body">
                                    <h5 class="card-title">ยังไม่อ่าน</h5>
                                    <h2 class="display-4"><?php echo number_format($notificationStats['unread_count']); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">ผู้รับทั้งหมด</h5>
                                    <h2 class="display-4"><?php echo number_format($notificationStats['recipient_count']); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">ปิดการแจ้งเตือน</h5>
                                    <h2 class="display-4"><?php echo number_format($notificationStats['dismissed_count']); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5>สถิติการส่งการแจ้งเตือน</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ช่องทาง</th>
                                            <th>ทั้งหมด</th>
                                            <th>ส่งแล้ว</th>
                                            <th>ส่งถึงแล้ว</th>
                                            <th>อ่านแล้ว</th>
                                            <th>ล้มเหลว</th>
                                            <th>อัตราความสำเร็จ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($deliveryStats as $stat): ?>
                                            <tr>
                                                <td>
                                                    <?php 
                                                    switch ($stat['channel']) {
                                                        case 'browser': echo 'เบราว์เซอร์'; break;
                                                        case 'email': echo 'อีเมล'; break;
                                                        case 'line': echo 'LINE'; break;
                                                        case 'sms': echo 'SMS'; break;
                                                        default: echo $stat['channel']; break;
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo number_format($stat['total_count']); ?></td>
                                                <td><?php echo number_format($stat['sent_count']); ?></td>
                                                <td><?php echo number_format($stat['delivered_count']); ?></td>
                                                <td><?php echo number_format($stat['read_count']); ?></td>
                                                <td><?php echo number_format($stat['failed_count']); ?></td>
                                                <td>
                                                    <?php 
                                                    $successRate = $stat['total_count'] > 0 ? 
                                                        round((($stat['sent_count'] + $stat['delivered_count'] + $stat['read_count']) / $stat['total_count']) * 100, 2) : 0;
                                                    echo $successRate . '%';
                                                    ?>
                                                </td>
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
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">ส่งการแจ้งเตือน</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="send_notification">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="notification_type" class="form-label">ประเภทการแจ้งเตือน</label>
                            <select class="form-select" id="notification_type" name="notification_type" required>
                                <?php foreach ($notificationTypes as $type): ?>
                                    <?php if ($type['is_active']): ?>
                                        <option value="<?php echo $type['type_code']; ?>"><?php echo $type['type_name']; ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">หัวข้อ</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">ข้อความ</label>
                            <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="priority" class="form-label">ความสำคัญ</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="low">ต่ำ</option>
                                <option value="normal" selected>ปกติ</option>
                                <option value="high">สูง</option>
                                <option value="urgent">ด่วน</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="icon" class="form-label">ไอคอน</label>
                            <input type="text" class="form-control" id="icon" name="icon" value="bell">
                            <small class="form-text text-muted">ชื่อไอคอนจาก Font Awesome เช่น bell, info-circle, exclamation-triangle</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="link" class="form-label">ลิงก์ (ถ้ามี)</label>
                            <input type="text" class="form-control" id="link" name="link">
                        </div>
                        
                        <div class="mb-3">
                            <label for="expires_at" class="form-label">วันหมดอายุ (ถ้ามี)</label>
                            <input type="datetime-local" class="form-control" id="expires_at" name="expires_at">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">ผู้รับ</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="recipient_type" id="recipient_all" value="all" checked>
                                <label class="form-check-label" for="recipient_all">
                                    ทุกคน
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="recipient_type" id="recipient_role" value="role">
                                <label class="form-check-label" for="recipient_role">
                                    บทบาท
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="recipient_type" id="recipient_user" value="user">
                                <label class="form-check-label" for="recipient_user">
                                    ผู้ใช้เฉพาะราย
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3 recipient-role" style="display: none;">
                            <label for="recipient_role_select" class="form-label">เลือกบทบาท</label>
                            <select class="form-select" id="recipient_role_select" name="recipient_role">
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_name']; ?>"><?php echo $role['role_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3 recipient-user" style="display: none;">
                            <label for="recipient_id" class="form-label">เลือกผู้ใช้</label>
                            <select class="form-select" id="recipient_id" name="recipient_id">
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['staff_id']; ?>"><?php echo $user['full_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">ส่งการแจ้งเตือน</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">จัดการประเภทการแจ้งเตือน</h5>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addNotificationTypeModal">
                        เพิ่มประเภทใหม่
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>รหัส</th>
                                    <th>ชื่อ</th>
                                    <th>ไอคอน</th>
                                    <th>ความสำคัญ</th>
                                    <th>สถานะ</th>
                                    <th>การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($notificationTypes as $type): ?>
                                    <tr>
                                        <td><?php echo $type['type_code']; ?></td>
                                        <td><?php echo $type['type_name']; ?></td>
                                        <td><i class="fas fa-<?php echo $type['icon']; ?>"></i> <?php echo $type['icon']; ?></td>
                                        <td>
                                            <?php 
                                            switch ($type['default_priority']) {
                                                case 'low': echo '<span class="badge bg-secondary">ต่ำ</span>'; break;
                                                case 'normal': echo '<span class="badge bg-primary">ปกติ</span>'; break;
                                                case 'high': echo '<span class="badge bg-warning">สูง</span>'; break;
                                                case 'urgent': echo '<span class="badge bg-danger">ด่วน</span>'; break;
                                                default: echo $type['default_priority']; break;
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($type['is_active']): ?>
                                                <span class="badge bg-success">เปิดใช้งาน</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">ปิดใช้งาน</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info edit-type" 
                                                data-type-code="<?php echo $type['type_code']; ?>"
                                                data-type-name="<?php echo $type['type_name']; ?>"
                                                data-description="<?php echo $type['description']; ?>"
                                                data-icon="<?php echo $type['icon']; ?>"
                                                data-priority="<?php echo $type['default_priority']; ?>"
                                                data-sound="<?php echo $type['default_sound']; ?>"
                                                data-active="<?php echo $type['is_active']; ?>"
                                                data-system="<?php echo $type['is_system']; ?>"
                                            >
                                                แก้ไข
                                            </button>
                                            
                                            <?php if (!$type['is_system']): ?>
                                                <button type="button" class="btn btn-sm btn-danger delete-type" 
                                                    data-type-code="<?php echo $type['type_code']; ?>"
                                                    data-type-name="<?php echo $type['type_name']; ?>"
                                                >
                                                    ลบ
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">ทำความสะอาดการแจ้งเตือน</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบการแจ้งเตือนเก่า?');">
                        <input type="hidden" name="action" value="clean_notifications">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="days" class="form-label">ลบการแจ้งเตือนที่เก่ากว่า</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="days" name="days" value="30" min="1">
                                <span class="input-group-text">วัน</span>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-warning">ทำความสะอาด</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">การแจ้งเตือนล่าสุด</h5>
                    <a href="notification_logs.php" class="btn btn-sm btn-secondary">ดูทั้งหมด</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ประเภท</th>
                                    <th>หัวข้อ</th>
                                    <th>ผู้รับ</th>
                                    <th>สถานะ</th>
                                    <th>เวลา</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentNotifications as $notification): ?>
                                    <tr>
                                        <td><?php echo $notification['notification_id']; ?></td>
                                        <td><?php echo $notification['type_name'] ?? $notification['notification_type']; ?></td>
                                        <td><?php echo $notification['title']; ?></td>
                                        <td><?php echo $notification['recipient_name'] ?? 'ไม่ระบุ'; ?></td>
                                        <td>
                                            <?php if ($notification['is_read']): ?>
                                                <span class="badge bg-success">อ่านแล้ว</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">ยังไม่อ่าน</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($notification['is_dismissed']): ?>
                                                <span class="badge bg-secondary">ปิดแล้ว</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?></td>
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

<!-- Modal เพิ่ม/แก้ไขประเภทการแจ้งเตือน -->
<div class="modal fade" id="addNotificationTypeModal" tabindex="-1" aria-labelledby="addNotificationTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addNotificationTypeModalLabel">เพิ่มประเภทการแจ้งเตือน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="notificationTypeForm" method="post" action="">
                    <input type="hidden" name="action" value="save_notification_type">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="type_code" class="form-label">รหัสประเภท</label>
                        <input type="text" class="form-control" id="type_code" name="type_code" required>
                        <small class="form-text text-muted">ตัวอักษรภาษาอังกฤษและตัวเลขเท่านั้น ไม่มีช่องว่าง</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="type_name" class="form-label">ชื่อประเภท</label>
                        <input type="text" class="form-control" id="type_name" name="type_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">คำอธิบาย</label>
                        <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="icon" class="form-label">ไอคอน</label>
                        <input type="text" class="form-control" id="modal_icon" name="icon" value="bell">
                        <small class="form-text text-muted">ชื่อไอคอนจาก Font Awesome เช่น bell, info-circle, exclamation-triangle</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="default_priority" class="form-label">ความสำคัญเริ่มต้น</label>
                        <select class="form-select" id="default_priority" name="default_priority">
                            <option value="low">ต่ำ</option>
                            <option value="normal" selected>ปกติ</option>
                            <option value="high">สูง</option>
                            <option value="urgent">ด่วน</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="default_sound" class="form-label">เสียงเริ่มต้น</label>
                        <input type="text" class="form-control" id="default_sound" name="default_sound" value="notification.wav">
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                        <label class="form-check-label" for="is_active">เปิดใช้งาน</label>
                    </div>
                    
                    <div id="system_warning" class="alert alert-warning" style="display: none;">
                        คุณกำลังแก้ไขประเภทการแจ้งเตือนของระบบ การเปลี่ยนแปลงบางอย่างอาจส่งผลต่อการทำงานของระบบ
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('notificationTypeForm').submit();">บันทึก</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal ลบประเภทการแจ้งเตือน -->
<div class="modal fade" id="deleteNotificationTypeModal" tabindex="-1" aria-labelledby="deleteNotificationTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteNotificationTypeModalLabel">ลบประเภทการแจ้งเตือน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>คุณแน่ใจหรือไม่ที่จะลบประเภทการแจ้งเตือน <span id="delete_type_name"></span>?</p>
                <p class="text-danger">การลบประเภทการแจ้งเตือนจะลบการแจ้งเตือนทั้งหมดที่เกี่ยวข้องด้วย</p>
                
                <form id="deleteNotificationTypeForm" method="post" action="">
                    <input type="hidden" name="action" value="delete_notification_type">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" id="delete_type_code" name="type_code" value="">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-danger" onclick="document.getElementById('deleteNotificationTypeForm').submit();">ลบ</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // แสดง/ซ่อนตัวเลือกผู้รับ
    document.querySelectorAll('input[name="recipient_type"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.querySelector('.recipient-role').style.display = 'none';
            document.querySelector('.recipient-user').style.display = 'none';
            
            if (this.value === 'role') {
                document.querySelector('.recipient-role').style.display = 'block';
            } else if (this.value === 'user') {
                document.querySelector('.recipient-user').style.display = 'block';
            }
        });
    });
    
    // แก้ไขประเภทการแจ้งเตือน
    document.querySelectorAll('.edit-type').forEach(function(button) {
        button.addEventListener('click', function() {
            const modal = document.getElementById('addNotificationTypeModal');
            const modalTitle = modal.querySelector('.modal-title');
            const form = document.getElementById('notificationTypeForm');
            const systemWarning = document.getElementById('system_warning');
            
            modalTitle.textContent = 'แก้ไขประเภทการแจ้งเตือน';
            
            document.getElementById('type_code').value = this.dataset.typeCode;
            document.getElementById('type_code').readOnly = true;
            document.getElementById('type_name').value = this.dataset.typeName;
            document.getElementById('description').value = this.dataset.description;
            document.getElementById('modal_icon').value = this.dataset.icon;
            document.getElementById('default_priority').value = this.dataset.priority;
            document.getElementById('default_sound').value = this.dataset.sound;
            document.getElementById('is_active').checked = this.dataset.active === '1';
            
            if (this.dataset.system === '1') {
                systemWarning.style.display = 'block';
            } else {
                systemWarning.style.display = 'none';
            }
            
            const modal_instance = new bootstrap.Modal(modal);
            modal_instance.show();
        });
    });
    
    // ลบประเภทการแจ้งเตือน
    document.querySelectorAll('.delete-type').forEach(function(button) {
        button.addEventListener('click', function() {
            const modal = document.getElementById('deleteNotificationTypeModal');
            
            document.getElementById('delete_type_code').value = this.dataset.typeCode;
            document.getElementById('delete_type_name').textContent = this.dataset.typeName;
            
            const modal_instance = new bootstrap.Modal(modal);
            modal_instance.show();
        });
    });
    
    // รีเซ็ตฟอร์มเมื่อเปิด Modal เพิ่มประเภทใหม่
    document.querySelector('[data-bs-target="#addNotificationTypeModal"]').addEventListener('click', function() {
        const modal = document.getElementById('addNotificationTypeModal');
        const modalTitle = modal.querySelector('.modal-title');
        const form = document.getElementById('notificationTypeForm');
        const systemWarning = document.getElementById('system_warning');
        
        modalTitle.textContent = 'เพิ่มประเภทการแจ้งเตือน';
        
        document.getElementById('type_code').value = '';
        document.getElementById('type_code').readOnly = false;
        document.getElementById('type_name').value = '';
        document.getElementById('description').value = '';
        document.getElementById('modal_icon').value = 'bell';
        document.getElementById('default_priority').value = 'normal';
        document.getElementById('default_sound').value = 'notification.wav';
        document.getElementById('is_active').checked = true;
        
        systemWarning.style.display = 'none';
    });
});
</script>

<?php include '../includes/footer.php'; ?>
