-- ตารางการแจ้งเตือน
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    notification_type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    icon VARCHAR(50) DEFAULT 'info-circle',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    link VARCHAR(255) NULL,
    is_system BOOLEAN DEFAULT FALSE,
    is_read BOOLEAN DEFAULT FALSE,
    is_dismissed BOOLEAN DEFAULT FALSE,
    recipient_id INT NULL,
    recipient_role VARCHAR(50) NULL,
    sender_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (recipient_id) REFERENCES staff_users(staff_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES staff_users(staff_id) ON DELETE SET NULL,
    INDEX idx_notification_recipient (recipient_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_notification_created (created_at)
);

-- ตารางการตั้งค่าการแจ้งเตือนของผู้ใช้
CREATE TABLE notification_preferences (
    preference_id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    email_enabled BOOLEAN DEFAULT FALSE,
    browser_enabled BOOLEAN DEFAULT TRUE,
    line_enabled BOOLEAN DEFAULT FALSE,
    sound_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff_users(staff_id) ON DELETE CASCADE,
    UNIQUE KEY unique_staff_notification_type (staff_id, notification_type)
);

-- ตารางประเภทการแจ้งเตือน
CREATE TABLE notification_types (
    type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_code VARCHAR(50) UNIQUE NOT NULL,
    type_name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'bell',
    default_priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    default_sound VARCHAR(100) DEFAULT 'notification.mp3',
    is_system BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางการส่งการแจ้งเตือนผ่านช่องทางต่างๆ
CREATE TABLE notification_deliveries (
    delivery_id INT PRIMARY KEY AUTO_INCREMENT,
    notification_id INT NOT NULL,
    channel ENUM('browser', 'email', 'line', 'sms') NOT NULL,
    status ENUM('pending', 'sent', 'failed', 'delivered', 'read') DEFAULT 'pending',
    error_message TEXT NULL,
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES notifications(notification_id) ON DELETE CASCADE,
    INDEX idx_delivery_status (status),
    INDEX idx_delivery_channel (channel)
);

-- ข้อมูลเริ่มต้นสำหรับประเภทการแจ้งเตือน
INSERT INTO notification_types (type_code, type_name, description, icon, default_priority, is_system) VALUES
('queue_called', 'เรียกคิว', 'แจ้งเตือนเมื่อมีการเรียกคิว', 'bullhorn', 'high', TRUE),
('queue_completed', 'คิวเสร็จสิ้น', 'แจ้งเตือนเมื่อคิวเสร็จสิ้น', 'check-circle', 'normal', TRUE),
('queue_forwarded', 'คิวถูกส่งต่อ', 'แจ้งเตือนเมื่อคิวถูกส่งต่อไปยังจุดบริการอื่น', 'arrow-right', 'normal', TRUE),
('queue_waiting_long', 'คิวรอนาน', 'แจ้งเตือนเมื่อมีคิวรอนานเกินกำหนด', 'clock', 'high', TRUE),
('system_alert', 'การแจ้งเตือนระบบ', 'แจ้งเตือนจากระบบ', 'exclamation-triangle', 'high', TRUE),
('auto_reset', 'Auto Reset', 'แจ้งเตือนเกี่ยวกับการ Reset คิวอัตโนมัติ', 'sync', 'normal', TRUE),
('staff_message', 'ข้อความจากเจ้าหน้าที่', 'ข้อความจากเจ้าหน้าที่คนอื่น', 'comment', 'normal', FALSE),
('system_update', 'อัปเดตระบบ', 'แจ้งเตือนเมื่อมีการอัปเดตระบบ', 'download', 'normal', TRUE),
('backup_complete', 'สำรองข้อมูลเสร็จสิ้น', 'แจ้งเตือนเมื่อการสำรองข้อมูลเสร็จสิ้น', 'database', 'low', TRUE),
('user_login', 'การเข้าสู่ระบบ', 'แจ้งเตือนเมื่อมีการเข้าสู่ระบบ', 'sign-in-alt', 'low', TRUE);

-- ตั้งค่าการแจ้งเตือนเริ่มต้นสำหรับ Admin
INSERT INTO notification_preferences (staff_id, notification_type, email_enabled, browser_enabled, line_enabled, sound_enabled)
SELECT 1, type_code, TRUE, TRUE, FALSE, TRUE FROM notification_types;
