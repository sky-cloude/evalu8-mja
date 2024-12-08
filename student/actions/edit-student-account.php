<?php
session_start();
include('../../db_conn.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: ../login/login-form.php");
    exit();
}

$id = intval($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and trim input data
    $firstName = mysqli_real_escape_string($conn, trim($_POST['firstName']));
    $middleName = mysqli_real_escape_string($conn, trim($_POST['middleName']));
    $lastName = mysqli_real_escape_string($conn, trim($_POST['lastName']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = trim($_POST['password']);
    $avatar = isset($_FILES['avatar']) ? $_FILES['avatar'] : null;

    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($email)) {
        $_SESSION['flash_message'] = "First Name, Last Name, and Email are required fields.";
        $_SESSION['flash_message_type'] = "error";
        header("Location: ../student-dashboard.php");
        exit();
    }

    // Check if the new email already exists in other tables
    $emailCheckQuery = "
        SELECT `email` FROM `admin_account` WHERE `email` = '$email'
        UNION
        SELECT `email` FROM `student_account` WHERE `email` = '$email' AND `student_id` != '$id'
        UNION
        SELECT `email` FROM `teacher_account` WHERE `email` = '$email'
        UNION
        SELECT `email` FROM `staff_account` WHERE `email` = '$email'
    ";
    $emailCheckResult = mysqli_query($conn, $emailCheckQuery);

    if (mysqli_num_rows($emailCheckResult) > 0) {
        // Set error message in session
        $_SESSION['flash_message'] = "The email address is already in use. Please use a different email address.";
        $_SESSION['flash_message_type'] = "error";
        header("Location: ../student-dashboard.php");
        exit();
    }

    // Initialize update fields
    $updateFields = [
        "`firstName` = '$firstName'",
        "`middleName` = '$middleName'",
        "`lastName` = '$lastName'",
        "`email` = '$email'"
    ];

    // Update password if provided
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updateFields[] = "`password` = '$hashedPassword'";
    }

    // Handle avatar upload
    if ($avatar && $avatar['error'] == UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($avatar['type'], $allowedTypes)) {
            $avatarData = addslashes(file_get_contents($avatar['tmp_name']));
            $updateFields[] = "`avatar` = '$avatarData'";
        } else {
            $_SESSION['flash_message'] = "Invalid avatar file type. Only JPG, PNG, and GIF are allowed.";
            $_SESSION['flash_message_type'] = "error";
            header("Location: ../student-dashboard.php");
            exit();
        }
    }

    // Build the update query
    $updateQuery = "UPDATE `student_account` SET " . implode(", ", $updateFields) . " WHERE `student_id` = '$id'";

    // Execute the update query
    if (isset($conn) && $conn) {
        $result = mysqli_query($conn, $updateQuery);

        if ($result) {
            // Set success message in session
            $_SESSION['flash_message'] = "Account updated successfully.";
            $_SESSION['flash_message_type'] = "success";
        } else {
            // Set error message in session
            $_SESSION['flash_message'] = "Failed to update account: " . mysqli_error($conn);
            $_SESSION['flash_message_type'] = "error";
        }
    } else {
        // Set error message in session
        $_SESSION['flash_message'] = "Database connection failed.";
        $_SESSION['flash_message_type'] = "error";
    }

    // Redirect back to the dashboard
    header("Location: ../student-dashboard.php");
    exit();
} else {
    header("Location: ../student-dashboard.php");
    exit();
}
?>
