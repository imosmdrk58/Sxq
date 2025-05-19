<?php
// This file is included within admin.php
// Security check
defined('IN_ADMIN') or define('IN_ADMIN', true);

// Process delete request if exists
if (isset($_GET['action']) && $_GET['action'] === 'delete-bookmark' && isset($_GET['id'])) {
  $bookmarkId = (int)$_GET['id'];
  
  try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Delete bookmark's chapters
    $stmt = $pdo->prepare("DELETE FROM manga_chapters WHERE bookmark_id = ?");
    $stmt->execute([$bookmarkId]);
    
    // Delete the bookmark
    $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE id = ?");
    $stmt->execute([$bookmarkId]);
    
    // Commit transaction
    $pdo->commit();
    
    echo '<div class="alert alert-success">Bookmark and all related data deleted successfully!</div>';
  } catch (PDOException $e) {
    // Rollback on error
    $pdo->rollBack();
    echo '<div class="alert alert-error">Error deleting bookmark: ' . htmlspecialchars($e->getMessage()) . '</div>';
  }
}
?>

<h2><i class="fas fa-bookmark"></i> Bookmark Management</h2>

<div class="table-responsive">
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>User</th>
        <th>Manga Title</th>
        <th>Last Ch.</th>
        <th>Max Ch.</th>
        <th>Created</th>
        <th>Updated</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php
      // Get all bookmarks with user info
      $stmt = $pdo->query("
        SELECT b.*, u.username 
        FROM bookmarks b
        JOIN users u ON b.user_id = u.id
        ORDER BY b.updated_at DESC
      ");
      
      while ($bookmark = $stmt->fetch()) {
      ?>      <tr>
        <td data-label="ID"><?= $bookmark['id'] ?></td>
        <td data-label="User"><?= htmlspecialchars($bookmark['username']) ?></td>
        <td data-label="Manga Title"><?= htmlspecialchars($bookmark['manga_title']) ?></td>
        <td data-label="Last Ch."><?= $bookmark['last_chapter'] ?></td>
        <td data-label="Max Ch."><?= $bookmark['max_chapters'] ?: 'Not set' ?></td>
        <td data-label="Created"><?= date('Y-m-d', strtotime($bookmark['created_at'])) ?></td>
        <td data-label="Updated"><?= date('Y-m-d', strtotime($bookmark['updated_at'])) ?></td>        <td data-label="Actions" class="action-buttons">
          <a href="edit_bookmark.php?id=<?= $bookmark['id'] ?>" class="btn btn-sm btn-secondary">
            <i class="fas fa-edit"></i> Edit
          </a>
          <a href="#" onclick="return confirmDelete('index.php?tab=bookmarks&action=delete-bookmark&id=<?= $bookmark['id'] ?>', 'bookmark <?= htmlspecialchars($bookmark['manga_title']) ?>')" class="btn btn-sm btn-danger">
            <i class="fas fa-trash"></i> Delete
          </a>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>
