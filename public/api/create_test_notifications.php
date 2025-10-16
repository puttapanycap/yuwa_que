<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json');

try {
    $db = getDB();
    
    // สร้าง test notifications
    $testNotifications = [
        [
            'type' => 'queue_called',
            'title' => 'เรียกคิว A001',
            'message' => 'กรุณาไปที่เคาน์เตอร์ 1',
            'priority' => 'high'
        ],
        [
            'type' => 'announcement', 
            'title' => 'ประกาศ',
            'message' => 'ระบบจะปิดปรับปรุงในวันอาทิตย์',
            'priority' => 'normal'
        ],
        [
            'type' => 'system_alert',
            'title' => 'แจ้งเตือนระบบ',
            'message' => 'กรุณาตรวจสอบการเชื่อมต่อเครือข่าย',
            'priority' => 'urgent'
        ]
    ];
    
    $created = [];
    
    foreach ($testNotifications as $notification) {
        // ตรวจสอบว่าตารางมีคอลัมน์อะไรบ้าง
        $checkColumns = "SHOW COLUMNS FROM notifications";
        $columnsStmt = $db->query($checkColumns);
        $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // สร้าง SQL ตามคอลัมน์ที่มีอยู่
        $insertColumns = ['notification_type', 'title', 'message', 'priority', 'created_at'];
        $insertValues = ['?', '?', '?', '?', 'NOW()'];
        $params = [
            $notification['type'],
            $notification['title'], 
            $notification['message'],
            $notification['priority']
        ];
        
        // เพิ่มคอลัมน์เสริมหากมี
        if (in_array('is_public', $columns)) {
            $insertColumns[] = 'is_public';
            $insertValues[] = '1';
        }
        
        if (in_array('is_active', $columns)) {
            $insertColumns[] = 'is_active';
            $insertValues[] = '1';
        }
        
        if (in_array('service_point_id', $columns)) {
            $insertColumns[] = 'service_point_id';
            $insertValues[] = 'NULL';
        }
        
        $sql = "INSERT INTO notifications (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertValues) . ")";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        $created[] = [
            'id' => $db->lastInsertId(),
            'type' => $notification['type'],
            'title' => $notification['title']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'สร้าง test notifications สำเร็จ',
        'created' => $created,
        'count' => count($created)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
