<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require 'db_connection.php';

// ===========================
// Klassen
// ===========================
class Person {
    public ?int $id = null;
    public string $vorname;
    public string $nachname;
    public string $email;
    public int $age;
    public string $school;
    public float $sum;

    public function __construct(array $data) {
        $this->vorname = $data['vorname'];
        $this->nachname = $data['nachname'];
        $this->email = $data['email'];
        $this->age = (int)$data['age'];
        $this->school = $data['school'];
        $this->sum = (float)$data['sum'];
    }
}

class Kaeufer {
    public ?int $id = null;
    public int $person_id;
    public DateTime $created;
    public DateTime $submited;
    public float $charges;
    public float $summe;
    public float $paid_charges = 0.00;
    public int $tickets;
    public bool $send_confMail = false;

    public function __construct(Person $person, array $data) {
        $this->person_id = $person->id;
        $this->created = (new DateTime())->setTimestamp($data['created']);
        $this->submited = (new DateTime())->setTimestamp($data['submited']);
        $this->charges = (float)$data['charges'];
        $this->summe = (float)$data['sum'];
        $this->tickets = (int)$data['tickets'];
    }
}

class TicketBesitzer {
    public int $kaeufer_id;
    public int $person_id;

    public function __construct(int $kaeufer_id, int $person_id) {
        $this->kaeufer_id = $kaeufer_id;
        $this->person_id = $person_id;
    }
}

// ===========================
// Hilfsfunktionen
// ===========================

function personExistsByData(mysqli $conn, string $vorname, string $nachname, string $email): ?int {
    $stmt = $conn->prepare("SELECT id FROM person WHERE vorname = ? AND nachname = ? AND email = ?");
    if (!$stmt) return null;
    $stmt->bind_param("sss", $vorname, $nachname, $email);
    if (!$stmt->execute()) return null;
    $stmt->bind_result($id);
    if ($stmt->fetch()) {
        $stmt->close();
        return $id;
    }
    $stmt->close();
    return null;
}

