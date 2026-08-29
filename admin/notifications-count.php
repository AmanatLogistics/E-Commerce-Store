<?php
/** Tiny JSON endpoint the admin header polls every 30 seconds. */
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (admin_id() === null) {
    http_response_code(401);
    echo json_encode(['error' => 'not signed in']);
    exit;
}

echo json_encode(['unread' => unread_notifications($pdo)]);
