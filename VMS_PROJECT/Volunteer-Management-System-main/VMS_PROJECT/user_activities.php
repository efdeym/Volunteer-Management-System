<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['user']['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = (int)$_SESSION['user']['user_id'];

try {
    $stmt = $pdo->prepare(
        "SELECT p.project_id, p.title,
                COALESCE(p.end_date, p.start_date) AS event_date,
                A.status AS application_status,
                A.application_date,
                P.status AS participation_status,
                P.hours_logged
         FROM Applications A
         JOIN Projects p ON A.project_id = p.project_id
         LEFT JOIN Participations P ON P.event_id = p.project_id AND P.volunteer_id = A.volunteer_id
         WHERE A.volunteer_id = ?
         ORDER BY COALESCE(p.end_date, p.start_date) DESC"
    );
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$r) {
        $r['event_date'] = $r['event_date'] ? date('Y-m-d', strtotime($r['event_date'])) : null;
        $r['application_date'] = $r['application_date'] ? date('Y-m-d', strtotime($r['application_date'])) : null;
        $r['hours_logged'] = $r['hours_logged'] === null ? null : (float)$r['hours_logged'];
    }
    unset($r);

    echo json_encode(['success' => true, 'activities' => $rows]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
