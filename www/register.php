<?php require_once __DIR__.'/config/config.php'; 
$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $u = trim($_POST['username']);
  $p = $_POST['password'];
  $email = isset($_POST['email']) ? trim($_POST['email']) : '';
  
  // Basic validation
  if(strlen($p) < 6) {
    $error = "Password must be at least 6 characters";
  } else {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username=?");
    $stmt->execute([$u]);
    if ($stmt->fetch()) {
      $error = "Username already taken";
    } else {
      $h = password_hash($p, PASSWORD_DEFAULT);
      $pdo->prepare("INSERT INTO users (username, password_hash, email) VALUES (?,?,?)")
          ->execute([$u, $h, $email]);
      
      // Redirect with success message
      $_SESSION['register_success'] = true;
      header('Location: login.php');
      exit;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register – Manga Tracker</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <?php include __DIR__.'/header.php'; ?>
  
  <div class="container" style="max-width: 500px;">
    <h1 class="text-center">Create Account</h1>
    <p class="text-center">Join Manga Tracker to keep track of your reading progress</p>
    
    <?php if($error): ?>
      <div style="background: #ffebee; color: var(--danger); padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; text-align: center;">
        <i class="fas fa-exclamation-circle"></i> <?=htmlspecialchars($error)?>
      </div>
    <?php endif; ?>
    
    <form method="post">
      <label for="username"><i class="fas fa-user"></i> Username</label>
      <input id="username" name="username" type="text" placeholder="Choose a username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
      
      <label for="email"><i class="fas fa-envelope"></i> Email (optional)</label>
      <input id="email" name="email" type="email" placeholder="Enter your email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
      
      <label for="password"><i class="fas fa-lock"></i> Password</label>
      <input id="password" name="password" type="password" placeholder="Choose a password (min. 6 characters)" required>
      
      <button type="submit"><i class="fas fa-user-plus"></i> Create Account</button>
    </form>
    
    <div class="text-center mt-2">
      <p>Already have an account? <a href="login.php" style="color: var(--primary); font-weight: 600;">Log in here</a></p>
    </div>
  </div>
  
  <?php include __DIR__.'/footer.php'; ?>
</body>
</html>
