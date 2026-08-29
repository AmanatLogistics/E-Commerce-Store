<?php
/**
 * Shared helpers for the storefront and the admin panel.
 * Every page starts by requiring this file; it pulls in the database too.
 */

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------------------------------------------------------------- output */

/** Escape anything before it is printed into HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Plain-text price, e.g. "Rs 4,500". */
function money(float $amount): string
{
    return 'Rs ' . number_format($amount, 0);
}

/**
 * Price set the way the shop sets prices: a small "Rs" in the UI face
 * followed by the figure in the display face.
 */
function price_html(float $amount): string
{
    return '<span class="price"><span class="price__unit">Rs</span>'
        . '<span class="price__figure">' . e(number_format($amount, 0)) . '</span></span>';
}

/* ------------------------------------------------------------------ urls */

/** Web path to the app root, so admin/ and the store can share links. */
function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if (basename($dir) === 'admin') {
        $dir = dirname($dir);
    }
    $dir = rtrim(str_replace('\\', '/', $dir), '/');
    return $base = ($dir === '' || $dir === '.') ? '' : $dir;
}

function url(string $path): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/* ------------------------------------------------------------------ csrf */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Drop this inside every POST form. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Stop the request unless the posted token matches the session token. */
function csrf_verify(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('That form expired before it was submitted. Go back, reload the page and try again.');
    }
}

/* --------------------------------------------------------------- flashes */

function flash(string $message, string $tone = 'ok'): void
{
    $_SESSION['flash'][] = ['message' => $message, 'tone' => $tone];
}

/** Returns the queued messages and empties the queue. */
function take_flash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ------------------------------------------------------------------ cart */

/** The cart is a plain [product_id => quantity] map in the session. */
function cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(cart());
}

/**
 * Cart contents joined against live product rows, so a deleted or renamed
 * product cannot leave a stale line behind.
 */
function cart_lines(PDO $pdo): array
{
    $cart = cart();
    if (!$cart) {
        return [];
    }
    $ids = array_map('intval', array_keys($cart));
    $in  = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($in)");
    $stmt->execute($ids);

    $lines = [];
    foreach ($stmt->fetchAll() as $product) {
        $quantity = (int) $cart[$product['id']];
        $lines[]  = $product + [
            'quantity'   => $quantity,
            'line_total' => $quantity * (float) $product['price'],
        ];
    }
    return $lines;
}

function cart_total(array $lines): float
{
    return array_sum(array_column($lines, 'line_total'));
}

/* -------------------------------------------------------------- products */

/** Web path to a product photo, or null when there is nothing to show. */
function product_image(array $product): ?string
{
    $file = trim((string) ($product['image'] ?? ''));
    if ($file === '' || !is_file(UPLOAD_DIR . '/' . $file)) {
        return null;
    }
    return url('uploads/' . rawurlencode($file));
}

/**
 * Stand-in for a missing photo: the product's initial, set in the display
 * face on a glazed tile. Keeps the grid intact instead of leaving a hole.
 */
function image_placeholder(string $name): string
{
    $initial = mb_strtoupper(mb_substr(trim($name), 0, 1) ?: '?');
    return '<div class="thumb__blank" aria-hidden="true">' . e($initial) . '</div>';
}

/* ---------------------------------------------------------------- orders */

function order_statuses(): array
{
    return ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
}

/** Human-readable order number, e.g. "#1042". */
function order_number(int $id): string
{
    return '#' . (1000 + $id);
}
