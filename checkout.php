<?php
/** Checkout — cash on delivery. Places the order and decrements stock. */
require_once __DIR__ . '/includes/functions.php';

$lines = cart_lines($pdo);
$total = cart_total($lines);

if (!$lines) {
    flash('There is nothing to check out yet.', 'warn');
    redirect('cart.php');
}

$values = ['name' => '', 'email' => '', 'phone' => '', 'address' => '', 'notes' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach ($values as $key => $_) {
        $values[$key] = trim((string) ($_POST[$key] ?? ''));
    }

    if ($values['name'] === '') {
        $errors['name'] = 'Tell us who the parcel is for.';
    } elseif (mb_strlen($values['name']) > 120) {
        $errors['name'] = 'That name is too long for our label — keep it under 120 characters.';
    }

    if ($values['email'] === '') {
        $errors['email'] = 'We send the order confirmation here.';
    } elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'That is not a working email address. Check for a missing @ or a typo in the domain.';
    }

    if ($values['phone'] === '') {
        $errors['phone'] = 'The rider needs a number to call.';
    } elseif (!preg_match('/^[0-9+\-\s()]{7,20}$/', $values['phone'])) {
        $errors['phone'] = 'Use digits only, like 0300 1234567.';
    }

    if (mb_strlen($values['address']) < 10) {
        $errors['address'] = 'Give us the full address — house, street, area and city.';
    }

    if (mb_strlen($values['notes']) > 500) {
        $errors['notes'] = 'Keep the notes under 500 characters.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Lock the product rows so two shoppers cannot buy the same last piece.
            $ids  = array_column($lines, 'id');
            $in   = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id IN ($in) FOR UPDATE");
            $stmt->execute($ids);
            $live = [];
            foreach ($stmt->fetchAll() as $row) {
                $live[$row['id']] = $row;
            }

            $short = [];
            $sum   = 0.0;
            foreach ($lines as $line) {
                $id  = (int) $line['id'];
                $qty = (int) $line['quantity'];
                if (!isset($live[$id]) || (int) $live[$id]['stock'] < $qty) {
                    $have    = isset($live[$id]) ? (int) $live[$id]['stock'] : 0;
                    $short[] = $line['name'] . ' (' . $have . ' left, you asked for ' . $qty . ')';
                    continue;
                }
                $sum += $qty * (float) $live[$id]['price'];
            }

            if ($short) {
                $pdo->rollBack();
                $errors['cart'] = 'Someone got there first. These are no longer available in the quantity you wanted: '
                    . implode('; ', $short) . '. Adjust your cart and try again.';
            } else {
                $order = $pdo->prepare(
                    'INSERT INTO orders (customer_name, email, phone, address, notes, total, status)
                     VALUES (?, ?, ?, ?, ?, ?, "pending")'
                );
                $order->execute([
                    $values['name'], $values['email'], $values['phone'],
                    $values['address'], $values['notes'], $sum,
                ]);
                $order_id = (int) $pdo->lastInsertId();

                $item = $pdo->prepare(
                    'INSERT INTO order_items (order_id, product_id, product_name, price, quantity)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $take = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');

                foreach ($lines as $line) {
                    $id  = (int) $line['id'];
                    $qty = (int) $line['quantity'];
                    $item->execute([$order_id, $id, $live[$id]['name'], $live[$id]['price'], $qty]);
                    $take->execute([$qty, $id]);
                }

                $note = $pdo->prepare('INSERT INTO notifications (order_id, message) VALUES (?, ?)');
                $note->execute([
                    $order_id,
                    'New order ' . order_number($order_id) . ' from ' . $values['name'] . ' — ' . money($sum),
                ]);

                $pdo->commit();

                $_SESSION['cart']          = [];
                $_SESSION['last_order_id'] = $order_id;
                redirect('order-success.php');
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors['cart'] = 'We could not save the order. Nothing was charged and your cart is untouched. '
                . 'Please try again in a moment.';
            error_log('Checkout failed: ' . $e->getMessage());
        }
    }
}

$page_title = 'Checkout';
require __DIR__ . '/includes/header.php';
?>

<div class="wall-head">
    <h1>Checkout</h1>
    <p>Cash on delivery &mdash; no card, no account</p>
</div>

<?php if (isset($errors['cart'])): ?>
    <p class="flash flash--error"><?= e($errors['cart']) ?></p>
<?php endif; ?>

<form method="post" action="<?= e(url('checkout.php')) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="checkout-grid">
        <div class="panel">
            <h2>Where it goes</h2>

            <div class="cod">
                <div>
                    <strong>You pay cash when it arrives</strong>
                    <span>Have <?= e(money($total)) ?> ready for the rider. We do not take cards or online payment.</span>
                </div>
            </div>

            <div class="pair">
                <div class="field<?= isset($errors['name']) ? ' field--bad' : '' ?>">
                    <label for="name">Full name</label>
                    <input type="text" id="name" name="name" value="<?= e($values['name']) ?>" autocomplete="name" required>
                    <?php if (isset($errors['name'])): ?><p class="field__error"><?= e($errors['name']) ?></p><?php endif; ?>
                </div>

                <div class="field<?= isset($errors['phone']) ? ' field--bad' : '' ?>">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" value="<?= e($values['phone']) ?>" autocomplete="tel" placeholder="0300 1234567" required>
                    <?php if (isset($errors['phone'])): ?><p class="field__error"><?= e($errors['phone']) ?></p><?php endif; ?>
                </div>
            </div>

            <div class="field<?= isset($errors['email']) ? ' field--bad' : '' ?>">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($values['email']) ?>" autocomplete="email" required>
                <?php if (isset($errors['email'])): ?><p class="field__error"><?= e($errors['email']) ?></p><?php endif; ?>
            </div>

            <div class="field<?= isset($errors['address']) ? ' field--bad' : '' ?>">
                <label for="address">Delivery address</label>
                <textarea id="address" name="address" autocomplete="street-address" required><?= e($values['address']) ?></textarea>
                <?php if (isset($errors['address'])): ?>
                    <p class="field__error"><?= e($errors['address']) ?></p>
                <?php else: ?>
                    <p class="field__hint">House and street, area, city. Add a landmark if the rider will need one.</p>
                <?php endif; ?>
            </div>

            <div class="field<?= isset($errors['notes']) ? ' field--bad' : '' ?>">
                <label for="notes">Order notes <span class="opt">(optional)</span></label>
                <textarea id="notes" name="notes" placeholder="Gift wrap, a delivery time that suits you, anything else."><?= e($values['notes']) ?></textarea>
                <?php if (isset($errors['notes'])): ?><p class="field__error"><?= e($errors['notes']) ?></p><?php endif; ?>
            </div>
        </div>

        <aside class="summary">
            <h2>Your order</h2>
            <div class="summary__mini">
                <?php foreach ($lines as $line): ?>
                    <div>
                        <span><?= (int) $line['quantity'] ?> &times; <?= e($line['name']) ?></span>
                        <span><?= e(money((float) $line['line_total'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="summary__row"><span>Delivery</span><span>Free</span></div>
            <div class="summary__row summary__row--total">
                <span>You pay the rider</span>
                <?= price_html($total) ?>
            </div>
            <button class="btn btn--wide" type="submit">Place order</button>
            <a class="btn btn--quiet btn--wide" href="<?= e(url('cart.php')) ?>">Back to cart</a>
            <p class="summary__note">Placing the order reserves your pieces straight away.</p>
        </aside>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
