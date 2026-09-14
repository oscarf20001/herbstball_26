import { displayMessage } from "./displayMessages.js";

const btn = document.getElementById('getAllTicketsForCustomerButton');
const form = document.getElementById('form');

// Haupt-Button für Ticketabfrage
btn.addEventListener('click', () => {
    const input = document.getElementById('f-email');

    if (!input.value.trim()) {
        console.log("Input-Feld leer");
        return;
    }

    if (btn.classList.contains('inactive')) {
        console.log("Button gesperrt!");
        return;
    }

    fetchAndRender(input.value.trim());
});

// Zum Aktivieren von Buttons
export function setBtnAsActive(btn) {
    btn.classList.remove('inactive');
    btn.classList.add('active');
}

// --------------------------------------------------
// Hauptfunktion zum Abrufen und Rendern der Tabellen
// --------------------------------------------------
function fetchAndRender(email) {
    fetch('../server/php/getAllTickets.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Serverantwort:', data);
        renderTables(data, email);

        // Generierung der Tickets
        if(data.kaeufer[0].open_charges <= 0){
            displayMessage("req_gen-ticket");
            trigger_generation_tickets(data);
        }

    })
    .catch(error => {
        console.error('Fehler beim Senden:', error);
    });
}

// --------------------------------------------------
// Tabellen rendern + Finanzierungsteil einfügen
// --------------------------------------------------
function renderTables(data, email) {
    form.reset();

    const container = document.getElementById('displayAllTicketsContainer');
    container.style.display = 'block';
    container.innerHTML = ''; // Vorherigen Inhalt löschen

    const buyer = data.kaeufer[0];

    const buyerTable = `
        <div class="table_component_käufer table_component" role="region" tabindex="0">
            <table>
                <caption>Käufer</caption>
                <thead>
                    <tr>
                        <th>Person-ID</th>
                        <th>Vorname</th>
                        <th>Nachname</th>
                        <th>Tickets</th>
                        <th>Zu Bezahlen</th>
                        <th id="table-column-Heading-AmountWasPaid">${buyer.method == 'PayPal' ? 'Gezahlt <sup style="font-weight: normal; color: var(--greyLighter);">exkl. 0.9%</sup>' : 'Gezahlt'}</th>
                        <th>Offen</th>
                        <th>Methode</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>${buyer.id}</td>
                        <td>${buyer.vorname}</td>
                        <td>${buyer.nachname}</td>
                        <td>${buyer.tickets}</td>
                        <td>${parseFloat(buyer.charges).toFixed(2)}</td>
                        <td>${buyer.method == 'PayPal' ? parseFloat(buyer.paid_charges).toFixed(2) + '<sub style="font-weight: normal; color: var(--successGreen);"> ' + parseFloat(buyer.paid_charges * 1.009).toFixed(2) + '</sub>' : parseFloat(buyer.paid_charges).toFixed(2)}</td>
                        <td>${parseFloat(buyer.open_charges).toFixed(2)}</td>
                        <td>${buyer.method == null ? 'Noch nicht bezahlt' : buyer.method}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    `;

    const personRows = data.persons.map(p => `
        <tr>
            <td>${p.id}</td>
            <td>${p.vorname}</td>
            <td>${p.nachname}</td>
            <td>${p.email}</td>
            <td>${p.age}</td>
            <td>${p.school}</td>
            <td>${parseFloat(p.sum).toFixed(2)}</td>
        </tr>
    `).join('');

    const personsTable = `
        <div class="table_component_tickets table_component" role="region" tabindex="0">
            <table>
                <caption>Tickets</caption>
                <thead>
                    <tr>
                        <th>Person-ID</th>
                        <th>Vorname</th>
                        <th>Nachname</th>
                        <th>Email</th>
                        <th>Alter</th>
                        <th>Schule</th>
                        <th>Kosten</th>
                    </tr>
                </thead>
                <tbody>
                    ${personRows}
                </tbody>
            </table>
        </div>
    `;

    const financingForm = `
        <div id="setFinancing">
            <form id="financing" method="POST">
                <div id="money-form-left">
                    <div class="input-field money">
                        <input type="number" min="0" step="0.01" id="receivedMoney" name="money" required>
                        <label for="receivedMoney">Empfangenes Geld in €:</label>
                    </div>
                    <select name="method" id="pay-method">
                        <option value="-" disabled selected>Bitte auswählen</option>
                        <option value="PayPal">PayPal</option>
                        <option value="Überweisung">Überweisung</option>
                        <option value="Bar">Bar</option>
                        <option value="Abendkasse" disabled>Abendkasse</option>
                    </select>
                </div>
                <div id="money-form-right">
                    <input type="button" value="Eintragen und Ticket senden" id="set-money-btn">
                </div>
            </form>
        </div>
    `;

    container.innerHTML = buyerTable + personsTable + financingForm;

    // --------------------------------------------------
    // Finanzierung Button aktivieren
    // --------------------------------------------------
    document.getElementById('set-money-btn').addEventListener('click', () => {
        handleFinancing(data.kaeufer[0].id, email);
    });

    // --------------------------------------------------
    // Event-Listener für PayPal-Handler
    // --------------------------------------------------
    document.getElementById('pay-method').addEventListener('change', function(){
        const selectedValue = document.getElementById('pay-method').value;
        const tableColumnToUpdate = document.getElementById('table-column-Heading-AmountWasPaid');
        
        if(selectedValue == 'PayPal'){
            tableColumnToUpdate.innerHTML = 'Gezahlt <sup style="font-weight: normal; color: var(--greyLighter);">exkl. 0.9%</sup>';
        }else{
            tableColumnToUpdate.innerHTML = 'Gezahlt';
        }
    });
}

