<?php
session_start();
require_once 'db.php';

$isLoggedIn = isset($_SESSION['user']['user_id']);
$currentUserId = $isLoggedIn ? $_SESSION['user']['user_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_project_id'])) {
    if (!$isLoggedIn) {
        header("Location: login.php");
        exit;
    }

    $projId = (int)$_POST['apply_project_id'];

    $checkProj = $pdo->prepare("
        SELECT status, end_date, max_capacity, 
        (SELECT COUNT(*) FROM Applications WHERE project_id = ? AND status = 'Approved') as approved_count 
        FROM Projects WHERE project_id = ?
    ");
    $checkProj->execute([$projId, $projId]);
    $projectDetails = $checkProj->fetch();

    $today = date('Y-m-d');
    $isExpired = ($projectDetails['end_date'] && $projectDetails['end_date'] < $today);
    $isFull = ($projectDetails['approved_count'] >= $projectDetails['max_capacity']);

    if ($projectDetails['status'] !== 'Open' || $isExpired || $isFull) {
        header("Location: programs.php?error=not_available");
        exit;
    }

    $checkStmt = $pdo->prepare("SELECT application_id FROM Applications WHERE project_id = ? AND volunteer_id = ?");
    $checkStmt->execute([$projId, $currentUserId]);
    
    if (!$checkStmt->fetch()) {
        $insStmt = $pdo->prepare("INSERT INTO Applications (project_id, volunteer_id, status) VALUES (?, ?, 'Pending')");
        $insStmt->execute([$projId, $currentUserId]);
        
        header("Location: programs.php?success=1");
        exit;
    }
}

try {
$sql = "SELECT P.*, O.org_name, A.status as app_status,
            (SELECT COUNT(*) FROM Applications WHERE project_id = P.project_id AND status = 'Approved') as approved_count
            FROM Projects P 
            LEFT JOIN Organizations O ON P.org_id = O.org_id 
            LEFT JOIN Applications A ON P.project_id = A.project_id AND A.volunteer_id = :uid
            ORDER BY P.created_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':uid' => $currentUserId]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Programs & Initiatives - VolunteerHub</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="style.css">
<script defer src="script.js"></script>
<style>
    .section-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
    }
    .project-card {
        background: white;
        padding: 2rem;
        border-radius: 12px;
        border: 1px solid #eee;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        text-align: left; 
    }

    .project-card h3 {
        color: #333 !important;
        margin: 10px 0;
    }
    .project-card p {
        color: #555 !important;
    }
    .project-card .card-date {
        color: #bbb7b7ff !important;
    }
    .app-status-tag {
        font-size: 0.8rem;
        font-weight: bold;
        padding: 4px 12px;
        border-radius: 20px;
        display: inline-block;
        margin-bottom: 10px;
    }
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-approved { background: #d1fae5; color: #065f46; }
    .status-rejected { background: #fee2e2; color: #b91c1c; }
    .status-full { background: #ffedd5; color: #9a3412; }
    .status-expired { background: #f3f4f6; color: #374151; }
</style>
</head>
<body class="site-body">
<header class="site-hero">
  <nav class="site-nav">
    <a href="index.php" class="brand">
        <img src="assets/logo.svg" alt="VMS Logo">
    </a>
    <div class="nav-name">Programs</div>
    <button class="nav-toggle" data-nav-toggle aria-label="Toggle navigation">Menu</button>
    <div class="nav-links" data-nav>
       <a href="index.php" class="pill">Home</a>
       <a href="about.html" class="pill">About</a>
       <a href="programs.php" class="pill">Programs</a>
       <a href="contact.html" class="pill">Contact</a>
       <?php if($isLoggedIn): ?>
            <a href="profile.php" class="pill">Profile</a>
            <a href="logout.php" class="pill" style="color: #ef4444;">Logout</a>
       <?php else: ?>
            <a href="login.php" class="pill">Login</a>
       <?php endif; ?>
       <button class="theme-toggle" data-theme-toggle aria-label="Toggle color theme">Shift Colors</button>
    </div>
  </nav>

  <div class="hero-grid">
    <div>
      <p class="eyebrow">Program Catalog</p>
      <h1>Navigate service opportunities with curated playbooks.</h1>
      <p class="lead">Programs bundle tasks, volunteers, and outcomes so coordinators can deliver repeatable experiences at scale.</p>
      <div class="hero-actions">
        <a class="btn secondary" href="#active-projects">Join a Program</a>
      </div>
    </div>
    <div class="hero-visual">
      <img src="assets/undraw_professor_d7zn.svg" alt="Programs illustration">
    </div>
  </div>
</header>

<main>
  <section class="section program-templates" id="active-projects">
    <header class="section-header">
      <h2>Live Projects & Programs</h2>
      <p>Real-time opportunities filtered from our active database. Apply to make an impact today.</p>
    </header>

    <div class="section-grid">
      <?php if (count($projects) > 0): ?>
        <?php foreach ($projects as $proj):
          $today = date('Y-m-d');
            $isExpired = ($proj['end_date'] && $proj['end_date'] < $today);
            $isFull = ($proj['approved_count'] >= $proj['max_capacity']);
            $canApply = ($proj['status'] === 'Open' && !$isExpired && !$isFull);
        ?>
          <article class="project-card">
    <div>
        <?php if ($isExpired): ?>
            <span class="app-status-tag status-expired">Expired</span>
        <?php elseif ($isFull): ?>
            <span class="app-status-tag status-full">Project Full</span>
        <?php elseif ($proj['app_status']): ?>
            <span class="app-status-tag status-<?= strtolower($proj['app_status']) ?>">
                Application: <?= htmlspecialchars($proj['app_status']) ?>
            </span>
        <?php endif; ?>

        <p class="card-date"><?= htmlspecialchars($proj['location'] ?? 'Global') ?> | <?= date('M d, Y', strtotime($proj['start_date'])) ?></p>
        <h3><?= htmlspecialchars($proj['title']) ?></h3>
        
        <p style="font-size: 0.9rem; color: #666;">
            Capacity: <strong><?= $proj['approved_count'] ?> / <?= $proj['max_capacity'] ?></strong>
        </p>
        
        <p style="margin-bottom: 1.5rem;"><?= htmlspecialchars(mb_strimwidth($proj['description'], 0, 120, "...")) ?></p>
    </div>

    <div class="card-footer">
    <?php if (!$isLoggedIn): ?>
        <a href="login.php" class="btn primary" style="width: 100%; text-align: center;">Login to Apply</a>
    <?php else: ?>
        <?php if ($isFull && $proj['app_status'] !== 'Approved'): ?>
            <button class="btn ghost" disabled style="width: 100%; color: #999;">Applications Closed</button>
            
        <?php elseif ($proj['app_status'] === 'Approved'): ?>
            <button class="btn secondary" disabled style="width: 100%; background: #d1fae5; color: #065f46; border: none;">Approved / Joined</button>
            
        <?php elseif ($proj['app_status'] === 'Pending'): ?>
            <button class="btn ghost" disabled style="width: 100%;">Application Processed</button>
            
        <?php elseif (!$canApply): ?>
            <button class="btn ghost" disabled style="width: 100%; color: #999;">Applications Closed</button>
            
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="apply_project_id" value="<?= $proj['project_id'] ?>">
                <button type="submit" class="btn primary" style="width: 100%;">Apply Now</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
</article>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="grid-column: 1/-1; text-align: center;">No active projects available right now. Please check back later.</p>
      <?php endif; ?>
    </div>
  </section>

  <section class="section lifecycle-section">
    <header class="section-header">
      <h2>Program Lifecycle</h2>
      <p>VMS guides every step so no volunteer or task is left behind.</p>
    </header>
    <ul class="timeline">
      <li>
        <strong>Discover</strong>
        <p>Publish branded landing pages, embed public interest forms, and highlight urgent roles.</p>
      </li>
      <li>
        <strong>Mobilize</strong>
        <p>Automate confirmations, share playbooks, and send SMS reminders from the same workspace.</p>
      </li>
      <li>
        <strong>Deliver</strong>
        <p>Capture attendance, log hours, and push updates to operations leads in real time.</p>
      </li>
      <li>
        <strong>Report</strong>
        <p>Export metrics, collect testimonials, and close the loop with donors.</p>
      </li>
    </ul>
  </section>
</main>

<footer class="site-footer">
    VolunteerHub 
</footer>
</body>
</html>