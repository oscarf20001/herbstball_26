<div id="mainContainer">
    <?php 
        if (!isset($_SESSION['logged_in'])){
            require __DIR__ . '/../loginForm.php';
        } else{
            // -- 👉 Hier wird der normale Resendbereich geladen 
            // Login-Versuch
            if (empty($_SESSION['logged_in']) || empty($_SESSION['permissions']) || !in_array('can_resend_mails', $_SESSION['permissions'])) {
                echo "<script>
                    alert('Permission denied');
                    window.location.href = document.referrer && document.referrer !== window.location.href 
                        ? document.referrer 
                        : '../index.php';
                </script>";
                exit;
            }
            ?>
                <form action="" method="post" id="form">
                    <div id="ticketsContainer">
                        <div id="mail" class="ticket">
                            <div class="input-field name">
                                <input type="email" id="f-email" name="search" required>
                                <label for="f-email">Email:</label>
                            </div>
                            <div id="feedback">
                                <div id="feedback-positiv" class="feedbackContainer">
                                    <div class="feedback-light-bulb bulb-new" id="light-bulb-result-negative"></div>
                                    <div class="feedback-text">Email neu</div>
                                </div>
                                <div id="feedback-positiv" class="feedbackContainer">
                                    <div class="feedback-light-bulb bulb-recognized" id="light-bulb-result-positive"></div>
                                    <div class="feedback-text">Email wiedererkannt</div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div id="suggestionContainer" class="ticket">
                        <div id="suggestions">
                        </div>
                    </div>

                    <!-- END OF TICKET-SECTION: BUYING AND SUBMITTING OF THE TICKETS! -->
                    <div id="downerTickets">
                        <select name="" id="selectNewEmailType">
                            <option value="" disabled selected>Bitte auswählen</option>
                            <option value="submit_ticket">Reservierungsbestätigung</option>
                            <option value="ticket">Ticket senden</option>
                            <option value="confirm_payment" disabled>Kosten clearen</option>
                        </select>
                        <button type="button" id="getAllTicketsForCustomerButton" class="inactive getAllTicketsForCustomerButton"><i class="fa-solid fa-paper-plane"></i>Email senden</button>
                    </div>
                </form>
            <?php
        }
    ?>
</div>

<script>
    document.getElementById("suggestions").addEventListener("click", function(event) {
        // Prüfen, ob auf ein <p> geklickt wurde
        if (event.target.tagName === "P") {
            const email = event.target.textContent;
            setLightBulb('positive');
        }
    });

    const fEmail = document.getElementById('f-email');
    fEmail.addEventListener('keyup', () => {
        let suggestionElements = document.querySelectorAll("#suggestions p");
        let inputValue = fEmail.value.trim().toLowerCase();

        if(inputValue != ''){
            setLightBulb('negative');
            document.getElementById('getAllTicketsForCustomerButton').classList.remove('inactive');
            document.getElementById('getAllTicketsForCustomerButton').classList.add('active');
        }

        for (let p of suggestionElements) {
            if (p.textContent.trim().toLowerCase() === inputValue) {
                setLightBulb('positive');
            }else{
                setLightBulb('negative');
            }
        }

        if(inputValue === ''){
            setLightBulb('booth-off');
        }
    });

    document.getElementById('getAllTicketsForCustomerButton').addEventListener('click', () => {
        const selectElement = document.getElementById('selectNewEmailType');
        const fEmail = document.getElementById('f-email');
        let emailFieldValue = document.getElementById('f-email').value;
        
        if(selectElement.value == '' || selectElement.value === ''){
            alert("Error: Keine Methode ausgewählt")
            return;
        }

        if(emailFieldValue == '' || emailFieldValue == undefined){
            alert("Error: Input leer");
            return;
        }

        alert("Email an: " + emailFieldValue + '; Methode: ' + selectElement.value);
        setLightBulb('booth-off');
    });

    function setLightBulb(element){
        switch (element) {
            case 'booth-off':
                document.getElementById('light-bulb-result-positive').classList.remove('active');
                document.getElementById('light-bulb-result-negative').classList.remove('active');        
                break;

            case 'positive':
                document.getElementById('light-bulb-result-negative').classList.remove('active');
                document.getElementById('light-bulb-result-positive').classList.add('active');
                break;

            case 'negative':
                document.getElementById('light-bulb-result-positive').classList.remove('active');
                document.getElementById('light-bulb-result-negative').classList.add('active');
                break;
        
            default:
                document.getElementById('light-bulb-result-positive').classList.remove('active');
                document.getElementById('light-bulb-result-negative').classList.remove('active');
                break;
        }
    }
</script>