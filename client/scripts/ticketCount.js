import { updateShoppingCart } from './finances.js';
import { addLiveValidationListeners } from './checks.js';

const ticketCountButtons = document.querySelectorAll('.ticketCountOption');
const hiddenInput_ticketCount = document.getElementById('ticketCountInput');

const defaultTicketSize = 2;
const maxTicketSize = 5;
let latestTicketSize = defaultTicketSize;
let operation;
let countsOfElements;

const parentElement = document.getElementById('ticketsContainer');

const plusTicketButton = document.getElementById('addTicketButton');
plusTicketButton.addEventListener('click', () => {
  ensureTicketCount(1);
  updateShoppingCart(parentElement.querySelectorAll('.ticket').length);
});

// Nach laden der Seite den default-Wert für den hidden-Input auf 1 setzen. Genauso wie das UI
hiddenInput_ticketCount.value = defaultTicketSize;

// Sobald ein Button geklickt wird, wird das UI und der hiddenInput_ticketCount geupdated
ticketCountButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        ticketCountButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        hiddenInput_ticketCount.value = btn.dataset.value;

        countsOfElements = latestTicketSize - btn.dataset.value;

        if (countsOfElements < 0) {
            operation = 'add';
        } else if (countsOfElements > 0) {
            operation = 'sub';
        } else {
            return;
        }

        console.log(operation + ' ' + countsOfElements);

        switch (operation) {
            case 'add':
                ensureTicketCount(Math.abs(countsOfElements));
                break;
            case 'sub':
                removeFromEnd(Math.abs(countsOfElements));
                break;
        }

        updateShoppingCart(parentElement.querySelectorAll('.ticket').length);
        latestTicketSize = btn.dataset.value;
    });
});

export function clearInputFields(){
  document.getElementById('ticket-01').style.outline = "0px solid var(--pureRed)";

  let inputs = document.querySelectorAll('input[type="text"]');
  inputs.forEach(input => {
    input.value = '';
    input.classList.remove('user-invalid');
    input.classList.remove('duplicate');
    input.classList.remove('empty');
  });

  let email = document.querySelectorAll('input[type="email"]');
  email.forEach(input => {
    input.value = '';
    input.classList.remove('user-invalid');
    input.classList.remove('duplicate');
    input.classList.remove('empty');
  });

  let emptyClasses = document.querySelectorAll('.empty');
  emptyClasses.forEach(input => {
    input.classList.remove('empty');
  });
}

export function removeFromEnd(count) {
    const container = document.getElementById("ticketsContainer");
    const children = container.querySelectorAll(".ticket");
    const total = children.length;

    const maxRemovable = total - 1;
    const toRemove = Math.min(count, maxRemovable);

    for (let i = 0; i < toRemove; i++) {
        const index = total - 1 - i;
        container.removeChild(children[index]);
    }

    if(parentElement.querySelectorAll('.ticket').length >= maxTicketSize){
      document.getElementById('addTicketButton').style.visibility = 'hidden';
      return;
    }else{
      document.getElementById('addTicketButton').style.visibility = 'visible';
    }
}

function ensureTicketCount(countToAdd) {
    const parentElement = document.getElementById('ticketsContainer');

    for (let i = 0; i < countToAdd; i++) {
        addTicketBlock();
    }

    const currentCount = parentElement.querySelectorAll('.ticket').length;

    // Update hidden input
    hiddenInput_ticketCount.value = currentCount;

    // Update active button
    ticketCountButtons.forEach(b => b.classList.remove('active'));
    const matchingButton = document.querySelector(`.ticketCountOption[data-value="${currentCount}"]`);
    if (matchingButton) {
        matchingButton.classList.add('active');
    }

    // Update latestTicketSize
    latestTicketSize = currentCount;
}

export { ensureTicketCount };

function initAgeOptions(ticket) {
    const ageButtons = ticket.querySelectorAll('.ageOption');
    const hiddenInput = ticket.querySelector('input[id^="ageInput-"]');

    if (!hiddenInput || ageButtons.length === 0) return;

    hiddenInput.value = ageButtons[0].dataset.age;
    ageButtons[0].classList.add('active');

    ageButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            ageButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            hiddenInput.value = btn.dataset.age;
        });
    });
}

