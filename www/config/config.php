<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    
    session_start();
}

// Database configuration
// Combell hosting settings
define('DB_HOST', 'ID467063_Mangatracker.db.webhosting.be');  // Combell database host
define('DB_NAME', 'ID467063_Mangatracker1');  // Combell database name
define('DB_USER', 'ID467063_Mangatracker1');  // Combell username
define('DB_PASS', 'Mangatracker1234');  // IMPORTANT: Replace with your Combell password before uploading!

// Uncomment these lines for local development
// define('DB_HOST', 'localhost');  // Usually 'localhost' for local development
// define('DB_NAME', 'manga_tracker');  // Your local database name
// define('DB_USER', 'root');  // Default XAMPP/WAMP username
// define('DB_PASS', '');  // Default XAMPP/WAMP password is often empty

try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die("DB connection failed: " . $e->getMessage());
}
