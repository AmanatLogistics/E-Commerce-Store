// The only module the pages talk to. It turns sheet rows into objects and
// hides which backend (Google Sheets or the local demo file) is in use.
import {
  USING_SHEETS,
  SHIPMENT_TAB,
  EVENT_TAB,
  SHIPMENT_HEADERS,
  EVENT_HEADERS,
} from './config.js';

const driver = USING_SHEETS
  ? await import('./google.js')
  : await import('./demo.js');

// Row 1 of the sheet holds the headers, so the first record sits on row 2.
const FIRST_DATA_ROW = 2;

function toObject(headers, row, index) {
  const item = { _row: index + FIRST_DATA_ROW };
  headers.forEach((key, i) => {
    item[key] = row[i] ?? '';
  });
  return item;
}

function toRow(headers, item) {
  return headers.map((key) => item[key] ?? '');
}

const sameTracking = (a, b) =>
  String(a).trim().toLowerCase() === String(b).trim().toLowerCase();

export function timestamp() {
  return new Date().toISOString().slice(0, 16).replace('T', ' ');
}

export async function listShipments() {
  const rows = await driver.readRows(SHIPMENT_TAB);
  return rows
    .map((row, i) => toObject(SHIPMENT_HEADERS, row, i))
    .filter((s) => s.tracking);
}

export async function getShipment(tracking) {
  const all = await listShipments();
  return all.find((s) => sameTracking(s.tracking, tracking)) ?? null;
}

export async function createShipment(data) {
  const existing = await getShipment(data.tracking);
  if (existing) throw new Error(`Tracking number ${data.tracking} already exists.`);

  const shipment = { ...data, updated: timestamp() };
  await driver.appendRow(SHIPMENT_TAB, toRow(SHIPMENT_HEADERS, shipment));

  // Every shipment starts with one history entry so the client view is never blank.
  await addEvent({
    tracking: shipment.tracking,
    location: shipment.origin,
    status: shipment.status,
    note: 'Shipment created',
  });
  return shipment;
}

export async function updateShipment(tracking, patch) {
  const current = await getShipment(tracking);
  if (!current) throw new Error(`Tracking number ${tracking} was not found.`);

  const merged = { ...current, ...patch, updated: timestamp() };
  await driver.updateRow(SHIPMENT_TAB, current._row, toRow(SHIPMENT_HEADERS, merged));
  return merged;
}

export async function deleteShipment(tracking) {
  const current = await getShipment(tracking);
  if (!current) return;
  await driver.clearRow(SHIPMENT_TAB, current._row);
}

export async function listEvents(tracking) {
  const rows = await driver.readRows(EVENT_TAB);
  return rows
    .map((row, i) => toObject(EVENT_HEADERS, row, i))
    .filter((e) => e.tracking && sameTracking(e.tracking, tracking))
    .sort((a, b) => String(b.date).localeCompare(String(a.date)));
}

export async function addEvent(data) {
  const event = { ...data, date: data.date || timestamp() };
  await driver.appendRow(EVENT_TAB, toRow(EVENT_HEADERS, event));
  return event;
}
