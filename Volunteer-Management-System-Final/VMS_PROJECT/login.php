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
                header("Location: profile.php");
                exit;
            } 
            if ($user['role_id'] == 2) {
                header("Location: profile.php");
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
  <meta name="viewport" content="width=device-width">
  <title>Log In </title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="section-grid">

  <main class="hero-grid">
    <img src="assets/logo.svg" alt="VMS logo" >

    <h1 style="color:#fff; margin-bottom: 20px;">Log In</h1>

    <form action="login.php" method="POST" class="form-grid">

      <label for="email">
        Email Address
        <input type="email" id="email" name="email" placeholder="example@email.com" required>
      </label>

      <label for="password">
        Password
        <input type="password" id="password" name="password" placeholder="Your password" required>
      </label>

      <button type="submit" class="btn primary narrow-btn">Log In</button>

      <p class="auth-switch">
        Don’t have an account?
        <a href="signup.php">Create Account</a>
      </p>
    </form>
  </main>

</body>
</html>


