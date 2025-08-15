<?php
require_once '../config/config.php';
header('Content-Type: application/json');

$filepath = __DIR__ . '/../config/queue_categories.json';
if (!file_exists($filepath)) {
    file_put_contents($filepath, json_encode([]));
}

$categories = json_decode(file_get_contents($filepath), true);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode(['success' => true, 'categories' => $categories]);
    exit;
}

// Use either JSON body or form data
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST;
}

if ($method === 'POST') {
    $action = $data['action'] ?? 'create';
    if ($action === 'assign') {
        $queueId = $data['queue_id'] ?? null;
        $categoryId = $data['category_id'] ?? null;
        if (!$queueId || !$categoryId) {
            echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบ']);
            exit;
        }
        foreach ($categories as &$cat) {
            if ($cat['id'] == $categoryId) {
                $cat['count'] = ($cat['count'] ?? 0) + 1;
                break;
            }
        }
        file_put_contents($filepath, json_encode($categories));
        echo json_encode(['success' => true]);
        exit;
    }

    $name = trim($data['name'] ?? '');
    $start = $data['start'] ?? '';
    $end = $data['end'] ?? '';
    if ($name === '' || $start === '' || $end === '') {
        echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบ']);
        exit;
    }

    $id = time();
    $categories[] = [
        'id' => $id,
        'name' => $name,
        'start' => $start,
        'end' => $end,
        'count' => 0
    ];
    file_put_contents($filepath, json_encode($categories));
    echo json_encode(['success' => true, 'id' => $id]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
