<?php
require_once '../config/config.php';

// Get service point from URL parameter
$servicePointId = $_GET['service_point'] ?? null;
$servicePointName = 'ทุกจุดบริการ';

if ($servicePointId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT point_name FROM service_points WHERE service_point_id = ? AND is_active = 1");
        $stmt->execute([$servicePointId]);
        $servicePoint = $stmt->fetch();
        if ($servicePoint) {
            $servicePointName = $servicePoint['point_name'];
        }
    } catch (Exception $e) {
        // Use default name
    }
}

$hospitalName = getSetting('hospital_name', 'โรงพยาบาลยุวประสาทไวทโยปถัมภ์');
$ttsEnabled = getSetting('tts_enabled', '1');
$queueCallTemplate = getSetting('queue_call_template', 'หมายเลข {queue_number} เชิญที่ {service_point_name}');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าจอแสดงคิว - <?php echo htmlspecialchars($servicePointName); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        .monitor-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            color: white;
        }

        .header {
            background: rgba(0,0,0,0.3);
            padding: clamp(0.5rem, 2vw, 2rem);
            text-align: center;
            border-bottom: 3px solid rgba(255,255,255,0.3);
            flex-shrink: 0;
        }

        .hospital-name {
            font-size: clamp(1.5rem, 4vw, 2.5rem);
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            line-height: 1.2;
        }

        .service-point-name {
            font-size: clamp(1rem, 3vw, 1.8rem);
            font-weight: 600;
            opacity: 0.9;
            line-height: 1.2;
        }

        .current-time {
            font-size: clamp(0.8rem, 2vw, 1.2rem);
            opacity: 0.8;
            margin-top: 0.5rem;
        }

        .content {
            flex: 1;
            display: flex;
            padding: clamp(0.5rem, 2vw, 2rem);
            gap: clamp(0.5rem, 2vw, 2rem);
            min-height: 0;
            overflow: hidden;
        }

        .current-queue-section {
            flex: 2;
            background: rgba(255,255,255,0.1);
            border-radius: clamp(10px, 2vw, 20px);
            padding: clamp(1rem, 3vw, 2rem);
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 0;
        }

        .waiting-queue-section {
            flex: 1;
            background: rgba(255,255,255,0.1);
            border-radius: clamp(10px, 2vw, 20px);
            padding: clamp(1rem, 2vw, 2rem);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }

        .current-queue-title {
            font-size: clamp(1.2rem, 3vw, 2rem);
            font-weight: 700;
            margin-bottom: clamp(1rem, 3vw, 2rem);
            color: #FFD700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .current-queue-number {
            font-size: clamp(3rem, 12vw, 8rem);
            font-weight: 900;
            color: #FFD700;
            text-shadow: 4px 4px 8px rgba(0,0,0,0.5);
            margin: clamp(1rem, 3vw, 2rem) 0;
            animation: pulse 2s infinite;
            line-height: 1;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .current-queue-info {
            font-size: clamp(1rem, 2.5vw, 1.5rem);
            margin-bottom: 1rem;
            opacity: 0.9;
            line-height: 1.4;
        }

        .no-current-queue {
            font-size: clamp(1.5rem, 4vw, 3rem);
            color: rgba(255,255,255,0.6);
            margin: clamp(2rem, 5vw, 4rem) 0;
            line-height: 1.2;
        }

        .waiting-title {
            font-size: clamp(1rem, 2.5vw, 1.5rem);
            font-weight: 700;
            margin-bottom: clamp(0.5rem, 2vw, 1.5rem);
            text-align: center;
            color: #87CEEB;
            flex-shrink: 0;
        }

        .waiting-queues-container {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 0.5rem;
        }

        .waiting-queue-item {
            background: rgba(255,255,255,0.1);
            border-radius: clamp(5px, 1vw, 10px);
            padding: clamp(0.5rem, 1.5vw, 1rem);
            margin-bottom: clamp(0.25rem, 0.5vw, 0.5rem);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255,255,255,0.1);
            min-height: clamp(40px, 8vw, 60px);
        }

        .waiting-queue-number {
            font-size: clamp(1rem, 2.5vw, 1.8rem);
            font-weight: 700;
            color: #87CEEB;
            line-height: 1;
        }

        .waiting-queue-type {
            font-size: clamp(0.7rem, 1.5vw, 1rem);
            opacity: 0.8;
            line-height: 1.2;
            margin-top: 0.2rem;
        }

        .queue-position {
            font-size: clamp(0.8rem, 2vw, 1.2rem);
            font-weight: 600;
            background: rgba(255,255,255,0.2);
            padding: clamp(0.2rem, 0.5vw, 0.3rem) clamp(0.5rem, 1vw, 0.8rem);
            border-radius: 15px;
            white-space: nowrap;
        }

        .footer {
            background: rgba(0,0,0,0.3);
            padding: clamp(0.5rem, 1.5vw, 1rem) clamp(1rem, 2vw, 2rem);
            text-align: center;
            border-top: 1px solid rgba(255,255,255,0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            flex-shrink: 0;
            font-size: clamp(0.7rem, 1.5vw, 1rem);
        }

        .status-indicator {
            display: inline-block;
            width: clamp(8px, 1.5vw, 12px);
            height: clamp(8px, 1.5vw, 12px);
            border-radius: 50%;
            margin-right: 0.5rem;
            animation: blink 1s infinite;
        }

        .status-online {
            background-color: #28a745;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }

        .announcement {
            background: rgba(255,193,7,0.2);
            border: 2px solid #FFD700;
            border-radius: clamp(8px, 2vw, 15px);
            padding: clamp(0.5rem, 2vw, 1rem);
            margin: clamp(0.5rem, 1vw, 1rem) 0;
            text-align: center;
            font-size: clamp(0.9rem, 2vw, 1.2rem);
            animation: slideIn 0.5s ease-out;
            line-height: 1.4;
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .calling-animation {
            animation: calling 1s infinite;
        }

        @keyframes calling {
            0%, 100% { background-color: rgba(255,215,0,0.3); }
            50% { background-color: rgba(255,215,0,0.6); }
        }

        .audio-controls {
            display: flex;
            align-items: center;
            gap: clamp(0.5rem, 1vw, 1rem);
            flex-wrap: wrap;
        }

        .audio-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: clamp(0.7rem, 1.5vw, 0.9rem);
        }

        .audio-toggle {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: clamp(0.3rem, 1vw, 0.5rem) clamp(0.5rem, 1.5vw, 1rem);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: clamp(0.7rem, 1.5vw, 0.9rem);
            white-space: nowrap;
        }

        .audio-toggle:hover {
            background: rgba(255,255,255,0.3);
        }

        .audio-toggle.enabled {
            background: rgba(40,167,69,0.3);
            border-color: #28a745;
        }

        .audio-toggle.disabled {
            background: rgba(220,53,69,0.3);
            border-color: #dc3545;
        }

        /* Scrollbar styling for waiting queue */
        .waiting-queues-container::-webkit-scrollbar {
            width: 6px;
        }

        .waiting-queues-container::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
            border-radius: 3px;
        }

        .waiting-queues-container::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }

        .waiting-queues-container::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }

        /* Media Queries for specific breakpoints */
        @media (max-width: 1200px) {
            .content {
                flex-direction: column;
            }
            
            .current-queue-section {
                flex: 1;
                min-height: 60vh;
            }
            
            .waiting-queue-section {
                flex: 1;
                min-height: 30vh;
            }
        }

        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }
            
            .content {
                padding: 1rem;
                gap: 1rem;
            }
            
            .footer {
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
            }
            
            .audio-controls {
                justify-content: center;
            }
            
            .waiting-queue-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .queue-position {
                align-self: flex-end;
            }
        }

        @media (max-width: 480px) {
            .current-queue-section,
            .waiting-queue-section {
                padding: 1rem;
            }
            
            .waiting-queue-item {
                padding: 0.75rem;
            }
            
            .audio-toggle {
                padding: 0.4rem 0.8rem;
            }
        }

        @media (max-height: 600px) {
            .header {
                padding: 0.5rem 1rem;
            }
            
            .content {
                padding: 0.5rem;
            }
            
            .current-queue-title {
                margin-bottom: 1rem;
            }
            
            .current-queue-number {
                margin: 1rem 0;
            }
            
            .footer {
                padding: 0.5rem 1rem;
            }
        }

        /* Landscape orientation for tablets */
        @media (orientation: landscape) and (max-height: 768px) {
            .content {
                flex-direction: row;
            }
            
            .current-queue-section {
                flex: 2;
            }
            
            .waiting-queue-section {
                flex: 1;
            }
        }

        /* High DPI displays */
        @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
            .hospital-name,
            .current-queue-number {
                text-shadow: 3px 3px 6px rgba(0,0,0,0.5);
            }
        }
    </style>
