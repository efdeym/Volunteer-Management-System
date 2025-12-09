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

$action = $_GET['action'] ?? ($_POST['action'] ?? null);
if (!$action) {
    http_response_code(400);
    echo json_encode(['error' => 'missing action']);
    exit;
}

if ($action === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';

    $stmt = $pdo->prepare('SELECT id, username, password_hash, email FROM users WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        echo json_encode(['success' => true, 'username' => $user['username'], 'email' => $user['email']]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
    }
    exit;
}

if ($action === 'logout') {
    session_unset();
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'status') {
    if (!empty($_SESSION['user_id'])) {
        echo json_encode(['logged_in' => true, 'user_id' => $_SESSION['user_id'], 'username' => $_SESSION['username'] ?? null]);
    } else {
        echo json_encode(['logged_in' => false]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'unknown action']);

?>
