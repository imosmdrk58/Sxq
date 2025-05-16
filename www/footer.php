<?php
// Footer file for Manga Tracker
?>
<footer>
  <div class="container">
    <div class="footer-grid">
      <div>
        <h4>Manga Tracker</h4>
        <p>Keep track of your manga reading progress with our simple and intuitive tracking system.</p>
      </div>
      <div class="footer-links">
        <h4>Quick Links</h4>
        <a href="index.php">Home</a>
        <a href="manga.php">My Manga</a>
        <a href="comment.php">Guestbook</a>
      </div>
      <div class="footer-links">
        <h4>Account</h4>
        <?php if (empty($_SESSION['user_id'])): ?>
          <a href="login.php">Sign In</a>
          <a href="register.php">Register</a>
        <?php else: ?>
          <a href="logout.php">Sign Out</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="copyright">
      &copy; <?php echo date('Y'); ?> Manga Tracker. All rights reserved.
    </div>
  </div>
</footer>
