<?php
require_once '../config/config.php';
requireLogin();

if (!hasPermission('manage_settings')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $settingsToUpdate = $_POST['settings'] ?? [];
        
        // Define all boolean settings that need explicit handling
        $booleanSettings = [
            'enable_priority_queue',
            'auto_forward_enabled',
            'tts_enabled',
            'sound_notification_before', // Added this setting
            'email_notifications',
            'telegram_notifications'
        ];

        foreach ($settingsToUpdate as $key => $value) {
            setSetting($key, $value);
        }

        // Explicitly handle boolean settings (checkboxes)
        foreach ($booleanSettings as $key) {
            if (!isset($settingsToUpdate[$key])) {
                // If the checkbox value is not in the POST data, it means it was unchecked
                setSetting($key, 'false');
            }
        }
        
        logActivity("อัปเดตการตั้งค่าระบบ");
        $message = 'บันทึกการตั้งค่าสำเร็จ';
        $messageType = 'success';
        
    } catch (Exception $e) {
        $message = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get current settings
$currentSettings = [
    // Application Configuration
    'app_name' => getSetting('app_name', 'โรงพยาบาลยุวประสาทไวทโยปถัมภ์'),
    'app_description' => getSetting('app_description', 'ระบบจัดการคิวโรงพยาบาล'),
    'app_logo' => getSetting('app_logo', ''),
    'app_timezone' => getSetting('app_timezone', 'Asia/Bangkok'),
    'app_language' => getSetting('app_language', 'th'),
    
    // Queue System Configuration
    'queue_prefix_length' => getSetting('queue_prefix_length', '1'),
    'queue_number_length' => getSetting('queue_number_length', '3'),
    'max_queue_per_day' => getSetting('max_queue_per_day', '999'),
    'queue_timeout_minutes' => getSetting('queue_timeout_minutes', '30'),
    'display_refresh_interval' => getSetting('display_refresh_interval', '3'),
    'enable_priority_queue' => getSetting('enable_priority_queue', 'true'),
    'auto_forward_enabled' => getSetting('auto_forward_enabled', 'false'),
    
    // Working Hours
    'working_hours_start' => getSetting('working_hours_start', '08:00'),
    'working_hours_end' => getSetting('working_hours_end', '16:00'),
    
    // Audio/TTS Configuration
    'tts_enabled' => getSetting('tts_enabled', 'true'),
    'tts_provider' => getSetting('tts_provider', 'browser'), // Changed default to 'browser' as per previous fix
    'tts_api_url' => getSetting('tts_api_url', ''),
    'tts_language' => getSetting('tts_language', 'th-TH'),
    'tts_voice' => getSetting('tts_voice', 'th-TH-Standard-A'),
    'tts_speed' => getSetting('tts_speed', '1.0'),
    'tts_pitch' => getSetting('tts_pitch', '0'),
    'audio_volume' => getSetting('audio_volume', '1.0'),
    'audio_repeat_count' => getSetting('audio_repeat_count', '1'),
    'sound_notification_before' => getSetting('sound_notification_before', 'true'), // Added this setting
    
    // Google Cloud TTS
    'google_cloud_project_id' => getSetting('google_cloud_project_id', ''),
    'google_cloud_key_file' => getSetting('google_cloud_key_file', ''),
    
    // Azure Speech Service
    'azure_speech_key' => getSetting('azure_speech_key', ''),
    'azure_speech_region' => getSetting('azure_speech_region', ''),
    
    // Amazon Polly
    'aws_access_key_id' => getSetting('aws_access_key_id', ''),
    'aws_secret_access_key' => getSetting('aws_secret_access_key', ''),
    'aws_region' => getSetting('aws_region', ''),
    
    // Email Configuration
    'email_notifications' => getSetting('email_notifications', 'false'),
    'mail_host' => getSetting('mail_host', 'smtp.gmail.com'),
    'mail_port' => getSetting('mail_port', '587'),
    'mail_username' => getSetting('mail_username', ''),
    'mail_password' => getSetting('mail_password', ''),
    'mail_encryption' => getSetting('mail_encryption', 'tls'),
    'mail_from_address' => getSetting('mail_from_address', 'noreply@hospital.com'),
    'mail_from_name' => getSetting('mail_from_name', 'Queue System'),
    
    // Telegram Configuration
    'telegram_notifications' => getSetting('telegram_notifications', 'false'),
    'telegram_bot_token' => getSetting('telegram_bot_token', ''),
    'telegram_chat_id' => getSetting('telegram_chat_id', ''),
    'telegram_admin_chat_id' => getSetting('telegram_admin_chat_id', ''),
    'telegram_group_chat_id' => getSetting('telegram_group_chat_id', ''),
];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การตั้งค่าระบบ - <?php echo getAppName(); ?></title>
    
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
        
        .setting-group {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .setting-group h6 {
            color: #007bff;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .form-switch .form-check-input {
            width: 3rem;
            height: 1.5rem;
        }
        
        .preview-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
            font-family: monospace;
        }
        
        .telegram-preview {
            background: #0088cc;
            color: white;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 0.5rem;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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
                    
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>แดชบอร์ด
                        </a>
                        <a class="nav-link" href="realtime_dashboard.php">
                            <i class="fas fa-chart-line"></i>Dashboard Analytics
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i>จัดการผู้ใช้
                        </a>
                        <a class="nav-link" href="roles.php">
                            <i class="fas fa-user-tag"></i>บทบาทและสิทธิ์
                        </a>
                        <a class="nav-link" href="service_points.php">
                            <i class="fas fa-map-marker-alt"></i>จุดบริการ
                        </a>
                        <a class="nav-link" href="queue_types.php">
                            <i class="fas fa-list"></i>ประเภทคิว
                        </a>
                        <a class="nav-link" href="service_flows.php">
                            <i class="fas fa-route"></i>Service Flows
                        </a>
                        <a class="nav-link active" href="settings.php">
                            <i class="fas fa-cog"></i>การตั้งค่า
                        </a>
                        <a class="nav-link" href="environment_settings.php">
                            <i class="fas fa-server"></i>Environment
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar"></i>รายงาน
                        </a>
                        <a class="nav-link" href="audit_logs.php">
                            <i class="fas fa-history"></i>บันทึกการใช้งาน
                        </a>
                        <hr class="my-3" style="border-color: rgba(255,255,255,0.3);">
                        <a class="nav-link" href="../staff/dashboard.php">
                            <i class="fas fa-arrow-left"></i>กลับหน้าเจ้าหน้าที่
                        </a>
                        <a class="nav-link" href="../staff/logout.php">
                            <i class="fas fa-sign-out-alt"></i>ออกจากระบบ
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2>การตั้งค่าระบบ</h2>
                            <p class="text-muted">กำหนดค่าการทำงานของระบบเรียกคิว</p>
                        </div>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <!-- Application Configuration -->
                        <div class="content-card">
                            <div class="setting-group">
                                <h6><i class="fas fa-hospital me-2"></i>ข้อมูลแอปพลิเคชัน</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label">ชื่อแอปพลิเคชัน</label>
                                    <input type="text" class="form-control" name="settings[app_name]" 
                                           value="<?php echo htmlspecialchars($currentSettings['app_name']); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">คำอธิบายแอปพลิเคชัน</label>
                                    <input type="text" class="form-control" name="settings[app_description]" 
                                           value="<?php echo htmlspecialchars($currentSettings['app_description']); ?>">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">เขตเวลา</label>
                                            <select class="form-select" name="settings[app_timezone]">
                                                <option value="Asia/Bangkok" <?php echo $currentSettings['app_timezone'] == 'Asia/Bangkok' ? 'selected' : ''; ?>>Asia/Bangkok</option>
                                                <option value="Asia/Jakarta" <?php echo $currentSettings['app_timezone'] == 'Asia/Jakarta' ? 'selected' : ''; ?>>Asia/Jakarta</option>
                                                <option value="Asia/Singapore" <?php echo $currentSettings['app_timezone'] == 'Asia/Singapore' ? 'selected' : ''; ?>>Asia/Singapore</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">ภาษา</label>
                                            <select class="form-select" name="settings[app_language]">
                                                <option value="th" <?php echo $currentSettings['app_language'] == 'th' ? 'selected' : ''; ?>>ไทย</option>
                                                <option value="en" <?php echo $currentSettings['app_language'] == 'en' ? 'selected' : ''; ?>>English</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">เวลาเปิดทำการ</label>
                                            <input type="time" class="form-control" name="settings[working_hours_start]" 
                                                   value="<?php echo $currentSettings['working_hours_start']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">เวลาปิดทำการ</label>
                                            <input type="time" class="form-control" name="settings[working_hours_end]" 
                                                   value="<?php echo $currentSettings['working_hours_end']; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Queue Settings -->
                            <div class="setting-group">
                                <h6><i class="fas fa-list-ol me-2"></i>การตั้งค่าคิว</h6>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">ความยาว Prefix คิว</label>
                                            <input type="number" class="form-control" name="settings[queue_prefix_length]" 
                                                   value="<?php echo $currentSettings['queue_prefix_length']; ?>" min="1" max="3">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">ความยาวหมายเลขคิว</label>
                                            <input type="number" class="form-control" name="settings[queue_number_length]" 
                                                   value="<?php echo $currentSettings['queue_number_length']; ?>" min="2" max="5">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">จำนวนคิวสูงสุดต่อวัน</label>
                                            <input type="number" class="form-control" name="settings[max_queue_per_day]" 
                                                   value="<?php echo $currentSettings['max_queue_per_day']; ?>" min="1" max="9999">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">เวลาหมดอายุคิว (นาที)</label>
                                            <input type="number" class="form-control" name="settings[queue_timeout_minutes]" 
                                                   value="<?php echo $currentSettings['queue_timeout_minutes']; ?>" min="5" max="120">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">ความถี่ในการรีเฟรชหน้าจอ (วินาที)</label>
                                            <select class="form-select" name="settings[display_refresh_interval]">
                                                <option value="1" <?php echo $currentSettings['display_refresh_interval'] == '1' ? 'selected' : ''; ?>>1 วินาที</option>
                                                <option value="3" <?php echo $currentSettings['display_refresh_interval'] == '3' ? 'selected' : ''; ?>>3 วินาที</option>
                                                <option value="5" <?php echo $currentSettings['display_refresh_interval'] == '5' ? 'selected' : ''; ?>>5 วินาที</option>
                                                <option value="10" <?php echo $currentSettings['display_refresh_interval'] == '10' ? 'selected' : ''; ?>>10 วินาที</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[enable_priority_queue]" 
                                           value="true" <?php echo $currentSettings['enable_priority_queue'] == 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">เปิดใช้งานคิวพิเศษ (ผู้สูงอายุ/พิการ)</label>
                                </div>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[auto_forward_enabled]" 
                                           value="true" <?php echo $currentSettings['auto_forward_enabled'] == 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">ส่งต่อคิวอัตโนมัติ</label>
                                </div>
                            </div>
                            
                            <!-- Audio Settings -->
                            <div class="setting-group">
                                <h6><i class="fas fa-volume-up me-2"></i>การตั้งค่าเสียง</h6>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[tts_enabled]" 
                                           value="true" <?php echo $currentSettings['tts_enabled'] == 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">เปิดใช้งานระบบเสียงเรียกคิว</label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[sound_notification_before]" 
                                           value="true" <?php echo $currentSettings['sound_notification_before'] == 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">เล่นเสียงแจ้งเตือนก่อนเรียกคิว</label>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">ผู้ให้บริการ TTS</label>
                                            <select class="form-select" name="settings[tts_provider]" id="tts_provider">
                                                <option value="google" <?php echo $currentSettings['tts_provider'] == 'google' ? 'selected' : ''; ?>>Google Cloud TTS</option>
                                                <option value="azure" <?php echo $currentSettings['tts_provider'] == 'azure' ? 'selected' : ''; ?>>Azure Speech Service</option>
                                                <option value="amazon" <?php echo $currentSettings['tts_provider'] == 'amazon' ? 'selected' : ''; ?>>Amazon Polly</option>
                                                <option value="browser" <?php echo $currentSettings['tts_provider'] == 'browser' ? 'selected' : ''; ?>>Browser TTS</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">ภาษา</label>
                                            <select class="form-select" name="settings[tts_language]">
                                                <option value="th-TH" <?php echo $currentSettings['tts_language'] == 'th-TH' ? 'selected' : ''; ?>>ไทย (th-TH)</option>
                                                <option value="en-US" <?php echo $currentSettings['tts_language'] == 'en-US' ? 'selected' : ''; ?>>อังกฤษ (en-US)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">ความเร็ว: <span id="speed_value"><?php echo $currentSettings['tts_speed']; ?></span></label>
                                            <input type="range" class="form-range" name="settings[tts_speed]" id="tts_speed"
                                                   min="0.5" max="2.0" step="0.1" value="<?php echo $currentSettings['tts_speed']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">ระดับเสียง: <span id="volume_value"><?php echo $currentSettings['audio_volume']; ?></span></label>
                                            <input type="range" class="form-range" name="settings[audio_volume]" id="audio_volume"
                                                   min="0.1" max="1.0" step="0.1" value="<?php echo $currentSettings['audio_volume']; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">จำนวนครั้งที่เล่นซ้ำ</label>
                                            <input type="number" class="form-control" name="settings[audio_repeat_count]" 
                                                   value="<?php echo $currentSettings['audio_repeat_count']; ?>" min="1" max="5">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- TTS Provider Settings -->
                                <div id="google_settings" class="provider-settings" style="display: none;">
                                    <h6 class="text-secondary">Google Cloud TTS</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Project ID</label>
                                                <input type="text" class="form-control" name="settings[google_cloud_project_id]" 
                                                       value="<?php echo htmlspecialchars($currentSettings['google_cloud_project_id']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Key File Path</label>
                                                <input type="text" class="form-control" name="settings[google_cloud_key_file]" 
                                                       value="<?php echo htmlspecialchars($currentSettings['google_cloud_key_file']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="azure_settings" class="provider-settings" style="display: none;">
                                    <h6 class="text-secondary">Azure Speech Service</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Speech Key</label>
                                                <input type="password" class="form-control" name="settings[azure_speech_key]" 
                                                       value="<?php echo htmlspecialchars($currentSettings['azure_speech_key']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Region</label>
                                                <input type="text" class="form-control" name="settings[azure_speech_region]" 
                                                       value="<?php echo htmlspecialchars($currentSettings['azure_speech_region']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="amazon_settings" class="provider-settings" style="display: none;">
                                    <h6 class="text-secondary">Amazon Polly</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Access Key ID</label>
                                                <input type="password" class="form-control" name="settings[aws_access_key_id]" 
                                                       value="<?php echo htmlspecialchars($currentSettings['aws_access_key_id']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Secret Access Key</label>
                                                <input type="password" class="form-control" name="settings[aws_secret_access_key]" 
                                                       value="<?php echo htmlspecialchars($currentSettings['aws_secret_access_key']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Region</label>
                                                <input type="text" class="form-control" name="settings[aws_region]" 
                                                       value="<?php echo htmlspecialchars($currentSettings['aws_region']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="testTTS()">
                                    <i class="fas fa-play me-1"></i>ทดสอบเสียง
                                </button>
                            </div>
                            
                            <!-- Email Settings -->
                            <div class="setting-group">
                                <h6><i class="fas fa-envelope me-2"></i>การตั้งค่าอีเมล</h6>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[email_notifications]" 
                                           value="true" <?php echo $currentSettings['email_notifications'] == 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">เปิดใช้งานการแจ้งเตือนทางอีเมล</label>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Host</label>
                                            <input type="text" class="form-control" name="settings[mail_host]" 
                                                   value="<?php echo htmlspecialchars($currentSettings['mail_host']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">SMTP Port</label>
                                            <input type="number" class="form-control" name="settings[mail_port]" 
                                                   value="<?php echo $currentSettings['mail_port']; ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Username</label>
                                            <input type="text" class="form-control" name="settings[mail_username]" 
                                                   value="<?php echo htmlspecialchars($currentSettings['mail_username']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Password</label>
                                            <input type="password" class="form-control" name="settings[mail_password]" 
                                                   value="<?php echo htmlspecialchars($currentSettings['mail_password']); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Encryption</label>
                                            <select class="form-select" name="settings[mail_encryption]">
                                                <option value="tls" <?php echo $currentSettings['mail_encryption'] == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                                <option value="ssl" <?php echo $currentSettings['mail_encryption'] == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                                <option value="" <?php echo $currentSettings['mail_encryption'] == '' ? 'selected' : ''; ?>>None</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">From Address</label>
                                            <input type="email" class="form-control" name="settings[mail_from_address]" 
                                                   value="<?php echo htmlspecialchars($currentSettings['mail_from_address']); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">From Name</label>
                                            <input type="text" class="form-control" name="settings[mail_from_name]" 
                                                   value="<?php echo htmlspecialchars($currentSettings['mail_from_name']); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Telegram Settings -->
                            <div class="setting-group">
                                <h6><i class="fab fa-telegram me-2"></i>การตั้งค่า Telegram</h6>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[telegram_notifications]" 
                                           value="true" <?php echo $currentSettings['telegram_notifications'] == 'true' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">เปิดใช้งานการแจ้งเตือนทาง Telegram</label>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Bot Token</label>
                                    <input type="password" class="form-control" name="settings[telegram_bot_token]" 
                                           value="<?php echo htmlspecialchars($currentSettings['telegram_bot_token']); ?>"
                                           placeholder="123456789:ABCdefGHIjklMNOpqrSTUvwxyz">
                                    <div class="form-text">
                                        สร้าง Bot ใหม่ได้ที่ <a href="https://t.me/BotFather" target="_blank">@BotFather</a>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Chat ID (ทั่วไป)</label>
                                            <input type="text" class="form-control" name="settings[telegram_chat_id]" 
                                                   value="<?php echo htmlspecialchars($currentSettings['telegram_chat_id']); ?>"
                                                   placeholder="-1001234567890">
                                            <div class="form-text">สำหรับแจ้งเตือนทั่วไป</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Admin Chat ID</label>
                                            <input type="text" class="form-control" name="settings[telegram_admin_chat_id]" 
                                                   value="<?php echo htmlspecialchars($currentSettings['telegram_admin_chat_id']); ?>"
                                                   placeholder="123456789">
                                            <div class="form-text">สำหรับแจ้งเตือนผู้ดูแล</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Group Chat ID</label>
                                            <input type="text" class="form-control" name="settings[telegram_group_chat_id]" 
                                                   value="<?php echo htmlspecialchars($currentSettings['telegram_group_chat_id']); ?>"
                                                   placeholder="-1001234567890">
                                            <div class="form-text">สำหรับกลุ่มเจ้าหน้าที่</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">เทมเพลตข้อความ</label>
                                    <input type="text" class="form-control" name="settings[telegram_notify_template]" 
                                           value="<?php echo htmlspecialchars($currentSettings['telegram_notify_template']); ?>"
                                           placeholder="คิว {queue_number} กรุณามาที่จุดบริการ {service_point}">
                                    <div class="form-text">
                                        ใช้ {queue_number} สำหรับหมายเลขคิว และ {service_point} สำหรับชื่อจุดบริการ
                                    </div>
                                    <div class="telegram-preview mt-2">
                                        <strong>🏥 <?php echo getAppName(); ?></strong><br>
                                        <span id="telegram_preview">คิว A001 กรุณามาที่จุดบริการ ห้องตรวจ 1</span>
                                    </div>
                                </div>
                                
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="testTelegram()">
                                    <i class="fab fa-telegram me-1"></i>ทดสอบ Telegram
                                </button>
                            </div>
                        </div>
                        
                        <!-- Save Button -->
                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>บันทึกการตั้งค่า
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Update range value displays
        $('#tts_speed').on('input', function() {
            $('#speed_value').text($(this).val());
        });
        
        $('#audio_volume').on('input', function() {
            $('#volume_value').text($(this).val());
        });
        
        // Show/hide TTS provider settings
        function toggleTTSProviderSettings() {
            const provider = $('#tts_provider').val();
            $('.provider-settings').hide();
            if (provider !== 'browser') { // 'browser' provider doesn't have specific API settings
                $('#' + provider + '_settings').show();
            }
        }
        
        $('#tts_provider').on('change', toggleTTSProviderSettings);
        
        // Initialize on page load
        $(document).ready(function() {
            toggleTTSProviderSettings();
        });
        
        // Preview Telegram message template
        $('input[name="settings[telegram_notify_template]"]').on('input', function() {
            const template = $(this).val();
            const preview = template
                .replace('{queue_number}', 'A001')
                .replace('{service_point}', 'ห้องตรวจ 1');
            $('#telegram_preview').text(preview);
        });
        
        // Test TTS
        function testTTS() {
            const provider = $('#tts_provider').val();
            const language = $('select[name="settings[tts_language]"]').val();
            const speed = $('#tts_speed').val();
            const volume = $('#audio_volume').val();
            const message = "ทดสอบระบบเสียงเรียกคิว หมายเลข A001 เชิญที่ห้องตรวจ 1";
            
            // Disable button during test
            const testButton = $('button[onclick="testTTS()"]');
            testButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>กำลังทดสอบ...');

            if (provider === 'browser') {
                // Use browser TTS
                if (window.speechSynthesis) {
                    const utterance = new SpeechSynthesisUtterance(message);
                    utterance.lang = language;
                    utterance.rate = parseFloat(speed);
                    utterance.volume = parseFloat(volume);
                    
                    utterance.onend = function() {
                        alert('ทดสอบเสียงสำเร็จ');
                        testButton.prop('disabled', false).html('<i class="fas fa-play me-1"></i>ทดสอบเสียง');
                    };
                    utterance.onerror = function(event) {
                        alert('เกิดข้อผิดพลาดในการทดสอบ: ' + event.error);
                        testButton.prop('disabled', false).html('<i class="fas fa-play me-1"></i>ทดสอบเสียง');
                    };
                    speechSynthesis.speak(utterance);
                } else {
                    alert('เบราว์เซอร์ไม่รองรับระบบเสียง');
                    testButton.prop('disabled', false).html('<i class="fas fa-play me-1"></i>ทดสอบเสียง');
                }
            } else {
                // Use API
                $.ajax({
                    url: '../api/test_audio.php',
                    method: 'POST',
                    data: {
                        provider: provider,
                        message: message,
                        language: language,
                        speed: speed,
                        volume: volume
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('ทดสอบเสียงสำเร็จ');
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('เกิดข้อผิดพลาดในการทดสอบ');
                    },
                    complete: function() {
                        testButton.prop('disabled', false).html('<i class="fas fa-play me-1"></i>ทดสอบเสียง');
                    }
                });
            }
        }
        
        // Test Telegram
        function testTelegram() {
            const botToken = $('input[name="settings[telegram_bot_token]"]').val();
            const chatId = $('input[name="settings[telegram_chat_id]"]').val();
            const template = $('input[name="settings[telegram_notify_template]"]').val();
            
            if (!botToken || !chatId) {
                alert('กรุณากรอก Bot Token และ Chat ID');
                return;
            }
            
            const message = template
                .replace('{queue_number}', 'TEST001')
                .replace('{service_point}', 'ทดสอบระบบ');
            
            $.ajax({
                url: '../api/test_telegram.php',
                method: 'POST',
                data: {
                    bot_token: botToken,
                    chat_id: chatId,
                    message: '🧪 ทดสอบระบบ Telegram\n\n' + message
                },
                beforeSend: function() {
                    $('button[onclick="testTelegram()"]').prop('disabled', true)
                        .html('<i class="fas fa-spinner fa-spin me-1"></i>กำลังส่ง...');
                },
                success: function(response) {
                    if (response.success) {
                        alert('ส่งข้อความทดสอบสำเร็จ');
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + response.message);
                    }
                },
                error: function() {
                    alert('เกิดข้อผิดพลาดในการส่งข้อความ');
                },
                complete: function() {
                    $('button[onclick="testTelegram()"]').prop('disabled', false)
                        .html('<i class="fab fa-telegram me-1"></i>ทดสอบ Telegram');
                }
            });
        }
    </script>
</body>
</html>
