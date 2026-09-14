import { displayMessage } from "./displayMessages.js";

const btn = document.getElementById('getAllTicketsForCustomerButton');
const form = document.getElementById('form');
const selectElement = document.getElementById('selectNewEmailType');

// Haupt-Button für Ticketabfrage
btn.addEventListener('click', () => {
    const input = document.getElementById('f-email');

    if (!input.value.trim()) {
        console.error("Fetch bezüglich Käufer für neue Emails abgelehnt: Input-Feld leer");
        return;
    }

    if (btn.classList.contains('inactive')) {
        console.error("Fetch bezüglich Käufer für neue Emails abgelehnt: Button gesperrt!");
        return;
    }

    if(selectElement.value == '' || selectElement.value === ''){
        console.error("Fetch bezüglich Käufer für neue Emails abgelehnt: Keine Methode ausgewählt");
        return;
    }

    fetchDataForNewEmailToKaeufer(input.value.trim());
});

// Zum Aktivieren von Buttons
export function setBtnAsActive(btn) {
    btn.classList.remove('inactive');
    btn.classList.add('active');
}

// --------------------------------------------------
// Hauptfunktion zum Abrufen und Rendern der Tabellen
// --------------------------------------------------
function fetchDataForNewEmailToKaeufer(email) {
    fetch('../server/php/getAllTickets.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Serverantwort:', data);
        resendEmail(data, selectElement.value);
    })
    .catch(error => {
        console.error('Fehler beim Senden:', error);
    });
}

function resendEmail(data, method){
    switch (method) {
        case 'submit_ticket':
            console.info("Submit-Mail senden");

            fetch('../server/php/resendMail_submitTicket.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                console.log('Serverantwort:', data);
            })
            .catch(error => {
                console.error('Fehler beim Senden:', error);
            });

            break;

        case 'confirm_payment':
            console.info("Zahlungsmail senden");
            
            fetch('../server/php/checkOpenUnderOrEvenZero.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    mailerPerson: data,
                    action: 'sendConfirmationMail'
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Serverantwort:', data);
            })
            .catch(error => {
                console.error('Fehler beim Senden:', error);
            });

            break;
            
        default:
            console.error("Mailversand abgebrochen: Fehler bei Zuweisung der Methode");
            alert("Dieser Mailweg ist noch nicht fertiggestellt!")
            break;
    }
}

function searchMails(){
    const input = document.getElementById('f-email').value;
    
    if (input.length === 0) {
        displaySuggestions('');
        return; // Leerer Input löscht Vorschläge
    }
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../server/php/searchEmails.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            displaySuggestions(xhr.responseText);
        }
    };
    xhr.send('query=' + encodeURIComponent(input));
}

function selectMail(email) {
    document.getElementById('f-email').value = email;
    displaySuggestions('');

    const btn = document.getElementById('getAllTicketsForCustomerButton');
    setBtnAsActive(btn);
}

window.selectMail = selectMail;

function displaySuggestions(suggestion){
    const container = document.getElementById('suggestionContainer');
    const suggestionText = document.getElementById('suggestions');

    if(suggestion == '' || suggestion == null){
        container.style.display = 'none';
        suggestionText.innerHTML = '';
        return;
    }

    container.style.display = 'block';
    suggestionText.innerHTML = suggestion;
}

const input = document.getElementById('f-email');
input.addEventListener('keyup',() => {
    searchMails();
});