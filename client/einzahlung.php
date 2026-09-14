<?php

session_start();

// Logout abfangen
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: einzahlung.php");
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

require_once __DIR__ . '/../config.php'; // Holt BASE_PATH und BASE_URL aus config.php
require_once '../server/php/html-structure/extract_part-URL.php';

$outputURLEnding = getOutputURLEnding();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Herbstball des MCG 2025 - Powered by Metis</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/client/styles/barStyles.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/client/styles/einzahlungen.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/client/styles/inputFields.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/client/styles/tables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/client/styles/cookieBanner.css">

    <script type="module" src="<?= BASE_URL ?>/client/scripts/cookies.js" defer></script>
    <script type="module" src="<?= BASE_URL ?>/client/scripts/denied.js" defer></script>
    <script type="module" src="<?= BASE_URL ?>/client/scripts/searchEmails.js" defer></script>
    <script type="module" src="<?= BASE_URL ?>/client/scripts/einzahlung.js" defer></script>

    <script src="https://kit.fontawesome.com/b9446e8a7d.js" crossorigin="anonymous"></script>
    <!--<script type="module" src="client/scripts/main.js"></script>
    <script type="module" src="client/scripts/finances.js"></script>
    <script type="module" src="client/scripts/dataTicket.js" defer></script>
    <script type="module" src="client/scripts/checks.js" defer></script>
    <script type="module" src="client/scripts/displayMessages.js"></script>-->
</head>
<body>

    <!-- DEFAULT TEMPLATE LADEN -->
    <?php
        require('../server/php/html-structure/DEFAULT-HTML-TEMPLATE.php');
    ?>
</body>
</html>