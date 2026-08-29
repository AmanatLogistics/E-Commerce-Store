# Meenakar — a small PHP + MySQL shop

A complete storefront and admin panel in plain PHP 8 and MySQL. No framework,
no Composer, no Node build step. Drop the folder into `htdocs`, import one SQL
file, and it runs.

The sample shop sells handmade Pakistani homeware — Multani blue pottery,
brassware, ajrak — with prices in PKR and cash on delivery. Everything is
sample data, so replace it with your own.

---

## Setup

**1. Put the folder in your web root**

```
XAMPP (Windows)   C:\xampp\htdocs\meenakar
XAMPP (macOS)     /Applications/XAMPP/htdocs/meenakar
LAMP (Linux)      /var/www/html/meenakar
```

**2. Start Apache and MySQL** from the XAMPP control panel, or on Linux:

```bash
sudo service apache2 start
sudo service mysql start
```

**3. Import the database**

In phpMyAdmin: open <http://localhost/phpmyadmin>, click **Import**, choose
`database.sql`, and press Go. It creates the `meenakar` database, all five
tables, one admin account and ten sample products.

Or from a terminal:

```bash
mysql -u root -p < database.sql
```

**4. Check the connection settings** in `config/db.php`. The XAMPP defaults
are already there:

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'meenakar');
define('DB_USER', 'root');
define('DB_PASS', '');
```

**5. Make `/uploads` writable** so product photos can be saved. On Windows it
already is. On Linux or macOS:

```bash
chmod 755 uploads
sudo chown www-data:www-data uploads    # or _www on macOS
```

**6. Open it**

| | |
|---|---|
| Shop | <http://localhost/meenakar/> |
| Admin | <http://localhost/meenakar/admin/login.php> |

### Admin login

```
username: admin
password: meenakar123
```

Change that password before putting this anywhere public. To set a new one,
generate a hash and update the row:

```bash
php -r 'echo password_hash("your-new-password", PASSWORD_DEFAULT), "\n";'
```

```sql
UPDATE admins SET password_hash = '<paste the hash here>' WHERE username = 'admin';
```

---

## What it does

**Shop** — a product grid, search across names and descriptions, category
filtering, product pages with a quantity selector, a session cart you can edit,
and a cash-on-delivery checkout that saves the order, decrements stock and
shows an order number.

**Admin** (`/admin`) — a dashboard of five figures and the latest orders;
products you can add, edit and delete with an image upload; orders you can open
and move through pending → confirmed → shipped → delivered → cancelled; and
notifications, whose unread badge refreshes every 30 seconds without a reload.

---

## Files

```
config/db.php              connection settings, shop name, upload limits
includes/functions.php     escaping, CSRF, cart, prices, order numbers
includes/header.php        shop masthead, search bar, category strip
includes/footer.php
index.php                  product grid, search, category filter
product.php                product page; the only route into the cart
cart.php                   quantities, removals, running total
checkout.php               validation, then order + stock in one transaction
order-success.php          confirmation, read from the session

admin/includes/auth.php    session guard
admin/includes/header.php  sidebar, top bar, unread badge
admin/includes/footer.php
admin/login.php            admin/logout.php
admin/dashboard.php        admin/products.php    admin/product-form.php
admin/orders.php           admin/order-view.php  admin/notifications.php
admin/notifications-count.php   JSON the badge polls

assets/css/style.css       the shop
assets/css/admin.css       the admin panel
assets/js/main.js          quantity steppers
assets/js/admin.js         badge polling
uploads/                   product photos
database.sql               schema and sample data
```

## Tables

| Table | Holds |
|---|---|
| `admins` | username and a `password_hash` |
| `products` | name, description, price, stock, category, image filename |
| `orders` | customer details, notes, total, status, timestamp |
| `order_items` | one row per line, with the name and price as sold |
| `notifications` | one row per order, with a read flag |

`order_items` keeps its own copy of the product name and price, and its
`product_id` goes `NULL` if the product is later deleted, so old orders still
read correctly.

## Notes

- Every query is a PDO prepared statement. No SQL is built by concatenation.
- Every value printed into a page goes through `e()` (`htmlspecialchars`).
- Every POST form carries a CSRF token, checked with `hash_equals`.
- Checkout locks the product rows (`SELECT … FOR UPDATE`) inside a transaction,
  so two shoppers cannot buy the same last piece.
- Uploads are checked for size, extension and actual image content, then stored
  under a random filename. A photo that arrives alongside a validation error is
  deleted rather than left orphaned in the folder.
- `uploads/.htaccess` stops the web server running anything from that folder,
  whichever way PHP is wired up.
- The session cookie is set HttpOnly and SameSite=Lax, strict session ids are
  on, and the cookie marks itself Secure once the site is served over HTTPS.
  Signing in and out both regenerate the session id.
- The tables are InnoDB, which the transaction and the foreign keys need.
- The sample product photos are generated geometric patterns, not photographs.
  Replace them from the admin panel.
- The two typefaces load from Google Fonts, so the shop needs a network
  connection to look right. Offline it falls back to Georgia and a system sans.

## Design

Two typefaces: **Rozha One** for the wordmark, headings and prices — a
fat-face didone with the weight of a 19th-century advertising poster — and
**Archivo** for body text and the interface. Both descend from Victorian
commercial printing, which is what makes them sit together.

Six colours, defined once as custom properties at the top of each stylesheet
and never hard-coded anywhere else:

| | | |
|---|---|---|
| `--ink` | `#101A33` | text, admin sidebar, footer |
| `--cobalt` | `#1B44A6` | the accent, from Multani underglaze |
| `--firozi` | `#0F7D7A` | in stock, delivered |
| `--brass` | `#A9761B` | low stock, pending, unread, focus rings |
| `--porcelain` | `#E4E9F2` | page background |
| `--glaze` | `#FBFCFE` | panels and tiles |

The signature is the **tile wall**: products sit in a continuous grid separated
by 1px cobalt seams rather than gutters, and hovering floods a whole tile solid
cobalt with the photo dropping to a monochrome layer inside it. There is no
border-radius and no box-shadow anywhere in either stylesheet.

The admin panel uses the same six colours and the same two faces, but at 13px
in condensed Archivo with tabular figures and the seam rule reused as table
borders — the same grammar, four times the density.
