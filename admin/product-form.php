<?php
/** Add or edit a product. Same form both ways; ?id=N switches it to edit. */
require_once __DIR__ . '/includes/auth.php';
require_admin();

$id        = (int) ($_GET['id'] ?? 0);
$editing   = false;
$existing  = null;

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetch();

    if (!$existing) {
        flash('That product no longer exists.', 'warn');
        redirect('admin/products.php');
    }
    $editing = true;
}

$values = [
    'name'        => $existing['name']        ?? '',
    'price'       => $existing['price']       ?? '',
    'stock'       => $existing['stock']       ?? '',
    'category'    => $existing['category']    ?? '',
    'description' => $existing['description'] ?? '',
];
$errors = [];

/**
 * Move an uploaded photo into /uploads and hand back the stored filename.
 * Returns null when no file was chosen; writes into $errors when it is bad.
 */
function store_upload(array $file, array &$errors): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        $errors['image'] = 'That file is larger than the server allows. Pick one under '
            . (int) (UPLOAD_MAX_BYTES / 1024 / 1024) . ' MB.';
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors['image'] = 'The upload did not finish. Try choosing the file again.';
        return null;
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        $errors['image'] = 'That image is ' . round($file['size'] / 1024 / 1024, 1) . ' MB. The limit is '
            . (int) (UPLOAD_MAX_BYTES / 1024 / 1024) . ' MB.';
        return null;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_ALLOWED, true)) {
        $errors['image'] = 'A .' . $ext . ' file will not do. Use ' . implode(', ', UPLOAD_ALLOWED) . '.';
        return null;
    }

    // The extension is only a claim; check the bytes are really an image.
    if (@getimagesize($file['tmp_name']) === false) {
        $errors['image'] = 'That file is not a real image, whatever it is named.';
        return null;
    }

    if (!is_dir(UPLOAD_DIR) && !@mkdir(UPLOAD_DIR, 0755, true)) {
        $errors['image'] = 'The uploads folder is missing and could not be created. Create /uploads and make it writable.';
        return null;
    }
    if (!is_writable(UPLOAD_DIR)) {
        $errors['image'] = 'The uploads folder is not writable. Give the web server write permission on /uploads.';
        return null;
    }

    $stored = bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $stored)) {
        $errors['image'] = 'The image could not be saved to /uploads. Check the folder permissions.';
        return null;
    }
    return $stored;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach ($values as $key => $_) {
        $values[$key] = trim((string) ($_POST[$key] ?? ''));
    }

    if ($values['name'] === '') {
        $errors['name'] = 'Give the product a name.';
    } elseif (mb_strlen($values['name']) > 150) {
        $errors['name'] = 'Names have to stay under 150 characters.';
    }

    if ($values['price'] === '' || !is_numeric($values['price']) || (float) $values['price'] < 0) {
        $errors['price'] = 'Enter the price in rupees, digits only, like 4500.';
    }

    if ($values['stock'] === '' || !ctype_digit($values['stock'])) {
        $errors['stock'] = 'Enter how many you have, as a whole number.';
    }

    if ($values['category'] === '') {
        $errors['category'] = 'Pick a category so it shows up in the shop menu.';
    }

    if ($values['description'] === '') {
        $errors['description'] = 'Write a line or two — this is what the customer reads.';
    }

    $new_image = store_upload($_FILES['image'] ?? [], $errors);

    if (!$errors) {
        if ($editing) {
            $image = $new_image ?? $existing['image'];

            $pdo->prepare(
                'UPDATE products SET name = ?, price = ?, stock = ?, category = ?, description = ?, image = ?
                 WHERE id = ?'
            )->execute([
                $values['name'], (float) $values['price'], (int) $values['stock'],
                $values['category'], $values['description'], $image, $id,
            ]);

            // Only bin the old photo once the row points at the new one.
            $old = trim((string) $existing['image']);
            if ($new_image !== null && $old !== '' && $old !== $new_image && is_file(UPLOAD_DIR . '/' . $old)) {
                @unlink(UPLOAD_DIR . '/' . $old);
            }
            flash('Saved ' . $values['name'] . '.');
        } else {
            $pdo->prepare(
                'INSERT INTO products (name, price, stock, category, description, image)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                $values['name'], (float) $values['price'], (int) $values['stock'],
                $values['category'], $values['description'], $new_image,
            ]);
            flash($values['name'] . ' is on the shelf.');
        }
        redirect('admin/products.php');
    }
}

