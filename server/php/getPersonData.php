<?php
// ========================================
// Header
// ========================================
header('Content-Type: application/json; charset=utf-8');

// Optional: CORS-Header
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// ========================================
// DB-Verbindung
// ========================================
require 'db_connection.php'; 
// Die Datei sollte eine Variable $conn bereitstellen:
// $conn = new mysqli($host, $user, $pass, $dbname);
// $conn->set_charset("utf8mb4");

// ========================================
// Parameter holen
// ========================================
$personId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($personId <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Ungültige oder fehlende ID"]);
    exit;
}

// ========================================
// SQL mit Prepared Statement
// ========================================
$sql = "
SELECT 
  p.id AS person_id,
  k.id AS kaeufer_id,
  p.vorname,
  p.nachname,
  p.email,
  p.school,
  p.muttizettel,
  p.age,
  k.method,
  u.username AS ausgefuehrt_durch,
  k.d_paid,
  k.open_charges,
  p.sum,
  k.charges,
  k.paid_charges,
  k.open_charges
FROM person p
INNER JOIN ticket_besitzer tb ON tb.person_id = p.id
INNER JOIN kaeufer k ON k.id = tb.kaeufer_id
LEFT JOIN payments pay ON pay.kaeufer_id = k.id
LEFT JOIN users u ON u.id = pay.user_id
WHERE p.id = ?
";

// ========================================
// Statement vorbereiten und ausführen
// ========================================
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Fehler beim Vorbereiten des Statements", "details" => $conn->error]);
    exit;
}

$stmt->bind_param("i", $personId);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["error" => "Fehler beim Ausführen des Statements", "details" => $stmt->error]);
    exit;
}

// ========================================
// Ergebnis abrufen
// ========================================
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if ($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(404);
    echo json_encode(["error" => "Keine Person mit dieser ID gefunden."]);
}

// ========================================
// Aufräumen
// ========================================
$stmt->close();
$conn->close();
?>