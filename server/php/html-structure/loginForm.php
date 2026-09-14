<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// config.php einbinden
require_once(BASE_PATH . '/server/php/html-structure/extract_part-URL.php');
require_once __DIR__ . '../../db_connection.php';

$outputURLEnding = getOutputURLEnding();
$loginFormSubText = '';

switch ($outputURLEnding) {
    case 'index':
        $loginFormSubText = '';
        break;
        
    case 'einzahlung':
        $loginFormSubText = 'Einzahlungen';
        break;

    case 'admin':
        $loginFormSubText = 'Adminpanel';
        break;

    case 'mails':
        $loginFormSubText = 'Mails erneut senden';
        break;

    case 'create_user':
        $loginFormSubText = 'Benutzerverwaltung';
        break;

    case 'einlass':
        $loginFormSubText = 'Einlass-Panel';
        break;

    default:
        $loginFormSubText = '<code style="color: red;">Keine Unterschrift festgelegt</code>';
        break;
}

// Fehler-Variable vorbereiten
$error = '';

// Login-Versuch
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['username'], $_POST['password'])) {
    $user = trim($_POST['username']);
    $pass = sha1($_POST['password']);

    if ($conn->connect_error) {
        die("Datenbankverbindung fehlgeschlagen: " . $conn->connect_error);
    }

    // Benutzer abrufen
    $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Passwort prüfen (gehashed, z.B. password_hash)
        if ($pass == $row['password_hash']) {
            // Login erfolgreich
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];

            // Berechtigungen abrufen und als Array speichern
            $perm_stmt = $conn->prepare("
                SELECT p.name 
                FROM permissions p
                INNER JOIN user_permissions up ON up.permission_id = p.id
                WHERE up.user_id = ?
            ");
            $perm_stmt->bind_param("i", $row['id']);
            $perm_stmt->execute();
            $perm_result = $perm_stmt->get_result();

            $_SESSION['permissions'] = []; // Einfaches Array
            while ($perm = $perm_result->fetch_assoc()) {
                $_SESSION['permissions'][] = $perm['name'];
            }
            echo "<script>window.location.href = ''</script>";
            exit;
        } else {
            $error = "Falscher Benutzername oder Passwort!";
        }
    } else {
        $error = "Falscher Benutzername";
    }

    $stmt->close();
    $conn->close();
}
?>

<div id="login-box">
    <h2>Login erforderlich</h2>
    <sub><?= $loginFormSubText ?></sub>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST">
        <div class="input-field username">
            <input type="text" name="username" id="username" required>
            <label for="username">Benutzername:</label>
        </div>
        <div class="input-field">
            <input type="password" name="password" id="password" required>
            <label for="password">Passwort:</label>
        </div>
        <button type="submit" id="login-btn">Einloggen</button>
    </form>
</div>