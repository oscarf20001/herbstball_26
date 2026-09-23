<?php

use Dotenv\Store\File\Reader;
use Safe\Exceptions\ReadlineException;

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require "db_connection.php";

// =====================================================
// EIGENE EXCEPTION FÜR BUCHUNGSFEHLER
// =====================================================

class BookingException extends Exception
{
    public function __construct(
        string $message,
        public readonly ?string $detailedError = null,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $email = null
    ) {
        parent::__construct($message);
    }
}

// =====================================================
// KLASSEN
// =====================================================

class Person
{
    public ?int $id = null;
    public string $vorname;
    public string $nachname;
    public string $email;
    public int $age;
    public string $school;
    public float $sum;

    public function __construct(array $data)
    {
        $this->vorname = trim($data["vorname"] ?? "");
        $this->nachname = trim($data["nachname"] ?? "");
        $this->email = strtolower(trim($data["email"] ?? ""));
        $this->age = (int) ($data["age"] ?? 0);
        $this->school = trim($data["school"] ?? "");
        $this->sum = (float) ($data["sum"] ?? 0);
    }
}

class Kaeufer
{
    public ?int $id = null;
    public int $person_id;
    public DateTime $created;
    public DateTime $submited;
    public float $charges;
    public float $summe;
    public float $paid_charges = 0.0;
    public int $tickets;
    public bool $send_confMail = false;

    public function __construct(Person $person, array $data)
    {
        if ($person->id === null) {
            throw new RuntimeException(
                "Für den Käufer existiert keine Personen-ID."
            );
        }

        $this->person_id = $person->id;

        $createdTimestamp = (int) ($data["created"] ?? time());
        $submitedTimestamp = (int) ($data["submited"] ?? time());

        $this->created = (new DateTime())->setTimestamp($createdTimestamp);
        $this->submited = (new DateTime())->setTimestamp($submitedTimestamp);

        $this->charges = (float) ($data["charges"] ?? 0);
        $this->summe = (float) ($data["sum"] ?? 0);
        $this->tickets = (int) ($data["tickets"] ?? 1);
    }
}

class TicketBesitzer
{
    public int $kaeufer_id;
    public int $person_id;

    public function __construct(int $kaeufer_id, int $person_id)
    {
        $this->kaeufer_id = $kaeufer_id;
        $this->person_id = $person_id;
    }
}

// =====================================================
// PERSON
// =====================================================

