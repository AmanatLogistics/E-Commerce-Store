<?php
/** Every order, newest first, filterable by status. */
require_once __DIR__ . '/includes/auth.php';
require_admin();

$filter = (string) ($_GET['status'] ?? '');
if ($filter !== '' && !in_array($filter, order_statuses(), true)) {
    $filter = '';
}

$sql = 'SELECT o.*, (SELECT COALESCE(SUM(quantity), 0) FROM order_items WHERE order_id = o.id) AS item_count
        FROM orders o';
if ($filter !== '') {
    $stmt = $pdo->prepare($sql . ' WHERE o.status = ? ORDER BY o.id DESC');
    $stmt->execute([$filter]);
} else {
    $stmt = $pdo->query($sql . ' ORDER BY o.id DESC');
}
$orders = $stmt->fetchAll();

$total = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();

$page_title = 'Orders';
$nav        = 'orders';
require __DIR__ . '/includes/header.php';
?>

<div class="toolbar">
    <form method="get" action="<?= e(url('admin/orders.php')) ?>">
        <select name="status" onchange="this.form.submit()" aria-label="Filter by status">
            <option value="">All statuses</option>
            <?php foreach (order_statuses() as $status): ?>
                <option value="<?= e($status) ?>" <?= $filter === $status ? 'selected' : '' ?>>
                    <?= e(ucfirst($status)) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <noscript><button class="btn btn--quiet btn--small" type="submit">Filter</button></noscript>
    </form>
    <span class="spacer"></span>
    <span class="lbl"><?= count($orders) ?> of <?= $total ?> orders</span>
</div>

<div class="box">
    <?php if (!$orders && $total === 0): ?>
        <div class="empty">
            <h2>No orders yet</h2>
            <p>Orders show up here the moment a customer checks out.</p>
        </div>

    <?php elseif (!$orders): ?>
        <div class="empty">
            <h2>Nothing is <?= e($filter) ?></h2>
            <p>No order currently has that status.</p>
            <a class="btn btn--quiet" href="<?= e(url('admin/orders.php')) ?>">Show all orders</a>
        </div>

    <?php else: ?>
        <div class="tablewrap">
            <table class="grid">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Placed</th>
                        <th class="num">Items</th>
                        <th class="num">Total</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <a class="rowlink" href="<?= e(url('admin/order-view.php?id=' . (int) $order['id'])) ?>">
                                <?= e(order_number((int) $order['id'])) ?>
                            </a>
                        </td>
                        <td><?= e($order['customer_name']) ?></td>
                        <td><?= e($order['phone']) ?></td>
                        <td><?= e(date('j M Y, g:i a', strtotime($order['created_at']))) ?></td>
                        <td class="num"><?= (int) $order['item_count'] ?></td>
                        <td class="num"><?= e(money((float) $order['total'])) ?></td>
                        <td><span class="pip pip--<?= e($order['status']) ?>"><?= e($order['status']) ?></span></td>
                        <td class="num">
                            <a class="btn btn--quiet btn--small"
                               href="<?= e(url('admin/order-view.php?id=' . (int) $order['id'])) ?>">Open</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
