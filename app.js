require('dotenv').config({
  path: '.env'
});
//WorkingDirectory=/var/www/metis-pdfgen
const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');
const bwipjs = require('bwip-js');
const express = require('express');
const mysql = require('mysql2/promise');

const app = express();
const port = 3001;
const ticketsDir = path.resolve(__dirname, 'ticket/gen_pdfs');
if (!fs.existsSync(ticketsDir)) fs.mkdirSync(ticketsDir);

const MOD = 65537;
const mul = 73;

const logDir = path.resolve(__dirname, 'ticket/node-logs');
if (!fs.existsSync(logDir)) fs.mkdirSync(logDir);

const logFile = fs.createWriteStream(path.join(logDir, 'server.log'), { flags: 'a' });
const errorFile = fs.createWriteStream(path.join(logDir, 'error.log'), { flags: 'a' });

const origConsoleLog = console.log;
const origConsoleError = console.error;

// Timestamp hinzufügen
function getTimestamp() {
  return new Date().toISOString();
}

// Konsole umleiten
console.log = (...args) => {
	logFile.write(`[${getTimestamp()}] LOG: ${args.join(' ')}\n`);
	origConsoleLog(...args); // auch auf die Konsole ausgeben
};

console.error = (...args) => {
  	errorFile.write(`[${getTimestamp()}] ERROR: ${args.join(' ')}\n`);
	origConsoleError(...args);
};

function transform(x, key) {
  return ((x * mul) ^ key) % MOD;
}

function inverseTransform(y, key) {
  const afterXor = y ^ key;
  const invMul = modInverse(mul, MOD);

  return (afterXor * invMul) % MOD;
}

function modInverse(a, m) {
  let m0 = m, t, q;
  let x0 = 0, x1 = 1;

  if (m === 1) return 0;

  while (a > 1) {
    q = Math.floor(a / m);
    t = m;

    m = a % m;
    a = t;
    t = x0;

    x0 = x1 - q * x0;
    x1 = t;
  }

  return x1 < 0 ? x1 + m0 : x1;
}

function getBase64Image(filePath) {
  const image = fs.readFileSync(filePath);
  const ext = path.extname(filePath).substring(1);
  return `data:image/${ext};base64,${image.toString('base64')}`;
}

function generateBarcode(person_id, codeText, filePath) {
  return new Promise((resolve, reject) => {
    bwipjs.toBuffer({
        bcid: 'code128',
        text: codeText,
        scale: 3,
        height: 10,
        includetext: true,
        textxalign: 'center',
        textyoffset: 2,
        barcolor: 'FFFFFF',
        textcolor: 'FFFFFF'
    }, (err, png) => {
      if (err) return reject(err);
      try {
        fs.mkdirSync(path.dirname(filePath), { recursive: true });
        fs.writeFileSync(filePath, png);
        console.log(`✅ Barcode gespeichert unter ${filePath}`);
        resolve(filePath);
      } catch (e) {
        reject(e);
      }
    });
  });
}

