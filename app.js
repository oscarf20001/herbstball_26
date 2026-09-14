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

  const eventCode = 'WB2025_';
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
      p.age
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
      <title>Weihnachtsball Ticket</title>
      <link rel="stylesheet" href="style.css">
      <link rel="stylesheet" href="../../client/styles/tables.css">
      <style>
        @page {
          margin: 0;
          size: A4;
        }

        :root{
            --black: #000;
            --blackLighter: #231c3f;
            --border: rgba(35, 28, 63, 0.4);
            --grey: #484459;
            --greyLighter: #777484;
            --primaryColor: #fffcf4;
            --primaryDarker: #f1f1f1;
            --primaryVeryDark: #e3e3e3;
            --secondaryColor: #7F63F4;
            --secondaryColorDarker: #6a48f1;
            --atentionColor: #f14848;
            --pureRed: #ff0000;
            --signalRed: #ff1a1a;
            --signalShineRed: rgba(255, 0, 0, 0.6);
            --signalGreen: #00cc44;
            --signalShineGreen: rgba(0, 255, 0, 0.5);
            --hover: rgba(127, 99, 244, 0.15);
            --selected: rgba(127, 99, 244, 0.3);
            --headingFontSize: 1.5rem;
            --borderRadius: 0.4rem;
        }

        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }

        .ticket {
            border: 2px solid #333;
            max-height: 1150px;
            box-sizing: border-box;
            display: grid;
            grid-template-columns: 1fr;
            grid-template-rows: 100px 1.5fr 1fr 1fr 50px;
            grid-column-gap: 0px;
            grid-row-gap: 0px; 
            aspect-ratio: 1/1.414;
        }

        header{
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-direction: row;
            height: 100px;
            background-color: var(--secondaryColor);
            padding: 0 0cm 0 1cm;
            color: var(--primaryColor);
        }

        #metis-logo{
            height: 50%;
            width: auto;
        }

        #buyNewTicketsQrCode{
            height: 100%;
            width: auto;
        }

        .headliner {
            text-align: center;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .headliner h1{
            margin: 0;
            text-align: center;
        }

        .headliner p{
            margin: 0;
            text-align: center;
        }

        .info {
            font-size: 1.2em;
            line-height: 1.6;
            /*border: 2px dashed blue;*/
        }

        table{
            height: fit-content;
            width: calc(100% - 20px);
            table-layout: auto;
            border-collapse: collapse;
            text-align: left;
            margin: 2rem 10px 0 10px;
            position: relative;
        }

        table caption{
            font-size: 1.25rem;
            font-weight: bold;
            text-align: left;
            margin-bottom: 1em;
            caption-side: top;
            text-align: left;
            top: 0;
            left: 0;
            /*background-color: var(--secondaryColor);*/
            color: var(--blackLighter);
            border-bottom: 2px solid var(--secondaryColor);
        }

        table thead{
            margin: 2rem 0 0 0;
        }

        #service caption {
            font-weight: bold;
            text-align: left;
            margin-bottom: 1em;
        }

        #service th,
        #service td {
            padding: 0.2em;
            text-align: left;
        }

        #service th {
            width: 40%;
        }

        #teilnahmebedingungen td{
            font-size: 16px;
        }

        .bc {
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            /*border: 2px dashed red;*/
        }

        footer {
            text-align: center;
            font-size: 0.9em;
            color: #555;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            /*border: 2px dashed green;*/
        }

        footer p{
            margin: 0 auto;
        }

        :root{
            --black: #000;
            --blackLighter: #231c3f;
            --border: rgba(35, 28, 63, 0.4);
            --grey: #484459;
            --greyLighter: #777484;
            --primaryColor: #fffcf4;
            --primaryDarker: #f1f1f1;
            --primaryVeryDark: #e3e3e3;
            --secondaryColor: #7F63F4;
            --secondaryColorDarker: #6a48f1;
            --atentionColor: #f14848;
            --pureRed: #ff0000;
            --hover: rgba(127, 99, 244, 0.15);
            --selected: rgba(127, 99, 244, 0.3);
            --headingFontSize: 1.5rem;
        }

        #displayAllTicketsContainer{
            display: none;
        }

        .table_component {
            margin: 2rem 0 0 0;
            padding: 0.5rem;

            overflow: auto;
            width: 100%;
            background-color: var(--primaryColor);

            border-radius: 0.4rem;
            position: relative;
        }

        .table_component table {
            height: 100%;
            width: 100%;
            table-layout: auto;
            border-collapse:collapse;
            text-align: left;
            margin: 2rem 0 0 0;
        }

        .table_component caption {
            caption-side: top;
            text-align: left;
            position: absolute;
            top: 0;
            left: 0;
            background-color: var(--secondaryColor);
            padding: 0.5rem;
            color: var(--primaryColor);
            border-bottom-right-radius: 0.4rem;
        }

        .table_component th {
            color: #000000;
            padding: 5px;
        }

        .table_component td {
            color: #000000;
            padding: 5px;
        }

        #setFinancing{
            margin: 1rem 0 0 0;
        }

        #financing{
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-direction: row;
        }

        #financing #money-form-left{
            display: flex;
            flex-direction: row;
            align-items: center;
        }

        input[type='number'] {
            -moz-appearance:textfield;
            appearance: textfield;
        }

        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
        }

        #financing #money-form-left select{
            background-color: var(--primaryColor);
            border:none;
            border-radius: 0.4rem;
            padding: 1rem;
            margin: 0 0 0 1rem;
        }

        #financing #money-form-right{
            transform: translateX(1rem);
        }
      </style>
  </head>
  <body>
      <section class="ticket">
          <header>
              <div id="metis-logo">
                <img src="${logoBase64}" alt="Logo" height="100%" width="auto">
              </div>
              <div id="headliner" class="headliner">
                  <h1>Weihnachtsball 2025</h1>
                  <p>Marie-Curie Gymnasium</p>
              </div>
              <div id="buyNewTicketsQrCode" style="padding: 16px !important; display: flex; justify-content: center; align-items: center;">
                <img src="${qrBase64}" alt="QR-Code for new Tickets" height="75%" width="auto">
              </div>
          </header>
          <div class="info">
              <table id="customer">
                  <caption>Deine Daten:</caption>
                  <thead>
                      <tr>
                          <th>ID</th>
                          <th>Käufer-ID</th>
                          <th>Vorname</th>
                          <th>Nachname</th>
                          <th>Alter</th>
                      </tr>
                  </thead>

                  <tbody>
                      <tr>
                          <td>${person_id}</td>
                          <td>${data[0].kaeufer_id}</td>
                          <td>${data[0].vorname}</td>
                          <td>${data[0].nachname}</td>
                          <td>${data[0].age}</td>
                      </tr>
                  </tbody>
              </table>
              <table id="service">
                  <caption>Hinweise zur Veranstaltung:</caption>
                  <tbody>
                      <tr>
                          <th>Einlass</th>
                          <td>18:45 Uhr / 19.12.2025</td>
                      </tr>
                      <tr>
                          <th>Ende Einlass</th>
                          <td>21:00 Uhr / 19.12.2025</td>
                      </tr>
                      <tr>
                          <th>Beginn der Veranstaltung</th>
                          <td>20:00 Uhr / 19.12.2025</td>
                      </tr>
                      <tr>
                          <th>Ende der Veranstaltung</th>
                          <td>01:00 Uhr / 20.12.2025</td>
                      </tr>
                      <tr>
                          <th>Adresse</th>
                          <td>Friedrich-Wolf-Straße 31, Oranienburg</td>
                      </tr>
                      <tr>
                          <th>Mindestalter</th>
                          <td>16</td>
                      </tr>
                  </tbody>
              </table>
          </div>
          <div class="bc">  
              <img src="${barcodeBase64}" alt="Bar-Code">
              <!--<img src="{{ qr_path }}" alt="Bar-Code">-->
          </div>
          <table id="teilnahmebedingungen">
                  <caption>Teilnahmebedingungen:</caption>
                  <tr>
                      <th></th>
                      <td>Einlass ab 16 Jahren (unter 18 nur bis 24:00 Uhr oder mit Erziehungsbeauftragung gemäß JuSchG). Keine Rücknahme oder Erstattung von Tickets. Keine Haftung für Sach- oder Personenschäden. Mit Betreten des Geländes erklären Sie sich mit möglichen Foto- und Videoaufnahmen einverstanden. Es gelten die vollständigen Teilnahmebedingungen unter:
                          <br><a href="https://www.curiegymnasium.de/client/bedingungen.php">curiegymnasium.de/client/bedingungen.php</a>
                      </td>
                  </tr>
            </table>
          <footer>
              <p>Bitte beim Einlass bereithalten · Kein Wiedereinlass möglich · Alle Angaben ohne Gewähr</p>
              <p>Weitere Informationen: Impressum & Datenschutz: <a href="https://curiegymnasium.de/client/imprint.php">https://curiegymnasium.de/client/imprint.php</a></p>
          </footer>
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
