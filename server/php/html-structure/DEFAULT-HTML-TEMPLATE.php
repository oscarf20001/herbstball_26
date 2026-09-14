<!--
===========================================================================
|                                                                         |
|        GENERATE THE DEFAULT ELEMENTS WITHOUT THE MAIN CONTAINER         |
|                                                                         |
===========================================================================
-->

<div id="display">
    <?php
        //require($basePath . '/server/php/html-structure/displayMessages.php');
        require __DIR__ . '/displayMessages.php';
    ?>
</div>

<header id="header">
    <?php
        //require($basePath . '/server/php/html-structure/header.php');
        require __DIR__ . '/header.php';
    ?>
</header>

<div id="sidebar">
    <?php
        //require($basePath . '/server/php/html-structure/sidebar.php');
        require __DIR__ . '/sidebar.php';
    ?>
</div>

<div id="logo">
    <?php
        //require($basePath . '/server/php/html-structure/logo.php');
        require __DIR__ . '/logo.php';
    ?>
</div>

<div id="cookies" style="display:none">
    <?php
        //require($basePath . '/server/php/html-structure/logo.php');
        require __DIR__ . '/cookieBanner.php';
    ?>
</div>

<footer id="footer">
    <?php
        //require($_SERVER['DOCUMENT_ROOT'] . '/Metis/herbstball_25/server/php/html-structure/footer.php');
        //require($basePath . '/server/php/html-structure/footer.php');
        require __DIR__ . '/footer.php';
    ?>
</footer>

<!--
===========================================================================
|                                                                         |
|     NOW WE GENERATE THE MAIN CONTAINER DEPENEND ON THE URL IT SENDS     |
|                                                                         |
===========================================================================
-->

<?php

require_once(BASE_PATH . '/server/php/html-structure/extract_part-URL.php');

$outputURLEnding = getOutputURLEnding();

if($outputURLEnding == 'index'){
    require __DIR__ . '/mainContainer/MC_index.php';
}else if($outputURLEnding == 'einzahlung'){
    require __DIR__ . '/mainContainer/MC_einzahlung.php';
}else if($outputURLEnding == 'admin'){
    require __DIR__ . '/mainContainer/MC_admin.php';
}else if($outputURLEnding == 'mails'){
    require __DIR__ . '/mainContainer/MC_mails.php';
}else if($outputURLEnding == 'bedingungen'){
    require __DIR__ . '/mainContainer/MC_bedingungen.php';
}else if($outputURLEnding == 'create_user'){
    require __DIR__ . '/mainContainer/MC_user.php';
}else if($outputURLEnding == 'musikwuensche'){
    require __DIR__ . '/mainContainer/MC_musikwuensche.php';
}else if($outputURLEnding == 'einlass'){
    require __DIR__ . '/mainContainer/MC_einlass.php';
}else if($outputURLEnding == 'imprint'){
    require __DIR__ . '/mainContainer/MC_imprint.php';
}else if($outputURLEnding == 'datasecurity'){
    require __DIR__ . '/mainContainer/MC_datasecurity.php';
}else{
    echo '<p><code style="color: red; font-weight:900;">Error: No specific (main)-container given. Contact -> oscar-streich@t-online.de</code></p>';
}

?>