<?php

require '../php/db_connection.php';
require '../../vendor/autoload.php'; // Autoloader einbinden

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$logHandle = fopen(__DIR__ . '/mail.log', 'a'); // oder anderer Pfad

$sqlGetAllMails = "SELECT email, vorname, kaeufer.open_charges
FROM person 
INNER JOIN kaeufer 
ON kaeufer.person_id = person.id
WHERE kaeufer.open_charges > 0 AND person.id > 75";
//$sqlGetAllMails = "SELECT email, vorname FROM k150883_fruehlingsball.käufer WHERE id > 250 LIMIT 250";
$stmt = $conn->prepare($sqlGetAllMails);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $allMails[] = [
        'email' => $row['email'],
        'vorname' => $row['vorname'],
        'open' => $row['open_charges']
    ];
}

//stark.eventsolution@gmail.com

for ($i=0; $i < count($allMails); $i++) { 
    //echo $allMails[$i]['vorname'] . "<br>";

    $nachricht = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Ticketbezahlung Herbstball 2025 MCG-FFR</title>
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
            margin-bottom: 24px;
            padding: 12px 24px;
            font-size: 16px;
            color: white;
            background-color: #7F63F4;
            text-decoration: none;
            border-radius: 5px;
            color: #ffffff;
        }
        .qr-section {
            text-align: left;
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
        <h1>Hey " . $allMails[$i]['vorname'] . ",</h1>
        <p>
            ihr habt eure Tickets gesichert – jetzt geht’s um die Bezahlung!<br><br>

            Mir ist zu Ohren gekommen, dass ihr das noch nicht von eurer To-Do Liste abgehakt habt. Ab sofort könnt ihr eure Tickets ganz bequem bezahlen:<br><br>

            🏫 direkt in den Schulen des Marie-Curie-Gymnasiums (MCG) oder des Friedlieb-Runge-Gymnasiums (FFRG). Der Verkauf an den Schulen wird von den Verantwortlichen der jeweiligen Schule selber koordiniert.<br>
            🎟️ per Vor-Ort Bezahlung (ab 21 Uhr Abendkasse; + 2,50€)<br>
            💸 ganz easy per PayPal (am Veranstaltungstag wird kein PayPal mehr akzeptiert)<br>
        </p>

        <p>
            Bei dir handelt es sich um eine offene Summe von:<br>
            <strong style='color:#c0392b;'>".$allMails[$i]['open']."€</strong>
        </p>

        <div class='qr-section'>
            Wenn du dich für PayPal entscheidest, scanne den folgenden PayPal-QR-Code und überweise die gerade genannte Summe mit dem folgenden Verwendungszweck:<br><br>
            <strong>'" . str_replace("@", "at", $allMails[$i]['email']) . " Herbstball'</strong><br>
            </p><br>
            <img src='cid:paypal_qr' alt='QR zur Bezahlung' style='max-width: 100%; height: auto; border-radius: 6px;'>
        </div>

        <p style='color:#c0392b;'>
            Wichtig: Ab <strong>21:00 Uhr</strong> gilt ausschließlich Abendkasse – und da kommen noch einmal extra Kosten oben drauf. Also: sichert euch vorher eure Tickets, um Geld zu sparen!
        </p>

        <p>
            Und denkt dran: So ein Abend macht mit vielen Freunden noch viel mehr Spaß! 🎉<br>
            Also ladet unbedingt noch weitere Leute ein und feiert mit uns gemeinsam eine unvergessliche Herbstnacht. 🍂✨
        </p>

        <p> 
            <a href='https://curiegymnasium.de/' class='cta-button'> 🎟️ Tickets holen </a> 
        </p>

        <p>
            Wir sehen uns auf der Tanzfläche – mit euch, eurer Crew und jeder Menge Energie!<br><br>
            Beste Grüße,<br>
            euer Gordon ✨
        </p>

        <div class='footer'>
            *Alle Angaben ohne Gewähr; Änderungen vorbehalten
        </div>
        ";

        try {
            $mail = new PHPMailer(true);
            
            // SMTP-Konfiguration
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'];
            $mail->Password = $_ENV['MAIL_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $_ENV['MAIL_PORT'];
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            // Absender und Empfänger
            $mail->setFrom($_ENV['MAIL_USERNAME'], 'Marie-Curie Gymnasium');
            $mail->addReplyTo('oscar-streich@t-online.de', 'Oscar');
            $mail->addAddress($allMails[$i]['email'], $allMails[$i]['vorname']);

            // E-Mail-Inhalt
            $mail->AddEmbeddedImage('images/paypal.jpeg', 'paypal_qr');
            $mail->isHTML(true);
            $mail->Body = $nachricht;
            $mail->Subject = 'Herbstball-Tickets: So einfach zahlst du jetzt 💸🍂';
            $mail->AltBody = 'Hier Tickets für den Herbstball des MCG 2025 sichern: https://www.curiegymnasium.de/';

            // E-Mail senden
            // E-Mail senden und loggen
            if ($mail->send()) {
                writeToLog($logHandle, "ERFOLG: E-Mail an {$allMails[$i]["email"]} gesendet.");
            } else {
                writeToLog($logHandle, "FEHLER: E-Mail an {$allMails[$i]["email"]} nicht gesendet. Fehler: " . $mail->ErrorInfo);
            }

            // Empfänger und Anhänge leeren
            $mail->clearAddresses();
            $mail->clearAttachments();
            sleep(1);
        } catch (Exception $e) {
            writeToLog($logHandle, "FEHLER: E-Mail an {$allMails[$i]["email"]} nicht gesendet. Fehler: {$mail->ErrorInfo}");
        }
}

function writeToLog($handleOrPath, string $message): void {
    // Aktuelles Datum/Zeit im ISO-Format
    $timestamp = date('Y-m-d H:i:s');

    // Formatierte Logzeile
    $logLine = "[{$timestamp}] {$message}\n";

    // Falls ein Datei-Handle übergeben wurde
    if (is_resource($handleOrPath)) {
        fwrite($handleOrPath, $logLine);
    } 
    // Falls ein Dateipfad übergeben wurde
    elseif (is_string($handleOrPath)) {
        file_put_contents($handleOrPath, $logLine, FILE_APPEND | LOCK_EX);
    } 
    else {
        error_log("writeToLog: Ungültiger Parameter für Log-Ziel.");
    }
}