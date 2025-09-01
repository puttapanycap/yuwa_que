<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$navItems = [
    ['dashboard.php', 'fas fa-tachometer-alt', 'แดชบอร์ด'],
    ['users.php', 'fas fa-users', 'จัดการผู้ใช้'],
    ['roles.php', 'fas fa-user-tag', 'บทบาทและสิทธิ์'],
    ['service_points.php', 'fas fa-map-marker-alt', getServicePointLabel()],
    ['queue_types.php', 'fas fa-list', 'ประเภทคิว'],
    ['service_flows.php', 'fas fa-route', 'Service Flows'],
    ['queue_management.php', 'fas fa-tasks', 'จัดการคิว'],
    ['settings.php', 'fas fa-cog', 'การตั้งค่า'],
    ['environment_settings.php', 'fas fa-server', 'Environment'],
    ['audio_settings.php', 'fas fa-volume-up', 'ระบบเสียงเรียกคิว'],
    ['auto_reset_settings.php', 'fas fa-clock', 'Auto Reset'],
    ['auto_reset_logs.php', 'fas fa-history', 'ประวัติ Auto Reset'],
    ['backup_management.php', 'fas fa-database', 'จัดการ Backup'],
    ['reports.php', 'fas fa-chart-bar', 'รายงาน'],
    ['audit_logs.php', 'fas fa-history', 'บันทึกการใช้งาน'],
];
?>
<nav class="nav flex-column">
    <?php foreach ($navItems as $item):
        [$href, $icon, $label] = $item;
        $active = $currentPage === $href ? 'active' : '';
    ?>
        <a class="nav-link <?php echo $active; ?>" href="<?php echo $href; ?>">
            <i class="<?php echo $icon; ?>"></i><?php echo $label; ?>
        </a>
    <?php endforeach; ?>
    <hr class="my-3" style="border-color: rgba(255,255,255,0.3);">
    <a class="nav-link" href="../staff/dashboard.php">
        <i class="fas fa-arrow-left"></i>กลับหน้าเจ้าหน้าที่
    </a>
    <a class="nav-link" href="../staff/logout.php">
        <i class="fas fa-sign-out-alt"></i>ออกจากระบบ
    </a>
</nav>

