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
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }

        return $_SESSION[CSRF_TOKEN_NAME];
    }
}

if (!function_exists('verifyCSRFToken')) {
    function verifyCSRFToken($token) {
        return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
}

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($data) {
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirectTo')) {
    function redirectTo($url) {
        header('Location: ' . $url);
        exit();
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['staff_id']) && isset($_SESSION['username']);
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            redirectTo(BASE_URL . '/staff/login.php');
        }
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission($permission) {
        if (!isLoggedIn()) {
            return false;
        }

        return in_array($permission, $_SESSION['permissions'] ?? []);
    }
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

/**
 * Get the label used for service points throughout the system
 *
 * @return string
 */
function getServicePointLabel() {
    return getSetting('service_point_label', 'จุดบริการ');
}

// Working hours helpers (used by staff/index.php)
function getWorkingHoursStart() {
    return getSetting('working_hours_start', '08:30');
}

function getWorkingHoursEnd() {
    return getSetting('working_hours_end', '17:30');
}

function isWithinWorkingHours() {
    $start = getWorkingHoursStart(); // HH:MM
    $end = getWorkingHoursEnd();     // HH:MM

    $now = new DateTime('now');
    $startDt = DateTime::createFromFormat('H:i', $start) ?: new DateTime('08:30');
    $endDt = DateTime::createFromFormat('H:i', $end) ?: new DateTime('17:30');

    // Align to today
    $startDt->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
    $endDt->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));

    return ($now >= $startDt && $now <= $endDt);
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= (1 << (10 * $pow));

    return round($bytes, 2) . ' ' . $units[$pow];
}

// -------------------------------------------------------------------------
// Kiosk management helpers
// -------------------------------------------------------------------------

if (!function_exists('getKioskCookieName')) {
    /**
     * Get the cookie name used for kiosk device identification.
     *
     * @return string
     */
    function getKioskCookieName() {
        return 'queue_kiosk_token';
    }
}

if (!function_exists('sanitizeKioskToken')) {
    /**
     * Sanitize and validate kiosk token values.
     *
     * @param string|null $token Raw token value
     *
     * @return string|null Sanitized token or null when invalid
     */
    function sanitizeKioskToken($token) {
        if (!is_string($token)) {
            return null;
        }

        $trimmed = trim($token);
        if ($trimmed === '') {
            return null;
        }

        if (!preg_match('/^[A-Fa-f0-9]{32,128}$/', $trimmed)) {
            return null;
        }

        return strtoupper($trimmed);
    }
}

if (!function_exists('ensureKioskCookie')) {
    /**
     * Ensure the kiosk cookie exists and return its value.
     *
     * If the cookie is missing or invalid, a new secure token will be
     * generated and stored for one year.
     *
     * @return string The kiosk token stored in the browser cookie
     */
    function ensureKioskCookie() {
        $existing = sanitizeKioskToken($_COOKIE[getKioskCookieName()] ?? null);
        if ($existing !== null) {
            return $existing;
        }

        $token = strtoupper(bin2hex(random_bytes(32)));

        $secure = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
        setcookie(
            getKioskCookieName(),
            $token,
            [
                'expires' => time() + (86400 * 365),
                'path' => '/',
                'secure' => $secure,
                'httponly' => false,
                'samesite' => 'Lax',
            ]
        );

        $_COOKIE[getKioskCookieName()] = $token;

        return $token;
    }
}

if (!function_exists('getKioskTokenFromCookie')) {
    /**
     * Retrieve the kiosk token from the current request cookie without creating
     * a new one.
     *
     * @return string|null
     */
    function getKioskTokenFromCookie() {
        return sanitizeKioskToken($_COOKIE[getKioskCookieName()] ?? null);
    }
}

if (!function_exists('generateKioskIdentifier')) {
    /**
     * Generate a short identifier for kiosk records.
     *
     * @return string
     */
    function generateKioskIdentifier() {
        return 'KIOSK_' . strtoupper(bin2hex(random_bytes(4)));
    }
}

