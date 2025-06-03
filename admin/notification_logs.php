<?php
require_once '../config/config.php';
require_once '../api/notification_center.php';
requireLogin();

if (!hasPermission('manage_settings')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

// ตัวกรอง
$filters = [
    'type' => $_GET['type'] ?? '',
    'priority' => $_GET['priority'] ?? '',
    'status' => $_GET['status'] ?? '',
    'recipient' => $_GET['recipient'] ?? '',
    'start_date' => $_GET['start_date'] ?? '',
    'end_date' => $_GET['end_date'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// การเรียงลำดับ
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'desc';

// การแบ่งหน้า
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = ($page - 1) * $limit;

try {
    $db = getDB();
    
    // สร้าง WHERE clause
    $whereClause = "1=1";
    $params = [];
    
    if (!empty($filters['type'])) {
        $whereClause .= " AND n.notification_type = ?";
        $params[] = $filters['type'];
    }
    
    if (!empty($filters['priority'])) {
        $whereClause .= " AND n.priority = ?";
        $params[] = $filters['priority'];
    }
    
    if (!empty($filters['status'])) {
        if ($filters['status'] === 'read') {
            $whereClause .= " AND n.is_read = 1";
        } elseif ($filters['status'] === 'unread') {
            $whereClause .= " AND n.is_read = 0";
        } elseif ($filters['status'] === 'dismissed') {
            $whereClause .= " AND n.is_dismissed = 1";
        }
    }
    
    if (!empty($filters['recipient'])) {
        $whereClause .= " AND n.recipient_id = ?";
        $params[] = $filters['recipient'];
    }
    
    if (!empty($filters['start_date'])) {
        $whereClause .= " AND n.created_at >= ?";
        $params[] = $filters['start_date'] . ' 00:00:00';
    }
    
    if (!empty($filters['end_date'])) {
        $whereClause .= " AND n.created_at <= ?";
        $params[] = $filters['end_date'] . ' 23:59:59';
    }
    
    if (!empty($filters['search'])) {
        $whereClause .= " AND (n.title LIKE ? OR n.message LIKE ? OR su_recipient.full_name LIKE ? OR su_sender.full_name LIKE ?)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // ดึงจำนวนทั้งหมด
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM notifications n
        LEFT JOIN staff_users su_recipient ON n.recipient_id = su_recipient.staff_id
        LEFT JOIN staff_users su_sender ON n.sender_id = su_sender.staff_id
        WHERE $whereClause
    ");
    $stmt->execute($params);
    $totalCount = $stmt->fetch()['total'];
    
    // คำนวณจำนวนหน้า
    $totalPages = ceil($totalCount / $limit);
    
    // ตรวจสอบการเรียงลำดับ
    $allowedSortFields = ['notification_id', 'notification_type', 'title', 'priority', 'recipient_name', 'sender_name', 'created_at', 'is_read', 'is_dismissed'];
    if (!in_array($sort, $allowedSortFields)) {
        $sort = 'created_at';
    }
    
    $allowedOrderValues = ['asc', 'desc'];
    if (!in_array(strtolower($order), $allowedOrderValues)) {
        $order = 'desc';
    }
    
    // ดึงข้อมูลการแจ้งเตือน
    $stmt = $db->prepare("
        SELECT 
            n.*,
            nt.type_name,
            su_recipient.full_name as recipient_name,
            su_sender.full_name as sender_name
        FROM notifications n
        LEFT JOIN notification_types nt ON n.notification_type = nt.type_code
        LEFT JOIN staff_users su_recipient ON n.recipient_id = su_recipient.staff_id
        LEFT JOIN staff_users su_sender ON n.sender_id = su_sender.staff_id
        WHERE $whereClause
        ORDER BY $sort $order
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $notifications = $stmt->fetchAll();
    
    // ดึงประเภทการแจ้งเตือน
    $stmt = $db->prepare("
        SELECT * FROM notification_types
        ORDER BY type_name ASC
    ");
    $stmt->execute();
    $notificationTypes = $stmt->fetchAll();
    
    // ดึงผู้ใช้
    $stmt = $db->prepare("
        SELECT staff_id, full_name FROM staff_users
        ORDER BY full_name ASC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
}

// Page title
$pageTitle = 'บันทึกการแจ้งเตือน';
include '../includes/header.php';
?>

<div class="container-fluid mt-4">
    <h1 class="h3 mb-4">บันทึกการแจ้งเตือน</h1>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">ตัวกรอง</h5>
        </div>
        <div class="card-body">
            <form method="get" action="" id="filter-form">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="type" class="form-label">ประเภท</label>
                        <select class="form-select" id="type" name="type">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($notificationTypes as $type): ?>
                                <option value="<?php echo $type['type_code']; ?>" <?php echo $filters['type'] === $type['type_code'] ? 'selected' : ''; ?>>
                                    <?php echo $type['type_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="priority" class="form-label">ความสำคัญ</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="">ทั้งหมด</option>
                            <option value="low" <?php echo $filters['priority'] === 'low' ? 'selected' : ''; ?>>ต่ำ</option>
                            <option value="normal" <?php echo $filters['priority'] === 'normal' ? 'selected' : ''; ?>>ปกติ</option>
                            <option value="high" <?php echo $filters['priority'] === 'high' ? 'selected' : ''; ?>>สูง</option>
                            <option value="urgent" <?php echo $filters['priority'] === 'urgent' ? 'selected' : ''; ?>>ด่วน</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="status" class="form-label">สถานะ</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">ทั้งหมด</option>
                            <option value="read" <?php echo $filters['status'] === 'read' ? 'selected' : ''; ?>>อ่านแล้ว</option>
                            <option value="unread" <?php echo $filters['status'] === 'unread' ? 'selected' : ''; ?>>ยังไม่อ่าน</option>
                            <option value="dismissed" <?php echo $filters['status'] === 'dismissed' ? 'selected' : ''; ?>>ปิดแล้ว</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="recipient" class="form-label">ผู้รับ</label>
                        <select class="form-select" id="recipient" name="recipient">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['staff_id']; ?>" <?php echo $filters['recipient'] == $user['staff_id'] ? 'selected' : ''; ?>>
                                    <?php echo $user['full_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="start_date" class="form-label">วันที่เริ่มต้น</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $filters['start_date']; ?>">
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="end_date" class="form-label">วันที่สิ้นสุด</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $filters['end_date']; ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="search" class="form-label">ค้นหา</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo $filters['search']; ?>" placeholder="ค้นหาจากหัวข้อ, ข้อความ, ชื่อผู้รับ, ชื่อผู้ส่ง">
                    </div>
                    
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">กรอง</button>
                        <a href="notification_logs.php" class="btn btn-secondary">รีเซ็ต</a>
                        <a href="notification_center.php" class="btn btn-link">กลับไปยังศูนย์การแจ้งเตือน</a>
                        
                        <div class="float-end">
                            <a href="<?php echo $_SERVER['REQUEST_URI'] . (strpos($_SERVER['REQUEST_URI'], '?') !== false ? '&' : '?') . 'export=csv'; ?>" class="btn btn-success">
                                <i class="fas fa-file-csv"></i> ส่งออก CSV
                            </a>
                        </div>
                    </div>
                </div>
                
                <input type="hidden" name="sort" id="sort" value="<?php echo $sort; ?>">
                <input type="hidden" name="order" id="order" value="<?php echo $order; ?>">
                <input type="hidden" name="page" id="page" value="<?php echo $page; ?>">
                <input type="hidden" name="limit" id="limit" value="<?php echo $limit; ?>">
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">รายการการแจ้งเตือน</h5>
            <span>พบ <?php echo number_format($totalCount); ?> รายการ</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>
                                <a href="#" class="sort-link" data-sort="notification_id">
                                    ID
                                    <?php if ($sort === 'notification_id'): ?>
                                        <i class="fas fa-sort-<?php echo $order === 'asc' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="notification_type">
                                    ประเภท
                                    <?php if ($sort === 'notification_type'): ?>
                                        <i class="fas fa-sort-<?php echo $order === 'asc' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="title">
                                    หัวข้อ
                                    <?php if ($sort === 'title'): ?>
                                        <i class="fas fa-sort-<?php echo $order === 'asc' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="priority">
                                    ความสำคัญ
                                    <?php if ($sort === 'priority'): ?>
                                        <i class="fas fa-sort-<?php echo $order === 'asc' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="recipient_name">
                                    ผู้รับ
                                    <?php if ($sort === 'recipient_name'): ?>
                                        <i class="fas fa-sort-<?php echo $order === 'asc' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="sender_name">
                                    ผู้ส่ง
                                    <?php if ($sort === 'sender_name'): ?>
                                        <i class="fas fa-sort-<?php echo $order === 'asc' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="created_at">
                                    เวลา
                                    <?php if ($sort === 'created_at'): ?>
                                        <i class="fas fa-sort-<?php echo $order === 'asc' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="sort-link" data-sort="is_read">
                                    สถานะ
                                    <?php if ($sort === 'is_read'): ?>
                                        <i class="fas fa-sort-<?php echo $order === 'asc' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($notifications)): ?>
                            <tr>
                                <td colspan="9" class="text-center">ไม่พบข้อมูล</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <tr>
                                    <td><?php echo $notification['notification_id']; ?></td>
                                    <td>
                                        <i class="fas fa-<?php echo $notification['icon']; ?>"></i>
                                        <?php echo $notification['type_name'] ?? $notification['notification_type']; ?>
                                    </td>
                                    <td><?php echo $notification['title']; ?></td>
                                    <td>
                                        <?php 
                                        switch ($notification['priority']) {
                                            case 'low': echo '<span class="badge bg-secondary">ต่ำ</span>'; break;
                                            case 'normal': echo '<span class="badge bg-primary">ปกติ</span>'; break;
                                            case 'high': echo '<span class="badge bg-warning">สูง</span>'; break;
                                            case 'urgent': echo '<span class="badge bg-danger">ด่วน</span>'; break;
                                            default: echo $notification['priority']; break;
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $notification['recipient_name'] ?? 'ไม่ระบุ'; ?></td>
                                    <td><?php echo $notification['sender_name'] ?? ($notification['is_system'] ? 'ระบบ' : 'ไม่ระบุ'); ?></td>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($notification['created_at'])); ?></td>
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
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info view-notification" 
                                            data-id="<?php echo $notification['notification_id']; ?>"
                                            data-title="<?php echo htmlspecialchars($notification['title']); ?>"
                                            data-message="<?php echo htmlspecialchars($notification['message']); ?>"
                                            data-type="<?php echo $notification['type_name'] ?? $notification['notification_type']; ?>"
                                            data-icon="<?php echo $notification['icon']; ?>"
                                            data-priority="<?php echo $notification['priority']; ?>"
                                            data-recipient="<?php echo $notification['recipient_name'] ?? 'ไม่ระบุ'; ?>"
                                            data-sender="<?php echo $notification['sender_name'] ?? ($notification['is_system'] ? 'ระบบ' : 'ไม่ระบุ'); ?>"
                                            data-created="<?php echo date('d/m/Y H:i:s', strtotime($notification['created_at'])); ?>"
                                            data-link="<?php echo $notification['link']; ?>"
                                        >
                                            ดู
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="#" data-page="<?php echo $page - 1; ?>">ก่อน
