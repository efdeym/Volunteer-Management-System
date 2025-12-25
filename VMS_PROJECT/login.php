<?php
require_once __DIR__ . '/db.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        $errors[] = 'Invalid email or password.';
    } else {
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        header('Location: ' . ($user['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login — Volunteer Management System</title>
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
      <input type="email" name="email" required>
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
