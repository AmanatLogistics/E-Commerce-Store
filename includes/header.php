<?php
/** Storefront chrome. Set $page_title before including this file. */
require_once __DIR__ . '/functions.php';

$page_title   = $page_title ?? SHOP_NAME;
$active_cat   = $_GET['category'] ?? '';
$search_term  = trim((string) ($_GET['q'] ?? ''));
$nav_cats     = $pdo->query('SELECT DISTINCT category FROM products WHERE category <> "" ORDER BY category')
                    ->fetchAll(PDO::FETCH_COLUMN);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title) ?> &middot; <?= e(SHOP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,400..700&family=Rozha+One&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>

<div class="topbar">
    <div class="shell topbar__row">
        <span><?= e(SHOP_TAGLINE) ?></span>
        <span>Cash on delivery, everywhere in Pakistan</span>
    </div>
</div>

<header class="masthead">
    <div class="shell masthead__row">
        <a class="wordmark" href="<?= e(url('index.php')) ?>">
            <?= e(SHOP_NAME) ?>
            <small>Handmade homeware</small>
        </a>
        <a class="cart-link" href="<?= e(url('cart.php')) ?>">
            Cart <span class="cart-link__count"><?= (int) cart_count() ?></span>
        </a>
    </div>
</header>

<div class="searchbar">
    <div class="shell">
        <form class="searchbar__form" action="<?= e(url('index.php')) ?>" method="get" role="search">
            <input class="searchbar__input" type="search" name="q" id="q"
                   value="<?= e($search_term) ?>"
                   placeholder="Search the shelf — pottery, brass, ajrak&hellip;"
                   aria-label="Search products">
            <button class="searchbar__go" type="submit">Search</button>
        </form>
    </div>
</div>

<?php if ($nav_cats): ?>
<nav class="categories" aria-label="Categories">
    <div class="categories__inner">
        <a href="<?= e(url('index.php')) ?>" aria-current="<?= $active_cat === '' ? 'true' : 'false' ?>">Everything</a>
        <?php foreach ($nav_cats as $cat): ?>
            <a href="<?= e(url('index.php?category=' . urlencode($cat))) ?>"
               aria-current="<?= $active_cat === $cat ? 'true' : 'false' ?>"><?= e($cat) ?></a>
        <?php endforeach; ?>
    </div>
</nav>
<?php endif; ?>

<main class="shell" id="main">
<?php foreach (take_flash() as $note): ?>
    <p class="flash flash--<?= e($note['tone']) ?>"><?= e($note['message']) ?></p>
<?php endforeach; ?>
