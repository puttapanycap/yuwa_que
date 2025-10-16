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

require_once dirname(__DIR__, 2) . '/config/config.php';

// Determine layout type and selected service points
$layout = strtolower($_GET['layout'] ?? 'single');
$isDualLayout = $layout === 'dual';
$servicePointId = $_GET['service_point'] ?? null;
$leftServicePointId = $_GET['left'] ?? $_GET['left_service_point'] ?? null;
$rightServicePointId = $_GET['right'] ?? $_GET['right_service_point'] ?? null;
$servicePointName = 'ทุกจุดบริการ';
$voiceTemplateId = null;
$servicePointConfigs = [
    'left' => [
        'id' => $leftServicePointId ? (int)$leftServicePointId : null,
        'name' => '',
        'voice_template_id' => null,
    ],
    'right' => [
        'id' => $rightServicePointId ? (int)$rightServicePointId : null,
        'name' => '',
        'voice_template_id' => null,
    ],
];

try {
    $db = getDB();

    if ($isDualLayout) {
        foreach ($servicePointConfigs as $position => &$config) {
            if (!$config['id']) {
                continue;
            }

            $stmt = $db->prepare("SELECT TRIM(CONCAT(COALESCE(point_label,''),' ', point_name)) AS service_point_name, voice_template_id FROM service_points WHERE service_point_id = ? AND is_active = 1");
            $stmt->execute([$config['id']]);
            $servicePoint = $stmt->fetch();
            if ($servicePoint) {
                $config['name'] = $servicePoint['service_point_name'];
                $config['voice_template_id'] = $servicePoint['voice_template_id'] ?? null;
            }
        }
        unset($config);

        $leftDisplayName = $servicePointConfigs['left']['name'] ?: 'ยังไม่เลือก';
        $rightDisplayName = $servicePointConfigs['right']['name'] ?: 'ยังไม่เลือก';
        $servicePointName = "ซ้าย: {$leftDisplayName} | ขวา: {$rightDisplayName}";
    } elseif ($servicePointId) {
        $stmt = $db->prepare("SELECT TRIM(CONCAT(COALESCE(point_label,''),' ', point_name)) AS service_point_name, voice_template_id FROM service_points WHERE service_point_id = ? AND is_active = 1");
        $stmt->execute([$servicePointId]);
        $servicePoint = $stmt->fetch();
        if ($servicePoint) {
            $servicePointName = $servicePoint['service_point_name'];
            $voiceTemplateId = $servicePoint['voice_template_id'] ?? null;
        }
    }
} catch (Exception $e) {
    if ($isDualLayout) {
        $servicePointName = 'หน้าจอ 2 จุดบริการ';
    }
}

if ($isDualLayout && !isset($leftDisplayName, $rightDisplayName)) {
    $leftDisplayName = $servicePointConfigs['left']['name'] ?: 'ยังไม่เลือก';
    $rightDisplayName = $servicePointConfigs['right']['name'] ?: 'ยังไม่เลือก';
}

