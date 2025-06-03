<?php
// Global Configuration
session_start();

// Timezone
date_default_timezone_set('Asia/Bangkok');

// Error reporting (ปิดในการใช้งานจริง)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database
require_once __DIR__ . '/database.php';

// Base URL
define('BASE_URL', 'http://localhost/yuwa_que/v4');

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');

// Queue Settings
define('QUEUE_PREFIX_LENGTH', 1);
define('QUEUE_NUMBER_LENGTH', 3);

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
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

function getSetting($key, $default = '') {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function setSetting($key, $value, $description = '') {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = CURRENT_TIMESTAMP");
        $stmt->execute([$key, $value, $description, $value]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
