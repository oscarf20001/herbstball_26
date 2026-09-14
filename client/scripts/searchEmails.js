import { setBtnAsActive } from "./einzahlung.js";

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