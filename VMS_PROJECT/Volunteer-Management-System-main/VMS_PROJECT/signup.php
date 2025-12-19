<?php
require_once __DIR__ . '/db.php';

$errors = [];
$success = '';

$first_name = '';
$last_name = '';
$phone = '';
$email = '';
$password = '';
$confirm = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($first_name === '') {
        $errors[] = 'First Name is required.';
    }
    if ($last_name === '') {
        $errors[] = 'Last Name is required.';
    }
    if (!preg_match('/^[a-zA-Z\s]+$/', $first_name) || !preg_match('/^[a-zA-Z\s]+$/', $last_name)) {
        $errors[] = 'First Name and Last Name must only contain letters and spaces.';
    }

    if ($phone === '') {
        $errors[] = 'Phone number is required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address format (e.g., user@example.com).';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Your password must contain at least one uppercase letter.';
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Your password must contain at least one lowercase letter.';
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Your password must contain at least one number.';
    }

    if (!preg_match('/[^a-zA-Z0-9\s]/', $password)) {
        $errors[] = 'Your password must contain at least one special character (e.g., !, #, $).';
    }
    
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $check = $pdo->prepare('SELECT user_id FROM Users WHERE email = ? LIMIT 1');
        $check->execute([$email]);

        if ($check->fetch()) {
            $errors[] = 'That email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $role_id_volunteer = 1;
            $insert = $pdo->prepare('INSERT INTO Users (first_name, last_name, phone, email, password_hash, role_id) VALUES (?, ?, ?, ?, ?, ?)');
            $insert->execute([$first_name, $last_name, $phone, $email, $hash, $role_id_volunteer]);
            $success = 'Registration successful! You may log in now.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Volunteer Signup � VMS</title>
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
      First Name
			<input type="text" name="first_name" value="<?= htmlspecialchars($first_name ?? '') ?>" required>
		</label>
        <label>
			Last Name
			<input type="text" name="last_name" value="<?= htmlspecialchars($last_name ?? '') ?>" required>
		</label>
        <label>
			Phone Number
			<input type="tel" name="phone" value="<?= htmlspecialchars($phone ?? '') ?>" required>
		</label>
		<label>
    Email Address
    <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required> 
    </label>
    <label>
      Password
    <input 
        type="password" 
        name="password" 
        required 
        minlength="8" 
        title="Your password must be at least 8 characters long and must contain at least one uppercase letter, one lowercase letter, one number, and one special character"
        pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9\s]).{8,}"
    >
    <small class="password-hint">
        Must be at least 8 characters, and contain 1 uppercase, 1 lowercase, 1 number, and 1 special character.
    </small>
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
