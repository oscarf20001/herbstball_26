import { displayMessage } from "./displayMessages.js";

const options = document.querySelectorAll('.option');
const highlight = document.querySelector('.highlight');
const containers = document.querySelectorAll('.wrapper');

containers.forEach(c => c.style.display = 'none');
const defaultContainer = document.querySelector('[data-id="wrapper-0"]');
if (defaultContainer) defaultContainer.style.display = 'block';

options.forEach(option => {
    option.addEventListener('click', () => {
        const index = option.dataset.index;

        // Prüfen, ob User die Berechtigung für "create_user" hat
        if (index === "1" && option.dataset.canCreate !== "1") {
            // Berechtigung fehlt → zurück zur Passwort-Reset-Seite
            //alert("Du hast keine Berechtigung, einen neuen User zu erstellen!");
            displayMessage("permission_denied");
            const resetContainer = document.querySelector('[data-id="wrapper-0"]');
            highlight.style.left = `0%`;
            options.forEach(opt => opt.classList.remove('active'));
            options[0].classList.add('active');
            containers.forEach(c => c.style.display = 'none');
            if (resetContainer) resetContainer.style.display = 'block';
            return; // Klick abbrechen
        }

        // Highlight verschieben
        highlight.style.left = `${index * 50}%`;

        // Aktive Option markieren
        options.forEach(opt => opt.classList.remove('active'));
        option.classList.add('active');

        // Container umschalten
        containers.forEach(c => c.style.display = 'none');
        const activeContainer = document.querySelector(`[data-id="wrapper-${index}"]`);
        if (activeContainer) activeContainer.style.display = 'block';
    });
});
