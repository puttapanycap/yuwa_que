<?php
require_once 'config/config.php';
requireLogin();

// Get TTS settings
$ttsEnabled = getSetting('tts_enabled', '0');
$ttsProvider = getSetting('tts_provider', 'google');
$ttsLanguage = getSetting('tts_language', 'th-TH');
$ttsVoice = getSetting('tts_voice', 'th-TH-Standard-A');
$ttsSpeed = getSetting('tts_speed', '1.0');
$ttsPitch = getSetting('tts_pitch', '0');
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

// Get recent test history
try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT ach.*, s.username as staff_name 
        FROM audio_call_history ach 
        LEFT JOIN staff s ON ach.staff_id = s.staff_id 
        WHERE ach.message LIKE 'ทดสอบ%' 
        ORDER BY ach.call_time DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $testHistory = $stmt->fetchAll();
} catch (Exception $e) {
    $testHistory = [];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทดสอบระบบ TTS แบบทันสมัย</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --success-color: #4ade80;
            --warning-color: #facc15;
            --danger-color: #f87171;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
        }
        
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .header-gradient {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .card-modern {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 1.5rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        
        .card-modern:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .card-header-modern {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            border: none;
        }
        
        .btn-modern {
            border-radius: 50px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-primary-modern {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
        }
        
        .btn-success-modern {
            background: linear-gradient(135deg, var(--success-color) 0%, #22c55e 100%);
            color: white;
        }
        
        .btn-warning-modern {
            background: linear-gradient(135deg, var(--warning-color) 0%, #eab308 100%);
            color: white;
        }
        
        .btn-danger-modern {
            background: linear-gradient(135deg, var(--danger-color) 0%, #ef4444 100%);
            color: white;
        }
        
        .form-control-modern {
            border-radius: 12px;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .form-control-modern:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .range-slider {
            width: 100%;
            height: 8px;
            border-radius: 5px;
            background: #e2e8f0;
            outline: none;
            -webkit-appearance: none;
        }
        
        .range-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary-color);
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .range-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary-color);
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            border: none;
        }
        
        .badge-modern {
            padding: 6px 12px;
            border-radius: 50px;
            font-weight: 600;
        }
        
        .history-item {
            border-left: 3px solid var(--primary-color);
            padding: 15px 20px;
            margin-bottom: 15px;
            background: white;
            border-radius: 0 10px 10px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
        }
        
        .audio-visualizer {
            height: 60px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 3px;
            margin: 20px 0;
        }
        
        .visualizer-bar {
            width: 4px;
            background: var(--primary-color);
            border-radius: 2px 2px 0 0;
            transition: height 0.2s ease;
        }
        
        .template-preview {
            background: #f1f5f9;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            font-style: italic;
        }
        
        .setting-item {
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .setting-item:last-child {
            border-bottom: none;
        }
        
        .setting-label {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .setting-value {
            color: #64748b;
        }
        
        @media (max-width: 768px) {
            .header-gradient {
                padding: 1.5rem;
            }
            
            .card-modern {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header-gradient text-center">
            <h1><i class="fas fa-volume-up me-3"></i>ทดสอบระบบ TTS แบบทันสมัย</h1>
            <p class="lead mb-0">ทดสอบและปรับแต่งระบบเสียงเรียกคิวของคุณ</p>
        </div>
        
        <!-- Alert Messages -->
        <?php if ($ttsEnabled != '1'): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ระบบเสียงเรียกคิวยังไม่ได้เปิดใช้งาน กรุณาเปิดใช้งานในหน้าตั้งค่าระบบเสียง
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Test TTS Card -->
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h5 class="mb-0"><i class="fas fa-play-circle me-2"></i>ทดสอบเสียงเรียกคิว</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label fw-bold">เลือกรูปแบบข้อความ</label>
                            <select class="form-control-modern" id="templateSelect">
                                <?php foreach ($voiceTemplates as $template): ?>
                                    <option value="<?php echo htmlspecialchars($template['template_text']); ?>" 
                                            <?php echo $template['is_default'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($template['template_name']); ?>
                                        <?php echo $template['is_default'] ? '(ค่าเริ่มต้น)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">ข้อความที่จะอ่าน</label>
                            <textarea class="form-control-modern" id="messageText" rows="3" placeholder="ใส่ข้อความที่ต้องการอ่าน...">หมายเลข A001 เชิญที่ ห้องตรวจ 1</textarea>
                            <div class="form-text">คุณสามารถใช้ตัวแปร: {queue_number}, {service_point_name}, {patient_name}</div>
                        </div>
                        
                        <div class="audio-visualizer" id="visualizer">
                            <?php for ($i = 0; $i < 32; $i++): ?>
                                <div class="visualizer-bar" style="height: 5px;"></div>
                            <?php endfor; ?>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <button class="btn btn-modern btn-primary-modern me-md-2" id="playButton">
                                <i class="fas fa-play me-2"></i>เล่นเสียง
                            </button>
                            <button class="btn btn-modern btn-warning-modern me-md-2" id="stopButton">
                                <i class="fas fa-stop me-2"></i>หยุด
                            </button>
                            <button class="btn btn-modern btn-success-modern" id="testSystemButton">
                                <i class="fas fa-vial me-2"></i>ทดสอบระบบ
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Template Preview Card -->
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h5 class="mb-0"><i class="fas fa-eye me-2"></i>ตัวอย่างข้อความ</h5>
                    </div>
                    <div class="card-body">
                        <div class="template-preview" id="templatePreview">
                            หมายเลข A001 เชิญที่ ห้องตรวจ 1
                        </div>
                    </div>
                </div>
                
                <!-- Test History Card -->
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>ประวัติการทดสอบ</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($testHistory)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                <p class="text-muted">ยังไม่มีประวัติการทดสอบ</p>
                            </div>
                        <?php else: ?>
                            <div class="history-list">
                                <?php foreach ($testHistory as $history): ?>
                                    <div class="history-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($history['message']); ?></strong>
                                                <div class="small text-muted">
                                                    โดย: <?php echo htmlspecialchars($history['staff_name'] ?? 'ระบบ'); ?> | 
                                                    <?php echo date('d/m/Y H:i', strtotime($history['call_time'])); ?>
                                                </div>
                                            </div>
                                            <span class="badge bg-success status-badge">สำเร็จ</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- TTS Settings Card -->
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>การตั้งค่า TTS</h5>
                    </div>
                    <div class="card-body">
                        <div class="setting-item">
                            <div class="d-flex justify-content-between">
                                <span class="setting-label">ผู้ให้บริการ</span>
                                <span class="setting-value"><?php echo ucfirst($ttsProvider); ?></span>
                            </div>
                        </div>
                        
                        <div class="setting-item">
                            <div class="d-flex justify-content-between">
                                <span class="setting-label">ภาษา</span>
                                <span class="setting-value"><?php echo $ttsLanguage; ?></span>
                            </div>
                        </div>
                        
                        <div class="setting-item">
                            <div class="d-flex justify-content-between">
                                <span class="setting-label">เสียง</span>
                                <span class="setting-value"><?php echo $ttsVoice; ?></span>
                            </div>
                        </div>
                        
                        <div class="setting-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="setting-label">ความเร็ว</span>
                                <span class="setting-value" id="speedValue"><?php echo $ttsSpeed; ?></span>
                            </div>
                            <input type="range" class="range-slider mt-2" id="speedSlider" min="0.5" max="2.0" step="0.1" value="<?php echo $ttsSpeed; ?>">
                        </div>
                        
                        <div class="setting-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="setting-label">ระดับเสียงสูง-ต่ำ</span>
                                <span class="setting-value" id="pitchValue"><?php echo $ttsPitch; ?></span>
                            </div>
                            <input type="range" class="range-slider mt-2" id="pitchSlider" min="-10" max="10" step="1" value="<?php echo $ttsPitch; ?>">
                        </div>
                        
                        <div class="setting-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="setting-label">ความดัง</span>
                                <span class="setting-value" id="volumeValue"><?php echo $audioVolume; ?></span>
                            </div>
                            <input type="range" class="range-slider mt-2" id="volumeSlider" min="0" max="1" step="0.1" value="<?php echo $audioVolume; ?>">
                        </div>
                        
                        <div class="setting-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="setting-label">จำนวนครั้งที่เล่นซ้ำ</span>
                                <span class="setting-value"><?php echo $audioRepeatCount; ?></span>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <a href="admin/audio_settings.php" class="btn btn-modern btn-primary-modern w-100">
                                <i class="fas fa-cog me-2"></i>ตั้งค่าระบบเสียง
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- System Status Card -->
                <div class="card-modern">
                    <div class="card-header-modern">
                        <h5 class="mb-0"><i class="fas fa-heartbeat me-2"></i>สถานะระบบ</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                <?php if ($ttsEnabled == '1'): ?>
                                    <i class="fas fa-check-circle text-success fa-2x"></i>
                                <?php else: ?>
                                    <i class="fas fa-times-circle text-danger fa-2x"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h6 class="mb-0">ระบบเสียงเรียกคิว</h6>
                                <small class="<?php echo $ttsEnabled == '1' ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $ttsEnabled == '1' ? 'เปิดใช้งาน' : 'ปิดใช้งาน'; ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                <i class="fas fa-database text-primary fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">การเชื่อมต่อฐานข้อมูล</h6>
                                <small class="text-success">เชื่อมต่อปกติ</small>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-server text-info fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">ผู้ให้บริการ TTS</h6>
                                <small class="text-primary"><?php echo ucfirst($ttsProvider); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Back Button -->
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-modern btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>กลับหน้าหลัก
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Update template preview when template changes
            $('#templateSelect').on('change', function() {
                const template = $(this).val();
                const preview = template
                    .replace('{queue_number}', 'A001')
                    .replace('{service_point_name}', 'ห้องตรวจ 1')
                    .replace('{patient_name}', 'คุณทดสอบ');
                $('#messageText').val(preview);
                $('#templatePreview').text(preview);
            });
            
            // Update message preview when text changes
            $('#messageText').on('input', function() {
                $('#templatePreview').text($(this).val());
            });
            
            // Update slider values
            $('#speedSlider').on('input', function() {
                $('#speedValue').text($(this).val());
            });
            
            $('#pitchSlider').on('input', function() {
                $('#pitchValue').text($(this).val());
            });
            
            $('#volumeSlider').on('input', function() {
                $('#volumeValue').text($(this).val());
            });
            
            // Play button
            $('#playButton').on('click', function() {
                const message = $('#messageText').val();
                if (!message.trim()) {
                    alert('กรุณาใส่ข้อความที่ต้องการอ่าน');
                    return;
                }
                
                // Animate visualizer
                animateVisualizer();
                
                // Use Web Speech API for testing
                if ('speechSynthesis' in window) {
                    const utterance = new SpeechSynthesisUtterance(message);
                    utterance.lang = '<?php echo $ttsLanguage; ?>';
                    utterance.rate = parseFloat($('#speedSlider').val());
                    utterance.pitch = parseFloat($('#pitchSlider').val());
                    utterance.volume = parseFloat($('#volumeSlider').val());
                    
                    utterance.onend = function() {
                        stopVisualizer();
                    };
                    
                    speechSynthesis.speak(utterance);
                } else {
                    alert('เบราว์เซอร์ของคุณไม่รองรับการอ่านเสียง');
                    stopVisualizer();
                }
            });
            
            // Stop button
            $('#stopButton').on('click', function() {
                if ('speechSynthesis' in window) {
                    speechSynthesis.cancel();
                }
                stopVisualizer();
            });
            
            // Test system button
            $('#testSystemButton').on('click', function() {
                $.get('api/test_audio.php', function(response) {
                    if (response.success) {
                        alert('ทดสอบระบบสำเร็จ: ' + response.message);
                        // Reload page to update history
                        location.reload();
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + response.error);
                    }
                }).fail(function() {
                    alert('ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                });
            });
            
            // Visualizer animation
            let visualizerInterval;
            
            function animateVisualizer() {
                stopVisualizer();
                const bars = $('#visualizer .visualizer-bar');
                
                visualizerInterval = setInterval(function() {
                    bars.each(function() {
                        const height = Math.floor(Math.random() * 50) + 5;
                        $(this).css('height', height + 'px');
                    });
                }, 100);
            }
            
            function stopVisualizer() {
                if (visualizerInterval) {
                    clearInterval(visualizerInterval);
                    visualizerInterval = null;
                }
                $('#visualizer .visualizer-bar').css('height', '5px');
            }
            
            // Initialize preview
            $('#templatePreview').text($('#messageText').val());
        });
    </script>
</body>
</html>
