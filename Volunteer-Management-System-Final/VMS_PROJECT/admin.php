<?php

//Don't clean commed lines. I wrote for that I can remember and you can understand my code. (Elif)
//this page's purpose is to handle admin functions
require_once __DIR__ . '/auth.php';

//only admin users can access this page

//role kontrol sunum için
if ($currentUser['role'] !== 'Admin') {
    header('Location: about.html');
    exit;
}

$activeTab = $_GET['tab'] ?? 'dashboard';
$searchQuery = $_GET['q'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
  //user CRUD operations
    if (isset($_POST['add_user'])) {
        $fname = trim($_POST['first_name']);
        $lname = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = (int)$_POST['role_id'];

        try {
            $stmt = $pdo->prepare("INSERT INTO Users (first_name, last_name, email, password_hash, role_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$fname, $lname, $email, $pass, $role]);
            $message = "New user created successfully.";
        } catch (PDOException $e) {
            $error = "Error adding user: " . $e->getMessage();
        }
    }

    if (isset($_POST['delete_user'])) {
        $uid = (int)$_POST['user_id'];
        if ($uid == $currentUser['user_id']) {
            $error = "You cannot delete your own account!";
        } else {
            $pdo->prepare("DELETE FROM Users WHERE user_id = ?")->execute([$uid]);
            $message = "User deleted.";
        }
    }

    if (isset($_POST['update_user_role'])) {
        $uid = (int)$_POST['user_id'];
        $rid = (int)$_POST['role_id'];
        $pdo->prepare("UPDATE Users SET role_id = ? WHERE user_id = ?")->execute([$rid, $uid]);
        $message = "User role updated.";
    }

    //PROJECT crud operations
    if (isset($_POST['create_project'])) {
        $title = trim($_POST['title']);
        $org_id = (int)$_POST['org_id'];
        $desc = trim($_POST['description']);
        $sdate = $_POST['event_date'];
        
        if ($title && $org_id) {
            $stmt = $pdo->prepare("INSERT INTO Projects (title, description, start_date, org_id, manager_id, status) VALUES (?, ?, ?, ?, ?, 'Open')");
            $stmt->execute([$title, $desc, $sdate, $org_id, $currentUser['user_id']]);
            $message = "Project created successfully.";
        }
    }

    if (isset($_POST['delete_project'])) {
        $pid = (int)$_POST['project_id'];
        $pdo->prepare("DELETE FROM Projects WHERE project_id = ?")->execute([$pid]);
        $message = "Project deleted.";
    }

    //organization CRUD operations
    if (isset($_POST['add_org'])) {
        $orgName = trim($_POST['org_name']);
        $orgDesc = trim($_POST['description']);
        $contactId = (int)$_POST['contact_user_id'];

        $pdo->prepare("INSERT INTO Organizations (org_name, description, contact_user_id) VALUES (?, ?, ?)")
            ->execute([$orgName, $orgDesc, $contactId]);
        $message = "Organization added.";
    }

    if (isset($_POST['delete_org'])) {
        $oid = (int)$_POST['org_id'];
        try {
            $pdo->prepare("DELETE FROM Organizations WHERE org_id = ?")->execute([$oid]);
            $message = "Organization deleted.";
        } catch (PDOException $e) {
            $error = "Cannot delete organization. It might have active projects.";
        }
    }

    if (isset($_POST['update_org_desc'])) {
        $oid = (int)$_POST['org_id'];
        $newDesc = trim($_POST['description']);
        $pdo->prepare("UPDATE Organizations SET description = ? WHERE org_id = ?")->execute([$newDesc, $oid]);
        $message = "Organization updated.";
    }

    //application CRUD operations
    if (isset($_POST['update_application'])) {
        $appId = (int)$_POST['app_id'];
        $status = $_POST['status']; 
        $pdo->prepare("UPDATE Applications SET status = ?, decision_date = NOW() WHERE application_id = ?")->execute([$status, $appId]);
        $message = "Application status updated.";
    }

    if (isset($_POST['delete_application'])) {
        $appId = (int)$_POST['app_id'];
        $pdo->prepare("DELETE FROM Applications WHERE application_id = ?")->execute([$appId]);
        $message = "Application deleted.";
    }
}

//fetch operations (databasedeki verileri çekmek için)
$rolesList = $pdo->query("SELECT * FROM Roles")->fetchAll();

$usersListAll = $pdo->query("SELECT * FROM Users ORDER BY first_name ASC")->fetchAll(); 

$orgList = $pdo->query("
    SELECT o.*, u.first_name, u.last_name 
    FROM Organizations o 
    LEFT JOIN Users u ON o.contact_user_id = u.user_id
")->fetchAll();

$userQuery = "SELECT u.*, r.role_name FROM Users u LEFT JOIN Roles r ON u.role_id = r.role_id";
if ($activeTab == 'users' && $searchQuery) {
    $userQuery .= " WHERE u.first_name LIKE '%$searchQuery%' OR u.email LIKE '%$searchQuery%'";
}
$userQuery .= " ORDER BY u.created_date DESC";
$usersList = $pdo->query($userQuery)->fetchAll();

$projectsList = $pdo->query("SELECT p.*, o.org_name FROM Projects p JOIN Organizations o ON p.org_id = o.org_id ORDER BY p.start_date DESC")->fetchAll();

$appsList = $pdo->query("
    SELECT a.*, u.first_name, u.last_name, u.email, p.title as project_title 
    FROM Applications a
    JOIN Users u ON a.volunteer_id = u.user_id
    JOIN Projects p ON a.project_id = p.project_id
    ORDER BY a.application_date DESC
")->fetchAll();

$stats = [
    'users' => count($usersList),
    'projects' => count($projectsList),
    'apps' => count($appsList)
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - VMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
    <style>
        body.site-body {
            display: flex;
            min-height: 100vh;
            padding: 0;
            background: #f4f6f9;
            color: #333;
        }

        .admin-sidebar {
            width: 260px;
            background: linear-gradient(180deg, #6a11cb 0%, #a008b4ff 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            padding: 20px;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .admin-sidebar .brand {
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-sidebar .brand img {
            max-width: 150px;
            filter: brightness(0) invert(1);
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .sidebar-link {
            display: block;
            padding: 12px 16px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .sidebar-link:hover, .sidebar-link.active {
            background: rgba(255,255,255,0.2);
            color: #fff;
            transform: translateX(5px);
        }

        .sidebar-footer {
            margin-top: auto;
            border-top: 1px solid rgba(255,255,255,0.2);
            padding-top: 20px;
        }

        .admin-main {
            flex: 1;
            margin-left: 260px;
            padding: 40px;
            overflow-y: auto;
        }

        .dashboard-panel, .stat-card {
            background: #ffffff;
            color: #333 !important;
            border: 1px solid #e0e0e0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .stat-card h3 {
            color: #2575fc;
            font-size: 2.5rem;
            margin: 0;
        }
        
        .stat-card p {
            color: #666;
            margin: 5px 0 0;
        }

        table th { color: #555; background: #f9f9f9; }
        table td { color: #333; }
        h2, h3 { color: #222; }
        label { color: #444; }
        
        .btn.ghost {
             color: #d9534f;
             border-color: #d9534f;
        }
        .btn.ghost:hover {
            background: #d9534f;
            color: white;
        }

        .site-header, .site-hero { display: none !important; }
    </style>
</head>
<body class="site-body">

<aside class="admin-sidebar">
    <a href="about.html" class="brand">
        <img src="assets/logo.svg" alt="VMS Logo">
    </a>
    
    <nav class="sidebar-nav">
        <a href="?tab=dashboard" class="sidebar-link <?= $activeTab=='dashboard'?'active':'' ?>">Dashboard</a>
        <a href="?tab=users" class="sidebar-link <?= $activeTab=='users'?'active':'' ?>">Users</a>
        <a href="?tab=projects" class="sidebar-link <?= $activeTab=='projects'?'active':'' ?>">Projects</a>
        <a href="?tab=orgs" class="sidebar-link <?= $activeTab=='orgs'?'active':'' ?>">Organizations</a>
        <a href="?tab=applications" class="sidebar-link <?= $activeTab=='applications'?'active':'' ?>">Applications</a>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php" class="sidebar-link">Logout</a>
    </div>
</aside>

<main class="admin-main">

    <?php if (!empty($message)): ?>
        <div class="alert success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($activeTab == 'dashboard'): ?>
        <header style="margin-bottom: 30px;">
            <h2>Dashboard Overview</h2>
        </header>
        <section class="dashboard-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <article class="stat-card" style="padding: 30px; border-radius: 12px;">
                <h3><?= $stats['users'] ?></h3> 
                <p>Total Users</p>
            </article>
            <article class="stat-card" style="padding: 30px; border-radius: 12px;">
                <h3><?= $stats['projects'] ?></h3> 
                <p>Active Projects</p>
            </article>
            <article class="stat-card" style="padding: 30px; border-radius: 12px;">
                <h3><?= $stats['apps'] ?></h3> 
                <p>Pending Applications</p>
            </article>
        </section>
    <?php endif; ?>

    <?php if ($activeTab == 'users'): ?>
        <section class="dashboard-panel">
            <header class="section-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h2>User Management</h2>
            </header>
            
            <form method="get" class="search-bar" style="margin: 20px 0;">
                <input type="hidden" name="tab" value="users">
                <input type="text" name="q" placeholder="Search by name or email..." value="<?= htmlspecialchars($searchQuery) ?>" style="padding:10px; border:1px solid #ccc; width: 300px;">
                <button type="submit" class="btn secondary">Search</button>
            </form>

            <details style="margin-bottom:20px; border:1px solid #e0e0e0; padding:15px; border-radius:8px; background:#fcfcfc;">
                <summary style="cursor:pointer; color:#2575fc; font-weight:bold;">+ Add New User</summary>
                <form method="post" class="form-grid" style="margin-top:15px;">
                    <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <input type="text" name="first_name" placeholder="First Name" required>
                        <input type="text" name="last_name" placeholder="Last Name" required>
                    </div>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <select name="role_id">
                        <?php foreach ($rolesList as $role): ?>
                            <option value="<?= $role['role_id'] ?>"><?= $role['role_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="add_user" class="btn primary">Create User</button>
                </form>
            </details>

            <div class="table-wrapper">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:2px solid #eee;">
                            <th style="text-align:left; padding:12px;">Name</th>
                            <th style="text-align:left; padding:12px;">Email</th>
                            <th style="text-align:left; padding:12px;">Role</th>
                            <th style="text-align:right; padding:12px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usersList as $u): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:12px; font-weight:bold;"><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></td>
                            <td style="padding:12px;"><?= htmlspecialchars($u['email']) ?></td>
                            <td style="padding:12px;">
                                <form method="post" class="inline-form">
                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                    <select name="role_id" onchange="this.form.submit()" style="padding:6px; border-radius:4px; border:1px solid #ddd;">
                                        <?php foreach ($rolesList as $role): ?>
                                            <option value="<?= $role['role_id'] ?>" <?= $u['role_id'] == $role['role_id'] ? 'selected' : '' ?>>
                                                <?= $role['role_name'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="update_user_role" value="1">
                                </form>
                            </td>
                            <td style="padding:12px; text-align:right;">
                                <form method="post" onsubmit="return confirm('Delete this user?');" style="display:inline;">
                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                    <button type="submit" name="delete_user" class="btn ghost" style="padding:6px 12px; font-size:12px;">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($activeTab == 'projects'): ?>
        <section class="dashboard-panel">
            <header class="section-header"><h2>Project Management</h2></header>
            
            <details style="margin:20px 0; border:1px solid #e0e0e0; padding:15px; border-radius:8px; background:#fcfcfc;">
                <summary style="cursor:pointer; color:#2575fc; font-weight:bold;">+ Create New Project</summary>
                <form method="post" class="form-grid" style="margin-top: 15px;">
                     <div class="form-row" style="display:grid; grid-template-columns: 2fr 1fr; gap:16px;">
                         <label>Title <input type="text" name="title" required></label>
                         <label>Organization
                             <select name="org_id" required>
                                 <option value="">Select...</option>
                                 <?php foreach ($orgList as $org): ?>
                                     <option value="<?= $org['org_id'] ?>"><?= htmlspecialchars($org['org_name']) ?></option>
                                 <?php endforeach; ?>
                             </select>
                         </label>
                     </div>
                     <label>Start Date <input type="date" name="event_date" required></label>
                     <label>Description <textarea name="description" rows="2"></textarea></label>
                     <button class="btn primary" type="submit" name="create_project" value="1">Publish Project</button>
                </form>
            </details>

            <div class="dashboard-list">
                 <?php foreach ($projectsList as $proj): ?>
                   <article class="dashboard-item" style="border:1px solid #eee; padding:15px; margin-bottom:10px; border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                     <div>
                       <h3 style="margin:0 0 5px 0;"><?= htmlspecialchars($proj['title']) ?></h3>
                       <small style="color:#777;">Org: <?= htmlspecialchars($proj['org_name']) ?> | Date: <?= $proj['start_date'] ?></small>
                       <p style="margin:5px 0 0 0; color:#555;"><?= htmlspecialchars($proj['description']) ?></p>
                     </div>
                     <form method="post">
                       <input type="hidden" name="project_id" value="<?= $proj['project_id'] ?>">
                       <button class="btn ghost" type="submit" name="delete_project" onclick="return confirm('Delete?');">Delete</button>
                     </form>
                   </article>
                 <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($activeTab == 'orgs'): ?>
        <section class="dashboard-panel">
            <header class="section-header"><h2>Organization Management</h2></header>
            
            <div style="margin:20px 0; padding:20px; background:#f9f9f9; border-radius:8px; border:1px solid #eee;">
                <h4>Add New Organization</h4>
                <form method="post" class="form-grid" style="margin-top:10px;">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                        <input type="text" name="org_name" placeholder="Organization Name" required>
                        <select name="contact_user_id" required>
                            <option value="">Select Contact Person...</option>
                            <?php foreach ($usersListAll as $u): ?>
                                <option value="<?= $u['user_id'] ?>"><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="text" name="description" placeholder="Description / Purpose" style="margin-top:10px;">
                    <button type="submit" name="add_org" class="btn primary" style="margin-top:10px;">Add Organization</button>
                </form>
            </div>

            <div class="table-wrapper">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:2px solid #eee;">
                            <th style="text-align:left; padding:12px;">Organization</th>
                            <th style="text-align:left; padding:12px;">Description (Edit)</th>
                            <th style="text-align:left; padding:12px;">Contact Person</th>
                            <th style="text-align:right; padding:12px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orgList as $org): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:12px; font-weight:bold;"><?= htmlspecialchars($org['org_name']) ?></td>
                            <td style="padding:12px;">
                                <form method="post" style="display:flex; gap:5px;">
                                    <input type="hidden" name="org_id" value="<?= $org['org_id'] ?>">
                                    <input type="text" name="description" value="<?= htmlspecialchars($org['description'] ?? '') ?>" style="padding:5px; border:1px solid #ddd; border-radius:4px;">
                                    <button type="submit" name="update_org_desc" class="btn secondary" style="padding:5px 10px; font-size:12px;">Save</button>
                                </form>
                            </td>
                            <td style="padding:12px;"><?= htmlspecialchars($org['first_name'] . ' ' . $org['last_name']) ?></td>
                            <td style="padding:12px; text-align:right;">
                                <form method="post" onsubmit="return confirm('Delete this organization? All its projects will be deleted too!');">
                                    <input type="hidden" name="org_id" value="<?= $org['org_id'] ?>">
                                    <button type="submit" name="delete_org" class="btn ghost" style="padding:6px 12px; font-size:12px;">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($activeTab == 'applications'): ?>
        <section class="dashboard-panel">
            <header class="section-header"><h2>Volunteer Applications</h2></header>
            <div class="table-wrapper">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="border-bottom:2px solid #eee;">
                            <th style="text-align:left; padding:12px;">Volunteer</th>
                            <th style="text-align:left; padding:12px;">Project</th>
                            <th style="text-align:left; padding:12px;">Status</th>
                            <th style="text-align:right; padding:12px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appsList as $app): ?>
                        <tr style="border-bottom:1px solid #eee;">
                            <td style="padding:12px;">
                                <strong><?= htmlspecialchars($app['first_name']) ?></strong><br>
                                <small style="color:#666;"><?= $app['email'] ?></small>
                            </td>
                            <td style="padding:12px;"><?= htmlspecialchars($app['project_title']) ?></td>
                            <td style="padding:12px;">
                                <span style="font-weight:bold; color: <?= $app['status']=='Approved'?'green':($app['status']=='Rejected'?'red':'orange') ?>">
                                    <?= $app['status'] ?>
                                </span>
                            </td>
                            <td style="padding:12px; text-align:right;">
                                <div style="display:flex; justify-content:flex-end; gap:5px;">
                                    <?php if($app['status'] == 'Pending'): ?>
                                        <form method="post">
                                            <input type="hidden" name="app_id" value="<?= $app['application_id'] ?>">
                                            <button type="submit" name="update_application" onclick="this.form.appendChild(document.createElement('input')).setAttribute('name', 'status'); this.form.lastChild.setAttribute('value', 'Approved');" class="btn success" style="color:green; border-color:green; padding:5px;">Approve</button>
                                            <button type="submit" name="update_application" onclick="this.form.appendChild(document.createElement('input')).setAttribute('name', 'status'); this.form.lastChild.setAttribute('value', 'Rejected');" class="btn danger" style="color:red; border-color:red; padding:5px;">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color:#aaa; font-size:12px; padding:5px;">Decided</span>
                                    <?php endif; ?>
                                    
                                    <form method="post" onsubmit="return confirm('Delete this application record?');">
                                        <input type="hidden" name="app_id" value="<?= $app['application_id'] ?>">
                                        <button type="submit" name="delete_application" class="btn ghost" style="padding:5px 10px; font-size:12px;">Del</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

</main>

</body>
</html></html>