<?php

$dbFile = __DIR__ . '/data.sqlite';
if (file_exists($dbFile)) {
    echo "Database already exists at $dbFile\n";
    exit;
}

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        email TEXT
    );");

    $pdo->exec("CREATE TABLE departments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        owner_user_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        head TEXT,
        budget INTEGER DEFAULT 0,
        description TEXT,
        FOREIGN KEY(owner_user_id) REFERENCES users(id)
    );");

    $pdo->exec("CREATE TABLE employees (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        owner_user_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        email TEXT,
        department TEXT,
        position TEXT,
        phone TEXT,
        avatar TEXT,
        FOREIGN KEY(owner_user_id) REFERENCES users(id)
    );");

    // Seed users
    $u1 = 'alice'; $p1 = password_hash('alicepass', PASSWORD_DEFAULT); $e1 = 'alice@example.com';
    $u2 = 'bob'; $p2 = password_hash('bobpass', PASSWORD_DEFAULT); $e2 = 'bob@example.com';

    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, email) VALUES (?, ?, ?)');
    $stmt->execute([$u1, $p1, $e1]);
    $stmt->execute([$u2, $p2, $e2]);

    $aliceId = $pdo->lastInsertId();
    // Because lastInsertId returns last inserted (bob), fetch ids properly
    $stmt = $pdo->query("SELECT id, username FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $ids = [];
    foreach ($users as $u) $ids[$u['username']] = $u['id'];

    // Seed departments and employees for alice
    $stmt = $pdo->prepare('INSERT INTO departments (owner_user_id, name, head, budget, description) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$ids['alice'], 'Engineering', 'Alice Smith', 500000, 'Engineering team']);
    $stmt->execute([$ids['alice'], 'HR', 'Alice HR', 150000, 'HR and recruiting']);

    $stmt = $pdo->prepare('INSERT INTO employees (owner_user_id, name, email, department, position, phone, avatar) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$ids['alice'], 'Alice Smith', 'alice@company.com', 'Engineering', 'CTO', '555-1001', 'AS']);
    $stmt->execute([$ids['alice'], 'Dev One', 'dev1@company.com', 'Engineering', 'Developer', '555-1002', 'D1']);

    // Seed departments and employees for bob
    $stmt = $pdo->prepare('INSERT INTO departments (owner_user_id, name, head, budget, description) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$ids['bob'], 'Sales', 'Bob Lead', 300000, 'Sales team']);

    $stmt = $pdo->prepare('INSERT INTO employees (owner_user_id, name, email, department, position, phone, avatar) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$ids['bob'], 'Bob Lead', 'bob@company.com', 'Sales', 'Head of Sales', '555-2001', 'BL']);

    echo "Database created and seeded at $dbFile\n";

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    if (file_exists($dbFile)) @unlink($dbFile);
    exit(1);
}

?>
