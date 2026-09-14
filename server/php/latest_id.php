<?php
// Setzt den Content-Type auf JSON mit UTF-8-Zeichensatz
header('Content-Type: application/json; charset=utf-8');

// Optional: CORS-Header, falls die Anfrage von einer anderen Domain kommt
header('Access-Control-Allow-Origin: *'); // Erlaubt Anfragen von allen Domains
header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // Erlaubt nur bestimmte HTTP-Methoden
header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Erlaubt bestimmte Header

require 'db_connection.php';

// Beispiel-Abfrage
$sql = "SELECT MAX(id) AS max_id FROM kaeufer;";
$result = $conn->query($sql);

if ($result === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler bei der Abfrage']);
    exit;
}

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    // Ausgabe als JSON
    echo json_encode(['max_id' => (int)$row['max_id']]);
} else {
    // Keine Daten gefunden
    echo json_encode(['max_id' => null]);
}

$conn->close();