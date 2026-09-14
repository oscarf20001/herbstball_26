// =======================
// Setup & UI-Interaktion
// =======================

const barCodeInputField = document.getElementById('barCodeInput');
const barCodeSubmitButton = document.getElementById('barCodeSubmitButton');
const confirmEntranceButton = document.getElementById('confirmEntranceButton');

const checkAgeContainer = document.getElementById('checkAge');
const ageToCheckField = document.getElementById('ageCheck_setDbAge');
const ageSetDate = document.getElementById('ageCheck_setControllDate');
const setNameCheck = document.getElementById('nameCheck_setName');

const entrancePanelContainer = document.getElementById('entrancePanelContainer');
const messageContainer = document.getElementById('messageContainer');
const textFieldMessage = document.getElementById('message');
const downerInformationContainer = document.getElementById('settingsContainer');
const braceletMessage = document.getElementById('bracelet');
const confirmEntranceContainer = document.getElementById('confirmEntranceContainer');

let currentEntrance = null;

window.addEventListener('DOMContentLoaded', initPage);

function initPage() {
  focusInput();

  barCodeSubmitButton.addEventListener('click', handleBarcodeSubmit);
  confirmEntranceButton.addEventListener('click', () => {
    if (currentEntrance) currentEntrance.confirmEntrance(currentEntrance.code);
  });

  // Enter-Taste global abfangen
  window.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') handleBarcodeSubmit();
    else focusInput(); // Scanner-Fokus erhalten
  });
}

// =======================
// Barcode-Handling
// =======================

async function handleBarcodeSubmit() {
  const rawValue = barCodeInputField.value.trim();
  if (!rawValue) return alertAndFocus("Bitte Code eingeben oder scannen.");

  const code = parseCode(rawValue);
  try {
    const entrance = new Entrance(code);
    await entrance.init();

    resetUi();
    barCodeInputField.value = '';
    displayPerson(entrance);
  } catch (error) {
    console.error(error);
    alert("Fehler beim Laden der Personendaten.");
  } finally {
    focusInput();
  }
}

function parseCode(value) {
  return parseInt(value.replace(/^HB\d{4}[_?]/, "").replace(/^0+/, ""), 10);
}

function alertAndFocus(msg) {
  alert(msg);
  focusInput();
}

// =======================
// Datenlogik
// =======================

class Entrance {
  constructor(code) {
    this.code = code;
  }

  async init() {
    this.isIn = await this.checkAlreadyIn(this.code);
    this.data = await this.getPersonData(this.code);
    Object.assign(this, this.data);
  }

  async checkAlreadyIn(id) {
    try {
      const res = await fetch(`../server/php/checkAlreadyIn.php?id=${id}`);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return !!(await res.json()).einlass;
    } catch (err) {
      console.error("Fehler beim Einlass-Check:", err);
      return null;
    }
  }

  async getPersonData(id) {
    try {
      const res = await fetch(`../server/php/getPersonData.php?id=${id}`);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const d = await res.json();

      return {
        id: d.person_id,
        kaeuferId: d.kaeufer_id,
        vorname: d.vorname,
        nachname: d.nachname,
        email: d.email,
        muttizettel: !!d.muttizettel,
        alter: d.age,
        schule: d.school,
        methodPay: d.method,
        payedAt: d.ausgefuehrt_durch,
        payedDate: d.d_paid,
        offen: Number(d.open_charges),
        person_charges: Number(d.sum),
        kaeufer_charges: Number(d.charges),
        kaeufer_open: Number(d.open_charges),
        bezahlt: Number(d.open_charges) <= 0
      };
    } catch (err) {
      console.error("Fehler beim Laden der Personendaten:", err);
      return null;
    }
  }

  async confirmEntrance(id) {
    try {
      const res = await fetch(`../server/php/confirmEntrance.php?id=${id}`);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const d = await res.json();
      alert(d.message);
      return !!d.success;
    } catch (err) {
      console.error("Fehler beim Bestätigen des Einlasses:", err);
      return null;
    }
  }
}

// =======================
// UI-Logik
// =======================

function displayPerson(entrance) {
  currentEntrance = entrance;
  const data = entrance.data || entrance;

  // alle Felder direkt 1:1 ins HTML schreiben
  for (const [key, val] of Object.entries(data)) {
    const el = document.getElementById(key);
    if (!el) continue;
    el.textContent = (typeof val === 'boolean') ? (val ? "✅ Ja" : "❌ Nein") : val ?? '';
  }

  // bereits eingelassen oder nicht bezahlt
  if (entrance.isIn || !entrance.bezahlt) {
    showWarning(entrance.isIn ? "Bereits eingelassen" : "Nicht bezahlt");
    return;
  }

  // Alters- und Namenskontrolle
  ageToCheckField.textContent = data.alter;
  ageSetDate.textContent = getCheckDate(data.alter);
  setNameCheck.textContent = `${entrance.vorname} ${entrance.nachname}`;

  checkAgeContainer.style.display = data.alter <= 17 ? 'none' : 'block';
  downerInformationContainer.style.display = 'block';
  confirmEntranceContainer.style.display = 'flex';

  // Bracelet
  if(entrance.alter == 18){
    braceletMessage.textContent = 'Rot';
  }else if(entrance.alter < 16){
    braceletMessage.textContent = 'andere Farbe';
  }else{
    braceletMessage.textContent = 'keins';
  }
}

function showWarning(msg) {
  entrancePanelContainer.classList.add('warning');
  messageContainer.style.display = 'flex';
  textFieldMessage.textContent = msg;
  confirmEntranceContainer.style.display = 'none';
}

function resetUi() {
  entrancePanelContainer.classList.remove('warning', 'error');
  messageContainer.style.display = 'none';
  downerInformationContainer.style.display = 'none';
}

function focusInput() {
  barCodeInputField.focus();
}

function getCheckDate(age) {
  if (age === 18) return '17.10.2007';
  return '';
}