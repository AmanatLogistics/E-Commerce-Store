<?php
/** Order notifications. Opening one marks it read and jumps to the order. */
require_once __DIR__ . '/includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (isset($_POST['read_all'])) {
        $pdo->exec('UPDATE notifications SET is_read = 1 WHERE is_read = 0');
        flash('All notifications marked as read.');
        redirect('admin/notifications.php');
    }

    $open = (int) ($_POST['open'] ?? 0);
    $stmt = $pdo->prepare('SELECT order_id FROM notifications WHERE id = ?');
    $stmt->execute([$open]);
    $note = $stmt->fetch();

    if (!$note) {
        flash('That notification is gone.', 'warn');
        redirect('admin/notifications.php');
    }

    $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?')->execute([$open]);

    if ($note['order_id'] === null) {
        flash('The order behind that notification has been deleted.', 'warn');
        redirect('admin/notifications.php');
    }
    redirect('admin/order-view.php?id=' . (int) $note['order_id']);
}

$notes  = $pdo->query('SELECT * FROM notifications ORDER BY id DESC LIMIT 100')->fetchAll();
$unread = unread_notifications($pdo);

$page_title = 'Notifications';
$nav        = 'notifications';
require __DIR__ . '/includes/header.php';
?>

<div class="toolbar">
    <span class="lbl"><?= $unread ?> unread of <?= count($notes) ?> shown</span>
    <span class="spacer"></span>
    <?php if ($unread > 0): ?>
        <form method="post" action="<?= e(url('admin/notifications.php')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn--quiet btn--small" type="submit" name="read_all" value="1">Mark all as read</button>
        </form>
    <?php endif; ?>
</div>

<div class="box">
    <?php if (!$notes): ?>
        <div class="empty">
            <h2>Nothing to report</h2>
            <p>Every time a customer places an order, a line appears here and the badge in the header goes up.</p>
        </div>
    <?php else: ?>
        <div class="notes">
            <?php foreach ($notes as $note): ?>
                <form method="post" action="<?= e(url('admin/notifications.php')) ?>">
                    <?= csrf_field() ?>
                    <button class="note<?= (int) $note['is_read'] === 0 ? ' note--unread' : '' ?>"
                            type="submit" name="open" value="<?= (int) $note['id'] ?>">
                        <span class="note__msg">
                            <?php if ((int) $note['is_read'] === 0): ?><strong><?= e($note['message']) ?></strong>
                            <?php else: ?><?= e($note['message']) ?><?php endif; ?>
                        </span>
                        <span class="note__when"><?= e(date('j M, g:i a', strtotime($note['created_at']))) ?></span>
                    </button>
                </form>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
