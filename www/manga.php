<?php require_once __DIR__.'/config/config.php';
if (empty($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD']==='POST') {
  // Add/update manga entry
  if(isset($_POST['action']) && $_POST['action'] === 'add') {
    $t = trim($_POST['title']);
    $c = trim($_POST['chapter']);
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    $pdo->prepare("
      INSERT INTO bookmarks (user_id, manga_title, last_chapter, notes, cover_image, description, api_id, api_source)
      VALUES (?,?,?,?,NULL,NULL,NULL,'manual')
      ON DUPLICATE KEY UPDATE
        last_chapter=VALUES(last_chapter),
        notes=VALUES(notes),
        updated_at=CURRENT_TIMESTAMP
    ")->execute([$_SESSION['user_id'], $t, $c, $notes]);
    
    // Log the read
    $pdo->prepare("
      INSERT INTO reads_log (user_id, manga_title, chapter, read_at)
      VALUES (?,?,?,NOW())
    ")->execute([$_SESSION['user_id'], $t, $c]);
  }
  
  // Delete manga entry
  if(isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $pdo->prepare("DELETE FROM bookmarks WHERE id = ? AND user_id = ?")->execute([$id, $_SESSION['user_id']]);
  }
}

// Get user's manga list
$stmt = $pdo->prepare("SELECT * FROM bookmarks WHERE user_id = ? ORDER BY updated_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll();

// Get reading stats
$statsStmt = $pdo->prepare("
  SELECT 
    COUNT(DISTINCT manga_title) as total_manga,
    COUNT(*) as total_chapters,
    MAX(read_at) as last_read
  FROM reads_log 
  WHERE user_id = ?
");
$statsStmt->execute([$_SESSION['user_id']]);
$stats = $statsStmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Manga – Manga Tracker</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <?php include __DIR__.'/header.php'; ?>
  <div class="container">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap;">
      <div>
        <h1>My Manga Collection</h1>
      </div>
      <div style="background: var(--primary-light); color: white; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
        <h3 style="color: white; margin-bottom: 0.5rem;">Reading Stats</h3>
        <div style="display: flex; gap: 1.5rem;">
          <div>
            <div style="font-size: 1.5rem; font-weight: bold;"><?= isset($stats['total_manga']) ? $stats['total_manga'] : 0 ?></div>
            <div>Titles</div>
          </div>
          <div>
            <div style="font-size: 1.5rem; font-weight: bold;"><?= isset($stats['total_chapters']) ? $stats['total_chapters'] : 0 ?></div>
            <div>Chapters</div>
          </div>
          <div>
            <div style="font-size: 1rem; font-weight: bold;">
              <?= $stats['last_read'] ? date('M j, Y', strtotime($stats['last_read'])) : 'N/A' ?>
            </div>
            <div>Last Read</div>
          </div>
        </div>
      </div>
    </div>
    
    <form method="post" style="background: #f9f9f9; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem;">
      <input type="hidden" name="action" value="add">
      <h2 style="margin-top: 0; margin-bottom: 1rem;">Add or Update Manga</h2>
      <div class="form-row">
        <div>
          <label for="title">Manga Title</label>
          <input id="title" name="title" placeholder="Enter manga title" required>
        </div>
        <div>
          <label for="chapter">Current Chapter</label>
          <input id="chapter" name="chapter" placeholder="Chapter number" required>
        </div>
      </div>
      <label for="notes">Notes (optional)</label>
      <textarea id="notes" name="notes" placeholder="Add notes about the manga or where you left off..." rows="3"></textarea>
      <button type="submit"><i class="fas fa-save"></i> Save Progress</button>
    </form>
    
    <?php if(count($items) > 0): ?>
      <h2>Your Collection</h2>
      <div class="manga-grid">
        <?php foreach($items as $r): ?>
          <div class="manga-item" id="manga-<?= $r['id'] ?>">
            <?php if(!empty($r['cover_image'])): ?>
              <img src="<?= htmlspecialchars($r['cover_image']) ?>" 
                   alt="<?= htmlspecialchars($r['manga_title']) ?>"
                   style="width: 100%; height: 180px; object-fit: cover; border-radius: 6px; margin-bottom: 1rem;">
            <?php endif; ?>
            
            <div class="manga-title">
              <?= htmlspecialchars($r['manga_title']) ?>
              <?php if(!empty($r['api_source'])): ?>
                <span class="api-badge <?= htmlspecialchars($r['api_source']) ?>"><?= htmlspecialchars(ucfirst($r['api_source'])) ?></span>
              <?php endif; ?>
            </div>
            <div class="manga-chapter">Chapter <?= htmlspecialchars($r['last_chapter']) ?></div>
            <div class="manga-date">Updated: <?= date('M j, Y', strtotime($r['updated_at'])) ?></div>
            
            <?php if(!empty($r['description'])): ?>
              <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid #eee;">
                <div style="font-size: 0.9rem; color: var(--gray); max-height: 100px; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical;">
                  <?= htmlspecialchars($r['description']) ?>
                </div>
              </div>
            <?php endif; ?>
            
            <?php if(!empty($r['notes'])): ?>
              <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid #eee;">
                <div style="font-size: 0.9rem; color: var(--gray);"><?= htmlspecialchars($r['notes']) ?></div>
              </div>
            <?php endif; ?>
            
            <div style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center;">
              <form method="post" style="margin: 0;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <button type="submit" style="background: var(--danger); padding: 0.4rem 0.75rem; font-size: 0.9rem;">
                  <i class="fas fa-trash"></i> Remove
                </button>
              </form>
              <button onclick="editManga('<?= htmlspecialchars(addslashes($r['manga_title'])) ?>', '<?= htmlspecialchars($r['last_chapter']) ?>', '<?= htmlspecialchars(addslashes(isset($r['notes']) ? $r['notes'] : '')) ?>')" 
                      style="background: var(--gray); padding: 0.4rem 0.75rem; font-size: 0.9rem;">
                <i class="fas fa-edit"></i> Edit
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div style="text-align: center; padding: 3rem 0;">
        <i class="fas fa-book" style="font-size: 3rem; color: var(--gray); margin-bottom: 1rem;"></i>
        <h3>Your collection is empty</h3>
        <p>Start tracking your manga reading progress by adding titles above!</p>
      </div>
    <?php endif; ?>
  </div>
  
  <?php include __DIR__.'/footer.php'; ?>
  
  <script>
  // Function to populate the form for editing
  function editManga(title, chapter, notes) {
    document.getElementById('title').value = title;
    document.getElementById('chapter').value = chapter;
    document.getElementById('notes').value = notes;
    document.getElementById('title').focus();
    window.scrollTo({top: 0, behavior: 'smooth'});
  }
  
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
</body>
</html>
