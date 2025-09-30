<?php
require_once '../config/config.php';
requireLogin();

if (!hasPermission('manage_audio_system')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

$successMessage = null;
$errorMessage = null;

try {
    $db = getDB();
} catch (Exception $e) {
    die('ไม่สามารถเชื่อมต่อฐานข้อมูลได้');
}

$voiceTemplates = [];
$ttsServices = [];

$fetchTemplates = function () use (&$db) {
    $stmt = $db->query("SELECT * FROM voice_templates ORDER BY is_default DESC, template_name");
    return $stmt->fetchAll();
};

$fetchServices = function () use (&$db) {
    $stmt = $db->query("SELECT * FROM tts_api_services ORDER BY is_active DESC, provider_name");
    return $stmt->fetchAll();
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_template'])) {
            $templateName = sanitizeInput($_POST['template_name'] ?? '');
            $templateText = sanitizeInput($_POST['template_text'] ?? '');

            if ($templateName === '' || $templateText === '') {
                throw new Exception('กรุณากรอกชื่อและข้อความของประโยคเรียกคิว');
            }

            $stmt = $db->prepare("INSERT INTO voice_templates (template_name, template_text) VALUES (?, ?)");
            $stmt->execute([$templateName, $templateText]);
            logActivity('เพิ่มรูปแบบข้อความเสียงเรียก: ' . $templateName);
            $successMessage = 'เพิ่มประโยคเรียกคิวเรียบร้อยแล้ว';
        } elseif (isset($_POST['update_template'])) {
            $templateId = (int)($_POST['template_id'] ?? 0);
            $templateName = sanitizeInput($_POST['template_name'] ?? '');
            $templateText = sanitizeInput($_POST['template_text'] ?? '');

            if ($templateId <= 0 || $templateName === '' || $templateText === '') {
                throw new Exception('ข้อมูลไม่ครบถ้วนสำหรับการแก้ไขประโยคเรียกคิว');
            }

            $stmt = $db->prepare("UPDATE voice_templates SET template_name = ?, template_text = ? WHERE template_id = ?");
            $stmt->execute([$templateName, $templateText, $templateId]);
            logActivity('แก้ไขรูปแบบข้อความเสียงเรียก ID: ' . $templateId);
            $successMessage = 'อัปเดตประโยคเรียกคิวเรียบร้อยแล้ว';
        } elseif (isset($_POST['set_default_template'])) {
            $templateId = (int)($_POST['template_id'] ?? 0);
            if ($templateId <= 0) {
                throw new Exception('ไม่พบรูปแบบข้อความที่ต้องการตั้งค่าเป็นค่าเริ่มต้น');
            }

            $db->beginTransaction();
            $db->exec('UPDATE voice_templates SET is_default = 0');
            $stmt = $db->prepare('UPDATE voice_templates SET is_default = 1 WHERE template_id = ?');
            $stmt->execute([$templateId]);
            $db->commit();

            logActivity('ตั้งค่ารูปแบบข้อความเสียงเรียกเริ่มต้น: ' . $templateId);
            $successMessage = 'อัปเดตค่าเริ่มต้นเรียบร้อยแล้ว';
        } elseif (isset($_POST['delete_template'])) {
            $templateId = (int)($_POST['template_id'] ?? 0);
            if ($templateId <= 0) {
                throw new Exception('ไม่พบรูปแบบข้อความที่ต้องการลบ');
            }

            $stmt = $db->prepare('SELECT is_default FROM voice_templates WHERE template_id = ?');
            $stmt->execute([$templateId]);
            $isDefault = (int)($stmt->fetchColumn() ?? 0);
            if ($isDefault === 1) {
                throw new Exception('ไม่สามารถลบรูปแบบข้อความเริ่มต้นได้');
            }

            $stmt = $db->prepare('DELETE FROM voice_templates WHERE template_id = ?');
            $stmt->execute([$templateId]);

            $stmt = $db->prepare('UPDATE service_points SET voice_template_id = NULL WHERE voice_template_id = ?');
            $stmt->execute([$templateId]);

            logActivity('ลบรูปแบบข้อความเสียงเรียก ID: ' . $templateId);
            $successMessage = 'ลบรูปแบบข้อความเรียบร้อยแล้ว';
        } elseif (isset($_POST['add_api_service'])) {
            $providerName = sanitizeInput($_POST['provider_name'] ?? '');
            $curlCommand = trim($_POST['curl_command'] ?? '');

            if ($providerName === '' || $curlCommand === '') {
                throw new Exception('กรุณากรอกชื่อผู้ให้บริการและคำสั่ง curl');
            }

            $stmt = $db->prepare('INSERT INTO tts_api_services (provider_name, curl_command) VALUES (?, ?)');
            $stmt->execute([$providerName, $curlCommand]);
            logActivity('เพิ่ม TTS API Service: ' . $providerName);
            $successMessage = 'เพิ่มบริการ API เรียบร้อยแล้ว';
        } elseif (isset($_POST['update_api_service'])) {
            $serviceId = (int)($_POST['service_id'] ?? 0);
            $providerName = sanitizeInput($_POST['provider_name'] ?? '');
            $curlCommand = trim($_POST['curl_command'] ?? '');

            if ($serviceId <= 0 || $providerName === '' || $curlCommand === '') {
                throw new Exception('ข้อมูลไม่ครบถ้วนสำหรับการแก้ไขบริการ API');
            }

            $stmt = $db->prepare('UPDATE tts_api_services SET provider_name = ?, curl_command = ? WHERE service_id = ?');
            $stmt->execute([$providerName, $curlCommand, $serviceId]);
            logActivity('แก้ไข TTS API Service ID: ' . $serviceId);
            $successMessage = 'อัปเดตบริการ API เรียบร้อยแล้ว';
        } elseif (isset($_POST['set_active_service'])) {
            $serviceId = (int)($_POST['service_id'] ?? 0);
            if ($serviceId <= 0) {
                throw new Exception('กรุณาเลือกบริการ API ที่ต้องการใช้งาน');
            }

            $db->beginTransaction();
            $db->exec('UPDATE tts_api_services SET is_active = 0');
            $stmt = $db->prepare('UPDATE tts_api_services SET is_active = 1 WHERE service_id = ?');
            $stmt->execute([$serviceId]);
            $db->commit();

            logActivity('ตั้งค่า TTS API Service ที่ใช้งาน: ' . $serviceId);
            $successMessage = 'อัปเดตบริการที่ใช้งานเรียบร้อยแล้ว';
        } elseif (isset($_POST['delete_api_service'])) {
            $serviceId = (int)($_POST['service_id'] ?? 0);
            if ($serviceId <= 0) {
                throw new Exception('ไม่พบบริการ API ที่ต้องการลบ');
            }

            $stmt = $db->prepare('SELECT is_active FROM tts_api_services WHERE service_id = ?');
            $stmt->execute([$serviceId]);
            $isActive = (int)($stmt->fetchColumn() ?? 0);
            if ($isActive === 1) {
                throw new Exception('ไม่สามารถลบบริการ API ที่กำลังใช้งานอยู่ได้');
            }

            $stmt = $db->prepare('DELETE FROM tts_api_services WHERE service_id = ?');
            $stmt->execute([$serviceId]);

            logActivity('ลบ TTS API Service ID: ' . $serviceId);
            $successMessage = 'ลบบริการ API เรียบร้อยแล้ว';
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $errorMessage = $e->getMessage();
    }
}

