<?php
require_once(BASE_PATH . '/server/php/html-structure/extract_part-URL.php');

$outputURLEnding = getOutputURLEnding();
?>

<div id="sidebarElementsWrapper">
    <!-- None Restricted Areas -->
    <div class="sidebarTextElement selected-site-active" id="site-index">
        <i class="fa-solid fa-ticket sideBarIconElement"></i>
        <a href="<?= BASE_URL ?>/index.php">Tickets</a>
    </div>
    <div class="sidebarTextElement" id="site-musikwuensche">
        <i class="fa-solid fa-music sideBarIconElement"></i>
        <a href="<?= BASE_URL ?>/client/musikwuensche.php">Musikwünsche</a>
    </div>
    
    <!-- Seperator Line -->
    <hr class="solid">

    <!-- Restricted Areas -->
    <div class="sidebarTextElement" id="site-einzahlung">
        <i class="fa-solid fa-euro-sign sideBarIconElement"></i>
        <a href="<?= BASE_URL ?>/client/einzahlung.php">Einzahlung</a>
    </div>
    <div class="sidebarTextElement" id="site-admin">
        <i class="fa-solid fa-lock sideBarIconElement"></i>
        <a href="<?= BASE_URL ?>/client/admin.php">Admin-Panel</a>
    </div>
    <div class="sidebarTextElement" id="site-mails">
        <i class="fa-solid fa-envelope sideBarIconElement"></i>
        <a href="<?= BASE_URL ?>/client/mails.php">Resend Mails</a>
    </div>
    <div class="sidebarTextElement" id="site-einlass">
        <i class="fa-solid fa-door-open sideBarIconElement"></i>
        <a href="<?= BASE_URL ?>/client/einlass.php">Einlass-Panel</a>
    </div>
    <div class="sidebarTextElement" id="site-create_user">
        <i class="fa-solid fa-user sideBarIconElement"></i>
        <a href="<?= BASE_URL ?>/client/create_user.php">Users</a>
    </div>
</div>

<script>
    const urlExtract = '<?= $outputURLEnding ?>';
    document.querySelectorAll('.sidebarTextElement').forEach(element => {
        element.classList.remove('selected-site-active');
    });

    const newActiveElement = document.getElementById('site-' + urlExtract);
    if (newActiveElement) {
        newActiveElement.classList.add('selected-site-active');
    }
</script>