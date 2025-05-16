<?php
session_start();

// Database configuration
// Use local settings for development
define('DB_HOST', 'localhost');  // Usually 'localhost' for local development
define('DB_NAME', 'manga_tracker');  // Your local database name
define('DB_USER', 'root');  // Default XAMPP/WAMP username
define('DB_PASS', '');  // Default XAMPP/WAMP password is often empty

try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die("DB connection failed: " . $e->getMessage());
}
