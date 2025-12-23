<?php
//Don't clean commed lines. I wrote for that I can remember and you can understand my code. (Elif)
//this page's purpose is to handle volunteer history functions
require_once __DIR__ . '/auth.php';


$volunteerId = (int)$_SESSION['user']['user_id'];


try {
    $sql = "SELECT 
                P.title AS project_title, 
                A.application_date, 
                A.status, 
                A.decision_date,
                A.notes
            FROM Applications A
            JOIN Projects P ON A.project_id = P.project_id
            WHERE A.volunteer_id = ?
            ORDER BY A.application_date DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$volunteerId]);
    $history = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching history.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Volunteer History </title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
    <script defer src="script.js"></script>
</head>
<body class="site-body">

<header class="site-hero" style="min-height: auto; padding-bottom: 2rem;">
  <nav class="site-nav">
    <a href="about.html" class="brand">
        <img src="assets/logo.svg" alt="VMS Logo">
    </a>
    <div class="nav-name">Dashboard</div>
    <div class="nav-links" data-nav>
       <a href="http://localhost:8080/Volunteer-Management-System-main/VMS_PROJECT/" class="pill">Home</a>
       <a href="profile.php" class="pill">Profile</a>
       <a href="volunteer_history.php" class="pill active">Volunteer History</a>
       <a href="logout.php" class="pill">Logout</a>
       <button class="theme-toggle" data-theme-toggle>Shift Colors</button>
    </div>
  </nav>
  <div class="hero-grid">
    <div>
      <h1>My Volunteer Journey</h1>
      <p class="lead">Track your applications and contributions to various projects.</p>
    </div>
  </div>
</header>

<main class="dashboard" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
    
    <?php if (isset($error)): ?>
        <div class="alert danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <section class="dashboard-panel">
        <header class="section-header" style="margin-bottom: 20px;">
            <h2>Application History</h2>
            <p style="color: var(--primary-dark); opacity: 0.7;">A record of all your service requests.</p>
        </header>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Project Title</th>
                        <th>Application Date</th>
                        <th>Status</th>
                        <th>Decision Date</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px;">
                                <p>You haven't applied to any programs yet.</p>
                                <a href="programs.php" class="btn primary" style="display:inline-block; text-decoration:none;">Browse Programs</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($history as $row): ?>
                            <tr>
                                <td style="font-weight: 600;"><?= htmlspecialchars($row['project_title']) ?></td>
                                <td><?= date('M d, Y', strtotime($row['application_date'])) ?></td>
                                <td>
                                    <span class="pill" style="font-size: 12px; background: 
                                        <?= $row['status'] === 'approved' ? '#e4f7ec' : ($row['status'] === 'rejected' ? '#ffe8e5' : '#f0f4ff') ?>; 
                                        color: <?= $row['status'] === 'approved' ? '#237741' : ($row['status'] === 'rejected' ? '#8a1e12' : '#711fff') ?>;">
                                        <?= ucfirst(htmlspecialchars($row['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= $row['decision_date'] ? date('M d, Y', strtotime($row['decision_date'])) : '<em>Pending</em>' ?>
                                </td>
                                <td style="max-width: 250px; font-size: 14px; opacity: 0.8;">
                                    <?= htmlspecialchars($row['notes'] ?: 'No notes available.') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<footer class="site-footer">
  VolunteerHub
</footer>

</body>
</html>