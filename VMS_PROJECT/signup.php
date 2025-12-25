<?php
require_once __DIR__ . '/db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);

        if ($check->fetch()) {
            $errors[] = 'That email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $insert = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
            $insert->execute([$name, $email, $hash, 'volunteer']);
            $success = 'Registration successful! You may log in now.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Volunteer Signup — VMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
<main class="auth-card">
  <img src="assets/logo.svg" alt="VMS logo" class="auth-logo">
  <h1>Create Volunteer Profile</h1>
  <?php if ($success): ?>
    <p class="alert success"><?= htmlspecialchars($success) ?></p>
  <?php endif; ?>
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
      Full Name
      <input type="text" name="name" value="<?= htmlspecialchars($name ?? '') ?>" required>
    </label>
    <label>
      Email Address
      <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
    </label>
    <label>
      Password
      <input type="password" name="password" required minlength="8">
    </label>
    <label>
      Confirm Password
      <input type="password" name="confirm_password" required minlength="8">
    </label>
    <button class="btn primary" type="submit">Register</button>
    <p class="auth-switch">Already registered? <a href="login.php">Log in</a></p>
  </form>
</main>
</body>
</html>
