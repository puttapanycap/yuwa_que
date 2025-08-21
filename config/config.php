<?php
// Global Configuration
require_once __DIR__ . '/env.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Bangkok'));

// Error reporting
if (env('APP_DEBUG', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Include database
require_once __DIR__ . '/database.php';

// Core Constants (from environment only)
define('BASE_URL', env('BASE_URL'));
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/' . env('UPLOAD_PATH', 'uploads'));
define('LOG_PATH', ROOT_PATH . '/' . env('LOG_PATH', 'logs'));
define('CACHE_PATH', ROOT_PATH . '/' . env('CACHE_PATH', 'cache'));
define('BACKUP_PATH', ROOT_PATH . '/' . env('BACKUP_PATH', 'backups'));

// Security (from environment only)
define('CSRF_TOKEN_NAME', env('CSRF_TOKEN_NAME', 'csrf_token'));
define('JWT_SECRET', env('JWT_SECRET', 'change-this-secret-key'));
define('SESSION_LIFETIME', env('SESSION_LIFETIME', 3600));

// File Upload Settings (from environment only)
define('MAX_FILE_SIZE', env('MAX_FILE_SIZE', 10485760)); // 10MB
define('ALLOWED_IMAGE_TYPES', explode(',', env('ALLOWED_IMAGE_TYPES', 'jpg,jpeg,png,gif')));
define('ALLOWED_AUDIO_TYPES', explode(',', env('ALLOWED_AUDIO_TYPES', 'mp3,wav,ogg')));

// Create directories if they don't exist
$directories = [UPLOAD_PATH, LOG_PATH, CACHE_PATH, BACKUP_PATH];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Logging class
class Logger {
    private static $logPath;
    
    public static function init() {
        self::$logPath = LOG_PATH;
    }
    
    public static function log($level, $message, $context = []) {
        if (!self::$logPath) {
            self::init();
        }
        
        $logLevel = env('LOG_LEVEL', 'info');
        $levels = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];
        
        if ($levels[$level] < $levels[$logLevel]) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logEntry = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
        
        $logFile = self::$logPath . '/' . date('Y-m-d') . '.log';
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Cleanup old log files
        self::cleanupLogs();
    }
    
    public static function debug($message, $context = []) {
        self::log('debug', $message, $context);
    }
    
    public static function info($message, $context = []) {
        self::log('info', $message, $context);
    }
    
    public static function warning($message, $context = []) {
        self::log('warning', $message, $context);
    }
    
    public static function error($message, $context = []) {
        self::log('error', $message, $context);
    }
    
    private static function cleanupLogs() {
        $maxFiles = env('LOG_MAX_FILES', 30);
        $files = glob(self::$logPath . '/*.log');
        
        if (count($files) > $maxFiles) {
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            $filesToDelete = array_slice($files, 0, count($files) - $maxFiles);
            foreach ($filesToDelete as $file) {
                unlink($file);
            }
        }
    }
}

// Cache class
class Cache {
    private static $cachePath;
    private static $enabled;
    
    public static function init() {
        self::$cachePath = CACHE_PATH;
        self::$enabled = env('CACHE_ENABLED', true);
    }
    
    public static function get($key, $default = null) {
        if (!self::$enabled) {
            return $default;
        }
        
        if (!self::$cachePath) {
            self::init();
        }
        
        $file = self::$cachePath . '/' . md5($key) . '.cache';
        
        if (!file_exists($file)) {
            return $default;
        }
        
        $data = unserialize(file_get_contents($file));
        
        if ($data['expires'] < time()) {
            unlink($file);
            return $default;
        }
        
        return $data['value'];
    }
    
    public static function set($key, $value, $ttl = null) {
        if (!self::$enabled) {
            return false;
        }
        
        if (!self::$cachePath) {
            self::init();
        }
        
        if ($ttl === null) {
            $ttl = env('CACHE_DEFAULT_TTL', 3600);
        }
        
        $file = self::$cachePath . '/' . md5($key) . '.cache';
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        return file_put_contents($file, serialize($data), LOCK_EX) !== false;
    }
    
    public static function delete($key) {
        if (!self::$cachePath) {
            self::init();
        }
        
        $file = self::$cachePath . '/' . md5($key) . '.cache';
        
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }
    
    public static function clear() {
        if (!self::$cachePath) {
            self::init();
        }
        
        $files = glob(self::$cachePath . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        
        return true;
    }
}

// Functions
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirectTo($url) {
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['staff_id']) && isset($_SESSION['username']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirectTo(BASE_URL . '/staff/login.php');
    }
}

function hasPermission($permission) {
    if (!isLoggedIn()) return false;
    return in_array($permission, $_SESSION['permissions'] ?? []);
}

function logActivity($description, $staff_id = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO audit_logs (staff_id, action_description, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $staff_id ?? $_SESSION['staff_id'] ?? null,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        Logger::info('Activity logged', [
            'staff_id' => $staff_id ?? $_SESSION['staff_id'] ?? null,
            'description' => $description
        ]);
    } catch (Exception $e) {
        Logger::error("Failed to log activity: " . $e->getMessage());
    }
}

function getSetting($key, $default = '') {
    // Settings are now managed through admin interface only
    static $settingsCache = [];
    
    if (isset($settingsCache[$key])) {
        return $settingsCache[$key];
    }
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        $value = $result ? $result['setting_value'] : $default;
        $settingsCache[$key] = $value;
        
        return $value;
    } catch (Exception $e) {
        Logger::error("Failed to get setting: " . $e->getMessage(), ['key' => $key]);
        return $default;
    }
}

