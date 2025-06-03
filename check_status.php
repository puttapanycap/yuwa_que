<?php
require_once 'config/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$queueId = $_GET['queue_id'] ?? null;
$error_message = '';
$queue = null;
$flowHistory = [];
$allServicePoints = [];

// Validate queue_id parameter
if (!$queueId) {
    $error_message = 'ไม่พบข้อมูลคิว - ไม่มี queue_id';
} else {
    try {
        // Test database connection
        $db = getDB();
        if (!$db) {
            throw new Exception('ไม่สามารถเชื่อมต่อฐานข้อมูลได้');
        }
        
        // Get queue info with better error handling
        $stmt = $db->prepare("
            SELECT q.*, qt.type_name, sp.point_name as current_service_point_name
            FROM queues q
            LEFT JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
            LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
            WHERE q.queue_id = ?
        ");
        
        if (!$stmt) {
            throw new Exception('ไม่สามารถเตรียม SQL statement ได้: ' . implode(', ', $db->errorInfo()));
        }
        
        $result = $stmt->execute([$queueId]);
        if (!$result) {
            throw new Exception('ไม่สามารถดำเนินการ query ได้: ' . implode(', ', $stmt->errorInfo()));
        }
        
        $queue = $stmt->fetch();
        
        if (!$queue) {
            $error_message = 'ไม่พบข้อมูลคิว ID: ' . htmlspecialchars($queueId);
        } else {
            // Get service flow history
            $stmt = $db->prepare("
                SELECT sfh.*, sp_from.point_name as from_point_name, sp_to.point_name as to_point_name
                FROM service_flow_history sfh
                LEFT JOIN service_points sp_from ON sfh.from_service_point_id = sp_from.service_point_id
                LEFT JOIN service_points sp_to ON sfh.to_service_point_id = sp_to.service_point_id
                WHERE sfh.queue_id = ?
                ORDER BY sfh.timestamp ASC
            ");
            
            if ($stmt && $stmt->execute([$queueId])) {
                $flowHistory = $stmt->fetchAll();
            }
            
            // Get all service points for this queue type to build complete timeline
            $stmt = $db->prepare("
                SELECT sp.service_point_id, sp.point_name, sp.sequence_order
                FROM service_points sp
                WHERE sp.is_active = 1 
                AND (sp.queue_type_id = ? OR sp.queue_type_id IS NULL)
                ORDER BY sp.sequence_order ASC
            ");
            
            if ($stmt && $stmt->execute([$queue['queue_type_id']])) {
                $allServicePoints = $stmt->fetchAll();
            }
        }
        
    } catch (PDOException $e) {
        $error_message = 'ข้อผิดพลาดฐานข้อมูล: ' . $e->getMessage();
        error_log("Database error in check_status.php: " . $e->getMessage());
    } catch (Exception $e) {
        $error_message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        error_log("General error in check_status.php: " . $e->getMessage());
    }
}

// If there's an error, show error page
if ($error_message) {
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ข้อผิดพลาด - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
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
                justify-content: center;
            }
            .error-container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                padding: 3rem;
                text-align: center;
                max-width: 500px;
            }
            .error-icon {
                font-size: 4rem;
                color: #dc3545;
                margin-bottom: 1rem;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2 class="text-danger mb-3">เกิดข้อผิดพลาด</h2>
            <p class="text-muted mb-4"><?php echo htmlspecialchars($error_message); ?></p>
            
            <div class="mb-3">
                <small class="text-muted">
                    <strong>ข้อมูลการแก้ไขปัญหา:</strong><br>
                    - ตรวจสอบว่า queue_id ถูกต้อง<br>
                    - ตรวจสอบการเชื่อมต่อฐานข้อมูล<br>
                    - ติดต่อผู้ดูแลระบบหากปัญหายังคงอยู่
                </small>
            </div>
            
            <div class="d-flex gap-2 justify-content-center">
                <button class="btn btn-primary" onclick="history.back()">
                    <i class="fas fa-arrow-left me-2"></i>ย้อนกลับ
                </button>
                <button class="btn btn-outline-primary" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-2"></i>ลองใหม่
                </button>
                <button class="btn btn-success" onclick="window.location.href='index.php'">
                    <i class="fas fa-home me-2"></i>หน้าแรก
                </button>
            </div>
            
            <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
            <div class="mt-4 p-3 bg-light rounded">
                <h6>Debug Information:</h6>
                <small class="text-muted">
                    Queue ID: <?php echo htmlspecialchars($queueId ?? 'null'); ?><br>
                    Error: <?php echo htmlspecialchars($error_message); ?><br>
                    Time: <?php echo date('Y-m-d H:i:s'); ?>
                </small>
            </div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถานะคิว <?php echo htmlspecialchars($queue['queue_number']); ?> - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .status-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .queue-header {
            text-align: center;
            padding-bottom: 1rem;
            border-bottom: 3px solid #007bff;
            margin-bottom: 2rem;
        }
        
        .queue-number {
            font-size: 3rem;
            font-weight: bold;
            color: #007bff;
        }
        
        .status-badge {
            font-size: 1.1rem;
            padding: 0.5rem 1rem;
            border-radius: 25px;
        }
        
        .timeline-container {
            display: flex;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .timeline-section {
            flex: 1;
        }
        
        .timeline {
            position: relative;
            padding: 1rem 0;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 30px;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(to top, #28a745, #ffc107, #dc3545);
            border-radius: 2px;
        }
        
        .timeline-item {
            position: relative;
            padding: 1rem 0 1rem 4rem;
            margin-bottom: 1rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 22px;
            top: 1.5rem;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 2;
        }
        
        .timeline-item.completed::before {
            background-color: #28a745;
        }
        
        .timeline-item.current::before {
            background-color: #ffc107;
            animation: pulse-timeline 2s infinite;
        }
        
        .timeline-item.pending::before {
            background-color: #dee2e6;
        }
        
        @keyframes pulse-timeline {
            0% { transform: scale(1); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
            50% { transform: scale(1.2); box-shadow: 0 4px 16px rgba(255,193,7,0.4); }
            100% { transform: scale(1); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        }
        
        .timeline-content {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid transparent;
        }
        
        .timeline-item.completed .timeline-content {
            border-left-color: #28a745;
            background: #f8fff9;
        }
        
        .timeline-item.current .timeline-content {
            border-left-color: #ffc107;
            background: #fffdf0;
            animation: glow-current 2s infinite;
        }
        
        .timeline-item.pending .timeline-content {
            border-left-color: #dee2e6;
            background: #f8f9fa;
            opacity: 0.7;
        }
        
        @keyframes glow-current {
            0% { box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            50% { box-shadow: 0 4px 20px rgba(255,193,7,0.3); }
            100% { box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        }
        
        .timeline-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .timeline-time {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .timeline-status {
            font-size: 0.8rem;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .timeline-item.completed .timeline-status {
            background-color: #d4edda;
            color: #155724;
        }
        
        .timeline-item.current .timeline-status {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .timeline-item.pending .timeline-status {
            background-color: #e2e3e5;
            color: #6c757d;
        }
        
        .flow-step {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 10px;
            border-left: 4px solid #dee2e6;
        }
        
        .flow-step.completed {
            background-color: #d4edda;
            border-left-color: #28a745;
        }
        
        .flow-step.current {
            background-color: #fff3cd;
            border-left-color: #ffc107;
        }
        
        .flow-step.pending {
            background-color: #f8f9fa;
            border-left-color: #dee2e6;
        }

        .qr-code {
            text-align: center;
            margin-top: 20px;
        }

        #qrcode {
            margin: 0 auto;
            display: block;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #495057;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
        }
        
        .progress-indicator {
            text-align: center;
            margin-bottom: 1rem;
            padding: 1rem;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
        }
        
        .progress-text {
            font-size: 1.1rem;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .progress-bar-custom {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 0.5rem 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #ffc107);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="status-container">
            <div class="queue-header">
                <h1 class="h3 text-primary mb-2">โรงพยาบาลยุวประสาทไวทโยปถัมภ์</h1>
                <div class="queue-number"><?php echo htmlspecialchars($queue['queue_number']); ?></div>
                <div class="h5 text-muted"><?php echo htmlspecialchars($queue['type_name']); ?></div>
                <div class="mt-3">
                    <?php
                    $statusClass = '';
                    $statusText = '';
                    switch ($queue['current_status']) {
                        case 'waiting':
                            $statusClass = 'bg-warning text-dark';
                            $statusText = 'รอเรียก';
                            break;
                        case 'called':
                            $statusClass = 'bg-info text-white';
                            $statusText = 'กำลังเรียก';
                            break;
                        case 'processing':
                            $statusClass = 'bg-primary text-white';
                            $statusText = 'กำลังให้บริการ';
                            break;
                        case 'completed':
                            $statusClass = 'bg-success text-white';
                            $statusText = 'เสร็จสิ้น';
                            break;
                        case 'cancelled':
                            $statusClass = 'bg-danger text-white';
                            $statusText = 'ยกเลิก';
                            break;
                        default:
                            $statusClass = 'bg-secondary text-white';
                            $statusText = 'ไม่ทราบสถานะ';
                    }
                    ?>
                    <span class="status-badge <?php echo $statusClass; ?>">
                        <?php echo $statusText; ?>
                    </span>
                </div>
            </div>
            
            <?php
            // สร้าง timeline data
            $timelineSteps = [];
            $completedSteps = [];
            $currentStep = null;

            // เก็บข้อมูลขั้นตอนที่เสร็จแล้ว
            foreach ($flowHistory as $history) {
                if ($history['to_service_point_id']) {
                    $completedSteps[] = $history['to_service_point_id'];
                    if ($history['action'] == 'completed') {
                        // ขั้นตอนนี้เสร็จแล้ว
                    } else {
                        // ขั้นตอนปัจจุบัน
                        $currentStep = $history['to_service_point_id'];
                    }
                }
            }

            // สร้าง timeline จาก service points ทั้งหมด
            foreach ($allServicePoints as $sp) {
                $status = 'pending';
                $timestamp = null;

                if (in_array($sp['service_point_id'], $completedSteps)) {
                    if ($sp['service_point_id'] == $currentStep) {
                        $status = 'current';
                    } else {
                        $status = 'completed';
                    }

                    // หา timestamp
                    foreach ($flowHistory as $history) {
                        if ($history['to_service_point_id'] == $sp['service_point_id']) {
                            $timestamp = $history['timestamp'];
                            break;
                        }
                    }
                }

                $timelineSteps[] = [
                    'service_point_id' => $sp['service_point_id'],
                    'point_name' => $sp['point_name'],
                    'status' => $status,
                    'timestamp' => $timestamp,
                    'sequence_order' => $sp['sequence_order']
                ];
            }

            // เรียงลำดับจากล่างขึ้นบน (sequence_order น้อยไปมาก)
            usort($timelineSteps, function($a, $b) {
                return $a['sequence_order'] - $b['sequence_order'];
            });
            
            // คำนวณความคืบหน้า
            $totalSteps = count($timelineSteps);
            $completedCount = 0;
            $currentStepIndex = -1;
            
            foreach ($timelineSteps as $index => $step) {
                if ($step['status'] == 'completed') {
                    $completedCount++;
                } elseif ($step['status'] == 'current') {
                    $currentStepIndex = $index;
                    break;
                }
            }
            
            $progressPercentage = $totalSteps > 0 ? ($completedCount / $totalSteps) * 100 : 0;
            ?>
            
            <div class="progress-indicator">
                <div class="progress-text">
                    ความคืบหน้า: <?php echo $completedCount; ?> จาก <?php echo $totalSteps; ?> ขั้นตอน
                </div>
                <div class="progress-bar-custom">
                    <div class="progress-fill" style="width: <?php echo $progressPercentage; ?>%"></div>
                </div>
                <small class="text-muted"><?php echo number_format($progressPercentage, 1); ?>% เสร็จสิ้น</small>
            </div>
            
            <div class="timeline-container">
                <!-- Timeline Section -->
                <div class="timeline-section">
                    <h4 class="section-title">
                        <i class="fas fa-route me-2"></i>เส้นทางการให้บริการ
                    </h4>
                    
                    <div class="timeline">
                        <?php foreach (array_reverse($timelineSteps) as $index => $step): ?>
                            <div class="timeline-item <?php echo $step['status']; ?>">
                                <div class="timeline-content">
                                    <div class="timeline-title">
                                        <?php if ($step['status'] == 'completed'): ?>
                                            <i class="fas fa-check-circle text-success"></i>
                                        <?php elseif ($step['status'] == 'current'): ?>
                                            <i class="fas fa-clock text-warning"></i>
                                        <?php else: ?>
                                            <i class="fas fa-circle text-muted"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($step['point_name']); ?>
                                    </div>
                                    
                                    <?php if ($step['timestamp']): ?>
                                        <div class="timeline-time">
                                            <i class="fas fa-calendar-alt me-1"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($step['timestamp'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="timeline-status">
                                        <?php
                                        switch ($step['status']) {
                                            case 'completed':
                                                echo 'เสร็จสิ้น';
                                                break;
                                            case 'current':
                                                echo 'กำลังดำเนินการ';
                                                break;
                                            case 'pending':
                                                echo 'รอดำเนินการ';
                                                break;
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- History Section -->
                <div class="timeline-section">
                    <h4 class="section-title">
                        <i class="fas fa-history me-2"></i>ประวัติการดำเนินการ
                    </h4>
                    
                    <?php if (!empty($flowHistory)): ?>
                        <?php foreach ($flowHistory as $step): ?>
                            <div class="flow-step <?php echo ($step['action'] == 'completed') ? 'completed' : (($step['to_service_point_id'] == $queue['current_service_point_id']) ? 'current' : 'pending'); ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php if ($step['action'] == 'completed'): ?>
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                            <?php elseif ($step['to_service_point_id'] == $queue['current_service_point_id']): ?>
                                                <i class="fas fa-clock text-warning me-2"></i>
                                            <?php else: ?>
                                                <i class="fas fa-circle text-muted me-2"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($step['to_point_name']); ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?php
                                            switch ($step['action']) {
                                                case 'created':
                                                    echo 'สร้างคิว';
                                                    break;
                                                case 'called':
                                                    echo 'เรียกคิว';
                                                    break;
                                                case 'forwarded':
                                                    echo 'ส่งต่อ';
                                                    break;
                                                case 'completed':
                                                    echo 'เสร็จสิ้น';
                                                    break;
                                                default:
                                                    echo $step['action'];
                                            }
                                            ?>
                                        </small>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo date('H:i', strtotime($step['timestamp'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="flow-step current">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">
                                        <i class="fas fa-clock text-warning me-2"></i>
                                        <?php echo htmlspecialchars($queue['current_service_point_name']); ?>
                                    </h6>
                                    <small class="text-muted">รอเรียก</small>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('H:i', strtotime($queue['creation_time'])); ?>
                                </small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="qr-code">
                <canvas id="qrcode"></canvas>
                <p class="mt-2">สแกน QR Code เพื่อตรวจสอบสถานะคิว</p>
            </div>
            
            <div class="text-center mt-4">
                <button class="btn btn-outline-primary" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-2"></i>รีเฟรช
                </button>
                <div class="dropdown ms-2">
                    <button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-download me-2"></i>Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="api/export_timeline.php?queue_id=<?php echo $queue['queue_id']; ?>&format=json" target="_blank">
                            <i class="fas fa-file-code me-2"></i>JSON
                        </a></li>
                        <li><a class="dropdown-item" href="api/export_timeline.php?queue_id=<?php echo $queue['queue_id']; ?>&format=csv" target="_blank">
                            <i class="fas fa-file-csv me-2"></i>CSV
                        </a></li>
                        <li><a class="dropdown-item" href="api/export_timeline.php?queue_id=<?php echo $queue['queue_id']; ?>&format=html" target="_blank">
                            <i class="fas fa-file-alt me-2"></i>HTML Report
                        </a></li>
                    </ul>
                </div>
                <button class="btn btn-primary ms-2" onclick="window.location.href='index.php'">
                    <i class="fas fa-home me-2"></i>หน้าแรก
                </button>
            </div>
            
            <div class="text-center mt-3">
                <small class="text-muted">
                    อัปเดตล่าสุด: <?php echo date('d/m/Y H:i:s'); ?>
                </small>
            </div>
        </div>
    </div>
    
    <!-- QR Code Libraries - Multiple fallbacks -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Generate QR Code with fallback
        $(document).ready(function() {
            const queueId = <?php echo json_encode($queue['queue_id']); ?>;
            const qrData = window.location.href; // Use current URL
            
            // Function to create QR Code
            function createQRCode() {
                // Load QRious library
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js';
                script.onload = function() {
                    try {
                        new QRious({
                            element: document.getElementById('qrcode'),
                            value: qrData,
                            size: 200,
                            backgroundAlpha: 1,
                            foreground: '#000000',
                            background: '#FFFFFF',
                            level: 'H'
                        });
                    } catch (error) {
                        console.error('QR Code generation error:', error);
                        drawFallbackText();
                    }
                };
                
                script.onerror = function() {
                    drawFallbackText();
                };
                
                document.head.appendChild(script);
            }
            
            // Fallback function to draw text if QR code fails
            function drawFallbackText() {
                const canvas = document.getElementById('qrcode');
                const ctx = canvas.getContext('2d');
                canvas.width = 200;
                canvas.height = 200;
                ctx.fillStyle = '#f8f9fa';
                ctx.fillRect(0, 0, 200, 200);
                ctx.fillStyle = '#6c757d';
                ctx.font = '14px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('QR Code', 100, 90);
                ctx.fillText('ไม่สามารถสร้างได้', 100, 110);
            }
            
            // Try to create QR Code
            createQRCode();
        });
        
        // Auto refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
    
    <!-- Fallback script if main QR library fails -->
    <script>
        // Check if QRCode library loaded, if not load alternative
        if (typeof QRCode === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js';
            script.onload = function() {
                if (typeof QRious !== 'undefined') {
                    const qr = new QRious({
                        element: document.getElementById('qrcode'),
                        value: window.location.href,
                        size: 200
                    });
                }
            };
            document.head.appendChild(script);
        }
    </script>
    <?php include 'components/notification-system.php'; renderNotificationSystem(); ?>
</body>
</html>
