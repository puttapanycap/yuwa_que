<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

if (!hasPermission('manage_users')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

// Get filter parameters
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$staffId = $_GET['staff_id'] ?? '';
$searchTerm = $_GET['search'] ?? '';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

try {
    $db = getDB();
    
    // Get staff list for filter
    $stmt = $db->prepare("SELECT staff_id, full_name FROM staff_users ORDER BY full_name");
    $stmt->execute();
    $staffList = $stmt->fetchAll();
    
    // Build WHERE clause
    $whereConditions = [];
    $params = [];
    
    // Date range
    $whereConditions[] = "DATE(al.timestamp) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    
    // Staff filter
    if ($staffId) {
        $whereConditions[] = "al.staff_id = ?";
        $params[] = $staffId;
    }
    
    // Search term
    if ($searchTerm) {
        $whereConditions[] = "al.action_description LIKE ?";
        $params[] = '%' . $searchTerm . '%';
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get total count
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM audit_logs al
        WHERE {$whereClause}
    ");
    $stmt->execute($params);
    $totalRecords = $stmt->fetch()['total'];
    $totalPages = ceil($totalRecords / $perPage);
    
    // Get audit logs
    $stmt = $db->prepare("
        SELECT al.*, su.full_name,
               DATE_FORMAT(al.timestamp, '%d/%m/%Y %H:%i:%s') as formatted_timestamp
        FROM audit_logs al
        LEFT JOIN staff_users su ON al.staff_id = su.staff_id
        WHERE {$whereClause}
        ORDER BY al.timestamp DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $auditLogs = $stmt->fetchAll();
    
} catch (Exception $e) {
    $auditLogs = [];
    $totalRecords = 0;
    $totalPages = 0;
    $staffList = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกการใช้งาน - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    
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
        
        .filter-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .log-item {
            border-left: 4px solid #007bff;
            background: #f8f9fa;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0 8px 8px 0;
        }
        
        .log-item.system {
            border-left-color: #28a745;
        }
        
        .log-item.warning {
            border-left-color: #ffc107;
        }
        
        .log-item.error {
            border-left-color: #dc3545;
        }
        
        .pagination-wrapper {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-top: 2rem;
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
                            <h2>บันทึกการใช้งาน</h2>
                            <p class="text-muted">ติดตามกิจกรรมและการใช้งานระบบ</p>
                        </div>
                        <div>
                            <span class="badge bg-info">
                                <?php echo number_format($totalRecords); ?> รายการ
                            </span>
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="filter-card">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">วันที่เริ่มต้น</label>
                                <input type="date" class="form-control" name="start_date" value="<?php echo $startDate; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">วันที่สิ้นสุด</label>
                                <input type="date" class="form-control" name="end_date" value="<?php echo $endDate; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">เจ้าหน้าที่</label>
                                <select class="form-select" name="staff_id">
                                    <option value="">ทุกคน</option>
                                    <?php foreach ($staffList as $staff): ?>
                                        <option value="<?php echo $staff['staff_id']; ?>" 
                                                <?php echo $staffId == $staff['staff_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($staff['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">ค้นหา</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" 
                                           value="<?php echo htmlspecialchars($searchTerm); ?>" 
                                           placeholder="ค้นหากิจกรรม...">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Audit Logs -->
                    <div class="content-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5>บันทึกก���จกรรม</h5>
                            <div>
                                <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
                                    <i class="fas fa-sync-alt me-1"></i>รีเฟรช
                                </button>
                                <button class="btn btn-outline-primary btn-sm" onclick="exportLogs()">
                                    <i class="fas fa-download me-1"></i>ส่งออก
                                </button>
                            </div>
                        </div>
                        
                        <?php if (empty($auditLogs)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-history fa-3x mb-3"></i>
                                <p>ไม่พบบันทึกการใช้งานในช่วงเวลาที่เลือก</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($auditLogs as $log): ?>
                                <div class="log-item <?php echo getLogClass($log['action_description']); ?>">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="fw-bold mb-1">
                                                <?php echo htmlspecialchars($log['action_description']); ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo htmlspecialchars($log['full_name'] ?: 'ระบบ'); ?>
                                                
                                                <?php if ($log['ip_address']): ?>
                                                    <i class="fas fa-globe ms-3 me-1"></i>
                                                    <?php echo htmlspecialchars($log['ip_address']); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <div class="fw-bold text-primary">
                                                <?php echo $log['formatted_timestamp']; ?>
                                            </div>
                                            <small class="text-muted">
                                                ID: <?php echo $log['log_id']; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="pagination-wrapper">
                                <div>
                                    แสดง <?php echo number_format(($page - 1) * $perPage + 1); ?> - 
                                    <?php echo number_format(min($page * $perPage, $totalRecords)); ?> 
                                    จาก <?php echo number_format($totalRecords); ?> รายการ
                                </div>
                                
                                <nav>
                                    <ul class="pagination mb-0">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $startPage = max(1, $page - 2);
                                        $endPage = min($totalPages, $page + 2);
                                        
                                        for ($i = $startPage; $i <= $endPage; $i++):
                                        ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        function exportLogs() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', '1');
            window.open('export_audit_logs.php?' + params.toString(), '_blank');
        }
        
        // Auto-refresh every 30 seconds
        setInterval(function() {
            if (!document.hidden) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>

<?php
function getLogClass($action) {
    if (strpos($action, 'ลบ') !== false || strpos($action, 'ยกเลิก') !== false) {
        return 'error';
    } elseif (strpos($action, 'เข้าสู่ระบบ') !== false || strpos($action, 'ออกจากระบบ') !== false) {
        return 'system';
    } elseif (strpos($action, 'แก้ไข') !== false) {
        return 'warning';
    }
    return '';
}
?>
