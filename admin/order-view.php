<?php
/** One order in full, and the control that moves it along. */
require_once __DIR__ . '/includes/auth.php';
require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    flash('That order does not exist.', 'warn');
    redirect('admin/orders.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $status = (string) ($_POST['status'] ?? '');
    if (!in_array($status, order_statuses(), true)) {
        flash('That is not a status we use.', 'error');
    } elseif ($status === $order['status']) {
        flash('Order ' . order_number($id) . ' was already ' . $status . '.', 'warn');
    } else {
        $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $id]);
        flash('Order ' . order_number($id) . ' is now ' . $status . '.');
    }
    redirect('admin/order-view.php?id=' . $id);
}

$items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
$items->execute([$id]);
$items = $items->fetchAll();

$page_title = 'Order ' . order_number($id);
$nav        = 'orders';
require __DIR__ . '/includes/header.php';
?>

<div class="toolbar">
    <a class="btn btn--quiet btn--small" href="<?= e(url('admin/orders.php')) ?>">&larr; All orders</a>
    <span class="spacer"></span>
    <span class="pip pip--<?= e($order['status']) ?>"><?= e($order['status']) ?></span>
</div>

<div class="split">
    <div class="box">
        <div class="box__head">
            <h2>What was ordered</h2>
            <span class="lbl"><?= e(date('j M Y, g:i a', strtotime($order['created_at']))) ?></span>
        </div>
        <div class="tablewrap">
            <table class="grid">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="num">Unit price</th>
                        <th class="num">Qty</th>
                        <th class="num">Line total</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?php if ($item['product_id'] !== null): ?>
                                <a class="rowlink" href="<?= e(url('product.php?id=' . (int) $item['product_id'])) ?>">
                                    <?= e($item['product_name']) ?>
                                </a>
                            <?php else: ?>
                                <?= e($item['product_name']) ?>
                                <span class="lbl">(deleted from the shop)</span>
                            <?php endif; ?>
                        </td>
                        <td class="num"><?= e(money((float) $item['price'])) ?></td>
                        <td class="num"><?= (int) $item['quantity'] ?></td>
                        <td class="num"><?= e(money((float) $item['price'] * (int) $item['quantity'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="3" class="num">Grand total, cash on delivery</td>
                        <td class="num"><?= e(money((float) $order['total'])) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="box">
            <div class="box__head"><h2>Customer</h2></div>
            <div class="box__body">
                <dl class="dl">
                    <div>
                        <dt>Name</dt>
                        <dd><?= e($order['customer_name']) ?></dd>
                    </div>
                    <div>
                        <dt>Phone</dt>
                        <dd><a href="tel:<?= e(preg_replace('/\s+/', '', $order['phone'])) ?>"><?= e($order['phone']) ?></a></dd>
                    </div>
                    <div>
                        <dt>Email</dt>
                        <dd><a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a></dd>
                    </div>
                    <div>
                        <dt>Delivery address</dt>
                        <dd><?= e($order['address']) ?></dd>
                    </div>
                    <?php if (trim((string) $order['notes']) !== ''): ?>
                        <div>
                            <dt>Order notes</dt>
                            <dd><?= e($order['notes']) ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <div class="box">
            <div class="box__head"><h2>Status</h2></div>
            <div class="box__body">
                <form method="post" action="<?= e(url('admin/order-view.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div class="field">
                        <label for="status">Move this order to</label>
                        <select id="status" name="status">
                            <?php foreach (order_statuses() as $status): ?>
                                <option value="<?= e($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>>
                                    <?= e(ucfirst($status)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="field__hint">Pending &rarr; confirmed &rarr; shipped &rarr; delivered. Cancel at any point.</p>
                    </div>
                    <button class="btn btn--wide" type="submit">Update status</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
