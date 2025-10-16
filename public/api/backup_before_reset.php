<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

function createBackupBeforeReset($resetType = 'all', $targetId = null) {
    try {
        $db = getDB();
        $backupData = [];
        $timestamp = date('Y-m-d H:i:s');
        
        // สร้างชื่อไฟล์ backup
        $backupFileName = 'queue_backup_' . date('Y-m-d_H-i-s') . '_' . $resetType;
        if ($targetId) {
            $backupFileName .= '_' . $targetId;
        }
        $backupFileName .= '.json';
        
        // ดึงข้อมูลปัจจุบันของคิว
        switch ($resetType) {
            case 'all':
                $stmt = $db->prepare("
                    SELECT qt.*, 
                           COUNT(q.queue_id) as active_queues,
                           MAX(q.queue_number) as last_queue_number
                    FROM queue_types qt
                    LEFT JOIN queues q ON qt.queue_type_id = q.queue_type_id 
                                       AND DATE(q.created_at) = CURDATE()
                                       AND q.status IN ('waiting', 'called', 'serving')
                    WHERE qt.is_active = 1
                    GROUP BY qt.queue_type_id
                ");
                $stmt->execute();
                break;
                
            case 'by_type':
                $stmt = $db->prepare("
                    SELECT qt.*, 
                           COUNT(q.queue_id) as active_queues,
                           MAX(q.queue_number) as last_queue_number
                    FROM queue_types qt
                    LEFT JOIN queues q ON qt.queue_type_id = q.queue_type_id 
                                       AND DATE(q.created_at) = CURDATE()
                                       AND q.status IN ('waiting', 'called', 'serving')
                    WHERE qt.queue_type_id = ? AND qt.is_active = 1
                    GROUP BY qt.queue_type_id
                ");
                $stmt->execute([$targetId]);
                break;
                
            case 'by_service_point':
                $stmt = $db->prepare("
                    SELECT qt.*, 
                           COUNT(q.queue_id) as active_queues,
                           MAX(q.queue_number) as last_queue_number
                    FROM queue_types qt
                    JOIN queue_type_service_points qtsp ON qt.queue_type_id = qtsp.queue_type_id
                    LEFT JOIN queues q ON qt.queue_type_id = q.queue_type_id 
                                       AND DATE(q.created_at) = CURDATE()
                                       AND q.status IN ('waiting', 'called', 'serving')
                    WHERE qtsp.service_point_id = ? AND qt.is_active = 1
                    GROUP BY qt.queue_type_id
                ");
                $stmt->execute([$targetId]);
                break;
        }
        
        $queueTypes = $stmt->fetchAll();
        
        // ดึงข้อมูลคิวที่ยังไม่เสร็จ
        $activeQueues = [];
        foreach ($queueTypes as $type) {
            $stmt = $db->prepare("
                SELECT q.*, sp.point_name
                FROM queues q
                LEFT JOIN service_points sp ON q.current_service_point_id = sp.service_point_id
                WHERE q.queue_type_id = ? 
                AND DATE(q.created_at) = CURDATE()
                AND q.status IN ('waiting', 'called', 'serving')
                ORDER BY q.queue_number
            ");
            $stmt->execute([$type['queue_type_id']]);
            $activeQueues[$type['queue_type_id']] = $stmt->fetchAll();
        }
        
        // สร้างข้อมูล backup
        $backupData = [
            'backup_info' => [
                'created_at' => $timestamp,
                'reset_type' => $resetType,
                'target_id' => $targetId,
                'created_by' => $_SESSION['staff_id'] ?? null,
                'backup_file' => $backupFileName
            ],
            'queue_types' => $queueTypes,
            'active_queues' => $activeQueues,
            'system_info' => [
                'php_version' => PHP_VERSION,
                'mysql_version' => $db->getAttribute(PDO::ATTR_SERVER_VERSION),
                'backup_size' => 0 // จะคำนวณหลังจากสร้างไฟล์
            ]
        ];
        
        // สร้างโฟลเดอร์ backup ถ้ายังไม่มี
        $backupDir = ROOT_PATH . '/backups/auto_reset';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // บันทึกไฟล์ backup
        $backupPath = $backupDir . '/' . $backupFileName;
        $jsonData = json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($backupPath, $jsonData);
        
        // อัปเดตขนาดไฟล์
        $backupData['system_info']['backup_size'] = filesize($backupPath);
        file_put_contents($backupPath, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // บันทึกข้อมูล backup ในฐานข้อมูล
        $stmt = $db->prepare("
            INSERT INTO backup_logs 
            (backup_type, backup_file, file_size, reset_type, target_id, created_by, backup_data)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'auto_reset',
            $backupFileName,
            filesize($backupPath),
            $resetType,
            $targetId,
            $_SESSION['staff_id'] ?? null,
            json_encode(['queue_types_count' => count($queueTypes), 'active_queues_count' => array_sum(array_map('count', $activeQueues))])
        ]);
        
        // ลบไฟล์ backup เก่าที่เกิน 30 วัน
        cleanOldBackups($backupDir);
        
        return [
            'success' => true,
            'backup_file' => $backupFileName,
            'backup_path' => $backupPath,
            'queue_types_count' => count($queueTypes),
            'active_queues_count' => array_sum(array_map('count', $activeQueues)),
            'file_size' => filesize($backupPath)
        ];
        
    } catch (Exception $e) {
        error_log("Backup before reset error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

function cleanOldBackups($backupDir) {
    try {
        $files = glob($backupDir . '/queue_backup_*.json');
        $cutoffTime = time() - (30 * 24 * 60 * 60); // 30 วันที่แล้ว
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    } catch (Exception $e) {
        error_log("Clean old backups error: " . $e->getMessage());
    }
}

function restoreFromBackup($backupFile) {
    try {
        $db = getDB();
        $backupDir = ROOT_PATH . '/backups/auto_reset';
        $backupPath = $backupDir . '/' . $backupFile;
        
        if (!file_exists($backupPath)) {
            throw new Exception("ไม่พบไฟล์ backup: " . $backupFile);
        }
        
        $backupData = json_decode(file_get_contents($backupPath), true);
        if (!$backupData) {
            throw new Exception("ไฟล์ backup เสียหาย");
        }
        
        $db->beginTransaction();
        
        // Restore queue types current numbers
        foreach ($backupData['queue_types'] as $type) {
            $stmt = $db->prepare("
                UPDATE queue_types 
                SET current_number = ?,
                    last_reset_date = ?,
                    last_reset_by = ?,
                    last_reset_type = 'restore'
                WHERE queue_type_id = ?
            ");
            $stmt->execute([
                $type['last_queue_number'] ?? $type['current_number'] ?? 0,
                $backupData['backup_info']['created_at'],
                $_SESSION['staff_id'] ?? null,
                $type['queue_type_id']
            ]);
        }
        
        $db->commit();
        
        // Log การ restore
        logActivity("Restore จากไฟล์ backup: " . $backupFile);
        
        return [
            'success' => true,
            'message' => 'Restore สำเร็จ',
            'restored_types' => count($backupData['queue_types'])
        ];
        
    } catch (Exception $e) {
        if (isset($db)) {
            $db->rollBack();
        }
        
        error_log("Restore from backup error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// เพิ่มตารางสำหรับ backup logs ถ้ายังไม่มี
function createBackupLogsTable() {
    try {
        $db = getDB();
        $db->exec("
            CREATE TABLE IF NOT EXISTS backup_logs (
                backup_id INT PRIMARY KEY AUTO_INCREMENT,
                backup_type ENUM('manual', 'auto_reset', 'scheduled') NOT NULL,
                backup_file VARCHAR(255) NOT NULL,
                file_size BIGINT DEFAULT 0,
                reset_type ENUM('all', 'by_type', 'by_service_point') NULL,
                target_id INT NULL,
                backup_data JSON NULL,
                created_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES staff_users(staff_id)
            )
        ");
    } catch (Exception $e) {
        error_log("Create backup logs table error: " . $e->getMessage());
    }
}

// สร้างตารางเมื่อโหลดไฟล์
createBackupLogsTable();
?>
