<div id="mainContainer">
        <?php 
            if (!isset($_SESSION['logged_in'])){
                require __DIR__ . '/../loginForm.php';
            } else{
                // -- 👉 Hier wird der normale Einzahlungsbereich geladen
                // Login-Versuch
                if (empty($_SESSION['logged_in']) || empty($_SESSION['permissions']) || !in_array('can_pay', $_SESSION['permissions'])) {
                    echo "<script>
                        alert('Permission denied');
                        window.location.href = document.referrer && document.referrer !== window.location.href 
                            ? document.referrer 
                            : '../index.php';
                    </script>";
                    exit;
                }
                require __DIR__ . '/../einzahlungsFormularUndTabellen.php';
            }
        ?>
    </div>