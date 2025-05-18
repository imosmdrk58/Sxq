<?php
require_once __DIR__.'/config/config.php';
require_once __DIR__.'/api/simplified_manga_api.php';

/**
 * Function to get manga cover image from title
 */
function getMangaCoverFromAPI($title) {
    try {
        $results = searchMangaByTitle($title, 1);
        if (!empty($results) && isset($results[0]['images']['jpg']['image_url'])) {
            return $results[0]['images']['jpg']['image_url'];
        }
    } catch (Exception $e) {
        // Silent fail - just return empty if API fails
    }
    return '';
}

if (empty($_SESSION['user_id'])) {
  $_SESSION['redirect_after_login'] = 'manga_detail.php?id=' . (isset($_GET['id']) ? $_GET['id'] : '');
  header('Location: login.php');
  exit;
}

// Check if we have manga ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
  header('Location: manga.php');
  exit;
}

$bookmark_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Get the manga details
$stmt = $pdo->prepare("
  SELECT * FROM bookmarks 
  WHERE id = ? AND user_id = ?
");
$stmt->execute([$bookmark_id, $user_id]);
$manga = $stmt->fetch();

if (!$manga) {
  header('Location: manga.php');
  exit;
}

// Handle chapter action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action']) && $_POST['action'] === 'mark_chapter') {
    $chapter_number = $_POST['chapter_number'];
    $status = $_POST['status'] === 'read' ? 1 : 0;
    $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';
    $readDate = $status ? date('Y-m-d H:i:s') : null;
    $readDateFormatted = $status ? date('d-m-Y') : null;
    $chapter_id = null;
    
    try {
      // Check if this chapter already exists in the database
      $checkStmt = $pdo->prepare("
        SELECT id FROM manga_chapters 
        WHERE bookmark_id = ? AND user_id = ? AND chapter_number = ?
      ");
      $checkStmt->execute([$bookmark_id, $user_id, $chapter_number]);
      $existing = $checkStmt->fetch();
      
      if ($existing) {
        // Update existing chapter
        $updateStmt = $pdo->prepare("
          UPDATE manga_chapters 
          SET is_read = ?, read_date = ? 
          WHERE id = ?
        ");
        $updateStmt->execute([
          $status,
          $readDate,
          $existing['id']
        ]);
        $chapter_id = $existing['id'];
      } else {
        // Insert new chapter
        $insertStmt = $pdo->prepare("
          INSERT INTO manga_chapters 
          (bookmark_id, user_id, chapter_number, is_read, read_date) 
          VALUES (?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([
          $bookmark_id,
          $user_id,
          $chapter_number,
          $status,
          $readDate
        ]);
        $chapter_id = $pdo->lastInsertId();
      }
      
      // If marking as read and it's higher than current last_chapter, update bookmark
      if ($status && $chapter_number > $manga['last_chapter']) {
        $updateBookmarkStmt = $pdo->prepare("
          UPDATE bookmarks 
          SET last_chapter = ?, updated_at = CURRENT_TIMESTAMP 
          WHERE id = ?
        ");
        $updateBookmarkStmt->execute([$chapter_number, $bookmark_id]);
        
        // Also log this read in the reads_log table
        $pdo->prepare("
          INSERT INTO reads_log (user_id, manga_title, chapter, read_at)
          VALUES (?, ?, ?, NOW())
        ")->execute([$user_id, $manga['manga_title'], $chapter_number]);
      }
      
      $message = "Chapter " . htmlspecialchars($chapter_number) . " marked as " . 
                ($status ? "read" : "unread");
      
      // Handle AJAX and non-AJAX responses differently
      if ($isAjax) {
        // Return JSON response for AJAX requests
        header('Content-Type: application/json');
        echo json_encode([
          'success' => true,
          'message' => $message,
          'is_read' => $status ? true : false,
          'chapter_id' => $chapter_id,
          'chapter_number' => $chapter_number,
          'read_date' => $readDate,
          'read_date_formatted' => $readDateFormatted
        ]);
        exit;
      } else {
        // Success message for regular form submission
        $success_message = $message;
        
        // Use POST-Redirect-GET pattern to avoid form resubmission issues
        // This prevents the "Document Expired" error when using back button
        header("Location: manga_detail.php?id=" . $bookmark_id . "&success=chapter_" . ($status ? "read" : "unread"));
        exit;
      }
    } catch (PDOException $e) {
      $error_message = "Database error: " . $e->getMessage();
      
      if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
          'success' => false,
          'error' => $e->getMessage()
        ]);
        exit;
      }
    }} elseif (isset($_POST['action']) && $_POST['action'] === 'update_max') {
    // Update max chapters
    $max_chapters = (int)$_POST['max_chapters'];
    
    try {
      $updateStmt = $pdo->prepare("
        UPDATE bookmarks 
        SET max_chapters = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
      ");
      $updateStmt->execute([$max_chapters, $bookmark_id]);
      
      // Use POST-Redirect-GET pattern to avoid form resubmission issues
      header("Location: manga_detail.php?id=" . $bookmark_id . "&success=max_updated");
      exit;
    } catch (PDOException $e) {
      $error_message = "Database error: " . $e->getMessage();
    }
  }
}

// Get all read chapters for this manga
$chapterStmt = $pdo->prepare("
  SELECT * FROM manga_chapters 
  WHERE bookmark_id = ? AND user_id = ?
  ORDER BY CAST(chapter_number AS DECIMAL) ASC
");
$chapterStmt->execute([$bookmark_id, $user_id]);
$read_chapters = $chapterStmt->fetchAll();

// Convert to associative array for easier lookup
$read_chapters_map = [];
foreach ($read_chapters as $chapter) {
  $read_chapters_map[$chapter['chapter_number']] = $chapter;
}

// Determine the maximum chapter number to display
$max_to_show = $manga['max_chapters'] ?? (int)$manga['last_chapter'] + 5;

// Handle success messages from redirects (POST-Redirect-GET pattern)
if (isset($_GET['success'])) {
  if ($_GET['success'] == 'chapter_read') {
    $success_message = "Chapter marked as read";
  } elseif ($_GET['success'] == 'chapter_unread') {
    $success_message = "Chapter marked as unread";
  } elseif ($_GET['success'] == 'max_updated') {
    $success_message = "Maximum chapters updated";
  }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($manga['manga_title']) ?> - Manga Tracker</title>  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/style-fixes.css">  <style>
    .manga-header {
      display: flex;
      align-items: flex-start;
      gap: 20px;
      margin-bottom: 30px;
      flex-wrap: wrap;
    }
    .manga-header-img {
      width: 180px;
      min-width: 180px;
      height: 260px;
      object-fit: cover;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .manga-info {
      flex-grow: 1;
      flex-basis: 400px;
      min-width: 250px; /* Better support for narrow screens */
    }    .manga-description, .manga-notes {
      margin-top: 20px;
      padding: 18px;
      background: #f5f5f5;
      border-radius: 8px;
      line-height: 1.7; /* Improved line height for readability */
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      max-height: none; /* Allow content to expand */
    }
    .manga-description h3, .manga-notes h3 {
      margin-bottom: 12px; /* Add space below headings */
      color: #333;
      border-bottom: 1px solid #e0e0e0;
      padding-bottom: 8px;
    }
    .manga-description p, .manga-notes p, .description-content, .notes-content {
      max-width: 100%;
      white-space: normal;
      word-break: normal;
      margin: 0;
      padding: 0;
      font-size: 1.02rem; /* Slightly larger font for readability */
      color: #222; /* Darker text for better contrast */
      overflow: visible; /* Ensure content is visible */
    }
    .chapter-actions {
      margin-top: 30px;
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      align-items: center;
    }    .set-max-form {
      display: inline-flex;
      align-items: center;
      flex-wrap: nowrap;
      gap: 10px;
      min-width: 210px; /* Reduced to prevent overflow */
      margin-right: 10px;
      background: rgba(0,0,0,0.02);
      padding: 8px 12px;
      border-radius: 6px;
    }
    .set-max-form label {
      white-space: nowrap;
      margin-bottom: 0; /* Remove any bottom margin */
      font-weight: 500;
      margin-right: 2px;
    }
    .set-max-form input {
      width: 80px;
      padding: 6px 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
      height: 32px; /* Match button height */
      margin-bottom: 0; /* Remove any bottom margin */
      vertical-align: middle; /* Align with other elements */
      box-shadow: inset 0 1px 2px rgba(0,0,0,.05);
    }
    .set-max-form button {
      white-space: nowrap;
    }
    .chapter-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
      gap: 12px;
      margin-top: 25px;
      width: 100%;
      padding: 5px;
    }
    
    /* Additional enhancements for manga details page */    .actions-container {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      width: 100%;
      align-items: center;
    }    /* Fix button alignment */
    .btn.btn-sm {
      height: 32px;
      display: inline-flex;
      align-items: center;
      padding: 0 12px;
      vertical-align: middle;
      border-radius: 4px;
      border: none;
      cursor: pointer;
      font-weight: 500;
      transition: all 0.2s ease;
    }
    
    .btn.btn-sm:hover {
      transform: translateY(-1px);
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .btn-find-manga {
      background-color: #4361ee;
      color: white;
      padding: 6px 12px;
      white-space: nowrap;
    }
    
    .btn-find-manga:hover {
      background-color: #3f37c9;
    }
    
    h2 {
      margin-top: 40px;
      margin-bottom: 15px;
      border-bottom: 2px solid #f0f0f0;
      padding-bottom: 8px;
    }    .description-content, .notes-content {
      font-size: 1.02rem;
      line-height: 1.7;
      text-align: left;
      hyphens: auto;
      padding: 15px;
      background-color: #ffffff;
      border-radius: 6px;
      margin-top: 10px;
      max-height: 500px; /* Increased max height */
      overflow-y: auto; /* Add scrollbar for long content */
      border: 1px solid #e0e0e0;
      white-space: pre-wrap; /* Preserve line breaks and spaces */
      word-break: break-word; /* Break long words */
      scrollbar-width: thin; /* Firefox */
      scrollbar-color: #ccc transparent; /* Firefox */
    }
    
    /* Custom scrollbar for WebKit browsers (Chrome, Safari) */
    .description-content::-webkit-scrollbar, 
    .notes-content::-webkit-scrollbar {
      width: 8px;
    }
    
    .description-content::-webkit-scrollbar-track, 
    .notes-content::-webkit-scrollbar-track {
      background: transparent;
    }
    
    .description-content::-webkit-scrollbar-thumb, 
    .notes-content::-webkit-scrollbar-thumb {
      background-color: #ccc;
      border-radius: 4px;
    }
    
    /* Improve chapter grid heading */
    .chapter-heading {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      margin-bottom: 15px;
    }
    
    .chapter-heading h2 {
      margin-top: 0;
      margin-bottom: 0;
      border: none;
      padding: 0;
    }
    
    .chapter-info {
      color: #666;
      font-size: 0.9rem;
    }
    /* Notification styling */
    .notification {
      position: fixed;
      bottom: 20px;
      right: 20px;
      padding: 12px 20px;
      border-radius: 6px;
      box-shadow: 0 3px 10px rgba(0,0,0,0.2);
      transform: translateY(100px);
      opacity: 0;
      transition: all 0.3s ease;
      z-index: 1000;
      font-weight: 500;
    }
    .notification.show {
      transform: translateY(0);
      opacity: 1;
    }
    .notification-success {
      background-color: #d4edda;
      color: #155724;
      border-left: 4px solid #28a745;
    }
    .notification-error {
      background-color: #f8d7da;
      color: #721c24;
      border-left: 4px solid #dc3545;
    }
    /* Responsive fixes */
    @media (max-width: 576px) {
      .manga-header {
        gap: 15px;
      }
      .manga-header-img {
        width: 140px;
        min-width: 140px;
        height: 210px;
      }
      .chapter-grid {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 10px;
      }
      .set-max-form {
        width: 100%;
        margin-bottom: 10px;
      }
    }</style>
  <script>
    // Function to toggle chapter status with AJAX
    function toggleChapterStatus(chapterId, chapterNumber, currentStatus) {
      // Get the form elements
      const form = document.getElementById('chapter-form-' + chapterId);
      const statusInput = document.getElementById('status-' + chapterId);
      const chapterBox = document.getElementById('chapter-box-' + chapterId);
      const chapterBtn = document.getElementById('chapter-btn-' + chapterId);
      
      // Get check icon element if it exists
      let checkIcon = chapterBtn.querySelector('.fa-check-circle');
      
      // Determine new status (opposite of current)
      const newStatus = currentStatus === '1' ? 'unread' : 'read';
      
      // Create FormData object to send form data
      const formData = new FormData();
      formData.append('action', 'mark_chapter');
      formData.append('chapter_number', chapterNumber);
      formData.append('status', newStatus);
      formData.append('ajax', '1'); // Add flag to indicate AJAX request
      
      // Send AJAX request
      fetch('manga_detail.php?id=<?= $bookmark_id ?>', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())      .then(data => {
        if (data.success) {
          // Toggle appearance based on new status returned from server
          if (data.is_read) {
            // Mark as read
            chapterBox.classList.remove('chapter-unread');
            chapterBox.classList.add('chapter-read');
            statusInput.value = 'unread'; // NEXT click will make it unread
            
            // Add check icon if it doesn't exist
            if (!checkIcon) {
              const titleDiv = chapterBtn.querySelector('.chapter-title');
              const newIcon = document.createElement('i');
              newIcon.className = 'fas fa-check-circle';
              newIcon.style.marginLeft = '4px';
              newIcon.style.fontSize = '0.8em';
              newIcon.style.color = '#28a745';
              titleDiv.appendChild(newIcon);
            }
            
            // Add read date if provided
            if (data.read_date) {
              let dateDiv = document.getElementById('date-' + chapterId);
              if (!dateDiv) {
                dateDiv = document.createElement('div');
                dateDiv.className = 'read-date';
                dateDiv.id = 'date-' + chapterId;
                chapterBtn.appendChild(dateDiv);
              }
              dateDiv.textContent = data.read_date_formatted;
            }
          } else {
            // Mark as unread
            chapterBox.classList.remove('chapter-read');
            chapterBox.classList.add('chapter-unread');
            statusInput.value = 'read'; // NEXT click will make it read
            
            // Remove check icon if it exists
            if (checkIcon) {
              checkIcon.remove();
            }
            
            // Remove read date if exists
            const dateDiv = document.getElementById('date-' + chapterId);
            if (dateDiv) {
              dateDiv.remove();
            }
          }
          
          // Show a temporary success notification
          showNotification(data.message);
        } else {
          // Show error
          showNotification('Error: ' + data.error, 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showNotification('Error processing request', 'error');
      });
      
      return false; // Prevent form submission
    }
    
    // Function to show temporary notification
    function showNotification(message, type = 'success') {
      // Create notification element
      const notification = document.createElement('div');
      notification.className = 'notification ' + (type === 'error' ? 'notification-error' : 'notification-success');
      notification.textContent = message;
      
      // Add to body
      document.body.appendChild(notification);
      
      // Show with animation
      setTimeout(() => {
        notification.classList.add('show');
      }, 10);
      
      // Remove after delay
      setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
          notification.remove();
        }, 300);
      }, 2000);
    }
  </script>
</head>

<body>
  <?php include __DIR__.'/header.php'; ?>
  
  <div class="container">
    <a href="manga.php" class="btn btn-sm" style="margin-bottom: 20px;">
      <i class="fas fa-arrow-left"></i> Terug naar collectie
    </a>
    
    <?php if(isset($success_message)): ?>
      <div style="background: #d4edda; color: #155724; padding: 0.75rem; border-radius: 4px; margin-bottom: 1.5rem;">
        <?= htmlspecialchars($success_message) ?>
      </div>
    <?php endif; ?>
    
    <?php if(isset($error_message)): ?>
      <div style="background: #f8d7da; color: #721c24; padding: 0.75rem; border-radius: 4px; margin-bottom: 1.5rem;">
        <?= htmlspecialchars($error_message) ?>
      </div>
    <?php endif; ?>      <div class="manga-header">
      <?php if(!empty($manga['cover_image'])): ?>
        <img src="<?= htmlspecialchars($manga['cover_image']) ?>" 
             alt="" class="manga-header-img">      <?php else: ?>        <?php 
          // Try to get cover from API
          $apiCover = getMangaCoverFromAPI($manga['manga_title']);
          if (!empty($apiCover)):
        ?>
          <img src="<?= htmlspecialchars($apiCover) ?>" alt="" class="manga-header-img">
        <?php else: ?>
          <div class="manga-header-img" style="background-color: #eee; display: flex; justify-content: center; align-items: center;">
            <i class="fas fa-book" style="font-size: 3rem; color: #ccc;"></i>
          </div>
        <?php endif; ?>
      <?php endif; ?>
      
      <div class="manga-info">
        <h1><?= htmlspecialchars($manga['manga_title']) ?></h1>
          <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px 25px;">
            <div>
              <p><strong>Laatste hoofdstuk gelezen:</strong> <?= htmlspecialchars($manga['last_chapter']) ?></p>
              <p><strong>Maximum hoofdstukken:</strong> <?= $manga['max_chapters'] ? htmlspecialchars($manga['max_chapters']) : 'Niet ingesteld' ?></p>
            </div>
            <div>
              <p><strong>Toegevoegd op:</strong> <?= date('d-m-Y', strtotime($manga['created_at'])) ?></p>
              <p><strong>Laatste update:</strong> <?= date('d-m-Y', strtotime($manga['updated_at'])) ?></p>
            </div>
          </div>
        </div>          <?php if(!empty($manga['description'])): ?>
          <div class="manga-description">
            <h3><i class="fas fa-book-open"></i> Beschrijving</h3>
            <div class="description-content">
              <?= nl2br(htmlspecialchars($manga['description'])) ?>
            </div>
          </div>
        <?php endif; ?>
        
        <?php if(!empty($manga['notes'])): ?>
          <div class="manga-notes">
            <h3><i class="fas fa-sticky-note"></i> Notities</h3>
            <div class="notes-content">
              <?= nl2br(htmlspecialchars($manga['notes'])) ?>
            </div>
          </div>
        <?php endif; ?>          <div class="chapter-actions">
            <div class="actions-container">
              <form method="post" class="set-max-form">
                <input type="hidden" name="action" value="update_max">
                <label for="max_chapters">Max hoofdstukken:</label>
                <input type="number" id="max_chapters" name="max_chapters" value="<?= $manga['max_chapters'] ?? '' ?>" min="1" required>
                <button type="submit" class="btn btn-sm"><i class="fas fa-save"></i> Bijwerken</button>
              </form>
              
              <a href="find_manga.php?id=<?= $bookmark_id ?>" class="btn btn-sm btn-find-manga">
                <i class="fas fa-search"></i> Find Correct Manga
              </a>
            </div>
          </div>
      </div>
    </div>      <div class="chapter-heading">
        <h2>Hoofdstukken</h2>
        <div class="chapter-info">
          <p>Klik op een hoofdstuk om het als gelezen/ongelezen te markeren.</p>
        </div>
      </div>
      
      <div class="chapter-grid">
      <?php for($i = 1; $i <= $max_to_show; $i++): 
        $chapter_num = (string)$i;
        $is_read = false;
        $read_date = null;
        $chapter_id = 'ch-' . $i; // Default ID if no record exists
        
        // Check if this chapter exists in our read chapters map
        if (isset($read_chapters_map[$chapter_num])) {
          $is_read = (bool)$read_chapters_map[$chapter_num]['is_read'];
          $read_date = $is_read ? $read_chapters_map[$chapter_num]['read_date'] : null;
          $chapter_id = $read_chapters_map[$chapter_num]['id'];
        }
        
        $class = $is_read ? 'chapter-read' : 'chapter-unread';
      ?>
        <div id="chapter-box-<?= $chapter_id ?>" class="chapter-box <?= $class ?>">          <form id="chapter-form-<?= $chapter_id ?>" method="post" onsubmit="return toggleChapterStatus('<?= $chapter_id ?>', '<?= $i ?>', '<?= $is_read ? '1' : '0' ?>')">
            <input type="hidden" name="action" value="mark_chapter">
            <input type="hidden" name="chapter_number" value="<?= $i ?>">
            <!-- "status" value is what we want to change TO, not current status -->
            <input type="hidden" id="status-<?= $chapter_id ?>" name="status" value="<?= $is_read ? 'unread' : 'read' ?>">            <button id="chapter-btn-<?= $chapter_id ?>" type="submit">              
              <div class="chapter-title">
                Hoofdstuk <?= $i ?>
                <?php if($is_read): ?>
                  <i class="fas fa-check-circle" style="margin-left: 4px; font-size: 0.8em; color: #28a745;"></i>
                <?php endif; ?>
              </div>
              <?php if($read_date): ?>
                <div id="date-<?= $chapter_id ?>" class="read-date"><?= date('d-m-Y', strtotime($read_date)) ?></div>
              <?php endif; ?>
            </button>
          </form>
        </div>
      <?php endfor; ?>
    </div>
    </div>
  
  <?php include __DIR__.'/footer.php'; ?>
</body>
</html>
