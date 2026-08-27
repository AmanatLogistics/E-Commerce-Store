# Shipment Tracker — Astro + Google Sheets

A small tracking site with two sides:

- **Client view** (`/`) — a customer types a tracking number and sees the current
  status plus the full history.
- **Admin panel** (`/admin`) — password protected. Add shipments, post tracking
  updates, edit details, delete.

There is **no database**. All data lives in one Google Sheet, so you (or anyone
in the office) can also edit rows directly in the sheet and the website shows the
change on the next page load.

## Quick start (no Google setup needed)

```bash
npm install
npm run dev
```

Open http://localhost:4321 and track **AMT-1001**.
The admin panel is at http://localhost:4321/admin, password **admin123**.

Until you add Google credentials the app runs in *demo mode* and saves to
`data/demo-data.json` instead of a sheet. Everything else behaves identically, so
you can try the whole flow first and connect the sheet after.

## Connecting your Google Sheet

**1. Create the spreadsheet.** New blank sheet in Google Drive. Copy the id out
of the URL:
`https://docs.google.com/spreadsheets/d/`**`THIS_LONG_ID`**`/edit`

**2. Create a service account** (this is the "robot user" the website logs in as):

1. Go to https://console.cloud.google.com → create a project (any name).
2. **APIs & Services → Library** → search *Google Sheets API* → **Enable**.
3. **APIs & Services → Credentials → Create credentials → Service account**.
   Give it a name, click through, then **Done**.
4. Open the service account → **Keys → Add key → Create new key → JSON**.
   A `.json` file downloads. Keep it private — never commit it.

**3. Share the sheet with the service account.** Open the JSON file, copy the
`client_email` value (looks like `something@your-project.iam.gserviceaccount.com`)
and share your spreadsheet with that address as an **Editor**. This step is the
one people forget; without it you get a permission error.

**4. Fill in `.env`:**

```bash
cp .env.example .env
```

```
GOOGLE_SHEET_ID=the_long_id_from_step_1
GOOGLE_SERVICE_ACCOUNT_EMAIL=something@your-project.iam.gserviceaccount.com
GOOGLE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nMII...\n-----END PRIVATE KEY-----\n"
ADMIN_PASSWORD=pick-a-real-password
```

`GOOGLE_PRIVATE_KEY` is the `private_key` value from the JSON file — copy it
exactly, keeping the quotes and the `\n` pieces.

**5. Create the tabs and headers:**

```bash
npm run init:sheet
```

**6. Restart the dev server.** The banner about demo mode disappears once the
sheet is connected.

## How the sheet is laid out

Tab **Shipments** — one row per shipment:

| tracking | customer | phone | origin | destination | status | eta | updated |
| -------- | -------- | ----- | ------ | ----------- | ------ | --- | ------- |
| AMT-1001 | Ali Raza | +92 300 1234567 | Karachi | Lahore | In Transit | 2026-09-02 | 2026-08-27 09:15 |

Tab **Events** — one row per tracking update, oldest first:

| tracking | date | location | status | note |
| -------- | ---- | -------- | ------ | ---- |
| AMT-1001 | 2026-08-25 08:00 | Karachi | Picked Up | Collected from shipper |

**Row 1 must stay the header row**, and column order must not change — the code
reads columns by position. Adding extra columns at the *end* is safe; they are
simply ignored.

### Moving your existing Excel data in

Open your Excel file, arrange the columns in the order above, then paste the rows
into the **Shipments** tab starting at row 2. Anything you paste is immediately
trackable on the website — no import step. If a shipment has no history yet, the
client view just shows the shipment card with an empty history.

## Status values

`Pending`, `Picked Up`, `In Transit`, `At Warehouse`, `Out for Delivery`,
`Delivered`, `On Hold` — edit the `STATUSES` list in `src/lib/config.js` to change
them.

## Project layout

```
src/
  lib/
    config.js    all settings + sheet column definitions
    google.js    reads/writes rows in Google Sheets
    demo.js      same thing against a local JSON file (demo mode)
    store.js     rows <-> objects; the only module the pages use
    auth.js      admin password + session cookie
  middleware.js  blocks /admin until logged in
  layouts/       page shell and all the CSS
  components/    status badge
  pages/
    index.astro          client tracking view
    admin/index.astro    shipment list + search + delete
    admin/new.astro      add shipment
    admin/[tracking].astro  post updates, edit details, see history
scripts/init-sheet.mjs   creates the tabs and header rows
```

Because everything sheet-related sits behind `src/lib/store.js`, swapping Google
Sheets for a real database later means rewriting that one file — the pages stay
as they are.

## Deploying

Build and run with Node:

```bash
npm run build
npm run preview   # serves dist/server/entry.mjs on port 4321
```

Set the same four environment variables on the host. The app is server-rendered,
so it needs a Node host (Railway, Render, Fly, a VPS, or Vercel/Netlify with their
Astro adapter). A plain static host will not work.

## Things to know before using this for real

- **Speed.** Every page load calls the Google Sheets API. That is fine for a few
  hundred shipments and a handful of users. For heavier traffic add caching or
  move to a database.
- **Limits.** Google allows 60 read requests per minute per user by default.
- **Login.** One shared password, checked against a cookie. Good enough for an
  internal panel; use proper accounts if many people need separate logins.
- **Two people editing at once.** The sheet is last-write-wins. Fine for one or
  two operators, not for a large team.