function insertPerson(mysqli $conn, Person $person): ?int {
    $stmt = $conn->prepare("INSERT INTO person (vorname, nachname, email, age, school, sum) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) return null;
    $stmt->bind_param("sssisd", $person->vorname, $person->nachname, $person->email, $person->age, $person->school, $person->sum);
    if (!$stmt->execute()) return null;
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}

function insertKaeufer(mysqli $conn, Kaeufer $kaeufer): ?int {
    $stmt = $conn->prepare("INSERT INTO kaeufer (person_id, created, submited, charges, paid_charges, tickets, checked) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) return null;
    $created = $kaeufer->created->format('Y-m-d H:i:s');
    $submited = $kaeufer->submited->format('Y-m-d H:i:s');
    $oneTicket = 1;
    $stmt->bind_param("issddii", $kaeufer->person_id, $created, $submited, $kaeufer->summe, $kaeufer->paid_charges, $oneTicket, $code);
    if (!$stmt->execute()) return null;
    $id = $stmt->insert_id;
    $stmt->close();
    return $id;
}

function insertTicketBesitzer(mysqli $conn, TicketBesitzer $tb): bool {
    $stmt = $conn->prepare("INSERT INTO ticket_besitzer (kaeufer_id, person_id) VALUES (?, ?)");
    if (!$stmt) return false;
    $stmt->bind_param("ii", $tb->kaeufer_id, $tb->person_id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function updateKaeuferTotals(mysqli $conn, int $kaeufer_id, float $charges, int $tickets, int $sum): bool {
    $stmt = $conn->prepare("UPDATE kaeufer SET charges = charges + ?, tickets = (tickets + ?) - 1 WHERE id = ?");
    if (!$stmt) return false;
    $totalCharges = ($charges - $sum);
    $stmt->bind_param("dii", $totalCharges, $tickets, $kaeufer_id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function getKaeuferByPersonId(mysqli $conn, int $person_id): ?int {
    $stmt = $conn->prepare("SELECT id FROM kaeufer WHERE person_id = ?");
    if (!$stmt) return null;
    $stmt->bind_param("i", $person_id);
    if (!$stmt->execute()) return null;
    $stmt->bind_result($id);
    if ($stmt->fetch()) {
        $stmt->close();
        return $id;
    }
    $stmt->close();
    return null;
}

// ===========================
// Hauptlogik
// ===========================

$json = file_get_contents('php://input');
$data = json_decode($json, true);
$results = [];

if (!is_array($data) || count($data) < 1) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Leere oder ungültige JSON']);
    exit;
}

$kaeuferData = $data[0];
$kaeuferPerson = new Person($kaeuferData);

// Käuferperson prüfen/erstellen
$existingPersonId = personExistsByData($conn, $kaeuferPerson->vorname, $kaeuferPerson->nachname, $kaeuferPerson->email);
if ($existingPersonId !== null) {
    $kaeuferPerson->id = $existingPersonId;
} else {
    $kaeuferPerson->id = insertPerson($conn, $kaeuferPerson);
    if (!$kaeuferPerson->id) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Fehler beim Einfügen des Käufers in person']);
        exit;
    }
}

// Käufer prüfen/erstellen
$existingKaeuferId = getKaeuferByPersonId($conn, $kaeuferPerson->id);
if ($existingKaeuferId !== null) {
    $kaeufer = new Kaeufer($kaeuferPerson, $kaeuferData);
    $kaeufer->id = $existingKaeuferId;
} else {
    $kaeufer = new Kaeufer($kaeuferPerson, $kaeuferData);
    $kaeufer->id = insertKaeufer($conn, $kaeufer);
    if (!$kaeufer->id) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Fehler beim Einfügen in kaeufer']);
        exit;
    }
}

// Käufer selbst als Ticketbesitzer hinzufügen
$alreadyOwnerStmt = $conn->prepare("SELECT 1 FROM ticket_besitzer WHERE kaeufer_id = ? AND person_id = ?");
$alreadyOwnerStmt->bind_param("ii", $kaeufer->id, $kaeuferPerson->id);
$alreadyOwnerStmt->execute();
$alreadyOwnerStmt->store_result();

$alreadyTicketOwner = $alreadyOwnerStmt->num_rows > 0;
$alreadyOwnerStmt->close();

if (!$alreadyTicketOwner) {
    insertTicketBesitzer($conn, new TicketBesitzer($kaeufer->id, $kaeuferPerson->id));
} elseif (count($data) === 1) {
    // Nur der Käufer wurde eingetragen, und er ist schon Ticketbesitzer
    echo json_encode([
        'status' => 'already-exists',
        'message' => 'Du hast dich bereits für ein Ticket registriert.',
        'results' => [[
            'status' => 'fail',
            'message' => "{$kaeuferPerson->vorname} {$kaeuferPerson->nachname} hat bereits ein Ticket und hat sonst keine weiteren Tickets gebucht!",
            'vorname' => $kaeuferPerson->vorname,
            'nachname' => $kaeuferPerson->nachname
        ]]
    ]);
    exit;
}

// Neue Tickets zählen
$newTickets = 0;
$newCharges = 0.0;

foreach (array_slice($data, 1) as $ticketData) {
    $person = new Person($ticketData);
    $existingId = personExistsByData($conn, $person->vorname, $person->nachname, $person->email);

    if ($existingId !== null) {
        // Prüfen ob bereits Ticketbesitzer
        $stmt = $conn->prepare("SELECT 1 FROM ticket_besitzer WHERE kaeufer_id = ? AND person_id = ?");
        $stmt->bind_param("ii", $kaeufer->id, $existingId);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $results[] = [
                'status' => 'fail', 
                'message' => "{$person->vorname} {$person->nachname} ist bereits Ticketbesitzer dieses Käufers",
                'vorname' => "{$person->vorname}",
                'nachname' => "{$person->nachname}"
            ];
            $stmt->close();
            continue;
        }
        $stmt->close();
        $person->id = $existingId;
    } else {
        $person->id = insertPerson($conn, $person);
        if (!$person->id) {
            $results[] = ['status' => 'error', 'message' => "Fehler beim Einfügen von {$person->vorname} {$person->nachname}"];
            continue;
        }
    }

    if (insertTicketBesitzer($conn, new TicketBesitzer($kaeufer->id, $person->id))) {
        $results[] = ['status' => 'success', 'message' => "{$person->vorname} {$person->nachname} wurde erfolgreich eingetragen"];
        $newTickets++;
        $newCharges += $person->sum;
    } else {
        $results[] = ['status' => 'error', 'message' => "Fehler beim Einfügen in ticket_besitzer für {$person->vorname} {$person->nachname}"];
    }
}

// Käuferdaten aktualisieren (nur wenn neue Tickets)
if ($newTickets > 0) {
    $sum = $person->sum;
    updateKaeuferTotals($conn, $kaeufer->id, $kaeufer->charges, count($data), $sum);
}

echo json_encode([
    'status' => 'finished',
    'results' => $results
]);