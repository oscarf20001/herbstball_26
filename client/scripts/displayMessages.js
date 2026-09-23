// displayMessages.js
let displayElementText = document.getElementById('display_textNode');
let displayContainer = document.getElementById('display');

document.addEventListener("DOMContentLoaded", (event) => {
  // gsap code here!
});

export function displayMessage(msg, details = null){
    switch (msg) {
        case 'email':
            displayElementText.textContent = 'Registrierung fehlgeschlagen: Ungültige Email';
            displayContainer.style.transform = 'translate(-50%, 0%)';
            setTimeout(() => {
                displayContainer.style.transform = 'translate(-50%, -200%)';
            },5000);
            break;
        
        case 'duplicateEmailSelfPurchase':
            displayElementText.textContent = 'Diese Email-Adresse ist bereits einem anderem Käufer zugeordnet. Ein einzelnes Ticket kann damit nicht erneut gekauft werden.';
            displayContainer.style.transform = 'translate(-50%, 0%)';
            setTimeout(() => {
                displayContainer.style.transform = 'translate(-50%, -200%)';
            },5000);
            break;

        case 'duplicate':
            displayElementText.textContent = 'Diese Person hat bereits ein Ticket.';
            displayContainer.style.transform = 'translate(-50%, 0%)';
            displayContainer.style.backgroundColor = 'var(--pureRed)';
            setTimeout(() => {
                displayContainer.style.transform = 'translate(-50%, -200%)';
            },5000);
            break;

        case 'duplicateEmailKaeufer':
            displayElementText.textContent = 'Diese Email-Adresse ist bereits einem anderem Käufer zugeordnet.';
            displayContainer.style.transform = 'translate(-50%, 0%)';
            setTimeout(() => {
                displayContainer.style.transform = 'translate(-50%, -200%)';
            },5000);
            break;

        case 'empty':
            displayElementText.textContent = 'Registrierung fehlgeschlagen: Leeres Feld erkannt';
            displayContainer.style.transform = 'translate(-50%, 0%)';
            displayContainer.style.backgroundColor = 'var(--pureRed)';
            setTimeout(() => {
                displayContainer.style.transform = 'translate(-50%, -200%)';
            },5000);
            break;

        case 'denied':
            displayElementText.textContent = 'Weiterleitung verweigert: Seite gesperrt';
            displayContainer.style.transform = 'translate(-50%, 0%)';
            displayContainer.style.backgroundColor = 'var(--pureRed)';
            setTimeout(() => {
                displayContainer.style.transform = 'translate(-50%, -200%)';
            },5000);
            break;

        case 'success':
            displayElementText.textContent = 'Reservierung erfolgreich!';
            displayContainer.style.backgroundColor = 'var(--successGreen)';
            displayContainer.style.transform = 'translate(-50%, 0%)';
            setTimeout(() => {
                displayContainer.style.transform = 'translate(-50%, -200%)';
            },5000);
            break;

        case 'financing_success':
            displayElementText.textContent = 'Eintragung erfolgreich!';
            displayContainer.style.backgroundColor = 'var(--successGreen)';
            displayContainer.style.transform = 'translate(-50%, 0%)';
            setTimeout(() => {
                displayContainer.style.transform = 'translate(-50%, -200%)';
            },5000);
            break;

        case 'req_gen-ticket':
            displayElementText.textContent = 'Anfrage für Ticketgenerierung und Versendung an Server geschickt!';
            displayContainer.style.backgroundColor = 'var(--successGreen)';
            displayContainer.style.transform = 'translate(-50%, 0%)';
            setTimeout(() => {
                displayContainer.style.transform = 'translate(-50%, -200%)';
            },5000);
            break;

        case 'success_generationAndDeliveryTicket':
            displayElementText.textContent = 'Ticket(s) wurden erfolgreich versendet!';
            displayContainer.style.backgroundColor = 'var(--successGreen)';
            displayContainer.style.transform = 'translate(-50%, 0%)';
            setTimeout(() => {
                displayContainer.style.transform = 'translate(-50%, -200%)';
            },5000);
            break;

        case 'error_generationAndDeliveryTicket':
            displayElementText.textContent = 'Unbekannter Fehler bei Generierung der Ticket(s)! Bitte Info an Oscar';
            displayContainer.style.backgroundColor = 'var(--pureRed)';
            displayContainer.style.transform = 'translate(-50%, 0%)';
            setTimeout(() => {
                displayContainer.style.transform = 'translate(-50%, -200%)';
            },5000);
            break;

        case 'specific-error_generationAndDeliveryTicket':
            displayElementText.textContent = 'Fehler bei Generierung der Ticket(s)! Bitte Info an Oscar: ' + details;
            displayContainer.style.backgroundColor = 'var(--pureRed)';
            displayContainer.style.transform = 'translate(-50%, 0%)';
            setTimeout(() => {
                displayContainer.style.transform = 'translate(-50%, -200%)';
            },5000);
            break;

        case 'permission_denied':
            displayElementText.textContent = 'Permission denied';
            displayContainer.style.backgroundColor = 'var(--pureRed)';
            displayContainer.style.transform = 'translate(-50%, 0%)';
            setTimeout(() => {
                displayContainer.style.transform = 'translate(-50%, -200%)';
            },5000);
            break;

        case 'entrance_successfull':
            displayElementText.textContent = 'Einlass für ' + details + ' erfolgreich';
            displayContainer.style.backgroundColor = 'var(--successGreen)';
            displayContainer.style.transform = 'translate(-50%, 0%)';
            setTimeout(() => {
                displayContainer.style.transform = 'translate(-50%, -200%)';
            },5000);
            break;

        case 'noTicket':
            displayElementText.textContent = 'Es wurde kein Ticket angegeben';
            displayContainer.style.backgroundColor = 'var(--pureRed)';
            displayContainer.style.transform = 'translate(-50%, 0%)';
            setTimeout(() => {
                displayContainer.style.transform = 'translate(-50%, -200%)';
            },5000);
            break;

        case 'invalidTicketData':
            displayElementText.textContent = 'Ungülgtige Ticketdaten enthalten';
            displayContainer.style.backgroundColor = 'var(--pureRed)';
            displayContainer.style.transform = 'translate(-50%, 0%)';
            setTimeout(() => {
                displayContainer.style.transform = 'translate(-50%, -200%)';
            },5000);
            break;

        default:
            displayElementText.textContent = 'Ein unbekannter Fehler ist aufgetreten';
            displayContainer.style.backgroundColor = 'var(--pureRed)';
            displayContainer.style.transform = 'translate(-50%, 0%)';
            setTimeout(() => {
                displayContainer.style.transform = 'translate(-50%, -200%)';
            },5000);
            break;
    }
}

export function makeFaltyTicketVisible(ticketElement) {
    // Entferne vorherige Markierungen
    document.querySelectorAll('.ticket').forEach(element => {
        element.classList.remove('duplicate');
    });

    // Neue Markierung setzen
    ticketElement.classList.add('duplicate');
}