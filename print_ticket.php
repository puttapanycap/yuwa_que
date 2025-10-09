<?php
require_once 'config/config.php';

$queueId = filter_input(INPUT_GET, 'queue_id', FILTER_VALIDATE_INT);
if (!$queueId) {
    http_response_code(400);
    exit('ไม่พบรหัสคิวที่ต้องการพิมพ์');
}

$format = strtolower((string)($_GET['format'] ?? ''));
$copies = filter_input(INPUT_GET, 'copies', FILTER_VALIDATE_INT, [
    'options' => [
        'default' => 1,
        'min_range' => 1,
    ],
]);

try {
    $db = getDB();
    $stmt = $db->prepare(<<<'SQL'
        SELECT
            q.queue_number,
            q.created_at,
            qt.type_name,
            qt.prefix,
            (
                SELECT COUNT(*)
                FROM queues
                WHERE queue_type_id = q.queue_type_id
                    AND current_status = 'waiting'
                    AND queue_id < q.queue_id
            ) AS waiting_count
        FROM queues q
        INNER JOIN queue_types qt ON q.queue_type_id = qt.queue_type_id
        WHERE q.queue_id = ?
    SQL);
    $stmt->execute([$queueId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        http_response_code(404);
        exit('ไม่พบคิวที่ระบุ');
    }

    $hospitalName = getSetting('hospital_name', 'โรงพยาบาลยุวประสาทไวทโยปถัมภ์');
} catch (Throwable $exception) {
    http_response_code(500);
    exit('เกิดข้อผิดพลาดในการดึงข้อมูลคิว: ' . $exception->getMessage());
}

$createdAt = new DateTimeImmutable($ticket['created_at']);
$queueDateTime = $createdAt->format('d/m/Y H:i:s');
$qrCodeUrl = rtrim((string)BASE_URL, '/') . '/check_status.php?queue_id=' . $queueId;

if ($format === 'pdf') {
    $autoloadPath = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
    }

    if (!class_exists('TCPDF')) {
        http_response_code(500);
        exit('ไม่พบไลบรารี TCPDF กรุณาติดตั้ง Composer dependencies');
    }

    $pdf = new TCPDF('P', 'mm', [80, 140], true, 'UTF-8', false);
    $pdf->SetCreator('Yuwa Queue System');
    $pdf->SetAuthor($hospitalName);
    $pdf->SetTitle('Queue Ticket ' . $ticket['queue_number']);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(5, 8, 5);
    $pdf->SetAutoPageBreak(true, 6);
    $pdf->AddPage();

    $pdf->SetFont('freeserif', 'B', 16);
    $pdf->MultiCell(0, 0, $hospitalName, 0, 'C', false, 1, '', '', true, 0, false, true, 0, 'M');
    $pdf->Ln(3);

    $pdf->SetFont('freeserif', '', 13);
    $pdf->MultiCell(0, 0, 'บริการ: ' . $ticket['type_name'], 0, 'C', false, 1);
    $pdf->Ln(2);

    $pdf->SetFont('freeserif', 'B', 38);
    $pdf->MultiCell(0, 0, $ticket['queue_number'], 0, 'C', false, 1);
    $pdf->Ln(1);

    $waitingText = 'จำนวนคิวรอ: ' . number_format((int)$ticket['waiting_count']) . ' คิว';
    $pdf->SetFont('freeserif', '', 12);
    $pdf->MultiCell(0, 0, $waitingText, 0, 'C', false, 1);
    $pdf->Ln(4);

    $pdf->SetFont('freeserif', '', 12);
    $pdf->MultiCell(0, 0, 'วันที่: ' . $queueDateTime, 0, 'C', false, 1);
    $pdf->Ln(6);

    $qrSize = 36;
    $qrX = ($pdf->getPageWidth() - $qrSize) / 2;
    $qrStyle = [
        'border' => 0,
        'padding' => 0,
        'fgcolor' => [0, 0, 0],
        'bgcolor' => false,
        'module_width' => 1,
        'module_height' => 1,
    ];
    $pdf->write2DBarcode($qrCodeUrl, 'QRCODE,H', $qrX, $pdf->GetY(), $qrSize, $qrSize, $qrStyle, 'N');
    $pdf->Ln($qrSize + 2);

    $pdf->SetFont('freeserif', '', 11);
    $pdf->MultiCell(0, 0, 'สแกน QR Code เพื่อตรวจสอบสถานะคิว', 0, 'C', false, 1);
    $pdf->Ln(3);

    $pdf->SetFont('freeserif', '', 10);
    $pdf->MultiCell(0, 0, 'กรุณารอเรียกคิวจากเจ้าหน้าที่ ขอบคุณค่ะ', 0, 'C', false, 1);

    $pdfContent = $pdf->Output('', 'S');

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="queue-ticket-' . $ticket['queue_number'] . '.pdf"');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Length: ' . strlen($pdfContent));

    echo $pdfContent;
    exit;
}

