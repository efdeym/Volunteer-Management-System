<?php
session_start();
header('Content-Type: application/json');

$dbFile = __DIR__ . '/data.sqlite';
try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$uid = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? ($_POST['action'] ?? null);
if (!$action) {
    http_response_code(400);
    echo json_encode(['error' => 'missing action']);
    exit;
}

try {
    if ($action === 'get_dashboard') {
        // departments
        $stmt = $pdo->prepare('SELECT id, name, head, budget, description FROM departments WHERE owner_user_id = :uid');
        $stmt->execute([':uid' => $uid]);
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare('SELECT id, name, email, department, position, phone, avatar FROM employees WHERE owner_user_id = :uid');
        $stmt->execute([':uid' => $uid]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'departments' => $departments, 'employees' => $employees]);
        exit;
    }

    if ($action === 'add_employee') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('INSERT INTO employees (owner_user_id, name, email, department, position, phone, avatar) VALUES (:uid, :name, :email, :dept, :pos, :phone, :avatar)');
        $stmt->execute([
            ':uid' => $uid,
            ':name' => $data['name'] ?? '',
            ':email' => $data['email'] ?? '',
            ':dept' => $data['department'] ?? '',
            ':pos' => $data['position'] ?? '',
            ':phone' => $data['phone'] ?? '',
            ':avatar' => $data['avatar'] ?? ''
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        exit;
    }

    if ($action === 'add_department') {
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare('INSERT INTO departments (owner_user_id, name, head, budget, description) VALUES (:uid, :name, :head, :budget, :desc)');
        $stmt->execute([
            ':uid' => $uid,
            ':name' => $data['name'] ?? '',
            ':head' => $data['head'] ?? '',
            ':budget' => $data['budget'] ?? 0,
            ':desc' => $data['description'] ?? ''
        ]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        exit;
    }

    if ($action === 'delete_employee') {
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM employees WHERE id = :id AND owner_user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $uid]);
        echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
        exit;
    }

    if ($action === 'delete_department') {
        $id = (int)($_GET['id'] ?? 0);
        // ensure no employees in this department for this owner
        $stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM employees WHERE owner_user_id = :uid AND department = (SELECT name FROM departments WHERE id = :id AND owner_user_id = :uid)');
        $stmt->execute([':uid' => $uid, ':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['cnt'] > 0) {
            echo json_encode(['success' => false, 'error' => 'Department not empty']);
            exit;
        }
        $stmt = $pdo->prepare('DELETE FROM departments WHERE id = :id AND owner_user_id = :uid');
        $stmt->execute([':id' => $id, ':uid' => $uid]);
        echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'unknown action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

?>
