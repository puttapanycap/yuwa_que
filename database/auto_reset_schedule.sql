-- เพิ่มตารางสำหรับจัดการ Auto Reset Schedule
CREATE TABLE auto_reset_schedules (
    schedule_id INT PRIMARY KEY AUTO_INCREMENT,
    schedule_name VARCHAR(100) NOT NULL,
    reset_type ENUM('all', 'by_type', 'by_service_point') NOT NULL DEFAULT 'all',
    target_id INT NULL, -- queue_type_id หรือ service_point_id
    schedule_time TIME NOT NULL, -- เวลาที่จะ reset
    schedule_days VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5,6,7', -- วันในสัปดาห์ (1=จันทร์, 7=อาทิตย์)
    is_active BOOLEAN DEFAULT TRUE,
    last_run_date DATE NULL,
    last_run_status ENUM('success', 'failed', 'skipped') NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES staff_users(staff_id)
);

-- เพิ่มตารางบันทึกการ Reset อัตโนมัติ
CREATE TABLE auto_reset_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    schedule_id INT,
    reset_type ENUM('all', 'by_type', 'by_service_point') NOT NULL,
    target_id INT NULL,
    reset_count INT DEFAULT 0,
    affected_types TEXT, -- JSON array ของประเภทคิวที่ถูก reset
    status ENUM('success', 'failed', 'skipped') NOT NULL,
    error_message TEXT NULL,
    execution_time DECIMAL(5,3) DEFAULT 0, -- เวลาที่ใช้ในการ execute (วินาที)
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (schedule_id) REFERENCES auto_reset_schedules(schedule_id) ON DELETE CASCADE
);

-- เพิ่มคอลัมน์ในตาราง queue_types สำหรับ auto reset
ALTER TABLE queue_types 
ADD COLUMN current_number INT DEFAULT 0,
ADD COLUMN last_reset_date TIMESTAMP NULL,
ADD COLUMN last_reset_by INT NULL,
ADD COLUMN last_reset_type ENUM('manual', 'auto') DEFAULT 'manual',
ADD FOREIGN KEY (last_reset_by) REFERENCES staff_users(staff_id);

-- เพิ่มการตั้งค่าระบบสำหรับ Auto Reset
INSERT INTO settings (setting_key, setting_value, description) VALUES
('auto_reset_enabled', '0', 'เปิดใช้งานระบบ Reset อัตโนมัติ'),
('auto_reset_notification', '1', 'ส่งการแจ้งเตือนเมื่อมีการ Reset อัตโนมัติ'),
('auto_reset_backup_before', '1', 'สำรองข้อมูลก่อน Reset อัตโนมัติ'),
('auto_reset_max_retries', '3', 'จำนวนครั้งสูงสุดในการลองใหม่เมื่อ Reset ล้มเหลว');

-- ตัวอย่างการตั้งค่า Auto Reset (Reset ทุกวันเวลา 00:00)
INSERT INTO auto_reset_schedules (schedule_name, reset_type, schedule_time, schedule_days, created_by) VALUES
('Reset รายวัน - เที่ยงคืน', 'all', '00:00:00', '1,2,3,4,5,6,7', 1),
('Reset คิวทั่วไป - เช้า', 'by_type', '06:00:00', '1,2,3,4,5', 1);
