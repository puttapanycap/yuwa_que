<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

if (!hasPermission('manage_queues') && !hasPermission('manage_settings')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

$csrfToken = generateCSRFToken();
$defaultDate = date('Y-m-d');

$idcard = '';
$dateFrom = $defaultDate;
$dateTo = $defaultDate;
$apiResponse = null;
$errorMessage = '';
$successMessage = '';
$rawResponse = '';
$curlInfo = [];
$rawDisplay = '';

$apiUrl = env('APPOINTMENT_API_URL', 'https://apm.ycap.go.th/api/appointments/by-idcard');
$apiKey = env('APPOINTMENT_API_KEY', '');

$statusBadges = [
    '0' => ['label' => 'ยกเลิก', 'class' => 'badge bg-secondary'],
    '1' => ['label' => 'รอพบแพทย์', 'class' => 'badge bg-success'],
    '2' => ['label' => 'สำเร็จ', 'class' => 'badge bg-info'],
    '3' => ['label' => 'เลื่อนนัด', 'class' => 'badge bg-warning text-dark'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($submittedToken)) {
        $errorMessage = 'ไม่สามารถยืนยันความถูกต้องของคำขอได้ กรุณารีเฟรชหน้าและลองใหม่อีกครั้ง';
    } else {
        $idcard = preg_replace('/\D/', '', $_POST['idcard'] ?? '');
        $dateFrom = trim($_POST['date_from'] ?? '');
        $dateTo = trim($_POST['date_to'] ?? '');

        if ($idcard === '' || strlen($idcard) !== 13) {
            $errorMessage = 'กรุณากรอกเลขประจำตัวประชาชน 13 หลัก';
        } elseif ($dateFrom === '' || $dateTo === '') {
            $errorMessage = 'กรุณาเลือกช่วงวันที่ที่ต้องการตรวจสอบ';
        } else {
            $start = DateTime::createFromFormat('Y-m-d', $dateFrom);
            $end = DateTime::createFromFormat('Y-m-d', $dateTo);

            if (!$start || $start->format('Y-m-d') !== $dateFrom) {
                $errorMessage = 'รูปแบบวันที่เริ่มต้นไม่ถูกต้อง';
            } elseif (!$end || $end->format('Y-m-d') !== $dateTo) {
                $errorMessage = 'รูปแบบวันที่สิ้นสุดไม่ถูกต้อง';
            } elseif ($start > $end) {
                $errorMessage = 'วันที่เริ่มต้นต้องไม่มากกว่าวันที่สิ้นสุด';
            }
        }

        if ($errorMessage === '') {
            if ($apiKey === '') {
                $errorMessage = 'ยังไม่ได้กำหนดค่า APPOINTMENT_API_KEY ในไฟล์ .env';
            } else {
                $payload = [
                    'idcard' => $idcard,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ];

                try {
                    $ch = curl_init($apiUrl);

                    if ($ch === false) {
                        throw new Exception('ไม่สามารถเริ่มการเชื่อมต่อ cURL ได้');
                    }

                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_HTTPHEADER => array_filter([
                            'Content-Type: application/json',
                            $apiKey !== '' ? 'X-API-Key: ' . $apiKey : null,
                        ]),
                        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
                        CURLOPT_TIMEOUT => 15,
                        CURLOPT_CONNECTTIMEOUT => 5,
                    ]);

                    $response = curl_exec($ch);
                    $curlErrNo = curl_errno($ch);
                    $curlErrMsg = curl_error($ch);
                    $curlInfo = curl_getinfo($ch) ?: [];
                    curl_close($ch);

                    if ($response === false) {
                        throw new Exception('การเรียก API ล้มเหลว: ' . ($curlErrMsg ?: 'ไม่ทราบสาเหตุ') . " (รหัสข้อผิดพลาด: $curlErrNo)");
                    }

                    $httpCode = $curlInfo['http_code'] ?? 0;
                    $rawResponse = $response;
                    $decoded = json_decode($response, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new Exception('ไม่สามารถแปลงข้อมูล JSON ได้: ' . json_last_error_msg());
                    }

                    if ($httpCode < 200 || $httpCode >= 300) {
                        $apiMessage = isset($decoded['message']) ? $decoded['message'] : 'API ตอบกลับสถานะ ' . $httpCode;
                        throw new Exception($apiMessage);
                    }

                    if (!($decoded['ok'] ?? false)) {
                        $apiMessage = $decoded['message'] ?? 'API แจ้งว่าไม่พบข้อมูล';
                        throw new Exception($apiMessage);
                    }

                    $apiResponse = $decoded;
                    $appointmentCount = count($apiResponse['appointments'] ?? []);
                    $successMessage = sprintf('พบ %d รายการนัดหมาย', $appointmentCount);

                    logActivity(sprintf('ตรวจสอบนัดหมาย (%s - %s) ผ่านระบบจัดการนัด', $dateFrom, $dateTo));
                } catch (Exception $e) {
                    $errorMessage = $e->getMessage();
                }
            }
        }
    }
}

