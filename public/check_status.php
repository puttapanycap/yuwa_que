<?php
require_once __DIR__ . '/../config/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$queueId = $_GET['queue_id'] ?? null;
$error_message = '';
$queue = null;
$flowHistory = [];
$allServicePoints = [];
$appointmentsToday = [];
$appointmentPatient = null;
$appointmentPatientLine = '';
$appointmentError = '';
$appointmentLookupPerformed = false;

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
            SELECT q.*, qt.type_name, qt.ticket_template, sp.point_label as current_service_point_label, sp.point_name as current_service_point_name
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
        if ($queue) {
            $queue['current_service_point_name'] = trim(($queue['current_service_point_label'] ? $queue['current_service_point_label'] . ' ' : '') . $queue['current_service_point_name']);
        }

        if (!$queue) {
            $error_message = 'ไม่พบข้อมูลคิว ID: ' . htmlspecialchars($queueId);
        } else {
            $ticketTemplate = $queue['ticket_template'] ?? 'standard';
            if ($ticketTemplate === 'appointment_list') {
                $appointmentLookupPerformed = true;
                $idCard = preg_replace('/\D/', '', $queue['patient_id_card_number'] ?? '');

                if ($idCard && strlen($idCard) === 13) {
                    try {
                        $appointmentResult = fetchAppointmentsForIdCard($idCard, date('Y-m-d'));
                        $appointmentsToday = $appointmentResult['appointments'] ?? [];
                        $appointmentPatient = $appointmentResult['patient'] ?? null;

                        if (!($appointmentResult['ok'] ?? false) && !empty($appointmentResult['error'])) {
                            $appointmentError = $appointmentResult['error'];
                        }
                    } catch (Exception $e) {
                        $appointmentError = $e->getMessage();
                    }
                } else {
                    $appointmentError = 'ไม่พบเลขบัตรประชาชนของผู้ป่วยสำหรับการดึงข้อมูลนัดหมาย';
                }

                if (is_array($appointmentPatient)) {
                    $hnText = trim((string) ($appointmentPatient['hn'] ?? ''));
                    if ($hnText === '') {
                        $hnText = trim((string) ($appointmentPatient['HN'] ?? $appointmentPatient['patient_hn'] ?? ''));
                    }
                    if ($hnText !== '') {
                        $appointmentPatientLine = 'HN ' . $hnText;
                    }
                }
            }

            // คำนวณจำนวนคิวที่รออยู่ก่อนหน้า
            $queuePosition = 0;
            $totalQueuesAhead = 0;
            $totalQueuesToday = 0;
            $estimatedWaitTime = 0;

            // นับจำนวนคิวที่รออยู่ก่อนหน้า (queue_number น้อยกว่า และยังไม่เสร็จสิ้น)
            $stmt = $db->prepare("
                SELECT COUNT(*) as queues_ahead
                FROM queues 
                WHERE queue_type_id = ? 
                AND DATE(creation_time) = DATE(?)
                AND queue_number < ?
                AND current_status NOT IN ('completed', 'cancelled')
            ");

            if ($stmt && $stmt->execute([$queue['queue_type_id'], $queue['creation_time'], $queue['queue_number']])) {
                $result = $stmt->fetch();
                $totalQueuesAhead = $result['queues_ahead'];
            }

            // นับจำนวนคิวทั้งหมดในวันนี้ (ประเภทเดียวกัน)
            $stmt = $db->prepare("
                SELECT COUNT(*) as total_today
                FROM queues 
                WHERE queue_type_id = ? 
                AND DATE(creation_time) = DATE(?)
            ");

            if ($stmt && $stmt->execute([$queue['queue_type_id'], $queue['creation_time']])) {
                $result = $stmt->fetch();
                $totalQueuesToday = $result['total_today'];
            }

            // คำนวณตำแหน่งของคิวปัจจุบัน
            $stmt = $db->prepare("
                SELECT COUNT(*) + 1 as position
                FROM queues 
                WHERE queue_type_id = ? 
                AND DATE(creation_time) = DATE(?)
                AND queue_number < ?
            ");

            if ($stmt && $stmt->execute([$queue['queue_type_id'], $queue['creation_time'], $queue['queue_number']])) {
                $result = $stmt->fetch();
                $queuePosition = $result['position'];
            }

            // คำนวณเวลารอโดยประมาณ (ใช้ข้อมูลเฉลี่ยจากการให้บริการในอดีต)
            $stmt = $db->prepare("
                SELECT AVG(TIMESTAMPDIFF(MINUTE, creation_time, 
                    CASE 
                        WHEN current_status = 'completed' THEN updated_at
                        ELSE NOW()
                    END
                )) as avg_service_time
                FROM queues 
                WHERE queue_type_id = ? 
                AND DATE(creation_time) = DATE(NOW() - INTERVAL 7 DAY)
                AND current_status = 'completed'
                AND TIMESTAMPDIFF(MINUTE, creation_time, updated_at) BETWEEN 5 AND 120
            ");

            if ($stmt && $stmt->execute([$queue['queue_type_id']])) {
                $result = $stmt->fetch();
                $avgServiceTime = $result['avg_service_time'] ?? 15; // default 15 นาที
                $estimatedWaitTime = $totalQueuesAhead * $avgServiceTime;
            }
            // Get service flow history
            $stmt = $db->prepare("
                SELECT sfh.*,
                       TRIM(CONCAT(COALESCE(sp_from.point_label,''),' ', sp_from.point_name)) as from_point_name,
                       TRIM(CONCAT(COALESCE(sp_to.point_label,''),' ', sp_to.point_name)) as to_point_name
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
                SELECT DISTINCT sp.service_point_id, TRIM(CONCAT(COALESCE(sp.point_label,''),' ', sp.point_name)) as point_name, sp.display_order
                FROM service_points sp
                INNER JOIN service_flows sf ON (sp.service_point_id = sf.to_service_point_id
                    OR sp.service_point_id = sf.from_service_point_id)
                WHERE sp.is_active = 1
                AND sf.queue_type_id = ?
                AND sf.is_active = 1
                ORDER BY sp.display_order ASC
            ");

            if ($stmt && $stmt->execute([$queue['queue_type_id']])) {
                $allServicePoints = $stmt->fetchAll();

                // ถ้าไม่พบจุดบริการที่เกี่ยวข้อง ให้เพิ่มจุดบริการปัจจุบันเข้าไป
                if (empty($allServicePoints) && !empty($queue['current_service_point_id'])) {
                    $currentPointStmt = $db->prepare("
                        SELECT service_point_id, TRIM(CONCAT(COALESCE(point_label,''),' ', point_name)) as point_name, display_order
                        FROM service_points
                        WHERE service_point_id = ? AND is_active = 1
                    ");

                    if ($currentPointStmt && $currentPointStmt->execute([$queue['current_service_point_id']])) {
                        $currentPoint = $currentPointStmt->fetch();
                        if ($currentPoint) {
                            $allServicePoints[] = $currentPoint;
                        }
                    }
                }
            } else {
                // Fallback: get all active service points if the flow query fails
                $stmt = $db->prepare("
                    SELECT service_point_id, TRIM(CONCAT(COALESCE(point_label,''),' ', point_name)) as point_name, display_order
                    FROM service_points
                    WHERE is_active = 1
                    ORDER BY display_order ASC
                ");
                if ($stmt && $stmt->execute()) {
                    $allServicePoints = $stmt->fetchAll();
                }
            }

            // เพิ่มการตรวจสอบว่าจุดบริการปัจจุบันอยู่ใน allServicePoints หรือไม่
            $currentPointExists = false;
            foreach ($allServicePoints as $sp) {
                if ($sp['service_point_id'] == $queue['current_service_point_id']) {
                    $currentPointExists = true;
                    break;
                }
            }

            // ถ้าไม่มีจุดบริการปัจจุบันใน allServicePoints ให้เพิ่มเข้าไป
            if (!$currentPointExists && !empty($queue['current_service_point_id'])) {
                $currentPointStmt = $db->prepare("
                    SELECT service_point_id, TRIM(CONCAT(COALESCE(point_label,''),' ', point_name)) as point_name, display_order
                    FROM service_points
                    WHERE service_point_id = ?
                ");

                if ($currentPointStmt && $currentPointStmt->execute([$queue['current_service_point_id']])) {
                    $currentPoint = $currentPointStmt->fetch();
                    if ($currentPoint) {
                        $allServicePoints[] = $currentPoint;
                    }
                }
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
        <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap"
            rel="stylesheet">
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
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
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
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

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
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
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
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
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
            0% {
                transform: scale(1);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            50% {
                transform: scale(1.2);
                box-shadow: 0 4px 16px rgba(255, 193, 7, 0.4);
            }

            100% {
                transform: scale(1);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }
        }

        .timeline-content {
            background: white;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
            0% {
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }

            50% {
                box-shadow: 0 4px 20px rgba(255, 193, 7, 0.3);
            }

            100% {
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }
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

        @media (max-width: 768px) {
            .timeline-container {
                flex-direction: column;
            }

            .timeline-section {
                width: 100%;
            }

            .queue-number {
                font-size: 2.5rem;
            }

            .status-badge {
                font-size: 1rem;
                padding: 0.4rem 0.8rem;
            }

            .timeline::before {
                left: 20px;
            }

            .timeline-item {
                padding-left: 3rem;
            }

            .timeline-item::before {
                left: 12px;
                width: 16px;
                height: 16px;
            }

            .qr-code canvas {
                width: 150px !important;
                height: 150px !important;
            }
        }

        @media (max-width: 480px) {
            .status-container {
                padding: 1rem;
            }

            .queue-number {
                font-size: 2rem;
            }

            .timeline-item {
                padding-left: 2.5rem;
            }

            .timeline::before {
                left: 15px;
            }

            .timeline-item::before {
                left: 7px;
            }

            .timeline-title {
                font-size: 1rem;
            }

            .qr-code canvas {
                width: 120px !important;
                height: 120px !important;
            }
        }

        .queue-info-container {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .queue-info-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .queue-info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .queue-info-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .queue-info-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #495057;
            margin-bottom: 0.25rem;
        }

        .queue-info-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
        }

        .queue-alert {
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .queue-info-card {
                padding: 0.75rem;
            }

            .queue-info-number {
                font-size: 1.5rem;
            }

            .queue-info-label {
                font-size: 0.8rem;
            }

            .queue-info-icon {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .queue-info-container {
                padding: 1rem;
            }

            .queue-info-card {
                padding: 0.5rem;
            }

            .queue-info-number {
                font-size: 1.3rem;
            }

        .queue-info-label {
            font-size: 0.75rem;
        }
        }

        .appointment-section {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
            padding: 1.75rem;
            margin-top: 2rem;
        }

        .appointment-section .section-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .appointment-patient {
            font-size: 0.95rem;
            color: #495057;
            margin-bottom: 1rem;
        }

        .appointment-list {
            margin: 0;
            padding: 0;
        }

        .appointment-entry {
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .appointment-entry:last-child {
            border-bottom: none;
        }

        .appointment-entry-line {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .appointment-entry-time {
            font-weight: 600;
            color: #0d6efd;
            font-size: 1.05rem;
        }

        .appointment-entry-detail {
            font-weight: 600;
            color: #212529;
        }

        .appointment-entry-notes {
            font-size: 0.95rem;
            color: #495057;
            margin-top: 0.35rem;
            white-space: pre-wrap;
        }

        .appointment-entry-status {
            font-size: 0.85rem;
            color: #0d6efd;
            margin-top: 0.35rem;
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

            // สร้าง timeline จาก service points ที่เกี่ยวข้องกับ flow ของคิวนี้
            foreach ($allServicePoints as $sp) {
                // เช็คว่าจุดบริการนี้อยู่ใน flow ของคิวประเภทนี้หรือไม่
                $isInFlow = false;
                $status = 'pending';
                $timestamp = null;
                $isCurrentPoint = ($sp['service_point_id'] == $queue['current_service_point_id']);

                // ตรวจสอบว่าเป็นจุดที่ผ่านมาแล้วหรือไม่
                if (in_array($sp['service_point_id'], $completedSteps)) {
                    $isInFlow = true;
                    if ($isCurrentPoint) {
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
                // ตรวจสอบว่าเป็นจุดปัจจุบันหรือไม่
                else if ($isCurrentPoint) {
                    $isInFlow = true;
                    $status = 'current';
                }

                // เพิ่มเฉพาะจุดที่อยู่ใน flow หรือเป็นจุดปัจจุบัน
                if ($isInFlow) {
                    $timelineSteps[] = [
                        'service_point_id' => $sp['service_point_id'],
                        'point_name' => $sp['point_name'],
                        'status' => $status,
                        'timestamp' => $timestamp,
                        'display_order' => $sp['display_order']
                    ];
                }
            }

            // เรียงลำดับจากล่างขึ้นบน (sequence_order น้อยไปมาก)
            usort($timelineSteps, function ($a, $b) {
                return $a['display_order'] - $b['display_order'];
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

            <!-- Queue Position Information -->
            <div class="queue-info-container mt-3">
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="queue-info-card">
                            <div class="queue-info-icon">
                                <i class="fas fa-users text-warning"></i>
                            </div>
                            <div class="queue-info-content">
                                <div class="queue-info-number"><?php echo $totalQueuesAhead; ?></div>
                                <div class="queue-info-label">คิวที่รออยู่ข้างหน้า</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="queue-info-card">
                            <div class="queue-info-icon">
                                <i class="fas fa-sort-numeric-up text-info"></i>
                            </div>
                            <div class="queue-info-content">
                                <div class="queue-info-number"><?php echo $queuePosition; ?></div>
                                <div class="queue-info-label">ลำดับที่</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="queue-info-card">
                            <div class="queue-info-icon">
                                <i class="fas fa-clock text-primary"></i>
                            </div>
                            <div class="queue-info-content">
                                <div class="queue-info-number">
                                    <?php
                                    if ($estimatedWaitTime > 60) {
                                        echo floor($estimatedWaitTime / 60) . 'ชม ' . ($estimatedWaitTime % 60) . 'น';
                                    } else {
                                        echo $estimatedWaitTime . ' นาที';
                                    }
                                    ?>
                                </div>
                                <div class="queue-info-label">เวลารอโดยประมาณ</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="queue-info-card">
                            <div class="queue-info-icon">
                                <i class="fas fa-calendar-day text-success"></i>
                            </div>
                            <div class="queue-info-content">
                                <div class="queue-info-number"><?php echo $totalQueuesToday; ?></div>
                                <div class="queue-info-label">คิวทั้งหมดวันนี้</div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($totalQueuesAhead > 0): ?>
                    <div class="queue-alert mt-3">
                        <div class="alert alert-info d-flex align-items-center">
                            <i class="fas fa-info-circle me-2"></i>
                            <div>
                                <strong>มีคิวรออยู่ข้างหน้า <?php echo $totalQueuesAhead; ?> คิว</strong><br>
                                <small>คาดว่าจะถึงคิวของคุณในอีกประมาณ <?php echo $estimatedWaitTime; ?> นาที</small>
                            </div>
                        </div>
                    </div>
                <?php elseif ($queue['current_status'] == 'waiting'): ?>
                    <div class="queue-alert mt-3">
                        <div class="alert alert-success d-flex align-items-center">
                            <i class="fas fa-star me-2"></i>
                            <div>
                                <strong>คิวของคุณถึงแล้ว!</strong><br>
                                <small>กรุณาเตรียมตัวเข้ารับบริการ</small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (($queue['ticket_template'] ?? 'standard') === 'appointment_list'): ?>
                <div class="appointment-section">
                    <h4 class="section-title">
                        <i class="fas fa-calendar-check text-primary"></i>
                        <span>รายการนัดวันนี้</span>
                    </h4>

                    <?php if ($appointmentPatientLine !== ''): ?>
                        <div class="appointment-patient">
                            <strong><?php echo htmlspecialchars($appointmentPatientLine); ?></strong>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($appointmentsToday)): ?>
                        <div class="appointment-list">
                            <?php foreach ($appointmentsToday as $appointment): ?>
                                <?php
                                $timeText = trim((string) ($appointment['time_range'] ?? $appointment['start_time'] ?? ''));
                                $metadata = isset($appointment['metadata']) && is_array($appointment['metadata']) ? $appointment['metadata'] : [];
                                $clinicText = trim((string) ($metadata['clinic_name'] ?? ($appointment['clinic_name'] ?? ($appointment['department'] ?? ''))));
                                $causeText = trim((string) ($metadata['app_cause'] ?? ($appointment['cause'] ?? '')));
                                $detailText = trim(implode(' ', array_filter([$clinicText, $causeText], static function ($part) {
                                    return trim((string) $part) !== '';
                                })));
                                ?>
                                <div class="appointment-entry">
                                    <div class="appointment-entry-line">
                                        <span class="appointment-entry-time"><?php echo htmlspecialchars($timeText !== '' ? $timeText : '--:--'); ?></span>
                                        <?php if ($detailText !== ''): ?>
                                            <span class="appointment-entry-detail"><?php echo htmlspecialchars($detailText); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($appointmentError !== ''): ?>
                        <div class="alert alert-warning d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle me-2 mt-1"></i>
                            <div><?php echo htmlspecialchars($appointmentError); ?></div>
                        </div>
                    <?php elseif ($appointmentLookupPerformed): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>ไม่พบรายการนัดสำหรับวันนี้
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

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
                        <?php
                        // เรียงลำดับประวัติจากใหม่ไปเก่า
                        $sortedHistory = array_reverse($flowHistory);
                        foreach ($sortedHistory as $step):
                            // ข้ามรายการที่ไม่มีจุดบริการปลายทาง
                            if (empty($step['to_point_name']))
                                continue;

                            // กำหนดสถานะของขั้นตอน
                            $stepStatus = 'pending';
                            if ($step['action'] == 'completed') {
                                $stepStatus = 'completed';
                            } else if ($step['to_service_point_id'] == $queue['current_service_point_id']) {
                                $stepStatus = 'current';
                            }
                            ?>
                            <div class="flow-step <?php echo $stepStatus; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php if ($stepStatus == 'completed'): ?>
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                            <?php elseif ($stepStatus == 'current'): ?>
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
                                                case 'processing':
                                                    echo 'กำลังให้บริการ';
                                                    break;
                                                case 'waiting':
                                                    echo 'รอเรียก';
                                                    break;
                                                default:
                                                    echo htmlspecialchars($step['action']);
                                            }
                                            ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted d-block">
                                            <?php echo date('H:i', strtotime($step['timestamp'])); ?>
                                        </small>
                                        <small class="text-muted d-block d-md-none">
                                            <?php echo date('d/m/y', strtotime($step['timestamp'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            ไม่พบประวัติการให้บริการ
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
                        <li><a class="dropdown-item"
                                href="api/export_timeline.php?queue_id=<?php echo $queue['queue_id']; ?>&format=json"
                                target="_blank">
                                <i class="fas fa-file-code me-2"></i>JSON
                            </a></li>
                        <li><a class="dropdown-item"
                                href="api/export_timeline.php?queue_id=<?php echo $queue['queue_id']; ?>&format=csv"
                                target="_blank">
                                <i class="fas fa-file-csv me-2"></i>CSV
                            </a></li>
                        <li><a class="dropdown-item"
                                href="api/export_timeline.php?queue_id=<?php echo $queue['queue_id']; ?>&format=html"
                                target="_blank">
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
        $(document).ready(function () {
            const queueId = <?php echo json_encode($queue['queue_id']); ?>;
            const qrData = window.location.href; // Use current URL

            // Function to create QR Code
            function createQRCode() {
                // Load QRious library
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js';
                script.onload = function () {
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

                script.onerror = function () {
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
        setTimeout(function () {
            location.reload();
        }, 30000);
    </script>

    <!-- Fallback script if main QR library fails -->
    <script>
        // Check if QRCode library loaded, if not load alternative
        if (typeof QRCode === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js';
            script.onload = function () {
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

</body>

</html>