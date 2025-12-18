<?php
//Don't clean commed lines. I wrote for that I can remember and you can understand my code. (Elif)
//this page's purpose is to handle authentication (login, logout, session management).
require_once __DIR__ . '/db.php';

if (!isset($_GET['action']) && !isset($_POST['action'])) {
    if (!isset($_SESSION['user']['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

//prepares user's information for  logged-in users.
if (isset($_SESSION['user']['user_id'])) {
    $uid = (int)$_SESSION['user']['user_id'];
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.first_name, u.last_name, u.email, r.role_name 
        FROM Users u 
        JOIN Roles r ON u.role_id = r.role_id 
        WHERE u.user_id = ?
    ");
    $stmt->execute([$uid]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

    // If user not found in database, log them out
    if (!$currentUser) {
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    //create a full name and role for easier access
    $currentUser['name'] = $currentUser['first_name'] . ' ' . $currentUser['last_name'];
    $currentUser['role'] = $currentUser['role_name']; 
}

//API Actions: login, logout, status
$action = $_GET['action'] ?? ($_POST['action'] ?? null);

if ($action) {
    header('Content-Type: application/json');

    // Login 
    if ($action === 'login') {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        $stmt = $pdo->prepare('SELECT user_id, password_hash, first_name, last_name, role_id FROM Users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = [
                'user_id' => (int)$user['user_id'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'role_id' => $user['role_id']
            ];
            echo json_encode(['success' => true, 'name' => $_SESSION['user']['name']]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
        }
        exit;
    }

    // Logout
    if ($action === 'logout') {
        session_unset();
        session_destroy();
        echo json_encode(['success' => true]);
        exit;
    }

    // Status (oturum kontolü )
    if ($action === 'status') {
        if (!empty($_SESSION['user']['user_id'])) {
            echo json_encode(['logged_in' => true, 'user' => $_SESSION['user']]);
        } else {
            echo json_encode(['logged_in' => false]);
        }
        exit;
    }
}
