<form action="" method="post" id="form">

    <!-- SAMPLE TICKET: DISPLAYING OF THE TICKETS-->
    <div id="ticketsContainer"> 
        <div id="einzahlung" class="ticket">
            <div class="input-field name">
                <input type="email" id="f-email" name="search" required>
                <label for="f-email">Email:</label>
            </div>
        </div>
    </div>

    <div id="suggestionContainer" class="ticket">
        <div id="suggestions">
        </div>
    </div>

    <!-- END OF TICKET-SECTION: BUYING AND SUBMITTING OF THE TICKETS! -->
    <div id="downerTickets">
        <button type="button" id="getAllTicketsForCustomerButton" class="inactive">Käufer suchen</button>
    </div>
</form>

        <div id="displayAllTicketsContainer">
            <div class="table_component_käufer table_component" role="region" tabindex="0">
                <table>
                    <caption>Käufer</caption>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Vorname</th>
                            <th>Nachname</th>
                            <th>Tickets</th>
                            <th>Zu Bezahlen</th>
                            <th>Gezahlt<sup>exklusiv 0.9% (PayPal)</sup></th>
                            <th>Offen</th>
                        </tr>
                        <tr>
                            <td>11</td>
                            <td>Justus</td>
                            <td>Hoffmann</td>
                            <td>2</td>
                            <td>24.00</td>
                            <td>0.00</td>
                            <td>24.00</td>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="table_component_tickets table_component" role="region" tabindex="0">
                <table>
                    <caption>Tickets</caption>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Vorname</th>
                            <th>Nachname</th>
                            <th>Email</th>
                            <th>Alter</th>
                            <th>Schule</th>
                            <th>Kosten</th>
                        </tr>
                        <tr>
                            <td>11</td>
                            <td>Justus</td>
                            <td>Hoffmann</td>
                            <td>jcjjust@gmail.com</td>
                            <td>18</td>
                            <td>MCG</td>
                            <td>12.00</td>
                        </tr>
                        <tr>
                            <td>12</td>
                            <td>Lea</td>
                            <td>Uppendahl</td>
                            <td>leauppendahl@web.de</td>
                            <td>18</td>
                            <td>MCG</td>
                            <td>12.00</td>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div id="setFinancing">
                <form action="" id="financing" method="POST">
                    <div id="money-form-left">
                        <div class="input-field money">
                            <input type="number" min="0" id="receivedMoney" name="money" required>
                            <label for="receivedMoney">Empfangenes Geld in €:</label>
                        </div>
                        <select name="method" id="pay-method">
                            <option value="-" disabled selected>Methode: Bitte auswählen</option>
                            <option value="PayPal">PayPal</option>
                            <option value="Überweisung">Überweisung</option>
                            <option value="Bar">Bar</option>
                            <option value="Abendkasse" disabled>Abendkasse</option>
                        </select>
                    </div>
                    <div id="money-form-right">
                        <input type="button" value="Eintragen" id="set-money-btn">
                    </div>
                </form>
            </div>
        </div>

        <!-- div für das Displayen der Nachrichten aus js -->
        <div id="display">
            <div id="display_icon">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <div id="display_textContainer">
                <p id="display_textNode">Hier steht eine Nachricht!</p>
            </div>
        </div>