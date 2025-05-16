<?php
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/api/manga_api.php';

// Handle search
$searchResults = [];
$searchError = '';
$searchTerm = '';

if (isset($_GET['search']) && !empty($_GET['search'])) {
  $searchTerm = trim($_GET['search']);
  
  // Call the API function to search for manga
  $results = searchMangaByTitle($searchTerm);
  
  if (isset($results['error'])) {
    $searchError = $results['error'];
  } else {
    $searchResults = $results['data'] ?? [];
  }
}

// Handle adding manga from API to bookmarks
if (isset($_POST['action']) && $_POST['action'] === 'add_from_api' && !empty($_SESSION['user_id'])) {
  $title = $_POST['title'] ?? '';
  $chapter = $_POST['chapter'] ?? '1';
  $image = $_POST['image'] ?? '';
  $description = $_POST['description'] ?? '';
  
  if (!empty($title)) {
    // Store in bookmarks
    $stmt = $pdo->prepare("
      INSERT INTO bookmarks (user_id, manga_title, last_chapter, notes, cover_image, description)
      VALUES (?, ?, ?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE
        last_chapter = VALUES(last_chapter),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    $notes = "Added from manga database.";
    $stmt->execute([$_SESSION['user_id'], $title, $chapter, $notes, $image, $description]);
    
    // Log the read
    $pdo->prepare("
      INSERT INTO reads_log (user_id, manga_title, chapter, read_at)
      VALUES (?, ?, ?, NOW())
    ")->execute([$_SESSION['user_id'], $title, $chapter]);
    
    // Redirect to prevent form resubmission
    header('Location: manga.php?added=1');
    exit;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Browse Manga – Manga Tracker</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .manga-result {
      display: flex;
      margin-bottom: 2rem;
      background: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    }
    .manga-cover {
      width: 120px;
      height: 180px;
      object-fit: cover;
    }
    .manga-info {
      padding: 1.2rem;
      flex: 1;
    }
    .manga-title {
      font-size: 1.4rem;
      margin-bottom: 0.5rem;
      color: var(--dark);
    }
    .manga-meta {
      display: flex;
      gap: 1.5rem;
      margin-bottom: 0.8rem;
      color: var(--gray);
      font-size: 0.9rem;
    }
    .manga-description {
      color: var(--gray);
      margin-bottom: 1rem;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .manga-genres {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-bottom: 1rem;
    }
    .manga-genre {
      background: rgba(67, 97, 238, 0.1);
      color: var(--primary);
      padding: 0.3rem 0.6rem;
      border-radius: 30px;
      font-size: 0.8rem;
    }
    .manga-actions {
      margin-top: auto;
    }
    @media (max-width: 768px) {
      .manga-result {
        flex-direction: column;
      }
      .manga-cover {
        width: 100%;
        height: 200px;
      }
    }
  </style>
</head>
<body>
  <?php include __DIR__.'/header.php'; ?>
  
  <div class="container">
    <h1>Browse Manga Database</h1>
    <p>Search our extensive database of manga titles and add them to your collection!</p>
    
    <form method="get" class="search-form">
      <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
        <input type="text" name="search" placeholder="Search for manga titles..." value="<?= htmlspecialchars($searchTerm) ?>" style="flex: 1;" required>
        <button type="submit" class="btn"><i class="fas fa-search"></i> Search</button>
      </div>
    </form>
    
    <?php if (!empty($searchError)): ?>
      <div style="background: #ffebeb; color: #c62828; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
        <p style="margin: 0;"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($searchError) ?></p>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($searchResults)): ?>
      <h2>Search Results</h2>
      <p class="section-subtitle">Found <?= count($searchResults) ?> manga titles matching "<?= htmlspecialchars($searchTerm) ?>"</p>
      
      <div class="manga-results">
        <?php foreach ($searchResults as $manga): ?>
          <div class="manga-result">
            <img src="<?= htmlspecialchars($manga['images']['jpg']['image_url'] ?? 'assets/images/no-cover.png') ?>" alt="<?= htmlspecialchars($manga['title']) ?>" class="manga-cover">
            
            <div class="manga-info">
              <h3 class="manga-title"><?= htmlspecialchars($manga['title']) ?></h3>
              
              <div class="manga-meta">
                <span><i class="fas fa-star"></i> <?= number_format($manga['score'] ?? 0, 1) ?>/10</span>
                <?php if (!empty($manga['chapters'])): ?>
                  <span><i class="fas fa-book-open"></i> <?= $manga['chapters'] ?> chapters</span>
                <?php endif; ?>
                <?php if (!empty($manga['published']['string'])): ?>
                  <span><i class="far fa-calendar-alt"></i> <?= htmlspecialchars($manga['published']['string']) ?></span>
                <?php endif; ?>
              </div>
              
              <?php if (!empty($manga['genres'])): ?>
                <div class="manga-genres">
                  <?php foreach (array_slice($manga['genres'], 0, 5) as $genre): ?>
                    <span class="manga-genre"><?= htmlspecialchars($genre['name']) ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              
              <div class="manga-description">
                <?= !empty($manga['synopsis']) ? htmlspecialchars($manga['synopsis']) : 'No description available.' ?>
              </div>
              
              <div class="manga-actions">
                <?php if (!empty($_SESSION['user_id'])): ?>
                  <form method="post">
                    <input type="hidden" name="action" value="add_from_api">
                    <input type="hidden" name="title" value="<?= htmlspecialchars($manga['title']) ?>">
                    <input type="hidden" name="image" value="<?= htmlspecialchars($manga['images']['jpg']['image_url'] ?? '') ?>">
                    <input type="hidden" name="description" value="<?= htmlspecialchars($manga['synopsis'] ?? '') ?>">
                    <input type="hidden" name="chapter" value="1">
                    <button type="submit" class="btn"><i class="fas fa-plus"></i> Add to My Collection</button>
                  </form>
                <?php else: ?>
                  <a href="login.php" class="btn"><i class="fas fa-sign-in-alt"></i> Login to Add</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php elseif (!empty($searchTerm)): ?>
      <div style="text-align: center; padding: 3rem 0;">
        <i class="fas fa-search" style="font-size: 3rem; color: var(--gray); margin-bottom: 1rem;"></i>
        <h3>No results found</h3>
        <p>Try searching with different keywords or check your spelling.</p>
      </div>
    <?php endif; ?>
  </div>
  
  <?php include __DIR__.'/footer.php'; ?>
</body>
</html>
