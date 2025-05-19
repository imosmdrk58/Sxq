<?php
// This script ensures the database is set up correctly for admin features
require_once __DIR__.'/../config/config.php';

echo '<h1>Admin Setup</h1>';

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Check if is_admin column exists in users table
    $checkAdminCol = $pdo->query("
        SELECT COUNT(*) 
        FROM information_schema.COLUMNS 
        WHERE TABLE_NAME = 'users' 
        AND COLUMN_NAME = 'is_admin'
        AND TABLE_SCHEMA = DATABASE()
    ");
    
    if ($checkAdminCol->fetchColumn() == 0) {
        echo "<p>Adding is_admin column to users table...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0");
        echo "<p>✅ is_admin column added successfully.</p>";
    } else {
        echo "<p>✅ is_admin column already exists in users table.</p>";
    }
    
    // Check if bookmark_id column exists in comments table
    $checkBookmarkCol = $pdo->query("
        SELECT COUNT(*) 
        FROM information_schema.COLUMNS
        WHERE TABLE_NAME = 'comments'
        AND COLUMN_NAME = 'bookmark_id' 
        AND TABLE_SCHEMA = DATABASE()
    ");
    
    if ($checkBookmarkCol->fetchColumn() == 0) {
        echo "<p>Adding bookmark_id column to comments table...</p>";
        $pdo->exec("ALTER TABLE comments ADD COLUMN bookmark_id INT(11) NULL");
        echo "<p>✅ bookmark_id column added successfully.</p>";
    } else {
        echo "<p>✅ bookmark_id column already exists in comments table.</p>";
    }
    
    // Set up an admin user (the first user in the system)
    $setupAdmin = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = 1 LIMIT 1");
    $setupAdmin->execute();
    echo "<p>✅ Admin privileges added to user ID 1.</p>";
    
    // Get all admin users
    $adminUsers = $pdo->query("SELECT id, username FROM users WHERE is_admin = 1");
    if ($adminUsers->rowCount() > 0) {
        echo "<p>Current admin users:</p>";
        echo "<ul>";
        while ($admin = $adminUsers->fetch()) {
            echo "<li>ID {$admin['id']}: {$admin['username']}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>⚠️ No admin users found.</p>";
    }
    
    // Commit the transaction
    $pdo->commit();
    
    echo "<p>✅ Admin setup completed successfully.</p>";
    echo "<p><a href='../index.php'>Return to homepage</a> | <a href='index.php'>Go to Admin Panel</a></p>";
} catch (PDOException $e) {
    // Rollback the transaction in case of error
    $pdo->rollBack();
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
