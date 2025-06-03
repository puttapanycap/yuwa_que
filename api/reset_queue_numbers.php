<?php
require_once '../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// ตรวจสอบการเข้าสู่ระบบและสิทธิ์
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'ไม่ได้เข้าสู่ระบบ']);
    exit;
}

if (!hasPermission('manage_queues')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ในการจัดการคิว']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // รับข้อมูลจาก POST
    $input = json_decode(file_get_contents('php://input'), true);
    $resetType = $input['reset_type'] ?? 'all'; // 'all', 'by_type', 'by_service_point'
    $queueTypeId = $input['queue_type_id'] ?? null;
    $servicePointId = $input['service_point_id'] ?? null;
    
    $resetCount = 0;
    $affectedTypes = [];
    
    if ($resetType === 'all') {
        // Reset คิวทุกประเภท
        $stmt = $db->prepare("
            UPDATE queue_types 
            SET current_number = 0, 
                last_reset_date = NOW(),
                last_reset_by = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $resetCount = $stmt->rowCount();
        
        // ดึงรายชื่อประเภทคิวที่ reset
        $stmt = $db->prepare("SELECT type_name FROM queue_types WHERE is_active = 1");
        $stmt->execute();
        $affectedTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
    } elseif ($resetType === 'by_type' && $queueTypeId) {
        // Reset คิวประเภทเดียว
        $stmt = $db->prepare("
            UPDATE queue_types 
            SET current_number = 0,
                last_reset_date = NOW(),
                last_reset_by = ?
            WHERE queue_type_id = ? AND is_active = 1
        ");
        $stmt->execute([$_SESSION['user_id'], $queueTypeId]);
        $resetCount = $stmt->rowCount();
        
        if ($resetCount > 0) {
            $stmt = $db->prepare("SELECT type_name FROM queue_types WHERE queue_type_id = ?");
            $stmt->execute([$queueTypeId]);
            $affectedTypes = [$stmt->fetchColumn()];
        }
        
    } elseif ($resetType === 'by_service_point' && $servicePointId) {
        // Reset คิวตามจุดบริการ (ประเภทคิวที่ใช้จุดบริการนั้น)
        $stmt = $db->prepare("
            UPDATE queue_types qt
            JOIN queue_type_service_points qtsp ON qt.queue_type_id = qtsp.queue_type_id
            SET qt.current_number = 0,
                qt.last_reset_date = NOW(),
                qt.last_reset_by = ?
            WHERE qtsp.service_point_id = ? AND qt.is_active = 1
        ");
        $stmt->execute([$_SESSION['user_id'], $servicePointId]);
        $resetCount = $stmt->rowCount();
        
        if ($resetCount > 0) {
            $stmt = $db->prepare("
                SELECT DISTINCT qt.type_name 
                FROM queue_types qt
                JOIN queue_type_service_points qtsp ON qt.queue_type_id = qtsp.queue_type_id
                WHERE qtsp.service_point_id = ? AND qt.is_active = 1
            ");
            $stmt->execute([$servicePointId]);
            $affectedTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    }
    
    // บันทึก audit log
    if ($resetCount > 0) {
        $logMessage = "Reset หมายเลขคิว: " . implode(', ', $affectedTypes);
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent)
            VALUES (?, 'reset_queue_numbers', 'queue_types', NULL, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            json_encode(['reset_type' => $resetType, 'affected_types' => $affectedTypes]),
            json_encode(['current_number' => 0, 'reset_date' => date('Y-m-d H:i:s')]),
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Reset หมายเลขคิวสำเร็จ จำนวน {$resetCount} ประเภท",
        'reset_count' => $resetCount,
        'affected_types' => $affectedTypes,
        'reset_type' => $resetType,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Reset queue numbers error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการ reset หมายเลขคิว: ' . $e->getMessage()
    ]);
}
?>
