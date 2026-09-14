import { displayMessage } from './displayMessages.js';

let blockedSitesI = document.querySelectorAll('i.denied');
blockedSitesI.forEach(element => {
    element.addEventListener('click', () => displayMessage('denied'));
});

let blockedSitesA = document.querySelectorAll('a.denied');
blockedSitesA.forEach(element => {
    element.addEventListener('click', () => displayMessage('denied'));
});