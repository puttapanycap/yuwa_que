<?php
require_once dirname(__DIR__, 2) . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

$idCardInput = $_GET['id_card_number'] ?? ($_POST['id_card_number'] ?? null);
$idCardNumber = null;

if ($idCardInput !== null) {
    $idCardNumber = preg_replace('/[^0-9]/', '', (string) $idCardInput);
}

$appointments = [
    [
        'appointment_id' => 'APT-20251001-001',
        'clinic' => 'อายุรกรรม',
        'doctor' => 'นพ. ธนกฤต ใจดี',
        'appointment_time' => '2025-10-01T09:30:00+07:00',
        'status' => 'confirmed',
        'notes' => 'ตรวจติดตามอาการและจ่ายยาเพิ่มเติม'
    ],
    [
        'appointment_id' => 'APT-20251015-017',
        'clinic' => 'ทันตกรรม',
        'doctor' => 'ทพญ. ศศิกานต์ พัฒนา',
        'appointment_time' => '2025-10-15T14:00:00+07:00',
        'status' => 'pending',
        'notes' => 'ขูดหินปูนและเคลือบฟลูออไรด์'
    ],
];

$response = [
    'success' => true,
    'id_card_number' => $idCardNumber,
    'generated_at' => date(DATE_ATOM),
    'appointments' => $appointments,
    'summary' => sprintf('พบ %d รายการนัดหมาย (ข้อมูลตัวอย่าง)', count($appointments))
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