</head>
<body>
    <div class="monitor-container">
        <!-- Header -->
        <div class="header">
            <div class="hospital-name"><?php echo htmlspecialchars($hospitalName); ?></div>
            <div class="service-point-name"><?php echo htmlspecialchars($servicePointName); ?></div>
            <div class="current-time" id="currentTime"></div>
        </div>
        
        <!-- Content -->
        <div class="content">
            <!-- Current Queue -->
            <div class="current-queue-section">
                <div class="current-queue-title">
                    <i class="fas fa-bullhorn me-2"></i>กำลังเรียกคิว
                </div>
                
                <div id="currentQueueDisplay">
                    <div class="no-current-queue">
                        <i class="fas fa-clock me-2"></i>
                        รอการเรียกคิว
                    </div>
                </div>
                
                <div id="announcementArea"></div>
            </div>
            
            <!-- Waiting Queues -->
            <div class="waiting-queue-section">
                <div class="waiting-title">
                    <i class="fas fa-list me-2"></i>คิวรอ
                </div>
                
                <div class="waiting-queues-container" id="waitingQueuesList">
                    <div class="text-center text-muted">
                        <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div>
                <span class="status-indicator status-online"></span>
                ระบบออนไลน์ | อัปเดตล่าสุด: <span id="lastUpdate"></span>
            </div>
            
            <div class="audio-controls">
                <div class="audio-status">
                    <i class="fas fa-volume-up" id="audioIcon"></i>
                    <span id="audioStatusText">กำลังตรวจสอบ...</span>
                </div>
                <button class="audio-toggle" id="audioToggle" onclick="toggleAudio()">
                    <i class="fas fa-volume-mute me-1"></i>เปิดเสียง
                </button>
                <button class="audio-toggle" onclick="testAudio()">
                    <i class="fas fa-play me-1"></i>ทดสอบ
                </button>
            </div>
        </div>
    </div>

    <?php include '../components/notification-system.php'; renderNotificationSystem(); ?>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        let servicePointId = <?php echo json_encode($servicePointId); ?>;
        let lastCalledQueue = null;
        let lastCalledCount = 0; // เพิ่มตัวแปรเก็บจำนวนครั้งที่เรียกล่าสุด
        let audioEnabled = <?php echo $ttsEnabled == '1' ? 'true' : 'false'; ?>;
        let speechSynthesisReady = false;
        let voices = [];
        let queueCallTemplate = <?php echo json_encode($queueCallTemplate); ?>;
        let ttsApiUrl = <?php echo json_encode(getSetting('tts_api_url', '')); ?>;
        
        $(document).ready(function() {
            updateTime();
            initializeAudio();
            loadQueueData();
            
            // Update time every second
            setInterval(updateTime, 1000);
            
            // Refresh queue data every 3 seconds
            setInterval(loadQueueData, 3000);

            // เพิ่มใน $(document).ready()
            setInterval(function() {
                // ตรวจสอบสถานะ Speech Synthesis ทุก 10 วินาที
                if (audioEnabled && speechSynthesis) {
                    const voices = speechSynthesis.getVoices();
                    if (voices.length === 0 && speechSynthesisReady) {
                        console.log('Voices lost, reinitializing...');
                        speechSynthesisReady = false;
                        initializeAudio();
                    }
                }
            }, 10000);
        });
        
        // ฟังก์ชันสำหรับดึงการตั้งค่าปัจจุบัน
        function refreshSettings() {
            return $.get('../api/get_settings.php').then(function(settings) {
                // อัปเดตการตั้งค่าทั้งหมด
                audioEnabled = settings.tts_enabled == '1';
                queueCallTemplate = settings.queue_call_template || 'หมายเลข {queue_number} เชิญที่ {service_point_name}';
                ttsApiUrl = settings.tts_api_url || '';
                
                console.log('Settings refreshed:', {
                    audioEnabled: audioEnabled,
                    ttsApiUrl: ttsApiUrl,
                    queueCallTemplate: queueCallTemplate
                });
                
                return settings;
            }).fail(function() {
                console.error('Failed to refresh settings');
                return null;
            });
        }
        
        function initializeAudio() {
            // ตรวจสอบการรองรับ Speech Synthesis
            if (!window.speechSynthesis) {
                audioEnabled = false;
                updateAudioStatus('ไม่รองรับเสียง', 'disabled');
                console.log('Speech Synthesis not supported');
                return;
            }
            
            // รอให้ voices โหลดเสร็จ
            function loadVoices() {
                voices = speechSynthesis.getVoices();
                if (voices.length > 0) {
                    speechSynthesisReady = true;
                    updateAudioStatus(audioEnabled ? 'เสียงพร้อม' : 'เสียงปิด', audioEnabled ? 'enabled' : 'disabled');
                    console.log('Available voices:', voices.length);
                } else {
                    // ลองอีกครั้งหลัง 100ms
                    setTimeout(loadVoices, 100);
                }
            }
            
            // Event listener สำหรับการโหลด voices
            speechSynthesis.onvoiceschanged = loadVoices;
            
            // เรียกทันทีในกรณีที่ voices พร้อมแล้ว
            loadVoices();
            
            // เตรียม speechSynthesis ด้วยการเล่นเสียงเงียบ
            prepareSpeechSynthesis();
        }
        
        function prepareSpeechSynthesis() {
            try {
                // ตรวจสอบว่า Speech Synthesis พร้อมใช้งาน
                if (!speechSynthesis) {
                    console.error('Speech Synthesis not supported');
                    return;
                }

                // สร้าง utterance เงียบเพื่อเตรียม speechSynthesis
                const utterance = new SpeechSynthesisUtterance(' ');
                utterance.volume = 0.01; // เสียงเบามาก
                utterance.rate = 10; // เร็วมาก
                utterance.pitch = 1;
                
                utterance.onend = function() {
                    console.log('Speech synthesis prepared and ready');
                };
                
                utterance.onerror = function(event) {
                    console.log('Preparation error (expected):', event.error);
                };
                
                // เล่นเสียงเงียบ
                speechSynthesis.speak(utterance);
                
            } catch (error) {
                console.error('Failed to prepare speech synthesis:', error);
            }
        }
        
        function updateAudioStatus(text, status) {
            $('#audioStatusText').text(text);
            
            const toggle = $('#audioToggle');
            const icon = $('#audioIcon');
            
            toggle.removeClass('enabled disabled');
            
            if (status === 'enabled') {
                toggle.addClass('enabled');
                toggle.html('<i class="fas fa-volume-up me-1"></i>ปิดเสียง');
                icon.removeClass('fa-volume-mute fa-volume-off').addClass('fa-volume-up');
            } else if (status === 'disabled') {
                toggle.addClass('disabled');
                toggle.html('<i class="fas fa-volume-mute me-1"></i>เปิดเสียง');
                icon.removeClass('fa-volume-up').addClass('fa-volume-mute');
            } else {
                toggle.html('<i class="fas fa-volume-off me-1"></i>ไม่รองรับ');
                icon.removeClass('fa-volume-up fa-volume-mute').addClass('fa-volume-off');
            }
        }
        
        function toggleAudio() {
            if (!speechSynthesisReady) {
                alert('ระบบเสียงยังไม่พร้อม กรุณารอสักครู่');
                return;
            }
            
            audioEnabled = !audioEnabled;
            updateAudioStatus(audioEnabled ? 'เสียงพร้อม' : 'เสียงปิด', audioEnabled ? 'enabled' : 'disabled');
            
            // บันทึกการตั้งค่าใน localStorage
            localStorage.setItem('audioEnabled', audioEnabled);
            
            if (audioEnabled) {
                // ทดสอบเสียงเมื่อเปิด
                testAudio();
            }
        }
        
        function resetSpeechSynthesis() {
            try {
                // หยุดการพูดทั้งหมด
                speechSynthesis.cancel();
                
                // รอสักครู่แล้วเตรียมใหม่
                setTimeout(() => {
                    prepareSpeechSynthesis();
                    console.log('Speech synthesis reset completed');
                }, 300);
                
            } catch (error) {
                console.error('Failed to reset speech synthesis:', error);
            }
        }
        
        function testAudio() {
            // ดึงการตั้งค่าปัจจุบันก่อนทดสอบ
            refreshSettings().then(function(settings) {
                if (!speechSynthesisReady || !audioEnabled) {
                    if (!speechSynthesisReady) {
                        alert('ระบบเสียงยังไม่พร้อม กรุณารอสักครู่');
                        // ลองเตรียมใหม่
                        initializeAudio();
                    } else {
                        alert('เสียงถูกปิดอยู่ กรุณาเปิดเสียงก่อน');
                    }
                    return;
                }
                
                // Reset ก่อนทดสอบ
                resetSpeechSynthesis();
                
                setTimeout(() => {
                    const testMessage = 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1';
                    speakText(testMessage);
                }, 500);
            });
        }
        
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleString('th-TH', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            $('#currentTime').text(timeString);
        }
        
        function loadQueueData() {
            const url = servicePointId ? 
                `../api/get_monitor_data.php?service_point_id=${servicePointId}` : 
                '../api/get_monitor_data.php';
                
            $.get(url, function(data) {
                displayCurrentQueue(data.current);
                displayWaitingQueues(data.waiting);
                updateLastUpdate();
                
                // Check for newly called queue or repeat call
                if (data.current) {
                    const currentQueueId = data.current.queue_id;
                    const currentCalledCount = parseInt(data.current.called_count) || 1;
                    
                    // ตรวจสอบการเรียกคิวใหม่หรือเรียกซ้ำ
                    const isNewCall = currentQueueId !== lastCalledQueue;
                    const isRepeatCall = currentQueueId === lastCalledQueue && currentCalledCount > lastCalledCount;
                    
                    if (isNewCall || isRepeatCall) {
                        console.log('Queue call detected:', {
                            isNewCall: isNewCall,
                            isRepeatCall: isRepeatCall,
                            queueId: currentQueueId,
                            calledCount: currentCalledCount
                        });
                        
                        // ดึงการตั้งค่าปัจจุบันก่อนเล่นเสียง
                        refreshSettings().then(function(settings) {
                            if (audioEnabled) {
                                // หยุดเสียงที่อาจกำลังเล่นอยู่
                                if (speechSynthesis && speechSynthesis.speaking) {
                                    speechSynthesis.cancel();
                                }
                                
                                // รอสักครู่แล้วเล่นเสียงใหม่
                                setTimeout(() => {
                                    announceQueue(data.current);
                                }, 300);
                            } else {
                                // แสดงการประกาศแบบไม่มีเสียง
                                announceQueue(data.current);
                            }
                        });
                    }
                    
                    // อัปเดตค่าล่าสุด
                    lastCalledQueue = currentQueueId;
                    lastCalledCount = currentCalledCount;
                } else {
                    // ไม่มีคิวปัจจุบัน
                    lastCalledQueue = null;
                    lastCalledCount = 0;
                }
            }).fail(function() {
                console.error('Failed to load queue data');
                showOfflineStatus();
            });
        }
        
        function displayCurrentQueue(queue) {
            const container = $('#currentQueueDisplay');
            
            if (queue) {
                const html = `
                    <div class="current-queue-number calling-animation">${queue.queue_number}</div>
                    <div class="current-queue-info">
                        <div><strong>${queue.type_name}</strong></div>
                        <div>เรียกเมื่อ: ${formatTime(queue.last_called_time)}</div>
                        ${queue.called_count > 1 ? `<div class="text-warning">เรียกครั้งที่ ${queue.called_count}</div>` : ''}
                    </div>
                `;
                container.html(html);
            } else {
                container.html(`
                    <div class="no-current-queue">
                        <i class="fas fa-clock me-2"></i>
                        รอการเรียกคิว
                    </div>
                `);
            }
        }
        
        function displayWaitingQueues(queues) {
            const container = $('#waitingQueuesList');
            
            if (queues.length === 0) {
                container.html(`
                    <div class="text-center" style="opacity: 0.6; margin-top: 2rem;">
                        <i class="fas fa-check-circle fa-2x mb-2"></i><br>
                        ไม่มีคิวรอ
                    </div>
                `);
                return;
            }
            
            let html = '';
            queues.slice(0, 12).forEach(function(queue, index) {
                html += `
                    <div class="waiting-queue-item">
                        <div class="queue-info">
                            <div class="waiting-queue-number">${queue.queue_number}</div>
                            <div class="waiting-queue-type">${queue.type_name}</div>
                        </div>
                        <div class="queue-position">คิวที่ ${index + 1}</div>
                    </div>
                `;
            });
            
            if (queues.length > 12) {
                html += `
                    <div class="text-center mt-2" style="opacity: 0.7; font-size: clamp(0.8rem, 1.5vw, 1rem);">
                        และอีก ${queues.length - 12} คิว
                    </div>
                `;
            }
            
            container.html(html);
        }
        
        function announceQueue(queue) {
            // Visual announcement
            const announcement = `
                <div class="announcement">
                    <i class="fas fa-volume-up me-2"></i>
                    <strong>เรียกคิวหมายเลข ${queue.queue_number}</strong><br>
                    ${queue.service_point_name ? `เชิญที่ ${queue.service_point_name}` : ''}
                    ${queue.called_count > 1 ? `<br><small>เรียกครั้งที่ ${queue.called_count}</small>` : ''}
                </div>
            `;
            
            $('#announcementArea').html(announcement);
            
            // Remove announcement after 5 seconds
            setTimeout(function() {
                $('#announcementArea').fadeOut(500, function() {
                    $(this).empty().show();
                });
            }, 5000);
            
            // Add to announceQueue function after the visual announcement
            if (typeof notificationSystem !== 'undefined') {
                notificationSystem.addNotification({
                    type: 'queue_called',
                    message: `เรียกคิวหมายเลข ${queue.queue_number} ที่ ${queue.service_point_name || 'จุดบริการ'}`,
                    timestamp: new Date().toISOString(),
                    queue_id: queue.queue_id,
                    priority: 'info'
                });
            }
            
            // Audio announcement
            if (audioEnabled && speechSynthesisReady) {
                speakQueue(queue);
            }
        }
        
        function speakQueue(queue) {
    let message = queueCallTemplate
        .replace('{queue_number}', queue.queue_number)
        .replace('{service_point_name}', queue.service_point_name || 'จุดบริการ');
    
    // ไม่เพิ่มข้อความเรียกซ้ำในเสียง (แสดงเฉพาะบนหน้าจอ)
    
    speakText(message);
}
        
        function speakText(text) {
    try {
        // ตรวจสอบว่ามี TTS API URL หรือไม่
        if (ttsApiUrl && ttsApiUrl.trim() !== '') {
            // ใช้ TTS API
            speakWithAPI(text);
        } else {
            // ใช้ Browser Speech Synthesis
            speakWithBrowser(text);
        }
    } catch (error) {
        console.error('Speech error:', error);
        updateAudioStatus('เกิดข้อผิดพลาด', 'disabled');
    }
}

