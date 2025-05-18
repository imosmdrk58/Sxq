<?php require_once __DIR__.'/config/config.php'; 
$error = '';
$success = '';

// Check for registration success message
if (isset($_SESSION['register_success'])) {
  $success = "Registration successful! You can now log in with your new account.";
  unset($_SESSION['register_success']);
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $u = $_POST['username'];
  $p = $_POST['password'];
  $stmt = $pdo->prepare("SELECT * FROM users WHERE username=?");
  $stmt->execute([$u]);
  $user = $stmt->fetch();
  if ($user && password_verify($p,$user['password_hash'])) {
    // Clear any existing session data
    $_SESSION = array();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    
    // Handle redirection
    $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'manga.php';
    unset($_SESSION['redirect_after_login']);
    
    header('Location: ' . $redirect);
    exit;
  } else {
    $error = "Invalid credentials";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login – Manga Tracker</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <?php include __DIR__.'/header.php'; ?>
  <div class="container" style="max-width: 500px;">
    <h1 class="text-center">Log In</h1>
    
    <?php if($error): ?>
      <div style="background: #ffebee; color: var(--danger); padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; text-align: center;">
        <i class="fas fa-exclamation-circle"></i> <?=htmlspecialchars($error)?>
      </div>
    <?php endif; ?>
    
    <?php if($success): ?>
      <div style="background: #e8f5e9; color: #2e7d32; padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; text-align: center;">
        <i class="fas fa-check-circle"></i> <?=htmlspecialchars($success)?>
      </div>
    <?php endif; ?>
    
    <form method="post">
      <label for="username"><i class="fas fa-user"></i> Username</label>
      <input id="username" name="username" type="text" placeholder="Enter your username" required>
      
      <label for="password"><i class="fas fa-lock"></i> Password</label>
      <input id="password" name="password" type="password" placeholder="Enter your password" required>
      
      <button type="submit"><i class="fas fa-sign-in-alt"></i> Log In</button>
    </form>
    
    <div class="text-center mt-2">
      <p>Don't have an account? <a href="register.php" style="color: var(--primary); font-weight: 600;">Register here</a></p>
    </div>
  </div>
  
  <?php include __DIR__.'/footer.php'; ?>
</body>
</html>
