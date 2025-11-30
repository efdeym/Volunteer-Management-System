<?php
require_once __DIR__ . '/auth.php';

$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_project'])) {
    $projectId = (int)$_POST['join_project'];
    
    $join = $pdo->prepare('INSERT IGNORE INTO Applications (volunteer_id, project_id, status) VALUES (?, ?, ?)');
    $join->execute([$currentUser['user_id'], $projectId, 'Pending']);
    
    $feedback = 'Thanks for applying! Your application is pending review.';
}

$eventsStmt = $pdo->prepare(
    'SELECT p.project_id AS id, p.title, p.start_date AS event_date, p.description,
            EXISTS(SELECT 1 FROM Applications a WHERE a.volunteer_id = ? AND a.project_id = p.project_id) AS joined
     FROM Projects p
     WHERE p.status = \'Open\'
     ORDER BY p.start_date ASC'
);

$eventsStmt->execute([$currentUser['user_id']]);
$events = $eventsStmt->fetchAll();

$totalEvents = count($events);
$joinedCount = array_reduce($events, fn($carry, $item) => $carry + (int)$item['joined'], 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Volunteer Dashboard – VMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<header class="app-header">
    <div class="brand">
        <img src="assets/logo.svg" alt="VMS logo">
        <div>
            <strong>Volunteer Management System</strong>
            <p><?= htmlspecialchars($currentUser['name']) ?></p>
        </div>
    </div>
    <div class="header-actions">
        <a class="pill" href="index.html">Home</a>
        <a class="pill" href="logout.php">Logout</a>
    </div>
</header>

<main class="dashboard">
    <?php if ($feedback): ?>
        <p class="alert success"><?= htmlspecialchars($feedback) ?></p>
    <?php endif; ?>
    <section class="dashboard-cards">
        <article>
            <h3>Available Projects</h3>
            <p><?= $totalEvents ?></p>
        </article>
        <article>
            <h3>Projects Applied</h3>
            <p><?= $joinedCount ?></p>
        </article>
        <article>
            <h3>Role</h3>
            <p><?= htmlspecialchars(ucfirst($currentUser['role_name'])) ?></p>
        </article>
    </section>

    <section class="dashboard-panel">
        <header>
            <h2>Available Projects</h2>
            <p>Select a project to apply.</p>
        </header>
        <div class="dashboard-list">
            <?php foreach ($events as $event): ?>
                <article class="dashboard-item">
                    <div>
                        <h3><?= htmlspecialchars($event['title']) ?></h3>
                        <p>Starts: <?= htmlspecialchars(date('F j, Y', strtotime($event['event_date']))) ?></p>
                        <p><?= htmlspecialchars($event['description']) ?></p>
                    </div>
                    <form method="post">
                        <input type="hidden" name="join_project" value="<?= (int)$event['id'] ?>">
                        <button class="btn <?= $event['joined'] ? 'ghost' : 'primary' ?>" type="submit" <?= $event['joined'] ? 'disabled' : '' ?>>
                            <?= $event['joined'] ? 'Applied' : 'Apply Now' ?>
                        </button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
</body>
</html>