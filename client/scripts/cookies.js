function initCookies() {
    const container = document.getElementById('cookies');
    const cancelBtn = document.getElementById('cancelCookiesBtn');
    const submitBtn = document.getElementById('submitCookiesBtn');

    // Safety Checks
    if (!container || !cancelBtn || !submitBtn) {
        console.warn("Cookie-Banner-Elemente nicht gefunden.");
        return;
    }

    // Wenn Cookie existiert → Banner entfernen
    if (getCookie("userConsent")) {
        container.remove();
        return;
    }

    // Cookie Banner sichtbar machen
    container.style.display = 'block';

    // Buttons
    cancelBtn.addEventListener('click', () => {
        history.back(); // oder: window.location.href = "/";
    });

    submitBtn.addEventListener('click', () => {
        setCookie("userConsent", "accepted");
        container.remove();
    });
}

function setCookie(name, value) {
    const days = 7;
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));

    const expires = "expires=" + date.toUTCString();
    document.cookie = `${name}=${value}; ${expires}; path=/`;
}

function getCookie(name) {
    const cookies = document.cookie.split("; ");
    for (let c of cookies) {
        const [key, value] = c.split("=");
        if (key === name) {
            return value;
        }
    }
    return null;
}

// Init ausführen, wenn DOM fertig ist
document.addEventListener("DOMContentLoaded", initCookies);