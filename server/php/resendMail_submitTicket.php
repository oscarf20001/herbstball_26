<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require 'db_connection.php';
require __DIR__ . '/../../vendor/autoload.php'; // Autoloader einbinden

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$results = [];

class EmpfaengerPerson {
    public ?int $id = null;
    public string $vorname;
    public string $nachname;
    public string $email;
    public ?int $overAllSum = null;
    public array $tickets;

    public function __construct(mysqli $conn, array $data) {
        $this->vorname = $data['vorname'];
        $this->nachname = $data['nachname'];
        $this->email = $data['email'];
        $this->id = getIdForMail($conn, $this->vorname, $this->nachname, $this->email);
        $this->tickets = getTicketsFromCustomer($conn, $this->id);
        $this->overAllSum = getOverallSumOfCustomer($conn, $this->tickets);
    }
}

function getIdForMail($conn, $vorname, $nachname, $email){
    $getIdStmt = $conn->prepare("SELECT k.id FROM kaeufer k JOIN person p ON k.person_id = p.id WHERE p.vorname = ? AND p.nachname = ? AND p.email = ?");
    if (!$getIdStmt) return null;
    $getIdStmt->bind_param('sss', $vorname, $nachname, $email);

    if (!$getIdStmt->execute()) {
        $getIdStmt->close();
        return null;
    }

    $getIdStmt->bind_result($kaeuferId);
    if ($getIdStmt->fetch()) {
        $getIdStmt->close();

        return $kaeuferId;
    }

    $getIdStmt->close();
    return null;
}

function getTicketsFromCustomer($conn, $id){
    $tickets = [];

    $getPersonIDsForTicketsStmt = $conn->prepare("SELECT person_id FROM ticket_besitzer WHERE kaeufer_id = ?;");
    if(!$getPersonIDsForTicketsStmt) return null;

    $getPersonIDsForTicketsStmt->bind_param('i', $id);
    if(!$getPersonIDsForTicketsStmt->execute()){
        $getPersonIDsForTicketsStmt->close();
        $results[] = [
            "message" => "Fehler beim Ausführen des Statements"
        ];
        return null;
    }

    $result = $getPersonIDsForTicketsStmt->get_result();

    if ($result->num_rows === 0) {
        $results[] = [
            "message" => "Keine Tickets gefunden für Käufer-ID $id"
        ];
        return null;
    }

    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row['person_id']; // Jeden PersonId in das Array speichern
    }

    $getPersonIDsForTicketsStmt->close();

    // In $tickets sind die IDs gespeichert, die für die Tickets relevant sind; jetzt müssen die Daten der Tickets abgefragt werden; das geschieht mithilfe der Person_id = Teil des Arrays
    $persons = [];

    foreach ($tickets as $key => $value) {
        $getWholePersonsStmt = $conn->prepare("SELECT * FROM person WHERE id = ?;");
        if(!$getWholePersonsStmt) return null;

        $getWholePersonsStmt->bind_param('i', $value);
        if(!$getWholePersonsStmt->execute()){
            $getWholePersonsStmt->close();
            $results[] = [
                "message" => "Fehler beim Ausführen des Statements"
            ];
            return null;
        }

        $result = $getWholePersonsStmt->get_result();

        if ($result->num_rows === 0) {
            $results[] = [
                "message" => "Keine Personen gefunden für Personen-ID $id"
            ];
            return null;
        }

        $row = $result->fetch_assoc();
        $persons[] = $row; // Jeden PersonId in das Array speichern
        

        $getWholePersonsStmt->close();

    }

    return $persons;
}

function getOverallSumOfCustomer($conn, $tickets){
    $sum = 0;
    foreach ($tickets as $ticket) {
        $sum += (float)$ticket["sum"];
    }
    return $sum;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!is_array($data) || count($data) < 1) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Leere oder ungültige JSON']);
    exit;
}

// Käufer vorbereiten
$empfaengerData = $data['persons'][0];
$empfaengerPerson = new EmpfaengerPerson($conn, $empfaengerData);

$ntn = [];
$code = generateCodeFromId($empfaengerPerson);
$result = sentCodeToKaeufer($conn, $empfaengerPerson, $code);
$ntn[] = $result;

