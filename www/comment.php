<?php require_once __DIR__.'/config/config.php'; 

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = isset($_POST['name']) ? trim($_POST['name']) : '';
  $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
  $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
  
  if (!empty($name) && !empty($comment)) {
    // Insert comment
    $stmt = $pdo->prepare("
      INSERT INTO comments (user_id, name, content, created_at) 
      VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $name, $comment]);
  }
}

// Get comments (most recent first)
$stmt = $pdo->prepare("
  SELECT c.*, u.username 
  FROM comments c
  LEFT JOIN users u ON c.user_id = u.id
  ORDER BY c.created_at DESC 
  LIMIT 20
");
$stmt->execute();
$comments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Guestbook – Manga Tracker</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <?php include __DIR__.'/header.php'; ?>
  
  <div class="container">
    <h1>Community Guestbook</h1>
    <p>Share your thoughts, recommendations, or connect with other manga enthusiasts!</p>
    
    <form method="post" class="mt-2">
      <div class="form-row">
        <div>
          <label for="name">Your Name</label>
          <input type="text" id="name" name="name" placeholder="Enter your name" value="<?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : '' ?>" required>
        </div>
      </div>
      
      <label for="comment">Your Comment</label>
      <textarea id="comment" name="comment" rows="4" placeholder="Share your thoughts, manga recommendations, or just say hello!" required></textarea>
      
      <button type="submit"><i class="fas fa-paper-plane"></i> Post Comment</button>
    </form>
    
    <div class="comment-list">
      <?php if (count($comments) > 0): ?>
        <?php foreach($comments as $comment): ?>
          <div class="comment">
            <div class="comment-header">
              <strong>
                <?php if($comment['user_id']): ?>
                  <i class="fas fa-user"></i> 
                  <?= htmlspecialchars($comment['username'] ?: $comment['name']) ?>
                <?php else: ?>
                  <i class="fas fa-user-circle"></i> 
                  <?= htmlspecialchars($comment['name']) ?>
                <?php endif; ?>
              </strong>
              <em><i class="far fa-clock"></i> <?= date('F j, Y, g:i a', strtotime($comment['created_at'])) ?></em>
            </div>
            <p><?= nl2br(htmlspecialchars($comment['content'])) ?></p>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div style="text-align: center; padding: 2rem 0;">
          <i class="far fa-comments" style="font-size: 2.5rem; color: var(--gray); margin-bottom: 1rem;"></i>
          <h3>No comments yet</h3>
          <p>Be the first to leave a comment!</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
  
  <?php include __DIR__.'/footer.php'; ?>
  
  <script>
  // Add active class to current nav item
  document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('header nav a');
    
    navLinks.forEach(link => {
      const linkPage = link.getAttribute('href');
      if (linkPage === currentPage) {
        link.classList.add('active');
      }
    });
  });
  </script>
</body>
</html>
