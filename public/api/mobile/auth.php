<?php
/**
 * Mobile API Authentication
 * ระบบการยืนยันตัวตนสำหรับ Mobile API
 */

require_once dirname(__DIR__, 3) . '/config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Validate API Key and Secret
 */
function validateApiCredentials($apiKey, $apiSecret) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM mobile_app_registrations 
            WHERE api_key = ? AND api_secret = ? AND is_active = 1
            AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $stmt->execute([$apiKey, hash('sha256', $apiSecret)]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("API validation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create mobile session
 */
function createMobileSession($registrationId, $deviceId, $deviceInfo = []) {
    try {
        $db = getDB();
        $db->beginTransaction();
        
        // Get or create mobile user
        $stmt = $db->prepare("
            SELECT * FROM mobile_users 
            WHERE device_id = ? AND registration_id = ?
        ");
        $stmt->execute([$deviceId, $registrationId]);
        $mobileUser = $stmt->fetch();
        
        if (!$mobileUser) {
            // Create new mobile user
            $stmt = $db->prepare("
                INSERT INTO mobile_users 
                (registration_id, device_id, device_token, platform, app_version, os_version, device_model)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $registrationId,
                $deviceId,
                $deviceInfo['device_token'] ?? null,
                $deviceInfo['platform'] ?? 'unknown',
                $deviceInfo['app_version'] ?? null,
                $deviceInfo['os_version'] ?? null,
                $deviceInfo['device_model'] ?? null
            ]);
            $mobileUserId = $db->lastInsertId();
        } else {
            $mobileUserId = $mobileUser['mobile_user_id'];
            
            // Update last login and device info
            $stmt = $db->prepare("
                UPDATE mobile_users 
                SET last_login = NOW(), 
                    device_token = COALESCE(?, device_token),
                    app_version = COALESCE(?, app_version),
                    os_version = COALESCE(?, os_version),
                    device_model = COALESCE(?, device_model)
                WHERE mobile_user_id = ?
            ");
            $stmt->execute([
                $deviceInfo['device_token'] ?? null,
                $deviceInfo['app_version'] ?? null,
                $deviceInfo['os_version'] ?? null,
                $deviceInfo['device_model'] ?? null,
                $mobileUserId
            ]);
        }
        
        // Create session
        $sessionId = bin2hex(random_bytes(32));
        $sessionTimeout = (int)getSetting('session_timeout', '3600');
        $expiresAt = date('Y-m-d H:i:s', time() + $sessionTimeout);
        
        $stmt = $db->prepare("
            INSERT INTO mobile_sessions 
            (session_id, mobile_user_id, registration_id, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $sessionId,
            $mobileUserId,
            $registrationId,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $expiresAt
        ]);
        
        $db->commit();
        
        return [
            'session_id' => $sessionId,
            'mobile_user_id' => $mobileUserId,
            'expires_at' => $expiresAt,
            'expires_in' => $sessionTimeout
        ];
        
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Create session error: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate session
 */
function validateSession($sessionId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT ms.*, mu.device_id, mar.app_name
            FROM mobile_sessions ms
            JOIN mobile_users mu ON ms.mobile_user_id = mu.mobile_user_id
            JOIN mobile_app_registrations mar ON ms.registration_id = mar.registration_id
            WHERE ms.session_id = ? AND ms.expires_at > NOW()
            AND mu.is_active = 1 AND mar.is_active = 1
        ");
        $stmt->execute([$sessionId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Session validation error: " . $e->getMessage());
        return false;
    }
}

// Handle authentication requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'login':
            $apiKey = $input['api_key'] ?? '';
            $apiSecret = $input['api_secret'] ?? '';
            $deviceId = $input['device_id'] ?? '';
            $deviceInfo = $input['device_info'] ?? [];
            
            if (empty($apiKey) || empty($apiSecret) || empty($deviceId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing required parameters',
                    'code' => 'MISSING_PARAMS'
                ]);
                exit;
            }
            
            // Validate API credentials
            $registration = validateApiCredentials($apiKey, $apiSecret);
            if (!$registration) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid API credentials',
                    'code' => 'INVALID_CREDENTIALS'
                ]);
                exit;
            }
            
            // Create session
            $session = createMobileSession($registration['registration_id'], $deviceId, $deviceInfo);
            if (!$session) {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to create session',
                    'code' => 'SESSION_ERROR'
                ]);
                exit;
            }
            
            // Update last used
            try {
                $db = getDB();
                $stmt = $db->prepare("UPDATE mobile_app_registrations SET last_used_at = NOW() WHERE registration_id = ?");
                $stmt->execute([$registration['registration_id']]);
            } catch (Exception $e) {
                // Non-critical error
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'session_id' => $session['session_id'],
                    'expires_at' => $session['expires_at'],
                    'expires_in' => $session['expires_in'],
                    'app_name' => $registration['app_name'],
                    'api_version' => getSetting('api_version', '1.0')
                ]
            ]);
            break;
            
        case 'validate':
            $sessionId = $input['session_id'] ?? '';
            
            if (empty($sessionId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing session_id',
                    'code' => 'MISSING_SESSION'
                ]);
                exit;
            }
            
            $session = validateSession($sessionId);
            if (!$session) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid or expired session',
                    'code' => 'INVALID_SESSION'
                ]);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'valid' => true,
                    'expires_at' => $session['expires_at'],
                    'app_name' => $session['app_name']
                ]
            ]);
            break;
            
        case 'logout':
            $sessionId = $input['session_id'] ?? '';
            
            if (!empty($sessionId)) {
                try {
                    $db = getDB();
                    $stmt = $db->prepare("DELETE FROM mobile_sessions WHERE session_id = ?");
                    $stmt->execute([$sessionId]);
                } catch (Exception $e) {
                    // Non-critical error
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action',
                'code' => 'INVALID_ACTION'
            ]);
            break;
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
        'code' => 'METHOD_NOT_ALLOWED'
    ]);
}
?>