$imgPath = '../mail/images/paypal.jpeg';
$imgData = base64_encode(file_get_contents($imgPath));
$src = 'data:image/jpeg;base64,' . $imgData;

$iban = 'DE61 1605 0000 1102 4637 24';

$mail = new PHPMailer(true);

try {
    $nachricht = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Ticketreservierung Herbstball 2025 MCG-FFR</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f6f6f6;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 24px;
            border-radius: 8px;
        }
        h1 {
            font-size: 24px;
            color: #333;
        }
        p {
            font-size: 16px;
            color: #333;
            line-height: 1.6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .cta-button {
            display: inline-block;
            margin-top: 24px;
            padding: 12px 24px;
            font-size: 16px;
            color: white;
            background-color: #7F63F4;
            text-decoration: none;
            border-radius: 5px;
            color: #ffffff;
        }
        .qr-section {
            text-align: center;
            margin: 32px 0;
        }
        .footer {
            font-size: 12px;
            color: #888;
            text-align: center;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Hey " . $empfaengerPerson->vorname . ",</h1>
        <p>
            Du hast es geschafft und dir deine grandiosen Tickets für den Herbstball 2025 gesichert – vielen Dank dafür! 🎉
        </p>
        <p>
            <strong>Hier sind alle wichtigen Infos:</strong><br><br>
            📅 Datum: 17.10.2025<br>
            🕓 Uhrzeit: Einlass ab 18:45 Uhr, Beginn um 20:00 Uhr, Ende: 01:00 Uhr<br>
            📍 Adresse: Friedrich-Wolf-Straße 31, Oranienburg
        </p>
        <p>
            Ab wann, wo und wie Bar gezahlt werden kann, teilen wir euch noch rechtzeitig mit!
        </p>
        <p style='color:#c0392b;'>
            <strong>Wichtig:</strong> Unbezahlte Tickets werden am <strong>10.10.2025 um 23:59 Uhr</strong> automatisch storniert!
        </p>

        <h2>🧾 Deine Reservierung:</h2>
        <table>
            <tr>
                <th>Gesamtsumme</th>
                <td>" . $empfaengerPerson->overAllSum . "€</td>
            </tr>
        </table>

        <h3>🎟️ Deine Tickets:</h3>
        <table>
            <thead>
                <tr>
                    <th>Vorname</th>
                    <th>Nachname</th>
                    <th>Summe</th>
                </tr>
            </thead>
            <tbody>";
                foreach($empfaengerPerson->tickets as $ticket){
                    $nachricht .= "
                    <tr>
                        <td>" . $ticket['vorname'] . "</td>
                        <td>" . $ticket["nachname"] . "</td>
                        <td>" . $ticket["sum"] . "€</td>
                    </tr>";
                }
$nachricht .= "
            </tbody>
        </table>

        <p>
            <a href='https://curiegymnasium.de/server/mail/bestaetigen.php?id=" . $empfaengerPerson->id . "&token=" . $code . "' class='cta-button'>
                ✅ Tickets bestätigen
            </a>
        </p>

        <div class='qr-section'>
            <p><strong>Wenn du schon bezahlen möchtest:</strong><br>
            Scanne den folgenden PayPal-QR-Code und überweise die oben genannte Gesamtsumme mit dem folgenden Verwendungszweck:<br><br>
            <strong>" . str_replace("@", "at", $empfaengerPerson->email) . " Herbstball</strong>
            </p><br>
            <img src='cid:paypal_qr' alt='QR zur Bezahlung' style='max-width: 100%; height: auto; border-radius: 6px;'>
        </div>

        <p>
            Wir freuen uns riesig auf einen crazytastischen Abend mit euch! 💕<br><br>
            Beste Grüße,<br>
            Gordon
        </p>

        <div class='footer'>
            *Alle Angaben ohne Gewähr; Änderungen vorbehalten
        </div>
    </div>
</body>
</html>";


    // SMTP-Konfiguration
    $mail->isSMTP();
    $mail->Host       = $mailHost;
    $mail->SMTPAuth   = true;
    $mail->Username   = $mailUsername;
    $mail->Password   = $mailPassword;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $mailPort;
    $mail->CharSet    = 'UTF-8';

    // Empfänger
    $mail->setFrom($mailUsername, 'Marie-Curie Gymnasium');
    $mail->addReplyTo('oscar-streich@t-online.de', 'Oscar');
    $fullName = $empfaengerPerson->vorname . " " . $empfaengerPerson->nachname;
    $mail->addAddress($empfaengerPerson->email, $fullName);

    // Nachricht
    $mail->AddEmbeddedImage('../mail/images/paypal.jpeg', 'paypal_qr');
    $mail->isHTML(true);
    $mail->Subject = 'Fancytastische Buchungsbestätigung: Herbstball 2025';
    $mail->Body    = $nachricht;

    $mail->send();
    log_data_mail($conn, $empfaengerPerson);
    #sendJsonResponse(['message' => 'E-Mail erfolgreich gesendet', 'sum' => number_format($sum, 2)]);
} catch (Exception $e) {
    echo json_encode([
        'error' => 'E-Mail konnte nicht gesendet werden',
        'info' => $mail->ErrorInfo
    ]);
    exit;
}

function sentCodeToKaeufer(mysqli $conn, EmpfaengerPerson $empfaengerPerson, int $code): array {
    $response = [];

    $stmt = $conn->prepare("UPDATE kaeufer SET checked = ? WHERE id = ?");
    if (!$stmt) {
        return [
            "status" => "error",
            "message" => "Statement preparation failed",
            "code" => $code
        ];
    }

    $stmt->bind_param("ii", $code, $empfaengerPerson->id);
    if (!$stmt->execute()) {
        $stmt->close();
        return [
            "status" => "error",
            "message" => "Statement execution failed",
            "code" => $code
        ];
    }

    $stmt->close();

    return [
        "status" => "success",
        "message" => "Code erfolgreich gesetzt für Käufer ID {$empfaengerPerson->id}",
        "code" => $code
    ];
}

function generateCodeFromId(EmpfaengerPerson $empfaengerPerson): string {
    $hash = hash('sha256', 'secret_salt' . microtime() . random_int(0,12345678));
    $decimal = gmp_strval(gmp_init(substr($hash, 0, 15), 16), 10);
    $shortCode = (int) substr($decimal, 0, 10);

    //Optional: Debug global ausgeben (wenn du willst)
    global $results;
    $results[] = [
        "ID" => $empfaengerPerson->id,
        "Hash" => $hash,
        "Decimal" => $decimal,
        "ShortCode" => $shortCode
    ];

    return $shortCode;
}

function log_data_mail($conn, $empfaengerPerson){
    #echo getcwd();
    $filename = 'fruelingsball.log';
    // Überprüfen, ob die Datei existiert
    if (!file_exists($filename)) {
        // Datei erstellen
        $file = fopen($filename, 'w'); // 'w' erstellt die Datei, falls sie nicht existiert
        if ($file) {
            fclose($file); // Schließt die neu erstellte Datei
            #echo "Datei '$filename' wurde erstellt.\n";
        } else {
            die("Fehler beim Erstellen der Datei '$filename'.");
        }
    }

    // Datei öffnen (zum Schreiben oder Anhängen)
    $file = fopen($filename, 'a'); // 'a' hängt den Inhalt an

    #time
    $microtime = microtime(true);
    $milliseconds = sprintf('%03d', ($microtime - floor($microtime)) * 1000);
    $time = date('Y:m:d / H:i:s') . ':' . $milliseconds;

    if ($file) {
        fwrite($file, $time . ': ✅ Mail should be sent to: ' . $empfaengerPerson->email . PHP_EOL); // Mail schreiben     
        fwrite($file, 'But check the internal webmail for possible errors!' . PHP_EOL); // Mail schreiben     
        fwrite($file, '---------------------------------------------------' . PHP_EOL); // Mail schreiben     
        #echo "Inhalt wurde erfolgreich in die Datei '$filename' geschrieben.";
        fclose($file); // Datei schließen
    } else {
        #echo "Fehler beim Öffnen der Datei '$filename'.";
    }
}

// Tickets vorbereiten
echo json_encode([
    'status' => 'finished',
    'empfaenger' => $empfaengerPerson,
    'ntn' => $ntn,
    'debug' => $results
]);