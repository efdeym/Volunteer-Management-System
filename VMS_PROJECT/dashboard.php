<?php
require_once __DIR__ . '/auth.php';

$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_event'])) {
    $eventId = (int)$_POST['join_event'];
    $join = $pdo->prepare('INSERT IGNORE INTO event_registrations (user_id, event_id) VALUES (?, ?)');
    $join->execute([$currentUser['id'], $eventId]);
    $feedback = 'Thanks for joining! Check your email for event details.';
}

$eventsStmt = $pdo->prepare(
    'SELECT e.id, e.title, e.event_date, e.description,
            EXISTS(SELECT 1 FROM event_registrations r WHERE r.user_id = ? AND r.event_id = e.id) AS joined
     FROM events e
     ORDER BY e.event_date ASC'
);
$eventsStmt->execute([$currentUser['id']]);
$events = $eventsStmt->fetchAll();

$totalEvents = count($events);
$joinedCount = array_reduce($events, fn($carry, $item) => $carry + (int)$item['joined'], 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Volunteer Dashboard — VMS</title>
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
      <h3>Upcoming Events</h3>
      <p><?= $totalEvents ?></p>
    </article>
    <article>
      <h3>Events Joined</h3>
      <p><?= $joinedCount ?></p>
    </article>
    <article>
      <h3>Role</h3>
      <p><?= htmlspecialchars(ucfirst($currentUser['role'])) ?></p>
    </article>
  </section>

  <section class="dashboard-panel">
    <header>
      <h2>Available Events</h2>
      <p>Select an event to participate.</p>
    </header>
    <div class="dashboard-list">
      <?php foreach ($events as $event): ?>
        <article class="dashboard-item">
          <div>
            <h3><?= htmlspecialchars($event['title']) ?></h3>
            <p><?= htmlspecialchars(date('F j, Y', strtotime($event['event_date']))) ?></p>
            <p><?= htmlspecialchars($event['description']) ?></p>
          </div>
          <form method="post">
            <input type="hidden" name="join_event" value="<?= (int)$event['id'] ?>">
            <button class="btn <?= $event['joined'] ? 'ghost' : 'primary' ?>" type="submit" <?= $event['joined'] ? 'disabled' : '' ?>>
              <?= $event['joined'] ? 'Joined' : 'Join Event' ?>
            </button>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</main>
</body>
</html>
