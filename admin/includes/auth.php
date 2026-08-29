<?php
/** Session guard. Every admin page requires this file first. */
require_once __DIR__ . '/../../includes/functions.php';

function admin_id(): ?int
{
    return isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
}

function admin_name(): string
{
    return (string) ($_SESSION['admin_user'] ?? '');
}

/** Bounce anyone who is not signed in back to the login page. */
function require_admin(): void
{
    if (admin_id() === null) {
        flash('Sign in to open the admin panel.', 'warn');
        redirect('admin/login.php');
    }
}

function unread_notifications(PDO $pdo): int
{
    return (int) $pdo->query('SELECT COUNT(*) FROM notifications WHERE is_read = 0')->fetchColumn();
}
