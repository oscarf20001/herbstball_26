<?php
// Setzt den Content-Type auf JSON mit UTF-8-Zeichensatz
header('Content-Type: application/json; charset=utf-8');

// Optional: CORS-Header, falls die Anfrage von einer anderen Domain kommt
header('Access-Control-Allow-Origin: *'); // Erlaubt Anfragen von allen Domains
header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // Erlaubt nur bestimmte HTTP-Methoden
header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Erlaubt bestimmte Header

require 'db_connection.php';

$rawData = file_get_contents("php://input");

// JSON in ein PHP-Array umwandeln
$data = json_decode($rawData, true);

if ($data && isset($data['userData'])) {
    $username = $data['userData'][0];
    $email    = $data['userData'][1];
    $pwd      = sha1($data['userData'][2]);

    // Prüfen, ob User existiert
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND email = ? LIMIT 1");
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result || $result->num_rows === 0) {
        // Kein Treffer → Daten eintragen
        $insert = $conn->prepare("INSERT INTO users (username, email, password_hash) VALUES (?,?,?)");
        $insert->bind_param('sss', $username, $email, $pwd);

        if ($insert->execute()) {
            // Erfolgreich eingefügt
            echo json_encode([
                "success" => true,
                "id"      => $insert->insert_id // letzte eingefügte ID
            ]);
        } else {
            // Fehler beim Einfügen
            echo json_encode([
                "success" => false,
                "error"   => $insert->error
            ]);
        }
    }else{
        // User existiert bereits
        echo json_encode([
            "success" => false,
            "message" => "Benutzer existiert bereits"
        ]);
        $stmt->close();
        return;
    }
} else {
    echo json_encode([
        "success" => false,
        "error"   => "Keine gültigen Daten empfangen"
    ]);
}