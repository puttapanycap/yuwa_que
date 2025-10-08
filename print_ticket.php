<?php
require_once 'config/config.php';

$queue_id = filter_input(INPUT_GET, 'queue_id', FILTER_VALIDATE_INT);

if (!$queue_id) {
    die('ไม่พบรหัสคิว');
}

try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT 
            q.queue_number,
            q.created_at,
            qt.type_name,
            qt.prefix,
            (SELECT COUNT(*) FROM queues WHERE queue_type_id = q.queue_type_id AND current_status = 'waiting' AND queue_id < q.queue_id) as waiting_count
        FROM queues q
        JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
        WHERE q.queue_id = ?
    ");
    $stmt->execute([$queue_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        die('ไม่พบคิวที่ระบุ');
    }

    // ดึงชื่อโรงพยาบาลจากการตั้งค่า
    $hospitalName = getSetting('hospital_name', 'โรงพยาบาลยุวประสาทไวทโยปถัมภ์');

} catch (Exception $e) {
    die('เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: ' . $e->getMessage());
}

$qrCodeUrl = BASE_URL . '/check_status.php?queue_id=' . $queue_id;

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บัตรคิว <?php echo htmlspecialchars($ticket['queue_number']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* --- General Styles --- */
        body {
            font-family: 'Sarabun', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        /* --- Ticket Styles for Screen Preview --- */
        .ticket-container {
            background-color: white;
            border: 1px dashed #ccc;
            text-align: center;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        /* --- Print Styles --- */
        @media print {
            /* ซ่อน element ที่ไม่ต้องการพิมพ์ */
            body > *:not(.ticket-container) {
                display: none;
            }

            /* ตั้งค่าหน้ากระดาษ */
            @page {
                size: 80mm 140mm; /* กว้าง 8cm, สูง 14cm */
                margin: 5mm; /* ตั้งค่าขอบกระดาษ */
            }

            body {
                background-color: white;
                margin: 0;
                padding: 0;
                display: block; /* Override flex for printing */
            }

            .ticket-container {
                width: 100%;
                height: 100%;
                border: none;
                box-shadow: none;
                padding: 0;
                margin: 0;
                page-break-after: always; /* ขึ้นหน้าใหม่สำหรับแต่ละบัตรคิว */
            }
        }

        /* --- Ticket Content Styles (Both Screen and Print) --- */
        .ticket-header h1 {
            font-size: 1.2em;
            font-weight: 700;
            margin: 0 0 5px 0;
        }

        .ticket-body .service-type {
            font-size: 1.5em;
            font-weight: 700;
            margin: 10px 0;
        }

        .ticket-body .queue-number {
            font-size: 4em;
            font-weight: 700;
            margin: 5px 0;
        }

        .ticket-body .waiting-count {
            font-size: 1.2em;
            margin-bottom: 15px;
        }

        .ticket-footer {
            font-size: 0.9em;
            margin-top: 15px;
        }

        .ticket-footer .qr-code {
            margin-top: 10px;
        }
    </style>
</head>
<body onload="window.print()">

    <div class="ticket-container">
        <div class="ticket-header">
            <h1><?php echo htmlspecialchars($hospitalName); ?></h1>
        </div>
        <hr>
        <div class="ticket-body">
            <div class="service-type"><?php echo htmlspecialchars($ticket['type_name']); ?></div>
            <div class="queue-number"><?php echo htmlspecialchars($ticket['queue_number']); ?></div>
            <div class="waiting-count">จำนวนคิวรอ: <?php echo $ticket['waiting_count']; ?> คิว</div>
        </div>
        <hr>
        <div class="ticket-footer">
            <div>วันที่: <?php echo date('d/m/Y H:i:s', strtotime($ticket['created_at'])); ?></div>
            <div class="qr-code">
                <p>สแกนเพื่อตรวจสอบสถานะคิว</p>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?php echo urlencode($qrCodeUrl); ?>" alt="QR Code">
            </div>
        </div>
    </div>

</body>
</html>