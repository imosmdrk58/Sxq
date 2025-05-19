<?php
// filepath: c:\Users\ziggy\source\repos\Pillendoosje\www\find_manga.php
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/utils/manga_utils.php';

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
  $_SESSION['redirect_after_login'] = 'manga.php';
  header('Location: login.php');
  exit;
}

// Check if we have the manga ID to link
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header('Location: manga.php');
  exit;
}

$bookmark_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Get the manga details
$stmt = $pdo->prepare("SELECT * FROM bookmarks WHERE id = ? AND user_id = ?");
$stmt->execute([$bookmark_id, $user_id]);
$manga = $stmt->fetch();

if (!$manga) {
  header('Location: manga.php');
  exit;
}

// Handle manga linking
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action']) && $_POST['action'] === 'link_manga') {
    // Get data from selected manga
    $selectedIndex = (int)$_POST['selected_manga'];
    
    if (isset($_POST['search_results']) && !empty($_POST['search_results'])) {
      $searchResults = json_decode($_POST['search_results'], true);
      
      if (isset($searchResults[$selectedIndex])) {
        $selected = $searchResults[$selectedIndex];
        
        // Extract the data to update
        $coverImage = null;
        if (isset($selected['images']['jpg']['image_url'])) {
          $coverImage = $selected['images']['jpg']['image_url'];
        }
        
        $description = null;
        if (isset($selected['synopsis']) && $selected['synopsis'] != 'No description available') {
          $description = $selected['synopsis'];
        }
        
        $apiId = null;
        if (isset($selected['api_id'])) {
          $apiId = $selected['api_id'];
        }
        
        $maxChapters = null;
        if (isset($selected['chapters']) && $selected['chapters'] > 0) {
          $maxChapters = $selected['chapters'];
        }
        
        try {
          // Update the bookmark with the selected manga data
          $updateStmt = $pdo->prepare("
            UPDATE bookmarks
            SET cover_image = ?,
                description = ?,
                api_id = ?,
                api_source = ?,
                max_chapters = COALESCE(?, max_chapters),
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?
          ");
          
          $updateStmt->execute([
            $coverImage,
            $description,
            $apiId,
            'jikan', // API source is always jikan in this case
            $maxChapters,
            $bookmark_id,
            $user_id
          ]);
          
          $message = "Successfully linked \"" . htmlspecialchars($manga['manga_title']) . "\" to the selected manga data!";
        } catch (PDOException $e) {
          $error = "Database error: " . $e->getMessage();
        }
      } else {
        $error = "Invalid selection. Please try again.";
      }
    } else {
      $error = "No search results available. Please try searching again.";
    }
  }
}

// Search for manga if a query is provided
$searchResults = [];
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : $manga['manga_title'];

