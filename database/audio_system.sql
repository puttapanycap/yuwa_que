-- Audio calling system tables
-- ตารางสำหรับระบบเสียงเรียกคิว

-- ตารางเสียงที่บันทึกไว้
CREATE TABLE audio_files (
    audio_id INT PRIMARY KEY AUTO_INCREMENT,
    file_name VARCHAR(100) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    audio_type ENUM('queue_number', 'service_point', 'message', 'system') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ตารางรูปแบบข้อความเสียงเรียก
CREATE TABLE voice_templates (
    template_id INT PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(100) NOT NULL,
    template_text TEXT NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ตารางประวัติการเรียกเสียง
CREATE TABLE audio_call_history (
    call_id INT PRIMARY KEY AUTO_INCREMENT,
    queue_id INT,
    service_point_id INT,
    staff_id INT,
    message TEXT,
    call_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tts_used BOOLEAN DEFAULT FALSE,
    audio_status ENUM('pending', 'played', 'failed') DEFAULT 'pending',
    FOREIGN KEY (queue_id) REFERENCES queues(queue_id) ON DELETE CASCADE,
    FOREIGN KEY (service_point_id) REFERENCES service_points(service_point_id),
    FOREIGN KEY (staff_id) REFERENCES staff_users(staff_id)
);

-- ข้อมูลเริ่มต้นสำหรับรูปแบบข้อความเสียงเรียก
INSERT INTO voice_templates (template_name, template_text, is_default) VALUES
('เรียกคิวมาตรฐาน', 'หมายเลข {queue_number} เชิญที่ {service_point_name}', TRUE),
('เรียกคิวแบบสั้น', 'คิว {queue_number} ที่ {service_point_name}', FALSE),
('เรียกคิวแบบมีชื่อ', 'คุณ {patient_name} หมายเลข {queue_number} เชิญที่ {service_point_name}', FALSE);

-- เพิ่มคอลัมน์เพื่อกำหนด voice template ในตาราง service_points
ALTER TABLE service_points ADD COLUMN voice_template_id INT NULL;
ALTER TABLE service_points ADD CONSTRAINT fk_voice_template FOREIGN KEY (voice_template_id) REFERENCES voice_templates(template_id);

-- อัพเดทตั้งค่าระบบเสียง
INSERT INTO settings (setting_key, setting_value, description) VALUES
('tts_provider', 'google', 'ผู้ให้บริการ Text-to-Speech (google, azure, amazon)'),
('tts_language', 'th-TH', 'ภาษาที่ใช้ในการอ่านเสียง'),
('tts_voice', 'th-TH-Standard-A', 'รูปแบบเสียงที่ใช้'),
('tts_speed', '1.0', 'ความเร็วในการพูด'),
('tts_pitch', '0', 'ระดับเสียงสูง-ต่ำ'),
('audio_volume', '1.0', 'ระดับความดังของเสียง'),
('audio_repeat_count', '1', 'จำนวนครั้งที่เล่นเสียงซ้ำ'),
('sound_notification_before', '1', 'เล่นเสียงแจ้งเตือนก่อนเรียกคิว');

-- สร้างข้อมูลสิทธิ์
INSERT INTO permissions (permission_name, description) VALUES
('manage_audio_system', 'จัดการระบบเสียงเรียกคิว');

-- เพิ่มสิทธิ์ให้กับ Admin
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, permission_id FROM permissions WHERE permission_name = 'manage_audio_system';
