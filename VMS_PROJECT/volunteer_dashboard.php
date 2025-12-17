<?php
session_start();

// Volunteer değilse dashboard'a erişemez
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 1) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Volunteer Dashboard</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-body">

<main class="dashboard-main">
    <h1>Welcome, <?= htmlspecialchars($user['name']) ?>!</h1>

    <div class="dashboard-grid">
        <a class="card-btn" href="volunteer_application_form.php">
            Submit New Application
        </a>

        <a href="volunteer_history.php" class="btn secondary">My History</a>

        <a class="card-btn" href="logout.php">
            Log Out
        </a>
    </div>
</main>

</body>
</html>
