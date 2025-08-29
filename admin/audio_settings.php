<?php
/**
 * This file provides the audio settings page for the admin panel.
 *
 * @package Yuwa_Queue
 */

require_once '../config/config.php';
requireLogin();

if (!hasPermission('manage_audio_system')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

// Force disable TTS and load audio settings
setSetting('tts_enabled', '0');
$ttsEnabled = '0';
$audioVolume = getSetting('audio_volume', '1.0');
$audioRepeatCount = getSetting('audio_repeat_count', '1');
$soundNotificationBefore = getSetting('sound_notification_before', '1');

// Get voice templates
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM voice_templates ORDER BY is_default DESC, template_name");
    $stmt->execute();
    $voiceTemplates = $stmt->fetchAll();
} catch (Exception $e) {
    $voiceTemplates = [];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
if (isset($_POST['update_audio_settings'])) {
        // Update audio settings
        setSetting('tts_enabled', '0');
        setSetting('audio_volume', $_POST['audio_volume'] ?? '1.0');
        setSetting('audio_repeat_count', $_POST['audio_repeat_count'] ?? '1');
        setSetting('sound_notification_before', $_POST['sound_notification_before'] ?? '1');

        logActivity('อัพเดทการตั้งค่าระบบเสียง');
        $successMessage = 'บันทึกการตั้งค่าเรียบร้อยแล้ว';

        // Refresh settings
        $audioVolume = $_POST['audio_volume'] ?? '1.0';
        $audioRepeatCount = $_POST['audio_repeat_count'] ?? '1';
        $soundNotificationBefore = $_POST['sound_notification_before'] ?? '1';
    } elseif (isset($_POST['add_template'])) {
        // Add new template
        $templateName = sanitizeInput($_POST['template_name'] ?? '');
        $templateText = sanitizeInput($_POST['template_text'] ?? '');
        
        if (!empty($templateName) && !empty($templateText)) {
            try {
                $db = getDB();
                $stmt = $db->prepare("INSERT INTO voice_templates (template_name, template_text) VALUES (?, ?)");
                $stmt->execute([$templateName, $templateText]);
                
                logActivity('เพิ่มรูปแบบข้อความเสียงเรียก: ' . $templateName);
                $successMessage = 'เพิ่มรูปแบบข้อความเสียงเรียกเรียบร้อยแล้ว';
                
                // Refresh templates
                $stmt = $db->prepare("SELECT * FROM voice_templates ORDER BY is_default DESC, template_name");
                $stmt->execute();
                $voiceTemplates = $stmt->fetchAll();
            } catch (Exception $e) {
                $errorMessage = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            }
        } else {
            $errorMessage = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        }
    } elseif (isset($_POST['delete_template'])) {
        // Delete template
        $templateId = (int)$_POST['template_id'];
        
        try {
            $db = getDB();
            // Check if this is the default template
            $stmt = $db->prepare("SELECT is_default FROM voice_templates WHERE template_id = ?");
            $stmt->execute([$templateId]);
            $isDefault = $stmt->fetch()['is_default'] ?? false;
            
            if ($isDefault) {
                $errorMessage = 'ไม่สามารถลบรูปแบบข้อความเริ่มต้นได้';
            } else {
                // Update service points using this template to use default template
                $stmt = $db->prepare(
                    "
                    UPDATE service_points 
                    SET voice_template_id = (SELECT template_id FROM voice_templates WHERE is_default = 1 LIMIT 1)
                    WHERE voice_template_id = ?
                    "
                );
                $stmt->execute([$templateId]);
                
                // Delete template
                $stmt = $db->prepare("DELETE FROM voice_templates WHERE template_id = ? AND is_default = 0");
                $stmt->execute([$templateId]);
                
                logActivity('ลบรูปแบบข้อความเสียงเรียก ID: ' . $templateId);
                $successMessage = 'ลบรูปแบบข้อความเสียงเรียกเรียบร้อยแล้ว';
                
                // Refresh templates
                $stmt = $db->prepare("SELECT * FROM voice_templates ORDER BY is_default DESC, template_name");
                $stmt->execute();
                $voiceTemplates = $stmt->fetchAll();
            }
        } catch (Exception $e) {
            $errorMessage = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    } elseif (isset($_POST['set_default_template'])) {
        // Set default template
        $templateId = (int)$_POST['template_id'];
        
        try {
            $db = getDB();
            // Remove default flag from all templates
            $stmt = $db->prepare("UPDATE voice_templates SET is_default = 0");
            $stmt->execute();
            
            // Set new default template
            $stmt = $db->prepare("UPDATE voice_templates SET is_default = 1 WHERE template_id = ?");
            $stmt->execute([$templateId]);
            
            logActivity('ตั้งค่ารูปแบบข้อความเริ่มต้น ID: ' . $templateId);
            $successMessage = 'ตั้งค่ารูปแบบข้อความเริ่มต้นเรียบร้อยแล้ว';
            
            // Refresh templates
            $stmt = $db->prepare("SELECT * FROM voice_templates ORDER BY is_default DESC, template_name");
            $stmt->execute();
            $voiceTemplates = $stmt->fetchAll();
        } catch (Exception $e) {
            $errorMessage = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    } elseif (isset($_POST['edit_template'])) {
        // Edit template
        $templateId = (int)$_POST['template_id'];
        $templateName = sanitizeInput($_POST['template_name'] ?? '');
        $templateText = sanitizeInput($_POST['template_text'] ?? '');
        
        if (!empty($templateName) && !empty($templateText)) {
            try {
                $db = getDB();
                $stmt = $db->prepare("UPDATE voice_templates SET template_name = ?, template_text = ? WHERE template_id = ?");
                $stmt->execute([$templateName, $templateText, $templateId]);
                
                logActivity('แก้ไขรูปแบบข้อความเสียงเรียก ID: ' . $templateId);
                $successMessage = 'แก้ไขรูปแบบข้อความเสียงเรียกเรียบร้อยแล้ว';
                
                // Refresh templates
                $stmt = $db->prepare("SELECT * FROM voice_templates ORDER BY is_default DESC, template_name");
                $stmt->execute();
                $voiceTemplates = $stmt->fetchAll();
            } catch (Exception $e) {
                $errorMessage = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            }
        } else {
            $errorMessage = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        }
    } elseif (isset($_POST['upload_audio'])) {
        // Handle audio file upload
        $displayName = sanitizeInput($_POST['display_name'] ?? '');
        $audioType = sanitizeInput($_POST['audio_type'] ?? '');
        
        if (empty($displayName) || empty($audioType)) {
            $errorMessage = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        } elseif (!isset($_FILES['audio_file']) || $_FILES['audio_file']['error'] != 0) {
            $errorMessage = 'กรุณาอัพโหลดไฟล์เสียง';
        } else {
            $uploadDir = ROOT_PATH . '/uploads/audio/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = basename($_FILES['audio_file']['name']);
            $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
            $allowedExts = ['mp3', 'wav', 'ogg'];
            
            if (!in_array(strtolower($fileExt), $allowedExts)) {
                $errorMessage = 'รูปแบบไฟล์ไม่ถูกต้อง กรุณาอัพโหลดไฟล์เสียง mp3, wav หรือ ogg';
            } else {
                // Generate unique filename
                $newFileName = uniqid('audio_') . '.' . $fileExt;
                $targetFile = $uploadDir . $newFileName;
                
                if (move_uploaded_file($_FILES['audio_file']['tmp_name'], $targetFile)) {
                    try {
                        $db = getDB();
                        $filePath = '/uploads/audio/' . $newFileName;
                        $stmt = $db->prepare("INSERT INTO audio_files (file_name, display_name, file_path, audio_type) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$newFileName, $displayName, $filePath, $audioType]);
                        
                        logActivity('อัพโหลดไฟล์เสียง: ' . $displayName);
                        $successMessage = 'อัพโหลดไฟล์เสียงเรียบร้อยแล้ว';
                    } catch (Exception $e) {
                        $errorMessage = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage();
                        // Delete uploaded file if database insert fails
                        @unlink($targetFile);
                    }
                } else {
                    $errorMessage = 'เกิดข้อผิดพลาดในการอัพโหลดไฟล์';
                }
            }
        }
    } elseif (isset($_POST['delete_audio'])) {
        // Delete audio file
        $audioId = (int)$_POST['audio_id'];

        try {
            $db = getDB();
            // Fetch file information
            $stmt = $db->prepare("SELECT file_path, display_name FROM audio_files WHERE audio_id = ?");
            $stmt->execute([$audioId]);
            $fileInfo = $stmt->fetch();

            if ($fileInfo) {
                $filePath = ROOT_PATH . ($fileInfo['file_path'] ?? '');
                if (!empty($fileInfo['file_path']) && file_exists($filePath)) {
                    @unlink($filePath);
                }

                $stmt = $db->prepare("DELETE FROM audio_files WHERE audio_id = ?");
                $stmt->execute([$audioId]);

                logActivity('ลบไฟล์เสียง: ' . ($fileInfo['display_name'] ?? ('ID: ' . $audioId)));
                $successMessage = 'ลบไฟล์เสียงเรียบร้อยแล้ว';

                // Refresh audio files list
                $stmt = $db->prepare("SELECT * FROM audio_files ORDER BY audio_type, display_name");
                $stmt->execute();
                $audioFiles = $stmt->fetchAll();
            } else {
                $errorMessage = 'ไม่พบไฟล์เสียงที่ต้องการลบ';
            }
        } catch (Exception $e) {
            $errorMessage = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    }
}

// Get audio files
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM audio_files ORDER BY audio_type, display_name");
    $stmt->execute();
    $audioFiles = $stmt->fetchAll();
} catch (Exception $e) {
    $audioFiles = [];
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าระบบเสียงเรียกคิว - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    
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
        
        .settings-section {
            margin-bottom: 2rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
        }
        
        .form-check-input:checked {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #495057;
            border-bottom: 2px solid transparent;
            padding: 0.5rem 1rem;
            margin-right: 1rem;
        }
        
        .nav-tabs .nav-link.active {
            color: #4e73df;
            border-bottom: 2px solid #4e73df;
            background: transparent;
        }
        
        .audio-control {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .audio-control button {
            margin-left: 1rem;
        }
        
        .template-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .template-card-default {
            border-color: #4e73df;
            background-color: #f8f9ff;
        }
        
        .template-badge {
            position: absolute;
            top: -10px;
            right: 10px;
            background-color: #4e73df;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.75rem;
        }
        
        .audio-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .audio-item {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
        }
        
        .audio-item .badge {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="p-3">
                    <h5 class="text-white mb-4">
                        <i class="fas fa-cogs me-2"></i>
                        จัดการระบบ
                    </h5>
                    
                    <?php include 'nav.php'; ?>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2>ตั้งค่าระบบเสียงเรียกคิว</h2>
                            <p class="text-muted">จัดการการตั้งค่าเสียงเรียกคิวและ Text-to-Speech</p>
                        </div>
                        <button type="button" class="btn btn-primary" id="testAudio">
                            <i class="fas fa-play-circle me-2"></i>ทดสอบเสียง
                        </button>
                    </div>
                    
                    <!-- Alert Messages -->
                    <?php if (isset($successMessage)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($errorMessage)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $errorMessage; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-4">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#general-settings">
                                <i class="fas fa-sliders-h me-2"></i>ตั้งค่าทั่วไป
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#voice-templates">
                                <i class="fas fa-file-alt me-2"></i>รูปแบบข้อความ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#audio-files">
                                <i class="fas fa-music me-2"></i>ไฟล์เสียง
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#call-history">
                                <i class="fas fa-history me-2"></i>ประวัติการเรียกเสียง
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#voice-templates-management">
                                <i class="fas fa-file-alt me-2"></i>จัดการรูปแบบข้อความ
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content">
                        <!-- General Settings Tab -->
                        <div class="tab-pane fade show active" id="general-settings">
                            <div class="content-card">
                                <h5 class="mb-4">การตั้งค่าทั่วไป</h5>
                                
                                <form method="POST" action="">
                                    <!-- Basic Settings -->
                                    <div class="settings-section">
                                        <div class="mb-3">
                                            <label for="audioRepeatCount" class="form-label">จำนวนครั้งที่เล่นเสียงซ้ำ</label>
                                            <select class="form-select" id="audioRepeatCount" name="audio_repeat_count">
                                                <option value="1" <?php echo $audioRepeatCount == '1' ? 'selected' : ''; ?>>1 ครั้ง</option>
                                                <option value="2" <?php echo $audioRepeatCount == '2' ? 'selected' : ''; ?>>2 ครั้ง</option>
                                                <option value="3" <?php echo $audioRepeatCount == '3' ? 'selected' : ''; ?>>3 ครั้ง</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="soundNotificationBefore" name="sound_notification_before" value="1" <?php echo $soundNotificationBefore == '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="soundNotificationBefore">เล่นเสียงแจ้งเตือนก่อนเรียกคิว</label>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="audioVolume" class="form-label">ความดังของเสียง: <span id="volumeValue"><?php echo $audioVolume; ?></span></label>
                                            <input type="range" class="form-range" min="0" max="1" step="0.1" id="audioVolume" name="audio_volume" value="<?php echo $audioVolume; ?>">
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <button type="submit" name="update_audio_settings" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>บันทึกการตั้งค่า
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Voice Templates Tab -->
                        <div class="tab-pane fade" id="voice-templates">
                            <div class="content-card">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0">รูปแบบข้อความเสียงเรียก</h5>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTemplateModal">
                                        <i class="fas fa-plus me-2"></i>เพิ่มรูปแบบใหม่
                                    </button>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>คำแนะนำ:</strong> คุณสามารถใช้ตัวแปรดังต่อไปนี้ในรูปแบบข้อความ
                                    <div class="mt-2">
                                        <code>{queue_number}</code> - หมายเลขคิว<br>
                                        <code>{service_point_name}</code> - ชื่อจุดบริการ<br>
                                        <code>{patient_name}</code> - ชื่อผู้ป่วย (หากมีข้อมูล)
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <?php if (empty($voiceTemplates)): ?>
                                        <div class="text-center text-muted py-5">
                                            <i class="fas fa-file-alt fa-3x mb-3"></i>
                                            <p>ยังไม่มีรูปแบบข้อความเสียงเรียก</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($voiceTemplates as $template): ?>
                                            <div class="template-card <?php echo $template['is_default'] ? 'template-card-default' : ''; ?>">
                                                <?php if ($template['is_default']): ?>
                                                    <span class="template-badge">ค่าเริ่มต้น</span>
                                                <?php endif; ?>
                                                
                                                <div class="d-flex justify-content-between">
                                                    <h6><?php echo htmlspecialchars($template['template_name']); ?></h6>
                                                    <div>
                                                        <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="playTemplate(<?php echo $template['template_id']; ?>, '<?php echo htmlspecialchars($template['template_text'], ENT_QUOTES); ?>')">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="editTemplate(<?php echo $template['template_id']; ?>, '<?php echo htmlspecialchars($template['template_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($template['template_text'], ENT_QUOTES); ?>')">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php if (!$template['is_default']): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger me-2" onclick="confirmDeleteTemplate(<?php echo $template['template_id']; ?>, '<?php echo htmlspecialchars($template['template_name'], ENT_QUOTES); ?>')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-success" onclick="confirmDefaultTemplate(<?php echo $template['template_id']; ?>, '<?php echo htmlspecialchars($template['template_name'], ENT_QUOTES); ?>')">
                                                                <i class="fas fa-check"></i> ตั้งเป็นค่าเริ่มต้น
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="mt-2 p-2 bg-light rounded">
                                                    <?php echo htmlspecialchars($template['template_text']); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Audio Files Tab -->
                        <div class="tab-pane fade" id="audio-files">
                            <div class="content-card">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0">จัดการไฟล์เสียง</h5>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadAudioModal">
                                        <i class="fas fa-upload me-2"></i>อัพโหลดไฟล์เสียงใหม่
                                    </button>
                                </div>
                                
                                <div class="audio-list mt-4">
                                    <?php if (empty($audioFiles)): ?>
                                        <div class="text-center text-muted py-5">
                                            <i class="fas fa-music fa-3x mb-3"></i>
                                            <p>ยังไม่มีไฟล์เสียงที่อัพโหลด</p>
                                        </div>
                                    <?php else: ?>
                                        <?php 
                                        $audioTypeLabels = [
                                            'queue_number' => 'หมายเลขคิว',
                                            'service_point' => 'จุดบริการ',
                                            'message' => 'ข้อความ',
                                            'system' => 'ระบบ'
                                        ];
                                        
                                        $audioTypeBadgeClass = [
                                            'queue_number' => 'bg-primary',
                                            'service_point' => 'bg-success',
                                            'message' => 'bg-info',
                                            'system' => 'bg-warning'
                                        ];
                                        ?>
                                        
                                        <?php foreach ($audioFiles as $audio): ?>
                                            <div class="audio-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <span class="badge <?php echo $audioTypeBadgeClass[$audio['audio_type']] ?? 'bg-secondary'; ?>">
                                                            <?php echo $audioTypeLabels[$audio['audio_type']] ?? 'อื่นๆ'; ?>
                                                        </span>
                                                        <strong><?php echo htmlspecialchars($audio['display_name']); ?></strong>
                                                    </div>
                                                    <div>
                                                        <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="playAudio('<?php echo $audio['file_path']; ?>')">
                                                            <i class="fas fa-play"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDeleteAudio(<?php echo $audio['audio_id']; ?>, '<?php echo htmlspecialchars($audio['display_name'], ENT_QUOTES); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="mt-2">
                                                    <small class="text-muted">ไฟล์: <?php echo $audio['file_name']; ?></small>
                                                </div>
                                                <audio class="d-none" id="audio-<?php echo $audio['audio_id']; ?>" src="<?php echo $audio['file_path']; ?>"></audio>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Call History Tab -->
                        <div class="tab-pane fade" id="call-history">
                            <div class="content-card">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0">ประวัติการเรียกเสียง</h5>
                                    <div>
                                        <button type="button" class="btn btn-outline-primary" id="refreshCallHistory">
                                            <i class="fas fa-sync-alt me-2"></i>รีเฟรช
                                        </button>
                                    </div>
                                </div>
                                
                                <div id="callHistoryTable" class="mt-4">
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">กำลังโหลด...</span>
                                        </div>
                                        <p class="mt-2">กำลังโหลดข้อมูล...</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Voice Templates Management Tab -->
                        <div class="tab-pane fade" id="voice-templates-management">
                            <div class="content-card">
                                <h5 class="mb-4">จัดการรูปแบบข้อความ</h5>
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="template_name" class="form-label">ชื่อรูปแบบ</label>
                                        <input type="text" class="form-control" id="template_name" name="template_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="template_text" class="form-label">ข้อความเสียงเรียก</label>
                                        <textarea class="form-control" id="template_text" name="template_text" rows="3" required></textarea>
                                        <div class="form-text">
                                            ตัวแปรที่ใช้ได้: {queue_number}, {service_point_name}, {patient_name}
                                        </div>
                                    </div>
                                    <button type="submit" name="add_template" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>เพิ่มรูปแบบ
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Template Modal -->
    <div class="modal fade" id="addTemplateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">เพิ่มรูปแบบข้อความเสียงเรียก</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="template_name" class="form-label">ชื่อรูปแบบ</label>
                            <input type="text" class="form-control" id="template_name" name="template_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="template_text" class="form-label">ข้อความเสียงเรียก</label>
                            <textarea class="form-control" id="template_text" name="template_text" rows="3" required></textarea>
                            <div class="form-text">
                                ตัวแปรที่ใช้ได้: {queue_number}, {service_point_name}, {patient_name}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="add_template" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Template Modal -->
    <div class="modal fade" id="editTemplateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">แก้ไขรูปแบบข้อความเสียงเรียก</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_template_id" name="template_id">
                        <div class="mb-3">
                            <label for="edit_template_name" class="form-label">ชื่อรูปแบบ</label>
                            <input type="text" class="form-control" id="edit_template_name" name="template_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_template_text" class="form-label">ข้อความเสียงเรียก</label>
                            <textarea class="form-control" id="edit_template_text" name="template_text" rows="3" required></textarea>
                            <div class="form-text">
                                ตัวแปรที่ใช้ได้: {queue_number}, {service_point_name}, {patient_name}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="edit_template" class="btn btn-primary">บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Template Confirmation Modal -->
    <div class="modal fade" id="deleteTemplateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ยืนยันการลบ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>คุณแน่ใจหรือไม่ว่าต้องการลบรูปแบบข้อความนี้?</p>
                    <p id="delete_template_name" class="fw-bold"></p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="">
                        <input type="hidden" id="delete_template_id" name="template_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="delete_template" class="btn btn-danger">ลบ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Set Default Template Confirmation Modal -->
    <div class="modal fade" id="defaultTemplateModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ยืนยันการตั้งค่าเริ่มต้น</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>คุณแน่ใจหรือไม่ว่าต้องการตั้งรูปแบบนี้เป็นค่าเริ่มต้น?</p>
                    <p id="default_template_name" class="fw-bold"></p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="">
                        <input type="hidden" id="default_template_id" name="template_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="set_default_template" class="btn btn-success">ยืนยัน</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upload Audio Modal -->
    <div class="modal fade" id="uploadAudioModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">อัพโหลดไฟล์เสียง</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="display_name" class="form-label">ชื่อที่ต้องการแสดง</label>
                            <input type="text" class="form-control" id="display_name" name="display_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="audio_type" class="form-label">ประเภทเสียง</label>
                            <select class="form-select" id="audio_type" name="audio_type" required>
                                <option value="">-- เลือกประเภท --</option>
                                <option value="queue_number">หมายเลขคิว</option>
                                <option value="service_point">จุดบริการ</option>
                                <option value="message">ข้อความ</option>
                                <option value="system">ระบบ</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="audio_file" class="form-label">ไฟล์เสียง (MP3, WAV, OGG)</label>
                            <input type="file" class="form-control" id="audio_file" name="audio_file" accept=".mp3,.wav,.ogg" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="upload_audio" class="btn btn-primary">อัพโหลด</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Audio Confirmation Modal -->
    <div class="modal fade" id="deleteAudioModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ยืนยันการลบ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>คุณแน่ใจหรือไม่ว่าต้องการลบไฟล์เสียงนี้?</p>
                    <p id="delete_audio_name" class="fw-bold"></p>
                </div>
                <div class="modal-footer">
                    <form method="POST" action="">
                        <input type="hidden" id="delete_audio_id" name="audio_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="delete_audio" class="btn btn-danger">ลบ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Update range input display values
            $('#audioVolume').on('input', function() {
                $('#volumeValue').text($(this).val());
            });

            // Load call history
            loadCallHistory();
            
            // Refresh call history
            $('#refreshCallHistory').on('click', function() {
                loadCallHistory();
            });
        });
        
function loadCallHistory() {
    $('#callHistoryTable').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">กำลังโหลด...</span></div><p class="mt-2">กำลังโหลดข้อมูล...</p></div>');
    
    $.get('../api/get_audio_call_history.php', function(data) {
        if (data.success) {
            let html = '';
            if (data.history.length === 0) {
                html = '<div class="text-center text-muted py-5"><i class="fas fa-history fa-3x mb-3"></i><p>ยังไม่มีประวัติการเรียกเสียง</p></div>';
            } else {
                html = '<div class="table-responsive"><table class="table table-striped"><thead><tr><th>วันที่/เวลา</th><th>หมายเลขคิว</th><th>จุดบริการ</th><th>เจ้าหน้าที่</th><th>ข้อความ</th><th>สถานะ</th></tr></thead><tbody>';
                
                data.history.forEach(function(item) {
                    const statusBadge = item.audio_status === 'played' ? 'bg-success' : 
                                       item.audio_status === 'failed' ? 'bg-danger' : 'bg-warning';
                    const statusText = item.audio_status === 'played' ? 'เล่นแล้ว' : 
                                      item.audio_status === 'failed' ? 'ล้มเหลว' : 'รอดำเนินการ';
                    
                    html += `<tr>
                        <td>${formatDateTime(item.call_time)}</td>
                        <td><strong>${item.queue_number || '-'}</strong></td>
                        <td>${item.service_point_name || '-'}</td>
                        <td>${item.staff_name || '-'}</td>
                        <td>${item.message || '-'}</td>
                        <td><span class="badge ${statusBadge}">${statusText}</span></td>
                    </tr>`;
                });
                
                html += '</tbody></table></div>';
            }
            $('#callHistoryTable').html(html);
        } else {
            $('#callHistoryTable').html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>เกิดข้อผิดพลาดในการโหลดข้อมูล</div>');
        }
    }).fail(function() {
        $('#callHistoryTable').html('<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>เกิดข้อผิดพลาดในการเชื่อมต่อ</div>');
    });
}

function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    return date.toLocaleString('th-TH', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function editTemplate(templateId, templateName, templateText) {
    $('#edit_template_id').val(templateId);
    $('#edit_template_name').val(templateName);
    $('#edit_template_text').val(templateText);
    $('#editTemplateModal').modal('show');
}

function confirmDeleteTemplate(templateId, templateName) {
    $('#delete_template_id').val(templateId);
    $('#delete_template_name').text(templateName);
    $('#deleteTemplateModal').modal('show');
}

function confirmDefaultTemplate(templateId, templateName) {
    $('#default_template_id').val(templateId);
    $('#default_template_name').text(templateName);
    $('#defaultTemplateModal').modal('show');
}

function confirmDeleteAudio(audioId, audioName) {
    $('#delete_audio_id').val(audioId);
    $('#delete_audio_name').text(audioName);
    $('#deleteAudioModal').modal('show');
}

function playTemplate(templateId, templateText) {
    // Replace template variables with sample data
    let sampleText = templateText
        .replace('{queue_number}', 'A001')
        .replace('{service_point_name}', 'ห้องตรวจ 1')
        .replace('{patient_name}', 'คุณสมชาย');
    
    // Use Text-to-Speech to play the sample
    if ('speechSynthesis' in window) {
        const utterance = new SpeechSynthesisUtterance(sampleText);
        utterance.lang = 'th-TH';
        utterance.rate = 0.8;
        utterance.pitch = 1;
        speechSynthesis.speak(utterance);
    } else {
        alert('เบราว์เซอร์ของคุณไม่รองรับการอ่านเสียง');
    }
}

function playAudio(filePath) {
    const audio = new Audio(filePath);
    audio.play().catch(function(error) {
        console.error('Error playing audio:', error);
        alert('ไม่สามารถเล่นไฟล์เสียงได้');
    });
}
    </script>
</body>
</html>
