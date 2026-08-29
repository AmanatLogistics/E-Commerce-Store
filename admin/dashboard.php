<?php
/** Dashboard: five figures and the five most recent orders. */
require_once __DIR__ . '/includes/auth.php';
require_admin();

$products   = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$orders     = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$pending    = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE status = "pending"')->fetchColumn();
$revenue    = (float) $pdo->query('SELECT COALESCE(SUM(total), 0) FROM orders WHERE status <> "cancelled"')->fetchColumn();

$low_stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE stock <= ?');
$low_stmt->execute([LOW_STOCK_AT]);
$low = (int) $low_stmt->fetchColumn();

$recent = $pdo->query(
    'SELECT id, customer_name, total, status, created_at FROM orders ORDER BY id DESC LIMIT 5'
)->fetchAll();

$page_title = 'Dashboard';
$nav        = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="stats">
    <div class="stat">
        <p class="lbl">Products</p>
        <p class="stat__n"><?= number_format($products) ?></p>
        <p class="stat__sub">on the shelf</p>
    </div>
    <div class="stat">
        <p class="lbl">Orders</p>
        <p class="stat__n"><?= number_format($orders) ?></p>
        <p class="stat__sub">all time</p>
    </div>
    <div class="stat stat--warn">
        <p class="lbl">Pending</p>
        <p class="stat__n"><?= number_format($pending) ?></p>
        <p class="stat__sub">waiting to be confirmed</p>
    </div>
    <div class="stat stat--accent">
        <p class="lbl">Revenue</p>
        <p class="stat__n"><?= number_format($revenue) ?></p>
        <p class="stat__sub">rupees, cancelled orders excluded</p>
    </div>
    <div class="stat stat--warn">
        <p class="lbl">Running low</p>
        <p class="stat__n"><?= number_format($low) ?></p>
        <p class="stat__sub"><?= LOW_STOCK_AT ?> or fewer in stock</p>
    </div>
</div>

<div class="box">
    <div class="box__head">
        <h2>Latest orders</h2>
        <a class="btn btn--quiet btn--small" href="<?= e(url('admin/orders.php')) ?>">See all orders</a>
    </div>

    <?php if (!$recent): ?>
        <div class="empty">
            <h2>No orders yet</h2>
            <p>When a customer checks out, the order lands here and you get a notification.</p>
            <a class="btn btn--quiet" href="<?= e(url('index.php')) ?>">Open the shop</a>
        </div>
    <?php else: ?>
        <div class="tablewrap">
            <table class="grid">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Placed</th>
                        <th>Status</th>
                        <th class="num">Total</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recent as $order): ?>
                    <tr>
                        <td>
                            <a class="rowlink" href="<?= e(url('admin/order-view.php?id=' . (int) $order['id'])) ?>">
                                <?= e(order_number((int) $order['id'])) ?>
                            </a>
                        </td>
                        <td><?= e($order['customer_name']) ?></td>
                        <td><?= e(date('j M Y, g:i a', strtotime($order['created_at']))) ?></td>
                        <td><span class="pip pip--<?= e($order['status']) ?>"><?= e($order['status']) ?></span></td>
                        <td class="num"><?= e(money((float) $order['total'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
