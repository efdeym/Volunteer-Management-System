<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

$action = $_GET['action'] ?? '';
if (!isset($_SESSION['user'])) {
    respond(['error' => 'Not authenticated'], 401);
}

$dbPath = __DIR__ . DIRECTORY_SEPARATOR . 'dashboard.sqlite';
try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('CREATE TABLE IF NOT EXISTS departments (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT UNIQUE, head TEXT, budget INTEGER, description TEXT)');
    $db->exec('CREATE TABLE IF NOT EXISTS employees (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, department TEXT, position TEXT, phone TEXT, avatar TEXT)');
} catch (Exception $e) {
    respond(['error' => 'Database unavailable'], 500);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

switch ($action) {
    case 'get_dashboard':
        $departments = $db->query('SELECT * FROM departments ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
        $employees = $db->query('SELECT * FROM employees ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
        respond(['departments' => $departments, 'employees' => $employees]);
        break;

    case 'add_department':
        $name = trim($input['name'] ?? '');
        $head = trim($input['head'] ?? '');
        $budget = (int)($input['budget'] ?? 0);
        $description = trim($input['description'] ?? '');
        if ($name === '' || $head === '' || $budget <= 0 || $description === '') {
            respond(['error' => 'Missing fields'], 400);
        }
        try {
            $stmt = $db->prepare('INSERT INTO departments (name, head, budget, description) VALUES (:name, :head, :budget, :description)');
            $stmt->execute([':name' => $name, ':head' => $head, ':budget' => $budget, ':description' => $description]);
            respond(['success' => true]);
        } catch (Exception $e) {
            respond(['error' => 'Department already exists'], 409);
        }
        break;

    case 'update_department':
        $id = (int)($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $head = trim($input['head'] ?? '');
        $budget = (int)($input['budget'] ?? 0);
        $description = trim($input['description'] ?? '');
        if ($id <= 0 || $name === '' || $head === '' || $budget <= 0 || $description === '') {
            respond(['error' => 'Missing fields'], 400);
        }
        $existing = $db->prepare('SELECT name FROM departments WHERE id = :id');
        $existing->execute([':id' => $id]);
        $row = $existing->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            respond(['error' => 'Department not found'], 404);
        }
        $oldName = $row['name'];
        try {
            $stmt = $db->prepare('UPDATE departments SET name = :name, head = :head, budget = :budget, description = :description WHERE id = :id');
            $stmt->execute([':name' => $name, ':head' => $head, ':budget' => $budget, ':description' => $description, ':id' => $id]);
            if ($oldName !== $name) {
                $updateEmployees = $db->prepare('UPDATE employees SET department = :newName WHERE department = :oldName');
                $updateEmployees->execute([':newName' => $name, ':oldName' => $oldName]);
            }
            respond(['success' => true]);
        } catch (Exception $e) {
            respond(['error' => 'Update failed'], 500);
        }
        break;

    case 'delete_department':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            respond(['error' => 'Invalid department'], 400);
        }
        $dept = $db->prepare('SELECT name FROM departments WHERE id = :id');
        $dept->execute([':id' => $id]);
        $deptRow = $dept->fetch(PDO::FETCH_ASSOC);
        if (!$deptRow) {
            respond(['error' => 'Department not found'], 404);
        }
        $count = $db->prepare('SELECT COUNT(*) AS cnt FROM employees WHERE department = :name');
        $count->execute([':name' => $deptRow['name']]);
        $cnt = (int)($count->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);
        if ($cnt > 0) {
            respond(['error' => 'Cannot delete department with employees'], 400);
        }
        $del = $db->prepare('DELETE FROM departments WHERE id = :id');
        $del->execute([':id' => $id]);
        respond(['success' => true]);
        break;

    case 'add_employee':
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $department = trim($input['department'] ?? '');
        $position = trim($input['position'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $avatar = trim($input['avatar'] ?? '');
        if ($name === '' || $email === '' || $department === '' || $position === '') {
            respond(['error' => 'Missing fields'], 400);
        }
        $stmt = $db->prepare('INSERT INTO employees (name, email, department, position, phone, avatar) VALUES (:name, :email, :department, :position, :phone, :avatar)');
        $stmt->execute([':name' => $name, ':email' => $email, ':department' => $department, ':position' => $position, ':phone' => $phone, ':avatar' => $avatar]);
        respond(['success' => true]);
        break;

    case 'update_employee':
        $id = (int)($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $department = trim($input['department'] ?? '');
        $position = trim($input['position'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $avatar = trim($input['avatar'] ?? '');
        if ($id <= 0 || $name === '' || $email === '' || $department === '' || $position === '') {
            respond(['error' => 'Missing fields'], 400);
        }
        $stmt = $db->prepare('UPDATE employees SET name = :name, email = :email, department = :department, position = :position, phone = :phone, avatar = :avatar WHERE id = :id');
        $stmt->execute([':name' => $name, ':email' => $email, ':department' => $department, ':position' => $position, ':phone' => $phone, ':avatar' => $avatar, ':id' => $id]);
        respond(['success' => true]);
        break;

    case 'delete_employee':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            respond(['error' => 'Invalid employee'], 400);
        }
        $stmt = $db->prepare('DELETE FROM employees WHERE id = :id');
        $stmt->execute([':id' => $id]);
        respond(['success' => true]);
        break;

    default:
        respond(['error' => 'Unknown action'], 400);
}