function speakWithAPI(text) {
    $.ajax({
        url: ttsApiUrl,
        method: 'POST',
        data: {
            text: text,
            language: 'th',
            speed: 1.0
        },
        xhrFields: {
            responseType: 'blob'
        },
        beforeSend: function() {
            updateAudioStatus('กำลังโหลดเสียง...', 'enabled');
        },
        success: function(blob, status, xhr) {
            try {
                // สร้าง URL จาก blob
                const audioUrl = URL.createObjectURL(blob);
                
                // สร้าง audio element และเล่น
                const audio = new Audio(audioUrl);
                
                audio.onloadeddata = function() {
                    updateAudioStatus('กำลังพูด...', 'enabled');
                    audio.play().then(() => {
                        console.log('TTS API audio started:', text);
                    }).catch(error => {
                        console.error('Failed to play TTS API audio:', error);
                        updateAudioStatus('เกิดข้อผิดพลาด', 'disabled');
                        // Fallback to browser TTS
                        speakWithBrowser(text);
                    });
                };
                
                audio.onended = function() {
                    console.log('TTS API audio ended');
                    updateAudioStatus('เสียงพร้อม', 'enabled');
                    // ล้าง URL เมื่อเล่นเสร็จ
                    URL.revokeObjectURL(audioUrl);
                };
                
                audio.onerror = function() {
                    console.error('TTS API audio loading error');
                    updateAudioStatus('เกิดข้อผิดพลาด', 'disabled');
                    URL.revokeObjectURL(audioUrl);
                    // Fallback to browser TTS
                    speakWithBrowser(text);
                };
                
            } catch (error) {
                console.error('Error creating audio from blob:', error);
                updateAudioStatus('เกิดข้อผิดพลาด', 'disabled');
                // Fallback to browser TTS
                speakWithBrowser(text);
            }
        },
        error: function(xhr, status, error) {
            console.error('TTS API request failed:', error);
            updateAudioStatus('API ล้มเหลว', 'disabled');
            // Fallback to browser TTS
            speakWithBrowser(text);
        }
    });
}

