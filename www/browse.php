<?php
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/api/manga_api.php';

// Handle search
$searchResults = [];
$searchError = '';
$searchTerm = '';
$apiSource = isset($_GET['api_source']) ? $_GET['api_source'] : 'auto';

if (isset($_GET['search']) && !empty($_GET['search'])) {
  $searchTerm = trim($_GET['search']);
  
  // Handle API source selection
  if ($apiSource === 'anilist') {
    // Force using Anilist API
    define('USE_RAPIDAPI', true);
    $results = searchMangaByTitleAnilist($searchTerm);
  } else if ($apiSource === 'jikan') {
    // Force using Jikan API
    if (!defined('USE_RAPIDAPI')) {
      define('USE_RAPIDAPI', false);
    }
    // URL encode the title for the API request
    $encodedTitle = urlencode($searchTerm);
    
    // Construct the API URL for searching manga
    $apiUrl = "https://api.jikan.moe/v4/manga?q={$encodedTitle}&limit=10";
    
    // Try to fetch from Jikan directly
    $jikanResponse = null;
    $apiError = null;
    
    if (function_exists('curl_init')) {
      // Use curl code...
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $apiUrl);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, 30);
      curl_setopt($ch, CURLOPT_USERAGENT, 'MangaTracker/1.0');
      
      $jikanResponse = curl_exec($ch);
      
      if (curl_errno($ch)) {
        $apiError = 'cURL error: ' . curl_error($ch);
      }
      
      curl_close($ch);
    } else if (ini_get('allow_url_fopen')) {
      // Use file_get_contents fallback...
      $opts = [
        'http' => [
          'method' => 'GET',
          'header' => ['User-Agent: MangaTracker/1.0'],
          'timeout' => 30
        ]
      ];
      
      $context = stream_context_create($opts);
      $jikanResponse = @file_get_contents($apiUrl, false, $context);
      
      if ($jikanResponse === false) {
        $apiError = 'file_get_contents failed';
      }
    }
    
    if ($jikanResponse) {
      $results = json_decode($jikanResponse, true);
      // Mark the source
      if (isset($results['data']) && is_array($results['data'])) {
        foreach ($results['data'] as &$manga) {
          $manga['api_source'] = 'jikan';
        }
      }
    } else {
      // If failed, use mock data
      $results = generateMockMangaData($searchTerm);
      $results['api_errors'] = ['Failed to fetch from Jikan: ' . $apiError];
    }
  } else {
    // Auto mode - try all APIs in sequence
    $results = searchMangaByTitle($searchTerm);
  }
  
  if (isset($results['error'])) {
    $searchError = $results['error'];
  } else {
    $searchResults = isset($results['data']) ? $results['data'] : [];
  }
  
  // Display API errors if any
  if (isset($results['api_errors']) && !empty($results['api_errors'])) {
    $searchError = 'API Errors: ' . implode(', ', $results['api_errors']);
  }
}

