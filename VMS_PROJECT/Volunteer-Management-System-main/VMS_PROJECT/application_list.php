<?php
//Don't clean commed lines. I wrote for that I can remember and you can understand my code. (Elif)
//this page's purpose is to let Organization Manager view and manage(accept or decline) volunteer applications

//start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//for Database connection
require_once 'db.php'; 

$page_message = "";
$error_message = "";
$applications = [];

// Check if user is logged in if not send the login page
if (!isset($_SESSION['user']['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user']['user_id'];
$user_role = $_SESSION['user']['role_name'] ?? 'Volunteer';

//check role authorization (just Organization Manager can access)
if ($user_role !== 'Organization Manager') {
    die("Access denied. Only Organization Manager are authorized to view this page.");
}

//check for success or error messages from redirects
if (isset($_GET['success'])) {
    $page_message = htmlspecialchars($_GET['success']);
} elseif (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

// update form status
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    
    //validate id and POST data
    $application_id = filter_input(INPUT_POST, 'application_id', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    $valid_statuses = ['Approved', 'Rejected'];
    if (!$application_id || !in_array($status, $valid_statuses)) {
        $error_message = "Invalid application ID or status.";
    }

    if (empty($error_message)) {
        try {
            //sql update statement
            $sql_update = "UPDATE Applications SET status = ?, decision_date = NOW() WHERE application_id = ?";
            
            $stmt_update = $pdo->prepare($sql_update);
            $success = $stmt_update->execute([$status, $application_id]);
            
            if ($success && $stmt_update->rowCount() > 0) {
                //redirect with success message(formun tekaradan gönderilmemesi için sayfayı yeniler)
                header("Location: application_list.php?success=Application status updated to $status.");
                exit;
            } else {
                 $error_message = "Error updating status or application not found.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error while updating status.";
        }
    }
}

try {
    //sql select statement to fetch application listh with user and project's data
    $sql_fetch = "SELECT 
                    A.application_id, A.application_date, A.status, A.notes,
                    U.user_id, U.first_name, U.last_name, 
                    P.title AS project_title, P.project_id
                  FROM Applications A
                  JOIN Users U ON A.volunteer_id = U.user_id
                  JOIN Projects P ON A.project_id = P.project_id
                  WHERE A.status IN ('Pending', 'Approved', 'Rejected') 
                  ORDER BY A.application_date DESC";
                  
    //$pdo->query for simple SELECT without user input is acceptable  (org. man. show status)            
    $stmt_fetch = $pdo->query($sql_fetch);
    $applications = $stmt_fetch->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message .= "Database error while fetching application list.";
}
?>
