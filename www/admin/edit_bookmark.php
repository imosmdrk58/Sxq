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

// Check if we have a bookmark ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header('Location: index.php?tab=bookmarks');
  exit;
}

$bookmarkId = (int)$_GET['id'];

// Get bookmark details
$stmt = $pdo->prepare("
  SELECT b.*, u.username 
  FROM bookmarks b
  JOIN users u ON b.user_id = u.id
  WHERE b.id = ?
");
$stmt->execute([$bookmarkId]);
$bookmark = $stmt->fetch();

if (!$bookmark) {
  header('Location: index.php?tab=bookmarks');
  exit;
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action']) && $_POST['action'] === 'update_bookmark') {
    $manga_title = trim($_POST['manga_title']);
    $last_chapter = trim($_POST['last_chapter']);
    $max_chapters = trim($_POST['max_chapters']) ?: null;
    $description = trim($_POST['description']);
    $notes = trim($_POST['notes']);
    $cover_image = trim($_POST['cover_image']);
    
    if (empty($manga_title)) {
      $error = "Manga title cannot be empty";
    } else {
      try {
        // Update bookmark
        $stmt = $pdo->prepare("
          UPDATE bookmarks
          SET manga_title = ?,
              last_chapter = ?,
              max_chapters = ?,
              description = ?,
              notes = ?,
              cover_image = ?,
              updated_at = CURRENT_TIMESTAMP
          WHERE id = ?
        ");
        
        $stmt->execute([
          $manga_title,
          $last_chapter,
          $max_chapters,
          $description,
          $notes,
          $cover_image,
          $bookmarkId
        ]);
        
        $message = "Bookmark updated successfully!";
        
        // Refresh bookmark data
        $stmt = $pdo->prepare("
          SELECT b.*, u.username 
          FROM bookmarks b
          JOIN users u ON b.user_id = u.id
          WHERE b.id = ?
        ");
        $stmt->execute([$bookmarkId]);
        $bookmark = $stmt->fetch();
        
      } catch (PDOException $e) {
        $error = "Error updating bookmark: " . $e->getMessage();
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
  <title>Edit Bookmark - Admin Panel</title>  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
    
    textarea.form-control {
      min-height: 100px;
    }
    
    .input-group {
      display: flex;
      gap: 10px;
    }
    
    .input-group .form-control {
      flex: 1;
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
    
    .preview-section {
      margin-bottom: 20px;
      padding: 15px;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
    
    .cover-preview {
      width: 150px;
      height: 200px;
      object-fit: cover;
      margin: 0 auto;
      display: block;
      background-color: #f5f5f5;
      border: 1px solid #ddd;
    }
  </style>
</head>

<body>
  <?php include __DIR__.'/../header.php'; ?>
  
  <div class="admin-container">
    <div class="admin-header">
      <h1><i class="fas fa-edit"></i> Edit Manga Bookmark</h1>
      <a href="index.php?tab=bookmarks" class="btn"><i class="fas fa-arrow-left"></i> Back to Bookmarks</a>
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
    
    <div class="preview-section">
      <div style="display:flex; align-items:center; gap:20px">
        <?php if (!empty($bookmark['cover_image'])): ?>
          <img src="<?= htmlspecialchars($bookmark['cover_image']) ?>" alt="Cover" class="cover-preview">
        <?php else: ?>
          <div class="cover-preview" style="display:flex; align-items:center; justify-content:center">
            <i class="fas fa-book" style="font-size:2rem; color:#aaa"></i>
          </div>
        <?php endif; ?>
        
        <div>
          <p><strong>Owner:</strong> <?= htmlspecialchars($bookmark['username']) ?></p>
          <p><strong>Created:</strong> <?= date('Y-m-d', strtotime($bookmark['created_at'])) ?></p>
          <p><strong>Last Updated:</strong> <?= date('Y-m-d', strtotime($bookmark['updated_at'])) ?></p>
        </div>
      </div>
    </div>
    
    <form action="" method="post">
      <input type="hidden" name="action" value="update_bookmark">
      
      <div class="form-group">
        <label for="manga_title">Manga Title</label>
        <input type="text" id="manga_title" name="manga_title" class="form-control" value="<?= htmlspecialchars($bookmark['manga_title']) ?>" required>
      </div>
      
      <div class="form-group">
        <label for="cover_image">Cover Image URL</label>
        <input type="url" id="cover_image" name="cover_image" class="form-control" value="<?= htmlspecialchars($bookmark['cover_image']) ?>">
        <small>Leave empty for default cover</small>
      </div>
      
      <div class="input-group">
        <div class="form-group" style="flex: 1">
          <label for="last_chapter">Last Chapter Read</label>
          <input type="number" id="last_chapter" name="last_chapter" class="form-control" value="<?= $bookmark['last_chapter'] ?>" min="0" step="any">
        </div>
        
        <div class="form-group" style="flex: 1">
          <label for="max_chapters">Maximum Chapters</label>
          <input type="number" id="max_chapters" name="max_chapters" class="form-control" value="<?= $bookmark['max_chapters'] ?>" min="0" step="any">
        </div>
      </div>
      
      <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" class="form-control"><?= htmlspecialchars($bookmark['description']) ?></textarea>
      </div>
      
      <div class="form-group">
        <label for="notes">User Notes</label>
        <textarea id="notes" name="notes" class="form-control"><?= htmlspecialchars($bookmark['notes']) ?></textarea>
      </div>
      
      <div class="form-actions">
        <a href="index.php?tab=bookmarks" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Update Bookmark</button>
      </div>
    </form>
  </div>
  
  <?php include __DIR__.'/../footer.php'; ?>
</body>
</html>
