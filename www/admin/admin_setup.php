<?php
// This script runs the admin setup SQL to add is_admin column to users table
require_once __DIR__.'/../config/config.php';

// Check if user is logged in (security measure)
if (empty($_SESSION['user_id'])) {
    die("You need to be logged in to run this setup.");
}

// Check if there's already an is_admin column
try {
    $checkAdminColumnStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM information_schema.COLUMNS 
        WHERE TABLE_NAME = 'users' 
        AND COLUMN_NAME = 'is_admin'
        AND TABLE_SCHEMA = DATABASE()
    ");
    $checkAdminColumnStmt->execute();
    $adminColumnExists = $checkAdminColumnStmt->fetchColumn();
    
    if ($adminColumnExists) {
        echo "<p>The is_admin column already exists in the users table.</p>";
    } else {
        // Read and execute the SQL file
        $sql = file_get_contents(__DIR__.'/../config/admin_setup.sql');
        $pdo->exec($sql);
        echo "<p>Successfully added is_admin column to users table and set admin user.</p>";
    }
    
    // Show all admin users
    $adminUsersStmt = $pdo->query("SELECT id, username FROM users WHERE is_admin = 1");
    if ($adminUsers = $adminUsersStmt->fetchAll()) {
        echo "<h3>Current Admin Users:</h3>";
        echo "<ul>";
        foreach ($adminUsers as $admin) {
            echo "<li>" . htmlspecialchars($admin['username']) . " (ID: " . $admin['id'] . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No admin users found. Please set at least one admin.</p>";
    }
    
    echo "<p><a href='../index.php'>Return to Home</a> | <a href='index.php'>Go to Admin Panel</a></p>";
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
