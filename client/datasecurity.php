<?php
require_once __DIR__ . '/../config.php'; // Holt BASE_PATH und BASE_URL aus config.php
require_once BASE_PATH . '/server/php/html-structure/extract_part-URL.php';

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
        require(BASE_PATH . '/server/php/html-structure/DEFAULT-HTML-TEMPLATE.php');
    ?>
</body>
</html>