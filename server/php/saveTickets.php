<?php

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

        throw new RuntimeException(
            "Fehler beim Einfügen der Person: {$error}"
        );
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

        throw new RuntimeException(
            "Fehler beim Ausführen der Käuferabfrage."
        );
    }

    $stmt->bind_result($kaeuferId);

    if ($stmt->fetch()) {
        $stmt->close();

        return (int) $kaeuferId;
    }

    $stmt->close();

    return null;
}

function getKaeuferByEmail(
    mysqli $conn,
    string $email
): ?int {
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

        throw new RuntimeException(
            "Fehler beim Ausführen der E-Mail-Prüfung."
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

function insertKaeufer(
    mysqli $conn,
    Kaeufer $kaeufer
): int {
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
     * Der Käufer besitzt bereits sein eigenes Ticket.
     *
     * Die weiteren Tickets werden später ergänzt.
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

function ticketAlreadyExistsAnywhere(
    mysqli $conn,
    int $personId
): bool {
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

        throw new RuntimeException(
            "Fehler beim Ausführen der Ticketprüfung."
        );
    }

    $stmt->store_result();

    $exists = $stmt->num_rows > 0;

    $stmt->close();

    return $exists;
}

function ticketExistsForKaeufer(
    mysqli $conn,
    int $kaeuferId,
    int $personId
): bool {
    $stmt = $conn->prepare("
        SELECT 1
        FROM ticket_besitzer
        WHERE kaeufer_id = ?
          AND person_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        throw new RuntimeException(
            "Fehler beim Vorbereiten der Käufer-Ticket-Prüfung."
        );
    }

    $stmt->bind_param(
        "ii",
        $kaeuferId,
        $personId
    );

    if (!$stmt->execute()) {
        $stmt->close();

        throw new RuntimeException(
            "Fehler beim Ausführen der Käufer-Ticket-Prüfung."
        );
    }

    $stmt->store_result();

    $exists = $stmt->num_rows > 0;

    $stmt->close();

    return $exists;
}

function insertTicketBesitzer(
    mysqli $conn,
    TicketBesitzer $tb
): void {
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

    $stmt->bind_param(
        "ii",
        $tb->kaeufer_id,
        $tb->person_id
    );

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

    $stmt->bind_param(
        "dii",
        $newCharges,
        $newTickets,
        $kaeuferId
    );

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
        "message" => "Leere oder ungültige JSON."
    ]);

    exit();
}

// =====================================================
// KÄUFERDATEN
// =====================================================

$kaeuferData = $data[0];

if (!is_array($kaeuferData)) {
    http_response_code(400);

    echo json_encode([
        "status" => "error",
        "message" => "Ungültige Käuferdaten."
    ]);

    exit();
}

$kaeuferPerson = new Person($kaeuferData);

$vorname = $kaeuferPerson->vorname;
$nachname = $kaeuferPerson->nachname;
$email = $kaeuferPerson->email;

// =====================================================
// TICKETANZAHL
// =====================================================
//
// data[0] = Käufer = eigenes Ticket
// data[1...] = weitere Ticketbesitzer
//
// Deshalb:
//
// 1 Person  => 1 Ticket
// 2 Personen => 2 Tickets
// 3 Personen => 3 Tickets
//
// =====================================================

$requestedTickets = (int) ($kaeuferData["tickets"] ?? count($data));

$actualTickets = count($data);

if ($requestedTickets !== $actualTickets) {
    http_response_code(400);

    echo json_encode([
        "status" => "error",
        "message" =>
            "Die angegebene Ticketanzahl stimmt nicht mit den Ticketbesitzern überein.",
        "requestedTickets" => $requestedTickets,
        "actualTickets" => $actualTickets
    ]);

    exit();
}

// =====================================================
// TRANSAKTION
// =====================================================

$conn->begin_transaction();

