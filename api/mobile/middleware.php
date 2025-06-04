<?php
/**
 * Mobile API Middleware
 * ตัวกลางสำหรับการตรวจสอบสิทธิ์และการจำกัดอัตรา
 */

require_once '../../config/config.php';
require_once 'auth.php';

/**
 * API Rate Limiting
 */
function checkRateLimit($registrationId) {
    try {
        $db = getDB();
        
        // Get rate limit for this registration
        $stmt = $db->prepare("SELECT rate_limit_per_minute FROM mobile_app_registrations WHERE registration_id = ?");
        $stmt->execute([$registrationId]);
        $registration = $stmt->fetch();
        
        if (!$registration) {
            return false;
        }
        
        $rateLimit = $registration['rate_limit_per_minute'];
        
        // Count requests in the last minute
        $stmt = $db->prepare("
            SELECT COUNT(*) as request_count 
            FROM api_usage_logs 
            WHERE registration_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ");
        $stmt->execute([$registrationId]);
        $usage = $stmt->fetch();
        
        return $usage['request_count'] < $rateLimit;
        
    } catch (Exception $e) {
        error_log("Rate limit check error: " . $e->getMessage());
        return true; // Allow on error
    }
}

/**
 * Log API usage
 */
function logApiUsage($registrationId, $endpoint, $method, $responseCode, $responseTime = null, $errorMessage = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO api_usage_logs 
            (registration_id, endpoint, method, ip_address, user_agent, response_code, response_time_ms, error_message)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $registrationId,
            $endpoint,
            $method,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $responseCode,
            $responseTime,
            $errorMessage
        ]);
    } catch (Exception $e) {
        error_log("API usage logging error: " . $e->getMessage());
    }
}

/**
 * Check endpoint permission
 */
function checkEndpointPermission($registrationId, $endpoint) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT allowed_endpoints FROM mobile_app_registrations WHERE registration_id = ?");
        $stmt->execute([$registrationId]);
        $registration = $stmt->fetch();
        
        if (!$registration) {
            return false;
        }
        
        $allowedEndpoints = json_decode($registration['allowed_endpoints'], true);
        
        // If no restrictions, allow all
        if (empty($allowedEndpoints)) {
            return true;
        }
        
        // Check if endpoint is allowed
        foreach ($allowedEndpoints as $allowedEndpoint) {
            if (strpos($endpoint, $allowedEndpoint) === 0) {
                return true;
            }
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Endpoint permission check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Mobile API Authentication Middleware
 */
function authenticateMobileAPI($requireSession = true) {
    $startTime = microtime(true);
    
    // Check if API is enabled
    if (getSetting('api_enabled', '1') !== '1') {
        http_response_code(503);
        echo json_encode([
            'success' => false,
            'error' => 'API is currently disabled',
            'code' => 'API_DISABLED'
        ]);
        exit;
    }
    
    // Get session from header or body
    $sessionId = null;
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $sessionId = $matches[1];
    } else {
        $input = json_decode(file_get_contents('php://input'), true);
        $sessionId = $input['session_id'] ?? '';
    }
    
    if ($requireSession && empty($sessionId)) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Missing session token',
            'code' => 'MISSING_TOKEN'
        ]);
        exit;
    }
    
    $session = null;
    if (!empty($sessionId)) {
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
    }
    
    $registrationId = $session['registration_id'] ?? null;
    
    // Check rate limiting
    if ($registrationId && getSetting('rate_limit_enabled', '1') === '1') {
        if (!checkRateLimit($registrationId)) {
            http_response_code(429);
            echo json_encode([
                'success' => false,
                'error' => 'Rate limit exceeded',
                'code' => 'RATE_LIMIT_EXCEEDED'
            ]);
            
            // Log the rate limit violation
            logApiUsage($registrationId, $_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'], 429, null, 'Rate limit exceeded');
            exit;
        }
    }
    
    // Check endpoint permission
    $endpoint = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($registrationId && !checkEndpointPermission($registrationId, $endpoint)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Access denied to this endpoint',
            'code' => 'ACCESS_DENIED'
        ]);
        
        logApiUsage($registrationId, $endpoint, $_SERVER['REQUEST_METHOD'], 403, null, 'Access denied');
        exit;
    }
    
    // Store session info for use in API endpoints
    $GLOBALS['mobile_session'] = $session;
    $GLOBALS['api_start_time'] = $startTime;
    
    // Register shutdown function to log API usage
    register_shutdown_function(function() use ($registrationId, $endpoint, $startTime) {
        if ($registrationId) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $responseCode = http_response_code();
            logApiUsage($registrationId, $endpoint, $_SERVER['REQUEST_METHOD'], $responseCode, $responseTime);
        }
    });
    
    return $session;
}

/**
 * Get current mobile session
 */
function getCurrentMobileSession() {
    return $GLOBALS['mobile_session'] ?? null;
}

/**
 * Send API response
 */
function sendApiResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Send API error
 */
function sendApiError($message, $code = 'UNKNOWN_ERROR', $statusCode = 400) {
    sendApiResponse([
        'success' => false,
        'error' => $message,
        'code' => $code
    ], $statusCode);
}
?>
