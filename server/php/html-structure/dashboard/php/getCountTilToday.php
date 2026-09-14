<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../../../../config.php'; // oder richtiger relativer Pfad
require_once BASE_PATH . '/server/php/db_connection.php';

$result = [];
$count = 0;

$stmt = $conn->prepare("SELECT COUNT(*) FROM person");
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();

echo json_encode($result[] = [
    "count" => $count
]);
$stmt->close();