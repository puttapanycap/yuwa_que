-- แก้ไข Foreign Key Constraints ของ service_flow_history
-- เพื่อให้สามารถบันทึกข้อมูลจาก Kiosk ได้ (staff_id = NULL)

-- ลบ constraint เดิม
ALTER TABLE service_flow_history DROP FOREIGN KEY service_flow_history_ibfk_4;

-- เพิ่ม constraint ใหม่ที่อนุญาตให้ staff_id เป็น NULL ได้
ALTER TABLE service_flow_history 
ADD CONSTRAINT service_flow_history_ibfk_4 
FOREIGN KEY (staff_id) REFERENCES staff_users(staff_id) ON DELETE SET NULL;

-- เพิ่ม index สำหรับการค้นหาที่เร็วขึ้น
CREATE INDEX idx_service_flow_queue_id ON service_flow_history(queue_id);
CREATE INDEX idx_service_flow_timestamp ON service_flow_history(timestamp);
CREATE INDEX idx_service_flow_action ON service_flow_history(action);

-- เพิ่มข้อมูล service flow history สำหรับคิวที่มีอยู่แล้วแต่ไม่มี history
INSERT INTO service_flow_history (queue_id, from_service_point_id, to_service_point_id, staff_id, action, notes, timestamp)
SELECT 
    q.queue_id,
    NULL as from_service_point_id,
    q.current_service_point_id as to_service_point_id,
    NULL as staff_id,
    'created' as action,
    'สร้างคิวจาก Kiosk (เพิ่มทีหลัง)' as notes,
    q.creation_time as timestamp
FROM queues q
LEFT JOIN service_flow_history sfh ON q.queue_id = sfh.queue_id
WHERE sfh.queue_id IS NULL
AND q.creation_time >= CURDATE() - INTERVAL 7 DAY;
