<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$layout = $input['layout'] ?? [];

try {
    $db = getDB();
    
    // Convert GridStack layout to our format
    $widgetLayout = [];
    foreach ($layout as $item) {
        if (isset($item['id'])) {
            $widgetId = str_replace('widget-', '', $item['id']);
            $widgetLayout[$widgetId] = [
                'x' => $item['x'],
                'y' => $item['y'],
                'w' => $item['w'],
                'h' => $item['h']
            ];
        }
    }
    
    // Save or update user preferences
    $stmt = $db->prepare("
        INSERT INTO dashboard_user_preferences (staff_id, widget_layout) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE 
        widget_layout = ?, 
        updated_at = CURRENT_TIMESTAMP
    ");
    
    $layoutJson = json_encode($widgetLayout);
    $stmt->execute([$_SESSION['staff_id'], $layoutJson, $layoutJson]);
    
    logActivity("Dashboard layout saved");
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    Logger::error("Failed to save dashboard layout: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to save layout']);
}
?>
