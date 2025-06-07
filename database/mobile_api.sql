-- Mobile API Database Schema
-- โครงสร้างฐานข้อมูลสำหรับ Mobile API

-- ตารางการลงทะเบียน Mobile App
CREATE TABLE mobile_app_registrations (
    registration_id INT PRIMARY KEY AUTO_INCREMENT,
    app_name VARCHAR(100) NOT NULL,
    api_key VARCHAR(255) UNIQUE NOT NULL,
    api_secret VARCHAR(255) NOT NULL,
    app_version VARCHAR(20),
    platform ENUM('ios', 'android', 'web', 'other') DEFAULT 'other',
    bundle_id VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    rate_limit_per_minute INT DEFAULT 60,
    allowed_endpoints TEXT, -- JSON array of allowed endpoints
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL
);

-- ตารางการใช้งาน API
CREATE TABLE api_usage_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    registration_id INT,
    endpoint VARCHAR(255) NOT NULL,
    method ENUM('GET', 'POST', 'PUT', 'DELETE') NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    request_data TEXT, -- JSON
    response_code INT,
    response_time_ms INT,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (registration_id) REFERENCES mobile_app_registrations(registration_id) ON DELETE CASCADE,
    INDEX idx_endpoint (endpoint),
    INDEX idx_created_at (created_at),
    INDEX idx_registration_id (registration_id)
);

-- ตารางผู้ใช้ Mobile App
CREATE TABLE mobile_users (
    mobile_user_id INT PRIMARY KEY AUTO_INCREMENT,
    registration_id INT,
    device_id VARCHAR(255) UNIQUE NOT NULL,
    device_token VARCHAR(255), -- For push notifications
    platform ENUM('ios', 'android', 'web') NOT NULL,
    app_version VARCHAR(20),
    os_version VARCHAR(50),
    device_model VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (registration_id) REFERENCES mobile_app_registrations(registration_id) ON DELETE CASCADE,
    INDEX idx_device_id (device_id),
    INDEX idx_registration_id (registration_id)
);

-- ตารางเซสชัน Mobile
CREATE TABLE mobile_sessions (
    session_id VARCHAR(255) PRIMARY KEY,
    mobile_user_id INT,
    registration_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mobile_user_id) REFERENCES mobile_users(mobile_user_id) ON DELETE CASCADE,
    FOREIGN KEY (registration_id) REFERENCES mobile_app_registrations(registration_id) ON DELETE CASCADE,
    INDEX idx_expires_at (expires_at),
    INDEX idx_mobile_user_id (mobile_user_id)
);

-- ตารางการแจ้งเตือน Push Notification
CREATE TABLE push_notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    mobile_user_id INT,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    data JSON, -- Additional data
    status ENUM('pending', 'sent', 'delivered', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mobile_user_id) REFERENCES mobile_users(mobile_user_id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_mobile_user_id (mobile_user_id),
    INDEX idx_created_at (created_at)
);

-- ตารางการตั้งค่า API
CREATE TABLE api_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ข้อมูลเริ่มต้น
INSERT INTO api_settings (setting_key, setting_value, description) VALUES
('api_enabled', '1', 'เปิดใช้งาน Mobile API'),
('api_version', '1.0', 'เวอร์ชัน API ปัจจุบัน'),
('rate_limit_enabled', '1', 'เปิดใช้งานการจำกัดอัตราการเรียกใช้'),
('default_rate_limit', '60', 'จำนวนการเรียกใช้สูงสุดต่อนาที'),
('session_timeout', '3600', 'เวลาหมดอายุเซสชัน (วินาที)'),
('push_notification_enabled', '1', 'เปิดใช้งาน Push Notification'),
('api_documentation_url', '/api/docs', 'URL เอกสาร API'),
('api_support_email', 'support@hospital.com', 'อีเมลสำหรับการสนับสนุน API');

-- สร้าง API Key เริ่มต้นสำหรับทดสอบ
INSERT INTO mobile_app_registrations (
    app_name, 
    api_key, 
    api_secret, 
    platform, 
    allowed_endpoints
) VALUES (
    'Hospital Queue Mobile App',
    'test_api_key_12345',
    SHA2('test_secret_67890', 256),
    'android',
    '["queue", "status", "types", "notifications"]'
);
