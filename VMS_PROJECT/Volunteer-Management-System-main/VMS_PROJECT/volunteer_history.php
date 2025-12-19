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
    <title>My Volunteer History – VMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<header class="app-header">
    <div class="brand">
        <img src="assets/logo.svg" alt="VMS logo">
        <div><strong>Volunteer History</strong><p><?= htmlspecialchars($currentUser['name']) ?></p></div>
    </div>
    <div class="header-actions">
        <a class="pill" href="dashboard.php">Dashboard</a>
        <a class="pill" href="logout.php">Logout</a>
    </div>
</header>

<main class="dashboard container" style="margin-top: 20px;">
    <section class="dashboard-panel">
        <header>
            <h2>My Applications</h2>
            <p>View the status of your past and current volunteer requests.</p>
        </header>

        <?php if (empty($history)): ?>
            <p>You haven't applied for any projects yet.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Project Title</th>
                            <th>Applied Date</th>
                            <th>Status</th>
                            <th>Decision Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['project_title']) ?></td>
                            <td><?= date('Y-m-d', strtotime($row['application_date'])) ?></td>
                            <td>
                                <span class="pill status-<?= strtolower($row['status']) ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?= $row['decision_date'] ? date('Y-m-d', strtotime($row['decision_date'])) : '—' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
