<?php
require_once 'api.php'; // ensures DB/table exist

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$project = null;

if ($id > 0) {
    $pdo = new PDO('sqlite:' . __DIR__ . '/projects.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$project) {
    http_response_code(404);
    echo "<p>Project not found. <a href=\"index.html\">Back</a></p>";
    exit;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Edit Project</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        body {
            padding: 20px;
            background: #f5f7fb;
        }

        main {
            max-width: 640px;
            margin: 0 auto;
        }

        .edit-card {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.15);
        }

        .edit-header {
            margin-bottom: 24px;
        }

        .edit-header h1 {
            margin: 0;
            font-size: 2rem;
            color: #0f172a;
        }
    </style>
</head>

<body>
    <main>
        <div class="edit-card">
            <div class="edit-header">
                <h1>Edit Project</h1>
            </div>
            <form id="editForm">
                <label class="field">
                    <span class="label-text">Project Title</span>
                    <input name="title" required value="<?= htmlspecialchars($project['title']) ?>">
                </label>
                <label class="field">
                    <span class="label-text">Description</span>
                    <textarea name="description"><?= htmlspecialchars($project['description']) ?></textarea>
                </label>
                <div class="row two-cols">
                    <label class="field">
                        <span class="label-text">Start Date</span>
                        <input type="date" name="start_date" value="<?= htmlspecialchars($project['start_date']) ?>">
                    </label>
                    <label class="field">
                        <span class="label-text">End Date</span>
                        <input type="date" name="end_date" value="<?= htmlspecialchars($project['end_date']) ?>">
                    </label>
                </div>
                <label class="field">
                    <span class="label-text">Status</span>
                    <select name="status">
                        <?php
                        $statuses = ['planned' => 'Planned', 'active' => 'Active', 'completed' => 'Completed'];
                        foreach ($statuses as $value => $label) {
                            $selected = $project['status'] === $value ? 'selected' : '';
                            echo "<option value=\"{$value}\" {$selected}>{$label}</option>";
                        }
                        ?>
                    </select>
                </label>
                <div class="actions">
                    <button class="btn primary" type="submit">Save Changes</button>
                    <a class="btn ghost" href="index.html">Back</a>
                </div>
            </form>
        </div>
    </main>

    <div id="toast" class="toast" aria-live="polite"></div>

    <script>
        const toast = document.getElementById('toast');

        function showToast(message, timeout = 3000) {
            if (!toast) return alert(message);
            toast.textContent = message;
            toast.classList.add('show');
            clearTimeout(showToast._t);
            showToast._t = setTimeout(() => toast.classList.remove('show'), timeout);
        }

        document.getElementById('editForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            const formData = new FormData(this);
            const payload = Object.fromEntries(formData.entries());
            try {
                const response = await fetch('api.php?action=update&id=<?= (int) $project['id'] ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (result.success) {
                    showToast('Project updated successfully!');
                    setTimeout(() => { window.location = 'index.html'; }, 1000);
                } else {
                    showToast('Error: ' + (result.error || 'Unknown issue'));
                }
            } catch (err) {
                console.error(err);
                showToast('Network error');
            }
        });
    </script>
</body>

</html>
