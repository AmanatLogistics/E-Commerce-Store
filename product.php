<?php
/** Product detail, and the one place items enter the cart. */
require_once __DIR__ . '/includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $page_title = 'Not found';
    require __DIR__ . '/includes/header.php';
    echo '<div class="empty"><h2>That piece is gone</h2>'
       . '<p>It was either sold and removed, or the link is wrong.</p>'
       . '<a class="btn btn--quiet" href="' . e(url('index.php')) . '">Back to the shelf</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$stock = (int) $product['stock'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $wanted   = max(1, (int) ($_POST['quantity'] ?? 1));
    $in_cart  = (int) (cart()[$id] ?? 0);

    if ($stock <= 0) {
        flash($product['name'] . ' is sold out. We will restock it soon.', 'warn');
    } elseif ($in_cart + $wanted > $stock) {
        $room = $stock - $in_cart;
        if ($room <= 0) {
            $message = 'Your cart already holds all ' . $stock . ' we have of ' . $product['name'] . '.';
        } elseif ($in_cart === 0) {
            $message = 'Only ' . $stock . ' of ' . $product['name'] . ' are in stock, so we added all ' . $stock . '.';
        } else {
            $message = 'Only ' . $stock . ' of ' . $product['name'] . ' are in stock and you already have '
                . $in_cart . ' in your cart, so we added the remaining ' . $room . '.';
        }
        flash($message, 'warn');
        if ($room > 0) {
            $_SESSION['cart'][$id] = $stock;
        }
    } else {
        $_SESSION['cart'][$id] = $in_cart + $wanted;
        flash($wanted . ' × ' . $product['name'] . ' added to your cart.');
    }
    redirect('cart.php');
}

$page_title = $product['name'];
require __DIR__ . '/includes/header.php';

$img = product_image($product);
?>

<p class="crumbs">
    <a href="<?= e(url('index.php')) ?>">Shelf</a> &nbsp;/&nbsp;
    <a href="<?= e(url('index.php?category=' . urlencode($product['category']))) ?>"><?= e($product['category']) ?></a>
</p>

<article class="product">
    <div class="product__figure">
        <?php if ($img): ?>
            <img src="<?= e($img) ?>" alt="<?= e($product['name']) ?>">
        <?php else: ?>
            <?= image_placeholder($product['name']) ?>
        <?php endif; ?>
    </div>

    <div class="chit">
        <p class="eyebrow"><?= e($product['category']) ?></p>
        <h1><?= e($product['name']) ?></h1>
        <?= price_html((float) $product['price']) ?>

        <p class="stockline stockline--<?= $stock <= 0 ? 'out' : ($stock <= LOW_STOCK_AT ? 'low' : 'in') ?>">
            <?php
            if ($stock <= 0)                echo 'Sold out';
            elseif ($stock <= LOW_STOCK_AT) echo 'Only ' . $stock . ' left';
            else                            echo $stock . ' in stock';
            ?>
        </p>

        <form method="post" action="<?= e(url('product.php?id=' . (int) $product['id'])) ?>">
            <?= csrf_field() ?>
            <?php if ($stock > 0): ?>
                <div class="field">
                    <label for="quantity">How many</label>
                    <div class="stepper" data-stepper>
                        <button type="button" data-step="-1" aria-label="One fewer">&minus;</button>
                        <input type="number" id="quantity" name="quantity" value="1"
                               min="1" max="<?= $stock ?>" step="1" inputmode="numeric">
                        <button type="button" data-step="1" aria-label="One more">+</button>
                    </div>
                </div>
                <button class="btn btn--wide" type="submit">Add to cart</button>
            <?php else: ?>
                <button class="btn btn--wide" type="button" disabled>Sold out</button>
                <p class="field__hint">Call 042-3577-1180 and we will tell you when the next batch is fired.</p>
            <?php endif; ?>
        </form>

        <div class="chit__meta">
            <span>Cash on delivery &mdash; pay the rider, not us.</span>
            <span>Dispatched from Lahore within 2 working days.</span>
        </div>
    </div>
</article>

<section class="description">
    <h2>About this piece</h2>
    <p><?= e($product['description']) ?></p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
