<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

if (isLoggedIn()) {
    logActivity("ออกจากระบบ");
}

session_destroy();
redirectTo(BASE_URL . '/staff/login.php');
?>
