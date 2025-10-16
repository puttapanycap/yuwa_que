<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user has admin access
function hasAdminAccess() {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Check if user has admin role
    $allowedRoles = ['admin', 'super_admin'];
    $userRole = $_SESSION['role'] ?? '';
    
    if (!in_array($userRole, $allowedRoles)) {
        return false;
    }
    
    // Check if user has admin permissions
    $requiredPermissions = ['admin_access', 'view_dashboard'];
    $userPermissions = $_SESSION['permissions'] ?? [];
    
    foreach ($requiredPermissions as $permission) {
        if (!in_array($permission, $userPermissions)) {
            return false;
        }
    }
    
    return true;
}

// Function to get appropriate dashboard URL based on permissions
function getAdminDashboardUrl() {
    $permissions = $_SESSION['permissions'] ?? [];
    
    // Super admin gets full dashboard
    if ($_SESSION['role'] === 'super_admin') {
        return 'dashboard.php';
    }
    
    // Check specific admin permissions and redirect accordingly
    if (in_array('manage_users', $permissions)) {
        return 'dashboard.php';
    } elseif (in_array('manage_queues', $permissions)) {
        return 'queue_management.php';
    } elseif (in_array('view_reports', $permissions)) {
        return 'reports.php';
    } elseif (in_array('manage_settings', $permissions)) {
        return 'settings.php';
    } else {
        return 'dashboard.php'; // Default fallback
    }
}

// Function to log access attempt
function logAccessAttempt($success, $reason = '') {
    $description = $success ? 'Admin area access granted' : 'Admin area access denied';
    if ($reason) {
        $description .= ': ' . $reason;
    }
    
    logActivity($description);
    
    Logger::info('Admin access attempt', [
        'success' => $success,
        'reason' => $reason,
        'user_id' => $_SESSION['staff_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

// Main logic
try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        logAccessAttempt(false, 'Not logged in');
        
        // Store the intended URL for redirect after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        redirectTo(BASE_URL . '/staff/login.php?redirect=admin');
    }
    
    // Check if user has admin access
    if (!hasAdminAccess()) {
        $reason = 'Insufficient permissions';
        if ($_SESSION['role'] ?? '' === 'staff') {
            $reason = 'Staff role cannot access admin area';
        }
        
        logAccessAttempt(false, $reason);
        
        // Redirect based on user role
        $userRole = $_SESSION['role'] ?? '';
        switch ($userRole) {
            case 'staff':
                redirectTo(BASE_URL . '/staff/dashboard.php');
                break;
            case 'viewer':
                redirectTo(BASE_URL . '/monitor/display.php');
                break;
            default:
                // Unknown role, logout and redirect to login
                session_destroy();
                redirectTo(BASE_URL . '/staff/login.php?error=invalid_role');
        }
    }
    
    // User has admin access, log successful access
    logAccessAttempt(true);
    
    // Get appropriate dashboard URL
    $dashboardUrl = getAdminDashboardUrl();
    
    // Redirect to dashboard
    redirectTo($dashboardUrl);
    
} catch (Exception $e) {
    // Log error
    Logger::error('Admin index error: ' . $e->getMessage(), [
        'user_id' => $_SESSION['staff_id'] ?? null,
        'trace' => $e->getTraceAsString()
    ]);
    
    // Show error page or redirect to login
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>เกิดข้อผิดพลาด - <?php echo getAppName(); ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Sarabun', sans-serif; }
        </style>
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <h4>เกิดข้อผิดพลาด</h4>
                            <p class="text-muted">ไม่สามารถเข้าสู่ระบบได้ในขณะนี้</p>
                            <a href="<?php echo BASE_URL; ?>/staff/login.php" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบใหม่
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}
?>
