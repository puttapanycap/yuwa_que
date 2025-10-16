<?php
/**
 * Advanced Security Middleware
 * Enhanced security features for the queue management system
 */

require_once dirname(__DIR__, 2) . '/config/config.php';

class SecurityMiddleware {
    
    private static $rateLimitCache = [];
    private static $securityConfig = [
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
        'session_timeout' => 3600, // 1 hour
        'password_min_length' => 8,
        'require_2fa' => false,
        'allowed_file_types' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],
        'max_file_size' => 10485760, // 10MB
    ];
    
    /**
     * Initialize security middleware
     */
    public static function init() {
        // Set security headers
        self::setSecurityHeaders();
        
        // Start secure session
        self::startSecureSession();
        
        // Check for security threats
        self::checkSecurityThreats();
    }
    
    /**
     * Set security headers
     */
    private static function setSecurityHeaders() {
        // Prevent XSS attacks
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        
        // HTTPS enforcement
        if (env('FORCE_HTTPS', false)) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://code.jquery.com; " .
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; " .
               "font-src 'self' https://fonts.gstatic.com; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self';";
        header("Content-Security-Policy: $csp");
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
    
    /**
     * Start secure session
     */
    private static function startSecureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', env('FORCE_HTTPS', false) ? 1 : 0);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', 1);
            
            session_start();
            
            // Regenerate session ID periodically
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }
    
    /**
     * Check for security threats
     */
    private static function checkSecurityThreats() {
        $ip = self::getClientIP();
        
        // Check for suspicious patterns
        $suspiciousPatterns = [
            '/\b(union|select|insert|update|delete|drop|create|alter)\b/i',
            '/<script[^>]*>.*?<\/script>/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload|onerror|onclick/i'
        ];
        
        $requestData = array_merge($_GET, $_POST, $_COOKIE);
        foreach ($requestData as $key => $value) {
            if (is_string($value)) {
                foreach ($suspiciousPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        self::logSecurityEvent('suspicious_input', [
                            'ip' => $ip,
                            'pattern' => $pattern,
                            'value' => substr($value, 0, 100),
                            'field' => $key
                        ]);
                        
                        if (env('BLOCK_SUSPICIOUS_REQUESTS', true)) {
                            self::blockRequest('Suspicious input detected');
                        }
                    }
                }
            }
        }
        
        // Check rate limiting
        self::checkRateLimit($ip);
    }
    
    /**
     * Rate limiting
     */
    public static function checkRateLimit($ip, $maxRequests = 100, $timeWindow = 3600) {
        $key = "rate_limit_$ip";
        $current = Cache::get($key, ['count' => 0, 'start_time' => time()]);
        
        // Reset if time window expired
        if (time() - $current['start_time'] > $timeWindow) {
            $current = ['count' => 0, 'start_time' => time()];
        }
        
        $current['count']++;
        Cache::set($key, $current, $timeWindow);
        
        if ($current['count'] > $maxRequests) {
            self::logSecurityEvent('rate_limit_exceeded', [
                'ip' => $ip,
                'requests' => $current['count'],
                'time_window' => $timeWindow
            ]);
            
            http_response_code(429);
            header('Retry-After: ' . ($timeWindow - (time() - $current['start_time'])));
            die(json_encode(['error' => 'Rate limit exceeded']));
        }
    }
    
    
    /**
     * Password strength validation
     */
    public static function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < self::$securityConfig['password_min_length']) {
            $errors[] = 'Password must be at least ' . self::$securityConfig['password_min_length'] . ' characters';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        // Check against common passwords
        $commonPasswords = ['password', '123456', 'admin', 'qwerty', 'letmein'];
        if (in_array(strtolower($password), $commonPasswords)) {
            $errors[] = 'Password is too common';
        }
        
        return empty($errors) ? ['valid' => true] : ['valid' => false, 'errors' => $errors];
    }
    
    /**
     * Check login attempts
     */
    public static function checkLoginAttempts($username, $ip) {
        $key = "login_attempts_{$username}_{$ip}";
        $attempts = Cache::get($key, 0);
        
        if ($attempts >= self::$securityConfig['max_login_attempts']) {
            $lockoutKey = "lockout_{$username}_{$ip}";
            $lockoutTime = Cache::get($lockoutKey);
            
            if ($lockoutTime && time() < $lockoutTime) {
                $remainingTime = $lockoutTime - time();
                return [
                    'allowed' => false, 
                    'remaining_time' => $remainingTime,
                    'message' => "Account locked. Try again in " . ceil($remainingTime / 60) . " minutes."
                ];
            } else {
                // Reset attempts after lockout period
                Cache::delete($key);
                Cache::delete($lockoutKey);
            }
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Record failed login attempt
     */
    public static function recordFailedLogin($username, $ip) {
        $key = "login_attempts_{$username}_{$ip}";
        $attempts = Cache::get($key, 0) + 1;
        Cache::set($key, $attempts, 3600); // 1 hour
        
        if ($attempts >= self::$securityConfig['max_login_attempts']) {
            $lockoutKey = "lockout_{$username}_{$ip}";
            Cache::set($lockoutKey, time() + self::$securityConfig['lockout_duration'], self::$securityConfig['lockout_duration']);
            
            self::logSecurityEvent('account_locked', [
                'username' => $username,
                'ip' => $ip,
                'attempts' => $attempts
            ]);
        }
        
        self::logSecurityEvent('failed_login', [
            'username' => $username,
            'ip' => $ip,
            'attempts' => $attempts
        ]);
    }
    
    /**
     * Clear login attempts on successful login
     */
    public static function clearLoginAttempts($username, $ip) {
        $key = "login_attempts_{$username}_{$ip}";
        $lockoutKey = "lockout_{$username}_{$ip}";
        Cache::delete($key);
        Cache::delete($lockoutKey);
    }
    
    /**
     * Get client IP address
     */
    public static function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Log security events
     */
    private static function logSecurityEvent($event, $data = []) {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO security_logs (event_type, ip_address, user_agent, event_data, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $event,
                self::getClientIP(),
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                json_encode($data)
            ]);
            
            Logger::warning("Security event: $event", $data);
            
        } catch (Exception $e) {
            Logger::error("Failed to log security event: " . $e->getMessage());
        }
    }
    
    /**
     * Block request
     */
    private static function blockRequest($reason) {
        http_response_code(403);
        header('Content-Type: application/json');
        die(json_encode(['error' => 'Access denied', 'reason' => $reason]));
    }
    
    /**
     * Generate secure token
     */
    public static function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateSecureToken();
        }
        return $_SESSION['csrf_token'];
    }
}

// Initialize security middleware
SecurityMiddleware::init();
?>
