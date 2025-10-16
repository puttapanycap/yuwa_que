<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// สำหรับ Cron Job - ไม่ต้องตรวจสอบ session
$isCronJob = isset($_GET['cron']) && $_GET['cron'] === 'true';

if (!$isCronJob) {
    // ตรวจสอบการเข้าสู่ระบบสำหรับการเรียกใช้ปกติ
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
}

try {
    $db = getDB();
    $currentTime = date('H:i:s');
    $currentDay = date('N'); // 1=จันทร์, 7=อาทิตย์
    $currentDate = date('Y-m-d');
    
    // ตรวจสอบว่าเปิดใช้งาน Auto Reset หรือไม่
    $autoResetEnabled = getSetting('auto_reset_enabled', '0');
    if ($autoResetEnabled !== '1') {
        echo json_encode([
            'success' => false, 
            'message' => 'ระบบ Auto Reset ถูกปิดใช้งาน',
            'schedules_checked' => 0
        ]);
        exit;
    }
    
    // ดึงรายการ Schedule ที่ต้องรัน
    $stmt = $db->prepare("
        SELECT * FROM auto_reset_schedules 
        WHERE is_active = 1 
        AND TIME(schedule_time) <= ? 
        AND FIND_IN_SET(?, schedule_days) > 0
        AND (last_run_date IS NULL OR last_run_date < ?)
        ORDER BY schedule_time ASC
    ");
    $stmt->execute([$currentTime, $currentDay, $currentDate]);
    $schedules = $stmt->fetchAll();
    
    $results = [];
    $totalProcessed = 0;
    $totalSuccess = 0;
    $totalFailed = 0;
    
    foreach ($schedules as $schedule) {
        $startTime = microtime(true);
        $resetResult = executeAutoReset($db, $schedule);
        $executionTime = microtime(true) - $startTime;
        
        // บันทึก log
        logAutoReset($db, $schedule, $resetResult, $executionTime);
        
        // อัปเดต schedule
        updateScheduleStatus($db, $schedule['schedule_id'], $resetResult['status']);
        
        $results[] = [
            'schedule_id' => $schedule['schedule_id'],
            'schedule_name' => $schedule['schedule_name'],
            'status' => $resetResult['status'],
            'message' => $resetResult['message'],
            'reset_count' => $resetResult['reset_count'] ?? 0,
            'execution_time' => round($executionTime, 3)
        ];
        
        $totalProcessed++;
        if ($resetResult['status'] === 'success') {
            $totalSuccess++;
        } else {
            $totalFailed++;
        }
        
        // ส่งการแจ้งเตือนถ้าเปิดใช้งาน
        if (getSetting('auto_reset_notification', '1') === '1') {
            sendAutoResetNotification($schedule, $resetResult);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "ประมวลผล Auto Reset เสร็จสิ้น",
        'summary' => [
            'total_processed' => $totalProcessed,
            'total_success' => $totalSuccess,
            'total_failed' => $totalFailed,
            'execution_time' => date('Y-m-d H:i:s')
        ],
        'results' => $results
    ]);
    
} catch (Exception $e) {
    error_log("Auto Reset error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการ Auto Reset: ' . $e->getMessage()
    ]);
}

