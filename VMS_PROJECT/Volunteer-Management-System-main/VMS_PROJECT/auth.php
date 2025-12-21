<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

$action = $_GET['action'] ?? '';
$users = [
    'admin' => 'admin123',
    'manager' => 'manager123'
];

switch ($action) {
    case 'status':
        if (isset($_SESSION['user'])) {
            respond(['logged_in' => true, 'username' => $_SESSION['user']]);
        }
        respond(['logged_in' => false]);
        break;

    case 'login':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            respond(['error' => 'Invalid payload'], 400);
        }
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        if ($username === '' || $password === '') {
            respond(['error' => 'Missing credentials'], 400);
        }
        if (!isset($users[$username]) || $users[$username] !== $password) {
            respond(['error' => 'Invalid credentials'], 401);
        }
        $_SESSION['user'] = $username;
        respond(['success' => true, 'username' => $username]);
        break;

    case 'logout':
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        respond(['success' => true]);
        break;

    default:
        respond(['error' => 'Unknown action'], 400);
}