$previewPayload = [
    'idcard' => $idcard !== '' ? $idcard : '1111111111111',
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
];

$rawDisplay = $rawResponse;
if ($rawResponse !== '') {
    $decodedRaw = json_decode($rawResponse, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $rawDisplay = json_encode($decodedRaw, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

$maskedKey = $apiKey !== '' ? substr($apiKey, 0, 6) . str_repeat('*', max(strlen($apiKey) - 6, 0)) : 'ยังไม่กำหนด';
$curlPreview = "curl --location '" . $apiUrl . "' \\\n --header 'Content-Type: application/json' \\\n --header 'X-API-Key: " . $maskedKey . "' \\\n --data '" . json_encode($previewPayload, JSON_UNESCAPED_UNICODE) . "'";

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการนัด - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
        }

        .nav-sidebar {
            background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%);
            min-height: 100vh;
            padding: 0;
        }

        .nav-sidebar .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 1rem 1.5rem;
            transition: all 0.3s;
        }

        .nav-sidebar .nav-link:hover,
        .nav-sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.15);
        }

        .nav-sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }

        .card-form {
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(91, 134, 229, 0.1);
        }

        .card-form .card-header {
            background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%);
            color: #fff;
            border-radius: 16px 16px 0 0;
        }

        .card-result {
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .appointment-card {
            border-radius: 14px;
            border: 1px solid #e5ecff;
            padding: 1.5rem;
            background: #fff;
            box-shadow: 0 10px 25px rgba(72, 128, 255, 0.08);
        }

        .appointment-card + .appointment-card {
            margin-top: 1.5rem;
        }

        .appointment-time {
            font-weight: 600;
            font-size: 1.1rem;
            color: #3a4cb1;
        }

        .metadata-preview {
            background: #0f172a;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            max-height: 300px;
            overflow: auto;
        }

        .curl-preview {
            background: #111827;
            color: #f9fafb;
            padding: 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .badge {
            font-size: 0.85rem;
            padding: 0.5rem 0.85rem;
            border-radius: 999px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-3 col-xl-2 nav-sidebar d-none d-lg-block">
            <?php include 'nav.php'; ?>
        </div>
        <div class="col-lg-9 col-xl-10 ms-auto px-4 py-5">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">จัดการนัด</h2>
                    <p class="text-muted mb-0">ค้นหานัดหมายของผู้ป่วยผ่านการเชื่อมต่อ API ระบบจัดการนัดหมาย</p>
                </div>
                <div class="mt-3 mt-lg-0">
                    <span class="badge bg-primary">API URL: <?php echo htmlspecialchars($apiUrl); ?></span>
                </div>
            </div>

            <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-triangle-exclamation me-2"></i><?php echo htmlspecialchars($errorMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($successMessage): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-circle-check me-2"></i><?php echo htmlspecialchars($successMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($apiKey === ''): ?>
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-key me-2"></i>ยังไม่ได้กำหนดค่า <code>APPOINTMENT_API_KEY</code> ในไฟล์ <code>.env</code> ระบบจะไม่สามารถเชื่อมต่อ API ได้
                </div>
            <?php endif; ?>

            <div class="card card-form mb-4">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-magnifying-glass me-2"></i>ค้นหานัดหมายผู้ป่วย</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" class="row g-4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <div class="col-md-4">
                            <label for="idcard" class="form-label fw-semibold">เลขประจำตัวประชาชน</label>
                            <input type="text" name="idcard" id="idcard" class="form-control" inputmode="numeric" pattern="\d{13}" maxlength="13" required value="<?php echo htmlspecialchars($idcard); ?>" placeholder="เช่น 1111111111111">
                            <div class="form-text">กรอกตัวเลข 13 หลักโดยไม่ต้องเว้นวรรค</div>
                        </div>
                        <div class="col-md-4">
                            <label for="date_from" class="form-label fw-semibold">วันที่เริ่มต้น</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" required value="<?php echo htmlspecialchars($dateFrom); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="date_to" class="form-label fw-semibold">วันที่สิ้นสุด</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" required value="<?php echo htmlspecialchars($dateTo); ?>">
                        </div>
                        <div class="col-12 d-flex flex-column flex-md-row align-items-md-end justify-content-between">
                            <div class="mb-3 mb-md-0">
                                <button type="submit" class="btn btn-primary btn-lg px-4"><i class="fas fa-search me-2"></i>ค้นหานัดหมาย</button>
                            </div>
                            <div class="ms-md-4 flex-grow-1">
                                <label class="form-label fw-semibold">ตัวอย่างคำสั่ง cURL ที่ระบบใช้งาน</label>
                                <pre class="curl-preview mb-0"><?php echo htmlspecialchars($curlPreview); ?></pre>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($apiResponse): ?>
                <div class="card card-result mb-4">
                    <div class="card-header bg-white py-3 px-4">
                        <h5 class="mb-0"><i class="fas fa-user me-2 text-primary"></i>ข้อมูลผู้ป่วย</h5>
                    </div>
                    <div class="card-body px-4 py-4">
                        <?php $patient = $apiResponse['patient'] ?? []; ?>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="text-muted">HN</div>
                                <div class="fw-semibold fs-5"><?php echo htmlspecialchars($patient['hn'] ?? '-'); ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted">ชื่อ-สกุล</div>
                                <div class="fw-semibold fs-5"><?php echo htmlspecialchars($patient['fullname_th'] ?? '-'); ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted">เลขประจำตัวประชาชน</div>
                                <div class="fw-semibold fs-6"><?php echo htmlspecialchars($patient['idcard'] ?? '-'); ?></div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-muted">วันเกิด</div>
                                <div class="fw-semibold fs-6"><?php echo htmlspecialchars($patient['birthday'] ?? '-'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-result mb-4">
                    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-calendar-check me-2 text-success"></i>รายการนัดหมาย</h5>
                        <span class="badge bg-success">ทั้งหมด <?php echo count($apiResponse['appointments'] ?? []); ?> รายการ</span>
                    </div>
                    <div class="card-body px-4 py-4">
                        <?php if (empty($apiResponse['appointments'])): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                <p class="mb-0">ไม่พบนัดหมายในช่วงวันที่ที่เลือก</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($apiResponse['appointments'] as $appointment): ?>
                                <?php
                                    $startDt = !empty($appointment['datetime']) ? new DateTime($appointment['datetime']) : null;
                                    $endDt = !empty($appointment['end_datetime']) ? new DateTime($appointment['end_datetime']) : null;
                                    $statusCode = (string)($appointment['status'] ?? '');
                                    $badge = $statusBadges[$statusCode] ?? ['label' => ($appointment['status'] ?? 'ไม่ระบุ'), 'class' => 'badge bg-dark'];
                                ?>
                                <div class="appointment-card">
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                                        <div>
                                            <div class="appointment-time">
                                                <?php echo $startDt ? htmlspecialchars($startDt->format('d/m/Y H:i')) : '-'; ?>
                                                <?php if ($endDt && $endDt != $startDt): ?>
                                                    <span class="text-muted">- <?php echo htmlspecialchars($endDt->format('d/m/Y H:i')); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted">
                                                <i class="fas fa-building me-1"></i><?php echo htmlspecialchars($appointment['department'] ?? '-'); ?>
                                                <?php if (!empty($appointment['clinic'])): ?>
                                                    <span class="ms-2">(คลินิก: <?php echo htmlspecialchars($appointment['clinic']); ?>)</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="mt-3 mt-md-0">
                                            <span class="<?php echo $badge['class']; ?>"><?php echo htmlspecialchars($badge['label']); ?></span>
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="text-muted">แพทย์ผู้ตรวจ</div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($appointment['doctor'] ?? '-'); ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-muted">รหัสนัด</div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars((string)($appointment['appointment_id'] ?? '-')); ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-muted">ระยะเวลา (นาที)</div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars((string)($appointment['duration_min'] ?? '0')); ?></div>
                                        </div>
                                    </div>
                                    <?php if (!empty($appointment['notes'])): ?>
                                        <div class="mt-3">
                                            <div class="text-muted">หมายเหตุ</div>
                                            <div><?php echo nl2br(htmlspecialchars($appointment['notes'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                        <span class="badge bg-light text-dark"><i class="fas fa-file me-1"></i>เอกสาร <?php echo htmlspecialchars((string)($appointment['documents_count'] ?? 0)); ?> รายการ</span>
                                        <?php if (!empty($appointment['metadata']['clinic_name'])): ?>
                                            <span class="badge bg-light text-dark"><i class="fas fa-hospital me-1"></i><?php echo htmlspecialchars($appointment['metadata']['clinic_name']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($appointment['metadata']['app_cause'])): ?>
                                            <span class="badge bg-light text-dark"><i class="fas fa-clipboard-list me-1"></i><?php echo htmlspecialchars($appointment['metadata']['app_cause']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($appointment['metadata'])): ?>
                                        <details class="mt-3">
                                            <summary class="text-primary fw-semibold" style="cursor: pointer;">ดู Metadata เพิ่มเติม</summary>
                                            <pre class="metadata-preview mt-2"><?php echo htmlspecialchars(json_encode($appointment['metadata'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
                                        </details>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($rawResponse !== ''): ?>
                <div class="card card-result mb-4">
                    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-code me-2 text-info"></i>รายละเอียดการเชื่อมต่อ</h5>
                    </div>
                    <div class="card-body px-4 py-4">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <h6 class="fw-semibold">ข้อมูลที่ได้รับจาก API</h6>
                                <pre class="metadata-preview"><?php echo htmlspecialchars($rawDisplay); ?></pre>
                            </div>
                            <div class="col-lg-6">
                                <h6 class="fw-semibold">ข้อมูลการตอบกลับของ cURL</h6>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between"><span>HTTP Status</span><span class="fw-semibold"><?php echo htmlspecialchars((string)($curlInfo['http_code'] ?? '-')); ?></span></li>
                                    <li class="list-group-item d-flex justify-content-between"><span>ระยะเวลา (วินาที)</span><span class="fw-semibold"><?php echo htmlspecialchars(number_format((float)($curlInfo['total_time'] ?? 0), 3)); ?></span></li>
                                    <li class="list-group-item d-flex justify-content-between"><span>ไอพีปลายทาง</span><span class="fw-semibold"><?php echo htmlspecialchars($curlInfo['primary_ip'] ?? '-'); ?></span></li>
                                    <li class="list-group-item d-flex justify-content-between"><span>ขนาดข้อมูลที่รับ (ไบต์)</span><span class="fw-semibold"><?php echo htmlspecialchars((string)($curlInfo['size_download'] ?? 0)); ?></span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
