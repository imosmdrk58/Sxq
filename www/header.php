<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
?>
<header>
  <div class="container">
    <a href="index.php" class="logo">
      <img src="assets/images/logo.png" alt="MangaTracker Logo">
    </a>
    <nav>
      <a href="index.php"><i class="fas fa-home"></i> Home</a>
      <a href="manga.php"><i class="fas fa-book"></i> My Manga</a>
      <a href="browse.php"><i class="fas fa-search"></i> Browse Manga</a>
      <a href="comment.php"><i class="far fa-comments"></i> Guestbook</a>
      <?php if (empty($_SESSION['user_id'])): ?>
        <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
        <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
      <?php else: ?>
        <div style="position: relative; display: inline-block;" class="user-menu">
          <a href="#" style="display: flex; align-items: center; gap: 0.5rem;">
            <i class="fas fa-user-circle"></i> 
            <?= htmlspecialchars($_SESSION['username']) ?>
            <i class="fas fa-caret-down" style="font-size: 0.8rem;"></i>
          </a>
          <div style="position: absolute; top: 100%; right: 0; background: white; border-radius: 6px; box-shadow: 0 2px 15px rgba(0,0,0,0.15); min-width: 160px; margin-top: 0.5rem; display: none; z-index: 100;" class="dropdown-menu">
            <a href="manga.php" style="color: #333; padding: 0.75rem 1rem; display: block; border-bottom: 1px solid #eee;">
              <i class="fas fa-book"></i> My Collection
            </a>
            <a href="logout.php" style="color: #333; padding: 0.75rem 1rem; display: block;">
              <i class="fas fa-sign-out-alt"></i> Sign Out
            </a>
          </div>
        </div>
      <?php endif; ?>
    </nav>
  </div>
  
  <script>
    // Simple dropdown menu
    document.addEventListener('DOMContentLoaded', function() {
      const userMenu = document.querySelector('.user-menu');
      if (userMenu) {
        userMenu.addEventListener('click', function(e) {
          e.preventDefault();
          const dropdown = this.querySelector('.dropdown-menu');
          if (dropdown.style.display === 'block') {
            dropdown.style.display = 'none';
          } else {
            dropdown.style.display = 'block';
          }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
          if (!userMenu.contains(e.target)) {
            userMenu.querySelector('.dropdown-menu').style.display = 'none';
          }
        });
      }
    });
  </script>
</header>