function initSchoolOptions(ticket) {
    const schoolButtons = ticket.querySelectorAll('.schoolOption');
    const hiddenInput = ticket.querySelector('input[id^="schoolInput-"]');

    if (!hiddenInput || schoolButtons.length === 0) return;

    hiddenInput.value = schoolButtons[0].dataset.school;
    schoolButtons[1].classList.add('active');

    schoolButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            schoolButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            hiddenInput.value = btn.dataset.school;
        });
    });
}

function addTicketBlock() {
    const parentElement = document.getElementById('ticketsContainer');
    const existingTickets = parentElement.querySelectorAll('.ticket');
    const newIndex = existingTickets.length + 1;
    const newIdSuffix = String(newIndex).padStart(2, '0');
    
    if(existingTickets.length >= maxTicketSize){
      return;
    }

    const template = `
      <div id="ticket-${newIdSuffix}" class="ticket">
        <div class="input-field vorname">
          <input type="text" id="vorname-${newIdSuffix}" name="vorname-${newIdSuffix}" required>
          <label for="vorname-${newIdSuffix}">Vorname:<sup>*</sup></label>
        </div>
        <div class="input-field name">
          <input type="text" id="name-${newIdSuffix}" name="nachname-${newIdSuffix}" required>
          <label for="name-${newIdSuffix}">Nachname:<sup>*</sup></label>
        </div>
        <div class="input-field email">
          <input type="email" id="email-${newIdSuffix}" name="email-${newIdSuffix}" required>
          <label for="email-${newIdSuffix}">Email-Adresse:<sup>*</sup></label>
        </div>
        <div class="age">
          <label id="ageLabel-${newIdSuffix}" class="ageLabel" for="ageInput-${newIdSuffix}">Alter:<sup>*</sup></label>
          <div id="age-optionGroup-${newIdSuffix}" class="age-optionGroup">
            <button type="button" class="ageOption selectiveButton buttonNeedsBorder" data-age="16">16</button>
            <button type="button" class="ageOption selectiveButton buttonNeedsBorder" data-age="17">17</button>
            <button type="button" class="ageOption selectiveButton buttonNeedsBorder" data-age="18+">18+</button>
          </div>
          <input type="hidden" name="age-${newIdSuffix}" id="ageInput-${newIdSuffix}" required>
        </div>
        <div class="school">
          <label id="schoolLabel-${newIdSuffix}" class="schoolLabel" for="schoolInput-${newIdSuffix}">Schule:<sup>*</sup></label>
          <div id="school-optionGroup-${newIdSuffix}" class="school-optionGroup">
            <button type="button" class="schoolOption selectiveButton buttonNeedsBorder" data-school="MCG">MCG</button>
            <!--<button type="button" class="schoolOption selectiveButton buttonNeedsBorder" data-school="FFR">FFR</button>-->
            <button type="button" class="schoolOption selectiveButton buttonNeedsBorder" data-school="EXT">extern</button>
          </div>
          <input type="hidden" name="school-${newIdSuffix}" id="schoolInput-${newIdSuffix}" required>
        </div>
      </div>
    `;

    const wrapper = document.createElement('div');
    wrapper.innerHTML = template.trim();
    const newTicket = wrapper.firstChild;

    parentElement.appendChild(newTicket);

    initAgeOptions(newTicket);
    initSchoolOptions(newTicket);

    ticketCountButtons.forEach(input => {
      ticketCountButtons.forEach(b => b.classList.remove('active'));
        document.querySelector('button[data-value="' + parentElement.querySelectorAll('.ticket').length + '"]').classList.add('active');
        document.getElementById('ticketCountInput').value = parentElement.querySelectorAll('.ticket').length;
    });

    addLiveValidationListeners();

    if(parentElement.querySelectorAll('.ticket').length >= maxTicketSize){
      document.getElementById('addTicketButton').style.visibility = 'hidden';
      return;
    }else{
      document.getElementById('addTicketButton').style.visibility = 'visible';
    }
}

export function getTicketLength(){
  const parentElement = document.getElementById('ticketsContainer');
  const currentCount = parentElement.querySelectorAll('.ticket').length;
  return currentCount;
}