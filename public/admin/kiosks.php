<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

if (!hasPermission('manage_settings')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

ensureKioskDevicesTableExists();

$formDefaults = [
    'kiosk_id' => '',
    'kiosk_name' => '',
    'cookie_token' => '',
    'printer_ip' => '',
    'printer_port' => '9100',
    'notes' => '',
    'is_active' => 1,
];

$message = '';
$messageType = '';
$formValues = $formDefaults;
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'ไม่สามารถยืนยันความปลอดภัยของแบบฟอร์มได้ กรุณาลองอีกครั้ง';
        $messageType = 'danger';
    } else {
        try {
            switch ($action) {
                case 'save_kiosk':
                    $kioskId = isset($_POST['kiosk_id']) ? (int) $_POST['kiosk_id'] : 0;
                    $kioskName = trim($_POST['kiosk_name'] ?? '');
                    $rawCookieToken = trim($_POST['cookie_token'] ?? '');
                    $rawCookieToken = preg_replace('/\s+/', '', $rawCookieToken);
                    $cookieToken = sanitizeKioskToken($rawCookieToken);
                    $printerIp = trim($_POST['printer_ip'] ?? '');
                    $printerPort = $_POST['printer_port'] !== '' ? (int) $_POST['printer_port'] : 9100;
                    $notes = trim($_POST['notes'] ?? '');
                    $isActive = isset($_POST['is_active']) ? 1 : 0;

                    $formValues = [
                        'kiosk_id' => $kioskId,
                        'kiosk_name' => $kioskName,
                        'cookie_token' => $cookieToken ?? $rawCookieToken,
                        'printer_ip' => $printerIp,
                        'printer_port' => (string) $printerPort,
                        'notes' => $notes,
                        'is_active' => $isActive,
                    ];
                    $editId = $kioskId ?: null;

                    if ($kioskName === '') {
                        throw new Exception('กรุณากรอกชื่อเครื่อง Kiosk');
                    }

                    if ($cookieToken === null) {
                        throw new Exception('รหัส Cookie ไม่ถูกต้อง กรุณาคัดลอกจากหน้าจอ Kiosk อีกครั้ง');
                    }

                    if ($printerIp !== '' && !filter_var($printerIp, FILTER_VALIDATE_IP)) {
                        throw new Exception('รูปแบบ IP Address ของเครื่องพิมพ์ไม่ถูกต้อง');
                    }

                    if ($printerPort < 1 || $printerPort > 65535) {
                        throw new Exception('หมายเลขพอร์ตต้องอยู่ระหว่าง 1 - 65535');
                    }

                    $db = getDB();

                    if ($kioskId > 0) {
                        $duplicateStmt = $db->prepare('SELECT id FROM kiosk_devices WHERE cookie_token = ? AND id != ? LIMIT 1');
                        $duplicateStmt->execute([$cookieToken, $kioskId]);
                    } else {
                        $duplicateStmt = $db->prepare('SELECT id FROM kiosk_devices WHERE cookie_token = ? LIMIT 1');
                        $duplicateStmt->execute([$cookieToken]);
                    }

                    if ($duplicateStmt->fetch()) {
                        throw new Exception('รหัส Cookie นี้ถูกใช้งานแล้วในระบบ');
                    }

                    if ($kioskId > 0) {
                        $stmt = $db->prepare('UPDATE kiosk_devices SET kiosk_name = ?, cookie_token = ?, printer_ip = ?, printer_port = ?, is_active = ?, notes = ?, updated_at = NOW() WHERE id = ?');
                        $stmt->execute([$kioskName, $cookieToken, $printerIp ?: null, $printerPort, $isActive, $notes ?: null, $kioskId]);

                        logActivity("อัปเดตเครื่อง Kiosk: {$kioskName} (#{$kioskId})");
                        $message = 'บันทึกข้อมูลเครื่อง Kiosk สำเร็จ';
                        $messageType = 'success';
                    } else {
                        $identifier = generateKioskIdentifier();
                        $attempts = 0;

                        do {
                            $identifierCheck = $db->prepare('SELECT id FROM kiosk_devices WHERE identifier = ? LIMIT 1');
                            $identifierCheck->execute([$identifier]);

                            if (!$identifierCheck->fetch()) {
                                break;
                            }

                            $identifier = generateKioskIdentifier();
                            $attempts++;
                        } while ($attempts < 5);

                        if ($attempts >= 5) {
                            throw new Exception('ไม่สามารถสร้างรหัสเครื่อง Kiosk ที่ไม่ซ้ำได้ กรุณาลองใหม่อีกครั้ง');
                        }

                        $stmt = $db->prepare('INSERT INTO kiosk_devices (kiosk_name, cookie_token, identifier, printer_ip, printer_port, is_active, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$kioskName, $cookieToken, $identifier, $printerIp ?: null, $printerPort, $isActive, $notes ?: null]);

                        logActivity("เพิ่มเครื่อง Kiosk: {$kioskName} ({$identifier})");
                        $message = 'เพิ่มเครื่อง Kiosk สำเร็จ';
                        $messageType = 'success';
                        $formValues = $formDefaults;
                        $editId = null;
                    }

                    break;

                case 'delete_kiosk':
                    $kioskId = isset($_POST['kiosk_id']) ? (int) $_POST['kiosk_id'] : 0;

                    if ($kioskId <= 0) {
                        throw new Exception('ไม่พบข้อมูลเครื่อง Kiosk ที่ต้องการลบ');
                    }

                    $db = getDB();
                    $stmt = $db->prepare('SELECT kiosk_name, identifier FROM kiosk_devices WHERE id = ? LIMIT 1');
                    $stmt->execute([$kioskId]);
                    $kiosk = $stmt->fetch();

                    if (!$kiosk) {
                        throw new Exception('ไม่พบเครื่อง Kiosk ที่ต้องการลบ');
                    }

                    $deleteStmt = $db->prepare('DELETE FROM kiosk_devices WHERE id = ?');
                    $deleteStmt->execute([$kioskId]);

                    logActivity("ลบเครื่อง Kiosk: {$kiosk['kiosk_name']} ({$kiosk['identifier']})");
                    $message = 'ลบเครื่อง Kiosk สำเร็จ';
                    $messageType = 'success';
                    $formValues = $formDefaults;
                    $editId = null;
                    break;

                default:
                    throw new Exception('ไม่พบคำสั่งที่ต้องการทำงาน');
            }
        } catch (Exception $e) {
            $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

try {
    $db = getDB();
    $stmt = $db->query('
        SELECT
            kd.*, 
            COUNT(q.queue_id) AS queue_count
        FROM
            kiosk_devices AS kd
            LEFT JOIN
            queues AS q
            ON 
                q.kiosk_id = kd.id
        GROUP BY
            kd.id
        ORDER BY
            kd.created_at DESC
    ');
    $kiosks = $stmt->fetchAll();
} catch (Exception $e) {
    $kiosks = [];
}

if ($editId) {
    foreach ($kiosks as $kiosk) {
        if ((int) $kiosk['id'] === $editId) {
            $formValues = [
                'kiosk_id' => $kiosk['id'],
                'kiosk_name' => $kiosk['kiosk_name'],
                'cookie_token' => $kiosk['cookie_token'],
                'printer_ip' => $kiosk['printer_ip'],
                'printer_port' => $kiosk['printer_port'] !== null ? (string) $kiosk['printer_port'] : '9100',
                'notes' => $kiosk['notes'] ?? '',
                'is_active' => (int) $kiosk['is_active'],
            ];
            break;
        }
    }
}

$csrfToken = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการเครื่อง Kiosk - <?php echo getAppName(); ?></title>

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

        .form-section-title {
            font-weight: 600;
            color: #0d6efd;
            margin-bottom: 1rem;
        }

        .token-preview {
            font-family: 'Courier New', Courier, monospace;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px dashed rgba(13, 110, 253, 0.35);
            letter-spacing: 1.5px;
            word-break: break-word;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.85rem;
        }

        .status-active {
            background: rgba(25, 135, 84, 0.15);
            color: #198754;
        }

        .status-inactive {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }

        .table thead th {
            background-color: #f1f3f5;
            color: #495057;
            font-weight: 600;
        }

        .table td {
            vertical-align: middle;
        }

        .note-text {
            max-width: 280px;
            white-space: pre-line;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="p-3">
                    <h5 class="text-white mb-4">
                        <i class="fas fa-tablet-screen-button me-2"></i>
                        เครื่อง Kiosk
                    </h5>

                    <?php include 'nav.php'; ?>
                </div>
            </div>

            <div class="col-md-9 col-lg-10 ms-sm-auto px-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-1">จัดการเครื่อง Kiosk</h2>
                        <p class="text-muted mb-0">ลงทะเบียนเครื่องใหม่และกำหนดค่าเครื่องพิมพ์เฉพาะสำหรับแต่ละเครื่อง</p>
                    </div>
                    <div class="text-end">
                        <a href="../index.php" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-desktop me-2"></i>เปิดหน้า Kiosk
                        </a>
                    </div>
                </div>

                <div class="alert alert-info d-flex align-items-start" role="alert">
                    <i class="fas fa-info-circle fa-lg me-3 mt-1"></i>
                    <div>
                        <strong>วิธีลงทะเบียนเครื่องใหม่:</strong>
                        <ol class="mb-0 mt-1 ps-3">
                            <li>เปิดหน้า Kiosk บนอุปกรณ์ที่ต้องการใช้งาน ระบบจะแสดงรหัส Cookie สำหรับเครื่องนั้น</li>
                            <li>คัดลอกรหัส Cookie แล้วบันทึกลงในแบบฟอร์มด้านล่าง พร้อมกำหนดชื่อและ IP Address ของเครื่องพิมพ์</li>
                            <li>บันทึกข้อมูล เมื่อสถานะเป็น "พร้อมใช้งาน" ให้รีเฟรชหน้า Kiosk เพื่อเริ่มต้นใช้งาน</li>
                        </ol>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($messageType ?: 'info', ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <div class="content-card">
                    <h5 class="form-section-title"><?php echo $formValues['kiosk_id'] ? 'แก้ไขเครื่อง Kiosk' : 'เพิ่มเครื่อง Kiosk ใหม่'; ?></h5>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="save_kiosk">
                        <input type="hidden" name="kiosk_id" value="<?php echo htmlspecialchars($formValues['kiosk_id'], ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="col-md-6">
                            <label class="form-label">ชื่อเครื่อง Kiosk <span class="text-danger">*</span></label>
                            <input type="text" name="kiosk_name" class="form-control" value="<?php echo htmlspecialchars($formValues['kiosk_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">สถานะการใช้งาน</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="isActiveSwitch" name="is_active" value="1" <?php echo !empty($formValues['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="isActiveSwitch">พร้อมใช้งาน</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">รหัส Cookie <span class="text-danger">*</span></label>
                            <input type="text" name="cookie_token" class="form-control" value="<?php echo htmlspecialchars($formValues['cookie_token'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="เช่น 8F0C4A2E..." required>
                            <div class="form-text">คัดลอกรหัสจากหน้า Kiosk (ตัวอักษรและตัวเลข 64 หลัก)</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">IP Address ของเครื่องพิมพ์</label>
                            <input type="text" name="printer_ip" class="form-control" value="<?php echo htmlspecialchars($formValues['printer_ip'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="เช่น 192.168.1.50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">พอร์ตของเครื่องพิมพ์</label>
                            <input type="number" name="printer_port" class="form-control" value="<?php echo htmlspecialchars($formValues['printer_port'], ENT_QUOTES, 'UTF-8'); ?>" min="1" max="65535">
                            <div class="form-text">ค่าเริ่มต้นทั่วไปคือ 9100 สำหรับเครื่องพิมพ์เครือข่าย</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">บันทึกเพิ่มเติม</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="เช่น ติดตั้งที่แผนกตรวจสุขภาพ"><?php echo htmlspecialchars($formValues['notes'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        </div>

                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>บันทึกข้อมูลเครื่อง
                            </button>
                            <?php if ($formValues['kiosk_id']): ?>
                                <a href="kiosks.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-plus me-2"></i>เพิ่มเครื่องใหม่
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="content-card">
                    <h5 class="form-section-title">รายการเครื่อง Kiosk ทั้งหมด</h5>
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>ชื่อเครื่อง</th>
                                    <th>รหัส Cookie</th>
                                    <th>รหัสภายใน</th>
                                    <th>เครื่องพิมพ์</th>
                                    <th>สถานะ</th>
                                    <th>ใช้งานครั้งสุดท้าย</th>
                                    <th class="text-end">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($kiosks)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">ยังไม่มีการลงทะเบียนเครื่อง Kiosk</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($kiosks as $kiosk): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($kiosk['kiosk_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php if (!empty($kiosk['notes'])): ?>
                                                    <div class="text-muted note-text small"><i class="fas fa-sticky-note me-1"></i><?php echo nl2br(htmlspecialchars($kiosk['notes'], ENT_QUOTES, 'UTF-8')); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="token-preview small mb-1"><?php echo htmlspecialchars(formatKioskTokenForDisplay($kiosk['cookie_token']), ENT_QUOTES, 'UTF-8'); ?></div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($kiosk['identifier'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                <?php $createdDisplay = !empty($kiosk['created_at']) ? date('d/m/Y H:i', strtotime($kiosk['created_at'])) : '-'; ?>
                                                <div class="text-muted small">สร้างแล้ว: <?php echo htmlspecialchars($createdDisplay, ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="text-muted small">บัตรคิวที่ออก: <?php echo (int) $kiosk['queue_count']; ?> รายการ</div>
                                            </td>
                                            <td>
                                                <?php if (!empty($kiosk['printer_ip'])): ?>
                                                    <div><i class="fas fa-network-wired me-1"></i><?php echo htmlspecialchars($kiosk['printer_ip'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                    <div class="text-muted small">พอร์ต: <?php echo htmlspecialchars($kiosk['printer_port'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php else: ?>
                                                    <span class="text-muted">ยังไม่กำหนด</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ((int) $kiosk['is_active'] === 1): ?>
                                                    <span class="status-badge status-active"><i class="fas fa-check-circle"></i>พร้อมใช้งาน</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-inactive"><i class="fas fa-pause-circle"></i>ปิดการใช้งาน</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($kiosk['last_seen_at'])): ?>
                                                    <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($kiosk['last_seen_at'])), ENT_QUOTES, 'UTF-8'); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">ไม่เคยใช้งาน</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="kiosks.php?edit=<?php echo (int) $kiosk['id']; ?>" class="btn btn-sm btn-outline-primary me-2">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <form method="post" class="d-inline" onsubmit="return confirm('ยืนยันการลบเครื่องนี้หรือไม่?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="action" value="delete_kiosk">
                                                    <input type="hidden" name="kiosk_id" value="<?php echo (int) $kiosk['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
