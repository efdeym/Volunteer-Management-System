<?php
// 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php'; 

$log_message = "";
$error_message = "";
$current_event_id = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT) ?? null;
$events = [];
$volunteers = [];

if (!isset($_SESSION['user']['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user']['user_id'];
$user_role = $_SESSION['user']['role_name'] ?? 'Volunteer';

if ($user_role !== 'Manager') {
    die("Access denied. Only Managers can access this page.");
}
$manager_id = $user_id;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['log_participation'])) {
    
    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
    $participations = $_POST['participations'] ?? [];

    if (!$event_id) {
        $error_message .= "Invalid Event ID.<br>";
    }

    if (empty($error_message)) {
        $log_count = 0;
        
        try {
            $pdo->beginTransaction(); 

            foreach ($participations as $volunteer_id => $data) {
                
                $volunteer_id = (int)$volunteer_id;
                $status = trim($data['status'] ?? '');
                $hours = filter_var($data['hours'] ?? 0, FILTER_VALIDATE_FLOAT);

                if (!in_array($status, ['Attended', 'Absent'])) {
                    continue;
                }
                
                if ($status === 'Attended' && ($hours === false || $hours <= 0)) {
                    $hours = 1;
                } elseif ($status === 'Absent') {
                    $hours = 0;
                }
                
                $sql_log = "INSERT INTO Participations 
                            (volunteer_id, event_id, status, hours_logged, logged_by) 
                            VALUES (:vid, :eid, :status, :hours, :lid)
                            ON DUPLICATE KEY UPDATE 
                            status = VALUES(status), 
                            hours_logged = VALUES(hours_logged),
                            logged_by = VALUES(logged_by),
                            logged_at = NOW()";

                $stmt = $pdo->prepare($sql_log);
                $stmt->execute([
                    ':vid' => $volunteer_id,
                    ':eid' => $event_id,
                    ':status' => $status,
                    ':hours' => $hours,
                    ':lid' => $manager_id
                ]);
                
                if ($stmt->rowCount() > 0) {
                    $log_count++;
                }
            }

            $pdo->commit();
            header("Location: log_participation.php?event_id=$event_id&success=$log_count records successfully updated.");
            exit;

        } catch (PDOException $e) {
            $pdo->rollback();
            $error_message = "A database error occurred during logging.";
        }
    }
}

if (isset($_GET['success'])) {
    $log_message = htmlspecialchars($_GET['success']);
} elseif (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

try {
    $sql_events = "SELECT project_id AS event_id, title FROM Projects WHERE status = 'Approved' ORDER BY project_id DESC";
    $stmt_events = $pdo->query($sql_events);
    $events = $stmt_events->fetchAll(PDO::FETCH_ASSOC);

    if ($current_event_id) {
        $sql_volunteers = "SELECT 
                               U.user_id, U.first_name, U.last_name,
                               P.status AS participation_status,
                               P.hours_logged
                           FROM Applications A
                           JOIN Users U ON A.volunteer_id = U.user_id
                           LEFT JOIN Participations P ON U.user_id = P.volunteer_id AND P.event_id = ?
                           WHERE A.project_id = ? AND A.status = 'Approved'
                           ORDER BY U.last_name";

        $stmt_volunteers = $pdo->prepare($sql_volunteers);
        $stmt_volunteers->execute([$current_event_id, $current_event_id]);
        $volunteers = $stmt_volunteers->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error_message .= "Database error while fetching lists.";
}
?>