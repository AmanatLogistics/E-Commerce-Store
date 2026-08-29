<?php
/**
 * Database connection.
 * Edit the four constants below to match your MySQL setup, then you are done.
 */

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'meenakar');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP default is an empty password

// Shop identity — change these two lines to rename the store.
define('SHOP_NAME', 'Meenakar');
define('SHOP_TAGLINE', 'Handmade in Multan, Chiniot and Bhera');

// Anything at or below this stock count is flagged low in the admin.
define('LOW_STOCK_AT', 5);

// Image uploads
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_MAX_BYTES', 2 * 1024 * 1024);            // 2 MB
define('UPLOAD_ALLOWED', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit(
        'Cannot reach the database "' . DB_NAME . '" on ' . DB_HOST . '. '
        . 'Check that MySQL is running and that the credentials in config/db.php are right. '
        . 'MySQL said: ' . $e->getMessage()
    );
}
