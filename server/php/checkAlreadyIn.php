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
$sql = "SELECT COUNT(*) FROM entrance WHERE person_id = ?;
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

if ($data['COUNT(*)'] > 0) {
    echo json_encode([
        "einlass" => true,
        "error" => "Person bereits eingelassen"
    ]);
} else {
    echo json_encode([
        "einlass" => false
    ]);
}

// ========================================
// Aufräumen
// ========================================
$stmt->close();
$conn->close();
?>