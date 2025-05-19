<?php require_once __DIR__.'/config/config.php';
require_once __DIR__.'/utils/manga_utils.php';

// Check if user is logged in, if not redirect to login page with return URL
if (empty($_SESSION['user_id'])) {
  $_SESSION['redirect_after_login'] = 'manga.php';
  header('Location: login.php');
  exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD']==='POST') {
  // Add/update manga entry
  if(isset($_POST['action']) && $_POST['action'] === 'add') {    $t = trim($_POST['title']);
    $c = trim($_POST['chapter']);
    $max = isset($_POST['max_chapters']) && !empty($_POST['max_chapters']) ? (int)$_POST['max_chapters'] : null;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
      // Try to get manga details from API
    $mangaDetails = getMangaDetailsFromAPI($t);
    
    // Set up variables for database insertion
    $coverImage = null;
    $description = null;
    $apiId = null;
    
    // Check if we have manga details and filter for inappropriate content
    $isAppropriate = true;
    if ($mangaDetails) {
      $adultKeywords = ['hentai', 'adult', 'explicit', 'ecchi', 'smut', 'yaoi', 'yuri'];
      
      // Check title for adult keywords
      foreach ($adultKeywords as $keyword) {
        if (stripos($mangaDetails['title'], $keyword) !== false) {
          $isAppropriate = false;
          break;
        }
      }
      
      // Also check genres if available
      if ($isAppropriate && isset($mangaDetails['genres']) && is_array($mangaDetails['genres'])) {
        foreach ($mangaDetails['genres'] as $genre) {
          if (in_array(strtolower($genre['name']), $adultKeywords)) {
            $isAppropriate = false;
            break;
          }
        }
      }
      
      // Only use the details if content is appropriate
      if ($isAppropriate) {
        // Get cover image if available
        if (isset($mangaDetails['images']['jpg']['image_url'])) {
          $coverImage = $mangaDetails['images']['jpg']['image_url'];
        }
        
        // Get description if available
        if (isset($mangaDetails['synopsis']) && $mangaDetails['synopsis'] != 'No description available') {
          $description = $mangaDetails['synopsis'];
        }
        
        // Get API ID if available
        if (isset($mangaDetails['api_id'])) {
          $apiId = $mangaDetails['api_id'];
        }
        
        // Use max chapters from API if not provided by user
        if (empty($max) && isset($mangaDetails['chapters']) && $mangaDetails['chapters'] > 0) {
          $max = $mangaDetails['chapters'];
        }
      }
    }
    
    // Try to insert with api_source first, but fallback to not using it if the column doesn't exist
    try {
      $pdo->prepare("
        INSERT INTO bookmarks (user_id, manga_title, last_chapter, max_chapters, notes, cover_image, description, api_id, api_source)
        VALUES (?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
          last_chapter=VALUES(last_chapter),
          max_chapters=VALUES(max_chapters),
          notes=VALUES(notes),
          cover_image=COALESCE(cover_image, VALUES(cover_image)),
          description=COALESCE(description, VALUES(description)),
          api_id=COALESCE(api_id, VALUES(api_id)),
          updated_at=CURRENT_TIMESTAMP
      ")->execute([$_SESSION['user_id'], $t, $c, $max, $notes, $coverImage, $description, $apiId, 'automatic']);    } catch (PDOException $e) {
      // If the max_chapters column doesn't exist, try without it
      if (strpos($e->getMessage(), "Unknown column 'max_chapters'") !== false) {
        $pdo->prepare("
          INSERT INTO bookmarks (user_id, manga_title, last_chapter, notes, cover_image, description, api_id, api_source)
          VALUES (?,?,?,?,?,?,?,?)
          ON DUPLICATE KEY UPDATE
            last_chapter=VALUES(last_chapter),
            notes=VALUES(notes),
            cover_image=COALESCE(cover_image, VALUES(cover_image)),
            description=COALESCE(description, VALUES(description)),
            api_id=COALESCE(api_id, VALUES(api_id)),
            updated_at=CURRENT_TIMESTAMP
        ")->execute([$_SESSION['user_id'], $t, $c, $notes, $coverImage, $description, $apiId, 'automatic']);
      } else {
        // If it's some other error, try with minimal fields
        try {
          $pdo->prepare("
            INSERT INTO bookmarks (user_id, manga_title, last_chapter)
            VALUES (?,?,?)
            ON DUPLICATE KEY UPDATE
              last_chapter=VALUES(last_chapter),
              updated_at=CURRENT_TIMESTAMP
          ")->execute([$_SESSION['user_id'], $t, $c]);
        } catch (PDOException $innerE) {
          // If we still get an error, throw it
          throw $innerE;
        }
      }
    }
    
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">  <title>My Manga – Manga Tracker</title>  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/style-fixes.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
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
    </div>      <?php
// Get pre-filled manga title from URL parameter if available
$prefilledTitle = isset($_GET['title']) ? htmlspecialchars($_GET['title']) : '';

// Get manga details if we have a title
$prefilledDetails = null;
if (!empty($prefilledTitle)) {
  try {
    $mangaData = searchMangaByTitle($prefilledTitle, 1);
    if (!empty($mangaData['results'])) {
      $prefilledDetails = $mangaData['results'][0];
    }
  } catch (Exception $e) {
    // Silent fail - just continue without prefilled data
  }
}
?>
<form method="post" style="background: #f9f9f9; border-radius: 8px; padding: 1.5rem; margin-bottom: 2rem;">
      <input type="hidden" name="action" value="add">
      <h2 style="margin-top: 0; margin-bottom: 1rem;">Add or Update Manga</h2>
      <div class="form-row">
        <div>
          <label for="title">Manga Title</label>
          <input id="title" name="title" placeholder="Enter manga title" required value="<?= $prefilledTitle ?>">
        </div>
        <div>
          <label for="chapter">Current Chapter</label>
          <input id="chapter" name="chapter" placeholder="Chapter number" required>
        </div>
        <div>
          <label for="max_chapters">Max Chapters (optional)</label>
          <input id="max_chapters" name="max_chapters" type="number" placeholder="Total chapters" min="1" 
                 value="<?= (!empty($prefilledDetails) && !empty($prefilledDetails['chapters'])) ? $prefilledDetails['chapters'] : '' ?>">
        </div>
      </div>
      <label for="notes">Notes (optional)</label>
      <textarea id="notes" name="notes" placeholder="Add notes about the manga or where you left off..." rows="3"><?= (!empty($prefilledDetails) && !empty($prefilledDetails['synopsis'])) ? htmlspecialchars($prefilledDetails['synopsis']) : '' ?></textarea>
      <div class="form-actions">
        <button type="submit"><i class="fas fa-save"></i> Save Progress</button>
      </div>
    </form>
      <?php if(count($items) > 0): ?>
      <h2>Your Collection</h2>      <div class="manga-grid">
        <?php foreach($items as $r): ?>
          <div class="manga-item" id="manga-<?= $r['id'] ?>">
            <div class="manga-cover">
            <?php if(!empty($r['cover_image'])): ?>
              <img src="<?= htmlspecialchars($r['cover_image']) ?>" alt="">
            <?php else: ?>              <?php 
                // Try to get cover from API
                $apiCover = getMangaCoverFromAPI($r['manga_title']);
                if (!empty($apiCover)):
              ?>
                <img src="<?= htmlspecialchars($apiCover) ?>" alt="">
              <?php else: ?>
                <div class="placeholder-cover">
                  <i class="fas fa-book" style="font-size: 3rem; color: #ccc;"></i>
                </div>
              <?php endif; ?>
            <?php endif; ?>
            </div>
            
            <div class="manga-title">
              <?= htmlspecialchars($r['manga_title']) ?>
              <?php if(!empty($r['api_source'])): ?>
                <span class="api-badge <?= htmlspecialchars($r['api_source']) ?>"><?= htmlspecialchars(ucfirst($r['api_source'])) ?></span>
              <?php endif; ?>
            </div>
            <div class="manga-chapter">Chapter <?= htmlspecialchars($r['last_chapter']) ?></div>
            <div class="manga-date">Updated: <?= date('M j, Y', strtotime($r['updated_at'])) ?></div>
            
            <?php if(!empty($r['description'])): ?>
              <div class="manga-description">
                <?= htmlspecialchars($r['description']) ?>
              </div>
            <?php endif; ?>
            
            <?php if(!empty($r['notes'])): ?>
              <div class="manga-notes">
                <?= htmlspecialchars($r['notes']) ?>
              </div>
            <?php endif; ?>            <div class="action-buttons">
              <div class="manga-btn-container">
                <form method="post" class="manga-btn-form">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button type="submit" class="manga-btn manga-btn-danger">
                    <i class="fas fa-trash"></i> <span>Remove</span>                  </button>
                </form>
                <div class="manga-btn-form">
                  <a href="manga_detail.php?id=<?= $r['id'] ?>" class="manga-btn manga-btn-primary">
                    <i class="fas fa-list-ol"></i> <span>Chapters</span>
                  </a>
                </div>                <div class="manga-btn-form">
                  <button type="button" 
                    data-title="<?= htmlspecialchars(addslashes($r['manga_title'])) ?>"
                    data-chapter="<?= htmlspecialchars($r['last_chapter']) ?>"
                    data-notes="<?= htmlspecialchars(addslashes(isset($r['notes']) ? $r['notes'] : '')) ?>"
                    data-max="<?= htmlspecialchars($r['max_chapters'] ?? '') ?>"
                    onclick="editMangaFromData(this)"
                    class="manga-btn manga-btn-edit" 
                    data-id="<?= $r['id'] ?>">
                    <i class="fas fa-edit"></i> <span>Edit</span>
                  </button>
                </div>
              </div>
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
    <script>  // Function to populate the form for editing
  function editManga(title, chapter, notes, maxChapters) {
    document.getElementById('title').value = title;
    document.getElementById('chapter').value = chapter;
    document.getElementById('notes').value = notes;
    if (maxChapters) {
      document.getElementById('max_chapters').value = maxChapters;
    } else {
      document.getElementById('max_chapters').value = '';
    }
    document.getElementById('title').focus();
    window.scrollTo({top: 0, behavior: 'smooth'});
  }
  
  // Helper function to edit manga from data attributes
  function editMangaFromData(button) {
    const title = button.getAttribute('data-title');
    const chapter = button.getAttribute('data-chapter');
    const notes = button.getAttribute('data-notes');
    const maxChapters = button.getAttribute('data-max');
    
    editManga(title, chapter, notes, maxChapters);
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