async function generatePDF(person_id) {
  const fileName = `ticket_person_${person_id}.pdf`;
  const outputPath = path.resolve(ticketsDir, fileName);

  const eventCode = 'HB2026_';
  const key = parseInt(process.env.ENC_KEY);
  if (isNaN(key)) {
    console.error('❌ ENV KEY ist ungültig oder nicht gesetzt');
    process.exit(1);
  }

  //const numberCode = transform(person_id, key);
  const numberCode = person_id.padStart(4, '0');
  const codeText = eventCode + numberCode;
  const barcodePath = path.join(__dirname, 'ticket/barcodes', `${codeText}.png`);

  console.log('🔍 Generierter Code:', codeText);

  await generateBarcode(person_id, codeText, barcodePath);

  const logoBase64 = getBase64Image(path.resolve(__dirname, 'ticket/images/Metis.png'));
  const qrBase64 = getBase64Image(path.resolve(__dirname, 'ticket/images/qr-code.png'));
  const barcodeBase64 = getBase64Image(barcodePath);

  const conn = await mysql.createConnection({
    host: process.env.DB_HOST,
    user: process.env.DB_USERNAME,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME
  });

  const dbNameENV = process.env.DB_NAME;
  const [rows] = await conn.query("SELECT DATABASE() AS db");
  const dbNameSQL = rows[0].db;

  const [data] = await conn.execute(`
    SELECT 
      p.id AS person_id,
      tb.kaeufer_id,
      p.vorname,
      p.nachname,
      p.email
    FROM 
      person p
    JOIN 
      ticket_besitzer tb ON tb.person_id = p.id
    WHERE 
      p.id = ?
  `, [person_id]);

  const person = data[0];
  if (!person) throw new Error(`Keine Person mit ID ${person_id} gefunden`);

  const html = `
  <!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <title>Unmuted – Ticket</title>

  <style>
    @page {
      margin: 0;
      size: A4;
    }

    /* =========================
       Design Tokens – Unmuted
       ========================= */
    :root {
    --black: #000;
    --blackLighter: #231c3f;
    --ticketBackground: #17191f;
    --ticketSection: #101114;
    --ticketField: #252731;

    --border: rgba(35, 28, 63, 0.4);
    --grey: #484459;
    --greyLighter: #777484;

    --primaryColor: #fffcf4;
    --primaryDarker: #f1f1f1;

    --secondaryColor: #e98316;
    --secondaryColorDarker: #e98316;

    --atentionColor: #f14848;
    --successGreen: #00cb11;
}

html,
body {
    margin: 0;
    padding: 0;
    width: 100%;
    height: 100%;
    background-color: var(--ticketBackground);
}

body {
    font-family: Arial, Helvetica, sans-serif;
}


/* =========================================================
   TICKET
   ========================================================= */

.ticket {
    width: 210mm;
    height: 297mm;
    margin: 0;
    padding: 0;
}

.ticket-pdf {
    width: 210mm;
    height: 297mm;

    margin: 0;
    padding: 0;

    box-sizing: border-box;

    background-color: var(--ticketBackground);
    color: var(--primaryColor);

    display: flex;
    flex-direction: column;

    overflow: hidden;
}


/* =========================================================
   HEADER
   ========================================================= */

.ticket-pdf-header {
    height: 150px;

    padding: 0 30px;

    display: flex;
    justify-content: space-between;
    align-items: center;

    background-color: var(--secondaryColor);
    color: var(--primaryColor);
}

.ticket-pdf-header-left {
    display: flex;
    align-items: center;
    gap: 25px;
}

.ticket-pdf-logo {
    width: 65px;
    height: 65px;
    object-fit: contain;
}

.ticket-pdf-title {
    display: flex;
    flex-direction: column;
}

.ticket-pdf-title h1 {
    margin: 0;

    color: var(--primaryColor);

    font-size: 34px;
    font-weight: 800;
}

.ticket-pdf-title p {
    margin: 4px 0 0;

    color: var(--primaryColor);

    font-size: 18px;
}


/* =========================================================
   QR-CODE
   ========================================================= */

.ticket-pdf-qr {
    width: 90px;
    height: 90px;
}


/* =========================================================
   CONTENT
   ========================================================= */

.ticket-pdf-content {
    padding: 30px;
    flex: 1;
    box-sizing: border-box;
}


/* =========================================================
   SECTIONS
   ========================================================= */

.ticket-pdf-section {
    margin-bottom: 14px;
}

.ticket-pdf-section-title {
    margin: 0;

    padding: 15px 20px;

    background-color: var(--ticketSection);

    color: #d6b86a;

    font-size: 22px;
    font-weight: 800;

    text-transform: uppercase;
    letter-spacing: 1px;

    border-radius: 14px 14px 0 0;
}


/* =========================================================
   DATA
   ========================================================= */

.ticket-pdf-data {
    background-color: var(--ticketField);

    border-radius: 0 0 14px 14px;

    overflow: hidden;
}

.ticket-pdf-row {
    display: grid;
    grid-template-columns: 35% 65%;

    min-height: 48px;

    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.ticket-pdf-row:last-child {
    border-bottom: none;
}

.ticket-pdf-label {
    display: flex;
    align-items: center;

    padding: 10px 20px;

    color: #aaaab3;

    font-size: 16px;
    font-weight: 600;
}

.ticket-pdf-value {
    display: flex;
    align-items: center;

    padding: 10px 20px;

    color: #f1f1f4;

    font-size: 17px;
    font-weight: 600;
}


/* =========================================================
   BARCODE
   ========================================================= */

.ticket-pdf-barcode-wrapper {
    padding: 25px 0 25px;

    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.ticket-pdf-barcode {
    max-width: 480px;
    width: 75%;
    height: auto;
}

.ticket-pdf-barcode-number {
    margin-top: 8px;

    color: var(--primaryColor);

    font-size: 25px;
    letter-spacing: 5px;
}


/* =========================================================
   HINWEISE
   ========================================================= */

.ticket-pdf-notices {
    background-color: var(--ticketField);

    padding: 18px 20px;

    border-radius: 0 0 14px 14px;
}

.ticket-pdf-notices p {
    margin: 0 0 12px;

    color: #aaaab3;

    font-size: 14px;
    line-height: 1.6;
}

.ticket-pdf-notices p:last-child {
    margin-bottom: 0;
}

.ticket-pdf-notices strong {
    color: #d6d6dc;
}


/* =========================================================
   FOOTER
   ========================================================= */

.ticket-pdf-footer {
    min-height: 55px;

    padding: 0 20px;

    display: flex;
    justify-content: center;
    align-items: center;

    background-color: #111318;

    color: var(--greyLighter);

    font-size: 13px;
    letter-spacing: 0.5px;

    text-align: center;
    flex-shrink: 0;
}
  </style>
</head>

<body>
  <section class="ticket">

    <div class="ticket-pdf">

    <!-- HEADER -->
    <div class="ticket-pdf-header">

        <div class="ticket-pdf-header-left">

            <img
                class="ticket-pdf-logo"
                src="${logoBase64}"
                alt="Metis"
            >

            <div class="ticket-pdf-title">
                <h1>Herbstball 2026</h1>
                <p>Marie-Curie Gymnasium</p>
            </div>

        </div>

        <img
            class="ticket-pdf-qr"
            src="${qrBase64}"
            alt="Tickets kaufen"
        >

    </div>


    <!-- CONTENT -->
    <div class="ticket-pdf-content">


        <!-- TICKETINHABER -->
        <div class="ticket-pdf-section">

            <div class="ticket-pdf-section-title">
                Ticketinhaber
            </div>

            <div class="ticket-pdf-data">

                <div class="ticket-pdf-row">
                    <div class="ticket-pdf-label">
                        Ticket-ID
                    </div>

                    <div class="ticket-pdf-value">
                        ${person_id}
                    </div>
                </div>


                <!--<div class="ticket-pdf-row">
                    <div class="ticket-pdf-label">
                        Käufer-ID
                    </div>

                    <div class="ticket-pdf-value">
                        ${data[0].kaeufer_id}
                    </div>
                </div>-->


                <div class="ticket-pdf-row">
                    <div class="ticket-pdf-label">
                        Vorname
                    </div>

                    <div class="ticket-pdf-value">
                        ${data[0].vorname}
                    </div>
                </div>


                <div class="ticket-pdf-row">
                    <div class="ticket-pdf-label">
                        Nachname
                    </div>

                    <div class="ticket-pdf-value">
                        ${data[0].nachname}
                    </div>
                </div>


                <div class="ticket-pdf-row">
                    <div class="ticket-pdf-label">
                        Email
                    </div>

                    <div class="ticket-pdf-value">
                        ${data[0].email}
                    </div>
                </div>

            </div>

        </div>


        <!-- VERANSTALTUNG -->
        <div class="ticket-pdf-section">

            <div class="ticket-pdf-section-title">
                Veranstaltungsdetails
            </div>

            <div class="ticket-pdf-data">

                <div class="ticket-pdf-row">
                    <div class="ticket-pdf-label">
                        Datum & Uhrzeit
                    </div>

                    <div class="ticket-pdf-value">
                        16.10.2026 20:00:00
                    </div>
                </div>


                <div class="ticket-pdf-row">
                    <div class="ticket-pdf-label">
                        Einlass
                    </div>

                    <div class="ticket-pdf-value">
                        18:45 Uhr
                    </div>
                </div>


                <div class="ticket-pdf-row">
                    <div class="ticket-pdf-label">
                        Ort
                    </div>

                    <div class="ticket-pdf-value">
                        Friedrich-Wolf-Straße 31, Oranienburg
                    </div>
                </div>

            </div>

        </div>


        <!-- BARCODE -->

        <div class="ticket-pdf-barcode-wrapper">

            <img
                class="ticket-pdf-barcode"
                src="${barcodeBase64}"
                alt="Ticket Barcode"
            >

        </div>


        <!-- HINWEISE -->

        <div class="ticket-pdf-section">

            <div class="ticket-pdf-section-title">
                Hinweise
            </div>

            <div class="ticket-pdf-notices">

                <p>
                    Dieses Ticket berechtigt zum Eintritt zur oben
                    genannten Vorstellung von
                    <strong>Herbstball 2026</strong><br>

                    Bitte halten Sie dieses Ticket
                    (digital oder ausgedruckt)
                    beim Einlass bereit.<br>

                    Einlass nur zur gebuchten Vorstellung.
                    Kein Wiedereinlass nach Verlassen des Veranstaltungsgeländes.<br><br>

                    Weitere Informationen unter:
                    <strong>curiegymnasium.de</strong><br>
                </p>

            </div>

        </div>

    </div>


    <!-- FOOTER -->

    <div class="ticket-pdf-footer">
        Herbstball 2026 · Alle Angaben ohne Gewähr · Powered by Metis
    </div>

</div>

  </section>
</body>
</html>
  `

  const browser = await puppeteer.launch({ headless: true, product: 'firefox' });
  const page = await browser.newPage();

  await page.setContent(html, { waitUntil: 'domcontentloaded' });
  await page.pdf({
    path: outputPath,
    format: 'A4',
    printBackground: true,
    margin: { top: '0mm', bottom: '0mm', left: '0mm', right: '0mm' }
  });

  await browser.close();
  console.log(`✅ PDF wurde erstellt: ${outputPath}`);
  return Buffer.from(fs.readFileSync(outputPath), "utf8").toString('base64');
}

app.get('/', async (req, res) => {
  if (!req.query.person_id) return res.status(400).send('fail');

  try {
    const pdf = await generatePDF(req.query.person_id);
    //res.send('success');
    res.json({ status: 'success', pdf });
  } catch (err) {
    console.error('❌ Fehler bei PDF-Erstellung:', err);
    res.status(500).send('fail');
  }
});

app.listen(port, () => {
  console.log(`listening on port ${port}`);
});
