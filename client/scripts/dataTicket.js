import { getEachTicketprice } from './finances.js';
import { displayMessage, makeFaltyTicketVisible } from './displayMessages.js';
import { clearInputFields } from './ticketCount.js';

class Ticket {
    constructor(vorname, nachname, email, age, school, kaeufer, kaeuferID, countAnzahlTickets, sum, charges, created) {
        this.vorname = vorname;
        this.nachname = nachname;
        this.email = email;
        this.age = age;
        this.school = school;

        this.kaeufer = kaeufer;
        this.kaeuferID = kaeuferID;
        this.sum = sum;
        this.tickets = countAnzahlTickets;
        this.charges = charges;

        this.created = created;
        this.submited = this.currentTime();
    }

    currentTime() {
        return Math.floor(Date.now() / 1000);
    }

    static async getNextId() {
        try {
            const response = await fetch('server/php/latest_id.php');

            const text = await response.text();
            console.log('Antwort von latest_id.php:', text);

            const data = JSON.parse(text);

            if (data.max_id !== undefined) {
                return data.max_id + 1;
            } else {
                console.error('Keine max_id erhalten');
                return 0;
            }
        } catch (error) {
            console.error('Fetch-Fehler:', error);
            return 0;
        }
    }

    static async createTicket(vorname, nachname, email, age, school, countAnzahlTickets, sum, charges, created, kaeufer) {
        const id = await Ticket.getNextId();
        let kaeuferID = id;
        return new Ticket(vorname, nachname, email, age, school, kaeufer, kaeuferID, countAnzahlTickets, sum, charges, created);
    }

    static async writeIntoDatabase(element){
        fetch('server/php/saveTickets.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(tickets) // Array als JSON-String senden
        })
        .then(response => response.json())
        .then(data => {
            console.log('Serverantwort:', data);

            if (Array.isArray(data.results)) {
                const hasFail = data.results.some(result => result.status === 'fail');

                if (hasFail) {
                    displayMessage('duplicate');
                    for (const res of data.results) {
                        if (res.status === "fail") {
                            const ticketDiv = findeTicketDivMitNamen(res.vorname, res.nachname);
                            if (ticketDiv) {
                                makeFaltyTicketVisible(ticketDiv);
                            }
                        }
                    }
                } else {
                    console.log('✅ Kein Fehler in results – success');
                    displayMessage('success');
                    const form = document.getElementById('form');
                    form.reset();
                    this.sendConfirmationMail(element);
                }
            } else if (data.results && data.results.status === "fail") {
                // Einzelnes Fehlerobjekt
                console.log('❌ Einzelfehler in results');
                displayMessage('duplicate');
                const res = data.results;
                const ticketDiv = findeTicketDivMitNamen(res.vorname, res.nachname);
                if (ticketDiv) {
                    makeFaltyTicketVisible(ticketDiv);
                }
            } else {
                // Kein results-Feld? Oder generischer Erfolg?
                console.log('🎯 Fallback: success ohne results');
                displayMessage('success');
                this.sendConfirmationMail(element);
            }
        })
        .catch(error => {
            console.error('Fehler beim Senden:', error);
        });
    }

    static async sendConfirmationMail(element){
        clearInputFields();
        fetch('server/php/sendEmail.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(element) // Array als JSON-String senden
        })
        .then(response => response.json())
        .then(data => {
            console.log('Serverantwort:', data);
        })
        .catch(error => {
            console.error('Fehler beim Senden:', error);
        });
    }
}

const createdTimestamp = Math.floor(Date.now() / 1000);
let tickets = [];

function getTicketSize() {
    const parentElement = document.getElementById('ticketsContainer');
    return parentElement.querySelectorAll('.ticket').length;
}

export async function writeObjects() {
    tickets = [];
    const ticketCount = getTicketSize();

    for (let i = 1; i <= ticketCount; i++) {

        const index = i < 10 ? '0' + i : i;
        let kaeuferStatus = true;
        let chargesGesamt = parseFloat(document.getElementById('shoppingCartIndex').textContent) || 0;
        let sumEachTicket = getEachTicketprice();
        const parentElement = document.getElementById('ticketsContainer');
        let countAnzahlTickets = parentElement.querySelectorAll('.ticket').length

        if(i !== 1){
            kaeuferStatus = false;
            chargesGesamt = 0;
            countAnzahlTickets = 0;
        }

        const ageGroup = document.getElementById('age-optionGroup-' + index);
        const schoolGroup = document.getElementById('school-optionGroup-' + index);

        const age = ageGroup?.querySelector('button.ageOption.active')?.getAttribute('data-age') || "16";
        const school = schoolGroup?.querySelector('button.schoolOption.active')?.getAttribute('data-school') || 'ADR';

        const newTicket = await Ticket.createTicket(
            document.getElementById('vorname-' + index).value.trim(),
            document.getElementById('name-' + index).value.trim(),
            document.getElementById('email-' + index).value.trim(),
            age,
            school,
            countAnzahlTickets,
            sumEachTicket,
            chargesGesamt,
            createdTimestamp,
            kaeuferStatus
        );
        tickets.push(newTicket);
    }
    console.log(tickets);
    return tickets;
}

export function loadIntoDB(tickets){
    Ticket.writeIntoDatabase(tickets);
}

function findeTicketDivMitNamen(vorname, nachname) {
    const tickets = document.querySelectorAll('.ticket');

    for (const ticket of tickets) {
        const vornameInput = ticket.querySelector('input[id^="vorname-"]');
        const nachnameInput = ticket.querySelector('input[id^="name-"]');

        if (
            vornameInput && nachnameInput &&
            vornameInput.value.trim().toLowerCase() === vorname.trim().toLowerCase() &&
            nachnameInput.value.trim().toLowerCase() === nachname.trim().toLowerCase()
        ) {
            return ticket;
        }
    }

    return null;
}