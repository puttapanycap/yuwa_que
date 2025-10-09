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
    <style>
        :root {
            --ticket-width: 80mm;
            --ticket-padding: 12px;
        }

        /* --- General Styles --- */
        body {
            font-family: 'Sarabun', 'TH Sarabun New', 'Tahoma', 'Arial', sans-serif;
            margin: 0;
            padding: 20px var(--ticket-padding);
            background-color: #f0f0f0;
            display: flex;
            justify-content: center;
        }

        html, body {
            width: 100%;
            box-sizing: border-box;
        }

        * {
            box-sizing: inherit;
        }

        /* --- Ticket Styles for Screen Preview --- */
        .ticket-container {
            width: min(100%, var(--ticket-width));
            background-color: #ffffff;
            border: 1px dashed #ccc;
            text-align: center;
            padding: 18px 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
            margin: 0 auto;
        }

        /* --- Print Styles --- */
        @media print {
            html, body {
                width: var(--ticket-width);
                margin: 0;
                padding: 0;
                background: #ffffff !important;
            }

            body {
                display: block; /* Override flex for printing */
                -webkit-print-color-adjust: exact;
            }

            body > *:not(.ticket-container) {
                display: none !important;
            }

            @page {
                size: var(--ticket-width) auto; /* Thermal roll width 80mm */
                margin: 0; /* Let printer handle its own margins */
            }

            .ticket-container {
                width: var(--ticket-width);
                min-height: 120mm;
                border: none;
                box-shadow: none;
                padding: 12px;
                margin: 0 auto;
                page-break-after: always; /* Force paper cut per ticket */
            }

            .ticket-footer .qr-code img {
                width: 100px;
                height: 100px;
            }

            /* Extra feed at the end to ensure auto-cutter engages */
            body::after {
                content: "";
                display: block;
                height: 6mm;
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

        .ticket-footer .qr-code img {
            max-width: 100%;
            height: auto;
        }

        hr {
            border: none;
            border-top: 1px dashed #bfbfbf;
            margin: 12px auto;
            width: 90%;
        }
    </style>
</head>
<body>

    <script>
        (function() {
            const androidPrinter = window.AndroidPrinter;
            const androidPrinterAvailable = !!(androidPrinter && typeof androidPrinter.printCurrentPage === 'function');

            const triggerPrint = () => {
                if (androidPrinterAvailable) {
                    try {
                        androidPrinter.printCurrentPage('Yuwa Queue Ticket');
                        return;
                    } catch (error) {
                        console.error('Android printing failed, falling back to window.print():', error);
                    }
                }

                window.focus();
                window.print();
            };

            if (document.readyState === 'complete') {
                setTimeout(triggerPrint, 300);
            } else {
                window.addEventListener('load', () => setTimeout(triggerPrint, 300));
            }

            if (!androidPrinterAvailable) {
                const closeWindow = () => setTimeout(() => window.close(), 600);

                if ('onafterprint' in window) {
                    window.addEventListener('afterprint', closeWindow);
                } else {
                    const mediaQueryList = window.matchMedia('print');
                    if (mediaQueryList && mediaQueryList.addListener) {
                        mediaQueryList.addListener((mql) => {
                            if (!mql.matches) {
                                closeWindow();
                            }
                        });
                    }
                }
            }
        })();
    </script>

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
