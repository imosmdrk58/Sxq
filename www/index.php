<?php require_once __DIR__.'/config/config.php'; 

// Get popular manga (aggregated, no personal information exposed)
$popularStmt = $pdo->query("
  SELECT manga_title, COUNT(*) as bookmark_count 
  FROM bookmarks 
  GROUP BY manga_title 
  ORDER BY bookmark_count DESC, manga_title ASC
  LIMIT 3
");
$popularManga = $popularStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home – Manga Tracker</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <?php include __DIR__.'/header.php'; ?>
  
  <section class="hero">
    <div class="container">
      <h1>Welcome to Manga Tracker</h1>
      <p>Keep track of every chapter you read and never lose your place again. Join our community of manga enthusiasts!</p>
      <div class="mt-2">
        <?php if (empty($_SESSION['user_id'])): ?>
          <a href="register.php" class="btn">Get Started</a>
          <a href="login.php" class="btn btn-secondary" style="margin-left: 10px;">Sign In</a>
        <?php else: ?>
          <a href="manga.php" class="btn">View My Manga</a>
          <a href="browse.php" class="btn btn-secondary" style="margin-left: 10px;">Browse Manga Database</a>
        <?php endif; ?>
      </div>
    </div>
  </section>
  
  <div class="container">
    <h2>How It Works</h2>
    <div class="card-grid">
      <div class="card">
        <div class="card-body">
          <h3 class="card-title"><i class="fas fa-user"></i> Create Account</h3>
          <p class="card-text">Register for a free account to start tracking your manga collection and reading progress.</p>
        </div>
      </div>
      <div class="card">
        <div class="card-body">
          <h3 class="card-title"><i class="fas fa-book"></i> Add Manga</h3>
          <p class="card-text">Add your favorite manga titles and track which chapter you left off reading.</p>
        </div>
      </div>
      <div class="card">
        <div class="card-body">
          <h3 class="card-title"><i class="fas fa-bookmark"></i> Track Progress</h3>
          <p class="card-text">Update your progress as you read and never lose track of where you stopped.</p>
        </div>
      </div>
    </div>
    
    <h2 class="mt-2">Popular Manga</h2>
    <p class="section-subtitle">Most bookmarked titles by our community</p>
    <div class="card-grid">
      <?php foreach($popularManga as $manga): 
        // Create URL-friendly manga title for placeholder image
        $imgText = urlencode(str_replace(' ', '+', $manga['manga_title']));
      ?>
      <div class="card">
        <img src="https://via.placeholder.com/400x250?text=<?= $imgText ?>" alt="<?= htmlspecialchars($manga['manga_title']) ?>" class="card-img">
        <div class="card-body">
          <h3 class="card-title"><?= htmlspecialchars($manga['manga_title']) ?></h3>
          <p class="card-text">
            <i class="fas fa-bookmark"></i> <?= $manga['bookmark_count'] ?> reader<?= $manga['bookmark_count'] > 1 ? 's' : '' ?> tracking this manga
          </p>
          <?php if (!empty($_SESSION['user_id'])): ?>
          <a href="manga.php" class="btn btn-sm">Track This Manga</a>
          <?php else: ?>
          <a href="register.php" class="btn btn-sm">Sign Up to Track</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      
      <?php if (count($popularManga) == 0): ?>
      <div class="card">
        <div class="card-body text-center">
          <h3 class="card-title">No Manga Yet</h3>
          <p class="card-text">Be the first to bookmark your favorite manga!</p>
        </div>
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
      if (linkPage === currentPage || (currentPage === '' && linkPage === 'index.php')) {
        link.classList.add('active');
      }
    });
  });
  </script>
</body>
</html>