try {

    // =================================================
    // KÄUFER SUCHEN
    // =================================================

    $existingKaeuferId = getKaeuferByPersonData(
        $conn,
        $vorname,
        $nachname
    );

    // =================================================
    // FALL 1:
    // KÄUFER EXISTIERT BEREITS
    // =================================================

    if ($existingKaeuferId !== null) {

        $kaeuferPerson->id = personExistsByData(
            $conn,
            $vorname,
            $nachname
        );

        if ($kaeuferPerson->id === null) {
            throw new RuntimeException(
                "Der bestehende Käufer konnte nicht gefunden werden."
            );
        }

        $kaeufer = new Kaeufer(
            $kaeuferPerson,
            $kaeuferData
        );

        $kaeufer->id = $existingKaeuferId;

        /*
         * Der Käufer darf nicht nochmals als eigenes Ticket
         * eingefügt werden, wenn er bereits eines besitzt.
         *
         * Das wird später berücksichtigt.
         */
    }

    // =================================================
    // FALL 2:
    // KÄUFER EXISTIERT NOCH NICHT
    // =================================================

    else {

        // -------------------------------------------------
        // E-Mail darf noch keinem anderen Käufer gehören
        // -------------------------------------------------

        $otherKaeuferId = getKaeuferByEmail(
            $conn,
            $email
        );

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

        $existingPersonId = personExistsByData(
            $conn,
            $vorname,
            $nachname
        );

        if ($existingPersonId !== null) {

            $kaeuferPerson->id = $existingPersonId;

            /*
             * Die Person existiert bereits, darf aber noch kein
             * Ticket besitzen, weil sie sonst bereits jemand anderem
             * zugeordnet wäre.
             */
            if (ticketAlreadyExistsAnywhere(
                $conn,
                $kaeuferPerson->id
            )) {
                throw new BookingException(
                    "{$vorname} {$nachname} besitzt bereits ein Ticket und kann nicht erneut als Käufer verwendet werden.",
                    "duplicate",
                    $vorname,
                    $nachname,
                    $email
                );
            }

        } else {

            $kaeuferPerson->id = insertPerson(
                $conn,
                $kaeuferPerson
            );
        }

        // -------------------------------------------------
        // Käufer anlegen
        // -------------------------------------------------

        $kaeufer = new Kaeufer(
            $kaeuferPerson,
            $kaeuferData
        );

        $kaeufer->id = insertKaeufer(
            $conn,
            $kaeufer
        );
    }

    // =================================================
    // TICKETBESITZER PRÜFEN
    // =================================================
    //
    // data[0] ist IMMER der Käufer selbst.
    //
    // Deshalb muss der Käufer als erstes Ticket
    // berücksichtigt werden.
    //
    // =================================================

    $ticketChecks = [];

    // -------------------------------------------------
    // KÄUFER = EIGENES TICKET
    // -------------------------------------------------

    $buyerAlreadyHasOwnTicket = ticketExistsForKaeufer(
        $conn,
        $kaeufer->id,
        $kaeuferPerson->id
    );

    /*
     * Wenn der Käufer bereits ein eigenes Ticket besitzt,
     * wird KEIN zweites Ticket für dieselbe Person angelegt.
     */
    if (!$buyerAlreadyHasOwnTicket) {

        $ticketChecks[] = [
            "person" => $kaeuferPerson,
            "existing" => true,
            "isBuyer" => true
        ];
    }

    // =================================================
    // WEITERE TICKETBESITZER
    // =================================================

    $ticketDataList = array_slice($data, 1);

    foreach ($ticketDataList as $ticketData) {

        if (!is_array($ticketData)) {
            throw new BookingException(
                "Ungültige Ticketdaten.",
                "invalidTicketData"
            );
        }

        $person = new Person($ticketData);

        // -------------------------------------------------
        // Prüfen, ob Person bereits existiert
        // -------------------------------------------------

        $existingPersonId = personExistsByData(
            $conn,
            $person->vorname,
            $person->nachname
        );

        if ($existingPersonId !== null) {

            $person->id = $existingPersonId;

            // -------------------------------------------------
            // Person besitzt bereits irgendein Ticket?
            // -------------------------------------------------

            if (ticketAlreadyExistsAnywhere(
                $conn,
                $person->id
            )) {
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
                "isBuyer" => false
            ];

        } else {

            $ticketChecks[] = [
                "person" => $person,
                "existing" => false,
                "isBuyer" => false
            ];
        }
    }

    // =================================================
    // WICHTIGE KONTROLLE
    // =================================================
    //
    // Die Anzahl der tatsächlich neuen Tickets muss
    // der erwarteten Anzahl entsprechen.
    //
    // Bei einem neuen Käufer:
    //
    // Käufer + weitere Personen
    //
    // Bei einem bestehenden Käufer:
    //
    // Bereits vorhandenes eigenes Ticket wird nicht erneut
    // angelegt.
    //
    // =================================================

    $newTickets = count($ticketChecks);

    /*
     * Bei einem neuen Käufer muss exakt die gesamte
     * Buchungsanzahl angelegt werden.
     */
    if ($existingKaeuferId === null) {

        if ($newTickets !== $actualTickets) {
            throw new BookingException(
                "Die Anzahl der zu erstellenden Tickets stimmt nicht mit der Buchung überein.",
                "ticketCountMismatch"
            );
        }
    }

    // =================================================
    // TICKETS SCHREIBEN
    // =================================================

    $newCharges = 0.0;

    $results = [];

    foreach ($ticketChecks as $ticketCheck) {

        /** @var Person $person */
        $person = $ticketCheck["person"];

        // -------------------------------------------------
        // Neue Person anlegen
        // -------------------------------------------------

        if (!$ticketCheck["existing"]) {

            $person->id = insertPerson(
                $conn,
                $person
            );
        }

        // -------------------------------------------------
        // Ticketbesitzer anlegen
        // -------------------------------------------------

        insertTicketBesitzer(
            $conn,
            new TicketBesitzer(
                $kaeufer->id,
                $person->id
            )
        );

        // -------------------------------------------------
        // Preis addieren
        // -------------------------------------------------

        $newCharges += $person->sum;

        // -------------------------------------------------
        // Ergebnis
        // -------------------------------------------------

        $results[] = [
            "status" => "success",
            "message" =>
                "{$person->vorname} {$person->nachname} wurde als Ticketbesitzer hinzugefügt.",
            "vorname" => $person->vorname,
            "nachname" => $person->nachname
        ];
    }

    // =================================================
    // KÄUFER AKTUALISIEREN
    // =================================================
    //
    // Bei einem neuen Käufer wurde sein eigenes Ticket
    // bereits durch insertKaeufer() berücksichtigt.
    //
    // Deshalb werden hier nur zusätzliche Tickets
    // hinzugefügt.
    //
    // =================================================

    if ($existingKaeuferId !== null) {

        /*
         * Bei einem bestehenden Käufer werden nur die
         * tatsächlich neu angelegten Tickets addiert.
         */
        updateKaeuferTotals(
            $conn,
            $kaeufer->id,
            $newCharges,
            $newTickets
        );

    } else {

        /*
         * Beim neuen Käufer wurde bereits:
         *
         * tickets = 1
         * charges = Käuferpreis
         *
         * gespeichert.
         *
         * Jetzt kommen die weiteren Tickets dazu.
         */

        $additionalTickets = $newTickets - 1;

        $additionalCharges = $newCharges - $kaeuferPerson->sum;

        if ($additionalTickets > 0) {

            updateKaeuferTotals(
                $conn,
                $kaeufer->id,
                $additionalCharges,
                $additionalTickets
            );
        }
    }

    // =================================================
    // TRANSAKTION ABSCHLIESSEN
    // =================================================

    $conn->commit();

    // =================================================
    // ERFOLGSANTWORT
    // =================================================

    echo json_encode([
        "status" => "finished",

        "kaeufer" => [
            "id" => $kaeufer->id,
            "vorname" => $kaeuferPerson->vorname,
            "nachname" => $kaeuferPerson->nachname,
            "email" => $kaeuferPerson->email
        ],

        "newTickets" => $newTickets,
        "newCharges" => $newCharges,

        "results" => $results
    ]);

    exit();

} catch (BookingException $e) {

    // =================================================
    // BUCHUNG ABGELEHNT
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
            ]
        ]
    ]);

    exit();

} catch (Throwable $e) {

    // =================================================
    // TECHNISCHER FEHLER
    // =================================================

    $conn->rollback();

    http_response_code(500);

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);

    exit();
}