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

// Admin is logged in, continue with admin panel
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Manga Tracker</title>  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/style-fixes.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <style>
    .admin-container {
      max-width: 1200px;
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
    
    .admin-tabs {
      display: flex;
      margin-bottom: 20px;
      border-bottom: 1px solid #eee;
    }
    
    .admin-tab {
      padding: 10px 20px;
      cursor: pointer;
      border-bottom: 2px solid transparent;
      margin-right: 10px;
      font-weight: 500;
    }
    
    .admin-tab.active {
      border-bottom: 2px solid var(--primary);
      color: var(--primary);
    }
    
    .admin-tab i {
      margin-right: 5px;
    }
    
    .admin-panel {
      background: #fff;
      border-radius: 5px;
      padding: 20px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .admin-table {
      width: 100%;
      border-collapse: collapse;
    }
    
    .admin-table th, .admin-table td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #eee;
    }
    
    .admin-table th {
      background-color: #f9f9f9;
      font-weight: bold;
    }
    
    .admin-table tr:hover {
      background-color: #f5f5f5;
    }
    
    .action-buttons {
      display: flex;
      gap: 5px;
    }
    .action-buttons a, .action-buttons button {
      padding: 5px 10px;
      font-size: 0.8rem;
      border-radius: 3px;
    }
    
    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 4px;
    }
    
    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border-left: 4px solid #28a745;
    }
    
    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      border-left: 4px solid #dc3545;
    }
    
    .admin-stats {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 20px;
    }
    
    .stat-card {
      background: #fff;
      border-radius: 5px;
      padding: 15px;
      text-align: center;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .stat-card .number {
      font-size: 2rem;
      font-weight: bold;
      margin: 10px 0;
      color: var(--primary);
    }
    
    .stat-card .label {
      color: #666;
      font-size: 0.9rem;
    }
    
    .confirm-dialog {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0,0,0,0.5);
      z-index: 999;
      align-items: center;
      justify-content: center;
    }
    
    .confirm-dialog-content {
      background: white;
      padding: 20px;
      border-radius: 5px;
      width: 300px;
      max-width: 90%;
    }
    
    .confirm-dialog-actions {
      display: flex;
      justify-content: flex-end;
      margin-top: 20px;
      gap: 10px;
    }
  </style>
</head>
<body>
  <?php include __DIR__.'/../header.php'; ?>
  
  <div class="admin-container">
    <div class="admin-header">
      <h1><i class="fas fa-shield-alt"></i> Admin Panel</h1>
    </div>
    
    <div class="admin-tabs">
      <a href="index.php?tab=users" class="admin-tab <?= $activeTab === 'users' ? 'active' : '' ?>">
        <i class="fas fa-users"></i> Users
      </a>
      <a href="index.php?tab=bookmarks" class="admin-tab <?= $activeTab === 'bookmarks' ? 'active' : '' ?>">
        <i class="fas fa-bookmark"></i> Bookmarks
      </a>
      <a href="index.php?tab=comments" class="admin-tab <?= $activeTab === 'comments' ? 'active' : '' ?>">
        <i class="fas fa-comments"></i> Comments
      </a>
    </div>
    
    <div class="admin-stats">
      <?php
      // Get quick stats
      $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
      $bookmarkCount = $pdo->query("SELECT COUNT(*) FROM bookmarks")->fetchColumn();
      $commentCount = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
      ?>
      
      <div class="stat-card">
        <div class="label">Total Users</div>
        <div class="number"><?= $userCount ?></div>
      </div>
      
      <div class="stat-card">
        <div class="label">Total Manga Bookmarks</div>
        <div class="number"><?= $bookmarkCount ?></div>
      </div>
      
      <div class="stat-card">
        <div class="label">Total Comments</div>
        <div class="number"><?= $commentCount ?></div>
      </div>
    </div>
      <div class="admin-panel">
      <?php 
      // Define IN_ADMIN constant to prevent direct access to tab files
      define('IN_ADMIN', true);
      
      // Include the appropriate tab content based on the active tab
      include __DIR__ . '/' . $activeTab . '.php';
      ?>
    </div>
  </div>
  
  <?php include __DIR__.'/../footer.php'; ?>
  
  <!-- Confirmation dialog -->
  <div id="confirmDialog" class="confirm-dialog">
    <div class="confirm-dialog-content">
      <h3>Confirm Action</h3>
      <p id="confirmMessage">Are you sure you want to proceed?</p>
      <div class="confirm-dialog-actions">
        <button class="btn btn-secondary" onclick="hideConfirmDialog()">Cancel</button>
        <button class="btn btn-danger" id="confirmButton">Confirm</button>
      </div>
    </div>
  </div>
    <script>
    // Function to show confirmation dialog
    function showConfirmDialog(message, confirmUrl) {
      document.getElementById('confirmMessage').textContent = message;
      document.getElementById('confirmButton').onclick = function() {
        window.location.href = confirmUrl;
      };
      document.getElementById('confirmDialog').style.display = 'flex';
    }
    
    // Function to hide confirmation dialog
    function hideConfirmDialog() {
      document.getElementById('confirmDialog').style.display = 'none';
    }
    
    // Function for delete confirmation used by delete buttons
    function confirmDelete(url, itemName) {
      showConfirmDialog('Are you sure you want to delete ' + itemName + '?', url);
      return false;
    }
  </script>
</body>
</html>
