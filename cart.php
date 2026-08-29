<?php
/** Cart: change quantities, drop lines, see the running total. */
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    // One form drives the whole page, so a remove button just names itself.
    if (isset($_POST['remove'])) {
        $drop = (int) $_POST['remove'];
        if (isset($_SESSION['cart'][$drop])) {
            unset($_SESSION['cart'][$drop]);
            flash('Removed from your cart.');
        }
        redirect('cart.php');
    }

    $wanted = (array) ($_POST['quantity'] ?? []);
    if ($wanted) {
        $ids  = array_map('intval', array_keys($wanted));
        $in   = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, name, stock FROM products WHERE id IN ($in)");
        $stmt->execute($ids);
        $stock_of = [];
        $name_of  = [];
        foreach ($stmt->fetchAll() as $row) {
            $stock_of[$row['id']] = (int) $row['stock'];
            $name_of[$row['id']]  = $row['name'];
        }

        $trimmed = [];
        foreach ($wanted as $pid => $qty) {
            $pid = (int) $pid;
            $qty = (int) $qty;
            if (!isset($stock_of[$pid])) {
                unset($_SESSION['cart'][$pid]);
                continue;
            }
            if ($qty < 1) {
                unset($_SESSION['cart'][$pid]);
                continue;
            }
            if ($qty > $stock_of[$pid]) {
                $trimmed[] = $name_of[$pid] . ' (' . $stock_of[$pid] . ' in stock)';
                $qty = $stock_of[$pid];
            }
            $_SESSION['cart'][$pid] = $qty;
        }

        flash(
            $trimmed
                ? 'Cart updated. We could not give you as many as you asked for of: ' . implode(', ', $trimmed) . '.'
                : 'Cart updated.',
            $trimmed ? 'warn' : 'ok'
        );
    }
    redirect('cart.php');
}

$page_title = 'Your cart';
require __DIR__ . '/includes/header.php';

$lines = cart_lines($pdo);
$total = cart_total($lines);
?>

<div class="wall-head">
    <h1>Your cart</h1>
    <?php if ($lines): ?><p><?= cart_count() ?> item<?= cart_count() === 1 ? '' : 's' ?></p><?php endif; ?>
</div>

<?php if (!$lines): ?>
    <div class="empty">
        <h2>Your cart is empty</h2>
        <p>Nothing picked out yet. The shelf is one click away.</p>
        <a class="btn" href="<?= e(url('index.php')) ?>">Browse the shelf</a>
    </div>
<?php else: ?>

<form method="post" action="<?= e(url('cart.php')) ?>">
    <?= csrf_field() ?>
    <div class="cart-grid">
        <div class="lines">
            <?php foreach ($lines as $line):
                $img = product_image($line);
            ?>
                <div class="line">
                    <div class="line__thumb">
                        <?php if ($img): ?>
                            <img src="<?= e($img) ?>" alt="">
                        <?php else: ?>
                            <?= image_placeholder($line['name']) ?>
                        <?php endif; ?>
                    </div>

                    <div class="line__info">
                        <a class="line__name" href="<?= e(url('product.php?id=' . (int) $line['id'])) ?>"><?= e($line['name']) ?></a>
                        <div class="line__unit"><?= e(money((float) $line['price'])) ?> each &middot; <?= (int) $line['stock'] ?> in stock</div>
                    </div>

                    <div class="line__qty">
                        <div class="stepper" data-stepper>
                            <button type="button" data-step="-1" aria-label="One fewer <?= e($line['name']) ?>">&minus;</button>
                            <input type="number" name="quantity[<?= (int) $line['id'] ?>]"
                                   value="<?= (int) $line['quantity'] ?>" min="1" max="<?= (int) $line['stock'] ?>"
                                   step="1" inputmode="numeric"
                                   aria-label="Quantity of <?= e($line['name']) ?>">
                            <button type="button" data-step="1" aria-label="One more <?= e($line['name']) ?>">+</button>
                        </div>
                    </div>

                    <div class="line__total"><?= price_html((float) $line['line_total']) ?></div>

                    <button class="line__drop" type="submit" name="remove" value="<?= (int) $line['id'] ?>">Remove</button>
                </div>
            <?php endforeach; ?>
        </div>

        <aside class="summary">
            <h2>Total</h2>
            <div class="summary__row"><span>Items</span><span><?= cart_count() ?></span></div>
            <div class="summary__row"><span>Delivery</span><span>Free</span></div>
            <div class="summary__row summary__row--total">
                <span>You pay the rider</span>
                <?= price_html($total) ?>
            </div>
            <a class="btn btn--wide" href="<?= e(url('checkout.php')) ?>">Go to checkout</a>
            <button class="btn btn--quiet btn--wide" type="submit">Update quantities</button>
            <p class="summary__note">Cash on delivery. Nothing is charged until the parcel is in your hands.</p>
        </aside>
    </div>
</form>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
