<?php
// require_once '../config/config.php';

/**
 * ระบบศูนย์กลางการแจ้งเตือน (Notification Center)
 * 
 * ไฟล์นี้ประกอบด้วยฟังก์ชันสำหรับการจัดการการแจ้งเตือนทั้งหมดในระบบ
 * รวมถึงการสร้าง, การส่ง, การอ่าน, และการจัดการการแจ้งเตือน
 */

/**
 * สร้างการแจ้งเตือนใหม่
 * 
 * @param string $type ประเภทการแจ้งเตือน
 * @param string $title หัวข้อการแจ้งเตือน
 * @param string $message ข้อความการแจ้งเตือน
 * @param array $options ตัวเลือกเพิ่มเติม (recipient_id, priority, icon, link, etc.)
 * @return array ผลลัพธ์การสร้างการแจ้งเตือน
 */
function createNotification($type, $title, $message, $options = []) {
    try {
        $db = getDB();
        
        // ตรวจสอบประเภทการแจ้งเตือน
        $stmt = $db->prepare("SELECT * FROM notification_types WHERE type_code = ? AND is_active = 1");
        $stmt->execute([$type]);
        $notificationType = $stmt->fetch();
        
        if (!$notificationType) {
            return [
                'success' => false,
                'message' => 'ประเภทการแจ้งเตือนไม่ถูกต้องหรือไม่ได้เปิดใช้งาน'
            ];
        }
        
        // กำหนดค่าเริ่มต้น
        $defaults = [
            'recipient_id' => null,
            'recipient_role' => null,
            'priority' => $notificationType['default_priority'],
            'icon' => $notificationType['icon'],
            'link' => null,
            'is_system' => $notificationType['is_system'],
            'sender_id' => $_SESSION['staff_id'] ?? null,
            'expires_at' => null
        ];
        
        // รวมตัวเลือกกับค่าเริ่มต้น
        $options = array_merge($defaults, $options);
        
        // ตรวจสอบว่าต้องส่งให้ใครบ้าง
        $recipients = [];
        
        if ($options['recipient_id']) {
            // ส่งให้ผู้ใช้เฉพาะราย
            $recipients[] = $options['recipient_id'];
        } elseif ($options['recipient_role']) {
            // ส่งให้ผู้ใช้ตามบทบาท
            $stmt = $db->prepare("SELECT staff_id FROM staff_users WHERE role_id IN (SELECT role_id FROM roles WHERE role_name = ?)");
            $stmt->execute([$options['recipient_role']]);
            while ($row = $stmt->fetch()) {
                $recipients[] = $row['staff_id'];
            }
        } else {
            // ส่งให้ทุกคน (เฉพาะการแจ้งเตือนระบบ)
            if ($options['is_system']) {
                $stmt = $db->prepare("SELECT staff_id FROM staff_users WHERE is_active = 1");
                $stmt->execute();
                while ($row = $stmt->fetch()) {
                    $recipients[] = $row['staff_id'];
                }
            }
        }
        
        // ถ้าไม่มีผู้รับ
        if (empty($recipients)) {
            return [
                'success' => false,
                'message' => 'ไม่พบผู้รับการแจ้งเตือน'
            ];
        }
        
        $notificationIds = [];
        $db->beginTransaction();
        
        // สร้างการแจ้งเตือนสำหรับแต่ละผู้รับ
        foreach ($recipients as $recipientId) {
            // ตรวจสอบการตั้งค่าการแจ้งเตือนของผู้รับ
            $stmt = $db->prepare("
                SELECT * FROM notification_preferences 
                WHERE staff_id = ? AND notification_type = ?
            ");
            $stmt->execute([$recipientId, $type]);
            $preferences = $stmt->fetch();
            
            // ถ้าไม่มีการตั้งค่า ให้ใช้ค่าเริ่มต้น
            if (!$preferences) {
                $stmt = $db->prepare("
                    INSERT INTO notification_preferences 
                    (staff_id, notification_type, browser_enabled, sound_enabled) 
                    VALUES (?, ?, 1, 1)
                ");
                $stmt->execute([$recipientId, $type]);
            }
            
            // ถ้าปิดการแจ้งเตือนทั้งหมด ให้ข้ามไป
            if ($preferences && !$preferences['browser_enabled'] && !$preferences['email_enabled']) {
                continue;
            }
            
            // สร้างการแจ้งเตือน
            $stmt = $db->prepare("
                INSERT INTO notifications 
                (notification_type, title, message, icon, priority, link, is_system, recipient_id, sender_id, expires_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $type,
                $title,
                $message,
                $options['icon'],
                $options['priority'],
                $options['link'],
                $options['is_system'] ? 1 : 0,
                $recipientId,
                $options['sender_id'],
                $options['expires_at']
            ]);
            
            $notificationId = $db->lastInsertId();
            $notificationIds[] = $notificationId;
            
            // สร้างรายการส่งการแจ้งเตือน
            if ($preferences) {
                // Browser notification
                if ($preferences['browser_enabled']) {
                    $stmt = $db->prepare("
                        INSERT INTO notification_deliveries 
                        (notification_id, channel, status) 
                        VALUES (?, 'browser', 'pending')
                    ");
                    $stmt->execute([$notificationId]);
                }
                
                // Email notification
                if ($preferences['email_enabled']) {
                    $stmt = $db->prepare("
                        INSERT INTO notification_deliveries 
                        (notification_id, channel, status) 
                        VALUES (?, 'email', 'pending')
                    ");
                    $stmt->execute([$notificationId]);
                }
                
            }
        }
        
        $db->commit();
        
        // ส่งการแจ้งเตือนแบบ real-time ถ้ามีการตั้งค่า
        if (getSetting('enable_realtime_notifications', '1') === '1') {
            sendRealtimeNotifications($notificationIds);
        }
        
        // ส่งการแจ้งเตือนทางอีเมลและ LINE ในพื้นหลัง
        processNotificationQueue();
        
        return [
            'success' => true,
            'message' => 'สร้างการแจ้งเตือนสำเร็จ',
            'notification_ids' => $notificationIds,
            'recipient_count' => count($recipients)
        ];
        
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        
        error_log("Create notification error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
}

/**
 * ส่งการแจ้งเตือนแบบ real-time
 * 
 * @param array $notificationIds รายการ ID ของการแจ้งเตือน
 * @return void
 */
function sendRealtimeNotifications($notificationIds) {
    // ในระบบจริงอาจใช้ WebSocket, Server-Sent Events หรือ Push API
    // สำหรับตัวอย่างนี้จะเก็บไว้ใน session เพื่อให้ JavaScript ดึงไปแสดงผล
    
    if (empty($notificationIds)) {
        return;
    }
    
    try {
        $db = getDB();
        $placeholders = implode(',', array_fill(0, count($notificationIds), '?'));
        
        $stmt = $db->prepare("
            SELECT n.*, nt.default_sound, su.full_name as sender_name
            FROM notifications n
            LEFT JOIN notification_types nt ON n.notification_type = nt.type_code
            LEFT JOIN staff_users su ON n.sender_id = su.staff_id
            WHERE n.notification_id IN ($placeholders)
        ");
        $stmt->execute($notificationIds);
        $notifications = $stmt->fetchAll();
        
        // อัปเดตสถานะการส่ง
        $stmt = $db->prepare("
            UPDATE notification_deliveries
            SET status = 'sent', sent_at = NOW()
            WHERE notification_id IN ($placeholders) AND channel = 'browser'
        ");
        $stmt->execute($notificationIds);
        
        // เก็บข้อมูลใน session สำหรับ JavaScript
        if (!isset($_SESSION['realtime_notifications'])) {
            $_SESSION['realtime_notifications'] = [];
        }
        
        foreach ($notifications as $notification) {
            $_SESSION['realtime_notifications'][] = [
                'id' => $notification['notification_id'],
                'type' => $notification['notification_type'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'icon' => $notification['icon'],
                'priority' => $notification['priority'],
                'sound' => $notification['default_sound'],
                'link' => $notification['link'],
                'sender' => $notification['sender_name'],
                'time' => date('Y-m-d H:i:s')
            ];
        }
        
    } catch (Exception $e) {
        error_log("Send realtime notifications error: " . $e->getMessage());
    }
}

/**
 * ประมวลผลคิวการแจ้งเตือน (อีเมล, LINE)
 * 
 * @return void
 */
function processNotificationQueue() {
    // ในระบบจริงควรทำงานในพื้นหลังหรือใช้ cron job
    // สำหรับตัวอย่างนี้จะทำงานแบบง่ายๆ
    
    try {
        $db = getDB();
        
        // ดึงการแจ้งเตือนที่รอส่ง
        $stmt = $db->prepare("
            SELECT nd.*, n.recipient_id, n.title, n.message, n.notification_type
            FROM notification_deliveries nd
            JOIN notifications n ON nd.notification_id = n.notification_id
            WHERE nd.status = 'pending'
            AND nd.channel IN ('email', 'line')
            LIMIT 10
        ");
        $stmt->execute();
        $pendingDeliveries = $stmt->fetchAll();
        
        foreach ($pendingDeliveries as $delivery) {
            $success = false;
            $errorMessage = '';
            
            // ส่งอีเมล
            if ($delivery['channel'] === 'email') {
                $success = sendEmailNotification($delivery);
            }
            
            // ส่ง LINE
            if ($delivery['channel'] === 'line') {
                $success = sendLineNotification($delivery);
            }
            
            // อัปเดตสถานะ
            $status = $success ? 'sent' : 'failed';
            $stmt = $db->prepare("
                UPDATE notification_deliveries
                SET status = ?, sent_at = NOW(), error_message = ?
                WHERE delivery_id = ?
            ");
            $stmt->execute([$status, $errorMessage, $delivery['delivery_id']]);
        }
        
    } catch (Exception $e) {
        error_log("Process notification queue error: " . $e->getMessage());
    }
}

/**
 * ส่งการแจ้งเตือนทางอีเมล
 * 
 * @param array $delivery ข้อมูลการส่ง
 * @return bool สถานะการส่ง
 */
function sendEmailNotification($delivery) {
    try {
        // ดึงอีเมลของผู้รับ
        $db = getDB();
        $stmt = $db->prepare("SELECT email FROM staff_users WHERE staff_id = ?");
        $stmt->execute([$delivery['recipient_id']]);
        $recipient = $stmt->fetch();
        
        if (!$recipient || empty($recipient['email'])) {
            return false;
        }
        
        // ในระบบจริงจะใช้ PHPMailer หรือ SMTP
        // สำหรับตัวอย่างนี้จะจำลองการส่งอีเมล
        $to = $recipient['email'];
        $subject = $delivery['title'];
        $message = $delivery['message'];
        
        // จำลองการส่งอีเมล (ในระบบจริงจะใช้ mail() หรือ SMTP)
        // mail($to, $subject, $message);
        
        // บันทึก log
        logActivity("ส่งการแจ้งเตือนทางอีเมล: {$delivery['notification_id']} ถึง {$to}");
        
        return true;
        
    } catch (Exception $e) {
        error_log("Send email notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * ส่งการแจ้งเตือนทาง LINE
 * 
 * @param array $delivery ข้อมูลการส่ง
 * @return bool สถานะการส่ง
 */
function sendLineNotification($delivery) {
    try {
        // ดึง LINE Token ของผู้รับ
        $db = getDB();
        $stmt = $db->prepare("SELECT line_token FROM staff_users WHERE staff_id = ?");
        $stmt->execute([$delivery['recipient_id']]);
        $recipient = $stmt->fetch();
        
        if (!$recipient || empty($recipient['line_token'])) {
            return false;
        }
        
        $lineToken = $recipient['line_token'];
        $message = "{$delivery['title']}\n{$delivery['message']}";
        
        // ในระบบจริงจะใช้ LINE Notify API
        // สำหรับตัวอย่างนี้จะจำลองการส่ง LINE
        
        /*
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://notify-api.line.me/api/notify');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "message=$message");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer ' . $lineToken
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        */
        
        // บันทึก log
        logActivity("ส่งการแจ้งเตือนทาง LINE: {$delivery['notification_id']}");
        
        return true;
        
    } catch (Exception $e) {
        error_log("Send LINE notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * ดึงการแจ้งเตือนของผู้ใช้
 * 
 * @param int $staffId ID ของเจ้าหน้าที่
 * @param array $options ตัวเลือกเพิ่มเติม (limit, offset, unread_only)
 * @return array รายการการแจ้งเตือน
 */
function getUserNotifications($staffId, $options = []) {
    try {
        $db = getDB();
        
        // กำหนดค่าเริ่มต้น
        $defaults = [
            'limit' => 20,
            'offset' => 0,
            'unread_only' => false
        ];
        
        // รวมตัวเลือกกับค่าเริ่มต้น
        $options = array_merge($defaults, $options);
        
        // สร้าง WHERE clause
        $whereClause = "n.recipient_id = ?";
        $params = [$staffId];
        
        if ($options['unread_only']) {
            $whereClause .= " AND n.is_read = 0";
        }
        
        // ดึงการแจ้งเตือน
        $stmt = $db->prepare("
            SELECT n.*, nt.default_sound, su.full_name as sender_name
            FROM notifications n
            LEFT JOIN notification_types nt ON n.notification_type = nt.type_code
            LEFT JOIN staff_users su ON n.sender_id = su.staff_id
            WHERE $whereClause
            ORDER BY n.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $params[] = $options['limit'];
        $params[] = $options['offset'];
        $stmt->execute($params);
        $notifications = $stmt->fetchAll();
        
        // นับจำนวนการแจ้งเตือนที่ยังไม่อ่าน
        $stmt = $db->prepare("
            SELECT COUNT(*) as unread_count
            FROM notifications
            WHERE recipient_id = ? AND is_read = 0
        ");
        $stmt->execute([$staffId]);
        $unreadCount = $stmt->fetch()['unread_count'];
        
        return [
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'total' => count($notifications),
            'has_more' => count($notifications) == $options['limit']
        ];
        
    } catch (Exception $e) {
        error_log("Get user notifications error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
}

/**
 * อ่านการแจ้งเตือน
 * 
 * @param int $notificationId ID ของการแจ้งเตือน
 * @param int $staffId ID ของเจ้าหน้าที่
 * @return array ผลลัพธ์การอ่านการแจ้งเตือน
 */
function markNotificationAsRead($notificationId, $staffId) {
    try {
        $db = getDB();
        
        // ตรวจสอบว่าการแจ้งเตือนเป็นของผู้ใช้หรือไม่
        $stmt = $db->prepare("
            SELECT * FROM notifications
            WHERE notification_id = ? AND recipient_id = ?
        ");
        $stmt->execute([$notificationId, $staffId]);
        $notification = $stmt->fetch();
        
        if (!$notification) {
            return [
                'success' => false,
                'message' => 'ไม่พบการแจ้งเตือนหรือไม่มีสิทธิ์เข้าถึง'
            ];
        }
        
        // อัปเดตสถานะการอ่าน
        $stmt = $db->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE notification_id = ?
        ");
        $stmt->execute([$notificationId]);
        
        // อัปเดตสถานะการส่ง
        $stmt = $db->prepare("
            UPDATE notification_deliveries
            SET status = 'read', read_at = NOW()
            WHERE notification_id = ?
        ");
        $stmt->execute([$notificationId]);
        
        return [
            'success' => true,
            'message' => 'อ่านการแจ้งเตือนเรียบร้อยแล้ว'
        ];
        
    } catch (Exception $e) {
        error_log("Mark notification as read error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
}

/**
 * อ่านการแจ้งเตือนทั้งหมด
 * 
 * @param int $staffId ID ของเจ้าหน้าที่
 * @return array ผลลัพธ์การอ่านการแจ้งเตือน
 */
function markAllNotificationsAsRead($staffId) {
    try {
        $db = getDB();
        
        // อัปเดตสถานะการอ่าน
        $stmt = $db->prepare("
            UPDATE notifications
            SET is_read = 1
            WHERE recipient_id = ? AND is_read = 0
        ");
        $stmt->execute([$staffId]);
        
        $count = $stmt->rowCount();
        
        // อัปเดตสถานะการส่ง
        $stmt = $db->prepare("
            UPDATE notification_deliveries nd
            JOIN notifications n ON nd.notification_id = n.notification_id
            SET nd.status = 'read', nd.read_at = NOW()
            WHERE n.recipient_id = ? AND nd.status IN ('sent', 'delivered')
        ");
        $stmt->execute([$staffId]);
        
        return [
            'success' => true,
            'message' => "อ่านการแจ้งเตือนทั้งหมด $count รายการเรียบร้อยแล้ว"
        ];
        
    } catch (Exception $e) {
        error_log("Mark all notifications as read error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
}

/**
 * ลบการแจ้งเตือน
 * 
 * @param int $notificationId ID ของการแจ้งเตือน
 * @param int $staffId ID ของเจ้าหน้าที่
 * @return array ผลลัพธ์การลบการแจ้งเตือน
 */
function dismissNotification($notificationId, $staffId) {
    try {
        $db = getDB();
        
        // ตรวจสอบว่าการแจ้งเตือนเป็นของผู้ใช้หรือไม่
        $stmt = $db->prepare("
            SELECT * FROM notifications
            WHERE notification_id = ? AND recipient_id = ?
        ");
        $stmt->execute([$notificationId, $staffId]);
        $notification = $stmt->fetch();
        
        if (!$notification) {
            return [
                'success' => false,
                'message' => 'ไม่พบการแจ้งเตือนหรือไม่มีสิทธิ์เข้าถึง'
            ];
        }
        
        // อัปเดตสถานะการปิด
        $stmt = $db->prepare("
            UPDATE notifications
            SET is_dismissed = 1, is_read = 1
            WHERE notification_id = ?
        ");
        $stmt->execute([$notificationId]);
        
        return [
            'success' => true,
            'message' => 'ปิดการแจ้งเตือนเรียบร้อยแล้ว'
        ];
        
    } catch (Exception $e) {
        error_log("Dismiss notification error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
}

/**
 * ลบการแจ้งเตือนทั้งหมด
 * 
 * @param int $staffId ID ของเจ้าหน้าที่
 * @return array ผลลัพธ์การลบการแจ้งเตือน
 */
function dismissAllNotifications($staffId) {
    try {
        $db = getDB();
        
        // อัปเดตสถานะการปิด
        $stmt = $db->prepare("
            UPDATE notifications
            SET is_dismissed = 1, is_read = 1
            WHERE recipient_id = ? AND is_dismissed = 0
        ");
        $stmt->execute([$staffId]);
        
        $count = $stmt->rowCount();
        
        return [
            'success' => true,
            'message' => "ปิดการแจ้งเตือนทั้งหมด $count รายการเรียบร้อยแล้ว"
        ];
        
    } catch (Exception $e) {
        error_log("Dismiss all notifications error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
}

/**
 * บันทึกการตั้งค่าการแจ้งเตือน
 * 
 * @param int $staffId ID ของเจ้าหน้าที่
 * @param array $preferences การตั้งค่าการแจ้งเตือน
 * @return array ผลลัพธ์การบันทึกการตั้งค่า
 */
function saveNotificationPreferences($staffId, $preferences) {
    try {
        $db = getDB();
        $db->beginTransaction();
        
        foreach ($preferences as $type => $settings) {
            // ตรวจสอบว่ามีการตั้งค่าอยู่แล้วหรือไม่
            $stmt = $db->prepare("
                SELECT * FROM notification_preferences
                WHERE staff_id = ? AND notification_type = ?
            ");
            $stmt->execute([$staffId, $type]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // อัปเดตการตั้งค่า
                $stmt = $db->prepare("
                    UPDATE notification_preferences
                    SET email_enabled = ?, browser_enabled = ?, sound_enabled = ?
                    WHERE preference_id = ?
                ");
                $stmt->execute([
                    $settings['email'] ? 1 : 0,
                    $settings['browser'] ? 1 : 0,
                    $settings['sound'] ? 1 : 0,
                    $existing['preference_id']
                ]);
            } else {
                // สร้างการตั้งค่าใหม่
                $stmt = $db->prepare("
                    INSERT INTO notification_preferences
                    (staff_id, notification_type, email_enabled, browser_enabled, sound_enabled)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $staffId,
                    $type,
                    $settings['email'] ? 1 : 0,
                    $settings['browser'] ? 1 : 0,
                    $settings['sound'] ? 1 : 0
                ]);
            }
        }
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'บันทึกการตั้งค่าการแจ้งเตือนเรียบร้อยแล้ว'
        ];
        
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        
        error_log("Save notification preferences error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
}

/**
 * ดึงการตั้งค่าการแจ้งเตือนของผู้ใช้
 * 
 * @param int $staffId ID ของเจ้าหน้าที่
 * @return array การตั้งค่าการแจ้งเตือน
 */
function getNotificationPreferences($staffId) {
    try {
        $db = getDB();
        
        // ดึงประเภทการแจ้งเตือนทั้งหมด
        $stmt = $db->prepare("
            SELECT * FROM notification_types
            WHERE is_active = 1
            ORDER BY is_system DESC, type_name ASC
        ");
        $stmt->execute();
        $types = $stmt->fetchAll();
        
        // ดึงการตั้งค่าของผู้ใช้
        $stmt = $db->prepare("
            SELECT * FROM notification_preferences
            WHERE staff_id = ?
        ");
        $stmt->execute([$staffId]);
        $userPreferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // แปลงเป็น associative array
        $preferences = [];
        foreach ($userPreferences as $pref) {
            $preferences[$pref['notification_type']] = $pref;
        }
        
        // สร้างผลลัพธ์
        $result = [];
        foreach ($types as $type) {
            $pref = $preferences[$type['type_code']] ?? null;
            
            $result[$type['type_code']] = [
                'type_name' => $type['type_name'],
                'description' => $type['description'],
                'icon' => $type['icon'],
                'is_system' => $type['is_system'] ? true : false,
                'email' => $pref ? ($pref['email_enabled'] ? true : false) : false,
                'browser' => $pref ? ($pref['browser_enabled'] ? true : false) : true,
                'sound' => $pref ? ($pref['sound_enabled'] ? true : false) : true
            ];
        }
        
        return [
            'success' => true,
            'preferences' => $result
        ];
        
    } catch (Exception $e) {
        error_log("Get notification preferences error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
}

/**
 * ดึงการแจ้งเตือนที่ยังไม่อ่านสำหรับแสดงผลแบบ real-time
 * 
 * @return array การแจ้งเตือนที่ยังไม่อ่าน
 */
function getRealtimeNotifications() {
    $notifications = $_SESSION['realtime_notifications'] ?? [];
    
    // ล้างการแจ้งเตือนหลังจากดึงไปแล้ว
    $_SESSION['realtime_notifications'] = [];
    
    return $notifications;
}

/**
 * ส่งการแจ้งเตือนเมื่อมีการเรียกคิว
 * 
 * @param array $queueData ข้อมูลคิว
 * @param array $servicePointData ข้อมูลจุดบริการ
 * @return void
 */
function sendQueueCalledNotification($queueData, $servicePointData) {
    $title = "เรียกคิว {$queueData['queue_number']}";
    $message = "คิวหมายเลข {$queueData['queue_number']} ถูกเรียกที่ {$servicePointData['point_name']}";
    
    createNotification('queue_called', $title, $message, [
        'priority' => 'high',
        'icon' => 'bullhorn',
        'link' => "/staff/dashboard.php?service_point={$servicePointData['service_point_id']}"
    ]);
}

/**
 * ส่งการแจ้งเตือนเมื่อมีการ Reset คิวอัตโนมัติ
 * 
 * @param array $resetData ข้อมูลการ Reset
 * @return void
 */
function sendAutoResetNotification($resetData) {
    $title = "Auto Reset: {$resetData['schedule_name']}";
    $message = "การ Reset คิวอัตโนมัติ \"{$resetData['schedule_name']}\" {$resetData['status']}";
    
    if ($resetData['status'] === 'success') {
        $message .= " (Reset {$resetData['reset_count']} ประเภท)";
    } else {
        $message .= " - {$resetData['message']}";
    }
    
    createNotification('auto_reset', $title, $message, [
        'priority' => $resetData['status'] === 'success' ? 'normal' : 'high',
        'icon' => 'sync',
        'recipient_role' => 'Admin',
        'link' => "/admin/auto_reset_logs.php"
    ]);
}

/**
 * ส่งการแจ้งเตือนเมื่อมีคิวรอนาน
 * 
 * @param array $queueData ข้อมูลคิว
 * @param int $waitTime เวลาที่รอ (นาที)
 * @return void
 */
function sendLongWaitingQueueNotification($queueData, $waitTime) {
    $title = "คิวรอนาน: {$queueData['queue_number']}";
    $message = "คิวหมายเลข {$queueData['queue_number']} รอนานกว่า {$waitTime} นาที";
    
    createNotification('queue_waiting_long', $title, $message, [
        'priority' => 'high',
        'icon' => 'clock',
        'recipient_role' => 'Admin',
        'link' => "/staff/dashboard.php?service_point={$queueData['current_service_point_id']}"
    ]);
}

/**
 * ส่งการแจ้งเตือนระบบ
 * 
 * @param string $title หัวข้อ
 * @param string $message ข้อความ
 * @param string $priority ความสำคัญ
 * @return void
 */
function sendSystemNotification($title, $message, $priority = 'normal') {
    createNotification('system_alert', $title, $message, [
        'priority' => $priority,
        'icon' => 'info-circle',
        'is_system' => true
    ]);
}

/**
 * ส่งข้อความระหว่างเจ้าหน้าที่
 * 
 * @param int $fromStaffId ID ของผู้ส่ง
 * @param int $toStaffId ID ของผู้รับ
 * @param string $message ข้อความ
 * @return array ผลลัพธ์การส่งข้อความ
 */
function sendStaffMessage($fromStaffId, $toStaffId, $message) {
    try {
        $db = getDB();
        
        // ดึงข้อมูลผู้ส่ง
        $stmt = $db->prepare("SELECT full_name FROM staff_users WHERE staff_id = ?");
        $stmt->execute([$fromStaffId]);
        $sender = $stmt->fetch();
        
        if (!$sender) {
            return [
                'success' => false,
                'message' => 'ไม่พบข้อมูลผู้ส่ง'
            ];
        }
        
        $title = "ข้อความจาก {$sender['full_name']}";
        
        $result = createNotification('staff_message', $title, $message, [
            'priority' => 'normal',
            'icon' => 'comment',
            'recipient_id' => $toStaffId,
            'sender_id' => $fromStaffId
        ]);
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Send staff message error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
}

/**
 * ลบการแจ้งเตือนที่หมดอายุ
 * 
 * @return void
 */
function cleanExpiredNotifications() {
    try {
        $db = getDB();
        
        // ลบการแจ้งเตือนที่หมดอายุ
        $stmt = $db->prepare("
            DELETE FROM notifications
            WHERE expires_at IS NOT NULL AND expires_at < NOW()
        ");
        $stmt->execute();
        
        // ลบการแจ้งเตือนที่เก่ากว่า 30 วัน
        $stmt = $db->prepare("
            DELETE FROM notifications
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Clean expired notifications error: " . $e->getMessage());
    }
}

// ทำความสะอาดการแจ้งเตือนที่หมดอายุเมื่อโหลดไฟล์
if (rand(1, 10) === 1) { // สุ่ม 10% เพื่อไม่ให้ทำงานทุกครั้ง
    cleanExpiredNotifications();
}
?>
