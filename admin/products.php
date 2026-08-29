<?php
/** Product list, plus the delete action. */
require_once __DIR__ . '/includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $id   = (int) ($_POST['delete'] ?? 0);
    $stmt = $pdo->prepare('SELECT name, image FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product) {
        flash('That product was already deleted.', 'warn');
    } else {
        $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);

        // Past orders keep their own copy of the name and price, so history survives.
        $file = trim((string) $product['image']);
        if ($file !== '' && is_file(UPLOAD_DIR . '/' . $file)) {
            @unlink(UPLOAD_DIR . '/' . $file);
        }
        flash('Deleted ' . $product['name'] . '. Past orders still show it.');
    }
    redirect('admin/products.php');
}

$search = trim((string) ($_GET['q'] ?? ''));

if ($search !== '') {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE name LIKE ? OR category LIKE ? ORDER BY id DESC');
    $stmt->execute(['%' . $search . '%', '%' . $search . '%']);
} else {
    $stmt = $pdo->query('SELECT * FROM products ORDER BY id DESC');
}
$products = $stmt->fetchAll();

$total = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

$page_title = 'Products';
$nav        = 'products';
require __DIR__ . '/includes/header.php';
?>

<div class="toolbar">
    <form method="get" action="<?= e(url('admin/products.php')) ?>">
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Filter by name or category">
    </form>
    <?php if ($search !== ''): ?>
        <a class="btn btn--quiet btn--small" href="<?= e(url('admin/products.php')) ?>">Clear</a>
    <?php endif; ?>
    <span class="spacer"></span>
    <a class="btn" href="<?= e(url('admin/product-form.php')) ?>">Add a product</a>
</div>

<div class="box">
    <?php if (!$products && $total === 0): ?>
        <div class="empty">
            <h2>No products yet</h2>
            <p>Add your first one and it appears on the shop shelf straight away.</p>
            <a class="btn" href="<?= e(url('admin/product-form.php')) ?>">Add your first product</a>
        </div>

    <?php elseif (!$products): ?>
        <div class="empty">
            <h2>Nothing matches &ldquo;<?= e($search) ?>&rdquo;</h2>
            <p>No product name or category contains that. Try a shorter word.</p>
            <a class="btn btn--quiet" href="<?= e(url('admin/products.php')) ?>">Show all <?= $total ?></a>
        </div>

    <?php else: ?>
        <div class="tablewrap">
            <table class="grid">
                <thead>
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th>Category</th>
                        <th class="num">Price</th>
                        <th class="num">Stock</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $product):
                    $img   = product_image($product);
                    $stock = (int) $product['stock'];
                ?>
                    <tr>
                        <td>
                            <?php if ($img): ?>
                                <img class="grid__thumb" src="<?= e($img) ?>" alt="">
                            <?php else: ?>
                                <div class="grid__blank"><?= e(mb_strtoupper(mb_substr($product['name'], 0, 1))) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="rowlink" href="<?= e(url('admin/product-form.php?id=' . (int) $product['id'])) ?>">
                                <?= e($product['name']) ?>
                            </a>
                        </td>
                        <td><?= e($product['category']) ?></td>
                        <td class="num"><?= e(money((float) $product['price'])) ?></td>
                        <td class="num">
                            <?php if ($stock <= 0): ?>
                                <span class="pip pip--out">Sold out</span>
                            <?php elseif ($stock <= LOW_STOCK_AT): ?>
                                <span class="pip pip--low"><?= $stock ?> left</span>
                            <?php else: ?>
                                <?= $stock ?>
                            <?php endif; ?>
                        </td>
                        <td class="num">
                            <a class="btn btn--quiet btn--small"
                               href="<?= e(url('admin/product-form.php?id=' . (int) $product['id'])) ?>">Edit</a>
                            <form class="rowform" method="post" action="<?= e(url('admin/products.php')) ?>"
                                  onsubmit="return confirm('Delete <?= e(addslashes($product['name'])) ?>? This cannot be undone.');">
                                <?= csrf_field() ?>
                                <button class="btn btn--danger btn--small" type="submit"
                                        name="delete" value="<?= (int) $product['id'] ?>">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
