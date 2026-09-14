<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require 'db_connection.php';

$json = file_get_contents('php://input');
$tickets = json_decode($json, true);
$correctKaeuferReference = 0;

if ($tickets === null) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Ungültiges JSON'
    ]);
    exit;
}

$invalidTickets = [];
$validTickets = [];
$results = [];

// 1. Validierung der Tickets auf fehlende Felder
foreach ($tickets as $index => $ticket) {
    $missingFields = [];

    foreach ($ticket as $field => $value) {
        if (!isset($value) || (is_string($value) && trim($value) === '') || $value === null) {
            $missingFields[] = $field;
        }
    }

    if (count($missingFields) > 0) {
        $invalidTickets[] = [
            'ticketIndex' => $index,
            'missingFields' => $missingFields
        ];
    } else {
        $validTickets[] = $ticket;
    }
}

// 2. Verarbeitung der validen Tickets
foreach ($validTickets as $index => $ticket) {

    if (checkForExistingTicket($conn, $ticket)) {
        // Existierendes Ticket
        if (!empty($ticket['kaeufer']) && $ticket['kaeufer']) {
            $results[] = [
                'status' => 'success',
                'message' => "Ein Käufer-Ticket wurde erkannt, das schon in der Datenbank steht!"
            ];
            addValuesToKaeuferTicket($conn, $ticket, $results);
        } else {
            $results[] = [
                'status' => 'fail',
                'message' => "Ticket für {$ticket['vorname']} {$ticket['nachname']} existiert bereits"
            ];
            $invalidTickets[] = [
                'ticketIndex' => $index,
                'vorname' => $ticket['vorname'],
                'nachname' => $ticket['nachname']
            ];
            continue;
        }
    } else {
        insertIntoDB($conn, $ticket, $index, $results, $correctKaeuferReference);
    }
}

// --- Funktionen ---

function insertIntoDB($conn, $ticket, $index, &$results, $correctKaeuferReference) {
    // Person anlegen
    $stmt1 = $conn->prepare("INSERT INTO person (vorname, nachname, email, age, school) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt1) {
        $results[] = ['status' => 'error', 'message' => "[{$index}] Fehler bei prepare für person: " . $conn->error];
        return;
    }
    $stmt1->bind_param("sssis", $ticket['vorname'], $ticket['nachname'], $ticket['email'], $ticket['age'], $ticket['school']);

    if (!$stmt1->execute()) {
        $results[] = [
            'status' => 'error',
            'message' => "[{$index}] Fehler beim Einfügen in person: " . $stmt1->error
        ];
        $stmt1->close();
        return;
    }
    $personId = $conn->insert_id;
    $stmt1->close();

    $kaeuferId = null;
    if (!empty($ticket['kaeufer']) && $ticket['kaeufer']) {
        // Käufer anlegen
        $stmt2 = $conn->prepare("INSERT INTO kaeufer (person_id, charges, paid_charges, created, submited) VALUES (?, ?, 0.00, FROM_UNIXTIME(?), FROM_UNIXTIME(?))");
        if (!$stmt2) {
            $results[] = ['status' => 'error', 'message' => "[{$index}] Fehler bei prepare für kaeufer: " . $conn->error];
            return;
        }
        $stmt2->bind_param("idii", $personId, $ticket['charges'], $ticket['created'], $ticket['submited']);
        if (!$stmt2->execute()) {
            $results[] = [
                'status' => 'error',
                'message' => "Fehler beim Einfügen in kaeufer: " . $stmt2->error
            ];
            $stmt2->close();
            return;
        }
        $kaeuferId = $conn->insert_id;
        $stmt2->close();
        $correctKaeuferReference = getKaeuferID($conn, $ticket);
        $results[] = [
            'status' => 'update',
            'message' => "KäuferID ist: " . $correctKaeuferReference
        ];
    }

    // Ticket Besitzer anlegen
    // Falls Käufer nicht gesetzt, kann kaeufer_id NULL sein (abhängig von DB-Design)
    $stmt3 = $conn->prepare("INSERT INTO ticket_besitzer (person_id, kaeufer_id) VALUES (?, ?)");
    if (!$stmt3) {
        $results[] = ['status' => 'error', 'message' => "[{$index}] Fehler bei prepare für ticket_besitzer: " . $conn->error];
        return;
    }

    if ($correctKaeuferReference === 0) {
        $results[] = ['status' => 'error', 'message' => "Käufer nicht gefunden: {$ticket['vorname']} {$ticket['nachname']}"];
        return;
    }

    echo json_encode([
        'status' => 'finished',
        'insertResults' => $results
    ]);

    $stmt3->bind_param("ii", $personId, $correctKaeuferReference);

    if ($stmt3->execute()) {
        $results[] = [
            'status' => 'success',
            'message' => "Ticket für {$ticket['vorname']} {$ticket['nachname']} eingefügt"
        ];
    } else {
        $results[] = [
            'status' => 'error',
            'message' => "Fehler beim Einfügen in ticket_besitzer: " . $stmt3->error
        ];
    }
    $stmt3->close();
}

