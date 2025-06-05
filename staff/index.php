<?php
require_once '../config/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user has staff access
function hasStaffAccess() {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Check if user has staff role or higher
    $allowedRoles = ['staff', 'admin', 'super_admin'];
    $userRole = $_SESSION['role'] ?? '';
    
    if (!in_array($userRole, $allowedRoles)) {
        return false;
    }
    
    // Check if user has basic staff permissions
    $requiredPermissions = ['staff_access'];
    $userPermissions = $_SESSION['permissions'] ?? [];
    
    // Admin and super_admin automatically have staff access
    if (in_array($userRole, ['admin', 'super_admin'])) {
        return true;
    }
    
    foreach ($requiredPermissions as $permission) {
        if (!in_array($permission, $userPermissions)) {
            return false;
        }
    }
    
    return true;
}

// Function to get appropriate dashboard URL based on role and permissions
function getStaffDashboardUrl() {
    $role = $_SESSION['role'] ?? '';
    $permissions = $_SESSION['permissions'] ?? [];
    
    // Admin users should go to admin area
    if (in_array($role, ['admin', 'super_admin'])) {
        return '../admin/dashboard.php';
    }
    
    // Staff users go to staff dashboard
    if ($role === 'staff') {
        return 'dashboard.php';
    }
    
    // Viewer role goes to monitor
    if ($role === 'viewer') {
        return '../monitor/display.php';
    }
    
    // Default fallback
    return 'dashboard.php';
}

// Function to log access attempt
function logAccessAttempt($success, $reason = '') {
    $description = $success ? 'Staff area access granted' : 'Staff area access denied';
    if ($reason) {
        $description .= ': ' . $reason;
    }
    
    logActivity($description);
    
    Logger::info('Staff access attempt', [
        'success' => $success,
        'reason' => $reason,
        'user_id' => $_SESSION['staff_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

// Function to check working hours
function isWithinWorkingHoursCheck() {
    // Allow admin access anytime
    if (in_array($_SESSION['role'] ?? '', ['admin', 'super_admin'])) {
        return true;
    }
    
    // Check if working hours restriction is enabled
    $enforceWorkingHours = getSetting('enforce_working_hours', 'false') === 'true';
    
    if (!$enforceWorkingHours) {
        return true;
    }
    
    return isWithinWorkingHours();
}

// Main logic
try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        logAccessAttempt(false, 'Not logged in');
        
        // Store the intended URL for redirect after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        redirectTo(BASE_URL . '/staff/login.php');
    }
    
    // Check if user has staff access
    if (!hasStaffAccess()) {
        $reason = 'Insufficient permissions';
        $userRole = $_SESSION['role'] ?? '';
        
        if ($userRole === 'viewer') {
            $reason = 'Viewer role has limited access';
        } elseif (empty($userRole)) {
            $reason = 'No role assigned';
        }
        
        logAccessAttempt(false, $reason);
        
        // Redirect based on user role
        switch ($userRole) {
            case 'viewer':
                redirectTo(BASE_URL . '/monitor/display.php');
                break;
            default:
                // Unknown or invalid role, logout and redirect to login
                session_destroy();
                redirectTo(BASE_URL . '/staff/login.php?error=invalid_role');
        }
    }
    
    // Check working hours for staff (not admin)
    if (!isWithinWorkingHoursCheck()) {
        logAccessAttempt(false, 'Outside working hours');
        
        // Show working hours message
        ?>
        <!DOCTYPE html>
        <html lang="th">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>นอกเวลาทำการ - <?php echo getAppName(); ?></title>
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
                                <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                                <h4>นอกเวลาทำการ</h4>
                                <p class="text-muted">
                                    เวลาทำการ: <?php echo getWorkingHoursStart(); ?> - <?php echo getWorkingHoursEnd(); ?><br>
                                    เวลาปัจจุบัน: <?php echo date('H:i'); ?>
                                </p>
                                <a href="logout.php" class="btn btn-secondary">
                                    <i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ
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
    
    // User has staff access, log successful access
    logAccessAttempt(true);
    
    // Get appropriate dashboard URL
    $dashboardUrl = getStaffDashboardUrl();
    
    // Redirect to dashboard
    redirectTo($dashboardUrl);
    
} catch (Exception $e) {
    // Log error
    Logger::error('Staff index error: ' . $e->getMessage(), [
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
