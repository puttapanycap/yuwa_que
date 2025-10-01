<?php
require_once '../config/config.php';
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

    // Remove deprecated currency layout widgets entirely
    $deprecatedWidgetIds = [];
    $filteredWidgets = [];
    foreach ($widgets as $widget) {
        if (($widget['widget_type'] ?? '') === 'currency_section_layout') {
            $deprecatedWidgetIds[] = (int) $widget['widget_id'];
            continue;
        }
        $filteredWidgets[] = $widget;
    }
    $widgets = $filteredWidgets;

    if (!empty($deprecatedWidgetIds)) {
        // Clean up saved layout preferences that may still reference removed widgets
        $layoutChanged = false;
        foreach ($deprecatedWidgetIds as $deprecatedId) {
            if (isset($layout[$deprecatedId])) {
                unset($layout[$deprecatedId]);
                $layoutChanged = true;
            }
        }

        if ($layoutChanged) {
            $stmt = $db->prepare("UPDATE dashboard_user_preferences SET widget_layout = ?, updated_at = CURRENT_TIMESTAMP WHERE staff_id = ?");
            $stmt->execute([json_encode($layout), $_SESSION['staff_id']]);
        }

        // Remove the deprecated widgets from the dashboard configuration store
        $placeholders = implode(',', array_fill(0, count($deprecatedWidgetIds), '?'));
        $stmt = $db->prepare("DELETE FROM dashboard_widgets WHERE widget_id IN ($placeholders)");
        $stmt->execute($deprecatedWidgetIds);
    }

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