// --------------------------------------------------
// Finanzierung prüfen & senden
// --------------------------------------------------
function handleFinancing(personID, email) {
    const moneyInput = document.getElementById('receivedMoney');
    const methodSelect = document.getElementById('pay-method');

    let rawValue = moneyInput.value.trim().replace(',', '.');
    const money = parseFloat(rawValue);
    let checkedMoney = 0.00;

    if (isNaN(money) || money <= 0) {
        alert("Bitte gültigen Betrag eingeben.");
        return;
    }

    const method = methodSelect.value;
    if (method === '-') {
        alert("Bitte Zahlungsmethode auswählen.");
        return;
    }else if(method === 'PayPal'){
        checkedMoney = subtractZeroPointNinePercent(money.toFixed(2));
    }else{
        checkedMoney = money.toFixed(2);
    }

    const payload = {
        ID: personID,
        Methode: method,
        Geld: checkedMoney
    };

    fetch('../server/php/sendMoneyIntoDatabase.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            clearForm(document.getElementById('financing'));
            displayMessage('financing_success');
            fetchAndRender(email); // Tabelle aktualisieren
        } else {
            alert("Eintrag fehlgeschlagen.");
        }
    })
    .catch(error => {
        console.error("Fehler beim Eintrag:", error);
    });
}

// --------------------------------------------------
// Formular zurücksetzen
// --------------------------------------------------
function clearForm(formElement) {
    formElement.reset();
}

function subtractZeroPointNinePercent(value) {
  return Math.round((value - (value * 0.009) + Number.EPSILON) * 100) / 100;
}

function trigger_generation_tickets(data){
    for (let index = 0; index < data.persons.length; index++) {
        fetch('../ticket/trigger_gen.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data.persons[index])
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                displayMessage("success_generationAndDeliveryTicket");
            } else {
                console.error("Fehler bei der Ticketgenerierung");
                displayMessage("error_generationAndDeliveryTicket");
            }
        })
        .catch(error => {
            console.error("Fehler bei der Ticketgenerierung:", error);
            displayMessage("specific-error_generationAndDeliveryTicket", error);
        });
    }
}