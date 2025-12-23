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

    if (!preg_match('/^[a-zA-Z\sçğıöşüÇĞİÖŞÜ]+$/u', $first_name) || !preg_match('/^[a-zA-Z\sçğıöşüÇĞİÖŞÜ]+$/u', $last_name)) {
        $errors[] = 'First Name and Last Name must only contain letters and spaces.';
    }

    if ($phone === '') {
        $errors[] = 'Phone number is required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address format (e.g., hidir@example.com).';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter.';
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number.';
    }

    if (!preg_match('/[^a-zA-Z0-9\s]/', $password)) {
        $errors[] = 'Password must contain at least one special character (e.g., !, #, $).';
    }
    
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
      try {
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

            session_start();
              $_SESSION['user_id'] = $pdo->lastInsertId();
              $_SESSION['first_name'] = $first_name;
              $_SESSION['email'] = $email;

           header("Location: about.html");
                exit;
        }
    }
    catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
}
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>VolunteerHub Signup </title>
<meta name="viewport" content="width=device-width, initial-scale=4">
<link rel="stylesheet" href="style.css">
</head>

<body class="section-header">
<nav class="site-nav">
    <a href="about.html" class="brand">
        <img src="assets/logo.svg" alt="VMS Logo">
    </a>
  
    <button class="nav-toggle" data-nav-toggle aria-label="Toggle navigation">Menu</button>
    <div class="nav-links" data-nav>
       <a href="index.php" class="pill">Home</a>
       <a href="about.html" class="pill">About</a>
       <a href="programs.php" class="pill">Programs</a>
       <a href="partners.html" class="pill">Partners</a>
       <a href="contact.html" class="pill">Contact</a>
       <a href="Profile.html" class="pill">Profile</a>
      <button class="theme-toggle" data-theme-toggle aria-label="Toggle color theme">Shift Colors</button>
    </div>
  </nav>
<main class="hero-grid">
  
  <a href="about.html" class="brand">
        <img src="assets/logo.svg" alt="VMS Logo">
    </a>

  <h1>Create Volunteer Profile</h1>
  <p class="alert success" style="display:none;">Registration successful!</p>

  <?php if (!empty($errors)): ?>
    <div class="alert danger" style="background-color: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border: 1px solid #f5c6cb; border-radius: 5px;">
        <strong>Please correct the following errors:</strong>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
  <?php endif; ?>

  <form class="form-grid" method="POST">

    <label for="first name" style="color:aliceblue">First Name
      <input type="text" name="first_name" placeholder="Ender" value="<?php echo htmlspecialchars($first_name); ?>" required>
    </label>

    <label for="last name" style="color:aliceblue">Last Name 
      <input type="text" name="last_name" placeholder="Sevinç" value="<?php echo htmlspecialchars($last_name); ?>" required>
    </label>

    <label for="phone number" style="color:aliceblue">Phone Number
      <input type="tel" name="phone" placeholder="0555 555 55 55" value="<?php echo htmlspecialchars($phone); ?>" required>
    </label>

    <label for="email" style="color: aliceblue"> Email Address
      <input type="email" name="email" placeholder="hıdır@mail.com" value="<?php echo htmlspecialchars($email); ?>" required>
    </label>

    <label for="password" style="color:aliceblue">Password
      <input type="password" name="password" placeholder="Enter a password">
    </label>

    <label for="confirm password" style="color:aliceblue">Confirm Password
      <input type="password" name="confirm_password" placeholder="Confirm your password" required>
      <br><small>Password must be at least 8 characters, include letters and numbers  </small>
    </label>
    
    <button class="btn primary narrow-btn" type="submit">Register</button>

    <p class="auth-switch">
      Already registered?
      <a href="login.php">Log in</a>
    </p>
  </form>

  <footer class="site-footer">
  VolunteerHub
</footer>
</main>

</body>
</html>
