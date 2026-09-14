<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../../../../config.php'; // oder richtiger relativer Pfad
require_once BASE_PATH . '/server/php/db_connection.php';

$result = [];
$count = 0;

$stmt = $conn->prepare("SELECT device_type, COUNT(*) AS count FROM analytics GROUP BY device_type;");
$stmt->execute();
$stmt->bind_result($type, $count);
while ($stmt->fetch()) {
    $result[] = [
        'type' => $type,
        'anzahl' => (int)$count
    ];
}

$stmt->close();
echo json_encode($result, JSON_UNESCAPED_UNICODE);