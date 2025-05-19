<?php
// This file is included within admin.php
// Security check
defined('IN_ADMIN') or define('IN_ADMIN', true);

// Process delete request if exists
if (isset($_GET['action']) && $_GET['action'] === 'delete-comment' && isset($_GET['id'])) {
  $commentId = (int)$_GET['id'];
  
  try {
    // Delete the comment
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$commentId]);
    
    echo '<div class="alert alert-success">Comment deleted successfully!</div>';
  } catch (PDOException $e) {
    echo '<div class="alert alert-error">Error deleting comment: ' . htmlspecialchars($e->getMessage()) . '</div>';
  }
}
?>

<h2><i class="fas fa-comments"></i> Comment Management</h2>

<div class="table-responsive">
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>User</th>
        <th>Content</th>
        <th>Manga</th>
        <th>Created</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php      // Get all comments with user and manga info (using LEFT JOIN to include all comments)
      $stmt = $pdo->query("
        SELECT c.*, u.username, b.manga_title
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        LEFT JOIN bookmarks b ON c.bookmark_id = b.id
        ORDER BY c.created_at DESC
      ");
      
      while ($comment = $stmt->fetch()) {
      ?>      <tr>
        <td data-label="ID"><?= $comment['id'] ?></td>
        <td data-label="User">
          <?php if (!empty($comment['user_id']) && !empty($comment['username'])): ?>
            <?= htmlspecialchars($comment['username']) ?>
          <?php elseif (!empty($comment['name'])): ?>
            <?= htmlspecialchars($comment['name']) ?> <em>(guest)</em>
          <?php else: ?>
            <em>Anonymous</em>
          <?php endif; ?>
        </td>        <td data-label="Content">
          <?php 
            // Truncate long comments for display
            $content = htmlspecialchars($comment['content']);
            echo (strlen($content) > 100) ? substr($content, 0, 100) . '...' : $content; 
          ?>
        </td>        <td data-label="Manga">
          <?php if (!empty($comment['bookmark_id']) && !empty($comment['manga_title'])): ?>
            <a href="manga_detail.php?id=<?= $comment['bookmark_id'] ?>" target="_blank">
              <?= htmlspecialchars($comment['manga_title']) ?>
            </a>
          <?php else: ?>
            <em>General comment</em>
          <?php endif; ?>
        </td>        <td data-label="Created"><?= date('Y-m-d H:i', strtotime($comment['created_at'])) ?></td>
        <td data-label="Actions" class="action-buttons">
          <a href="#" onclick="return confirmDelete('index.php?tab=comments&action=delete-comment&id=<?= $comment['id'] ?>', 'this comment')" class="btn btn-sm btn-danger">
            <i class="fas fa-trash"></i> Delete
          </a>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>
