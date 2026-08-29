<?php
/** Sign out: drop the admin keys and hand out a fresh session id. */
require_once __DIR__ . '/includes/auth.php';

unset($_SESSION['admin_id'], $_SESSION['admin_user']);
session_regenerate_id(true);

flash('Signed out.');
redirect('admin/login.php');
