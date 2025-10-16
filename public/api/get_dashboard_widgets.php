<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

header('Content-Type: application/json');

try {
    $db = getDB();
    
    // Get user's widget layout if exists
    $stmt = $db->prepare("SELECT widget_layout FROM dashboard_user_preferences WHERE staff_id = ?");
    $stmt->execute([$_SESSION['staff_id']]);
    $userPrefs = $stmt->fetch();
    $layout = $userPrefs ? json_decode($userPrefs['widget_layout'], true) : [];
    
    // Get active widgets
    $stmt = $db->prepare("SELECT * FROM dashboard_widgets WHERE is_active = 1 ORDER BY display_order");
    $stmt->execute();
    $widgets = $stmt->fetchAll();
    
    // Merge with user layout preferences
    foreach ($widgets as &$widget) {
        $widgetId = $widget['widget_id'];
        if (isset($layout[$widgetId])) {
            $widget['x'] = $layout[$widgetId]['x'];
            $widget['y'] = $layout[$widgetId]['y'];
            $widget['width'] = $layout[$widgetId]['w'];
            $widget['height'] = $layout[$widgetId]['h'];
        } else {
            // Default positions
            $widget['x'] = 0;
            $widget['y'] = 0;
            $widget['width'] = 3;
            $widget['height'] = 3;
        }
    }
    
    echo json_encode($widgets);
    
} catch (Exception $e) {
    Logger::error("Failed to get dashboard widgets: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to load widgets']);
}
?>
