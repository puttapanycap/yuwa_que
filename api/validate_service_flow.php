<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$queueTypeId = $_POST['queue_type_id'] ?? null;
$flows = $_POST['flows'] ?? [];

if (!$queueTypeId) {
    echo json_encode(['success' => false, 'message' => 'Missing queue_type_id']);
    exit;
}

try {
    $db = getDB();
    $issues = [];
    
    // Validate flows
    foreach ($flows as $index => $flow) {
        $stepNumber = $index + 1;
        
        // Check if to_service_point_id exists
        if (empty($flow['to_service_point_id'])) {
            $issues[] = "ขั้นตอนที่ {$stepNumber}: ไม่ได้เลือกจุดบริการปลายทาง";
            continue;
        }
        
        // Check if service point exists
        $stmt = $db->prepare("SELECT service_point_id FROM service_points WHERE service_point_id = ? AND is_active = 1");
        $stmt->execute([$flow['to_service_point_id']]);
        if (!$stmt->fetch()) {
            $issues[] = "ขั้นตอนที่ {$stepNumber}: จุดบริการปลายทางไม่ถูกต้องหรือไม่ได้เปิดใช้งาน";
        }
        
        // Check from service point if specified
        if (!empty($flow['from_service_point_id'])) {
            $stmt = $db->prepare("SELECT service_point_id FROM service_points WHERE service_point_id = ? AND is_active = 1");
            $stmt->execute([$flow['from_service_point_id']]);
            if (!$stmt->fetch()) {
                $issues[] = "ขั้นตอนที่ {$stepNumber}: จุดบริการต้นทางไม่ถูกต้องหรือไม่ได้เปิดใช้งาน";
            }
        }
        
        // Check for circular reference
        if (!empty($flow['from_service_point_id']) && $flow['from_service_point_id'] == $flow['to_service_point_id']) {
            $issues[] = "ขั้นตอนที่ {$stepNumber}: จุดบริการต้นทางและปลายทางไม่สามารถเป็นจุดเดียวกันได้";
        }
    }
    
    // Check for duplicate sequences
    $sequences = array_column($flows, 'sequence_order');
    $duplicates = array_diff_assoc($sequences, array_unique($sequences));
    if (!empty($duplicates)) {
        $issues[] = "มีลำดับขั้นตอนที่ซ้ำกัน";
    }
    
    // Check for logical flow issues
    $servicePointConnections = [];
    foreach ($flows as $flow) {
        if (!empty($flow['from_service_point_id'])) {
            $servicePointConnections[$flow['from_service_point_id']][] = $flow['to_service_point_id'];
        }
    }
    
    // Check for potential infinite loops
    foreach ($servicePointConnections as $fromId => $toIds) {
        foreach ($toIds as $toId) {
            if (isset($servicePointConnections[$toId]) && in_array($fromId, $servicePointConnections[$toId])) {
                $issues[] = "พบการวนซ้ำในเส้นทางการบริการ";
                break 2;
            }
        }
    }
    
    if (empty($issues)) {
        echo json_encode(['success' => true, 'message' => 'Service Flow ถูกต้อง']);
    } else {
        echo json_encode(['success' => false, 'issues' => $issues]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
