<?php
/** Admin chrome. Set $page_title and $nav before including. */
require_once __DIR__ . '/auth.php';
require_admin();

$page_title = $page_title ?? 'Admin';
$nav        = $nav ?? '';
$unread     = unread_notifications($pdo);

$links = [
    'dashboard'     => ['Dashboard',     'admin/dashboard.php'],
    'products'      => ['Products',      'admin/products.php'],
    'orders'        => ['Orders',        'admin/orders.php'],
    'notifications' => ['Notifications', 'admin/notifications.php'],
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title) ?> &middot; <?= e(SHOP_NAME) ?> admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,400..700&family=Rozha+One&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>">
</head>
<body>
<div class="admin">

    <aside class="rail">
        <a class="rail__mark" href="<?= e(url('admin/dashboard.php')) ?>">
            <?= e(SHOP_NAME) ?>
            <span>Shop admin</span>
        </a>
        <nav>
            <?php foreach ($links as $key => [$label, $href]): ?>
                <a href="<?= e(url($href)) ?>" <?= $nav === $key ? 'aria-current="page"' : '' ?>>
                    <?= e($label) ?>
                    <?php if ($key === 'notifications' && $unread > 0): ?>
                        <span class="bell__count" data-unread><?= $unread ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="rail__foot">
            <strong><?= e(admin_name()) ?></strong>
            <a href="<?= e(url('index.php')) ?>">View the shop</a> &middot;
            <a href="<?= e(url('admin/logout.php')) ?>">Sign out</a>
        </div>
    </aside>

    <div class="main">
        <div class="bar">
            <h1><?= e($page_title) ?></h1>
            <div class="bar__side">
                <a class="bell" href="<?= e(url('admin/notifications.php')) ?>">
                    Notifications
                    <span class="bell__count" data-unread <?= $unread > 0 ? '' : 'hidden' ?>><?= $unread ?></span>
                </a>
            </div>
        </div>

        <div class="page">
<?php foreach (take_flash() as $note): ?>
    <p class="flash flash--<?= e($note['tone']) ?>"><?= e($note['message']) ?></p>
<?php endforeach; ?>