try {
    $voiceTemplates = $fetchTemplates();
    $ttsServices = $fetchServices();
} catch (Exception $e) {
    $errorMessage = $errorMessage ?: 'เกิดข้อผิดพลาดในการโหลดข้อมูล: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าระบบเสียงเรียกคิว</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f5f7fb;
        }
        .nav-sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
        }
        .nav-sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 1rem 1.5rem;
        }
        .nav-sidebar .nav-link.active,
        .nav-sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255,255,255,0.1);
        }
        .card-setting {
            border: none;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(82, 63, 105, 0.1);
        }
        .badge-active {
            background: linear-gradient(135deg, #1dd1a1 0%, #10ac84 100%);
        }
        pre.curl-preview {
            background: #212529;
            color: #f8f9fa;
            padding: 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
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
                    <h2 class="fw-bold mb-1">ตั้งค่าระบบเสียงเรียกคิว</h2>
                    <p class="text-muted mb-0">จัดการประโยคเรียกคิวและบริการ API สำหรับสังเคราะห์เสียง</p>
                </div>
                <div class="mt-3 mt-lg-0">
                    <a href="tts_test_modern.php" class="btn btn-outline-primary"><i class="fas fa-play-circle me-2"></i>เปิดหน้าทดสอบเสียง</a>
                </div>
            </div>

            <?php if ($successMessage): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($successMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($errorMessage); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-12">
                    <div class="card card-setting">
                        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0"><i class="fas fa-comment-dots me-2 text-primary"></i>ประโยคเรียกคิว</h5>
                                <small class="text-muted">ใช้ตัวแปร {queue_number}, {service_point_name}, {patient_name} ได้ตามต้องการ</small>
                            </div>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
                                <i class="fas fa-plus me-2"></i>เพิ่มประโยคใหม่
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <?php if (empty($voiceTemplates)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-file-alt fa-2x mb-2"></i>
                                    <p class="mb-0">ยังไม่มีประโยคเรียกคิว</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($voiceTemplates as $template): ?>
                                    <div class="border rounded-3 p-3 mb-3 <?php echo $template['is_default'] ? 'border-primary bg-light' : 'border-light'; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1 fw-semibold">
                                                    <?php echo htmlspecialchars($template['template_name']); ?>
                                                    <?php if ($template['is_default']): ?>
                                                        <span class="badge badge-active ms-2">ค่าเริ่มต้น</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <p class="text-muted mb-2" style="white-space: pre-line;"><?php echo htmlspecialchars($template['template_text']); ?></p>
                                            </div>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editTemplateModal"
                                                        data-template-id="<?php echo (int)$template['template_id']; ?>"
                                                        data-template-name="<?php echo htmlspecialchars($template['template_name'], ENT_QUOTES); ?>"
                                                        data-template-text="<?php echo htmlspecialchars($template['template_text'], ENT_QUOTES); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if (!$template['is_default']): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="template_id" value="<?php echo (int)$template['template_id']; ?>">
                                                        <button type="submit" name="set_default_template" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-star"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('ยืนยันการลบประโยคนี้หรือไม่?');">
                                                        <input type="hidden" name="template_id" value="<?php echo (int)$template['template_id']; ?>">
                                                        <button type="submit" name="delete_template" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card card-setting">
                        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0"><i class="fas fa-wave-square me-2 text-primary"></i>บริการ API เสียง</h5>
                                <small class="text-muted">เพิ่มคำสั่ง curl สำหรับเรียกใช้งาน Text-to-Speech โดยใช้ตัวแปร {{_TEXT_TO_SPECH_}}</small>
                            </div>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                                <i class="fas fa-plus me-2"></i>เพิ่ม API Service
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <?php if (empty($ttsServices)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                    <p class="mb-0">ยังไม่มีการตั้งค่า API Service</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($ttsServices as $service): ?>
                                    <div class="border rounded-3 p-3 mb-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1 fw-semibold">
                                                    <?php echo htmlspecialchars($service['provider_name']); ?>
                                                    <?php if ($service['is_active']): ?>
                                                        <span class="badge badge-active ms-2">ใช้งานอยู่</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <pre class="curl-preview mb-3"><?php echo htmlspecialchars($service['curl_command']); ?></pre>
                                            </div>
                                            <div class="btn-group flex-shrink-0">
                                                <button class="btn btn-sm btn-outline-secondary btn-edit-service" data-bs-toggle="modal" data-bs-target="#editServiceModal"
                                                        data-service-id="<?php echo (int)$service['service_id']; ?>"
                                                        data-provider-name="<?php echo htmlspecialchars($service['provider_name'], ENT_QUOTES); ?>"
                                                        data-curl-command="<?php echo htmlspecialchars($service['curl_command'], ENT_QUOTES); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info btn-test-service"
                                                        data-service-id="<?php echo (int)$service['service_id']; ?>"
                                                        data-service-name="<?php echo htmlspecialchars($service['provider_name'], ENT_QUOTES); ?>">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                                <?php if (!$service['is_active']): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="service_id" value="<?php echo (int)$service['service_id']; ?>">
                                                        <button type="submit" name="set_active_service" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('ยืนยันการลบบริการนี้หรือไม่?');">
                                                        <input type="hidden" name="service_id" value="<?php echo (int)$service['service_id']; ?>">
                                                        <button type="submit" name="delete_api_service" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div class="modal fade" id="addTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เพิ่มประโยคเรียกคิว</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">ชื่อประโยค</label>
                    <input type="text" name="template_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">ข้อความประโยค</label>
                    <textarea name="template_text" class="form-control" rows="4" required></textarea>
                    <small class="text-muted">รองรับตัวแปร {queue_number}, {service_point_name}, {patient_name}</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" name="add_template" class="btn btn-primary">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="template_id" id="editTemplateId">
            <div class="modal-header">
                <h5 class="modal-title">แก้ไขประโยคเรียกคิว</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">ชื่อประโยค</label>
                    <input type="text" name="template_name" id="editTemplateName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">ข้อความประโยค</label>
                    <textarea name="template_text" id="editTemplateText" class="form-control" rows="4" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" name="update_template" class="btn btn-primary">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="addServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เพิ่ม API Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">ชื่อผู้ให้บริการ</label>
                    <input type="text" name="provider_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">คำสั่ง cURL</label>
                    <textarea name="curl_command" class="form-control" rows="6" placeholder="curl --location 'http://example.com/tts' \&#10;--header 'Content-Type: application/json' \&#10;--data '{&#10;    &quot;text&quot;: &quot;{{_TEXT_TO_SPECH_}}&quot;&#10}'" required></textarea>
                    <small class="text-muted">ใช้ตัวแปร <code>{{_TEXT_TO_SPECH_}}</code> เพื่อแทนข้อความที่จะส่งไปยัง API</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" name="add_api_service" class="btn btn-success">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
            <input type="hidden" name="service_id" id="editServiceId">
            <div class="modal-header">
                <h5 class="modal-title">แก้ไข API Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">ชื่อผู้ให้บริการ</label>
                    <input type="text" name="provider_name" id="editServiceName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">คำสั่ง cURL</label>
                    <textarea name="curl_command" id="editServiceCommand" class="form-control" rows="6" required></textarea>
                    <small class="text-muted">ตรวจสอบให้แน่ใจว่ามี <code>{{_TEXT_TO_SPECH_}}</code> อยู่ในคำสั่ง</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="submit" name="update_api_service" class="btn btn-primary">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="testServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ทดสอบเสียงจาก API</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">กรอกข้อความที่ต้องการทดสอบ</p>
                <input type="hidden" id="testServiceId">
                <div class="mb-3">
                    <label class="form-label">ข้อความตัวอย่าง</label>
                    <textarea class="form-control" id="testServiceText" rows="3">ทดสอบระบบเสียง หมายเลข A001 เชิญที่ ห้องตรวจ 1</textarea>
                </div>
                <div id="testServiceResult" class="d-none">
                    <div class="alert alert-success py-2">สร้างไฟล์เสียงเรียบร้อยแล้ว</div>
                    <audio id="testServiceAudio" controls class="w-100"></audio>
                </div>
                <div id="testServiceError" class="alert alert-danger d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                <button type="button" class="btn btn-primary" id="runServiceTest">ทดสอบเสียง</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const editTemplateModal = document.getElementById('editTemplateModal');
    if (editTemplateModal) {
        editTemplateModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('editTemplateId').value = button.getAttribute('data-template-id');
            document.getElementById('editTemplateName').value = button.getAttribute('data-template-name');
            document.getElementById('editTemplateText').value = button.getAttribute('data-template-text');
        });
    }

    const editServiceModal = document.getElementById('editServiceModal');
    if (editServiceModal) {
        editServiceModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('editServiceId').value = button.getAttribute('data-service-id');
            document.getElementById('editServiceName').value = button.getAttribute('data-provider-name');
            document.getElementById('editServiceCommand').value = button.getAttribute('data-curl-command');
        });
    }

    const testServiceModal = document.getElementById('testServiceModal');
    const testServiceId = document.getElementById('testServiceId');
    const testServiceText = document.getElementById('testServiceText');
    const testServiceResult = document.getElementById('testServiceResult');
    const testServiceAudio = document.getElementById('testServiceAudio');
    const testServiceError = document.getElementById('testServiceError');

    document.querySelectorAll('.btn-test-service').forEach(function (button) {
        button.addEventListener('click', function () {
            testServiceId.value = button.getAttribute('data-service-id');
            testServiceResult.classList.add('d-none');
            testServiceAudio.removeAttribute('src');
            testServiceError.classList.add('d-none');
            const modal = new bootstrap.Modal(testServiceModal);
            modal.show();
        });
    });

    document.getElementById('runServiceTest').addEventListener('click', function () {
        const serviceId = testServiceId.value;
        const text = testServiceText.value.trim();
        testServiceError.classList.add('d-none');
        testServiceResult.classList.add('d-none');

        fetch('../api/test_audio.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                service_id: serviceId,
                text: text
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    testServiceAudio.src = '../' + data.audio_path + '?_=' + Date.now();
                    testServiceResult.classList.remove('d-none');
                    testServiceAudio.play();
                } else {
                    testServiceError.textContent = data.error || 'ไม่สามารถทดสอบเสียงได้';
                    testServiceError.classList.remove('d-none');
                }
            })
            .catch(() => {
                testServiceError.textContent = 'ไม่สามารถติดต่อ API ได้';
                testServiceError.classList.remove('d-none');
            });
    });
</script>
</body>
</html>
