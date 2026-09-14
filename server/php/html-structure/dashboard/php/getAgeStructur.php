<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../../../../config.php'; // oder richtiger relativer Pfad
require_once BASE_PATH . '/server/php/db_connection.php';

$result = [];

$stmt = $conn->prepare("SELECT age, COUNT(*) AS count FROM person GROUP BY age ORDER BY age ASC;");

$stmt->execute();
$stmt->bind_result($age, $anzahl);

while ($stmt->fetch()) {
    $result[] = [
        'age' => $age,
        'anzahl' => (int)$anzahl
    ];
}

$stmt->close();

// JSON-Ausgabe
echo json_encode($result, JSON_UNESCAPED_UNICODE);

$conn->close();