$scriptSettings = [
    'copies' => $copies,
    'queueNumber' => $ticket['queue_number'],
    'serviceType' => $ticket['type_name'],
    'waitingCount' => (int)$ticket['waiting_count'],
    'issuedAt' => $queueDateTime,
    'pdfPath' => 'print_ticket.php?queue_id=' . $queueId . '&format=pdf',
];

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>บัตรคิว <?php echo htmlspecialchars($ticket['queue_number']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            color-scheme: light;
        }
        body {
            font-family: 'Sarabun', 'TH Sarabun New', 'Tahoma', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f6fa;
            color: #2c3e50;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        header {
            text-align: center;
            margin-bottom: 16px;
        }
        h1 {
            font-size: 1.4rem;
            margin: 0 0 0.5rem 0;
        }
        .ticket-info {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .ticket-info div {
            background: white;
            border-radius: 10px;
            padding: 12px 16px;
            min-width: 140px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            text-align: center;
        }
        .ticket-number {
            font-size: 2.8rem;
            font-weight: 700;
            color: #007bff;
        }
        #pdfPreview {
            flex: 1;
            width: 100%;
            min-height: 60vh;
            border: 1px solid #dcdfe6;
            border-radius: 12px;
            background: white;
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.03);
        }
        #statusMessage {
            margin-top: 16px;
            text-align: center;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <header>
        <h1><?php echo htmlspecialchars($hospitalName); ?></h1>
        <div>บัตรคิวสำหรับบริการ: <?php echo htmlspecialchars($ticket['type_name']); ?></div>
    </header>

    <section class="ticket-info">
        <div>
            <div>หมายเลขคิว</div>
            <div class="ticket-number"><?php echo htmlspecialchars($ticket['queue_number']); ?></div>
        </div>
        <div>
            <div>จำนวนคิวรอ</div>
            <div><?php echo number_format((int)$ticket['waiting_count']); ?> คิว</div>
        </div>
        <div>
            <div>ออกบัตรเมื่อ</div>
            <div><?php echo htmlspecialchars($queueDateTime); ?></div>
        </div>
    </section>

    <iframe id="pdfPreview" title="ตัวอย่างบัตรคิว"></iframe>
    <div id="statusMessage">กำลังเตรียมบัตรคิวสำหรับพิมพ์...</div>

    <script>
        (function() {
            const settings = <?php echo json_encode($scriptSettings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            const statusMessage = document.getElementById('statusMessage');
            const previewFrame = document.getElementById('pdfPreview');

            function buildPdfUrl(copyIndex) {
                const url = new URL(settings.pdfPath, window.location.href);
                url.searchParams.set('ts', Date.now().toString());
                if (typeof copyIndex === 'number') {
                    url.searchParams.set('copy', (copyIndex + 1).toString());
                }
                return url.toString();
            }

            function openFallbackTab(pdfUrl) {
                const newWindow = window.open(pdfUrl, '_blank');
                if (!newWindow) {
                    alert('ไม่สามารถเปิดหน้าต่างพิมพ์ได้ กรุณาปิดการบล็อกป๊อปอัป');
                }
            }

            function scheduleWindowClose() {
                setTimeout(() => {
                    try {
                        window.close();
                    } catch (error) {
                        console.warn('Unable to close window automatically:', error);
                    }
                }, 1200);
            }

            function printCopy(index) {
                statusMessage.textContent = `กำลังพิมพ์บัตรคิว (${index + 1}/${settings.copies})...`;
                const frame = document.createElement('iframe');
                frame.style.position = 'fixed';
                frame.style.right = '0';
                frame.style.bottom = '0';
                frame.style.width = '1px';
                frame.style.height = '1px';
                frame.style.opacity = '0';
                frame.setAttribute('title', 'พิมพ์บัตรคิวอัตโนมัติ');

                let cleaned = false;
                const cleanup = () => {
                    if (cleaned) {
                        return;
                    }
                    cleaned = true;
                    frame.remove();
                };

                frame.onload = () => {
                    try {
                        const printWindow = frame.contentWindow || frame;
                        if (!printWindow) {
                            throw new Error('ไม่พบหน้าต่างสำหรับพิมพ์');
                        }

                        const handleAfterPrint = () => {
                            cleanup();
                            const nextIndex = index + 1;
                            if (nextIndex < settings.copies) {
                                setTimeout(() => printCopy(nextIndex), 500);
                            } else {
                                statusMessage.textContent = 'พิมพ์บัตรคิวเสร็จเรียบร้อย';
                                scheduleWindowClose();
                            }
                        };

                        if ('onafterprint' in printWindow) {
                            printWindow.onafterprint = handleAfterPrint;
                        } else {
                            setTimeout(handleAfterPrint, 1500);
                        }

                        const printResult = printWindow.print();
                        if (printResult === false) {
                            throw new Error('เบราว์เซอร์ปฏิเสธคำสั่งพิมพ์อัตโนมัติ');
                        }
                    } catch (error) {
                        console.error('ไม่สามารถพิมพ์อัตโนมัติได้', error);
                        cleanup();
                        openFallbackTab(buildPdfUrl(index));
                        statusMessage.textContent = 'เปิดไฟล์ PDF ในแท็บใหม่เพื่อพิมพ์ด้วยตนเอง';
                    }
                };

                frame.onerror = () => {
                    cleanup();
                    openFallbackTab(buildPdfUrl(index));
                    statusMessage.textContent = 'ไม่สามารถโหลดไฟล์ PDF ได้ เปิดในแท็บใหม่เพื่อพิมพ์ด้วยตนเอง';
                };

                document.body.appendChild(frame);
                frame.src = buildPdfUrl(index);
            }

            function initialise() {
                try {
                    previewFrame.src = buildPdfUrl();
                } catch (error) {
                    console.warn('ไม่สามารถโหลดตัวอย่าง PDF ได้', error);
                }

                setTimeout(() => printCopy(0), 600);
            }

            if (document.readyState === 'complete') {
                initialise();
            } else {
                window.addEventListener('load', initialise);
            }
        })();
    </script>
</body>
</html>
