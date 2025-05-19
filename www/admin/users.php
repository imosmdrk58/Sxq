<?php
// This file is included within admin.php
// Security check
defined('IN_ADMIN') or define('IN_ADMIN', true);

// Process delete request if exists
if (isset($_GET['action']) && $_GET['action'] === 'delete-user' && isset($_GET['id'])) {
  $userId = (int)$_GET['id'];
  
  // Don't allow deleting yourself
  if ($userId === (int)$_SESSION['user_id']) {
    echo '<div class="alert alert-error">You cannot delete your own account!</div>';
  } else {
    try {
      // Begin transaction
      $pdo->beginTransaction();
      
      // Delete user's comments
      $stmt = $pdo->prepare("DELETE FROM comments WHERE user_id = ?");
      $stmt->execute([$userId]);
      
      // Delete user's chapter reads
      $stmt = $pdo->prepare("DELETE FROM manga_chapters WHERE bookmark_id IN (SELECT id FROM bookmarks WHERE user_id = ?)");
      $stmt->execute([$userId]);
      
      // Delete user's manga bookmarks
      $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE user_id = ?");
      $stmt->execute([$userId]);
      
      // Delete the user
      $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
      $stmt->execute([$userId]);
      
      // Commit transaction
      $pdo->commit();
      
      echo '<div class="alert alert-success">User and all related data deleted successfully!</div>';
    } catch (PDOException $e) {
      // Rollback on error
      $pdo->rollBack();
      echo '<div class="alert alert-error">Error deleting user: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
  }
}
?>

<h2><i class="fas fa-users"></i> User Management</h2>

<div class="table-responsive">
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Email</th>
        <th>Created</th>
        <th>Admin</th>
        <th>Bookmarks</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      // Get all users with bookmark count
      $stmt = $pdo->query("
        SELECT u.id, u.username, u.email, u.created_at, u.is_admin,
               COUNT(b.id) as bookmark_count
        FROM users u
        LEFT JOIN bookmarks b ON u.id = b.user_id
        GROUP BY u.id
        ORDER BY u.id ASC
      ");
      
      while ($user = $stmt->fetch()) {
      ?>      <tr>
        <td data-label="ID"><?= $user['id'] ?></td>
        <td data-label="Username"><?= htmlspecialchars($user['username']) ?></td>
        <td data-label="Email"><?= $user['email'] ? htmlspecialchars($user['email']) : '<em>Not set</em>' ?></td>
        <td data-label="Created"><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
        <td data-label="Admin">
          <?php if ($user['is_admin']): ?>
            <span style="color: green;"><i class="fas fa-check-circle"></i> Yes</span>
          <?php else: ?>
            <span style="color: red;"><i class="fas fa-times-circle"></i> No</span>
          <?php endif; ?>
        </td>
        <td data-label="Bookmarks"><?= $user['bookmark_count'] ?></td>        <td data-label="Actions" class="action-buttons">
          <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-secondary">
            <i class="fas fa-edit"></i> Edit
          </a>
          <?php if ($user['id'] != $_SESSION['user_id']): ?>
            <a href="#" onclick="return confirmDelete('index.php?tab=users&action=delete-user&id=<?= $user['id'] ?>', 'user <?= htmlspecialchars($user['username']) ?>')" class="btn btn-sm btn-danger">
              <i class="fas fa-trash"></i> Delete
            </a>
          <?php endif; ?>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>
