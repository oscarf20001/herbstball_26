<?php
require_once __DIR__ . '/../config.php'; // Holt BASE_PATH und BASE_URL aus config.php
require_once BASE_PATH . '/server/php/html-structure/extract_part-URL.php';

$outputURLEnding = getOutputURLEnding();

session_start();

// Logout abfangen
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// Logout abfangen
if (isset($_GET['refresh'])) {
    echo "Hier würden wir refreshen haha";
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Herbstball des MCG 2025 - Powered by Metis</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/client/styles/barStyles.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/client/styles/einzahlungen.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/client/styles/inputFields.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/client/styles/tables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/client/styles/dashboard.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/client/styles/cookieBanner.css">
    <script src="https://kit.fontawesome.com/b9446e8a7d.js" crossorigin="anonymous"></script>
    <script type="module" src="<?= BASE_URL ?>/client/scripts/cookies.js" defer></script>
    <script type="module" src="<?= BASE_URL ?>/client/scripts/denied.js" defer></script>
</head>
<body>

    <!-- DEFAULT TEMPLATE LADEN -->
    <?php
        require(BASE_PATH . '/server/php/html-structure/DEFAULT-HTML-TEMPLATE.php');
    ?>
</body>
</html>