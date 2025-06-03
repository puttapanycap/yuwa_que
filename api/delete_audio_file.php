<?php
require_once '../config/config.php';
requireLogin();

if (!hasPermission('manage_audio_system')) {
    die('ไม่มีสิทธิ์เข้าถึง');
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $audioId = $_POST['audio_id'] ?? null;
    
    if (!$audioId) {
        throw new Exception('ไม่ได้ระบุ ID ของไฟล์เสียง');
    }
    
    $db = getDB();
    
    // Get file information
    $stmt = $db->prepare("SELECT file_path, display_name FROM audio_files WHERE audio_id = ?");
    $stmt->execute([$audioId]);
    $audioFile = $stmt->fetch();
    
    if (!$audioFile) {
        throw new Exception('ไม่พบไฟล์เสียงที่ระบุ');
    }
    
    // Delete file from filesystem
    $filePath = ROOT_PATH . $audioFile['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    // Delete from database
    $stmt = $db->prepare("DELETE FROM audio_files WHERE audio_id = ?");
    $stmt->execute([$audioId]);
    
    logActivity('ลบไฟล์เสียง: ' . $audioFile['display_name']);
    
    echo json_encode([
        'success' => true,
        'message' => 'ลบไฟล์เสียงเรียบร้อยแล้ว'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
