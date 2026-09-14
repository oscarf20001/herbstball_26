<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../../../../config.php'; // oder richtiger relativer Pfad
require_once BASE_PATH . '/server/php/db_connection.php';

$result = [];

$stmt = $conn->prepare("WITH RECURSIVE letzte_7_tage AS (
  SELECT CURDATE() - INTERVAL 6 DAY AS tag
  UNION ALL
  SELECT tag + INTERVAL 1 DAY
  FROM letzte_7_tage
  WHERE tag + INTERVAL 1 DAY <= CURDATE()
)
SELECT
  tag,
  SUM(kaeufer.tickets) AS anzahl
FROM
  letzte_7_tage
LEFT JOIN
  kaeufer ON DATE(kaeufer.created) = tag
GROUP BY
  tag
ORDER BY
  tag;");

$stmt->execute();
$stmt->bind_result($tag, $anzahl);

while ($stmt->fetch()) {
    $result[] = [
        'tag' => $tag,
        'anzahl' => (int)$anzahl
    ];
}

$stmt->close();

// JSON-Ausgabe
echo json_encode($result, JSON_UNESCAPED_UNICODE);

$conn->close();