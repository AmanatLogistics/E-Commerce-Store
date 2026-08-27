// Central place for every setting. Everything comes from environment variables
// so nothing secret ever lands in the repo.
// Astro exposes .env values on import.meta.env, while a plain `node` script
// (and a real deployment) has them on process.env. Check both.
const env = (key, fallback = '') =>
  import.meta.env?.[key] || process.env[key] || fallback;

export const SHEET_ID = env('GOOGLE_SHEET_ID');
export const SERVICE_EMAIL = env('GOOGLE_SERVICE_ACCOUNT_EMAIL');
// Private keys are stored with literal "\n" in .env files, so turn them back
// into real newlines before handing the key to Google.
export const SERVICE_KEY = env('GOOGLE_PRIVATE_KEY').replace(/\\n/g, '\n');

export const ADMIN_PASSWORD = env('ADMIN_PASSWORD', 'admin123');

// When the Google credentials are missing we fall back to a local JSON file so
// the app still runs. Handy for a first look before any Google setup is done.
export const USING_SHEETS = Boolean(SHEET_ID && SERVICE_EMAIL && SERVICE_KEY);

export const SHIPMENT_TAB = 'Shipments';
export const EVENT_TAB = 'Events';

export const SHIPMENT_HEADERS = [
  'tracking',
  'customer',
  'phone',
  'origin',
  'destination',
  'status',
  'eta',
  'updated',
];

export const EVENT_HEADERS = ['tracking', 'date', 'location', 'status', 'note'];

export const STATUSES = [
  'Pending',
  'Picked Up',
  'In Transit',
  'At Warehouse',
  'Out for Delivery',
  'Delivered',
  'On Hold',
];
