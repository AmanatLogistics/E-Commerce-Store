<?php
/** Home / search / category listing — the tile wall. */
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Handmade homeware';
require __DIR__ . '/includes/header.php';

$where  = [];
$params = [];

if ($search_term !== '') {
    $where[]  = '(name LIKE ? OR description LIKE ?)';
    $params[] = '%' . $search_term . '%';
    $params[] = '%' . $search_term . '%';
}
if ($active_cat !== '') {
    $where[]  = 'category = ?';
    $params[] = $active_cat;
}

$sql = 'SELECT * FROM products'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$total_products = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

if ($search_term !== '') {
    $heading = 'Results for "' . $search_term . '"';
} elseif ($active_cat !== '') {
    $heading = $active_cat;
} else {
    $heading = 'On the shelf';
}
?>

<div class="wall-head">
    <h1><?= e($heading) ?></h1>
    <?php if ($products): ?>
        <p><?= count($products) ?> <?= count($products) === 1 ? 'piece' : 'pieces' ?><?php
            if ($search_term === '' && $active_cat === '') echo ', newest first';
        ?></p>
    <?php endif; ?>
</div>

<?php if ($products): ?>
    <div class="wall">
        <?php foreach ($products as $product):
            $stock = (int) $product['stock'];
            $img   = product_image($product);
        ?>
            <a class="tile" href="<?= e(url('product.php?id=' . (int) $product['id'])) ?>">
                <div class="thumb">
                    <?php if ($img): ?>
                        <img src="<?= e($img) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                    <?php else: ?>
                        <?= image_placeholder($product['name']) ?>
                    <?php endif; ?>
                </div>
                <div class="tile__body">
                    <p class="eyebrow"><?= e($product['category']) ?></p>
                    <h2 class="tile__name"><?= e($product['name']) ?></h2>
                    <div class="tile__foot">
                        <?= price_html((float) $product['price']) ?>
                        <span class="tile__stock<?= $stock <= LOW_STOCK_AT ? ' tile__stock--low' : '' ?>"><?php
                            if ($stock <= 0)                 echo 'Sold out';
                            elseif ($stock <= LOW_STOCK_AT)  echo 'Only ' . $stock . ' left';
                            else                             echo 'In stock';
                        ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

<?php elseif ($total_products === 0): ?>
    <div class="empty">
        <h2>The shelf is empty</h2>
        <p>Nothing has been put out for sale yet. Check back in a day or two.</p>
    </div>

<?php else: ?>
    <div class="empty">
        <h2>Nothing matches that</h2>
        <p>
            <?php if ($search_term !== ''): ?>
                No piece is called &ldquo;<?= e($search_term) ?>&rdquo;, and none mentions it
                in its description. Try a shorter word, or browse a category.
            <?php else: ?>
                There is nothing in <?= e($active_cat) ?> right now.
            <?php endif; ?>
        </p>
        <a class="btn btn--quiet" href="<?= e(url('index.php')) ?>">Show everything</a>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