$categories = $pdo->query('SELECT DISTINCT category FROM products WHERE category <> "" ORDER BY category')
                  ->fetchAll(PDO::FETCH_COLUMN);

$page_title = $editing ? 'Edit ' . $existing['name'] : 'Add a product';
$nav        = 'products';
require __DIR__ . '/includes/header.php';

$current_image = $editing ? product_image($existing) : null;
?>

<form method="post" enctype="multipart/form-data"
      action="<?= e(url('admin/product-form.php' . ($editing ? '?id=' . $id : ''))) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="split">

        <div class="box">
            <div class="box__head"><h2>Details</h2></div>
            <div class="box__body">
                <div class="field<?= isset($errors['name']) ? ' field--bad' : '' ?>">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="<?= e($values['name']) ?>" autofocus>
                    <?php if (isset($errors['name'])): ?><p class="field__error"><?= e($errors['name']) ?></p><?php endif; ?>
                </div>

                <div class="pair">
                    <div class="field<?= isset($errors['price']) ? ' field--bad' : '' ?>">
                        <label for="price">Price in rupees</label>
                        <input type="number" id="price" name="price" value="<?= e((string) $values['price']) ?>"
                               min="0" step="1" inputmode="numeric">
                        <?php if (isset($errors['price'])): ?><p class="field__error"><?= e($errors['price']) ?></p><?php endif; ?>
                    </div>

                    <div class="field<?= isset($errors['stock']) ? ' field--bad' : '' ?>">
                        <label for="stock">Stock quantity</label>
                        <input type="number" id="stock" name="stock" value="<?= e((string) $values['stock']) ?>"
                               min="0" step="1" inputmode="numeric">
                        <?php if (isset($errors['stock'])): ?>
                            <p class="field__error"><?= e($errors['stock']) ?></p>
                        <?php else: ?>
                            <p class="field__hint">Drops to <?= LOW_STOCK_AT ?> or below and it is flagged as running low.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="field<?= isset($errors['category']) ? ' field--bad' : '' ?>">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" value="<?= e($values['category']) ?>" list="known-categories">
                    <datalist id="known-categories">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                    <?php if (isset($errors['category'])): ?>
                        <p class="field__error"><?= e($errors['category']) ?></p>
                    <?php else: ?>
                        <p class="field__hint">Type a new one, or reuse an existing category from the list.</p>
                    <?php endif; ?>
                </div>

                <div class="field<?= isset($errors['description']) ? ' field--bad' : '' ?>">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?= e($values['description']) ?></textarea>
                    <?php if (isset($errors['description'])): ?><p class="field__error"><?= e($errors['description']) ?></p><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="box">
            <div class="box__head"><h2>Photo</h2></div>
            <div class="box__body">
                <?php if ($current_image): ?>
                    <img src="<?= e($current_image) ?>" alt="Current photo of <?= e($values['name']) ?>"
                         class="preview">
                <?php else: ?>
                    <div class="empty empty--tight">
                        <p>No photo yet. The shop shows the product&rsquo;s first letter instead.</p>
                    </div>
                <?php endif; ?>

                <div class="field<?= isset($errors['image']) ? ' field--bad' : '' ?> field--spaced">
                    <label for="image"><?= $current_image ? 'Replace the photo' : 'Upload a photo' ?></label>
                    <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.gif,.webp">
                    <?php if (isset($errors['image'])): ?>
                        <p class="field__error"><?= e($errors['image']) ?></p>
                    <?php else: ?>
                        <p class="field__hint">
                            <?= e(strtoupper(implode(', ', UPLOAD_ALLOWED))) ?>, up to
                            <?= (int) (UPLOAD_MAX_BYTES / 1024 / 1024) ?> MB. Square photos sit best in the grid.
                        </p>
                    <?php endif; ?>
                </div>

                <button class="btn btn--wide" type="submit"><?= $editing ? 'Save changes' : 'Add to the shelf' ?></button>
                <a class="btn btn--quiet btn--wide" href="<?= e(url('admin/products.php')) ?>">Cancel</a>
            </div>
        </div>
    </div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