function addValuesToKaeuferTicket($conn, $ticket, &$results) {
    $ticketAnzahl = $ticket['tickets'] - 1;
    $zusatzKosten = (float)($ticketAnzahl * $ticket['sum']);
    $kaeuferID = getKaeuferID($conn, $ticket);

    if ($kaeuferID === false) {
        $results[] = [
            'status' => 'error',
            'message' => "Käufer-ID konnte nicht ermittelt werden für {$ticket['vorname']} {$ticket['nachname']}"
        ];
        return;
    }

    $stmt = $conn->prepare("UPDATE kaeufer SET charges = charges + ? WHERE id = ?");
    if (!$stmt) {
        $results[] = ['status' => 'error', 'message' => "Fehler bei prepare für kaeufer UPDATE: " . $conn->error];
        return;
    }
    $stmt->bind_param("di", $zusatzKosten, $kaeuferID);

    if (!$stmt->execute()) {
        $results[] = [
            'status' => 'error',
            'message' => "Fehler beim Aktualisieren der Käuferdaten: " . $stmt->error
        ];
    }
    $stmt->close();
}

function getKaeuferID($conn, $ticket) {
    $stmt = $conn->prepare("SELECT k.id FROM kaeufer k JOIN person p ON k.person_id = p.id WHERE LOWER(TRIM(p.vorname)) = LOWER(TRIM(?)) AND LOWER(TRIM(p.nachname)) = LOWER(TRIM(?)) LIMIT 1");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    $vorname = trim($ticket['vorname']);
    $nachname = trim($ticket['nachname']);

    if (!$stmt->bind_param("ss", $vorname, $nachname)) {
        error_log("Bind failed: " . $stmt->error);
        return false;
    }

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }

    $stmt->store_result(); // ← wichtig bei MySQLi
    if ($stmt->num_rows === 0) {
        error_log("Kein Ergebnis für: $vorname $nachname");
        $stmt->close();
        return 0;
    }

    if (!$stmt->bind_result($id)) {
        error_log("Bind result failed: " . $stmt->error);
        $stmt->close();
        return false;
    }

    if ($stmt->fetch()) {
        $stmt->close();
        return (int)$id;
    } else {
        error_log("Fetch failed trotz Ergebnis");
        $stmt->close();
        return 1;
    }
}

function checkForExistingTicket($conn, $ticket) {
    $stmt = $conn->prepare("SELECT p.id FROM person p JOIN ticket_besitzer t ON p.id = t.person_id WHERE p.vorname = ? AND p.nachname = ?");
    if (!$stmt) return false;
    $stmt->bind_param("ss", $ticket['vorname'], $ticket['nachname']);
    $stmt->execute();
    $stmt->store_result();

    $exists = $stmt->num_rows > 0;
    $stmt->close();

    return $exists;
}

// Ergebnis JSON zurückgeben
echo json_encode([
    'status' => 'finished',
    'insertResults' => $results,
    'invalidTickets' => $invalidTickets
]);