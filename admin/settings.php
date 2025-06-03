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
        $settings = $_POST['settings'] ?? [];
        
        foreach ($settings as $key => $value) {
            setSetting($key, $value);
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
    'hospital_name' => getSetting('hospital_name', 'โรงพยาบาลยุวประสาทไวทโยปถัมภ์'),
    'tts_enabled' => getSetting('tts_enabled', '1'),
    'tts_api_url' => getSetting('tts_api_url', ''),
    'queue_call_template' => getSetting('queue_call_template', 'หมายเลข {queue_number} เชิญที่ {service_point_name}'),
    'auto_forward_enabled' => getSetting('auto_forward_enabled', '0'),
    'max_queue_per_day' => getSetting('max_queue_per_day', '999'),
    'queue_timeout_minutes' => getSetting('queue_timeout_minutes', '30'),
    'display_refresh_interval' => getSetting('display_refresh_interval', '3'),
    'enable_priority_queue' => getSetting('enable_priority_queue', '1'),
    'working_hours_start' => getSetting('working_hours_start', '08:00'),
    'working_hours_end' => getSetting('working_hours_end', '16:00')
];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การตั้งค่าระบบ - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    
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
                        <a class="nav-link active" href="settings.php">
                            <i class="fas fa-cog"></i>การตั้งค่า
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
                        <!-- General Settings -->
                        <div class="content-card">
                            <div class="setting-group">
                                <h6><i class="fas fa-hospital me-2"></i>ข้อมูลโรงพยาบาล</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label">ชื่อโรงพยาบาล</label>
                                    <input type="text" class="form-control" name="settings[hospital_name]" 
                                           value="<?php echo htmlspecialchars($currentSettings['hospital_name']); ?>">
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
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">จำนวนคิวสูงสุดต่อวัน</label>
                                            <input type="number" class="form-control" name="settings[max_queue_per_day]" 
                                                   value="<?php echo $currentSettings['max_queue_per_day']; ?>" min="1" max="9999">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">เวลาหมดอายุคิว (นาที)</label>
                                            <input type="number" class="form-control" name="settings[queue_timeout_minutes]" 
                                                   value="<?php echo $currentSettings['queue_timeout_minutes']; ?>" min="5" max="120">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[enable_priority_queue]" 
                                           value="1" <?php echo $currentSettings['enable_priority_queue'] == '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">เปิดใช้งานคิวพิเศษ (ผู้สูงอายุ/พิการ)</label>
                                </div>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[auto_forward_enabled]" 
                                           value="1" <?php echo $currentSettings['auto_forward_enabled'] == '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">ส่งต่อคิวอัตโนมัติ</label>
                                </div>
                            </div>
                            
                            <!-- Display Settings -->
                            <div class="setting-group">
                                <h6><i class="fas fa-desktop me-2"></i>การแสดงผล</h6>
                                
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
                            
                            <!-- Audio Settings -->
                            <div class="setting-group">
                                <h6><i class="fas fa-volume-up me-2"></i>การตั้งค่าเสียง</h6>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="settings[tts_enabled]" 
                                           value="1" <?php echo $currentSettings['tts_enabled'] == '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label">เปิดใช้งานระบบเสียงเรียกคิว</label>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">รูปแบบข้อความเรียกคิว</label>
                                    <input type="text" class="form-control" name="settings[queue_call_template]" 
                                           value="<?php echo htmlspecialchars($currentSettings['queue_call_template']); ?>"
                                           placeholder="หมายเลข {queue_number} เชิญที่ {service_point_name}">
                                    <div class="form-text">
                                        ใช้ {queue_number} สำหรับหมายเลขคิว และ {service_point_name} สำหรับชื่อจุดบริการ
                                    </div>
                                    <div class="preview-box">
                                        ตัวอย่าง: หมายเลข A001 เชิญที่ ห้องตรวจ 1
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">URL API สำหรับ Text-to-Speech (ถ้ามี)</label>
                                    <input type="url" class="form-control" name="settings[tts_api_url]" 
                                           id="tts_api_url"
                                           value="<?php echo htmlspecialchars($currentSettings['tts_api_url']); ?>"
                                           placeholder="https://api.example.com/tts">
                                    <div class="form-text">เว้นว่างเพื่อใช้ระบบเสียงของเบราว์เซอร์</div>
                                    
                                    <!-- TTS API Controls -->
                                    <div id="tts_api_controls" class="mt-3" style="display: none;">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">ภาษา</label>
                                                <select class="form-select" id="tts_language">
                                                    <option value="th">ไทย (th)</option>
                                                    <option value="en">อังกฤษ (en)</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">ความเร็ว: <span id="speed_value">1.0</span></label>
                                                <input type="range" class="form-range" id="tts_speed" 
                                                       min="0.5" max="2.0" step="0.1" value="1.0">
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="testTTS()">
                                            <i class="fas fa-play me-1"></i>ทดสอบเสียง
                                        </button>
                                    </div>
                                    
                                    <!-- Browser TTS Controls -->
                                    <div id="browser_tts_controls" class="mt-3">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="testBrowserTTS()">
                                            <i class="fas fa-play me-1"></i>ทดสอบเสียง (เบราว์เซอร์)
                                        </button>
                                    </div>
                                </div>
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
        // Preview queue call template
        $('input[name="settings[queue_call_template]"]').on('input', function() {
            const template = $(this).val();
            const preview = template
                .replace('{queue_number}', 'A001')
                .replace('{service_point_name}', 'ห้องตรวจ 1');
            $('.preview-box').text('ตัวอย่าง: ' + preview);
        });
        
        // Show/hide TTS controls based on API URL
        function toggleTTSControls() {
            const apiUrl = $('#tts_api_url').val().trim();
            if (apiUrl) {
                $('#tts_api_controls').show();
                $('#browser_tts_controls').hide();
            } else {
                $('#tts_api_controls').hide();
                $('#browser_tts_controls').show();
            }
        }
        
        // Update speed value display
        $('#tts_speed').on('input', function() {
            $('#speed_value').text($(this).val());
        });
        
        // Monitor API URL changes
        $('#tts_api_url').on('input', toggleTTSControls);
        
        // Initialize on page load
        $(document).ready(function() {
            toggleTTSControls();
        });
        
        // Test TTS with API
        function testTTS() {
            const apiUrl = $('#tts_api_url').val().trim();
            const template = $('input[name="settings[queue_call_template]"]').val();
            const message = template
                .replace('{queue_number}', 'A001')
                .replace('{service_point_name}', 'ห้องตรวจ 1');
            
            if (apiUrl) {
                // Use API
                const language = $('#tts_language').val();
                const speed = parseFloat($('#tts_speed').val());
                
                $.ajax({
                    url: apiUrl,
                    method: 'POST',
                    data: {
                        text: message,
                        language: language,
                        speed: speed
                    },
                    xhrFields: {
                        responseType: 'blob'
                    },
                    beforeSend: function() {
                        $('button[onclick="testTTS()"]').prop('disabled', true)
                            .html('<i class="fas fa-spinner fa-spin me-1"></i>กำลังทดสอบ...');
                    },
                    success: function(blob, status, xhr) {
                        try {
                            // สร้าง URL จาก blob
                            const audioUrl = URL.createObjectURL(blob);
                            
                            // สร้าง audio element และเล่น
                            const audio = new Audio(audioUrl);
                            audio.onloadeddata = function() {
                                audio.play().then(() => {
                                    console.log('TTS API audio played successfully');
                                }).catch(error => {
                                    console.error('Failed to play TTS API audio:', error);
                                    alert('ไม่สามารถเล่นเสียงได้: ' + error.message);
                                });
                            };
                            
                            audio.onerror = function() {
                                console.error('Audio loading error');
                                alert('ไม่สามารถโหลดไฟล์เสียงได้');
                            };
                            
                            audio.onended = function() {
                                // ล้าง URL เมื่อเล่นเสร็จ
                                URL.revokeObjectURL(audioUrl);
                            };
                            
                            // แสดงข้อความสำเร็จ
                            setTimeout(() => {
                                alert('ทดสอบเสียง API สำเร็จ');
                            }, 100);
                            
                        } catch (error) {
                            console.error('Error creating audio from blob:', error);
                            alert('เกิดข้อผิดพลาดในการสร้างเสียง: ' + error.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('TTS API Error:', error);
                        alert('เกิดข้อผิดพลาดในการทดสอบ: ' + error);
                    },
                    complete: function() {
                        $('button[onclick="testTTS()"]').prop('disabled', false)
                            .html('<i class="fas fa-play me-1"></i>ทดสอบเสียง');
                    }
                });
            } else {
                testBrowserTTS();
            }
        }
        
        // Test browser TTS
        function testBrowserTTS() {
            const template = $('input[name="settings[queue_call_template]"]').val();
            const message = template
                .replace('{queue_number}', 'A001')
                .replace('{service_point_name}', 'ห้องตรวจ 1');
            
            if (window.speechSynthesis) {
                const utterance = new SpeechSynthesisUtterance(message);
                utterance.lang = 'th-TH';
                utterance.rate = 0.8;
                speechSynthesis.speak(utterance);
            } else {
                alert('เบราว์เซอร์ไม่รองรับระบบเสียง');
            }
        }
    </script>
</body>
</html>