function executeAutoReset($db, $schedule) {
    try {
        $db->beginTransaction();
        
        $resetCount = 0;
        $affectedTypes = [];
        
        switch ($schedule['reset_type']) {
            case 'all':
                // Reset คิวทุกประเภท
                $stmt = $db->prepare("
                    UPDATE queue_types 
                    SET current_number = 0, 
                        last_reset_date = NOW(),
                        last_reset_by = ?,
                        last_reset_type = 'auto'
                    WHERE is_active = 1
                ");
                $stmt->execute([$schedule['created_by']]);
                $resetCount = $stmt->rowCount();
                
                // ดึงรายชื่อประเภทคิวที่ reset
                $stmt = $db->prepare("SELECT type_name FROM queue_types WHERE is_active = 1");
                $stmt->execute();
                $affectedTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
                break;
                
            case 'by_type':
                // Reset คิวประเภทเดียว
                $stmt = $db->prepare("
                    UPDATE queue_types 
                    SET current_number = 0,
                        last_reset_date = NOW(),
                        last_reset_by = ?,
                        last_reset_type = 'auto'
                    WHERE queue_type_id = ? AND is_active = 1
                ");
                $stmt->execute([$schedule['created_by'], $schedule['target_id']]);
                $resetCount = $stmt->rowCount();
                
                if ($resetCount > 0) {
                    $stmt = $db->prepare("SELECT type_name FROM queue_types WHERE queue_type_id = ?");
                    $stmt->execute([$schedule['target_id']]);
                    $affectedTypes = [$stmt->fetchColumn()];
                }
                break;
                
            case 'by_service_point':
                // Reset คิวตามจุดบริการ
                $stmt = $db->prepare("
                    UPDATE queue_types qt
                    JOIN queue_type_service_points qtsp ON qt.queue_type_id = qtsp.queue_type_id
                    SET qt.current_number = 0,
                        qt.last_reset_date = NOW(),
                        qt.last_reset_by = ?,
                        qt.last_reset_type = 'auto'
                    WHERE qtsp.service_point_id = ? AND qt.is_active = 1
                ");
                $stmt->execute([$schedule['created_by'], $schedule['target_id']]);
                $resetCount = $stmt->rowCount();
                
                if ($resetCount > 0) {
                    $stmt = $db->prepare("
                        SELECT DISTINCT qt.type_name 
                        FROM queue_types qt
                        JOIN queue_type_service_points qtsp ON qt.queue_type_id = qtsp.queue_type_id
                        WHERE qtsp.service_point_id = ? AND qt.is_active = 1
                    ");
                    $stmt->execute([$schedule['target_id']]);
                    $affectedTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
                }
                break;
        }
        
        $db->commit();
        
        return [
            'status' => 'success',
            'message' => "Reset สำเร็จ จำนวน {$resetCount} ประเภท",
            'reset_count' => $resetCount,
            'affected_types' => $affectedTypes
        ];
        
    } catch (Exception $e) {
        $db->rollBack();
        return [
            'status' => 'failed',
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage(),
            'reset_count' => 0,
            'affected_types' => []
        ];
    }
}

function logAutoReset($db, $schedule, $result, $executionTime) {
    try {
        $stmt = $db->prepare("
            INSERT INTO auto_reset_logs 
            (schedule_id, reset_type, target_id, reset_count, affected_types, status, error_message, execution_time)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $schedule['schedule_id'],
            $schedule['reset_type'],
            $schedule['target_id'],
            $result['reset_count'] ?? 0,
            json_encode($result['affected_types'] ?? []),
            $result['status'],
            $result['status'] === 'failed' ? $result['message'] : null,
            $executionTime
        ]);
    } catch (Exception $e) {
        error_log("Failed to log auto reset: " . $e->getMessage());
    }
}

function updateScheduleStatus($db, $scheduleId, $status) {
    try {
        $stmt = $db->prepare("
            UPDATE auto_reset_schedules 
            SET last_run_date = CURDATE(), 
                last_run_status = ?
            WHERE schedule_id = ?
        ");
        $stmt->execute([$status, $scheduleId]);
    } catch (Exception $e) {
        error_log("Failed to update schedule status: " . $e->getMessage());
    }
}

function sendAutoResetNotification($schedule, $result) {
    // ส่งการแจ้งเตือนผ่าน email หรือ system notification
    // สามารถเพิ่มการส่ง LINE Notify, Email, หรือ Push Notification ได้
    
    $message = "Auto Reset: {$schedule['schedule_name']} - {$result['status']}";
    if ($result['status'] === 'success') {
        $message .= " (Reset {$result['reset_count']} ประเภท)";
    } else {
        $message .= " - {$result['message']}";
    }
    
    // บันทึกใน audit log
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO audit_logs (staff_id, action_description, ip_address, user_agent)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $schedule['created_by'],
            $message,
            'AUTO_RESET_SYSTEM',
            'Auto Reset Scheduler'
        ]);
    } catch (Exception $e) {
        error_log("Failed to log auto reset notification: " . $e->getMessage());
    }
}
?>
