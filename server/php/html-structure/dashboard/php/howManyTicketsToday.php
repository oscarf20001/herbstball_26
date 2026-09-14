<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../../../../config.php'; // oder richtiger relativer Pfad
require_once BASE_PATH . '/server/php/db_connection.php';

$count = 0;

$stmt = $conn->prepare("SELECT SUM(tickets) FROM kaeufer WHERE DATE(submited) = CURDATE();");
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

echo json_encode([
    "count" => (int)$count
]);
exit;