if (!function_exists('ensureKioskDevicesTableExists')) {
    /**
     * Create kiosk_devices table when it does not exist yet.
     *
     * @return void
     */
    function ensureKioskDevicesTableExists() {
        try {
            $db = getDB();
            $db->exec(
                "CREATE TABLE IF NOT EXISTS kiosk_devices (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    kiosk_name VARCHAR(100) NOT NULL,
                    cookie_token VARCHAR(128) NOT NULL UNIQUE,
                    identifier VARCHAR(50) NOT NULL UNIQUE,
                    printer_ip VARCHAR(100) DEFAULT NULL,
                    printer_port INT DEFAULT 9100,
                    is_active TINYINT(1) NOT NULL DEFAULT 1,
                    notes TEXT DEFAULT NULL,
                    last_seen_at DATETIME DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (Exception $e) {
            Logger::error('Failed to ensure kiosk_devices table exists', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

if (!function_exists('findKioskByToken')) {
    /**
     * Find kiosk record by cookie token.
     *
     * @param string|null $token Kiosk token to lookup
     *
     * @return array|null
     */
    function findKioskByToken($token) {
        if ($token === null) {
            return null;
        }

        try {
            $db = getDB();
            $stmt = $db->prepare('SELECT * FROM kiosk_devices WHERE cookie_token = ? LIMIT 1');
            $stmt->execute([$token]);
            $kiosk = $stmt->fetch();

            if ($kiosk && empty($kiosk['identifier'])) {
                $identifier = generateKioskIdentifier();
                $updateStmt = $db->prepare('UPDATE kiosk_devices SET identifier = ?, updated_at = NOW() WHERE id = ?');
                $updateStmt->execute([$identifier, $kiosk['id']]);
                $kiosk['identifier'] = $identifier;
            }

            return $kiosk ?: null;
        } catch (Exception $e) {
            Logger::error('Failed to fetch kiosk by token', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}

if (!function_exists('getActiveKioskFromRequest')) {
    /**
     * Retrieve active kiosk record associated with the current request.
     *
     * @return array|null
     */
    function getActiveKioskFromRequest() {
        $token = getKioskTokenFromCookie();
        if ($token === null) {
            return null;
        }

        $kiosk = findKioskByToken($token);
        if (!$kiosk || (int) $kiosk['is_active'] !== 1) {
            return null;
        }

        return $kiosk;
    }
}

if (!function_exists('updateKioskLastSeen')) {
    /**
     * Update kiosk last seen timestamp.
     *
     * @param int $kioskId Kiosk primary key
     *
     * @return void
     */
    function updateKioskLastSeen($kioskId) {
        try {
            $db = getDB();
            $stmt = $db->prepare('UPDATE kiosk_devices SET last_seen_at = NOW() WHERE id = ?');
            $stmt->execute([$kioskId]);
        } catch (Exception $e) {
            Logger::error('Failed to update kiosk last seen', [
                'error' => $e->getMessage(),
                'kiosk_id' => $kioskId,
            ]);
        }
    }
}

if (!function_exists('formatKioskTokenForDisplay')) {
    /**
     * Split kiosk token into readable chunks for UI display.
     *
     * @param string $token
     *
     * @return string
     */
    function formatKioskTokenForDisplay($token) {
        return trim(chunk_split($token, 8, ' '));
    }
}

if (!function_exists('resolveAppointmentStatusLabel')) {
    function resolveAppointmentStatusLabel($status)
    {
        $statusMap = [
            '0' => 'ยกเลิก',
            '1' => 'รอพบแพทย์',
            '2' => 'สำเร็จ',
            '3' => 'เลื่อนนัด',
        ];

        $status = (string) ($status ?? '');
        if (isset($statusMap[$status])) {
            return $statusMap[$status];
        }

        return trim((string) $status) !== '' ? (string) $status : 'ไม่ระบุสถานะ';
    }
}

if (!function_exists('normaliseAppointmentDateTime')) {
    function normaliseAppointmentDateTime(array $appointment, DateTimeZone $timezone, bool $isEnd = false): ?DateTimeImmutable
    {
        $key = $isEnd ? 'end_datetime' : 'datetime';
        $value = isset($appointment[$key]) ? trim((string) $appointment[$key]) : '';

        if ($value !== '') {
            try {
                $dateTime = new DateTimeImmutable($value);
                return $dateTime->setTimezone($timezone);
            } catch (Exception $e) {
                // Ignore parsing error and fall back to metadata parsing.
            }
        }

        $metadata = isset($appointment['metadata']) && is_array($appointment['metadata']) ? $appointment['metadata'] : [];
        $dateCandidate = $isEnd
            ? ($metadata['enddate'] ?? $metadata['nextdate'] ?? null)
            : ($metadata['nextdate'] ?? $metadata['vstdate'] ?? null);
        $timeCandidate = $isEnd
            ? ($metadata['nexttime_end'] ?? $metadata['endtime'] ?? null)
            : ($metadata['nexttime'] ?? $metadata['entry_time'] ?? null);

        $dateCandidate = is_string($dateCandidate) ? trim($dateCandidate) : '';
        $timeCandidate = is_string($timeCandidate) ? trim($timeCandidate) : '';

        if ($dateCandidate === '') {
            return null;
        }

        if ($timeCandidate === '') {
            $timeCandidate = '00:00:00';
        }

        $timeCandidate = substr($timeCandidate, 0, 8);

        try {
            return new DateTimeImmutable($dateCandidate . ' ' . $timeCandidate, $timezone);
        } catch (Exception $e) {
            return null;
        }
    }
}

if (!function_exists('normaliseAppointmentRecords')) {
    function normaliseAppointmentRecords(array $appointments, DateTimeZone $timezone, ?string $targetDate = null): array
    {
        $targetDateString = null;
        if ($targetDate !== null && trim($targetDate) !== '') {
            try {
                $target = new DateTimeImmutable($targetDate, $timezone);
                $targetDateString = $target->format('Y-m-d');
            } catch (Exception $e) {
                $targetDateString = null;
            }
        }

        $normalised = [];

        foreach ($appointments as $appointment) {
            if (!is_array($appointment)) {
                continue;
            }

            $start = normaliseAppointmentDateTime($appointment, $timezone, false);
            $end = normaliseAppointmentDateTime($appointment, $timezone, true);

            $appointmentDate = null;
            if ($start instanceof DateTimeImmutable) {
                $appointmentDate = $start->format('Y-m-d');
            } else {
                $metadata = isset($appointment['metadata']) && is_array($appointment['metadata']) ? $appointment['metadata'] : [];
                $dateCandidate = isset($metadata['nextdate']) ? trim((string) $metadata['nextdate']) : '';
                if ($dateCandidate !== '') {
                    $appointmentDate = $dateCandidate;
                }
            }

            if ($targetDateString !== null && $appointmentDate !== null && $appointmentDate !== $targetDateString) {
                continue;
            }

            $startTimeDisplay = $start instanceof DateTimeImmutable ? $start->format('H:i') : null;
            if ($startTimeDisplay === null) {
                $metadata = isset($appointment['metadata']) && is_array($appointment['metadata']) ? $appointment['metadata'] : [];
                $nextTime = isset($metadata['nexttime']) ? trim((string) $metadata['nexttime']) : '';
                if ($nextTime !== '') {
                    $startTimeDisplay = substr($nextTime, 0, 5);
                }
            }

            $endTimeDisplay = $end instanceof DateTimeImmutable ? $end->format('H:i') : null;
            if ($endTimeDisplay === null) {
                $metadata = isset($appointment['metadata']) && is_array($appointment['metadata']) ? $appointment['metadata'] : [];
                $endTime = isset($metadata['nexttime_end']) ? trim((string) $metadata['nexttime_end']) : '';
                if ($endTime === '') {
                    $endTime = isset($metadata['endtime']) ? trim((string) $metadata['endtime']) : '';
                }
                if ($endTime !== '') {
                    $endTimeDisplay = substr($endTime, 0, 5);
                }
            }

            if ($endTimeDisplay === '00:00') {
                $endTimeDisplay = null;
            }

            $timeRange = null;
            if ($startTimeDisplay !== null && $endTimeDisplay !== null) {
                $timeRange = $startTimeDisplay . ' - ' . $endTimeDisplay;
            } elseif ($startTimeDisplay !== null) {
                $timeRange = $startTimeDisplay;
            }

            $metadata = isset($appointment['metadata']) && is_array($appointment['metadata']) ? $appointment['metadata'] : [];
            $department = trim((string) ($appointment['department'] ?? ($metadata['clinic_name'] ?? '')));
            $clinicName = trim((string) ($metadata['clinic_name'] ?? ''));
            $doctor = trim((string) ($appointment['doctor'] ?? ($metadata['userlogin'] ?? '')));
            $notes = trim((string) ($appointment['notes'] ?? ($metadata['note_emphasized'] ?? '')));
            $cause = trim((string) ($metadata['app_cause'] ?? ''));

            $statusCode = (string) ($appointment['status'] ?? '');
            $statusLabel = resolveAppointmentStatusLabel($statusCode);

            $sortKey = null;
            if ($start instanceof DateTimeImmutable) {
                $sortKey = $start->format('Y-m-d H:i:s');
            } elseif ($appointmentDate !== null) {
                $sortKey = $appointmentDate . ' ' . ($startTimeDisplay ?? '00:00:00');
            }

            $normalised[] = [
                'appointment_id' => trim((string) ($appointment['appointment_id'] ?? '')),
                'time_range' => $timeRange,
                'start_time' => $startTimeDisplay,
                'end_time' => $endTimeDisplay,
                'department' => $department !== '' ? $department : ($clinicName !== '' ? $clinicName : null),
                'clinic_name' => $clinicName !== '' ? $clinicName : null,
                'doctor' => $doctor,
                'status' => $statusCode,
                'status_label' => $statusLabel,
                'notes' => $notes,
                'cause' => $cause,
                'duration_min' => isset($appointment['duration_min']) && is_numeric($appointment['duration_min'])
                    ? (int) $appointment['duration_min']
                    : null,
                'sort_key' => $sortKey,
            ];
        }

        usort($normalised, static function ($a, $b) {
            $aKey = $a['sort_key'] ?? '';
            $bKey = $b['sort_key'] ?? '';
            return strcmp($aKey, $bKey);
        });

        foreach ($normalised as &$entry) {
            unset($entry['sort_key']);
        }
        unset($entry);

        return $normalised;
    }
}

if (!function_exists('fetchAppointmentsForIdCard')) {
    function fetchAppointmentsForIdCard(string $idCard, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $cleanId = preg_replace('/\D/', '', $idCard);
        $cleanId = $cleanId !== null ? $cleanId : '';

        if (strlen($cleanId) !== 13) {
            return [
                'ok' => false,
                'error' => 'เลขบัตรประชาชนไม่ถูกต้อง',
                'appointments' => [],
                'patient' => null,
                'raw' => null,
            ];
        }

        $apiUrl = trim((string) env('APPOINTMENT_API_URL', ''));
        $apiKey = trim((string) env('APPOINTMENT_API_KEY', ''));

        if ($apiUrl === '' || $apiKey === '') {
            return [
                'ok' => false,
                'error' => 'ระบบยังไม่ได้ตั้งค่า APPOINTMENT_API_URL หรือ APPOINTMENT_API_KEY',
                'appointments' => [],
                'patient' => null,
                'raw' => null,
            ];
        }

        $timezone = new DateTimeZone(env('APP_TIMEZONE', 'Asia/Bangkok'));
        $today = new DateTimeImmutable('now', $timezone);

        $from = $dateFrom !== null && trim($dateFrom) !== '' ? trim($dateFrom) : $today->format('Y-m-d');
        $to = $dateTo !== null && trim($dateTo) !== '' ? trim($dateTo) : $from;

        $payload = [
            'idcard' => $cleanId,
            'date_from' => $from,
            'date_to' => $to,
        ];

        $ch = curl_init($apiUrl);
        if ($ch === false) {
            return [
                'ok' => false,
                'error' => 'ไม่สามารถเริ่มการเชื่อมต่อ cURL ได้',
                'appointments' => [],
                'patient' => null,
                'raw' => null,
            ];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $responseBody = curl_exec($ch);
        $curlErrorNumber = curl_errno($ch);
        $curlErrorMessage = curl_error($ch);
        $httpCode = (int) (curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0);
        curl_close($ch);

        if ($responseBody === false) {
            $message = $curlErrorMessage !== '' ? $curlErrorMessage : 'ไม่สามารถเชื่อมต่อ API ได้';
            return [
                'ok' => false,
                'error' => $message . ($curlErrorNumber ? " (รหัส: {$curlErrorNumber})" : ''),
                'appointments' => [],
                'patient' => null,
                'raw' => null,
            ];
        }

        $decoded = json_decode($responseBody, true);
        if (!is_array($decoded)) {
            return [
                'ok' => false,
                'error' => 'รูปแบบข้อมูลจาก API ไม่ถูกต้อง',
                'appointments' => [],
                'patient' => null,
                'raw' => null,
            ];
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            $apiMessage = isset($decoded['message']) ? trim((string) $decoded['message']) : '';
            if ($apiMessage === '') {
                $apiMessage = 'API ตอบกลับสถานะ ' . $httpCode;
            }

            return [
                'ok' => false,
                'error' => $apiMessage,
                'appointments' => [],
                'patient' => null,
                'raw' => $decoded,
            ];
        }

        $success = (bool) ($decoded['ok'] ?? false);
        $error = null;
        if (!$success) {
            $error = isset($decoded['message']) ? trim((string) $decoded['message']) : 'ไม่พบข้อมูลนัดหมาย';
        }

        $rawAppointments = isset($decoded['appointments']) && is_array($decoded['appointments']) ? $decoded['appointments'] : [];
        $normalisedAppointments = normaliseAppointmentRecords($rawAppointments, $timezone, $from);

        $patient = isset($decoded['patient']) && is_array($decoded['patient']) ? $decoded['patient'] : [];
        $patientData = null;
        if (!empty($patient)) {
            $fullNameTh = isset($patient['fullname_th']) ? trim((string) $patient['fullname_th']) : '';
            $fullNameEn = isset($patient['fullname_en']) ? trim((string) $patient['fullname_en']) : '';
            $displayName = $fullNameTh !== '' ? $fullNameTh : $fullNameEn;

            $patientData = [
                'hn' => isset($patient['hn']) ? trim((string) $patient['hn']) : '',
                'idcard' => isset($patient['idcard']) ? trim((string) $patient['idcard']) : $cleanId,
                'fullname_th' => $fullNameTh,
                'fullname_en' => $fullNameEn,
                'birthday' => isset($patient['birthday']) ? trim((string) $patient['birthday']) : '',
                'display_name' => $displayName,
            ];
        }

        return [
            'ok' => $success,
            'error' => $error,
            'appointments' => $normalisedAppointments,
            'patient' => $patientData,
            'raw' => $decoded,
        ];
    }
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