// Handle adding manga from API to bookmarks
if (isset($_POST['action']) && $_POST['action'] === 'add_from_api' && !empty($_SESSION['user_id'])) {
  $title = isset($_POST['title']) ? $_POST['title'] : '';
  $chapter = isset($_POST['chapter']) ? $_POST['chapter'] : '1';
  $image = isset($_POST['image']) ? $_POST['image'] : '';
  $description = isset($_POST['description']) ? $_POST['description'] : '';
  $apiId = isset($_POST['api_id']) ? $_POST['api_id'] : '';
  $apiSource = isset($_POST['api_source']) ? $_POST['api_source'] : '';
  
  if (!empty($title)) {
    // Store in bookmarks
    $stmt = $pdo->prepare("
      INSERT INTO bookmarks (user_id, manga_title, last_chapter, notes, cover_image, description, api_id, api_source)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
      ON DUPLICATE KEY UPDATE
        last_chapter = VALUES(last_chapter),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    $notes = "Added from " . ($apiSource ? ucfirst($apiSource) : "manga database") . ".";
    $stmt->execute([$_SESSION['user_id'], $title, $chapter, $notes, $image, $description, $apiId, $apiSource]);
    
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
    }    .manga-description {
      color: var(--gray);
      margin-bottom: 1rem;
      max-height: 4.8em; /* Approximately 3 lines of text */
      overflow: hidden;
      position: relative;
      text-overflow: ellipsis;
      /* For browsers that support it */
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
    }
    .manga-genres {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-bottom: 1rem;
    }    .manga-genre {
      background: rgba(67, 97, 238, 0.1);
      color: var(--primary);
      padding: 0.3rem 0.6rem;
      border-radius: 30px;
      font-size: 0.8rem;
    }
    .api-badge {
      display: inline-block;
      font-size: 0.7rem;
      padding: 0.2rem 0.5rem;
      border-radius: 4px;
      margin-left: 0.5rem;
      vertical-align: middle;
      font-weight: normal;
    }
    .api-badge.anilist {
      background-color: #02a9ff;
      color: white;
    }
    .api-badge.jikan {
      background-color: #2e51a2;
      color: white;
    }
    .api-badge.mock {
      background-color: #ff6b6b;
      color: white;
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
      <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem;">
        <div style="display: flex; gap: 1rem;">
          <input type="text" name="search" placeholder="Search for manga titles..." value="<?= htmlspecialchars($searchTerm) ?>" style="flex: 1;" required>
          <button type="submit" class="btn"><i class="fas fa-search"></i> Search</button>
        </div>
        <div style="display: flex; gap: 1rem; align-items: center;">
          <label style="font-size: 0.9rem; color: #666;">API Source:</label>
          <div style="display: flex; gap: 0.5rem;">
            <label style="display: flex; align-items: center; gap: 0.3rem; cursor: pointer;">
              <input type="radio" name="api_source" value="auto" <?= (!isset($_GET['api_source']) || $_GET['api_source'] === 'auto') ? 'checked' : '' ?>>
              <span>Auto (Default)</span>
            </label>
            <label style="display: flex; align-items: center; gap: 0.3rem; cursor: pointer;">
              <input type="radio" name="api_source" value="anilist" <?= (isset($_GET['api_source']) && $_GET['api_source'] === 'anilist') ? 'checked' : '' ?>>
              <span>Anilist</span>
            </label>
            <label style="display: flex; align-items: center; gap: 0.3rem; cursor: pointer;">
              <input type="radio" name="api_source" value="jikan" <?= (isset($_GET['api_source']) && $_GET['api_source'] === 'jikan') ? 'checked' : '' ?>>
              <span>Jikan</span>
            </label>
          </div>
        </div>
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
        <?php foreach ($searchResults as $manga): ?>          <div class="manga-result">
            <img src="<?= htmlspecialchars(isset($manga['images']['jpg']['image_url']) ? $manga['images']['jpg']['image_url'] : 'assets/images/no-cover.png') ?>" alt="<?= htmlspecialchars($manga['title']) ?>" class="manga-cover">
            
            <div class="manga-info">              <h3 class="manga-title">
                <?= htmlspecialchars($manga['title']) ?>
                <?php if (!empty($manga['api_source'])): ?>
                  <span class="api-badge <?= htmlspecialchars($manga['api_source']) ?>"><?= htmlspecialchars(ucfirst($manga['api_source'])) ?></span>
                <?php endif; ?>
              </h3>
              
              <div class="manga-meta">
                <span><i class="fas fa-star"></i> <?= number_format(isset($manga['score']) ? $manga['score'] : 0, 1) ?>/10</span>
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
                <?php if (!empty($_SESSION['user_id'])): ?>                  <form method="post">
                    <input type="hidden" name="action" value="add_from_api">
                    <input type="hidden" name="title" value="<?= htmlspecialchars($manga['title']) ?>">
                    <input type="hidden" name="image" value="<?= htmlspecialchars(isset($manga['images']['jpg']['image_url']) ? $manga['images']['jpg']['image_url'] : '') ?>">
                    <input type="hidden" name="description" value="<?= htmlspecialchars(isset($manga['synopsis']) ? $manga['synopsis'] : '') ?>">
                    <input type="hidden" name="chapter" value="1">
                    <input type="hidden" name="api_id" value="<?= htmlspecialchars(isset($manga['api_id']) ? $manga['api_id'] : '') ?>">
                    <input type="hidden" name="api_source" value="<?= htmlspecialchars(isset($manga['api_source']) ? $manga['api_source'] : '') ?>">
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