$hospitalName = getSetting('hospital_name', 'โรงพยาบาลยุวประสาทไวทโยปถัมภ์');
// These settings will be fetched dynamically via API for real-time updates
// $queueCallTemplate = getSetting('queue_call_template', 'หมายเลข {queue_number} เชิญที่ {service_point_name}');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="../manifest_display.json">
    <title>หน้าจอแสดงคิว - <?php echo htmlspecialchars($servicePointName); ?></title>

    <link href="../assets/plugins/bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
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
            padding: 2rem 2rem 6rem 2rem;
            gap: clamp(1rem, 2vw, 2rem);
            min-height: 0;
            overflow: hidden;
        }

        .content.dual-mode {
            display: block;
        }

        .dual-layout {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: clamp(1rem, 2vw, 2rem);
        }

        .dual-column {
            display: flex;
            flex-direction: column;
            gap: clamp(1rem, 2vw, 1.5rem);
        }

        .dual-column-title {
            font-size: clamp(1.1rem, 2.5vw, 1.6rem);
            font-weight: 600;
            text-align: center;
            color: var(--primary-green);
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
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
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

        .current-queue-section.mh-475px {
            max-height: 475px;
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
        <div class="content <?php echo $isDualLayout ? 'dual-mode' : ''; ?>">
            <?php if ($isDualLayout): ?>
                <div class="dual-layout">
                    <div class="dual-column">
                        <div class="dual-column-title">
                            <i class="fas fa-arrow-left me-2"></i>ฝั่งซ้าย: <?php echo htmlspecialchars($leftDisplayName); ?>
                        </div>
                        <div class="current-queue-section mh-475px">
                            <div class="current-queue-title">
                                <i class="fas fa-bullhorn me-2"></i>กำลังเรียกคิว
                            </div>

                            <div id="currentQueueDisplayLeft">
                                <div class="no-current-queue">
                                    <i class="fas fa-clock me-2"></i>
                                    รอการเรียกคิว
                                </div>
                            </div>
                        </div>

                        <div class="waiting-queue-section mh-475px">
                            <div class="waiting-title">
                                <i class="fas fa-list me-2"></i>คิวรอ
                            </div>

                            <div class="waiting-queues-container" id="waitingQueuesListLeft">
                                <div class="text-center text-muted">
                                    <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="dual-column">
                        <div class="dual-column-title">
                            <i class="fas fa-arrow-right me-2"></i>ฝั่งขวา: <?php echo htmlspecialchars($rightDisplayName); ?>
                        </div>
                        <div class="current-queue-section mh-475px">
                            <div class="current-queue-title">
                                <i class="fas fa-bullhorn me-2"></i>กำลังเรียกคิว
                            </div>

                            <div id="currentQueueDisplayRight">
                                <div class="no-current-queue">
                                    <i class="fas fa-clock me-2"></i>
                                    รอการเรียกคิว
                                </div>
                            </div>
                        </div>

                        <div class="waiting-queue-section mh-475px">
                            <div class="waiting-title">
                                <i class="fas fa-list me-2"></i>คิวรอ
                            </div>

                            <div class="waiting-queues-container" id="waitingQueuesListRight">
                                <div class="text-center text-muted">
                                    <i class="fas fa-spinner fa-spin"></i> กำลังโหลด...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
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
            <?php endif; ?>
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

    <script src="../assets/plugins/jquery/jquery-3.7.1.js"></script>
    
    <script>
        const layoutType = <?php echo json_encode($isDualLayout ? 'dual' : 'single'); ?>;
        let servicePointId = <?php echo $isDualLayout ? 'null' : json_encode($servicePointId); ?>;
        let voiceTemplateId = <?php echo $isDualLayout ? 'null' : json_encode($voiceTemplateId ?? null); ?>;
        const servicePointConfigs = <?php echo json_encode($servicePointConfigs); ?>;
        const layoutSelectors = {
            left: {
                current: '#currentQueueDisplayLeft',
                waiting: '#waitingQueuesListLeft'
            },
            right: {
                current: '#currentQueueDisplayRight',
                waiting: '#waitingQueuesListRight'
            }
        };
        let lastCalledData = {};
        let audioEnabled = false; // Default to false, will be set by settings
        let audioContext = null;
        let activeAudios = []; // เก็บเสียงที่กำลังเล่นอยู่
        let currentPlaybackMeta = null;
        const audioVolumeSetting = 1.0;
        const defaultTestServicePointId = 1;

        // Debug mode toggle
        let debugMode = false; // Set to true for development, false for production

        function debugLog(message, data = null) {
            if (debugMode) {
                console.log(`[Audio Debug] ${message}`, data || '');
            }
        }

        function registerServiceWorker() {
            if (!('serviceWorker' in navigator)) {
                return;
            }

            window.addEventListener('load', () => {
                navigator.serviceWorker.register('../sw.js').catch((error) => {
                    console.error('Service worker registration failed.', error);
                });
            });
        }
        
        $(document).ready(function() {
            updateTime();
            loadQueueData();
            registerServiceWorker();

            // Update time every second
            setInterval(updateTime, 1000);

            // Refresh queue data every 3 seconds
            setInterval(loadQueueData, 3000);

            // Add user interaction handling for unlocking audio
            let audioUnlocked = false;
            function unlockAudio() {
                if (!audioUnlocked) {
                    unlockAudioContext();
                    audioUnlocked = true;
                }
            }
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
        
        // Function to unlock audio context (required by browsers for autoplay)
        function unlockAudioContext() {
            if (audioContext && audioContext.state === 'suspended') {
                debugLog('Unlocking audio context...');
                audioContext.resume().then(() => {
                    debugLog('Audio context unlocked:', audioContext.state);
                }).catch(error => {
                    debugLog('Failed to unlock audio context:', error);
                });
            } else if (!audioContext) {
                audioContext = new (window.AudioContext || window.webkitAudioContext)();
            }
        }
        
        // Preload audio files before playing to avoid missing segments
        function stopCurrentAudio() {
            if (activeAudios.length) {
                activeAudios.forEach(a => {
                    try {
                        a.pause();
                        a.currentTime = 0;
                        a.onended = null;
                        a.onerror = null;
                    } catch (e) {}
                });
                activeAudios = [];
            }
        }

        function preloadAudioFiles(files) {
            const normalize = path => {
                if (path.startsWith('http') || path.startsWith('/')) return path;
                return '../' + path;
            };

            const loaders = files.map(src => new Promise(resolve => {
                const audio = new Audio();
                // Cache busting query to ensure fresh load when recalling
                audio.src = normalize(src) + `?v=${Date.now()}`;
                audio.preload = 'auto';
                audio.volume = audioVolumeSetting;
                audio.addEventListener('canplaythrough', () => resolve({ audio, src }), { once: true });
                audio.addEventListener('error', () => {
                    debugLog('Failed to load audio file:', src);
                    resolve({ audio: null, src });
                }, { once: true });
                audio.load();
            }));

            return Promise.all(loaders).then(results => {
                return {
                    loaded: results.filter(r => r.audio).map(r => r.audio),
                    missing: results.filter(r => !r.audio).map(r => r.src)
                };
            });
        }

        function reportAudioIssue(queueId, missingFiles) {
            if (!missingFiles || missingFiles.length === 0) return;

            $.post('../api/report_audio_issue.php', {
                queue_id: queueId,
                service_point_id: servicePointId,
                missing_files: JSON.stringify(missingFiles)
            }).fail(function() {
                console.error('Failed to report audio issue');
            });
        }

        function cleanupAudioResources(files, callId, status = 'played') {
            if (!callId) {
                return;
            }

            const payload = {
                call_id: callId,
                status: status
            };

            if (Array.isArray(files) && files.length > 0) {
                payload.audio_files = files;
            }

            $.ajax({
                url: '../api/update_audio_status.php',
                method: 'POST',
                data: payload,
                traditional: true
            }).fail(function() {
                debugLog('Failed to update audio status or clean audio files.');
            });
        }

        function playAudioSequence(audioFiles, repeatCount, notificationBefore, queueId = null, callId = null) {
            const preparedFiles = Array.isArray(audioFiles) ? audioFiles.filter(Boolean) : [];
            const filesForCleanup = [...new Set(preparedFiles)];

            if (currentPlaybackMeta) {
                cleanupAudioResources(currentPlaybackMeta.files, currentPlaybackMeta.callId);
                currentPlaybackMeta = null;
            }

            if (!audioEnabled) {
                debugLog('Audio disabled, skipping playback.');
                cleanupAudioResources(filesForCleanup, callId);
                return;
            }

            if (preparedFiles.length === 0) {
                debugLog('No audio files to play.');
                cleanupAudioResources(filesForCleanup, callId, 'failed');
                return;
            }

            if (callId) {
                currentPlaybackMeta = {
                    files: filesForCleanup,
                    callId: callId
                };
            } else {
                currentPlaybackMeta = null;
            }

            unlockAudioContext();
            stopCurrentAudio();
            preloadAudioFiles(preparedFiles).then(result => {
                const loadedAudios = result.loaded;
                if (result.missing.length > 0) {
                    reportAudioIssue(queueId, result.missing);
                }

                let cleanedUp = false;
                const finalizePlayback = (status = 'played') => {
                    if (cleanedUp) {
                        return;
                    }
                    cleanedUp = true;
                    activeAudios = [];
                    if (currentPlaybackMeta && currentPlaybackMeta.callId === callId) {
                        currentPlaybackMeta = null;
                    }
                    cleanupAudioResources(filesForCleanup, callId, status);
                };

                if (loadedAudios.length === 0) {
                    debugLog('Audio files failed to load.');
                    finalizePlayback('failed');
                    return;
                }

                repeatCount = parseInt(repeatCount, 10);
                if (Number.isNaN(repeatCount) || repeatCount < 1) {
                    repeatCount = 1;
                }

                activeAudios = loadedAudios;

                const playSet = () => {
                    let index = 0;
                    const playNext = () => {
                        if (index < loadedAudios.length) {
                            const audio = loadedAudios[index];
                            audio.currentTime = 0;
                            audio.onended = playNext;
                            audio.onerror = function() {
                                debugLog('Audio element error encountered.');
                                finalizePlayback('failed');
                            };
                            audio.play().catch(e => {
                                debugLog('Audio play failed:', e.message);
                                finalizePlayback('failed');
                            });
                            index++;
                        } else if (--repeatCount > 0) {
                            index = 0;
                            playNext();
                        } else {
                            finalizePlayback();
                        }
                    };
                    playNext();
                };

                playSet();
            });
        }

        // Request audio playback with simple retry for reliability
        function requestAudio(queueId, targetServicePointId = servicePointId, templateIdOverride = voiceTemplateId, attempt = 0) {
            const payload = { queue_id: queueId };
            if (targetServicePointId) {
                payload.service_point_id = targetServicePointId;
            }
            if (templateIdOverride) {
                payload.template_id = templateIdOverride;
            }

            $.post('../api/play_queue_audio.php', payload)
                .done(function(response) {
                    if (response.success) {
                        debugLog('Audio API response for queue:', response);
                        playAudioSequence(response.audio_files, response.repeat_count, response.notification_before, queueId, response.call_id);
                        if (response.missing_words && response.missing_words.length) {
                            reportAudioIssue(queueId, response.missing_words);
                        }
                    } else if (attempt < 2) {
                        setTimeout(() => requestAudio(queueId, targetServicePointId, templateIdOverride, attempt + 1), 2000);
                    } else {
                        console.error('Failed to get audio parameters from API:', response.message);
                    }
                })
                .fail(function(xhr, status, error) {
                    if (attempt < 2) {
                        setTimeout(() => requestAudio(queueId, targetServicePointId, templateIdOverride, attempt + 1), 2000);
                    } else {
                        console.error('AJAX call to play_queue_audio.php failed:', error);
                    }
                });
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
            audioEnabled = !audioEnabled;
            updateAudioStatus(audioEnabled ? 'เสียงพร้อม' : 'เสียงปิด', audioEnabled ? 'enabled' : 'disabled');

            localStorage.setItem('audioEnabled', audioEnabled); // Save setting

            if (audioEnabled) {
                testAudio(); // Test audio when enabled
            }
        }

        // Manual audio test
        function testAudio() {
            debugLog('Manual audio test triggered');
            unlockAudioContext();

            if (!audioEnabled) {
                updateAudioStatus('เสียงปิดอยู่', 'disabled');
                debugLog('Audio disabled by user, cannot test.');
                return;
            }

            const testMessage = 'ทดสอบระบบเสียง หมายเลข A001 เชิญที่ห้องตรวจ 1';
            const testConfig = getTestAudioConfig();
            const payload = { custom_message: testMessage };
            payload.service_point_id = testConfig.id || defaultTestServicePointId;
            if (testConfig.template) {
                payload.template_id = testConfig.template;
            }

            $.post('../api/play_queue_audio.php', payload)
                .done(function(response) {
                    if (response.success) {
                        debugLog('Test audio API response:', response);
                        playAudioSequence(response.audio_files, response.repeat_count, response.notification_before, null, response.call_id);
                        if (response.missing_words && response.missing_words.length) {
                            reportAudioIssue(null, response.missing_words);
                        }
                    } else {
                        updateAudioStatus('ทดสอบเสียงล้มเหลว', 'disabled');
                        debugLog('Test audio API error response:', response.message);
                    }
                })
                .fail(function(xhr, status, error) {
                    updateAudioStatus('เชื่อมต่อบริการเสียงไม่ได้', 'disabled');
                    debugLog('Test audio AJAX failed:', error);
                });
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
                    audioContextState: audioContext?.state,
                    browserSupport: {
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

        function displayCurrentQueue(queue, containerSelector) {
            const currentQueueDisplay = $(containerSelector);
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

        function displayWaitingQueues(queues, containerSelector) {
            const waitingQueuesList = $(containerSelector);
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

        function handleAudioForServicePoint(servicePointKey, currentQueue, templateId) {
            const key = servicePointKey !== null ? String(servicePointKey) : 'all';

            if (!currentQueue) {
                lastCalledData[key] = { queueId: null, count: 0, time: null };
                return;
            }

            const previous = lastCalledData[key] || { queueId: null, count: 0, time: null };
            const currentQueueId = currentQueue.queue_id;
            const currentCalledCount = parseInt(currentQueue.called_count) || 1;
            const currentCallTime = currentQueue.last_called_time || null;

            const isNewCall = currentQueueId !== previous.queueId;
            const isRepeatCall = currentQueueId === previous.queueId && (
                currentCalledCount > previous.count || currentCallTime !== previous.time
            );

            if (isNewCall || isRepeatCall) {
                debugLog('Queue call detected:', {
                    isNewCall: isNewCall,
                    isRepeatCall: isRepeatCall,
                    queueId: currentQueueId,
                    calledCount: currentCalledCount,
                    servicePointKey: key
                });

                const targetServicePointId = key === 'all'
                    ? (currentQueue.current_service_point_id || null)
                    : servicePointKey;

                requestAudio(currentQueueId, targetServicePointId, templateId);
            }

            lastCalledData[key] = {
                queueId: currentQueueId,
                count: currentCalledCount,
                time: currentCallTime
            };
        }

        function getTestAudioConfig() {
            if (layoutType === 'dual') {
                const leftConfig = servicePointConfigs.left || {};
                const rightConfig = servicePointConfigs.right || {};
                if (leftConfig.id) {
                    return { id: leftConfig.id, template: leftConfig.voice_template_id || null };
                }
                if (rightConfig.id) {
                    return { id: rightConfig.id, template: rightConfig.voice_template_id || null };
                }
            }

            return { id: servicePointId || null, template: voiceTemplateId || null };
        }

        function loadQueueData() {
            if (layoutType === 'dual') {
                Object.keys(servicePointConfigs).forEach(position => {
                    const config = servicePointConfigs[position] || {};
                    const selectors = layoutSelectors[position];

                    if (!selectors) {
                        return;
                    }

                    if (!config.id) {
                        displayCurrentQueue(null, selectors.current);
                        displayWaitingQueues([], selectors.waiting);
                        return;
                    }

                    const url = `../api/get_monitor_data.php?service_point_id=${config.id}`;
                    $.get(url, { _: Date.now() }, function(data) {
                        displayCurrentQueue(data.current, selectors.current);
                        displayWaitingQueues(data.waiting, selectors.waiting);
                        updateLastUpdate();
                        handleAudioForServicePoint(config.id, data.current, config.voice_template_id);
                    }).fail(function() {
                        console.error('Failed to load queue data for service point', config.id);
                        showOfflineStatus();
                    });
                });
            } else {
                const url = servicePointId ?
                    `../api/get_monitor_data.php?service_point_id=${servicePointId}` :
                    '../api/get_monitor_data.php';

                $.get(url, { _: Date.now() }, function(data) {
                    displayCurrentQueue(data.current, '#currentQueueDisplay');
                    displayWaitingQueues(data.waiting, '#waitingQueuesList');
                    updateLastUpdate();

                    const key = servicePointId ? servicePointId : 'all';
                    handleAudioForServicePoint(key, data.current, voiceTemplateId);
                }).fail(function() {
                    console.error('Failed to load queue data');
                    showOfflineStatus();
                });
            }
        }
    </script>
</body>
</html>
