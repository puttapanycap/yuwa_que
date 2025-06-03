-- Yuwaprasart Queue System Database Schema
-- โครงสร้างฐานข้อมูลสำหรับระบบเรียกคิวโรงพยาบาล

-- ตารางประเภทคิว
CREATE TABLE queue_types (
    queue_type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(100) NOT NULL,
    description TEXT,
    prefix_char VARCHAR(5) NOT NULL DEFAULT 'A',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางจุดบริการ
CREATE TABLE service_points (
    service_point_id INT PRIMARY KEY AUTO_INCREMENT,
    point_name VARCHAR(100) NOT NULL,
    point_description TEXT,
    position_key VARCHAR(50) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางบทบาท
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางสิทธิ์
CREATE TABLE permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    permission_name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางความสัมพันธ์ระหว่างบทบาทและสิทธิ์
CREATE TABLE role_permissions (
    role_id INT,
    permission_id INT,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE
);

-- ตารางผู้ใช้งาน (เจ้าหน้าที่)
CREATE TABLE staff_users (
    staff_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- ตารางสิทธิ์การเข้าถึงจุดบริการของเจ้าหน้าที่
CREATE TABLE staff_service_point_access (
    staff_id INT,
    service_point_id INT,
    PRIMARY KEY (staff_id, service_point_id),
    FOREIGN KEY (staff_id) REFERENCES staff_users(staff_id) ON DELETE CASCADE,
    FOREIGN KEY (service_point_id) REFERENCES service_points(service_point_id) ON DELETE CASCADE
);

-- ตารางข้อมูลผู้ป่วยเบื้องต้น
CREATE TABLE patients (
    patient_id INT PRIMARY KEY AUTO_INCREMENT,
    id_card_number VARCHAR(13) UNIQUE,
    name VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตารางคิว
CREATE TABLE queues (
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
    FOREIGN KEY (queue_type_id) REFERENCES queue_types(queue_type_id),
    FOREIGN KEY (current_service_point_id) REFERENCES service_points(service_point_id),
    INDEX idx_queue_status (current_status),
    INDEX idx_queue_service_point (current_service_point_id),
    INDEX idx_creation_time (creation_time)
);

-- ตารางประวัติการเคลื่อนไหวของคิว
CREATE TABLE service_flow_history (
    flow_id INT PRIMARY KEY AUTO_INCREMENT,
    queue_id INT,
    from_service_point_id INT NULL,
    to_service_point_id INT NULL,
    staff_id INT NULL,
    action ENUM('created', 'called', 'forwarded', 'completed', 'recalled', 'skipped', 'cancelled', 'hold') NOT NULL,
    notes TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (queue_id) REFERENCES queues(queue_id) ON DELETE CASCADE,
    FOREIGN KEY (from_service_point_id) REFERENCES service_points(service_point_id),
    FOREIGN KEY (to_service_point_id) REFERENCES service_points(service_point_id),
    FOREIGN KEY (staff_id) REFERENCES staff_users(staff_id)
);

-- ตารางการตั้งค่าระบบ
CREATE TABLE settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ตารางบันทึกการใช้งานระบบ
CREATE TABLE audit_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NULL,
    action_description TEXT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff_users(staff_id)
);

-- ตารางการตั้งค่า Flow การบริการ
CREATE TABLE service_flows (
    flow_id INT PRIMARY KEY AUTO_INCREMENT,
    queue_type_id INT,
    from_service_point_id INT,
    to_service_point_id INT,
    sequence_order INT DEFAULT 0,
    is_optional BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (queue_type_id) REFERENCES queue_types(queue_type_id) ON DELETE CASCADE,
    FOREIGN KEY (from_service_point_id) REFERENCES service_points(service_point_id),
    FOREIGN KEY (to_service_point_id) REFERENCES service_points(service_point_id)
);

-- ข้อมูลเริ่มต้น
INSERT INTO queue_types (type_name, description, prefix_char) VALUES
('คิวทั่วไป', 'คิวสำหรับผู้ป่วยทั่วไป', 'A'),
('คิวนัดหมาย', 'คิวสำหรับผู้ป่วยที่มีการนัดหมาย', 'B'),
('คิวเร่งด่วน', 'คิวสำหรับผู้ป่วยเร่งด่วน', 'C'),
('คิวผู้สูงอายุ/พิการ', 'คิวสำหรับผู้สูงอายุและผู้พิการ', 'D');

INSERT INTO service_points (point_name, point_description, position_key) VALUES
('จุดคัดกรอง', 'จุดคัดกรองผู้ป่วยเบื้องต้น', 'SCREENING_01'),
('ห้องตรวจ 1', 'ห้องตรวจแพทย์ห้องที่ 1', 'DOCTOR_01'),
('ห้องตรวจ 2', 'ห้องตรวจแพทย์ห้องที่ 2', 'DOCTOR_02'),
('ห้องเภสัช', 'จุดรับยา', 'PHARMACY_01'),
('การเงิน', 'จุดชำระเงิน', 'CASHIER_01'),
('เวชระเบียน', 'จุดบริการเวชระเบียน', 'RECORDS_01');

INSERT INTO roles (role_name, description) VALUES
('Admin', 'ผู้ดูแลระบบ'),
('Staff-Screening', 'เจ้าหน้าที่จุดคัดกรอง'),
('Staff-Doctor', 'เจ้าหน้าที่ห้องตรวจแพทย์'),
('Staff-Pharmacy', 'เจ้าหน้าที่เภสัช'),
('Staff-Cashier', 'เจ้าหน้าที่การเงิน'),
('Staff-Records', 'เจ้าหน้าที่เวชระเบียน');

INSERT INTO permissions (permission_name, description) VALUES
('manage_users', 'จัดการบัญชีผู้ใช้'),
('manage_settings', 'จัดการการตั้งค่าระบบ'),
('manage_queues', 'จัดการคิว'),
('call_queue', 'เรียกคิว'),
('forward_queue', 'ส่งต่อคิว'),
('cancel_queue', 'ยกเลิกคิว'),
('view_reports', 'ดูรายงาน'),
('manage_service_points', 'จัดการจุดบริการ');

-- กำหนดสิทธิ์ให้ Admin
INSERT INTO role_permissions (role_id, permission_id) 
SELECT 1, permission_id FROM permissions;

-- กำหนดสิทธิ์พื้นฐานให้ Staff
INSERT INTO role_permissions (role_id, permission_id) VALUES
(2, 3), (2, 4), (2, 5), -- Staff-Screening
(3, 3), (3, 4), (3, 5), -- Staff-Doctor
(4, 3), (4, 4), (4, 5), -- Staff-Pharmacy
(5, 3), (5, 4), (5, 5), -- Staff-Cashier
(6, 3), (6, 4), (6, 5); -- Staff-Records

-- สร้างบัญชี Admin เริ่มต้น (password: admin123)
INSERT INTO staff_users (username, password_hash, full_name, role_id) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 1);

INSERT INTO settings (setting_key, setting_value, description) VALUES
('hospital_name', 'โรงพยาบาลยุวประสาทไวทโยปถัมภ์', 'ชื่อโรงพยาบาล'),
('tts_enabled', '1', 'เปิดใช้งานระบบเสียงเรียกคิว'),
('tts_api_url', '', 'URL สำหรับ Text-to-Speech API'),
('queue_call_template', 'หมายเลข {queue_number} เชิญที่ {service_point_name}', 'รูปแบบข้อความเรียกคิว'),
('auto_forward_enabled', '0', 'เปิดใช้งานการส่งต่อคิวอัตโนมัติ'),
('max_queue_per_day', '999', 'จำนวนคิวสูงสุดต่อวัน');
