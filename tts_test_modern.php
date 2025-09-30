<?php
require_once 'config/config.php';
requireLogin();

try {
    $db = getDB();
} catch (Exception $e) {
    die('ไม่สามารถเชื่อมต่อฐานข้อมูลได้');
}

$voiceTemplates = [];
$ttsServices = [];
$activeService = null;

try {
    $stmt = $db->query("SELECT * FROM voice_templates ORDER BY is_default DESC, template_name");
    $voiceTemplates = $stmt->fetchAll();

    $stmt = $db->query("SELECT * FROM tts_api_services ORDER BY provider_name");
    $ttsServices = $stmt->fetchAll();
    foreach ($ttsServices as $service) {
        if (!empty($service['is_active'])) {
            $activeService = $service;
            break;
        }
    }
} catch (Exception $e) {
    $voiceTemplates = [];
    $ttsServices = [];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทดสอบระบบเสียงเรียกคิว</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .card-shadow {
            border: none;
            border-radius: 18px;
            box-shadow: 0 15px 35px rgba(31, 45, 61, 0.1);
        }
        .badge-active {
            background: linear-gradient(135deg, #1dd1a1 0%, #10ac84 100%);
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-5">
                <h1 class="fw-bold">ทดสอบระบบเสียงเรียกคิว</h1>
                <p class="text-muted">สร้างประโยคเรียกคิวจากรูปแบบที่กำหนดและทดสอบกับบริการ API ที่ตั้งค่าไว้</p>
            </div>

            <?php if (!$activeService): ?>
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div>
                        ยังไม่มีการเลือก API Service ที่ใช้งานอยู่ กรุณาตั้งค่าจากหน้า <a href="admin/audio_settings.php" class="alert-link">ระบบเสียงเรียกคิว</a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card card-shadow mb-4">
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">เลือกรูปแบบประโยคเรียกคิว</label>
                            <select id="templateSelector" class="form-select">
                                <?php foreach ($voiceTemplates as $template): ?>
                                    <option value="<?php echo htmlspecialchars($template['template_text'], ENT_QUOTES); ?>">
                                        <?php echo htmlspecialchars($template['template_name']); ?><?php echo $template['is_default'] ? ' (ค่าเริ่มต้น)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">หมายเลขคิว</label>
                            <input type="text" id="queueNumber" class="form-control" value="A001">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">จุดบริการ</label>
                            <input type="text" id="servicePoint" class="form-control" value="ห้องตรวจ 1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ชื่อผู้ป่วย (ถ้ามี)</label>
                            <input type="text" id="patientName" class="form-control" placeholder="เช่น คุณสมชาย">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-shadow mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-semibold mb-3"><i class="fas fa-comment-dots me-2 text-primary"></i>ตัวอย่างข้อความที่จะเรียก</h5>
                    <div class="border rounded-3 p-3 bg-light" id="previewText">&nbsp;</div>
                </div>
            </div>

            <div class="card card-shadow">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-semibold mb-0"><i class="fas fa-wave-square me-2 text-success"></i>บริการ API ที่ใช้งาน</h5>
                        <?php if ($activeService): ?>
                            <span class="badge badge-active"><?php echo htmlspecialchars($activeService['provider_name']); ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted small mb-3">ข้อความจะถูกส่งไปยัง API โดยแทนที่ตัวแปร <code>{{_TEXT_TO_SPECH_}}</code> ในคำสั่ง cURL ของบริการ</p>
                    <div class="d-grid d-md-flex gap-3">
                        <button id="generatePreview" class="btn btn-outline-primary flex-fill">
                            <i class="fas fa-eye me-2"></i>อัปเดตตัวอย่างข้อความ
                        </button>
                        <button id="playTest" class="btn btn-primary flex-fill" <?php echo $activeService ? '' : 'disabled'; ?>>
                            <i class="fas fa-play me-2"></i>ทดสอบเสียงผ่าน API
                        </button>
                    </div>
                    <div id="testStatus" class="mt-3 d-none"></div>
                    <audio id="testAudio" class="w-100 mt-3" controls></audio>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const templateSelector = document.getElementById('templateSelector');
    const queueNumberInput = document.getElementById('queueNumber');
    const servicePointInput = document.getElementById('servicePoint');
    const patientNameInput = document.getElementById('patientName');
    const previewText = document.getElementById('previewText');
    const generatePreviewButton = document.getElementById('generatePreview');
    const playTestButton = document.getElementById('playTest');
    const testStatus = document.getElementById('testStatus');
    const testAudio = document.getElementById('testAudio');

    function buildMessage() {
        const template = templateSelector.value || '';
        let message = template;
        const queueNumber = queueNumberInput.value.trim() || 'A001';
        const servicePoint = servicePointInput.value.trim() || 'จุดบริการ';
        const patientName = patientNameInput.value.trim();

        message = message.replaceAll('{queue_number}', queueNumber);
        message = message.replaceAll('{service_point}', servicePoint);
        message = message.replaceAll('{service_point_name}', servicePoint);
        message = message.replaceAll('{patient_name}', patientName || '');
        return message;
    }

    function updatePreview() {
        const message = buildMessage();
        previewText.textContent = message;
    }

    generatePreviewButton.addEventListener('click', updatePreview);
    templateSelector.addEventListener('change', updatePreview);
    queueNumberInput.addEventListener('input', updatePreview);
    servicePointInput.addEventListener('input', updatePreview);
    patientNameInput.addEventListener('input', updatePreview);

    updatePreview();

    playTestButton?.addEventListener('click', function () {
        const message = buildMessage();
        testStatus.classList.add('d-none');
        testAudio.removeAttribute('src');

        fetch('api/test_audio.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                text: message
            })
        })
            .then(response => response.json())
            .then(data => {
                testStatus.classList.remove('d-none');
                if (data.success) {
                    testStatus.className = 'alert alert-success';
                    testStatus.textContent = 'สร้างไฟล์เสียงสำเร็จ สามารถฟังได้ด้านล่าง';
                    testAudio.src = data.audio_path ? (data.audio_path.startsWith('storage') ? data.audio_path : '../' + data.audio_path) : '';
                    if (testAudio.src) {
                        testAudio.play().catch(() => {});
                    }
                } else {
                    testStatus.className = 'alert alert-danger';
                    testStatus.textContent = data.error || 'ไม่สามารถสร้างเสียงจาก API ได้';
                }
            })
            .catch(() => {
                testStatus.classList.remove('d-none');
                testStatus.className = 'alert alert-danger';
                testStatus.textContent = 'ไม่สามารถติดต่อ API ได้';
            });
    });
</script>
</body>
</html>
