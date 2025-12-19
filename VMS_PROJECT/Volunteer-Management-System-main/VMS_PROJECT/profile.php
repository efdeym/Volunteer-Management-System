<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$update_message = "";
$error_message  = "";

try {
    $stmt = $pdo->prepare("
        SELECT first_name, last_name, email, phone, password_hash,
               created_date, updated_date
        FROM Users
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("User not found.");
    }

    // Friendly display strings for created/updated
    $user['created_display'] = isset($user['created_date']) && $user['created_date'] ? date('F j, Y', strtotime($user['created_date'])) : '';
    $user['updated_display'] = isset($user['updated_date']) && $user['updated_date'] ? date('F j, Y, g:i A', strtotime($user['updated_date'])) : '';
} catch (PDOException $e) {
    die("Database error.");
}

try {
    // Fetch approved applications (past participations) joined with project info.
    $stmt = $pdo->prepare("
        SELECT p.title,
               COALESCE(p.end_date, p.start_date) AS event_date,
               A.status AS application_status,
               A.application_date,
               P.status AS participation_status,
               P.hours_logged
        FROM Applications A
        JOIN Projects p ON A.project_id = p.project_id
        LEFT JOIN Participations P ON P.event_id = p.project_id AND P.volunteer_id = A.volunteer_id
        WHERE A.volunteer_id = ? AND A.status = 'Approved'
        ORDER BY COALESCE(p.end_date, p.start_date) DESC
    ");
    $stmt->execute([$user_id]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format display-friendly date strings and ensure keys exist
    foreach ($events as &$ev) {
        $ev['event_date_display'] = isset($ev['event_date']) && $ev['event_date'] ? date('F j, Y', strtotime($ev['event_date'])) : 'Unknown date';
        $ev['application_date_display'] = isset($ev['application_date']) && $ev['application_date'] ? date('F j, Y', strtotime($ev['application_date'])) : '';
        $ev['participation_status'] = $ev['participation_status'] ?? null;
        $ev['hours_logged'] = isset($ev['hours_logged']) ? $ev['hours_logged'] : null;
    }
    unset($ev);
} catch (PDOException $e) {
    // If participations or other tables are missing, gracefully show no events
    $events = [];
}

if (isset($_POST['update_profile'])) {

    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $phone      = trim($_POST['phone']);

    if (!preg_match('/^[a-zA-ZğĞşŞıİöÖçÇ\s]+$/', $first_name) ||
        !preg_match('/^[a-zA-ZğĞşŞıİöÖçÇ\s]+$/', $last_name)) {
        $error_message = "Name can only contain letters.";
    }

    if ($phone && !preg_match('/^\d{10}$/', $phone)) {
        $error_message = "Phone number must be 10 digits.";
    }

    if (!$error_message) {
        $stmt = $pdo->prepare("
            UPDATE Users
            SET first_name = ?, last_name = ?, phone = ?
            WHERE user_id = ?
        ");
        $stmt->execute([$first_name, $last_name, $phone, $user_id]);

        $update_message = "Profile updated successfully.";

        $user['first_name'] = $first_name;
        $user['last_name']  = $last_name;
        $user['phone']      = $phone;
    }
}

/* ================= CHANGE PASSWORD ================= */
if (isset($_POST['change_password'])) {

    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new !== $confirm) {
        $error_message = "Passwords do not match.";
    } elseif (!password_verify($current, $user['password_hash'])) {
        $error_message = "Current password is incorrect.";
    } elseif (
        strlen($new) < 8 ||
        !preg_match('/[A-Z]/', $new) ||
        !preg_match('/[a-z]/', $new) ||
        !preg_match('/[0-9]/', $new) ||
        !preg_match('/[^a-zA-Z0-9]/', $new)
    ) {
        $error_message = "Password is not strong enough.";
    }

    if (!$error_message) {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            UPDATE Users SET password_hash = ? WHERE user_id = ?
        ");
        $stmt->execute([$hashed, $user_id]);

        $update_message = "Password changed successfully.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Dashboard</title>
<style>
body { font-family: Arial; max-width: 900px; margin: auto; }
section { margin-bottom: 25px; padding: 20px; border: 1px solid #ddd; }
.success { color: green; }
.error { color: red; }
input { display: block; margin-bottom: 10px; padding: 6px; }
</style>
</head>
<body>

<h2>User Dashboard</h2>

<?php if ($update_message): ?><p class="success"><?= $update_message ?></p><?php endif; ?>
<?php if ($error_message): ?><p class="error"><?= $error_message ?></p><?php endif; ?>

<section>
<h3>Profile Information</h3>
<p><strong>Name:</strong> <?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></p>
<p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
<p><strong>Phone:</strong> <?= htmlspecialchars($user['phone']) ?></p>
<?php if (!empty($user['created_display'])): ?>
<p><strong>Profile created:</strong> <?= htmlspecialchars($user['created_display']) ?></p>
<?php endif; ?>
<?php if (!empty($user['updated_display'])): ?>
<p><strong>Last updated:</strong> <?= htmlspecialchars($user['updated_display']) ?></p>
<?php endif; ?>
</section>

<section>
<h3>Update Profile</h3>
<form method="POST">
<input name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
<input name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
<input name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
<button name="update_profile">Save</button>
<?php if (!empty($user['updated_display'])): ?>
    <p class="muted">Last saved: <?= htmlspecialchars($user['updated_display']) ?></p>
<?php endif; ?>
</form>
</section>

<section>
<h3>Change Password</h3>
<form method="POST">
<input type="password" name="current_password" placeholder="Current Password" required>
<input type="password" name="new_password" placeholder="New Password" required>
<input type="password" name="confirm_password" placeholder="Confirm Password" required>
<button name="change_password">Change</button>
</form>
</section>

<section>
<h3>Past Activities</h3>
<?php if (!$events): ?>
<p>No past activities.</p>
<?php else: ?>
<ul>
<?php foreach ($events as $e): ?>
<li>
<strong><?= htmlspecialchars($e['title']) ?></strong><br>
<?= htmlspecialchars($e['event_date_display']) ?> – <strong>Status:</strong> <?= htmlspecialchars($e['participation_status'] ?? $e['application_status'] ?? 'Approved') ?>
<?php if (!is_null($e['hours_logged'])): ?>
    – <strong>Hours:</strong> <?= htmlspecialchars($e['hours_logged']) ?>
<?php endif; ?>
<?php if (!empty($e['application_date_display'])): ?>
    <div class="muted">Applied: <?= htmlspecialchars($e['application_date_display']) ?></div>
<?php endif; ?>
</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</section>

</body>
</html>
