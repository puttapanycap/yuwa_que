<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $callId = $_POST['call_id'] ?? null;
    $status = $_POST['status'] ?? null;
    $audioFiles = $_POST['audio_files'] ?? [];

    if (!is_array($audioFiles)) {
        $audioFiles = $audioFiles !== '' ? [$audioFiles] : [];
    }
    
    if (!$callId || !$status) {
        throw new Exception('ข้อมูลไม่ครบถ้วน');
    }
    
    $allowedStatuses = ['pending', 'played', 'failed'];
    if (!in_array($status, $allowedStatuses)) {
        throw new Exception('สถานะไม่ถูกต้อง');
    }
    
    $db = getDB();
    $stmt = $db->prepare("UPDATE audio_call_history SET audio_status = ? WHERE call_id = ?");
    $stmt->execute([$status, $callId]);
    
    $deletedFiles = [];
    $publicRoot = ROOT_PATH . '/public';
    $baseStoragePath = realpath($publicRoot . '/storage/tts');

    if ($baseStoragePath !== false) {
        foreach ($audioFiles as $file) {
            if (!is_string($file) || $file === '') {
                continue;
            }

            $relativePath = ltrim($file, '/');
            $absolutePath = $publicRoot . '/' . $relativePath;
            $absoluteDir = realpath(dirname($absolutePath));

            if ($absoluteDir === false || strpos($absoluteDir, $baseStoragePath) !== 0) {
                continue;
            }

            if (is_file($absolutePath)) {
                if (@unlink($absolutePath)) {
                    $deletedFiles[] = $relativePath;
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'อัพเดทสถานะเรียบร้อยแล้ว',
        'deleted_files' => $deletedFiles
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
