-- เพิ่มการตั้งค่าสำหรับระบบเสียง
INSERT INTO settings (setting_key, setting_value, description) VALUES
('read_letters_in_thai', '1', 'อ่านตัวอักษรเป็นภาษาไทย (1=เปิด, 0=ปิด)'),
('separate_queue_characters', '1', 'แยกตัวอักษรและตัวเลขในหมายเลขคิว (1=เปิด, 0=ปิด)'),
('pause_between_characters', '500', 'ช่วงหยุดระหว่างตัวอักษร (มิลลิวินาที)'),
('queue_number_repeat', '2', 'จำนวนครั้งที่อ่านหมายเลขคิว')
ON DUPLICATE KEY UPDATE 
setting_value = VALUES(setting_value),
description = VALUES(description);

-- เพิ่มเทมเพลตเสียงสำหรับการอ่านแยกตัว
INSERT INTO voice_templates (template_name, template_text, is_default, description) VALUES
('แยกตัวอักษร', 'หมายเลข {queue_number} เชิญที่ {service_point_name}', 0, 'เทมเพลตสำหรับการอ่านแยกตัวอักษร'),
('แยกตัวพร้อมทำซ้ำ', 'หมายเลข {queue_number} หมายเลข {queue_number} เชิญที่ {service_point_name}', 0, 'อ่านหมายเลขคิว 2 ครั้ง')
ON DUPLICATE KEY UPDATE 
template_text = VALUES(template_text),
description = VALUES(description);
