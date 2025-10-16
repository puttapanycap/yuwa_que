<?php
require_once dirname(__DIR__, 2) . '/config/config.php';
requireLogin();

if (!hasPermission('manage_users')) {
    echo json_encode(['error' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

$roleId = $_GET['role_id'] ?? null;

if (!$roleId) {
    echo json_encode(['error' => 'Role ID required']);
    exit;
}

try {
    $db = getDB();
    
    // Get role info
    $stmt = $db->prepare("SELECT * FROM roles WHERE role_id = ?");
    $stmt->execute([$roleId]);
    $role = $stmt->fetch();
    
    if (!$role) {
        echo json_encode(['error' => 'Role not found']);
        exit;
    }
    
    // Get role permissions
    $stmt = $db->prepare("
        SELECT p.* 
        FROM permissions p
        JOIN role_permissions rp ON p.permission_id = rp.permission_id
        WHERE rp.role_id = ?
    ");
    $stmt->execute([$roleId]);
    $permissions = $stmt->fetchAll();
    
    echo json_encode([
        'role' => $role,
        'permissions' => $permissions
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error']);
}
?>
