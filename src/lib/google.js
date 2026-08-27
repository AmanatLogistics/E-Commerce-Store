// Thin wrapper over the Google Sheets API: rows in, rows out.
// Every row is a plain array of cell values, exactly like the sheet itself.
import { google } from 'googleapis';
import { SHEET_ID, SERVICE_EMAIL, SERVICE_KEY } from './config.js';

let sheetsClient;

function client() {
  if (!sheetsClient) {
    const auth = new google.auth.JWT({
      email: SERVICE_EMAIL,
      key: SERVICE_KEY,
      scopes: ['https://www.googleapis.com/auth/spreadsheets'],
    });
    sheetsClient = google.sheets({ version: 'v4', auth });
  }
  return sheetsClient;
}

// Reads every data row of a tab, skipping the header row.
export async function readRows(tab) {
  const res = await client().spreadsheets.values.get({
    spreadsheetId: SHEET_ID,
    range: `${tab}!A2:Z`,
  });
  return res.data.values ?? [];
}

export async function appendRow(tab, values) {
  await client().spreadsheets.values.append({
    spreadsheetId: SHEET_ID,
    range: `${tab}!A:Z`,
    valueInputOption: 'USER_ENTERED',
    insertDataOption: 'INSERT_ROWS',
    requestBody: { values: [values] },
  });
}

// rowNumber is the real spreadsheet row (header is row 1, first record is row 2).
export async function updateRow(tab, rowNumber, values) {
  const lastColumn = String.fromCharCode(64 + values.length);
  await client().spreadsheets.values.update({
    spreadsheetId: SHEET_ID,
    range: `${tab}!A${rowNumber}:${lastColumn}${rowNumber}`,
    valueInputOption: 'USER_ENTERED',
    requestBody: { values: [values] },
  });
}

export async function clearRow(tab, rowNumber) {
  await client().spreadsheets.values.clear({
    spreadsheetId: SHEET_ID,
    range: `${tab}!A${rowNumber}:Z${rowNumber}`,
  });
}
