<?php
require_once '../config/config.php';
requireLogin();

if (!hasPermission('manage_service_points')) {
    die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
}

$servicePointLabel = getServicePointLabel();
$baseUrl = rtrim(BASE_URL, '/');

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM service_points ORDER BY display_order, point_name");
    $stmt->execute();
    $servicePoints = $stmt->fetchAll();
} catch (Exception $e) {
    $servicePoints = [];
}

$activeServicePoints = array_filter($servicePoints, fn($sp) => (int)$sp['is_active'] === 1);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าจอเรียกคิว - <?php echo getAppName(); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f8f9fa;
        }

        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 0;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 1rem 1.5rem;
            border-radius: 0;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }

        .content-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .link-box {
            background-color: #f8f9fa;
            border: 1px dashed #ced4da;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            font-size: 0.95rem;
            word-break: break-all;
        }

        .disabled-state {
            opacity: 0.65;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="p-3">
                    <h5 class="text-white mb-4">
                        <i class="fas fa-cogs me-2"></i>
                        จัดการระบบ
                    </h5>

                    <?php include 'nav.php'; ?>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2>หน้าจอเรียกคิว</h2>
                            <p class="text-muted">กำหนดลิงก์หน้าจอแสดงคิวสำหรับ<?php echo $servicePointLabel; ?>แต่ละรูปแบบ</p>
                        </div>
                        <a href="../monitor/display.php" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-desktop me-2"></i>เปิดหน้าจอมาตรฐาน
                        </a>
                    </div>

                    <div class="content-card">
                        <h5 class="mb-4"><i class="fas fa-square me-2 text-primary"></i>หน้าจอเรียกคิว 1 <?php echo $servicePointLabel; ?></h5>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th><?php echo $servicePointLabel; ?></th>
                                        <th>URL</th>
                                        <th style="width: 130px;">การดำเนินการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>ทุก<?php echo $servicePointLabel; ?></td>
                                        <td>
                                            <div class="link-box mb-0">
                                                <code><?php echo $baseUrl; ?>/monitor/display.php</code>
                                            </div>
                                        </td>
                                        <td>
                                            <a href="../monitor/display.php" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-external-link-alt me-1"></i>เปิด
                                            </a>
                                        </td>
                                    </tr>
                                    <?php foreach ($activeServicePoints as $sp): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(trim(($sp['point_label'] ? $sp['point_label'].' ' : '') . $sp['point_name'])); ?></td>
                                            <td>
                                                <div class="link-box mb-0">
                                                    <code><?php echo $baseUrl; ?>/monitor/display.php?service_point=<?php echo $sp['service_point_id']; ?></code>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="../monitor/display.php?service_point=<?php echo $sp['service_point_id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-external-link-alt me-1"></i>เปิด
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="content-card">
                        <h5 class="mb-3"><i class="fas fa-columns me-2 text-success"></i>หน้าจอเรียกคิว 2 <?php echo $servicePointLabel; ?></h5>
                        <p class="text-muted mb-4">
                            เลือก<?php echo $servicePointLabel; ?>สำหรับฝั่งซ้ายและฝั่งขวา ระบบจะสร้างลิงก์หน้าจอให้โดยอัตโนมัติ
                        </p>

                        <?php if (count($activeServicePoints) < 2): ?>
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-info-circle me-2"></i>ต้องมี<?php echo $servicePointLabel; ?>ที่เปิดใช้งานอย่างน้อย 2 รายการเพื่อใช้หน้าจอแบบ 2 จุดบริการ
                            </div>
                        <?php else: ?>
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label">เลือก<?php echo $servicePointLabel; ?>ฝั่งซ้าย *</label>
                                    <select class="form-select" id="leftServicePoint">
                                        <option value="">- เลือก<?php echo $servicePointLabel; ?> -</option>
                                        <?php foreach ($activeServicePoints as $sp): ?>
                                            <option value="<?php echo $sp['service_point_id']; ?>">
                                                <?php echo htmlspecialchars(trim(($sp['point_label'] ? $sp['point_label'].' ' : '') . $sp['point_name'])); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">เลือก<?php echo $servicePointLabel; ?>ฝั่งขวา *</label>
                                    <select class="form-select" id="rightServicePoint">
                                        <option value="">- เลือก<?php echo $servicePointLabel; ?> -</option>
                                        <?php foreach ($activeServicePoints as $sp): ?>
                                            <option value="<?php echo $sp['service_point_id']; ?>">
                                                <?php echo htmlspecialchars(trim(($sp['point_label'] ? $sp['point_label'].' ' : '') . $sp['point_name'])); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">ลิงก์หน้าจอ</label>
                                    <div class="link-box" id="dualScreenLink">
                                        <code><?php echo $baseUrl; ?>/monitor/display.php?layout=dual&left=&right=</code>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="text-danger small mb-2" id="dualScreenWarning" style="display: none;"></div>
                                    <a id="openDualScreen" class="btn btn-primary disabled" target="_blank" rel="noopener">
                                        <i class="fas fa-external-link-alt me-2"></i>เปิดหน้าจอ 2 จุดบริการ
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const baseDisplayUrl = <?php echo json_encode($baseUrl . '/monitor/display.php'); ?>;

        function updateDualScreenLink() {
            const left = $('#leftServicePoint').val();
            const right = $('#rightServicePoint').val();
            const warning = $('#dualScreenWarning');
            const linkBox = $('#dualScreenLink code');
            const openButton = $('#openDualScreen');

            warning.hide().text('');
            let linkText = `${baseDisplayUrl}?layout=dual&left=${left || ''}&right=${right || ''}`;
            linkBox.text(linkText);

            if (!left || !right) {
                openButton.addClass('disabled').removeAttr('href');
                return;
            }

            if (left === right) {
                warning.text('กรุณาเลือก' + <?php echo json_encode($servicePointLabel); ?> + 'ที่ต่างกันสำหรับฝั่งซ้ายและขวา').show();
                openButton.addClass('disabled').removeAttr('href');
                return;
            }

            openButton.removeClass('disabled').attr('href', `../monitor/display.php?layout=dual&left=${left}&right=${right}`);
        }

        $('#leftServicePoint, #rightServicePoint').on('change', updateDualScreenLink);
    </script>
</body>
</html>
