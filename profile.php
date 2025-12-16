<?php
//Don't clean commed lines. I wrote for that I can remember and you can understand my code. (Elif)
//this page's purpose is to let user update their profile.

//Start the session if not already started (doğrudan $SESSION$ dizine erişmeye çalışırsak hata alırız))
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//Database connection
require_once 'db.php'; 

$update_message = "";
$error_message = "";

// Check if user is logged in if not send the login page
if (!isset($_SESSION['user']['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user']['user_id'];

//get the user data
try {
    $stmt_user = $pdo->prepare("SELECT first_name, last_name, phone, password_hash FROM Users WHERE user_id = ?");
    $stmt_user->execute([$user_id]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("User not found.");
    }
} catch (PDOException $e) {
    die("Database error while fetching user data.");
}

//update (post) profile information (user data)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    
    //clean and update data
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    //check format (validation)
    $regex_name = '/^[a-zA-ZğĞşŞıİöÖçÇ\s]+$/';
    if (!preg_match($regex_name, $first_name) || !preg_match($regex_name, $last_name)) {
        $error_message .= "First Name and Last Name must only contain letters and spaces.<br>";
    }
    
    if (!empty($phone) && !preg_match('/^\d{10}$/', $phone)) {
        $error_message .= "Phone number must be in a valid 10-digit format.<br>";
    }

    if (empty($error_message)) {
        try {
            //update query(database sorgusunu yazabilmek için)
            $sql_update = "UPDATE Users SET 
                            first_name = ?, 
                            last_name )= ?, 
                            phone = ?
                           WHERE user_id = ?";
            
            $stmt_update = $pdo->prepare($sql_update);
            $success = $stmt_update->execute([$first_name, $last_name, $phone, $user_id]);
            
            if ($success) {
                $update_message = "Profile information updated successfully!";
                $user['first_name'] = $first_name;
                $user['last_name'] = $last_name;
                $user['phone'] = $phone;
            } else {
                 $error_message = "Error updating profile.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error while updating profile.";
        }
    }
}

// Change Password (it's a little complex process so I separated)

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $error_message .= "New passwords do not match.<br>";
    } 
    
     if (strlen($password) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'New password must contain at least one uppercase letter.';
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'New password must contain at least one lowercase letter.';
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'New password must contain at least one number.';
    }

    if (!preg_match('/[^a-zA-Z0-9\s]/', $password)) {
        $errors[] = 'New password must contain at least one special character (e.g., !, #, $).';
    }

    // Verify current password(because it is stored as a hash))
    if (!password_verify($current_password, $user['password_hash'])) {
        $error_message .= "The current password you entered is incorrect.<br>";
    }

    if (empty($error_message)) {
        try {
            //hash the new password nad update
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $sql_pass_update = "UPDATE Users SET password_hash = ? WHERE user_id = ?";
            
            $stmt_pass = $pdo->prepare($sql_pass_update);
            $success = $stmt_pass->execute([$hashed_password, $user_id]);
            
            if ($success) {
                $update_message = "Password changed successfully!";
                $user['password_hash'] = $hashed_password;
            } else {
                $error_message = "Error changing password.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error while changing password.";
        }
    }
}


?>