function personExistsByData(
    mysqli $conn,
    string $vorname,
    string $nachname
): ?int {
    $stmt = $conn->prepare("
        SELECT id
        FROM person
        WHERE vorname = ?
          AND nachname = ?
        LIMIT 1
    ");

    if (!$stmt) {
        throw new RuntimeException(
            "Fehler beim Vorbereiten der Personenabfrage."
        );
    }

    $stmt->bind_param("ss", $vorname, $nachname);

    if (!$stmt->execute()) {
        $stmt->close();

        throw new RuntimeException(
            "Fehler beim Ausführen der Personenabfrage."
        );
    }

    $stmt->bind_result($id);

    if ($stmt->fetch()) {
        $stmt->close();

        return (int) $id;
    }

    $stmt->close();

    return null;
}

function insertPerson(mysqli $conn, Person $person): int
{
    $stmt = $conn->prepare("
        INSERT INTO person
        (
            vorname,
            nachname,
            email,
            age,
            school,
            sum
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        throw new RuntimeException(
            "Fehler beim Vorbereiten des Person-INSERTs."
        );
    }

    $stmt->bind_param(
        "sssisd",
        $person->vorname,
        $person->nachname,
        $person->email,
        $person->age,
        $person->school,
        $person->sum
    );

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();

        throw new RuntimeException("Fehler beim Einfügen der Person: {$error}");
    }

    $id = (int) $stmt->insert_id;

    $stmt->close();

    return $id;
}

// =====================================================
// KÄUFER
// =====================================================

function getKaeuferByPersonData(
    mysqli $conn,
    string $vorname,
    string $nachname
): ?int {
    $stmt = $conn->prepare("
        SELECT k.id
        FROM kaeufer k
        INNER JOIN person p
            ON p.id = k.person_id
        WHERE p.vorname = ?
          AND p.nachname = ?
        LIMIT 1
    ");

    if (!$stmt) {
        throw new RuntimeException(
            "Fehler beim Vorbereiten der Käuferabfrage."
        );
    }

    $stmt->bind_param("ss", $vorname, $nachname);

    if (!$stmt->execute()) {
        $stmt->close();

        throw new RuntimeException("Fehler beim Ausführen der Käuferabfrage.");
    }

    $stmt->bind_result($kaeuferId);

    if ($stmt->fetch()) {
        $stmt->close();

        return (int) $kaeuferId;
    }

    $stmt->close();

    return null;
}

function getKaeuferByEmail(mysqli $conn, string $email): ?int
{
    $stmt = $conn->prepare("
        SELECT k.id
        FROM kaeufer k
        INNER JOIN person p
            ON p.id = k.person_id
        WHERE LOWER(p.email) = ?
        LIMIT 1
    ");

    if (!$stmt) {
        throw new RuntimeException(
            "Fehler beim Vorbereiten der E-Mail-Prüfung."
        );
    }

    $stmt->bind_param("s", $email);

    if (!$stmt->execute()) {
        $stmt->close();

        throw new RuntimeException("Fehler beim Ausführen der E-Mail-Prüfung.");
    }

    $stmt->bind_result($id);

    if ($stmt->fetch()) {
        $stmt->close();

        return (int) $id;
    }

    $stmt->close();

    return null;
}

function personAlreadyExistsAsKaeufer(mysqli $conn, int $personId): bool
{
    $stmt = $conn->prepare("
        SELECT 1
        FROM kaeufer
        WHERE person_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        throw new RuntimeException(
            "Fehler beim Vorbereiten der Käufer-Person-Prüfung."
        );
    }

    $stmt->bind_param("i", $personId);

    if (!$stmt->execute()) {
        $stmt->close();

        throw new RuntimeException(
            "Fehler beim Ausführen der Käufer-Person-Prüfung."
        );
    }

    $stmt->store_result();

    $exists = $stmt->num_rows > 0;

    $stmt->close();

    return $exists;
}

function insertKaeufer(mysqli $conn, Kaeufer $kaeufer): int
{
    $stmt = $conn->prepare("
        INSERT INTO kaeufer
        (
            person_id,
            created,
            submited,
            charges,
            paid_charges,
            tickets,
            checked
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        throw new RuntimeException(
            "Fehler beim Vorbereiten des Käufer-INSERTs."
        );
    }

    $created = $kaeufer->created->format("Y-m-d H:i:s");
    $submited = $kaeufer->submited->format("Y-m-d H:i:s");

    /*
     * Beim erstmaligen Anlegen existiert genau der
     * im Käufer-Datensatz angegebene erste Kauf.
     */
    $tickets = 1;
    $checked = 0;

    $stmt->bind_param(
        "issddii",
        $kaeufer->person_id,
        $created,
        $submited,
        $kaeufer->summe,
        $kaeufer->paid_charges,
        $tickets,
        $checked
    );

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();

        throw new RuntimeException(
            "Fehler beim Einfügen des Käufers: {$error}"
        );
    }

    $id = (int) $stmt->insert_id;

    $stmt->close();

    return $id;
}

// =====================================================
// TICKETBESITZER
// =====================================================

function ticketAlreadyExistsAnywhere(mysqli $conn, int $personId): bool
{
    $stmt = $conn->prepare("
        SELECT 1
        FROM ticket_besitzer
        WHERE person_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        throw new RuntimeException(
            "Fehler beim Vorbereiten der Ticketprüfung."
        );
    }

    $stmt->bind_param("i", $personId);

    if (!$stmt->execute()) {
        $stmt->close();

        throw new RuntimeException("Fehler beim Ausführen der Ticketprüfung.");
    }

    $stmt->store_result();

    $exists = $stmt->num_rows > 0;

    $stmt->close();

    return $exists;
}

function insertTicketBesitzer(mysqli $conn, TicketBesitzer $tb): void
{
    $stmt = $conn->prepare("
        INSERT INTO ticket_besitzer
        (
            kaeufer_id,
            person_id
        )
        VALUES (?, ?)
    ");

    if (!$stmt) {
        throw new RuntimeException(
            "Fehler beim Vorbereiten des Ticket-INSERTs."
        );
    }

    $stmt->bind_param("ii", $tb->kaeufer_id, $tb->person_id);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();

        throw new RuntimeException(
            "Fehler beim Einfügen des Ticketbesitzers: {$error}"
        );
    }

    $stmt->close();
}

// =====================================================
// KÄUFER AKTUALISIEREN
// =====================================================

function updateKaeuferTotals(
    mysqli $conn,
    int $kaeuferId,
    float $newCharges,
    int $newTickets
): void {
    if ($newTickets <= 0) {
        return;
    }

    $stmt = $conn->prepare("
        UPDATE kaeufer
        SET
            charges = charges + ?,
            tickets = tickets + ?
        WHERE id = ?
    ");

    if (!$stmt) {
        throw new RuntimeException(
            "Fehler beim Vorbereiten der Käuferaktualisierung."
        );
    }

    $stmt->bind_param("dii", $newCharges, $newTickets, $kaeuferId);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();

        throw new RuntimeException(
            "Fehler beim Aktualisieren des Käufers: {$error}"
        );
    }

    $stmt->close();
}

// =====================================================
// JSON EINLESEN
// =====================================================

$json = file_get_contents("php://input");

$data = json_decode($json, true);

if (!is_array($data) || count($data) < 1) {
    http_response_code(400);

    echo json_encode([
        "status" => "error",
        "message" => "Leere oder ungültige JSON.",
    ]);

    exit();
}

// =====================================================
// GRUNDPRÜFUNGEN
// =====================================================

$kaeuferData = $data[0];

if (!is_array($kaeuferData)) {
    http_response_code(400);

    echo json_encode([
        "status" => "error",
        "message" => "Ungültige Käuferdaten.",
    ]);

    exit();
}

$kaeuferPerson = new Person($kaeuferData);

$vorname = $kaeuferPerson->vorname;
$nachname = $kaeuferPerson->nachname;
$email = $kaeuferPerson->email;

// =====================================================
// SONDERFALL ERMITTELN
// =====================================================
//
// Beispiel:
//
// [
//     {
//         "vorname": "Oscar",
//         "nachname": "Streich",
//         "email": "oscar-streich@t-online.de",
//         "tickets": 1
//     }
// ]
//
// Hier existiert kein zweiter Datensatz.
// Der Käufer ist gleichzeitig Ticketbesitzer.
//
// =====================================================

$selfTicketPurchase =
    count($data) === 1 && (int) ($kaeuferData["tickets"] ?? 0) === 1;

// =====================================================
// TRANSAKTION STARTEN
// =====================================================

$conn->begin_transaction();

try {
    // =================================================
    // KÄUFER ERMITTELN
    // =================================================

    $existingKaeuferId = getKaeuferByPersonData($conn, $vorname, $nachname);

    // =================================================
    // SONDERFALL:
    // EIN TICKET FÜR DEN KÄUFER SELBST
    // =================================================

    if ($selfTicketPurchase) {
        // =================================================
        // EINZELTICKET:
        // E-MAIL DARF NOCH NICHT ALS KÄUFER EXISTIEREN
        // =================================================

        $existingKaeuferByEmail = getKaeuferByEmail($conn, $email);

        if ($existingKaeuferByEmail !== null) {
            throw new BookingException(
                "Die E-Mail-Adresse {$email} wird bereits von einem Käufer verwendet. Ein einzelnes Ticket kann damit nicht erneut gekauft werden.",
                "duplicateEmailSelfPurchase",
                $vorname,
                $nachname, 
                $email
            );
        }

        // -------------------------------------------------
        // Danach normale Personenprüfung
        // -------------------------------------------------

        $existingPersonId = personExistsByData($conn, $vorname, $nachname);

        if ($existingPersonId !== null) {
            $kaeuferPerson->id = $existingPersonId;

            if (ticketAlreadyExistsAnywhere($conn, $kaeuferPerson->id)) {
                throw new BookingException(
                    "{$vorname} {$nachname} besitzt bereits ein Ticket und kann nicht erneut hinzugefügt werden.",
                    "duplicate",
                    $vorname,
                    $nachname, 
                    $email
                );
            }
        } else {
            $kaeuferPerson->id = insertPerson($conn, $kaeuferPerson);
        }

        // Neuer Käufer
        $kaeufer = new Kaeufer($kaeuferPerson, $kaeuferData);

        $kaeufer->id = insertKaeufer($conn, $kaeufer);

        // Käufer ist Ticketbesitzer
        insertTicketBesitzer(
            $conn,
            new TicketBesitzer($kaeufer->id, $kaeuferPerson->id)
        );

        $conn->commit();

        echo json_encode([
            "status" => "finished",
            "kaeufer" => [
                "id" => $kaeufer->id,
                "vorname" => $kaeuferPerson->vorname,
                "nachname" => $kaeuferPerson->nachname,
                "email" => $kaeuferPerson->email,
            ],
            "newTickets" => 1,
            "newCharges" => $kaeuferPerson->sum,
            "results" => [
                [
                    "status" => "success",
                    "message" => "{$kaeuferPerson->vorname} {$kaeuferPerson->nachname} wurde als Ticketbesitzer hinzugefügt.",
                    "vorname" => $kaeuferPerson->vorname,
                    "nachname" => $kaeuferPerson->nachname,
                ],
            ],
        ]);

        exit();
    }

    // =================================================
    // NORMALER FALL:
    // KÄUFER + EIN ODER MEHRERE ANDERE TICKETS
    // =================================================

    // =================================================
    // FALL 1:
    // Käufer existiert bereits exakt
    //
    // → Weitere Tickets sind erlaubt
    // =================================================

    if ($existingKaeuferId !== null) {
        $kaeuferPerson->id = personExistsByData($conn, $vorname, $nachname);

        if ($kaeuferPerson->id === null) {
            throw new RuntimeException(
                "Der bestehende Käufer konnte nicht gefunden werden."
            );
        }

        $kaeufer = new Kaeufer($kaeuferPerson, $kaeuferData);

        $kaeufer->id = $existingKaeuferId;
    }

    // =================================================
    // FALL 2:
    // Kein exakter Käufer gefunden
    // =================================================
    else {
        // -------------------------------------------------
        // E-Mail bereits bei anderem Käufer?
        // -------------------------------------------------

        $otherKaeuferId = getKaeuferByEmail($conn, $email);

        if ($otherKaeuferId !== null) {
            throw new BookingException(
                "Diese E-Mail-Adresse wird bereits von einem anderen Käufer verwendet.",
                "duplicateEmailKaeufer",
                $vorname, 
                $nachname, 
                $email
            );
        }

        // -------------------------------------------------
        // Person suchen
        // -------------------------------------------------

        $existingPersonId = personExistsByData($conn, $vorname, $nachname);

        if ($existingPersonId !== null) {
            $kaeuferPerson->id = $existingPersonId;
        } else {
            $kaeuferPerson->id = insertPerson($conn, $kaeuferPerson);
        }

        // -------------------------------------------------
        // Neuen Käufer erstellen
        // -------------------------------------------------

        $kaeufer = new Kaeufer($kaeuferPerson, $kaeuferData);

        $kaeufer->id = insertKaeufer($conn, $kaeufer);
    }

    // =================================================
    // TICKETS VORBEREITEN
    // =================================================
    //
    // Bei mehreren Datensätzen:
    //
    // data[0] = Käufer
    // data[1] = Ticketbesitzer 1
    // data[2] = Ticketbesitzer 2
    // ...
    //
    // Noch KEINE Tickets einfügen.
    // Erst alle prüfen.
    // =================================================

    $ticketDataList = array_slice($data, 1);

    if (count($ticketDataList) < 1) {
        throw new BookingException("Es wurde kein Ticket angegeben.",
        "noTicket");
    }

    $ticketChecks = [];

    // =================================================
    // ALLE TICKETS PRÜFEN
    // =================================================

    foreach ($ticketDataList as $ticketData) {
        if (!is_array($ticketData)) {
            throw new BookingException("Ungültige Ticketdaten.",
            "invalidTicketData");
        }

        $person = new Person($ticketData);

        // -------------------------------------------------
        // Personen-ID suchen
        // -------------------------------------------------

        $existingPersonId = personExistsByData(
            $conn,
            $person->vorname,
            $person->nachname
        );

        // -------------------------------------------------
        // PERSON EXISTIERT BEREITS
        // -------------------------------------------------

        if ($existingPersonId !== null) {
            $person->id = $existingPersonId;

            // -------------------------------------------------
            // Person besitzt bereits irgendein Ticket?
            // -------------------------------------------------

            if (ticketAlreadyExistsAnywhere($conn, $person->id)) {
                throw new BookingException(
                    "{$person->vorname} {$person->nachname} besitzt bereits ein Ticket und kann nicht erneut hinzugefügt werden.",
                    "duplicate",
                    $person->vorname, 
                    $person->nachname, 
                    $person->email
                );
            }

            $ticketChecks[] = [
                "person" => $person,
                "existing" => true,
            ];
        }

        // -------------------------------------------------
        // PERSON EXISTIERT NOCH NICHT
        // -------------------------------------------------
        else {
            $ticketChecks[] = [
                "person" => $person,
                "existing" => false,
            ];
        }
    }

    // =================================================
    // ALLE TICKETS SIND GÜLTIG
    //
    // JETZT ERST WIRD GESCHRIEBEN
    // =================================================

    $newTickets = 0;
    $newCharges = 0.0;

    foreach ($ticketChecks as $ticketCheck) {
        /** @var Person $person */
        $person = $ticketCheck["person"];

        // -------------------------------------------------
        // Neue Person jetzt anlegen
        // -------------------------------------------------

        if (!$ticketCheck["existing"]) {
            $person->id = insertPerson($conn, $person);
        }

        // -------------------------------------------------
        // Ticket anlegen
        // -------------------------------------------------

        insertTicketBesitzer(
            $conn,
            new TicketBesitzer($kaeufer->id, $person->id)
        );

        $newTickets++;
        $newCharges += $person->sum;
    }

    // =================================================
    // BESTEHENDEN / NEUEN KÄUFER AKTUALISIEREN
    // =================================================

    updateKaeuferTotals($conn, $kaeufer->id, $newCharges, $newTickets);

    // =================================================
    // ALLES ERFOLGREICH
    // =================================================

    $conn->commit();

    // =================================================
    // ERFOLGSANTWORT
    // =================================================

    $results = [];

    foreach ($ticketChecks as $ticketCheck) {
        /** @var Person $person */
        $person = $ticketCheck["person"];

        $results[] = [
            "status" => "success",
            "message" => "{$person->vorname} {$person->nachname} wurde als Ticketbesitzer hinzugefügt.",
            "vorname" => $person->vorname,
            "nachname" => $person->nachname,
        ];
    }

    echo json_encode([
        "status" => "finished",

        "kaeufer" => [
            "id" => $kaeufer->id,
            "vorname" => $kaeuferPerson->vorname,
            "nachname" => $kaeuferPerson->nachname,
            "email" => $kaeuferPerson->email,
        ],

        "newTickets" => $newTickets,
        "newCharges" => $newCharges,

        "results" => $results,
    ]);

    exit();
}catch (BookingException $e) {
    // =================================================
    // BUCHUNG ABGELEHNT
    //
    // ALLES ZURÜCKROLLEN
    // =================================================

    $conn->rollback();

    http_response_code(409);

    echo json_encode([
        "status" => "failed",

        "message" => $e->getMessage(),

        "results" => [
            [
                "status" => "fail",
                "message" => $e->getMessage(),
                "detailedError" => $e->detailedError,
                "firstName" => $e->firstName,
                "lastName" => $e->lastName,
                "email" => $e->email
            ],
        ],
    ]);

    exit();
} catch (Throwable $e) {
    // =================================================
    // TECHNISCHER FEHLER
    //
    // ALLES ZURÜCKROLLEN
    // =================================================

    $conn->rollback();

    http_response_code(500);

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage(),
    ]);

    exit();
}