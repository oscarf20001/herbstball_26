import { getTicketLength } from './ticketCount.js';
import { writeObjects, loadIntoDB } from './dataTicket.js';
import { displayMessage } from './displayMessages.js';

let doppelteWerte = [];

document.getElementById('takeReservationButton').addEventListener('click', async () => {
    if(!checkForEmptyFields() && handleLiveValidation() && !checkEmailInvalid()){
        console.log("Eintragung genehmigt!");
        const tickets = await writeObjects();
        loadIntoDB(tickets);
    }else if(checkForEmptyFields()){
        // Anzeigen eines Fensters im UI; Leere Felder
        displayMessage('empty');
    }else if(!handleLiveValidation()){
        // Anzeigen eines Fensters im UI; Doppelte Einträge (Front-End Duplicate)
        displayMessage('duplicate');
    }else if(checkEmailInvalid()){
        // Anzeigen eines Fensters im UI; Ungültige Email-Adresse
        displayMessage('email');
    }
    addLiveEmptyCheckHandler(); // live-validierung hinzufügen
});

// Event-Listener für alle Inputs
export function addLiveValidationListeners() {
    const inputs = document.querySelectorAll('input[id^="vorname-"], input[id^="name-"]');
    inputs.forEach(input => {
        input.addEventListener('input', handleLiveValidation);
    });
}

// Hauptfunktion für Live-Validierung
function handleLiveValidation() {
    const currentCount = getTicketLength();
    const combinations = [];

    for (let index = 1; index < currentCount + 1; index++) {
        const vornameInput = document.getElementById('vorname-0' + index);
        const nameInput = document.getElementById('name-0' + index);

        const prename = vornameInput?.value.trim() ?? '';
        const name = nameInput?.value.trim() ?? '';

        const full_name = prename + ' ' + name;
        combinations.push(full_name);
    }

    if (uniqueness(combinations)) {
        removeDuplicateClassFromAllInputs();
        return true;
    } else {
        removeDuplicateClassFromAllInputs(); // vorher leeren
        for (const full_name of doppelteWerte) {
            const inputIds = findInputIdsByFullName(full_name.trim());
            inputIds.forEach(id => {
                const input = document.getElementById(id);
                if (input) {
                    input.classList.add('duplicate');
                }
            });
        }
        return false;
    }
}

// Prüft auf doppelte Werte
function uniqueness(arr) {
    doppelteWerte = [];
    const wertZaehler = {};

    for (const wert of arr) {
        if (wertZaehler[wert]) {
            if (!doppelteWerte.includes(wert)) {
                doppelteWerte.push(wert);
            }
        } else {
            wertZaehler[wert] = 1;
        }
    }

    return doppelteWerte.length === 0;
}

// Findet IDs zu einem Namen
function findInputIdsByFullName(fullName) {
    const inputIds = [];
    const currentCount = getTicketLength();
    const [prename, ...lastnameParts] = fullName.split(' ');
    const name = lastnameParts.join(' '); // unterstützt Doppelnamen

    for (let index = 1; index < currentCount + 1; index++) {
        const vornameInput = document.getElementById('vorname-0' + index);
        const nameInput = document.getElementById('name-0' + index);

        if (!vornameInput || !nameInput) continue;

        const vorname = vornameInput.value.trim();
        const nachname = nameInput.value.trim();

        if (vorname === prename && nachname === name) {
            inputIds.push(vornameInput.id);
            inputIds.push(nameInput.id);
        }
    }

    return inputIds;
}

// Entfernt alte Markierungen
function removeDuplicateClassFromAllInputs() {
    const inputs = document.querySelectorAll('input.duplicate');
    inputs.forEach(input => input.classList.remove('duplicate'));
}

function checkForEmptyFields() {
    const inputs = document.querySelectorAll('input');

    let hasEmpty = false;

    inputs.forEach(input => {
        input.classList.remove('empty');
        if (!input.value.trim()) {
            input.classList.add('empty');
            hasEmpty = true;
        }
    });

    return hasEmpty;
}

function checkEmailInvalid() {
    const inputs = document.querySelectorAll('input[type="email"]');

    let isInvalid = false;

    inputs.forEach(input => {
        if (!input.checkValidity()) {
            isInvalid = true;
        }
    });

    return isInvalid;
}

function addLiveEmptyCheckHandler() {
    const inputs = document.querySelectorAll('input');

    inputs.forEach(input => {
        input.removeEventListener('input', handleEmptyCheck); // Doppelte vermeiden
        input.addEventListener('input', handleEmptyCheck);
    });
}

function handleEmptyCheck(event) {
    const input = event.target;
    if (input.value.trim() !== '') {
        input.classList.remove('empty');
    } else {
        input.classList.add('empty');
    }
}

// Initial beim Laden hinzufügen
document.addEventListener('DOMContentLoaded', addLiveValidationListeners);