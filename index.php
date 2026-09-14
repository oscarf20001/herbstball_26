<?php

session_start();
require_once __DIR__ . '/config.php';

// Logout abfangen
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Login-Versuch
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['username'])) {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    if ($user === 'admin' && $pass === 'herbstball25') {
        $_SESSION['logged_in'] = true;
    } else {
        $error = "Falscher Benutzername oder Passwort!";
    }
}

// Basis-Pfad setzen
$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');

// Prüfen, ob wir lokal sind
$isLocalhost = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false
    || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false;

// Falls lokal, Basis-Pfad anpassen, aber doppelte Verzeichnisse vermeiden
if ($isLocalhost) {
    // Beispiel: Wenn DOCUMENT_ROOT schon 'httpdocs' enthält, aber NICHT 'herbstball_25'
    if (!str_ends_with($docRoot, 'herbstball_25')) {
        $basePath = $docRoot . '/Metis/herbstball_25';
    } else {
        $basePath = $docRoot;  // Falls der Pfad schon korrekt ist
    }
} else {
    // Auf dem Server nehme einfach DOCUMENT_ROOT als Basis
    $basePath = $docRoot;
}

// Debug: Pfad anzeigen (zum Testen, später entfernen)
// var_dump($basePath);
// exit;

// Pfad zur benötigten Datei
$extractPartUrlPath = 'server/php/html-structure/extract_part-URL.php';

if (!file_exists($extractPartUrlPath)) {
    die("Fehler: Die Datei extract_part-URL.php wurde nicht gefunden unter: $extractPartUrlPath");
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weihnachtsball des MCG 2025 - Powered by Metis</title>

    <link rel="stylesheet" href="client/styles/barStyles.css">
    <link rel="stylesheet" href="client/styles/form.css">
    <link rel="stylesheet" href="client/styles/inputFields.css">
    <link rel="stylesheet" href="client/styles/cookieBanner.css">

    <!-- Standard-Favicon -->
    <link rel="icon" href="/favicon.ico" type="image/x-icon">

    <!-- PNG-Versionen (für moderne Browser) -->
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">

    <!-- Für Apple Geräte -->
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">


    <script type="module" src="client/scripts/main.js"></script>
    <script type="module" src="client/scripts/finances.js"></script>
    <script type="module" src="client/scripts/dataTicket.js" defer></script>
    <script type="module" src="client/scripts/checks.js" defer></script>
    <script type="module" src="client/scripts/denied.js" defer></script>
    <script src="client/scripts/analytics.js"></script>
    <script src="client/scripts/cookies.js" defer></script>
    <script type="module" src="client/scripts/displayMessages.js"></script>
    
    <script src="https://kit.fontawesome.com/b9446e8a7d.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js"></script>
</head>
<body>

    <!-- DEFAULT TEMPLATE LADEN -->
    <?php
        require('server/php/html-structure/DEFAULT-HTML-TEMPLATE.php');
    ?>

</body>
</html>