<?php

//Don't clean commed lines. I wrote for that I can remember and you can understand my code. (Elif)
//this page's purpose is to handle admin functions
require_once __DIR__ . '/auth.php';

//only admin users can access this page
if ($currentUser['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Create new project
    if (isset($_POST['create_project'])) {
        $title = trim($_POST['title'] ?? '');
        $date = $_POST['event_date'] ?? '';
        $description = trim($_POST['description'] ?? '');
        
        // insert into database(ord_id ve manager_id...)
        if ($title && $date) {
            $stmt = $pdo->prepare('INSERT INTO Projects (org_id, manager_id, title, description, start_date, status) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([1, $currentUser['user_id'], $title, $description, $date, 'Open']);
            $message = 'Project created successfully.';
        }
    }

    // Delete project
    if (isset($_POST['delete_project'])) {
        $projectId = (int)$_POST['delete_project'];
        $pdo->prepare('DELETE FROM Projects WHERE project_id = ?')->execute([$projectId]);
        $message = 'Project deleted.';
    }

    // Update user role
    if (isset($_POST['update_role'])) {
        $userId = (int)$_POST['user_id'];
        $roleId = $_POST['role'] === 'admin' ? 3 : 1;
        $pdo->prepare('UPDATE Users SET role_id = ? WHERE user_id = ?')->execute([$roleId, $userId]);
        $message = 'User role updated.';
    }
}

//Fetch volunteers and projects (select query)
$volunteers = $pdo->query('
    SELECT u.user_id, u.first_name, u.last_name, u.email, r.role_name 
    FROM Users u 
    JOIN Roles r ON u.role_id = r.role_id 
    ORDER BY u.created_date DESC
')->fetchAll();

$projects = $pdo->query('SELECT project_id, title, start_date, description FROM Projects ORDER BY start_date ASC')->fetchAll();
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
      <h2>Create New Project</h2>
      <p>Publish a new volunteer opportunity.</p>
    </header>
    <form method="post" class="form-grid">
      <label>Title <input type="text" name="title" required></label>
      <label>Start Date <input type="date" name="event_date" required></label>
      <label>Description <textarea name="description" rows="3"></textarea></label>
      <button class="btn primary" type="submit" name="create_project" value="1">Publish Project</button>
    </form>
  </section>

  <section class="dashboard-panel">
    <header><h2>Active Projects</h2></header>
    <div class="dashboard-list">
      <?php foreach ($projects as $proj): ?>
        <article class="dashboard-item">
          <div>
            <h3><?= htmlspecialchars($proj['title']) ?></h3>
            <p><?= htmlspecialchars(date('F j, Y', strtotime($proj['start_date']))) ?></p>
            <p><?= htmlspecialchars($proj['description']) ?></p>
          </div>
          <form method="post">
            <button class="btn ghost" type="submit" name="delete_project" value="<?= (int)$proj['project_id'] ?>">Delete</button>
          </form>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="dashboard-panel">
    <header><h2>System Users</h2></header>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($volunteers as $user): ?>
            <tr>
              <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
              <td><?= htmlspecialchars($user['email']) ?></td>
              <td><?= htmlspecialchars($user['role_name']) ?></td>
              <td>
                <form method="post" class="inline-form">
                  <input type="hidden" name="user_id" value="<?= (int)$user['user_id'] ?>">
                  <select name="role">
                    <option value="volunteer" <?= $user['role_name'] === 'volunteer' ? 'selected' : '' ?>>Volunteer</option>
                    <option value="admin" <?= $user['role_name'] === 'admin' ? 'selected' : '' ?>>Admin</option>
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

