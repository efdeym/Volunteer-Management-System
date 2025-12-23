<?php

session_start();

//role kontrol sunum için
if (!isset($_SESSION['user']) || $_SESSION['user']['role_id'] != 2) {
    header("Location: login.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organization Dashboard </title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
    <script defer src="org_dashboard.js"></script>
    <style>
        .tab-nav {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .tab-btn {
            background: transparent;
            border: 2px solid rgba(255,255,255,0.2);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 2rem;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .tab-btn:hover { background: rgba(255,255,255,0.1); }
        .tab-btn.active {
            background: white;
            color: #4f46e5; 
            border-color: white;
        }
        
        .tab-content { display: none; animation: fadeIn 0.4s; }
        .tab-content.active { display: block; }

        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(10px); } 
            to { opacity: 1; transform: translateY(0); } 
        }

        body { color: #333; }

        .table-wrapper {
            overflow-x: auto;
            margin-top: 1rem;
            background: white;
            border-radius: 8px;
            padding: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }
        th { text-align: left; padding: 12px; color: #666; border-bottom: 2px solid #eee; }
        td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; left: 0; 
            width: 100%; height: 100%; 
            background: rgba(0,0,0,0.5); 
            justify-content: center; 
            align-items: center; 
            z-index: 999; 
        }
        .modal-content { 
            background: white; 
            padding: 2rem; 
            border-radius: 12px; 
            width: 100%; 
            max-width: 500px; 
            position: relative; 
            color: #333;
        }
        .close-btn { 
            position: absolute; 
            top: 15px; right: 20px; 
            font-size: 1.5rem; 
            cursor: pointer; 
            color: #999; 
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-open { background: #dcfce7; color: #166534; }
        .status-closed { background: #fee2e2; color: #991b1b; }
        .status-full { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body class="site-body">

<header class="site-hero" style="min-height: auto; padding-bottom: 3rem;">
  <nav class="site-nav">
    <a href="index.php" class="brand">
        <img src="assets/logo.svg" alt="VMS Logo">
    </a>
    <div class="nav-name">Organization Panel</div>
    <div class="nav-links">
       <a href="index.php" class="pill">Home</a>
       <a href="logout.php" class="pill" style="color: #ff8a8a;">Logout</a>
    </div>
  </nav>

  <div class="hero-grid">
    <div style="text-align: center; margin: 0 auto;">
      <h1>Organization Manager Dashboard</h1>
      <p class="lead">Manage your projects and review volunteer applications.</p>
      
      <div class="tab-nav">
          <button class="tab-btn active" data-target="projects">My Projects</button>
          <button class="tab-btn" data-target="applications">Applications</button>
      </div>
    </div>
  </div>
</header>

<main>
  
  <section id="projects" class="section tab-content active">
    <div class="auth-card" style="max-width: 800px; margin: 0 auto 2rem auto; background: white; padding: 20px; border-radius: 12px;">
        <h3 style="margin-bottom: 1.5rem; color: #4f46e5;">Create New Project</h3>
        <form id="createProjectForm" class="form-grid">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <label>
                    Project Title
                    <input type="text" name="title" required placeholder="ex: Beach Cleanup">
                </label>
                <label>
                    Location
                    <input type="text" name="location" required placeholder="ex: Istanbul Moda">
                </label>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 10px;">
                <label>
                    Start Date
                    <input type="date" name="start_date" required>
                </label>
                <label>
                    End Date
                    <input type="date" name="end_date">
                </label>
            </div>

            <label style="display: block; margin-top: 10px;">
                Max Capacity
                <input type="number" name="max_capacity" min="1" value="1" required>
            </label>

            <label style="display: block; margin-top: 10px;">
                Description
                <textarea name="description" rows="3" required placeholder="Details regarding the event..." style="width: 100%; border: 1px solid #ddd; border-radius: 4px; padding: 8px;"></textarea>
            </label>

            <button class="btn primary" type="submit" style="margin-top: 15px; width: 100%;">Create Project</button>
        </form>
    </div>

    <div class="auth-card" style="max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 12px;">
        <h3>My Projects List</h3>
        <div class="table-wrapper">
            <table id="projectsTable">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Location</th>
                        <th>Dates</th>
                        <th>Cap.</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    </tbody>
            </table>
        </div>
    </div>
  </section>

  <section id="applications" class="section tab-content">
    <div class="auth-card" style="max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 12px;">
        <h3>Pending Applications</h3>
        <p class="lead" style="font-size: 0.9rem; margin-bottom: 1rem; color: #666;">Review volunteers who applied to your projects.</p>
        
        <div class="table-wrapper">
            <table id="applicationsTable">
                <thead>
                    <tr>
                        <th>Volunteer Name</th>
                        <th>Email</th>
                        <th>Applied Project</th>
                        <th>Date</th>
                        <th style="text-align: right;">Decision</th>
                    </tr>
                </thead>
                <tbody>
                    </tbody>
            </table>
        </div>
    </div>
  </section>

</main>

<footer class="site-footer" style="text-align: center; padding: 2rem; color: #fff;">
    VolunteerHub 
</footer>

<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3 style="color:#333; margin-bottom:15px;">Edit Project</h3>
        
        <form id="editProjectForm" class="form-grid">
            <input type="hidden" name="project_id" id="edit_project_id">
            
            <label>Title <input type="text" name="title" id="edit_title" required style="width: 100%; margin-bottom: 10px;"></label>
            <label>Location <input type="text" name="location" id="edit_location" required style="width: 100%; margin-bottom: 10px;"></label>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                <label>Start Date <input type="date" name="start_date" id="edit_start_date" required></label>
                <label>End Date <input type="date" name="end_date" id="edit_end_date"></label>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top: 10px;">
                <label>Capacity <input type="number" name="max_capacity" id="edit_capacity" required></label>
                <label>Status 
                    <select name="status" id="edit_status" style="width: 100%; padding: 5px;">
                        <option value="Open">Open</option>
                        <option value="Closed">Closed</option>
                        <option value="Completed">Completed</option>
                    </select>
                </label>
            </div>

            <label style="display: block; margin-top: 10px;">Description <textarea name="description" id="edit_description" rows="3" required style="width: 100%;"></textarea></label>
            
            <button class="btn primary" type="submit" style="background-color:#f59e0b; border:none; margin-top: 15px; width: 100%; cursor: pointer; color: white; padding: 10px; border-radius: 4px;">Save Changes</button>
        </form>
    </div>
</div>

<script>
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            
            btn.classList.add('active');
            const targetId = btn.getAttribute('data-target');
            document.getElementById(targetId).classList.add('active');
        });
    });
</script>

</body>
</html>