function setSetting($key, $value, $description = '') {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = CURRENT_TIMESTAMP");
        $stmt->execute([$key, $value, $description, $value]);
        
        Logger::info('Setting updated', ['key' => $key, 'value' => $value]);
        
        // Clear settings cache
        static $settingsCache = [];
        $settingsCache = [];
        
        return true;
    } catch (Exception $e) {
        Logger::error("Failed to set setting: " . $e->getMessage(), ['key' => $key]);
        return false;
    }
}

// Application Configuration Functions (from admin settings)
function getAppName() {
    return getSetting('app_name', 'โรงพยาบาลยุวประสาทไวทโยปถัมภ์');
}

function getAppDescription() {
    return getSetting('app_description', 'ระบบจัดการคิวโรงพยาบาล');
}

function getAppLogo() {
    return getSetting('app_logo', '');
}

function getAppTimezone() {
    return getSetting('app_timezone', 'Asia/Bangkok');
}

function getAppLanguage() {
    return getSetting('app_language', 'th');
}

// Queue System Configuration Functions (from admin settings)
function getQueuePrefixLength() {
    return (int)getSetting('queue_prefix_length', 1);
}

function getQueueNumberLength() {
    return (int)getSetting('queue_number_length', 3);
}

function getMaxQueuePerDay() {
    return (int)getSetting('max_queue_per_day', 999);
}

function getQueueTimeoutMinutes() {
    return (int)getSetting('queue_timeout_minutes', 30);
}

function getDisplayRefreshInterval() {
    return (int)getSetting('display_refresh_interval', 3);
}

function isPriorityQueueEnabled() {
    return getSetting('enable_priority_queue', 'false') === 'true';
}

function isAutoForwardEnabled() {
    return getSetting('auto_forward_enabled', 'false') === 'true';
}

// Working Hours Functions (from admin settings)
function getWorkingHoursStart() {
    return getSetting('working_hours_start', '08:00');
}

function getWorkingHoursEnd() {
    return getSetting('working_hours_end', '16:00');
}

function isWithinWorkingHours($time = null) {
    if ($time === null) {
        $time = date('H:i');
    }
    
    $start = getWorkingHoursStart();
    $end = getWorkingHoursEnd();
    
    return $time >= $start && $time <= $end;
}

// Audio/TTS Configuration Functions (from admin settings)
function isTTSEnabled() {
    return getSetting('tts_enabled', 'false') === 'true';
}

function getTTSProvider() {
    return getSetting('tts_provider', 'google');
}

function getTTSLanguage() {
    return getSetting('tts_language', 'th-TH');
}

function getTTSVoice() {
    return getSetting('tts_voice', 'th-TH-Standard-A');
}

function getTTSSpeed() {
    return (float)getSetting('tts_speed', 1.0);
}

function getTTSPitch() {
    return (float)getSetting('tts_pitch', 0);
}

function getAudioVolume() {
    return (float)getSetting('audio_volume', 1.0);
}

function getAudioRepeatCount() {
    return (int)getSetting('audio_repeat_count', 1);
}

function isSoundNotificationBeforeEnabled() {
    return getSetting('sound_notification_before', 'true') === 'true';
}

// Email Configuration Functions (from admin settings)
function isEmailNotificationEnabled() {
    return getSetting('email_notifications', 'false') === 'true';
}

function getMailHost() {
    return getSetting('mail_host', 'smtp.gmail.com');
}

function getMailPort() {
    return (int)getSetting('mail_port', 587);
}

function getMailUsername() {
    return getSetting('mail_username', '');
}

function getMailPassword() {
    return getSetting('mail_password', '');
}

function getMailEncryption() {
    return getSetting('mail_encryption', 'tls');
}

function getMailFromAddress() {
    return getSetting('mail_from_address', 'noreply@hospital.com');
}

function getMailFromName() {
    return getSetting('mail_from_name', 'Queue System');
}

// Telegram Configuration Functions (from admin settings)
function isTelegramNotificationEnabled() {
    return getSetting('telegram_notifications', 'false') === 'true';
}

function getTelegramBotToken() {
    return getSetting('telegram_bot_token', '');
}

function getTelegramChatId() {
    return getSetting('telegram_chat_id', '');
}

function getTelegramAdminChatId() {
    return getSetting('telegram_admin_chat_id', '');
}

function getTelegramGroupChatId() {
    return getSetting('telegram_group_chat_id', '');
}

function getTelegramNotifyTemplate() {
    return getSetting('telegram_notify_template', 'คิว {queue_number} กรุณามาที่จุดบริการ {service_point}');
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, 2) . ' ' . $units[$pow];
}


// Performance monitoring
if (env('PERFORMANCE_MONITORING', false)) {
    register_shutdown_function(function() {
        $executionTime = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        $memoryUsage = memory_get_peak_usage(true);
        
        Logger::debug('Performance metrics', [
            'execution_time' => round($executionTime, 4),
            'memory_usage' => formatFileSize($memoryUsage),
            'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]);
    });
}

// Initialize components
Logger::init();
Cache::init();
?>
