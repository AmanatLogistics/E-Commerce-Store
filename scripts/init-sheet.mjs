// One-off helper: creates the Shipments and Events tabs with their header rows.
// Run once after creating an empty spreadsheet:  npm run init:sheet
import { google } from 'googleapis';

try {
  process.loadEnvFile('.env');
} catch {
  // No .env file — fall back to whatever is already in the environment.
}

const {
  SHEET_ID,
  SERVICE_EMAIL,
  SERVICE_KEY,
  SHIPMENT_TAB,
  EVENT_TAB,
  SHIPMENT_HEADERS,
  EVENT_HEADERS,
} = await import('../src/lib/config.js');

if (!SHEET_ID || !SERVICE_EMAIL || !SERVICE_KEY) {
  console.error(
    'Missing credentials. Set GOOGLE_SHEET_ID, GOOGLE_SERVICE_ACCOUNT_EMAIL and\n' +
      'GOOGLE_PRIVATE_KEY in .env first (see .env.example).',
  );
  process.exit(1);
}

const auth = new google.auth.JWT({
  email: SERVICE_EMAIL,
  key: SERVICE_KEY,
  scopes: ['https://www.googleapis.com/auth/spreadsheets'],
});
const sheets = google.sheets({ version: 'v4', auth });

const meta = await sheets.spreadsheets.get({ spreadsheetId: SHEET_ID });
const existing = meta.data.sheets.map((s) => s.properties.title);

const missing = [SHIPMENT_TAB, EVENT_TAB].filter((tab) => !existing.includes(tab));
if (missing.length) {
  await sheets.spreadsheets.batchUpdate({
    spreadsheetId: SHEET_ID,
    requestBody: {
      requests: missing.map((title) => ({ addSheet: { properties: { title } } })),
    },
  });
  console.log(`Created tab(s): ${missing.join(', ')}`);
}

for (const [tab, headers] of [
  [SHIPMENT_TAB, SHIPMENT_HEADERS],
  [EVENT_TAB, EVENT_HEADERS],
]) {
  await sheets.spreadsheets.values.update({
    spreadsheetId: SHEET_ID,
    range: `${tab}!A1`,
    valueInputOption: 'RAW',
    requestBody: { values: [headers] },
  });
  console.log(`Headers written to ${tab}: ${headers.join(', ')}`);
}

console.log(`\nDone. Sheet: https://docs.google.com/spreadsheets/d/${SHEET_ID}`);