if (!empty($searchQuery)) {  try {
    $results = searchMangaByTitle($searchQuery, 10);
    if (!empty($results['results'])) {
      // Filter out inappropriate content
      $searchResults = filterInappropriateContent($results['results']);
    }
  } catch (Exception $e) {
    $error = "Search failed: " . $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Find Manga – Manga Tracker</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/style-fixes.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
  <style>
    .manga-search-results {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 1.5rem;
      margin-top: 1.5rem;
    }
    
    .manga-search-item {
      border: 1px solid #eee;
      border-radius: 8px;
      padding: 1rem;
      transition: all 0.3s ease;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .manga-search-item:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .manga-search-item.selected {
      border: 2px solid var(--primary);
      background-color: rgba(var(--primary-rgb), 0.05);
    }
    
    .manga-search-cover {
      width: 100%;
      height: 280px;
      object-fit: cover;
      border-radius: 4px;
      margin-bottom: 0.5rem;
    }
    
    .manga-search-title {
      font-weight: bold;
      margin-bottom: 0.3rem;
      font-size: 1rem;
      line-height: 1.3;
    }
    
    .manga-search-info {
      font-size: 0.85rem;
      color: #666;
      margin-bottom: 1rem;
    }
    
    .manga-search-actions {
      display: flex;
      justify-content: center;
    }
    
    .search-form {
      margin-bottom: 2rem;
      display: flex;
      gap: 0.5rem;
    }
    
    .search-form input {
      flex: 1;
    }
    
    .alert {
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
    }
    
    .alert-success {
      background-color: #d4edda;
      color: #155724;
    }
    
    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
    }
    
    #selectedMangaIndex {
      display: none;
    }
  </style>
</head>
<body>
  <?php include __DIR__.'/header.php'; ?>
  
  <div class="container">
    <div class="breadcrumb">
      <a href="manga.php">My Manga</a> &gt; 
      <a href="manga_detail.php?id=<?= $bookmark_id ?>"><?= htmlspecialchars($manga['manga_title']) ?></a> &gt; 
      Find Manga
    </div>
    
    <h1>Find Correct Manga Data</h1>
    <p>Search for the correct manga to link with your entry "<strong><?= htmlspecialchars($manga['manga_title']) ?></strong>"</p>
    
    <?php if ($message): ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= $message ?>
        <div style="margin-top: 1rem;">
          <a href="manga_detail.php?id=<?= $bookmark_id ?>" class="btn btn-sm">Return to Manga Details</a>
        </div>
      </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
      <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
      </div>
    <?php endif; ?>
    
    <form action="" method="get" class="search-form">
      <input type="hidden" name="id" value="<?= $bookmark_id ?>">
      <input type="text" name="q" placeholder="Search by manga title..." value="<?= htmlspecialchars($searchQuery) ?>">
      <button type="submit" class="btn">Search</button>
    </form>
    
    <?php if (!empty($searchResults)): ?>
      <h2>Search Results</h2>
      <p>Click on a manga to select it, then click "Link Selected Manga" to update your entry with the correct data.</p>
      
      <form action="" method="post" id="linkForm">
        <input type="hidden" name="action" value="link_manga">
        <input type="hidden" name="selected_manga" id="selectedMangaIndex" value="">
        <input type="hidden" name="search_results" value="<?= htmlspecialchars(json_encode($searchResults)) ?>">
        
        <div class="manga-search-results">
          <?php foreach ($searchResults as $index => $result): ?>
            <div class="manga-search-item" data-index="<?= $index ?>" onclick="selectManga(this, <?= $index ?>)">
              <?php if (!empty($result['images']['jpg']['image_url'])): ?>
                <img src="<?= htmlspecialchars($result['images']['jpg']['image_url']) ?>" alt="Cover" class="manga-search-cover">
              <?php else: ?>
                <div class="placeholder-cover manga-search-cover">
                  <i class="fas fa-book" style="font-size: 3rem; color: #ccc;"></i>
                </div>
              <?php endif; ?>
              
              <div class="manga-search-title"><?= htmlspecialchars($result['title']) ?></div>
              
              <div class="manga-search-info">
                <?php if (!empty($result['chapters'])): ?>
                  <div><i class="fas fa-book-open"></i> <?= $result['chapters'] ?> chapters</div>
                <?php endif; ?>
                
                <?php if (!empty($result['score'])): ?>
                  <div><i class="fas fa-star"></i> Rating: <?= $result['score'] ?>/10</div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        
        <div style="margin-top: 2rem; text-align: center;">
          <button type="submit" class="btn btn-primary" id="linkButton" disabled>
            <i class="fas fa-link"></i> Link Selected Manga
          </button>
          <a href="manga_detail.php?id=<?= $bookmark_id ?>" class="btn btn-secondary" style="margin-left: 1rem;">
            <i class="fas fa-arrow-left"></i> Cancel
          </a>
        </div>
      </form>
    <?php else: ?>
      <div style="text-align: center; padding: 2rem; background: #f9f9f9; border-radius: 8px;">
        <i class="fas fa-search" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
        <p>No manga found matching your search. Try a different search term.</p>
      </div>
    <?php endif; ?>
  </div>
  
  <?php include __DIR__.'/footer.php'; ?>
  
  <script>
    function selectManga(element, index) {
      // Remove selected class from all items
      document.querySelectorAll('.manga-search-item').forEach(item => {
        item.classList.remove('selected');
      });
      
      // Add selected class to clicked item
      element.classList.add('selected');
      
      // Store the selected index
      document.getElementById('selectedMangaIndex').value = index;
      
      // Enable the link button
      document.getElementById('linkButton').disabled = false;
    }
  </script>
</body>
</html>
