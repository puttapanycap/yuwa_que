<?php
require_once 'config/config.php';

// Create a test queue for demonstration
if (isset($_POST['create_test_queue'])) {
    try {
        $db = getDB();
        
        // Get first available queue type and service point
        $stmt = $db->query("SELECT queue_type_id FROM queue_types WHERE is_active = 1 LIMIT 1");
        $queueType = $stmt->fetch();
        
        $stmt = $db->query("SELECT service_point_id FROM service_points WHERE is_active = 1 LIMIT 1");
        $servicePoint = $stmt->fetch();
        
        if ($queueType && $servicePoint) {
            // Generate queue number
            $prefix = 'T';
            $stmt = $db->prepare("SELECT MAX(CAST(SUBSTRING(queue_number, 2) AS UNSIGNED)) as max_num FROM queues WHERE DATE(creation_time) = CURDATE() AND queue_number LIKE ?");
            $stmt->execute([$prefix . '%']);
            $result = $stmt->fetch();
            $nextNumber = ($result['max_num'] ?? 0) + 1;
            $queueNumber = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            
            // Insert test queue
            $stmt = $db->prepare("
                INSERT INTO queues (queue_number, queue_type_id, current_service_point_id, current_status, priority_level, creation_time) 
                VALUES (?, ?, ?, 'waiting', 1, NOW())
            ");
            $stmt->execute([$queueNumber, $queueType['queue_type_id'], $servicePoint['service_point_id']]);
            
            $testQueueId = $db->lastInsertId();
            $successMessage = "สร้างคิวทดสอบสำเร็จ: $queueNumber (ID: $testQueueId)";
        } else {
            $errorMessage = "ไม่พบประเภทคิวหรือจุดบริการที่ใช้งานได้";
        }
    } catch (Exception $e) {
        $errorMessage = "ข้อผิดพลาดในการสร้างคิวทดสอบ: " . $e->getMessage();
    }
}

// Get recent queues for testing
try {
    $db = getDB();
    $stmt = $db->query("
        SELECT q.queue_id, q.queue_number, qt.type_name, q.current_status, q.creation_time
        FROM queues q
        LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
        ORDER BY q.creation_time DESC
        LIMIT 10
    ");
    $recentQueues = $stmt->fetchAll();
} catch (Exception $e) {
    $recentQueues = [];
    $errorMessage = "ไม่สามารถดึงข้อมูลคิวได้: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทดสอบระบบตรวจสอบสถานะคิว</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-vial me-2"></i>ทดสอบระบบตรวจสอบสถานะคิว</h2>
                
                <?php if (isset($successMessage)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($errorMessage)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $errorMessage; ?>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-plus me-2"></i>สร้างคิวทดสอบ</h5>
                            </div>
                            <div class="card-body">
                                <p>สร้างคิวทดสอบเพื่อทดสอบการทำงานของระบบตรวจสอบสถานะ</p>
                                <form method="POST">
                                    <button type="submit" name="create_test_queue" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>สร้างคิวทดสอบ
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-list me-2"></i>คิวล่าสุด</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recentQueues)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>หมายเลขคิว</th>
                                                    <th>ประเภท</th>
                                                    <th>สถานะ</th>
                                                    <th>การทำงาน</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentQueues as $queue): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($queue['queue_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($queue['type_name']); ?></td>
                                                    <td>
                                                        <?php
                                                        $statusClass = '';
                                                        $statusText = '';
                                                        switch ($queue['current_status']) {
                                                            case 'waiting':
                                                                $statusClass = 'badge bg-warning text-dark';
                                                                $statusText = 'รอเรียก';
                                                                break;
                                                            case 'called':
                                                                $statusClass = 'badge bg-info';
                                                                $statusText = 'กำลังเรียก';
                                                                break;
                                                            case 'processing':
                                                                $statusClass = 'badge bg-primary';
                                                                $statusText = 'กำลังให้บริการ';
                                                                break;
                                                            case 'completed':
                                                                $statusClass = 'badge bg-success';
                                                                $statusText = 'เสร็จสิ้น';
                                                                break;
                                                            default:
                                                                $statusClass = 'badge bg-secondary';
                                                                $statusText = $queue['current_status'];
                                                        }
                                                        ?>
                                                        <span class="<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                                    </td>
                                                    <td>
                                                        <a href="check_status.php?queue_id=<?php echo $queue['queue_id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                                            <i class="fas fa-eye me-1"></i>ดู
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">ไม่พบข้อมูลคิว</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h5><i class="fas fa-tools me-2"></i>เครื่องมือทดสอบ</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6>ทดสอบ URL โดยตรง</h6>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="testQueueId" placeholder="Queue ID">
                                    <button class="btn btn-outline-secondary" onclick="testQueueStatus()">
                                        <i class="fas fa-external-link-alt"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h6>ทดสอบการเชื่อมต่อฐานข้อมูล</h6>
                                <button class="btn btn-outline-info" onclick="testDatabase()">
                                    <i class="fas fa-database me-2"></i>ทดสอบ DB
                                </button>
                            </div>
                            <div class="col-md-4">
                                <h6>ดูการวินิจฉัยระบบ</h6>
                                <a href="admin/database_diagnostic.php" class="btn btn-outline-success">
                                    <i class="fas fa-stethoscope me-2"></i>วินิจฉัย
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home me-2"></i>กลับหน้าแรก
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testQueueStatus() {
            const queueId = document.getElementById('testQueueId').value;
            if (queueId) {
                window.open(`check_status.php?queue_id=${queueId}&debug=1`, '_blank');
            } else {
                alert('กรุณาใส่ Queue ID');
            }
        }
        
        function testDatabase() {
            fetch('api/get_today_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('ข้อผิดพลาด: ' + data.error);
                    } else {
                        alert('การเชื่อมต่อฐานข้อมูลปกติ\nคิววันนี้: ' + (data.total_today || 0));
                    }
                })
                .catch(error => {
                    alert('ไม่สามารถเชื่อมต่อได้: ' + error.message);
                });
        }
    </script>
</body>
</html>
