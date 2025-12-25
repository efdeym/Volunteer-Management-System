<?php
require_once __DIR__ . '/auth.php';

if ($currentUser['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_event'])) {
        $title = trim($_POST['title'] ?? '');
        $date = $_POST['event_date'] ?? '';
        $description = trim($_POST['description'] ?? '');
        if ($title && $date) {
            $stmt = $pdo->prepare('INSERT INTO events (title, event_date, description) VALUES (?, ?, ?)');
            $stmt->execute([$title, $date, $description]);
            $message = 'Event created successfully.';
        }
    }

    if (isset($_POST['delete_event'])) {
        $eventId = (int)$_POST['delete_event'];
        $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$eventId]);
        $message = 'Event deleted.';
    }

    if (isset($_POST['update_role'])) {
        $userId = (int)$_POST['user_id'];
        $role = $_POST['role'] === 'admin' ? 'admin' : 'volunteer';
        $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $userId]);
        $message = 'Role updated.';
    }
}

$volunteers = $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();
$events = $pdo->query('SELECT id, title, event_date, description FROM events ORDER BY event_date ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Panel — VMS</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<header class="app-header">
  <div class="brand">
    <img src="assets/logo.svg" alt="VMS logo">
    <div>
      <strong>Admin Panel</strong>
      <p><?= htmlspecialchars($currentUser['name']) ?></p>
    </div>
  </div>
  <div class="header-actions">
    <a class="pill" href="dashboard.php">Dashboard</a>
    <a class="pill" href="logout.php">Logout</a>
  </div>
</header>

<main class="dashboard">
  <?php if ($message): ?>
    <p class="alert success"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>
  <section class="dashboard-panel">
    <header>
      <h2>Create Event</h2>
      <p>Publish a new volunteer opportunity.</p>
    </header>
    <form method="post" class="form-grid">
      <label>Title <input type="text" name="title" required></label>
      <label>Date <input type="date" name="event_date" required></label>
      <label>Description <textarea name="description" rows="3"></textarea></label>
      <button class="btn primary" type="submit" name="create_event" value="1">Publish Event</button>
    </form>
  </section>

  <section class="dashboard-panel">
    <header><h2>Events</h2></header>
    <div class="dashboard-list">
      <?php foreach ($events as $event): ?>
        <article class="dashboard-item">
          <div>
            <h3><?= htmlspecialchars($event['title']) ?></h3>
            <p><?= htmlspecialchars(date('F j, Y', strtotime($event['event_date']))) ?></p>
            <p><?= htmlspecialchars($event['description']) ?></p>
          </div>
          <form method="post">
            <button class="btn ghost" type="submit" name="delete_event" value="<?= (int)$event['id'] ?>">Delete</button>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="dashboard-panel">
    <header><h2>Volunteers</h2></header>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($volunteers as $volunteer): ?>
            <tr>
              <td><?= htmlspecialchars($volunteer['name']) ?></td>
              <td><?= htmlspecialchars($volunteer['email']) ?></td>
              <td><?= htmlspecialchars($volunteer['role']) ?></td>
              <td>
                <form method="post" class="inline-form">
                  <input type="hidden" name="user_id" value="<?= (int)$volunteer['id'] ?>">
                  <select name="role">
                    <option value="volunteer" <?= $volunteer['role'] === 'volunteer' ? 'selected' : '' ?>>Volunteer</option>
                    <option value="admin" <?= $volunteer['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                  </select>
                  <button class="btn secondary" type="submit" name="update_role" value="1">Update</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>
</body>
</html>
