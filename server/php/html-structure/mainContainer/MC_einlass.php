<div id="mainContainer">
    <?php 
        if (!isset($_SESSION['logged_in'])){
            require __DIR__ . '/../loginForm.php';
        } else{
            // -- 👉 Hier wird der normale Einzahlungsbereich geladen 
            // Login-Versuch
            if (empty($_SESSION['logged_in']) || empty($_SESSION['permissions']) || !in_array('can_view_reports', $_SESSION['permissions'])) {
                echo "<script>
                    alert('Permission denied');
                    window.location.href = document.referrer && document.referrer !== window.location.href 
                        ? document.referrer 
                        : 'index.php';
                </script>";
                exit;
            }
            ?>

        <!-- ============================== HIER HTML ============================== -->

        <div id="entrancePanelContainer" class="">
            <div id="eingabeContainer">
                <div class="input-field code">
                    <input type="text" id="barCodeInput" name="code">
                    <label for="code">HB2025_</label>
                </div>
                <input type="button" value="Suchen" id="barCodeSubmitButton">
            </div>

            <div id="displayResultContainer">
                <div class="dataSection">
                    <h2>🪪 IDs</h2>
                    <div class="dataTable">
                    <div class="row"><div class="label">ID:</div><div class="value" id="id">NaN</div></div>
                    <div class="row"><div class="label">Käufer-ID:</div><div class="value" id="kaeuferId">NaN</div></div>
                    </div>
                </div>

                <div class="dataSection">
                    <h2>🙎‍♂️ Angaben zur Person</h2>
                    <div class="dataTable">
                    <div class="row"><div class="label">Vorname:</div><div class="value" id="vorname">NaN</div></div>
                    <div class="row"><div class="label">Nachname:</div><div class="value" id="nachname">NaN</div></div>
                    <div class="row"><div class="label">Email:</div><div class="value" id="email">NaN</div></div>
                    <div class="row"><div class="label">Muttizettel:</div><div class="value" id="muttizettel">NaN</div></div>
                    <div class="row"><div class="label">Alter:</div><div class="value" id="alter">NaN</div></div>
                    <div class="row"><div class="label">Schule:</div><div class="value" id="schule">NaN</div></div>
                    </div>
                </div>

                <div class="dataSection">
                    <h2>💶 Informationen zur Bezahlung</h2>
                    <div class="dataTable">
                    <div class="row"><div class="label">Methode:</div><div class="value" id="methodPay">NaN</div></div>
                    <div class="row"><div class="label">Bezahlt bei:</div><div class="value" id="payedAt">NaN</div></div>
                    <div class="row"><div class="label">Bezahlt am:</div><div class="value" id="payedDate">NaN</div></div>
                    <div class="row"><div class="label">Offen:</div><div class="value" id="offen">NaN</div></div>
                    <div class="row"><div class="label">Ticketwert:</div><div class="value" id="person_charges">NaN</div></div>
                    <div class="row"><div class="label">Käufer-Wert:</div><div class="value" id="kaeufer_charges">NaN</div></div>
                    <div class="row"><div class="label">Käufer-offen:</div><div class="value" id="kaeufer_open">NaN</div></div>
                    <div class="row"><div class="label">Bezahlt:</div><div class="value" id="bezahlt">NaN</div></div>
                    </div>
                </div>
            </div>

            <div id="settingsContainer">
                <div id="checkContainer">
                    <div id="checkAge">
                        <p>Geburtsdatum auf Perso für über <span id="ageCheck_setDbAge">NaN</span>:</p>
                        <p>vor dem <span id="ageCheck_setControllDate">NaN</span></p>
                    </div>
                    <div id="checkName">
                        <p>Name auf Perso muss sein:</p>
                        <p><span id="nameCheck_setName">NaN</span></p>
                    </div>
                    <div id="armband">
                        <p>Armband:</p>
                        <p><span id="bracelet">NaN</span></p>
                    </div>
                </div>
            </div>

            <div id="confirmEntranceContainer">
                <input type="button" value="Einlassen" id="confirmEntranceButton">
            </div>
            
            <div id="messageContainer">
                <p id="message">Bereits eingelassen</p>
            </div>
        </div>

        <!-- ============================== ENDE HTML ============================== -->
            <?php
        }
    ?>
</div>