-- =====================================================
-- Yuwaprasart Queue System - Complete Database Schema
-- โครงสร้างฐานข้อมูลสำหรับระบบเรียกคิวโรงพยาบาล
-- =====================================================

-- =====================================================
-- SECTION 1: TABLE CREATION
-- =====================================================

-- ตารางรูปแบบข้อความเสียงเรียก (Audio System)
CREATE TABLE IF NOT EXISTS voice_templates (
    template_id INT PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(100) NOT NULL,
    template_text TEXT NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (template_name)
);

-- ตารางผู้ใช้งาน (เจ้าหน้าที่)
CREATE TABLE IF NOT EXISTS staff_users (
    staff_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff_users_username (username),
    INDEX idx_staff_users_active (is_active)
);

-- ตารางประเภทคิว (Core / Auto Reset)
CREATE TABLE IF NOT EXISTS queue_types (
    queue_type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(100) NOT NULL,
    description TEXT,
    prefix_char VARCHAR(5) NOT NULL DEFAULT 'A',
    is_active BOOLEAN DEFAULT TRUE,
    current_number INT DEFAULT 0,
    last_reset_date TIMESTAMP NULL,
    last_reset_by INT NULL,
    last_reset_type ENUM('manual', 'auto') DEFAULT 'manual',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (type_name),
    INDEX idx_queue_types_active (is_active),
    INDEX idx_queue_types_prefix (prefix_char)
);

-- ตารางจุดบริการ (Core / Audio)
CREATE TABLE IF NOT EXISTS service_points (
    service_point_id INT PRIMARY KEY AUTO_INCREMENT,
    point_name VARCHAR(100) NOT NULL,
    point_description TEXT,
    position_key VARCHAR(50) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    queue_type_id INT NULL,
    voice_template_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_service_points_active (is_active),
    INDEX idx_service_points_display_order (display_order),
    INDEX idx_service_points_queue_type (queue_type_id)
);

-- ตารางบทบาท
CREATE TABLE IF NOT EXISTS roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางสิทธิ์
CREATE TABLE IF NOT EXISTS permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    permission_name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางความสัมพันธ์ระหว่างบทบาทและสิทธิ์
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT,
    permission_id INT,
    PRIMARY KEY (role_id, permission_id)
);

-- ตารางสิทธิ์การเข้าถึงจุดบริการของเจ้าหน้าที่
CREATE TABLE IF NOT EXISTS staff_service_point_access (
    staff_id INT,
    service_point_id INT,
    PRIMARY KEY (staff_id, service_point_id)
);

-- ตารางข้อมูลผู้ป่วยเบื้องต้น
CREATE TABLE IF NOT EXISTS patients (
    patient_id INT PRIMARY KEY AUTO_INCREMENT,
    id_card_number VARCHAR(13) UNIQUE,
    name VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_patients_id_card (id_card_number)
);

-- ตารางคิว
CREATE TABLE IF NOT EXISTS queues (
    queue_id INT PRIMARY KEY AUTO_INCREMENT,
    queue_number VARCHAR(20) NOT NULL,
    queue_type_id INT,
    patient_id_card_number VARCHAR(13),
    kiosk_id VARCHAR(50),
    creation_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    current_status ENUM('waiting', 'called', 'processing', 'forwarded', 'completed', 'cancelled') DEFAULT 'waiting',
    current_service_point_id INT,
    last_called_time TIMESTAMP NULL,
    called_count INT DEFAULT 0,
    priority_level INT DEFAULT 0,
    estimated_wait_time INT DEFAULT 0,
	updated_at datetime NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_queue_status (current_status),
    INDEX idx_queue_service_point (current_service_point_id),
    INDEX idx_creation_time (creation_time),
    INDEX idx_queues_created_date (creation_time), -- FIXED
    INDEX idx_queues_status_date (current_status, creation_time) -- FIXED
);

-- ตารางประวัติการเคลื่อนไหวของคิว
CREATE TABLE IF NOT EXISTS service_flow_history (
    flow_id INT PRIMARY KEY AUTO_INCREMENT,
    queue_id INT,
    from_service_point_id INT NULL,
    to_service_point_id INT NULL,
    staff_id INT NULL,
    action ENUM('created', 'called', 'forwarded', 'completed', 'recalled', 'skipped', 'cancelled', 'hold') NOT NULL,
    notes TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_service_flow_queue_id (queue_id),
    INDEX idx_service_flow_timestamp (timestamp),
    INDEX idx_service_flow_action (action),
    INDEX idx_queue_history_date (timestamp) -- FIXED
);

