<?php
// Session is already started in config.php which is included in every page
// No need to start it again here

// Check if we're in the admin directory to adjust paths
$isAdminPage = strpos($_SERVER['PHP_SELF'], '/admin/') !== false;
$baseUrl = $isAdminPage ? '../' : '';
?>
<!-- Inline styles to ensure desktop layout takes priority -->
<style>
/* Make the header more compact */
header {
  padding: 0.3rem 0 !important; /* Reduced vertical padding */
}

@media (min-width: 769px) {
  header .container {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 0.25rem 1rem !important; /* Even smaller padding for container */
  }
    header .main-nav {
    display: flex !important;
    flex-direction: row !important;
    overflow: visible !important;
    max-height: none !important;
    gap: 1.2rem !important;
    width: auto !important;
    margin-left: auto !important; /* This pushes the nav to the right */
  }
  
  /* Add extra space to push the menu further right */
  header .logo {
    margin-right: 2rem !important;
  }
  
  .container {
    padding: 1.5rem !important;
    margin: 2rem auto !important;
  }
  
  header .container {
    padding: 0.5rem 1rem !important;
  }
}
</style>
<header>
  <div class="container">
    <div class="header-flex">
      <a href="<?= $baseUrl ?>index.php" class="logo">
        <img src="<?= $baseUrl ?>assets/images/logo.png" alt="MangaTracker Logo">
      </a>
      
      <!-- Mobile menu toggle button -->
      <button class="mobile-menu-toggle" aria-label="Toggle navigation menu" aria-expanded="false">
        <i class="fas fa-bars" aria-hidden="true"></i>
      </button>
    </div>    
    <nav class="main-nav">
      <a href="<?= $baseUrl ?>index.php"><i class="fas fa-home"></i> <span>Home</span></a>
      <a href="<?= $baseUrl ?>manga.php"><i class="fas fa-book"></i> <span>My Manga</span></a>
      <a href="<?= $baseUrl ?>browse.php"><i class="fas fa-search"></i> <span>Browse Manga</span></a>
      <a href="<?= $baseUrl ?>comment.php"><i class="far fa-comments"></i> <span>Guestbook</span></a>
      <?php if (empty($_SESSION['user_id'])): ?>
        <a href="<?= $baseUrl ?>login.php"><i class="fas fa-sign-in-alt"></i> <span>Login</span></a>
        <a href="<?= $baseUrl ?>register.php"><i class="fas fa-user-plus"></i> <span>Register</span></a>
      <?php else: ?>
        <div class="user-menu">
          <a href="#" class="user-menu-toggle">
            <i class="fas fa-user-circle"></i> 
            <span><?= htmlspecialchars($_SESSION['username']) ?></span>
            <i class="fas fa-caret-down"></i>
          </a>
          <div class="dropdown-menu">
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
            <a href="<?= $isAdminPage ? 'index.php' : 'admin/index.php' ?>">
              <i class="fas fa-shield-alt"></i> Admin Panel
            </a>
            <?php endif; ?>
            <a href="<?= $baseUrl ?>logout.php">
              <i class="fas fa-sign-out-alt"></i> Sign Out
            </a>
          </div>
        </div>
      <?php endif; ?>
    </nav>
  </div>  <script>
    // Mobile and dropdown menu functionality
    document.addEventListener('DOMContentLoaded', function() {
      // Mobile menu toggle
      const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
      const mainNav = document.querySelector('.main-nav');
      const isMobile = window.innerWidth <= 768;
      
      if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function(e) {          
          e.preventDefault();
          mainNav.classList.toggle('show');
          document.body.classList.toggle('menu-open');
          document.querySelector('header').classList.toggle('menu-open');
          
          // Toggle between hamburger and X icon with smooth transition
          const icon = this.querySelector('i');
          if (icon.classList.contains('fa-bars')) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-times');
            mobileMenuToggle.setAttribute('aria-expanded', 'true');
          } else {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
            mobileMenuToggle.setAttribute('aria-expanded', 'false');
          }
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {          if (!mobileMenuToggle.contains(e.target) && 
              !mainNav.contains(e.target) && 
              mainNav.classList.contains('show')) {
            mainNav.classList.remove('show');
            document.body.classList.remove('menu-open');
            document.querySelector('header').classList.remove('menu-open');
            const icon = mobileMenuToggle.querySelector('i');
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
            mobileMenuToggle.setAttribute('aria-expanded', 'false');
          }
        });
      }
      
      // User dropdown menu
      const userMenu = document.querySelector('.user-menu');
      if (userMenu) {
        // Handle opening/closing dropdown when clicking the profile toggle
        const menuToggle = userMenu.querySelector('.user-menu-toggle');
        menuToggle.addEventListener('click', function(e) {
          e.preventDefault();
          const dropdown = userMenu.querySelector('.dropdown-menu');
          dropdown.classList.toggle('show');
        });
        
        // Handle dropdown links
        const dropdownLinks = userMenu.querySelectorAll('.dropdown-menu a');
        dropdownLinks.forEach(link => {
          link.addEventListener('click', function(e) {
            window.location.href = this.href;
          });
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
          if (userMenu && !userMenu.contains(e.target)) {
            const dropdown = userMenu.querySelector('.dropdown-menu');
            if (dropdown) dropdown.classList.remove('show');
          }
        });
      }
    });
  </script>
</header>
