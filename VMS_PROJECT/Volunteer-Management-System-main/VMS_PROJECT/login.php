<?php
require_once __DIR__ . '/db.php';

$errors = [];
$email = '';
$password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("
            SELECT user_id, first_name, last_name, email, password_hash, role_id
            FROM Users
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = "Invalid email or password.";
        } else {
            session_start();
            $_SESSION['user'] = [
                'user_id' => $user['user_id'],
                'name'    => $user['first_name'] . " " . $user['last_name'],
                'email'   => $user['email'],
                'role_id' => $user['role_id']
            ];

            if ($user['role_id'] == 1) {
                header("Location: volunteer_dashboard.php");
                exit;
            } 
            if ($user['role_id'] == 2) {
                header("Location: manager_dashboard.php");
                exit;
            } 
            if ($user['role_id'] == 3) {
                header("Location: admin.php");
                exit;
            }
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
