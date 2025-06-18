-- แก้ไขโครงสร้างตาราง notifications
-- ตรวจสอบและเพิ่มคอลัมน์ที่จำเป็น

-- ตรวจสอบโครงสร้างตารางปัจจุบัน
DESCRIBE notifications;

-- เพิ่มคอลัมน์ที่อาจจะขาดหายไป
ALTER TABLE notifications 
ADD COLUMN IF NOT EXISTS is_public TINYINT(1) DEFAULT 0 AFTER priority,
ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1 AFTER is_public,
ADD COLUMN IF NOT EXISTS expires_at DATETIME NULL AFTER is_active,
ADD COLUMN IF NOT EXISTS auto_dismiss_after INT DEFAULT 5000 AFTER expires_at;

-- อัปเดตข้อมูลเดิมให้เป็น public สำหรับ monitor
UPDATE notifications 
SET is_public = 1, is_active = 1 
WHERE notification_type IN ('queue_called', 'announcement', 'system_alert', 'emergency');

-- สร้างดัชนีเพื่อเพิ่มประสิทธิภาพ
CREATE INDEX IF NOT EXISTS idx_notifications_public ON notifications(is_public, is_active, created_at);
CREATE INDEX IF NOT EXISTS idx_notifications_type ON notifications(notification_type, created_at);
CREATE INDEX IF NOT EXISTS idx_notifications_service_point ON notifications(service_point_id, created_at);

-- ตรวจสอบและสร้างตาราง notification_types หากไม่มี
CREATE TABLE IF NOT EXISTS notification_types (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    type_code VARCHAR(50) UNIQUE NOT NULL,
    type_name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT 'fas fa-bell',
    color VARCHAR(7) DEFAULT '#007bff',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- เพิ่มข้อมูลประเภทการแจ้งเตือนเริ่มต้น
INSERT IGNORE INTO notification_types (type_code, type_name, icon, color) VALUES
('queue_called', 'เรียกคิว', 'fas fa-bullhorn', '#28a745'),
('announcement', 'ประกาศ', 'fas fa-megaphone', '#17a2b8'),
('system_alert', 'แจ้งเตือนระบบ', 'fas fa-exclamation-triangle', '#ffc107'),
('emergency', 'เหตุฉุกเฉิน', 'fas fa-exclamation-circle', '#dc3545'),
('maintenance', 'บำรุงรักษา', 'fas fa-tools', '#6c757d'),
('info', 'ข้อมูล', 'fas fa-info-circle', '#007bff');

-- ตรวจสอบข้อมูลที่สร้าง
SELECT 'Notifications table structure:' as info;
DESCRIBE notifications;

SELECT 'Sample notifications:' as info;
SELECT notification_id, notification_type, title, is_public, is_active, created_at 
FROM notifications 
ORDER BY created_at DESC 
LIMIT 5;

SELECT 'Notification types:' as info;
SELECT * FROM notification_types;
