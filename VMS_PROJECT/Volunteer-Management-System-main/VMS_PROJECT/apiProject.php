<?php
// Simple API for project creation and editing using SQLite
header('Content-Type: application/json; charset=utf-8');

$dbFile = __DIR__ . '/projects.db';
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        start_date TEXT,
        end_date TEXT,
        status TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    // Applications table to store volunteer applications for projects
    $pdo->exec("CREATE TABLE IF NOT EXISTS applications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id INTEGER NOT NULL,
        applicant_name TEXT NOT NULL,
        applicant_email TEXT NOT NULL,
        message TEXT,
        status TEXT NOT NULL DEFAULT 'pending',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE
    )");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

function jsonOut($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'list') {
    $stmt = $pdo->query('SELECT id,title,description,start_date,end_date,status FROM projects ORDER BY id DESC');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonOut($rows);
}

if ($action === 'get') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { http_response_code(404); jsonOut(['success'=>false,'error'=>'Not found']); }
    jsonOut($row);
}

if ($action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $title = trim($input['title'] ?? '');
    if ($title === '') { http_response_code(400); jsonOut(['success'=>false,'error'=>'Title required']); }
    $stmt = $pdo->prepare('INSERT INTO projects (title,description,start_date,end_date,status) VALUES (:title,:description,:start_date,:end_date,:status)');
    $stmt->execute([
        ':title'=>$title,
        ':description'=>$input['description'] ?? '',
        ':start_date'=>$input['start_date'] ?? null,
        ':end_date'=>$input['end_date'] ?? null,
        ':status'=>$input['status'] ?? 'planned'
    ]);
    jsonOut(['success'=>true,'id'=>$pdo->lastInsertId()]);
}

if ($action === 'update') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) { http_response_code(400); jsonOut(['success'=>false,'error'=>'Missing id']); }
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $title = trim($input['title'] ?? '');
    if ($title === '') { http_response_code(400); jsonOut(['success'=>false,'error'=>'Title required']); }
    $stmt = $pdo->prepare('UPDATE projects SET title=:title,description=:description,start_date=:start_date,end_date=:end_date,status=:status WHERE id=:id');
    $stmt->execute([
        ':title'=>$title,
        ':description'=>$input['description'] ?? '',
        ':start_date'=>$input['start_date'] ?? null,
        ':end_date'=>$input['end_date'] ?? null,
        ':status'=>$input['status'] ?? 'planned',
        ':id'=>$id
    ]);
    jsonOut(['success'=>true,'rows'=>$stmt->rowCount()]);
}

if ($action === 'apply') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $project_id = isset($input['project_id']) ? (int)$input['project_id'] : (isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0);
    $name = trim((string)($input['applicant_name'] ?? ''));
    $email = trim((string)($input['applicant_email'] ?? ''));
    $message = trim((string)($input['message'] ?? ''));

    if ($project_id <= 0) { http_response_code(400); jsonOut(['success'=>false,'error'=>'Invalid project id']); }
    if ($name === '' || $email === '') { http_response_code(400); jsonOut(['success'=>false,'error'=>'Name and email are required']); }

    // ensure project exists
    $stmt = $pdo->prepare('SELECT id FROM projects WHERE id = :id');
    $stmt->execute([':id' => $project_id]);
    $proj = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$proj) { http_response_code(404); jsonOut(['success'=>false,'error'=>'Project not found']); }

    $ins = $pdo->prepare('INSERT INTO applications (project_id, applicant_name, applicant_email, message, status) VALUES (:pid, :name, :email, :msg, :status)');
    $ins->execute([
        ':pid' => $project_id,
        ':name' => $name,
        ':email' => $email,
        ':msg' => $message,
        ':status' => 'pending'
    ]);

    jsonOut(['success' => true, 'id' => $pdo->lastInsertId()]);
}

http_response_code(400);
jsonOut(['success'=>false,'error'=>'Unknown action']);
