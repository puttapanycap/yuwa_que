<?php
require_once '../config/config.php';

if (isLoggedIn()) {
    logActivity("ออกจากระบบ");
}

session_destroy();
redirectTo(BASE_URL . '/staff/login.php');
?>
