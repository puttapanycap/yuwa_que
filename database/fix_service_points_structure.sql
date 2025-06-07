-- แก้ไขโครงสร้างตาราง service_points
-- ตรวจสอบและเพิ่มคอลัมน์ที่จำเป็น

-- ตรวจสอบว่ามีคอลัมน์ display_order หรือไม่
ALTER TABLE service_points 
ADD COLUMN IF NOT EXISTS display_order INT DEFAULT 0;

-- ตรวจสอบว่ามีคอลัมน์ queue_type_id หรือไม่ (สำหรับการกำหนด service point ตาม queue type)
ALTER TABLE service_points 
ADD COLUMN IF NOT EXISTS queue_type_id INT NULL,
ADD FOREIGN KEY IF NOT EXISTS (queue_type_id) REFERENCES queue_types(queue_type_id);

-- อัปเดต display_order สำหรับ service points ที่มีอยู่
UPDATE service_points SET display_order = 1 WHERE position_key = 'SCREENING_01';
UPDATE service_points SET display_order = 2 WHERE position_key = 'DOCTOR_01';
UPDATE service_points SET display_order = 3 WHERE position_key = 'DOCTOR_02';
UPDATE service_points SET display_order = 4 WHERE position_key = 'PHARMACY_01';
UPDATE service_points SET display_order = 5 WHERE position_key = 'CASHIER_01';
UPDATE service_points SET display_order = 6 WHERE position_key = 'RECORDS_01';

-- เพิ่ม index สำหรับประสิทธิภาพ
CREATE INDEX IF NOT EXISTS idx_service_points_display_order ON service_points(display_order);
CREATE INDEX IF NOT EXISTS idx_service_points_queue_type ON service_points(queue_type_id);
