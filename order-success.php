<?php
/** Confirmation. Reads the order id the checkout left in the session. */
require_once __DIR__ . '/includes/functions.php';

$order_id = (int) ($_SESSION['last_order_id'] ?? 0);
if (!$order_id) {
    flash('There is no recent order to show.', 'warn');
    redirect('index.php');
}

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    unset($_SESSION['last_order_id']);
    flash('That order could not be found.', 'warn');
    redirect('index.php');
}

$stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

$page_title = 'Order ' . order_number($order_id);
require __DIR__ . '/includes/header.php';
?>

<div class="receipt">
    <p class="eyebrow">Order placed</p>
    <div class="receipt__number"><?= e(order_number($order_id)) ?></div>
    <h1>Thank you, <?= e($order['customer_name']) ?></h1>
    <p>
        We are packing it now. The rider will call
        <?= e($order['phone']) ?> before setting off, and you pay
        <?= e(money((float) $order['total'])) ?> in cash at the door.
    </p>
    <a class="btn" href="<?= e(url('index.php')) ?>">Keep shopping</a>

    <div class="receipt__lines">
        <?php foreach ($items as $item): ?>
            <div>
                <span><?= (int) $item['quantity'] ?> &times; <?= e($item['product_name']) ?></span>
                <span><?= e(money((float) $item['price'] * (int) $item['quantity'])) ?></span>
            </div>
        <?php endforeach; ?>
        <div>
            <span>Total, cash on delivery</span>
            <span><?= e(money((float) $order['total'])) ?></span>
        </div>
    </div>
</div>

<p class="crumbs crumbs--center">
    Delivering to <?= e($order['address']) ?>
</p>

<?php require __DIR__ . '/includes/footer.php'; ?>
