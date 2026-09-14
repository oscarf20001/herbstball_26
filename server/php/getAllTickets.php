<?php
// Setzt den Content-Type auf JSON mit UTF-8-Zeichensatz
header('Content-Type: application/json; charset=utf-8');

// Optional: CORS-Header, falls die Anfrage von einer anderen Domain kommt
header('Access-Control-Allow-Origin: *'); // Erlaubt Anfragen von allen Domains
header('Access-Control-Allow-Methods: GET, POST, OPTIONS'); // Erlaubt nur bestimmte HTTP-Methoden
header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Erlaubt bestimmte Header

require 'db_connection.php';

$rawInput = file_get_contents("php://input");

// In ein assoziatives Array umwandeln
$data = json_decode($rawInput, true);

// Zugriff auf das "email"-Feld
$email = $data['email'] ?? null;

if ($email === null) {
    http_response_code(400);
    echo json_encode(['error' => 'E-Mail fehlt']);
    exit;
}

$persons = [];
$kaeufer = [];

$stmt = $conn->prepare("SELECT p2.id, p2.vorname, p2.nachname, k.tickets, k.charges, k.paid_charges, k.open_charges, k.method
FROM kaeufer k
JOIN person p2 ON k.person_id = p2.id
WHERE p2.email = ?;
");

$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()){
    $kaeufer[] = $row;
}

$stmt->close();

$stmt = $conn->prepare("SELECT p.id, p.vorname, p.nachname, p.email, p.age, p.school, p.sum
FROM ticket_besitzer tb
JOIN person p ON tb.person_id = p.id
WHERE tb.kaeufer_id = (
    SELECT k.id
    FROM kaeufer k
    JOIN person p2 ON k.person_id = p2.id
    WHERE p2.email = ?
);");

$stmt->bind_param('s',$email);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()){
    $persons[] = $row;
}

$stmt->close();
$conn->close();

// Beide Statements wurden bereits ausgeführt, Arrays sind gefüllt
$response = [
    'kaeufer' => $kaeufer,
    'persons' => $persons
];

// Als JSON zurückgeben
header('Content-Type: application/json');
echo json_encode($response);
