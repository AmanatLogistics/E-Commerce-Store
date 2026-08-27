// Local stand-in for Google Sheets, used only when no credentials are set.
// It stores the same rows in a JSON file so the app is runnable straight away
// and the rest of the code never needs to know which backend is live.
import { readFile, writeFile, mkdir } from 'node:fs/promises';
import path from 'node:path';
import { SHIPMENT_TAB, EVENT_TAB } from './config.js';

const FILE = path.resolve('data/demo-data.json');

const seed = () => ({
  [SHIPMENT_TAB]: [
    ['AMT-1001', 'Ali Raza', '+92 300 1234567', 'Karachi', 'Lahore', 'In Transit', '2026-09-02', '2026-08-27 09:15'],
    ['AMT-1002', 'Sara Khan', '+92 321 7654321', 'Dubai', 'Karachi', 'Delivered', '2026-08-24', '2026-08-24 17:40'],
  ],
  [EVENT_TAB]: [
    ['AMT-1001', '2026-08-25 08:00', 'Karachi', 'Picked Up', 'Collected from shipper'],
    ['AMT-1001', '2026-08-27 09:15', 'Sukkur', 'In Transit', 'Departed transit hub'],
    ['AMT-1002', '2026-08-22 11:30', 'Dubai', 'Picked Up', 'Collected from shipper'],
    ['AMT-1002', '2026-08-24 17:40', 'Karachi', 'Delivered', 'Signed by receiver'],
  ],
});

async function load() {
  try {
    return JSON.parse(await readFile(FILE, 'utf8'));
  } catch {
    const data = seed();
    await save(data);
    return data;
  }
}

async function save(data) {
  await mkdir(path.dirname(FILE), { recursive: true });
  await writeFile(FILE, JSON.stringify(data, null, 2));
}

export async function readRows(tab) {
  const data = await load();
  return data[tab] ?? [];
}

export async function appendRow(tab, values) {
  const data = await load();
  (data[tab] ??= []).push(values);
  await save(data);
}

export async function updateRow(tab, rowNumber, values) {
  const data = await load();
  data[tab][rowNumber - 2] = values;
  await save(data);
}

export async function clearRow(tab, rowNumber) {
  const data = await load();
  data[tab][rowNumber - 2] = [];
  await save(data);
}
