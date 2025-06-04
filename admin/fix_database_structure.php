<?php
session_start();
require_once '../config/config.php';

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['staff_id']) || $_SESSION['role_name'] !== 'Admin') {
    header('Location: ../staff/login.php');
    exit;
}

$message = '';
$error = '';

if ($_POST['action'] ?? '' === 'fix_structure') {
    try {
        $db = getDB();
        
        // แก้ไขโครงสร้างฐานข้อมูล
        $fixes = [
            "ALTER TABLE service_points ADD COLUMN IF NOT EXISTS display_order INT DEFAULT 0",
            "ALTER TABLE service_points ADD COLUMN IF NOT EXISTS queue_type_id INT NULL",
            "UPDATE service_points SET display_order = 1 WHERE position_key = 'SCREENING_01' AND display_order = 0",
            "UPDATE service_points SET display_order = 2 WHERE position_key = 'DOCTOR_01' AND display_order = 0",
            "UPDATE service_points SET display_order = 3 WHERE position_key = 'DOCTOR_02' AND display_order = 0",
            "UPDATE service_points SET display_order = 4 WHERE position_key = 'PHARMACY_01' AND display_order = 0",
            "UPDATE service_points SET display_order = 5 WHERE position_key = 'CASHIER_01' AND display_order = 0",
            "UPDATE service_points SET display_order = 6 WHERE position_key = 'RECORDS_01' AND display_order = 0",
            "CREATE INDEX IF NOT EXISTS idx_service_points_display_order ON service_points(display_order)"
        ];
        
        foreach ($fixes as $sql) {
            try {
                $db->exec($sql);
            } catch (Exception $e) {
                // ข้าม error ที่เกิดจากคอลัมน์หรือ index ที่มีอยู่แล้ว
                if (strpos($e->getMessage(), 'Duplicate') === false) {
                    throw $e;
                }
            }
        }
        
        $message = 'แก้ไขโครงสร้างฐานข้อมูลเรียบร้อยแล้ว';
        
    } catch (Exception $e) {
        $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขโครงสร้างฐานข้อมูล - โรงพยาบาลยุวประสาทไวทโยปถัมภ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        .navbar-brand { font-weight: 600; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-hospital me-2"></i>
                โรงพยาบาลยุวประสาทไวทโยปถัมภ์
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-database me-2"></i>
                            แก้ไขโครงสร้างฐานข้อมูล
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6>ตรวจสอบโครงสร้างฐานข้อมูล</h6>
                                <div id="validation-result" class="mb-3">
                                    <button class="btn btn-info" onclick="validateStructure()">
                                        <i class="fas fa-search me-2"></i>ตรวจสอบ
                                    </button>
                                </div>
                                
                                <div id="validation-details"></div>
                            </div>
                            
                            <div class="col-md-6">
                                <h6>แก้ไขปัญหา</h6>
                                <form method="POST">
                                    <input type="hidden" name="action" value="fix_structure">
                                    <button type="submit" class="btn btn-warning" onclick="return confirm('คุณต้องการแก้ไขโครงสร้างฐานข้อมูลหรือไม่?')">
                                        <i class="fas fa-wrench me-2"></i>แก้ไขโครงสร้าง
                                    </button>
                                </form>
                                
                                <div class="mt-3">
                                    <small class="text-muted">
                                        การแก้ไขจะทำการ:
                                        <ul>
                                            <li>เพิ่มคอลัมน์ display_order</li>
                                            <li>กำหนดค่า display_order ให้ service points</li>
                                            <li>เพิ่ม index สำหรับประสิทธิภาพ</li>
                                        </ul>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validateStructure() {
            fetch('../api/validate_database_structure.php')
                .then(response => response.json())
                .then(data => {
                    const resultDiv = document.getElementById('validation-details');
                    
                    if (data.success) {
                        resultDiv.innerHTML = `
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                ${data.message}
                            </div>
                        `;
                    } else {
                        let issuesHtml = '<div class="alert alert-warning"><h6>พบปัญหา:</h6><ul>';
                        data.issues.forEach(issue => {
                            issuesHtml += `<li>${issue}</li>`;
                        });
                        issuesHtml += '</ul>';
                        
                        if (data.fixes && data.fixes.length > 0) {
                            issuesHtml += '<h6>การแก้ไขที่แนะนำ:</h6><ul>';
                            data.fixes.forEach(fix => {
                                issuesHtml += `<li><code>${fix}</code></li>`;
                            });
                            issuesHtml += '</ul>';
                        }
                        
                        issuesHtml += '</div>';
                        resultDiv.innerHTML = issuesHtml;
                    }
                })
                .catch(error => {
                    document.getElementById('validation-details').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            เกิดข้อผิดพลาด: ${error.message}
                        </div>
                    `;
                });
        }
        
        // ตรวจสอบอัตโนมัติเมื่อโหลดหน้า
        validateStructure();
    </script>
</body>
</html>
