<?php
// Jetzt BASE_PATH verwenden, um die Datei einzubinden
require_once(BASE_PATH . '/server/php/html-structure/extract_part-URL.php');

$outputURLEnding = getOutputURLEnding();
?>

<div class="header-left">
    <h1 id="headliner">HERBSTBALL 2026 <span id="post-Headline">- Marie Curie Gymnasium</span></h1>
    <?php
        if ($outputURLEnding == 'index' || $outputURLEnding == '') {
            echo '<p>🎟 Tickets vorbestellen</p>';
        } elseif ($outputURLEnding == 'einzahlung') {
            echo '<p>🤑 Geld einzahlen</p>';
        } elseif ($outputURLEnding == 'admin') {
            echo '<p>📈 Dashboard</p>';
        } elseif ($outputURLEnding == 'mails') {
            echo '<p>📧 Emails erneut versenden (Für den Fall, dass jemand zu dumm war, seine Mail richtig anzugeben)</p>';
        } elseif ($outputURLEnding == 'bedingungen') {
            echo '<p>📝 Teilnahmebedingungen der Veranstaltung</p>';
        } elseif ($outputURLEnding == 'create_user') {
            echo '<p>👮 Benutzerverwaltung</p>';
        } elseif ($outputURLEnding == 'musikwuensche') {
            echo '<p>🎵 Musikwünsche</p>';
        }elseif ($outputURLEnding == 'einlass') {
            echo '<p>🙎‍♂️ Einlass</p>';
        }elseif ($outputURLEnding == 'imprint') {
            echo '<p>ℹ️ Impressum</p>';
        }elseif ($outputURLEnding == 'datasecurity') {
            echo '<p>ℹ️ Datenschutz</p>';
        }else{
            echo '<p><code style="color: red; font-weight:900;">Error: No specific description given. Contact -> oscar-streich@t-online.de</code></p>';
        }
    ?>
</div>
<div class="header-right">
    <?php if (isset($_SESSION['logged_in'])): ?>
        <div id="logout-container">
            <a href="?logout=1" id="logout-button">Logout</a>
        </div>
    <?php endif; ?>
</div>