-- ตารางการตั้งค่าระบบ
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ตารางบันทึกการใช้งานระบบ
CREATE TABLE IF NOT EXISTS audit_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NULL,
    action_description TEXT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_logs_date (timestamp) -- FIXED
);

-- ตารางการตั้งค่า Flow การบริการ
CREATE TABLE IF NOT EXISTS service_flows (
    flow_id INT PRIMARY KEY AUTO_INCREMENT,
    queue_type_id INT,
    from_service_point_id INT,
    to_service_point_id INT,
    sequence_order INT DEFAULT 0,
    is_optional BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_service_flows_queue_type (queue_type_id),
    INDEX idx_service_flows_active (is_active)
);

-- AUTO RESET SYSTEM TABLES
CREATE TABLE IF NOT EXISTS auto_reset_schedules (
    schedule_id INT PRIMARY KEY AUTO_INCREMENT,
    schedule_name VARCHAR(100) NOT NULL,
    reset_type ENUM('all', 'by_type', 'by_service_point') NOT NULL DEFAULT 'all',
    target_id INT NULL,
    schedule_time TIME NOT NULL,
    schedule_days VARCHAR(20) NOT NULL DEFAULT '1,2,3,4,5,6,7',
    is_active BOOLEAN DEFAULT TRUE,
    last_run_date DATE NULL,
    last_run_status ENUM('success', 'failed', 'skipped') NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS auto_reset_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    schedule_id INT,
    reset_type ENUM('all', 'by_type', 'by_service_point') NOT NULL,
    target_id INT NULL,
    reset_count INT DEFAULT 0,
    affected_types TEXT,
    status ENUM('success', 'failed', 'skipped') NOT NULL,
    error_message TEXT NULL,
    execution_time DECIMAL(5,3) DEFAULT 0,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- NOTIFICATION SYSTEM TABLES
CREATE TABLE IF NOT EXISTS notification_types (
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

CREATE TABLE IF NOT EXISTS notifications (
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
    INDEX idx_notification_recipient (recipient_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_notification_created (created_at)
);

CREATE TABLE IF NOT EXISTS notification_preferences (
    preference_id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    email_enabled BOOLEAN DEFAULT FALSE,
    browser_enabled BOOLEAN DEFAULT TRUE,
    telegram_enabled BOOLEAN DEFAULT FALSE, -- Merged from line_enabled/telegram_enabled
    sound_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_staff_notification_type (staff_id, notification_type)
);

CREATE TABLE IF NOT EXISTS notification_deliveries (
    delivery_id INT PRIMARY KEY AUTO_INCREMENT,
    notification_id INT NOT NULL,
    channel ENUM('browser', 'email', 'telegram', 'sms') NOT NULL, -- Merged from line/telegram
    status ENUM('pending', 'sent', 'failed', 'delivered', 'read') DEFAULT 'pending',
    error_message TEXT NULL,
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_delivery_status (status),
    INDEX idx_delivery_channel (channel)
);

-- AUDIO SYSTEM TABLES
CREATE TABLE IF NOT EXISTS audio_files (
    audio_id INT PRIMARY KEY AUTO_INCREMENT,
    file_name VARCHAR(100) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    audio_type ENUM('queue_number', 'service_point', 'message', 'system') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS audio_call_history (
    call_id INT PRIMARY KEY AUTO_INCREMENT,
    queue_id INT,
    service_point_id INT,
    staff_id INT,
    message TEXT,
    call_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tts_used BOOLEAN DEFAULT FALSE,
    audio_status ENUM('pending', 'played', 'failed') DEFAULT 'pending'
);

-- MOBILE API TABLES
CREATE TABLE IF NOT EXISTS mobile_app_registrations (
    registration_id INT PRIMARY KEY AUTO_INCREMENT,
    app_name VARCHAR(100) NOT NULL,
    api_key VARCHAR(255) UNIQUE NOT NULL,
    api_secret VARCHAR(255) NOT NULL,
    app_version VARCHAR(20),
    platform ENUM('ios', 'android', 'web', 'other') DEFAULT 'other',
    bundle_id VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    rate_limit_per_minute INT DEFAULT 60,
    allowed_endpoints TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS api_usage_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    registration_id INT,
    endpoint VARCHAR(255) NOT NULL,
    method ENUM('GET', 'POST', 'PUT', 'DELETE') NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    request_data TEXT,
    response_code INT,
    response_time_ms INT,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_endpoint (endpoint),
    INDEX idx_created_at (created_at),
    INDEX idx_registration_id (registration_id)
);

CREATE TABLE IF NOT EXISTS mobile_users (
    mobile_user_id INT PRIMARY KEY AUTO_INCREMENT,
    registration_id INT,
    device_id VARCHAR(255) UNIQUE NOT NULL,
    device_token VARCHAR(255),
    platform ENUM('ios', 'android', 'web') NOT NULL,
    app_version VARCHAR(20),
    os_version VARCHAR(50),
    device_model VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_device_id (device_id)
);

CREATE TABLE IF NOT EXISTS mobile_sessions (
    session_id VARCHAR(255) PRIMARY KEY,
    mobile_user_id INT,
    registration_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expires_at (expires_at),
    INDEX idx_mobile_user_id (mobile_user_id)
);

CREATE TABLE IF NOT EXISTS push_notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    mobile_user_id INT,
    title VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    data JSON,
    status ENUM('pending', 'sent', 'delivered', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_mobile_user_id (mobile_user_id)
);

CREATE TABLE IF NOT EXISTS api_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- REPORTING & ANALYTICS TABLES
CREATE TABLE IF NOT EXISTS report_templates (
    template_id INT PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(100) NOT NULL,
    template_description TEXT,
    report_type ENUM('queue_performance', 'service_point_analysis', 'staff_productivity', 'patient_flow', 'custom') NOT NULL,
    template_config JSON,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS scheduled_reports (
    schedule_id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT NOT NULL,
    schedule_name VARCHAR(100) NOT NULL,
    schedule_frequency ENUM('daily', 'weekly', 'monthly', 'quarterly') NOT NULL,
    schedule_time TIME NOT NULL,
    schedule_day_of_week INT NULL,
    schedule_day_of_month INT NULL,
    recipients JSON,
    last_run_at TIMESTAMP NULL,
    next_run_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS report_execution_log (
    execution_id INT PRIMARY KEY AUTO_INCREMENT,
    schedule_id INT NULL,
    template_id INT NOT NULL,
    execution_type ENUM('manual', 'scheduled') NOT NULL,
    parameters JSON,
    status ENUM('running', 'completed', 'failed') NOT NULL,
    file_path VARCHAR(255) NULL,
    file_size INT NULL,
    execution_time_seconds DECIMAL(10,2) NULL,
    error_message TEXT NULL,
    executed_by INT,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS report_cache (
    cache_id INT PRIMARY KEY AUTO_INCREMENT,
    cache_key VARCHAR(255) UNIQUE NOT NULL,
    report_data LONGTEXT,
    parameters JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_cache_key (cache_key),
    INDEX idx_expires_at (expires_at)
);

CREATE TABLE IF NOT EXISTS daily_performance_summary (
    summary_id INT PRIMARY KEY AUTO_INCREMENT,
    summary_date DATE NOT NULL,
    queue_type_id INT,
    service_point_id INT,
    total_queues INT DEFAULT 0,
    completed_queues INT DEFAULT 0,
    cancelled_queues INT DEFAULT 0,
    avg_wait_time_minutes DECIMAL(10,2) DEFAULT 0,
    avg_service_time_minutes DECIMAL(10,2) DEFAULT 0,
    max_wait_time_minutes DECIMAL(10,2) DEFAULT 0,
    peak_hour_start TIME,
    peak_hour_end TIME,
    peak_hour_queue_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_daily_summary (summary_date, queue_type_id, service_point_id),
    INDEX idx_summary_date (summary_date)
);

-- DASHBOARD ANALYTICS TABLES
CREATE TABLE IF NOT EXISTS dashboard_widgets (
    widget_id INT PRIMARY KEY AUTO_INCREMENT,
    widget_name VARCHAR(100) NOT NULL,
    widget_type ENUM('chart', 'counter', 'table', 'gauge', 'map') NOT NULL,
    widget_config JSON,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS dashboard_user_preferences (
    preference_id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT,
    widget_layout JSON,
    refresh_interval INT DEFAULT 30,
    theme VARCHAR(20) DEFAULT 'light',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS real_time_metrics (
    metric_id INT PRIMARY KEY AUTO_INCREMENT,
    metric_name VARCHAR(100) NOT NULL,
    metric_value DECIMAL(10,2),
    metric_data JSON,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_metric_name_time (metric_name, recorded_at)
);

CREATE TABLE IF NOT EXISTS dashboard_alerts (
    alert_id INT PRIMARY KEY AUTO_INCREMENT,
    alert_type ENUM('warning', 'error', 'info', 'success') NOT NULL,
    alert_title VARCHAR(200) NOT NULL,
    alert_message TEXT,
    alert_data JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX idx_active_alerts (is_active, created_at)
);

-- =====================================================
-- SECTION 2: FOREIGN KEY CONSTRAINTS
-- =====================================================

-- Constraints for staff_users, queue_types, service_points
ALTER TABLE staff_users ADD CONSTRAINT fk_staff_role FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE SET NULL;
ALTER TABLE queue_types ADD CONSTRAINT fk_qt_reset_by FOREIGN KEY (last_reset_by) REFERENCES staff_users(staff_id) ON DELETE SET NULL;
ALTER TABLE service_points ADD CONSTRAINT fk_sp_queue_type FOREIGN KEY (queue_type_id) REFERENCES queue_types(queue_type_id) ON DELETE SET NULL;
ALTER TABLE service_points ADD CONSTRAINT fk_sp_voice_template FOREIGN KEY (voice_template_id) REFERENCES voice_templates(template_id) ON DELETE SET NULL;

-- Constraints for role_permissions, staff_service_point_access
ALTER TABLE role_permissions ADD CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE;
ALTER TABLE role_permissions ADD CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE;
ALTER TABLE staff_service_point_access ADD CONSTRAINT fk_sspa_staff FOREIGN KEY (staff_id) REFERENCES staff_users(staff_id) ON DELETE CASCADE;
ALTER TABLE staff_service_point_access ADD CONSTRAINT fk_sspa_sp FOREIGN KEY (service_point_id) REFERENCES service_points(service_point_id) ON DELETE CASCADE;

-- Constraints for queues, service_flow_history, audit_logs, service_flows
ALTER TABLE queues ADD CONSTRAINT fk_q_queue_type FOREIGN KEY (queue_type_id) REFERENCES queue_types(queue_type_id) ON DELETE SET NULL;
ALTER TABLE queues ADD CONSTRAINT fk_q_service_point FOREIGN KEY (current_service_point_id) REFERENCES service_points(service_point_id) ON DELETE SET NULL;
ALTER TABLE service_flow_history ADD CONSTRAINT fk_sfh_queue FOREIGN KEY (queue_id) REFERENCES queues(queue_id) ON DELETE CASCADE;
ALTER TABLE service_flow_history ADD CONSTRAINT fk_sfh_from_sp FOREIGN KEY (from_service_point_id) REFERENCES service_points(service_point_id) ON DELETE SET NULL;
ALTER TABLE service_flow_history ADD CONSTRAINT fk_sfh_to_sp FOREIGN KEY (to_service_point_id) REFERENCES service_points(service_point_id) ON DELETE SET NULL;
ALTER TABLE service_flow_history ADD CONSTRAINT fk_sfh_staff FOREIGN KEY (staff_id) REFERENCES staff_users(staff_id) ON DELETE SET NULL;
ALTER TABLE audit_logs ADD CONSTRAINT fk_al_staff FOREIGN KEY (staff_id) REFERENCES staff_users(staff_id) ON DELETE SET NULL;
ALTER TABLE service_flows ADD CONSTRAINT fk_sf_queue_type FOREIGN KEY (queue_type_id) REFERENCES queue_types(queue_type_id) ON DELETE CASCADE;
ALTER TABLE service_flows ADD CONSTRAINT fk_sf_from_sp FOREIGN KEY (from_service_point_id) REFERENCES service_points(service_point_id) ON DELETE CASCADE;
ALTER TABLE service_flows ADD CONSTRAINT fk_sf_to_sp FOREIGN KEY (to_service_point_id) REFERENCES service_points(service_point_id) ON DELETE CASCADE;

-- Constraints for Auto Reset System
ALTER TABLE auto_reset_schedules ADD CONSTRAINT fk_ars_created_by FOREIGN KEY (created_by) REFERENCES staff_users(staff_id) ON DELETE SET NULL;
ALTER TABLE auto_reset_logs ADD CONSTRAINT fk_arl_schedule FOREIGN KEY (schedule_id) REFERENCES auto_reset_schedules(schedule_id) ON DELETE CASCADE;

-- Constraints for Notification System
ALTER TABLE notifications ADD CONSTRAINT fk_n_recipient FOREIGN KEY (recipient_id) REFERENCES staff_users(staff_id) ON DELETE CASCADE;
ALTER TABLE notifications ADD CONSTRAINT fk_n_sender FOREIGN KEY (sender_id) REFERENCES staff_users(staff_id) ON DELETE SET NULL;
ALTER TABLE notification_preferences ADD CONSTRAINT fk_np_staff FOREIGN KEY (staff_id) REFERENCES staff_users(staff_id) ON DELETE CASCADE;
ALTER TABLE notification_deliveries ADD CONSTRAINT fk_nd_notification FOREIGN KEY (notification_id) REFERENCES notifications(notification_id) ON DELETE CASCADE;

-- Constraints for Audio System
ALTER TABLE audio_call_history ADD CONSTRAINT fk_ach_queue FOREIGN KEY (queue_id) REFERENCES queues(queue_id) ON DELETE CASCADE;
ALTER TABLE audio_call_history ADD CONSTRAINT fk_ach_sp FOREIGN KEY (service_point_id) REFERENCES service_points(service_point_id) ON DELETE SET NULL;
ALTER TABLE audio_call_history ADD CONSTRAINT fk_ach_staff FOREIGN KEY (staff_id) REFERENCES staff_users(staff_id) ON DELETE SET NULL;

-- Constraints for Mobile API
ALTER TABLE api_usage_logs ADD CONSTRAINT fk_aul_reg FOREIGN KEY (registration_id) REFERENCES mobile_app_registrations(registration_id) ON DELETE CASCADE;
ALTER TABLE mobile_users ADD CONSTRAINT fk_mu_reg FOREIGN KEY (registration_id) REFERENCES mobile_app_registrations(registration_id) ON DELETE CASCADE;
ALTER TABLE mobile_sessions ADD CONSTRAINT fk_ms_user FOREIGN KEY (mobile_user_id) REFERENCES mobile_users(mobile_user_id) ON DELETE CASCADE;
ALTER TABLE mobile_sessions ADD CONSTRAINT fk_ms_reg FOREIGN KEY (registration_id) REFERENCES mobile_app_registrations(registration_id) ON DELETE CASCADE;
ALTER TABLE push_notifications ADD CONSTRAINT fk_pn_user FOREIGN KEY (mobile_user_id) REFERENCES mobile_users(mobile_user_id) ON DELETE CASCADE;

-- Constraints for Reporting & Analytics
ALTER TABLE report_templates ADD CONSTRAINT fk_rt_created_by FOREIGN KEY (created_by) REFERENCES staff_users(staff_id) ON DELETE SET NULL;
ALTER TABLE scheduled_reports ADD CONSTRAINT fk_sr_template FOREIGN KEY (template_id) REFERENCES report_templates(template_id) ON DELETE CASCADE;
ALTER TABLE scheduled_reports ADD CONSTRAINT fk_sr_created_by FOREIGN KEY (created_by) REFERENCES staff_users(staff_id) ON DELETE SET NULL;
ALTER TABLE report_execution_log ADD CONSTRAINT fk_rel_schedule FOREIGN KEY (schedule_id) REFERENCES scheduled_reports(schedule_id) ON DELETE SET NULL;
ALTER TABLE report_execution_log ADD CONSTRAINT fk_rel_template FOREIGN KEY (template_id) REFERENCES report_templates(template_id) ON DELETE CASCADE;
ALTER TABLE report_execution_log ADD CONSTRAINT fk_rel_executed_by FOREIGN KEY (executed_by) REFERENCES staff_users(staff_id) ON DELETE SET NULL;
ALTER TABLE daily_performance_summary ADD CONSTRAINT fk_dps_queue_type FOREIGN KEY (queue_type_id) REFERENCES queue_types(queue_type_id) ON DELETE SET NULL;
ALTER TABLE daily_performance_summary ADD CONSTRAINT fk_dps_sp FOREIGN KEY (service_point_id) REFERENCES service_points(service_point_id) ON DELETE SET NULL;

-- Constraints for Dashboard
ALTER TABLE dashboard_user_preferences ADD CONSTRAINT fk_dup_staff FOREIGN KEY (staff_id) REFERENCES staff_users(staff_id) ON DELETE CASCADE;

-- =====================================================
-- SECTION 3: DEFAULT DATA INSERTION (IDEMPOTENT)
-- =====================================================

-- ข้อมูลเริ่มต้นสำหรับบทบาท
INSERT IGNORE INTO roles (role_id, role_name, description) VALUES
(1, 'Admin', 'ผู้ดูแลระบบ'),
(2, 'Staff-Screening', 'เจ้าหน้าที่จุดคัดกรอง'),
(3, 'Staff-Doctor', 'เจ้าหน้าที่ห้องตรวจแพทย์'),
(4, 'Staff-Pharmacy', 'เจ้าหน้าที่เภสัช'),
(5, 'Staff-Cashier', 'เจ้าหน้าที่การเงิน'),
(6, 'Staff-Records', 'เจ้าหน้าที่เวชระเบียน');

-- ข้อมูลเริ่มต้นสำหรับสิทธิ์
INSERT IGNORE INTO permissions (permission_id, permission_name, description) VALUES
(1, 'manage_users', 'จัดการบัญชีผู้ใช้'),
(2, 'manage_settings', 'จัดการการตั้งค่าระบบ'),
(3, 'manage_queues', 'จัดการคิว'),
(4, 'call_queue', 'เรียกคิว'),
(5, 'forward_queue', 'ส่งต่อคิว'),
(6, 'cancel_queue', 'ยกเลิกคิว'),
(7, 'view_reports', 'ดูรายงาน'),
(8, 'manage_service_points', 'จัดการจุดบริการ'),
(9, 'manage_audio_system', 'จัดการระบบเสียงเรียกคิว');

-- สร้างบัญชี Admin เริ่มต้น (password: admin123)
INSERT IGNORE INTO staff_users (staff_id, username, password_hash, full_name, role_id) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 1);

-- กำหนดสิทธิ์ทั้งหมดให้ Admin
INSERT IGNORE INTO role_permissions (role_id, permission_id) 
SELECT 1, p.permission_id FROM permissions p;

-- กำหนดสิทธิ์พื้นฐานให้ Staff (สิทธิ์จัดการ, เรียก, ส่งต่อคิว)
INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES
(2, 3), (2, 4), (2, 5),
(3, 3), (3, 4), (3, 5),
(4, 3), (4, 4), (4, 5),
(5, 3), (5, 4), (5, 5),
(6, 3), (6, 4), (6, 5);

-- ข้อมูลเริ่มต้นสำหรับประเภทคิว
INSERT IGNORE INTO queue_types (type_name, description, prefix_char) VALUES
('คิวทั่วไป', 'คิวสำหรับผู้ป่วยทั่วไป', 'A'),
('คิวนัดหมาย', 'คิวสำหรับผู้ป่วยที่มีการนัดหมาย', 'B'),
('คิวเร่งด่วน', 'คิวสำหรับผู้ป่วยเร่งด่วน', 'C'),
('คิวผู้สูงอายุ/พิการ', 'คิวสำหรับผู้สูงอายุและผู้พิการ', 'D');

-- ข้อมูลเริ่มต้นสำหรับจุดบริการ
INSERT IGNORE INTO service_points (point_name, point_description, position_key, display_order) VALUES
('จุดคัดกรอง', 'จุดคัดกรองผู้ป่วยเบื้องต้น', 'SCREENING_01', 1),
('ห้องตรวจ 1', 'ห้องตรวจแพทย์ห้องที่ 1', 'DOCTOR_01', 2),
('ห้องตรวจ 2', 'ห้องตรวจแพทย์ห้องที่ 2', 'DOCTOR_02', 3),
('ห้องเภสัช', 'จุดรับยา', 'PHARMACY_01', 4),
('การเงิน', 'จุดชำระเงิน', 'CASHIER_01', 5),
('เวชระเบียน', 'จุดบริการเวชระเบียน', 'RECORDS_01', 6);

-- ข้อมูลเริ่มต้นสำหรับรูปแบบข้อความเสียงเรียก
INSERT IGNORE INTO voice_templates (template_name, template_text, is_default) VALUES
('เรียกคิวมาตรฐาน', 'หมายเลข {queue_number} เชิญที่ {service_point_name}', TRUE),
('เรียกคิวแบบสั้น', 'คิว {queue_number} ที่ {service_point_name}', FALSE),
('เรียกคิวแบบมีชื่อ', 'คุณ {patient_name} หมายเลข {queue_number} เชิญที่ {service_point_name}', FALSE);

-- ข้อมูลเริ่มต้นสำหรับประเภทการแจ้งเตือน
INSERT IGNORE INTO notification_types (type_code, type_name, description, icon, default_priority, is_system) VALUES
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

-- ตัวอย่างการตั้งค่า Auto Reset
INSERT IGNORE INTO auto_reset_schedules (schedule_name, reset_type, schedule_time, schedule_days, created_by) VALUES
('Reset รายวัน - เที่ยงคืน', 'all', '00:00:00', '1,2,3,4,5,6,7', 1),
('Reset คิวทั่วไป - เช้า', 'by_type', '06:00:00', '1,2,3,4,5', 1);

-- สร้าง API Key เริ่มต้นสำหรับทดสอบ
INSERT IGNORE INTO mobile_app_registrations (app_name, api_key, api_secret, platform, allowed_endpoints) VALUES 
('Hospital Queue Mobile App','test_api_key_12345',SHA2('test_secret_67890', 256),'android','["queue", "status", "types", "notifications"]');

-- Insert default report templates
INSERT IGNORE INTO report_templates (template_name, template_description, report_type, template_config, created_by) VALUES
('รายงานประสิทธิภาพคิวรายวัน', 'รายงานสรุปประสิทธิภาพการให้บริการรายวัน', 'queue_performance', '{"period": "daily", "metrics": ["total_queues", "avg_wait_time", "completion_rate"], "groupBy": "queue_type"}', 1),
('รายงานการใช้งานจุดบริการ', 'วิเคราะห์การใช้งานจุดบริการต่างๆ', 'service_point_analysis', '{"period": "weekly", "metrics": ["utilization_rate", "avg_service_time", "peak_hours"], "groupBy": "service_point"}', 1),
('รายงานผลิตภาพเจ้าหน้าที่', 'ประเมินผลิตภาพการทำงานของเจ้าหน้าที่', 'staff_productivity', '{"period": "monthly", "metrics": ["queues_served", "avg_service_time", "efficiency_score"], "groupBy": "staff"}', 1),
('รายงานการไหลของผู้ป่วย', 'วิเคราะห์เส้นทางการให้บริการผู้ป่วย', 'patient_flow', '{"period": "weekly", "metrics": ["flow_completion_rate", "bottlenecks", "avg_flow_time"], "groupBy": "service_flow"}', 1),
('รายงานสรุปรายเดือน', 'รายงานสรุปภาพรวมประจำเดือน', 'queue_performance', '{"period": "monthly", "metrics": ["all"], "groupBy": "month", "includeCharts": true}', 1);

-- Insert default widgets
INSERT IGNORE INTO dashboard_widgets (widget_name, widget_type, widget_config, display_order) VALUES
('คิวรอทั้งหมด', 'counter', '{"color": "primary", "icon": "fas fa-users", "query": "waiting_queues"}', 1),
('คิวที่เสร็จสิ้นวันนี้', 'counter', '{"color": "success", "icon": "fas fa-check-circle", "query": "completed_today"}', 2),
('เวลารอเฉลี่ย', 'gauge', '{"color": "warning", "icon": "fas fa-clock", "query": "avg_wait_time", "max": 60}', 3),
('จุดบริการที่ใช้งาน', 'counter', '{"color": "info", "icon": "fas fa-map-marker-alt", "query": "active_service_points"}', 4),
('กราฟคิวรายชั่วโมง', 'chart', '{"type": "line", "query": "hourly_queues", "height": 300}', 5),
('สถานะจุดบริการ', 'table', '{"query": "service_point_status", "height": 400}', 6),
('การกระจายประเภทคิว', 'chart', '{"type": "doughnut", "query": "queue_type_distribution", "height": 300}', 7),
('คิวล่าสุด', 'table', '{"query": "recent_queues", "height": 400}', 8);

-- Insert default settings for admin-managed configurations
INSERT INTO settings (setting_key, setting_value, description) VALUES
('app_name', 'โรงพยาบาลยุวประสาทไวทโยปถัมภ์', 'ชื่อแอปพลิเคชัน'),
('app_description', 'ระบบจัดการคิวโรงพยาบาล', 'คำอธิบายแอปพลิเคชัน'),
('app_logo', '', 'โลโก้แอปพลิเคชัน'),
('app_timezone', 'Asia/Bangkok', 'เขตเวลาของแอปพลิเคชัน'),
('app_language', 'th', 'ภาษาของแอปพลิเคชัน'),
('queue_prefix_length', '1', 'ความยาวของ prefix คิว'),
('queue_number_length', '3', 'ความยาวของหมายเลขคิว'),
('max_queue_per_day', '999', 'จำนวนคิวสูงสุดต่อวัน'),
('queue_timeout_minutes', '30', 'เวลา timeout ของคิว (นาที)'),
('display_refresh_interval', '3', 'ช่วงเวลาการรีเฟรชหน้าจอ (วินาที)'),
('enable_priority_queue', 'true', 'เปิดใช้งานคิวพิเศษ'),
('auto_forward_enabled', 'false', 'เปิดใช้งานการส่งต่ออัตโนมัติ'),
('working_hours_start', '08:00', 'เวลาเริ่มทำงาน'),
('working_hours_end', '16:00', 'เวลาสิ้นสุดการทำงาน'),
('tts_enabled', 'true', 'เปิดใช้งาน TTS'),
('tts_provider', 'google', 'ผู้ให้บริการ TTS'),
('tts_api_url', '', 'URL API ของ TTS'),
('tts_language', 'th-TH', 'ภาษาของ TTS'),
('tts_voice', 'th-TH-Standard-A', 'เสียงของ TTS'),
('tts_speed', '1.0', 'ความเร็วของ TTS'),
('tts_pitch', '0', 'ระดับเสียงของ TTS'),
('audio_volume', '1.0', 'ระดับเสียง'),
('audio_repeat_count', '1', 'จำนวนครั้งที่เล่นซ้ำ'),
('sound_notification_before', 'true', 'เล่นเสียงแจ้งเตือนก่อน'),
('google_cloud_project_id', '', 'Google Cloud Project ID'),
('google_cloud_key_file', '', 'Google Cloud Key File'),
('azure_speech_key', '', 'Azure Speech Service Key'),
('azure_speech_region', '', 'Azure Speech Service Region'),
('aws_access_key_id', '', 'AWS Access Key ID'),
('aws_secret_access_key', '', 'AWS Secret Access Key'),
('aws_region', '', 'AWS Region'),
('email_notifications', 'false', 'เปิดใช้งานการแจ้งเตือนทางอีเมล'),
('mail_host', 'smtp.gmail.com', 'SMTP Host'),
('mail_port', '587', 'SMTP Port'),
('mail_username', '', 'SMTP Username'),
('mail_password', '', 'SMTP Password'),
('mail_encryption', 'tls', 'SMTP Encryption'),
('mail_from_address', 'noreply@hospital.com', 'ที่อยู่อีเมลผู้ส่ง'),
('mail_from_name', 'Queue System', 'ชื่อผู้ส่งอีเมล'),
('telegram_notifications', 'false', 'เปิดใช้งาน Telegram Notifications'),
('telegram_bot_token', '', 'Telegram Bot Token'),
('telegram_chat_id', '', 'Telegram Chat ID (ทั่วไป)'),
('telegram_admin_chat_id', '', 'Telegram Admin Chat ID'),
('telegram_group_chat_id', '', 'Telegram Group Chat ID'),
('telegram_notify_template', 'คิว {queue_number} กรุณามาที่จุดบริการ {service_point}', 'เทมเพลตข้อความ Telegram'),
('auto_reset_enabled', 'false', 'เปิดใช้งานการรีเซ็ตอัตโนมัติ'),
('auto_reset_notification', 'true', 'แจ้งเตือนเมื่อรีเซ็ต'),
('auto_reset_backup_before', 'true', 'สำรองข้อมูลก่อนรีเซ็ต'),
('auto_reset_max_retries', '3', 'จำนวนครั้งสูงสุดในการลองใหม่'),
('notification_enabled', 'true', 'เปิดใช้งานระบบแจ้งเตือน'),
('backup_enabled', 'true', 'เปิดใช้งานการสำรองข้อมูล'),
('backup_retention_days', '30', 'จำนวนวันเก็บข้อมูลสำรอง'),
('auto_backup_enabled', 'false', 'เปิดใช้งานการสำรองอัตโนมัติ'),
('auto_backup_time', '02:00', 'เวลาสำรองข้อมูลอัตโนมัติ'),
('report_cache_enabled', 'true', 'เปิดใช้งาน cache สำหรับรายงาน'),
('report_cache_ttl', '1800', 'เวลา cache รายงาน (วินาที)'),
('daily_summary_enabled', 'true', 'เปิดใช้งานสรุปรายวัน'),
('daily_summary_time', '23:30', 'เวลาสร้างสรุปรายวัน'),
('api_enabled', '1', 'เปิดใช้งาน Mobile API'),
('api_version', '1.0', 'เวอร์ชัน API ปัจจุบัน'),
('rate_limit_enabled', '1', 'เปิดใช้งานการจำกัดอัตราการเรียกใช้'),
('default_rate_limit', '60', 'จำนวนการเรียกใช้สูงสุดต่อนาที'),
('session_timeout', '3600', 'เวลาหมดอายุเซสชัน (วินาที)'),
('push_notification_enabled', '1', 'เปิดใช้งาน Push Notification'),
('api_documentation_url', '/api/docs', 'URL เอกสาร API'),
('api_support_email', 'support@hospital.com', 'อีเมลสำหรับการสนับสนุน API')
ON DUPLICATE KEY UPDATE 
setting_value = VALUES(setting_value),
description = VALUES(description),
updated_at = CURRENT_TIMESTAMP;

-- =====================================================
-- SECTION 4: DATA MIGRATION/BACKFILL
-- =====================================================

-- เพิ่มข้อมูล service flow history สำหรับคิวที่สร้างไปแล้วแต่ยังไม่มีประวัติ
INSERT INTO service_flow_history (queue_id, to_service_point_id, action, notes, timestamp)
SELECT 
    q.queue_id,
    q.current_service_point_id,
    'created',
    'Created (Backfilled History)',
    q.creation_time
FROM 
    queues q
WHERE 
    NOT EXISTS (
        SELECT 1 
        FROM service_flow_history sfh 
        WHERE sfh.queue_id = q.queue_id AND sfh.action = 'created'
    );

-- =====================================================
-- END OF SCRIPT
-- =====================================================
