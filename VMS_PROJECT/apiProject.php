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

http_response_code(400);
jsonOut(['success'=>false,'error'=>'Unknown action']);
