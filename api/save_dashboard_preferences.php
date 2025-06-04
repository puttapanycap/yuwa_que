<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$refreshInterval = $input['refresh_interval'] ?? 30;
$theme = $input['theme'] ?? 'light';

try {
    $db = getDB();
    
    $stmt = $db->prepare("
        INSERT INTO dashboard_user_preferences (staff_id, refresh_interval, theme) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        refresh_interval = ?, 
        theme = ?,
        updated_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->execute([
        $_SESSION['staff_id'], 
        $refreshInterval, 
        $theme, 
        $refreshInterval, 
        $theme
    ]);
    
    logActivity("Dashboard preferences updated");
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    Logger::error("Failed to save dashboard preferences: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to save preferences']);
}
?>
