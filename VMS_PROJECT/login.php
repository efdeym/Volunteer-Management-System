<?php

// Start the session if not already started (hesaba giriş yapılmasıysa)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

$errors = [];
$email = '';
$password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($errors)) {
    $stmt = $pdo->prepare('SELECT u.user_id, u.first_name, u.last_name, u.email, u.password_hash, r.role_name 
                           FROM Users u
                           JOIN Roles r ON u.role_id = r.role_id 
                           WHERE u.email = ? LIMIT 1');

    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) { 
            $errors[] = 'Invalid email or password.';
        } else {
            $_SESSION['user'] = [
                'user_id' => (int)$user['user_id'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $user['email'],
                'role_name' => $user['role_name'],
            ];
        header('Location: ' . ($user['role_name'] === 'Admin' ? 'admin.php' : 'dashboard.php'));
        exit;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login � Volunteer Management System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
<main class="auth-card">
  <img src="assets/logo.svg" alt="VMS logo" class="auth-logo">
  <h1>Welcome Back</h1>
  <?php if ($errors): ?>
    <div class="alert danger">
      <ul>
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
  <form method="post" class="form-grid">
    <label>
      Email Address
      <input type="email" name="email" required value="<?= htmlspecialchars($email) ?>"> 
    </label>
    <label>
      Password
      <input type="password" name="password" required>
    </label>
    <button class="btn primary" type="submit">Log In</button>
    <p class="auth-switch">Need an account? <a href="signup.php">Register</a></p>
  </form>
</main>
</body>
</html>
