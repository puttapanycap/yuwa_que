-- Insert default settings for admin-managed configurations
INSERT INTO settings (setting_key, setting_value, description) VALUES
-- Application Configuration
('app_name', 'โรงพยาบาลยุวประสาทไวทโยปถัมภ์', 'ชื่อแอปพลิเคชัน'),
('app_description', 'ระบบจัดการคิวโรงพยาบาล', 'คำอธิบายแอปพลิเคชัน'),
('app_logo', '', 'โลโก้แอปพลิเคชัน'),
('app_timezone', 'Asia/Bangkok', 'เขตเวลาของแอปพลิเคชัน'),
('app_language', 'th', 'ภาษาของแอปพลิเคชัน'),

-- Queue System Configuration
('queue_prefix_length', '1', 'ความยาวของ prefix คิว'),
('queue_number_length', '3', 'ความยาวของหมายเลขคิว'),
('max_queue_per_day', '999', 'จำนวนคิวสูงสุดต่อวัน'),
('queue_timeout_minutes', '30', 'เวลา timeout ของคิว (นาที)'),
('display_refresh_interval', '3', 'ช่วงเวลาการรีเฟรชหน้าจอ (วินาที)'),
('enable_priority_queue', 'true', 'เปิดใช้งานคิวพิเศษ'),
('auto_forward_enabled', 'false', 'เปิดใช้งานการส่งต่ออัตโนมัติ'),

-- Working Hours
('working_hours_start', '08:00', 'เวลาเริ่มทำงาน'),
('working_hours_end', '16:00', 'เวลาสิ้นสุดการทำงาน'),

-- Audio/TTS Configuration
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

-- Google Cloud TTS
('google_cloud_project_id', '', 'Google Cloud Project ID'),
('google_cloud_key_file', '', 'Google Cloud Key File'),

-- Azure Speech Service
('azure_speech_key', '', 'Azure Speech Service Key'),
('azure_speech_region', '', 'Azure Speech Service Region'),

-- Amazon Polly
('aws_access_key_id', '', 'AWS Access Key ID'),
('aws_secret_access_key', '', 'AWS Secret Access Key'),
('aws_region', '', 'AWS Region'),

-- Email Configuration
('email_notifications', 'false', 'เปิดใช้งานการแจ้งเตือนทางอีเมล'),
('mail_host', 'smtp.gmail.com', 'SMTP Host'),
('mail_port', '587', 'SMTP Port'),
('mail_username', '', 'SMTP Username'),
('mail_password', '', 'SMTP Password'),
('mail_encryption', 'tls', 'SMTP Encryption'),
('mail_from_address', 'noreply@hospital.com', 'ที่อยู่อีเมลผู้ส่ง'),
('mail_from_name', 'Queue System', 'ชื่อผู้ส่งอีเมล'),

-- Telegram Configuration (replacing LINE)
('telegram_notifications', 'false', 'เปิดใช้งาน Telegram Notifications'),
('telegram_bot_token', '', 'Telegram Bot Token'),
('telegram_chat_id', '', 'Telegram Chat ID (ทั่วไป)'),
('telegram_admin_chat_id', '', 'Telegram Admin Chat ID'),
('telegram_group_chat_id', '', 'Telegram Group Chat ID'),
('telegram_notify_template', 'คิว {queue_number} กรุณามาที่จุดบริการ {service_point}', 'เทมเพลตข้อความ Telegram'),

-- Auto Reset Configuration
('auto_reset_enabled', 'false', 'เปิดใช้งานการรีเซ็ตอัตโนมัติ'),
('auto_reset_notification', 'true', 'แจ้งเตือนเมื่อรีเซ็ต'),
('auto_reset_backup_before', 'true', 'สำรองข้อมูลก่อนรีเซ็ต'),
('auto_reset_max_retries', '3', 'จำนวนครั้งสูงสุดในการลองใหม่'),

-- Notification Configuration
('notification_enabled', 'true', 'เปิดใช้งานระบบแจ้งเตือน'),

-- Backup Configuration
('backup_enabled', 'true', 'เปิดใช้งานการสำรองข้อมูล'),
('backup_retention_days', '30', 'จำนวนวันเก็บข้อมูลสำรอง'),
('auto_backup_enabled', 'false', 'เปิดใช้งานการสำรองอัตโนมัติ'),
('auto_backup_time', '02:00', 'เวลาสำรองข้อมูลอัตโนมัติ'),

-- Report Configuration
('report_cache_enabled', 'true', 'เปิดใช้งาน cache สำหรับรายงาน'),
('report_cache_ttl', '1800', 'เวลา cache รายงาน (วินาที)'),
('daily_summary_enabled', 'true', 'เปิดใช้งานสรุปรายวัน'),
('daily_summary_time', '23:30', 'เวลาสร้างสรุปรายวัน')

ON DUPLICATE KEY UPDATE 
setting_value = VALUES(setting_value),
description = VALUES(description),
updated_at = CURRENT_TIMESTAMP;
