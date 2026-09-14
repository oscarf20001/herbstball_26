<?php
// ========================================
// Header
// ========================================
session_start();
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
$userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

if ($personId <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Ungültige oder fehlende ID"]);
    exit;
}

if ($userId <= 0) {
    echo json_encode([
        'status' => 'fail',
        'message' => 'Kein eingeloggter User oder ungültiger User'
    ]);
    exit;
}

// ========================================
// SQL mit Prepared Statement
// ========================================
$sql = "INSERT INTO entrance (person_id, user_id) VALUES (?,?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Fehler beim Vorbereiten des Statements", "details" => $conn->error]);
    exit;
}

$stmt->bind_param("ii", $personId, $userId);

if (!$stmt->execute()) {
    // Fehler beim Insert
    http_response_code(500);
    echo json_encode(["success" => false, "error" => "Fehler beim Einfügen", "details" => $stmt->error]);
    exit;
}

// Prüfen, ob wirklich eine Zeile eingefügt wurde
if ($stmt->affected_rows > 0) {
    echo json_encode(["success" => true, "message" => "Einlass erfolgreich"]);
} else {
    echo json_encode(["success" => false, "error" => "Einlass konnte nicht hinzugefügt werden"]);
}

// ========================================
// Aufräumen
// ========================================
$stmt->close();
$conn->close();
?>