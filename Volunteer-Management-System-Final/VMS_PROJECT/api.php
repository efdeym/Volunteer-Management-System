<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); 

require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

$action = isset($_GET['action']) ? $_GET['action'] : '';

function jsonOut($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $currentUserId = $_SESSION['user']['user_id'] ?? 0;
    $currentUserRoleId = $_SESSION['user']['role_id'] ?? 0;

    // Public Actions
    if ($action === 'list') {
        $stmt = $pdo->query('SELECT P.*, O.org_name FROM Projects P JOIN Organizations O ON P.org_id = O.org_id ORDER BY P.created_date DESC');
        jsonOut($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    if ($action === 'get') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $stmt = $pdo->prepare('SELECT * FROM Projects WHERE project_id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { jsonOut(['success'=>false,'error'=>'Project not found']); }
        jsonOut($row);
    }

    // Role-Based Protection
    $managerActions = ['create_project', 'delete_project', 'update_project', 'get_my_projects', 'get_applications', 'update_application'];
    if (in_array($action, $managerActions)) {
        if ($currentUserRoleId != 2) {
            jsonOut(['success'=>false, 'error'=>'Access Denied. Manager role required.']);
        }
    }

    // Manager: Get Applications for Dashboard
    if ($action === 'get_applications') {
        $sql = "SELECT A.application_id, A.application_date, U.first_name, U.last_name, U.email, P.title as project_title 
                FROM Applications A
                JOIN Users U ON A.volunteer_id = U.user_id
                JOIN Projects P ON A.project_id = P.project_id
                WHERE P.manager_id = ? AND A.status = 'Pending'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentUserId]);
        jsonOut(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // Manager: Get Own Projects
    if ($action === 'get_my_projects') {
        $stmt = $pdo->prepare('SELECT * FROM Projects WHERE manager_id = ? ORDER BY created_date DESC');
        $stmt->execute([$currentUserId]);
        jsonOut(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // Manager: Create Project
    if ($action === 'create_project') {
        $orgStmt = $pdo->prepare("SELECT org_id FROM Organizations WHERE contact_user_id = ?");
        $orgStmt->execute([$currentUserId]);
        $orgRow = $orgStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$orgRow) { jsonOut(['success'=>false, 'error'=>'Organization not found.']); }
        
        $sql = "INSERT INTO Projects (org_id, manager_id, title, description, location, start_date, end_date, max_capacity, status) 
                VALUES (:org, :mgr, :title, :desc, :loc, :sdate, :edate, :cap, 'Open')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':org'   => $orgRow['org_id'], 
            ':mgr'   => $currentUserId, 
            ':title' => trim($_POST['title']), 
            ':desc'  => trim($_POST['description']),
            ':loc'   => trim($_POST['location']), 
            ':sdate' => $_POST['start_date'], 
            ':edate' => !empty($_POST['end_date']) ? $_POST['end_date'] : null, 
            ':cap'   => (int)$_POST['max_capacity']
        ]);
        jsonOut(['success'=>true]);
    }

    // Manager: Update Project
    if ($action === 'update_project') {
        $sql = "UPDATE Projects SET title = :title, description = :desc, location = :loc, 
                start_date = :sdate, end_date = :edate, max_capacity = :cap, status = :status 
                WHERE project_id = :id AND manager_id = :mgr";
        $stmt = $pdo->prepare($sql);
        $stmt.execute([
            ':title' => $_POST['title'],
            ':desc'  => $_POST['description'],
            ':loc'   => $_POST['location'],
            ':sdate' => $_POST['start_date'],
            ':edate' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            ':cap'   => $_POST['max_capacity'],
            ':status'=> $_POST['status'],
            ':id'    => $_POST['project_id'],
            ':mgr'   => $currentUserId
        ]);
        jsonOut(['success'=>true]);
    }

    // Manager: Delete Project
    if ($action === 'delete_project') {
        $stmt = $pdo->prepare("DELETE FROM Projects WHERE project_id = ? AND manager_id = ?");
        $stmt->execute([$_POST['project_id'], $currentUserId]);
        jsonOut(['success'=>true]);
    }

    // Manager: Approve/Reject Application
    if ($action === 'update_application') {
        $aid = (int)$_POST['app_id'];
        $status = $_POST['status']; 

        $pdo->beginTransaction();
        $stmtApp = $pdo->prepare("SELECT project_id, volunteer_id FROM Applications WHERE application_id = ?");
        $stmtApp->execute([$aid]);
        $app = $stmtApp->fetch(PDO::FETCH_ASSOC);

        if (!$app) { jsonOut(['success'=>false, 'error'=>'Application not found.']); }

        if ($status === 'Approved') {
            $stmtCap = $pdo->prepare("SELECT max_capacity, (SELECT COUNT(*) FROM Applications WHERE project_id = ? AND status = 'Approved') as current_approved FROM Projects WHERE project_id = ?");
            $stmtCap->execute([$app['project_id'], $app['project_id']]);
            $capData = $stmtCap->fetch(PDO::FETCH_ASSOC);

            if ($capData['current_approved'] >= $capData['max_capacity']) {
                $pdo->rollBack();
                jsonOut(['success'=>false, 'error'=>'Project is full.']);
            }

            $stmtPart = $pdo->prepare("INSERT INTO Participations (volunteer_id, event_id, status) VALUES (?, ?, 'Registered')");
            $stmtPart->execute([$app['volunteer_id'], $app['project_id']]);

            if ($capData['current_approved'] + 1 >= $capData['max_capacity']) {
                $pdo->prepare("UPDATE Projects SET status = 'Full' WHERE project_id = ?")->execute([$app['project_id']]);
            }
        }

        $pdo->prepare("UPDATE Applications SET status = ?, decision_date = NOW() WHERE application_id = ?")->execute([$status, $aid]);
        $pdo->commit();
        jsonOut(['success'=>true]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    jsonOut(['success'=>false, 'error' => $e->getMessage()]);
}