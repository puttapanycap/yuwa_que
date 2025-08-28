<?php
/**
 * This file displays the queue monitoring screen.
 *
 * @category Queue
 * @package  Yuwa_Queue
 * @author   Your Name <you@example.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/puttapanycap/yuwa_que
 */

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
// These settings will be fetched dynamically via API for real-time updates
// $ttsEnabled = getSetting('tts_enabled', '1');
// $queueCallTemplate = getSetting('queue_call_template', 'หมายเลข {queue_number} เชิญที่ {service_point_name}');
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
        :root {
            --main-bg-color: #ffffff;
            --primary-green: #4CAF50;
            --dark-text-color: #333333;
            --light-text-color: #ffffff;
            --border-color: #e0e0e0;
            --shadow-color: rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background-color: var(--main-bg-color);
            margin: 0;
            padding: 0;
            overflow: hidden;
            color: var(--dark-text-color);
        }

        .monitor-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background-color: var(--primary-green);
            color: var(--light-text-color);
            padding: clamp(1rem, 2vw, 1.5rem);
            text-align: center;
            flex-shrink: 0;
            box-shadow: 0 2px 4px var(--shadow-color);
        }

        .hospital-name {
            font-size: clamp(1.5rem, 4vw, 2.2rem);
            font-weight: 700;
            margin-bottom: 0.25rem;
            line-height: 1.2;
        }

        .service-point-name {
            font-size: clamp(1rem, 3vw, 1.6rem);
            font-weight: 500;
            opacity: 0.95;
            line-height: 1.2;
        }

        .current-time {
            font-size: clamp(0.8rem, 2vw, 1.1rem);
            opacity: 0.9;
            margin-top: 0.5rem;
        }

        .content {
            flex: 1;
            display: flex;
            padding: clamp(1rem, 2vw, 2rem);
            gap: clamp(1rem, 2vw, 2rem);
            min-height: 0;
            overflow: hidden;
        }

        .current-queue-section,
        .waiting-queue-section {
            background-color: var(--main-bg-color);
            border-radius: clamp(10px, 1.5vw, 15px);
            padding: clamp(1rem, 2vw, 2rem);
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px var(--shadow-color);
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .current-queue-section {
            flex: 2;
            text-align: center;
            justify-content: center;
        }

        .waiting-queue-section {
            flex: 1;
            overflow: hidden;
        }

        .current-queue-title {
            font-size: clamp(1.2rem, 3vw, 1.8rem);
            font-weight: 600;
            margin-bottom: clamp(1rem, 2vw, 1.5rem);
            color: var(--primary-green);
        }

        .current-queue-number {
            font-size: clamp(3.5rem, 12vw, 9rem);
            font-weight: 800;
            color: var(--primary-green);
            margin: clamp(1rem, 2vw, 2rem) 0;
            line-height: 1;
            animation: pulse 1.8s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.03); }
            100% { transform: scale(1); }
        }

        .current-queue-info {
            font-size: clamp(1rem, 2.5vw, 1.4rem);
            margin-bottom: 0.8rem;
            opacity: 0.8;
            line-height: 1.4;
        }

        .no-current-queue {
            font-size: clamp(1.5rem, 4vw, 2.5rem);
            color: #999;
            margin: clamp(2rem, 5vw, 4rem) 0;
            line-height: 1.2;
        }

        .waiting-title {
            font-size: clamp(1.1rem, 2.5vw, 1.4rem);
            font-weight: 600;
            margin-bottom: clamp(0.8rem, 1.5vw, 1.2rem);
            text-align: center;
            color: var(--dark-text-color);
            flex-shrink: 0;
        }

        .waiting-queues-container {
            flex: 1;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        .waiting-queue-item {
            background-color: #f9f9f9;
            border-radius: clamp(8px, 1vw, 12px);
            padding: clamp(0.8rem, 1.2vw, 1rem);
            margin-bottom: clamp(0.5rem, 0.8vw, 0.8rem);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #f0f0f0;
            min-height: clamp(45px, 7vw, 65px);
        }

        .waiting-queue-number {
            font-size: clamp(1.1rem, 2.5vw, 1.6rem);
            font-weight: 700;
            color: var(--dark-text-color);
            line-height: 1;
        }

        .waiting-queue-type {
            font-size: clamp(0.7rem, 1.5vw, 0.9rem);
            opacity: 0.7;
            line-height: 1.2;
            margin-top: 0.2rem;
        }

        .queue-position {
            font-size: clamp(0.8rem, 2vw, 1.1rem);
            font-weight: 600;
            background-color: var(--primary-green);
            color: var(--light-text-color);
            padding: clamp(0.3rem, 0.6vw, 0.4rem) clamp(0.6rem, 1.2vw, 1rem);
            border-radius: 20px;
            white-space: nowrap;
        }

        .footer {
            background-color: #f5f5f5;
            padding: clamp(0.8rem, 1.5vw, 1rem) clamp(1rem, 2vw, 2rem);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            flex-shrink: 0;
            font-size: clamp(0.8rem, 1.5vw, 1rem);
        }

        .status-indicator {
            display: inline-block;
            width: clamp(10px, 1.5vw, 14px);
            height: clamp(10px, 1.5vw, 14px);
            border-radius: 50%;
            margin-right: 0.5rem;
            animation: blink 1.2s infinite;
        }

        .status-online {
            background-color: var(--primary-green);
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.4; }
        }

        .announcement {
            background-color: #e8f5e9;
            border: 2px solid var(--primary-green);
            border-radius: clamp(8px, 1.5vw, 12px);
            padding: clamp(0.8rem, 1.5vw, 1.2rem);
            margin: clamp(0.8rem, 1vw, 1rem) 0;
            text-align: center;
            font-size: clamp(0.9rem, 2vw, 1.2rem);
            animation: slideIn 0.5s ease-out;
            line-height: 1.4;
            color: var(--dark-text-color);
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .calling-animation {
            animation: calling 1.5s infinite;
        }

        @keyframes calling {
            0%, 100% { color: var(--primary-green); }
            50% { color: #388E3C; }
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
            font-size: clamp(0.8rem, 1.5vw, 0.9rem);
        }

        .audio-toggle {
            background-color: #e0e0e0;
            border: 1px solid #ccc;
            color: var(--dark-text-color);
            padding: clamp(0.4rem, 1vw, 0.6rem) clamp(0.8rem, 1.5vw, 1.2rem);
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: clamp(0.8rem, 1.5vw, 0.9rem);
            white-space: nowrap;
        }

        .audio-toggle:hover {
            background-color: #d5d5d5;
        }

        .audio-toggle.enabled {
            background-color: #e8f5e9;
            border-color: var(--primary-green);
            color: var(--primary-green);
        }

        .audio-toggle.disabled {
            background-color: #fbe9e7;
            border-color: #ff5252;
            color: #ff5252;
        }

        /* Scrollbar styling */
        .waiting-queues-container::-webkit-scrollbar {
            width: 8px;
        }

        .waiting-queues-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .waiting-queues-container::-webkit-scrollbar-thumb {
            background: #ccc;
            border-radius: 4px;
        }

        .waiting-queues-container::-webkit-scrollbar-thumb:hover {
            background: #aaa;
        }

        /* Media Queries */
        @media (max-width: 1200px) {
            .content { flex-direction: column; }
            .current-queue-section { min-height: 55vh; }
            .waiting-queue-section { min-height: 35vh; }
        }

        @media (max-width: 768px) {
            .header, .content, .footer { padding: 1rem; gap: 1rem; }
            .footer { flex-direction: column; text-align: center; gap: 0.8rem; }
            .audio-controls { justify-content: center; }
            .waiting-queue-item { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
            .queue-position { align-self: flex-end; }
        }

        @media (max-width: 480px) {
            .current-queue-section, .waiting-queue-section, .waiting-queue-item { padding: 0.8rem; }
            .audio-toggle { padding: 0.5rem 1rem; }
        }

        @media (max-height: 600px) {
            .header, .footer { padding: 0.5rem 1rem; }
            .content { padding: 0.5rem; }
            .current-queue-title, .current-queue-number { margin-bottom: 0.5rem; margin-top: 0.5rem; }
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
                <button class="audio-toggle" onclick="toggleDebug()" style="display: none;" id="debugToggle">
                    <i class="fas fa-bug me-1"></i>Debug
                </button>
            </div>
        </div>
    </div>

    <?php
    require '../components/notification-system.php';
    renderMonitorNotificationSystem($servicePointId);
    ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        let servicePointId = <?php echo json_encode($servicePointId); ?>;
        let lastCalledQueue = null;
        let lastCalledCount = 0; // เพิ่มตัวแปรเก็บจำนวนครั้งที่เรียกล่าสุด
        let audioEnabled = false; // Default to false, will be set by settings
        let speechSynthesisReady = false;
        let voices = [];
        let currentTTSProvider = 'browser'; // 'browser' or 'api'
        let audioContext = null;
        let speechSynthesisSupported = false;
        let isSpeaking = false; // Flag to prevent overlapping speech

        // Debug mode toggle
        let debugMode = false; // Set to true for development, false for production

        function debugLog(message, data = null) {
            if (debugMode) {
                console.log(`[Audio Debug] ${message}`, data || '');
            }
        }
        
        $(document).ready(function() {
            updateTime();
            initializeAudio();
            loadQueueData();
            
            // Update time every second
            setInterval(updateTime, 1000);
            
            // Refresh queue data every 3 seconds
            setInterval(loadQueueData, 3000);

            // Check Speech Synthesis status periodically
            setInterval(function() {
                if (audioEnabled && speechSynthesisSupported && !speechSynthesisReady) {
                    debugLog('Voices lost or not ready, reinitializing...');
                    initializeAudio();
                }
            }, 10000);

            // Add user interaction handling for unlocking audio
            let audioUnlocked = false;
            
            function unlockAudio() {
                if (!audioUnlocked) {
                    debugLog('Unlocking audio on user interaction');
                    
                    // Unlock AudioContext
                    unlockAudioContext();
                    
                    // Prepare Speech Synthesis if not ready
                    if (speechSynthesisSupported && !speechSynthesisReady) {
                        initializeAudio();
                    }
                    
                    audioUnlocked = true;
                    debugLog('Audio unlocked successfully');
                }
            }
            
            // Add event listeners for unlock audio
            document.addEventListener('click', unlockAudio, { once: true });
            document.addEventListener('touchstart', unlockAudio, { once: true });
            document.addEventListener('keydown', unlockAudio, { once: true });
            
            // Load audio settings from localStorage
            const savedAudioEnabled = localStorage.getItem('audioEnabled');
            if (savedAudioEnabled !== null) {
                audioEnabled = savedAudioEnabled === 'true';
            }
            updateAudioStatus(audioEnabled ? 'เสียงพร้อม' : 'เสียงปิด', audioEnabled ? 'enabled' : 'disabled');
        });
        
        // Initialize Audio System
        function initializeAudio() {
            debugLog('Initializing audio system...');
            
            // Check for Speech Synthesis support
            if (!window.speechSynthesis) {
                speechSynthesisSupported = false;
                updateAudioStatus('ไม่รองรับเสียง', 'disabled');
                debugLog('Speech Synthesis not supported');
            } else {
                speechSynthesisSupported = true;
                debugLog('Speech Synthesis supported');
                
                // Wait for voices to load
                function loadVoices() {
                    voices = speechSynthesis.getVoices();
                    debugLog('Available voices:', voices.length);
                    
                    if (voices.length > 0) {
                        speechSynthesisReady = true;
                        debugLog('Voices loaded successfully', voices.map(v => v.name));
                        // Auto-test audio after voices loaded if enabled
                        if (audioEnabled) {
                            debugLog('Auto-testing audio after voices loaded');
                            testAudioQuiet();
                        }
                    } else {
                        debugLog('No voices available, retrying...');
                        setTimeout(loadVoices, 500);
                    }
                }
                
                speechSynthesis.onvoiceschanged = loadVoices;
                loadVoices(); // Call immediately in case voices are already ready
            }
            
            // Create AudioContext for playing fetched audio
            try {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
                debugLog('AudioContext created', audioContext.state);
            } catch (e) {
                debugLog('AudioContext creation failed', e);
            }
        }

        // Function to test audio quietly (for initialization)
        function testAudioQuiet() {
            debugLog('Testing audio quietly...');
            try {
                const utterance = new SpeechSynthesisUtterance(' '); // Use a silent utterance
                utterance.volume = 0.01;
                utterance.rate = 10;
                utterance.pitch = 1;
                
                utterance.onstart = function() { debugLog('Quiet test audio started'); };
                utterance.onend = function() { debugLog('Quiet test audio ended - system ready'); };
                utterance.onerror = function(event) { debugLog('Quiet test audio error:', event.error); };
                
                speechSynthesis.speak(utterance);
            } catch (error) {
                debugLog('Quiet test failed:', error);
            }
        }
        
        // Function to unlock audio context (required by browsers for autoplay)
        function unlockAudioContext() {
            if (audioContext && audioContext.state === 'suspended') {
                debugLog('Unlocking audio context...');
                audioContext.resume().then(() => {
                    debugLog('Audio context unlocked:', audioContext.state);
                }).catch(error => {
                    debugLog('Failed to unlock audio context:', error);
                });
            }
        }

        // Main function to speak text (handles both browser TTS and API TTS)
        function speakText(text, ttsUrl = null, ttsParams = null) {
            if (isSpeaking) {
                debugLog('Already speaking, queuing new speech.');
                // Optionally queue speech here, or just ignore
                return;
            }
            isSpeaking = true;
            debugLog('Starting speakText:', { text, ttsUrl, ttsParams });
            
            unlockAudioContext(); // Ensure audio context is unlocked

            const onSpeechEnd = () => {
                isSpeaking = false;
                debugLog('Speech ended.');
                updateAudioStatus('เสียงพร้อม', 'enabled');
            };

            const onSpeechError = (error) => {
                isSpeaking = false;
                debugLog('Speech error:', error);
                updateAudioStatus('เกิดข้อผิดพลาด', 'disabled');
            };

            if (ttsUrl && currentTTSProvider !== 'browser') {
                // Use API for TTS
                debugLog(`Using API provider: ${currentTTSProvider}`, ttsUrl);
                $.ajax({
                    url: ttsUrl,
                    type: 'POST',
                    data: ttsParams,
                    xhrFields: {
                        responseType: 'blob' // Expecting audio blob
                    },
                    success: function(blob) {
                        debugLog('Audio blob received, playing...');
                        const audioUrl = URL.createObjectURL(blob);
                        const audio = new Audio(audioUrl);
                        audio.volume = 1.0; // Can be controlled by a setting
                        audio.onended = onSpeechEnd;
                        audio.onerror = (e) => onSpeechError(e.message || 'Audio playback error');
                        audio.play().catch(e => onSpeechError('Audio play failed: ' + e.message));
                        updateAudioStatus('กำลังพูด...', 'enabled');
                    },
                    error: function(xhr, status, error) {
                        onSpeechError('API TTS failed: ' + error);
                        // Fallback to browser TTS if API fails
                        debugLog('API TTS failed, falling back to browser TTS.');
                        speakWithBrowser(text, onSpeechEnd, onSpeechError);
                    }
                });
            } else {
                // Fallback to browser TTS
                debugLog(`Using browser TTS (Provider: ${currentTTSProvider}).`);
                speakWithBrowser(text, onSpeechEnd, onSpeechError);
            }
        }

        // Function to speak using browser's SpeechSynthesis
        function speakWithBrowser(text, onEndCallback, onErrorCallback) {
            debugLog('Speaking with browser TTS:', text);
            
            if (!speechSynthesisSupported || !speechSynthesisReady) {
                onErrorCallback('Speech Synthesis not ready or supported.');
                return;
            }

            if (speechSynthesis.speaking) {
                debugLog('Cancelling previous browser speech.');
                speechSynthesis.cancel();
            }

            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'th-TH';
            utterance.rate = 0.8; // Default rate, can be from settings
            utterance.pitch = 1; // Default pitch, can be from settings
            utterance.volume = 0.9; // Default volume, can be from settings
            
            const thaiVoice = voices.find(voice => 
                voice.lang.includes('th') || 
                voice.name.toLowerCase().includes('thai')
            );
            
            if (thaiVoice) {
                utterance.voice = thaiVoice;
                debugLog('Using Thai voice:', thaiVoice.name);
            } else {
                debugLog('No Thai voice found, using default.');
            }
            
            utterance.onstart = function() {
                debugLog('Browser speech started successfully.');
                updateAudioStatus('กำลังพูด...', 'enabled');
            };
            
            utterance.onend = function() {
                debugLog('Browser speech ended successfully.');
                if (onEndCallback) onEndCallback();
            };
            
            utterance.onerror = function(event) {
                debugLog('Browser speech error:', event.error);
                if (onErrorCallback) onErrorCallback(event.error);
            };
            
            try {
                speechSynthesis.speak(utterance);
            } catch (error) {
                debugLog('Failed to speak with browser TTS:', error);
                if (onErrorCallback) onErrorCallback(error.message);
            }
        }

        // Function to play a short notification sound
        function playNotificationSound() {
            debugLog('Playing notification sound.');
            unlockAudioContext();
            const audio = new Audio('../assets/audio/notification.mp3'); // Ensure this path is correct
            audio.volume = 0.5; // Can be controlled by a setting
            audio.play().catch(e => debugLog('Notification sound play failed:', e.message));
        }

        // Function to orchestrate audio sequence (notification + main announcement + repeats)
        function playAudioSequence(message, ttsEnabled, ttsUrl, ttsParams, repeatCount, notificationBefore) {
            if (!audioEnabled) {
                debugLog('Audio is disabled, skipping audio sequence.');
                return;
            }

            let currentRepeat = 0;

            const playNext = () => {
                if (currentRepeat < repeatCount) {
                    debugLog(`Playing audio repeat ${currentRepeat + 1}/${repeatCount}`);
                    speakText(message, ttsUrl, ttsParams);
                    currentRepeat++;
                    // Wait for speech to end before next repeat (simple delay for now)
                    setTimeout(playNext, (message.length * 80) + 1000); // Estimate speech duration + 1 sec pause
                } else {
                    debugLog('Audio sequence completed.');
                }
            };

            if (notificationBefore) {
                debugLog('Playing notification sound before announcement.');
                playNotificationSound();
                setTimeout(playNext, 1000); // Wait for notification sound to finish
            } else {
                playNext();
            }
        }
        
        // Update audio status display and toggle button
        function updateAudioStatus(text, status) {
            debugLog('Audio status updated:', { text, status });
            
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
            
            debugLog(`Audio Status: ${text} (${status})`);
        }
        
        // Toggle audio on/off
        function toggleAudio() {
            if (!speechSynthesisSupported && currentTTSProvider === 'browser') {
                alert('เบราว์เซอร์ของคุณไม่รองรับระบบเสียง');
                return;
            }
            
            audioEnabled = !audioEnabled;
            updateAudioStatus(audioEnabled ? 'เสียงพร้อม' : 'เสียงปิด', audioEnabled ? 'enabled' : 'disabled');
            
            localStorage.setItem('audioEnabled', audioEnabled); // Save setting
            
            if (audioEnabled) {
                testAudio(); // Test audio when enabled
            } else {
                if (speechSynthesis && speechSynthesis.speaking) {
                    speechSynthesis.cancel(); // Stop any ongoing speech
                }
                isSpeaking = false;
            }
        }
        
        // Manual audio test
        function testAudio() {
            debugLog('Manual audio test triggered');
            unlockAudioContext();

            if (!audioEnabled) {
                alert('เสียงถูกปิดอยู่ กรุณาเปิดเสียงก่อน');
                debugLog('Audio disabled by user, cannot test.');
                return;
            }

            const testMessage = 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1';

            // If provider is browser, test locally without server roundtrip
            if (currentTTSProvider === 'browser') {
                debugLog('Testing with browser provider directly.');
                speakWithBrowser(testMessage, 
                    () => { /* success callback */ }, 
                    (err) => { 
                        alert('การทดสอบเสียงล้มเหลว: ' + err); 
                        debugLog('Browser TTS test failed:', err);
                    }
                );
            } else {
                // For API providers, call the backend
                debugLog(`Testing with API provider: ${currentTTSProvider}`);
                $.post('../api/play_queue_audio.php', { custom_message: testMessage, service_point_id: servicePointId || 1 })
                    .done(function(response) {
                        if (response.success) {
                            debugLog('Test audio API response:', response);
                            playAudioSequence(
                                response.message,
                                response.tts_enabled,
                                response.tts_url,
                                response.tts_params,
                                response.repeat_count,
                                response.notification_before
                            );
                        } else {
                            alert('เกิดข้อผิดพลาดในการทดสอบเสียง: ' + response.message);
                            debugLog('Test audio API error response:', response.message);
                        }
                    })
                    .fail(function(xhr, status, error) {
                        alert('ไม่สามารถเชื่อมต่อกับบริการเสียงได้: ' + error);
                        debugLog('Test audio AJAX failed:', error);
                    });
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
                e.preventDefault(); // Prevent default browser fullscreen
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
        
        function toggleDebug() {
            debugMode = !debugMode;
            const btn = document.getElementById('debugToggle');
            if (debugMode) {
                btn.style.backgroundColor = 'rgba(40,167,69,0.3)';
                btn.innerHTML = '<i class="fas fa-bug me-1"></i>Debug ON';
                console.log('🔧 Debug mode enabled');
                
                // Display debug info
                console.log('Audio Debug Info:', {
                    audioEnabled,
                    speechSynthesisReady,
                    speechSynthesisSupported,
                    voicesCount: voices.length,
                    audioContextState: audioContext?.state,
                    browserSupport: {
                        speechSynthesis: !!window.speechSynthesis,
                        audioContext: !!(window.AudioContext || window.webkitAudioContext)
                    }
                });
            } else {
                btn.style.backgroundColor = 'rgba(220,53,69,0.3)';
                btn.innerHTML = '<i class="fas fa-bug me-1"></i>Debug OFF';
                console.log('🔧 Debug mode disabled');
            }
        }

        // Show debug button on Ctrl+Shift+D
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                document.getElementById('debugToggle').style.display = 'inline-block';
                console.log('🔧 Debug button enabled');
            }
        });

        function displayCurrentQueue(queue) {
            const currentQueueDisplay = $('#currentQueueDisplay');
            if (queue) {
                currentQueueDisplay.html(`
                    <div class="current-queue-number calling-animation">${htmlspecialchars(queue.queue_number)}</div>
                    <div class="current-queue-info">
                        <i class="fas fa-user me-2"></i>${htmlspecialchars(queue.patient_name || 'ไม่ระบุชื่อ')}
                    </div>
                    <div class="current-queue-info">
                        <i class="fas fa-clinic-medical me-2"></i>${htmlspecialchars(queue.service_point_name || 'จุดบริการ')}
                    </div>
                    <div class="current-queue-info">
                        <i class="fas fa-tag me-2"></i>${htmlspecialchars(queue.type_name || 'ไม่ระบุประเภท')}
                    </div>
                `);
            } else {
                currentQueueDisplay.html(`
                    <div class="no-current-queue">
                        <i class="fas fa-clock me-2"></i>
                        รอการเรียกคิว
                    </div>
                `);
            }
        }

        function displayWaitingQueues(queues) {
            const waitingQueuesList = $('#waitingQueuesList');
            waitingQueuesList.empty();
            if (queues && queues.length > 0) {
                queues.forEach((queue, index) => {
                    waitingQueuesList.append(`
                        <div class="waiting-queue-item">
                            <div>
                                <div class="waiting-queue-number">${htmlspecialchars(queue.queue_number)}</div>
                                <div class="waiting-queue-type">${htmlspecialchars(queue.type_name || 'ไม่ระบุประเภท')}</div>
                            </div>
                            <div class="queue-position">คิวที่ ${index + 1}</div>
                        </div>
                    `);
                });
            } else {
                waitingQueuesList.html(`
                    <div class="text-center text-muted mt-4">
                        <i class="fas fa-box-open me-2"></i>
                        ไม่มีคิวรอ
                    </div>
                `);
            }
        }

        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            $('#currentTime').text(timeString);
        }

        function htmlspecialchars(str) {
            if (typeof str !== 'string') return str;
            return str.replace(/&/g, '&amp;')
                      .replace(/</g, '&lt;')
                      .replace(/>/g, '&gt;')
                      .replace(/"/g, '&quot;')
                      .replace(/'/g, '&#039;');
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
                    
                    // Check if it's a new call or a repeat call
                    const isNewCall = currentQueueId !== lastCalledQueue;
                    const isRepeatCall = currentQueueId === lastCalledQueue && currentCalledCount > lastCalledCount;
                    
                    if (isNewCall || isRepeatCall) {
                        debugLog('Queue call detected:', {
                            isNewCall: isNewCall,
                            isRepeatCall: isRepeatCall,
                            queueId: currentQueueId,
                            calledCount: currentCalledCount
                        });
                        
                        // Create notification for queue call
                        if (typeof monitorNotificationSystem !== 'undefined') {
                            const notificationData = {
                                notification_id: Date.now(), // Use timestamp as temporary ID
                                type: 'queue_called',
                                title: 'เรียกคิวแล้ว',
                                message: `หมายเลข ${data.current.queue_number} เชิญที่ ${data.current.service_point_name || 'จุดบริการ'}`,
                                priority: 'high',
                                color: '#28a745',
                                bg_color: 'rgba(40, 167, 69, 0.1)',
                                icon: 'fas fa-bullhorn',
                                display_duration: 8000,
                                formatted_message: `หมายเลข <strong>${htmlspecialchars(data.current.queue_number)}</strong> เชิญที่ <strong>${htmlspecialchars(data.current.service_point_name || 'จุดบริการ')}</strong>`,
                                service_point_name: data.current.service_point_name
                            };
                            
                            monitorNotificationSystem.showNotification(notificationData);
                        }
                        
                        // Fetch audio parameters from backend API
                        $.post('../api/play_queue_audio.php', { queue_id: currentQueueId, service_point_id: servicePointId })
                            .done(function(response) {
                                if (response.success) {
                                    debugLog('Audio API response for queue:', response);
                                    playAudioSequence(
                                        response.message,
                                        response.tts_enabled,
                                        response.tts_url,
                                        response.tts_params,
                                        response.repeat_count,
                                        response.notification_before
                                    );
                                } else {
                                    console.error('Failed to get audio parameters from API:', response.message);
                                }
                            })
                            .fail(function(xhr, status, error) {
                                console.error('AJAX call to play_queue_audio.php failed:', error);
                            });
                    }
                    
                    // Update last values
                    lastCalledQueue = currentQueueId;
                    lastCalledCount = currentCalledCount;
                } else {
                    // No current queue
                    lastCalledQueue = null;
                    lastCalledCount = 0;
                }
            }).fail(function() {
                console.error('Failed to load queue data');
                showOfflineStatus();
            });
        }
    </script>
</body>
</html>