function speakWithBrowser(text) {
    try {
        // ตรวจสอบสถานะ Speech Synthesis
        if (!speechSynthesis) {
            console.error('Speech Synthesis not available');
            return;
        }

        // หยุดการพูดที่อาจกำลังทำงานอยู่
        if (speechSynthesis.speaking) {
            speechSynthesis.cancel();
        }

        // รอให้ Speech Synthesis พร้อม
        const speakWhenReady = () => {
            // ตรวจสอบว่า voices ยังพร้อมอยู่หรือไม่
            if (speechSynthesis.getVoices().length === 0) {
                console.log('Voices not ready, retrying...');
                setTimeout(speakWhenReady, 100);
                return;
            }

            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'th-TH';
            utterance.rate = 0.8;
            utterance.pitch = 1;
            utterance.volume = 0.9;
            
            // หา Thai voice ถ้ามี
            const currentVoices = speechSynthesis.getVoices();
            const thaiVoice = currentVoices.find(voice => 
                voice.lang.includes('th') || 
                voice.name.toLowerCase().includes('thai')
            );
            
            if (thaiVoice) {
                utterance.voice = thaiVoice;
                console.log('Using Thai voice:', thaiVoice.name);
            } else {
                console.log('No Thai voice found, using default');
            }
            
            // Event handlers
            utterance.onstart = function() {
                console.log('Browser speech started:', text);
                updateAudioStatus('กำลังพูด...', 'enabled');
            };
            
            utterance.onend = function() {
                console.log('Browser speech ended successfully');
                updateAudioStatus('เสียงพร้อม', 'enabled');
                
                // Reset speech synthesis เพื่อเตรียมสำหรับครั้งต่อไป
                setTimeout(() => {
                    if (!speechSynthesis.speaking) {
                        prepareSpeechSynthesis();
                    }
                }, 100);
            };
            
            utterance.onerror = function(event) {
                console.error('Browser speech error:', event.error);
                updateAudioStatus('เกิดข้อผิดพลาด', 'disabled');
                
                // ลองเตรียม Speech Synthesis ใหม่
                setTimeout(() => {
                    prepareSpeechSynthesis();
                }, 500);
            };
            
            utterance.onpause = function() {
                console.log('Browser speech paused');
            };
            
            utterance.onresume = function() {
                console.log('Browser speech resumed');
            };
            
            // เล่นเสียง
            try {
                speechSynthesis.speak(utterance);
                console.log('Browser speech synthesis started for:', text);
            } catch (error) {
                console.error('Failed to speak with browser:', error);
                updateAudioStatus('เกิดข้อผิดพลาด', 'disabled');
            }
        };

        // เริ่มการพูดหลังจาก delay เล็กน้อย
        setTimeout(speakWhenReady, 200);
        
    } catch (error) {
        console.error('Browser speech synthesis error:', error);
        updateAudioStatus('เกิดข้อผิดพลาด', 'disabled');
        
        // ลองเตรียม Speech Synthesis ใหม่
        setTimeout(() => {
            initializeAudio();
        }, 1000);
    }
}
        
        function formatTime(timeString) {
            if (!timeString) return '-';
            const date = new Date(timeString);
            return date.toLocaleTimeString('th-TH', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        function updateLastUpdate() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('th-TH');
            $('#lastUpdate').text(timeString);
        }
        
        function showOfflineStatus() {
            $('.status-indicator').removeClass('status-online').css('background-color', '#dc3545');
        }
        
        // โหลดการตั้งค่าเสียงจาก localStorage
        $(document).ready(function() {
            const savedAudioEnabled = localStorage.getItem('audioEnabled');
            if (savedAudioEnabled !== null) {
                audioEnabled = savedAudioEnabled === 'true';
            }
        });
        
        // Handle page visibility change
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                // Page became visible, refresh data
                loadQueueData();
            }
        });
        
        // Prevent context menu and selection for kiosk mode
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });
        
        document.addEventListener('selectstart', function(e) {
            e.preventDefault();
        });
        
        // Handle keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // F11 for fullscreen
            if (e.key === 'F11') {
                if (document.fullscreenElement) {
                    document.exitFullscreen();
                } else {
                    document.documentElement.requestFullscreen();
                }
            }
            
            // Escape to exit fullscreen
            if (e.key === 'Escape' && document.fullscreenElement) {
                document.exitFullscreen();
            }
            
            // Space bar to toggle audio
            if (e.key === ' ') {
                e.preventDefault();
                toggleAudio();
            }
            
            // T key to test audio
            if (e.key === 't' || e.key === 'T') {
                e.preventDefault();
                testAudio();
            }
        });
        
        // Click anywhere to enable audio (required by browsers)
        document.addEventListener('click', function() {
            if (!speechSynthesisReady) {
                initializeAudio();
            }
        }, { once: true });
    </script>
</body>
</html>
