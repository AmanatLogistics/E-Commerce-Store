<?php
/** Admin sign-in. The only admin page that does not require a session. */
require_once __DIR__ . '/includes/auth.php';

if (admin_id() !== null) {
    redirect('admin/dashboard.php');
}

$username = '';
$error    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Fill in both the username and the password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM admins WHERE username = ?');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = (int) $admin['id'];
            $_SESSION['admin_user'] = $admin['username'];
            redirect('admin/dashboard.php');
        }

        // Deliberately vague: saying which half was wrong helps guessers.
        $error = 'That username and password do not match an account.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in &middot; <?= e(SHOP_NAME) ?> admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,400..700&family=Rozha+One&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>">
</head>
<body>
<main class="login">
    <div class="login__box">
        <div class="login__head">
            <p class="mark"><?= e(SHOP_NAME) ?></p>
            <p>Shop admin. Staff only.</p>
        </div>
        <div class="login__body">
            <?php foreach (take_flash() as $note): ?>
                <p class="flash flash--<?= e($note['tone']) ?>"><?= e($note['message']) ?></p>
            <?php endforeach; ?>

            <?php if ($error !== ''): ?>
                <p class="flash flash--error"><?= e($error) ?></p>
            <?php endif; ?>

            <form method="post" action="<?= e(url('admin/login.php')) ?>">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= e($username) ?>"
                           autocomplete="username" autofocus required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                </div>
                <button class="btn btn--wide" type="submit">Sign in</button>
            </form>

            <a class="login__back" href="<?= e(url('index.php')) ?>">Back to the shop</a>
        </div>
    </div>
</main>
</body>
</html>
