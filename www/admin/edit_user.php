<?php
require_once __DIR__.'/../config/config.php';

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
  header('Location: ../login.php');
  exit;
}

// Check if user is an admin
$stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['is_admin'] != 1) {
  // Not an admin, redirect to homepage
  header('Location: ../index.php');
  exit;
}

// Check if we have a user ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header('Location: index.php?tab=users');
  exit;
}

$userId = (int)$_GET['id'];

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$editUser = $stmt->fetch();

if (!$editUser) {
  header('Location: index.php?tab=users');
  exit;
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $email = trim($_POST['email']);
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
    
    // Extra validation for self-editing
    if ($userId === (int)$_SESSION['user_id'] && $isAdmin === 0) {
      $error = "You cannot remove admin privileges from yourself!";
    } else {
      try {
        // Update user
        $stmt = $pdo->prepare("UPDATE users SET email = ?, is_admin = ? WHERE id = ?");
        $stmt->execute([$email, $isAdmin, $userId]);
        
        // Handle password change if provided
        if (!empty($_POST['password'])) {
          $passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);
          $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
          $stmt->execute([$passwordHash, $userId]);
        }
        
        $message = "User updated successfully!";
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $editUser = $stmt->fetch();
        
      } catch (PDOException $e) {
        $error = "Error updating user: " . $e->getMessage();
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit User - Admin Panel</title>  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/style-fixes.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <style>
    .admin-container {
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
    }
    
    .admin-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
    }
    
    .form-control {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 16px;
    }
      .form-check {
      margin-top: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 30px;
    }
    
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 4px;
    }
    
    .alert-success {
      background-color: #d4edda;
      color: #155724;
    }
    
    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
    }
    
    .user-stats {
      background: #f9f9f9;
      border-radius: 4px;
      padding: 15px;
      margin-bottom: 20px;
    }
    
    .user-stats-title {
      font-weight: bold;
      margin-bottom: 10px;
    }
    
    .user-stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      gap: 15px;
    }
    
    .user-stat-item {
      text-align: center;
    }
    
    .user-stat-item .number {
      font-size: 1.5rem;
      font-weight: bold;
      color: var(--primary);
    }
    
    .user-stat-item .label {
      font-size: 0.9rem;
      color: #666;
    }
  </style>
</head>

<body>
  <?php include __DIR__.'/../header.php'; ?>
  
  <div class="admin-container">
    <div class="admin-header">
      <h1><i class="fas fa-user-edit"></i> Edit User</h1>
      <a href="index.php?tab=users" class="btn"><i class="fas fa-arrow-left"></i> Back to Users</a>
    </div>
    
    <?php if ($message): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= $message ?>
      </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
      <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
      </div>
    <?php endif; ?>
    
    <div class="user-stats">
      <div class="user-stats-title">User Statistics</div>      <div class="user-stats-grid">
        <?php
        // Get user stats
        $bookmarkStmt = $pdo->prepare("SELECT COUNT(*) FROM bookmarks WHERE user_id = ?");
        $bookmarkStmt->execute([$userId]);
        $bookmarkCount = $bookmarkStmt->fetchColumn();
        
        $commentStmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE user_id = ?");
        $commentStmt->execute([$userId]);
        $commentCount = $commentStmt->fetchColumn();
        
        $memberSince = date('M d, Y', strtotime($editUser['created_at']));
        ?>
        
        <div class="user-stat-item">
          <div class="number"><?= $bookmarkCount ?></div>
          <div class="label">Bookmarks</div>
        </div>
        
        <div class="user-stat-item">
          <div class="number"><?= $commentCount ?></div>
          <div class="label">Comments</div>
        </div>
        
        <div class="user-stat-item">
          <div class="label">Member Since</div>
          <div><?= $memberSince ?></div>
        </div>
      </div>
    </div>
    
    <form action="" method="post">
      <input type="hidden" name="action" value="update_user">
      
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" class="form-control" value="<?= htmlspecialchars($editUser['username']) ?>" disabled>
        <small>Username cannot be changed</small>
      </div>
      
      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($editUser['email']) ?>">
      </div>
      
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
        <small>Enter a new password only if you want to change it</small>      </div>        <div class="form-check" style="display: flex; justify-content: space-between; align-items: center;">
        <label for="is_admin" style="margin-bottom: 0; margin-right: 10px; white-space: nowrap;">Administrator Access</label>
        <input type="checkbox" id="is_admin" name="is_admin" <?= $editUser['is_admin'] ? 'checked' : '' ?>>
      </div>
      
      <div class="form-actions">
        <a href="index.php?tab=users" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Update User</button>
      </div>
    </form>
  </div>
  
  <?php include __DIR__.'/../footer.php'; ?>
</body>
</html>
