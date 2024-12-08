<?php
session_start();
include('../../db_conn.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/src/Exception.php';
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = [];
    $firstName = mysqli_real_escape_string($conn, $_POST['teacherFirstName']);
    $middleName = mysqli_real_escape_string($conn, $_POST['teacherMiddleName']);
    $lastName = mysqli_real_escape_string($conn, $_POST['teacherLastName']);
    $email = mysqli_real_escape_string($conn, $_POST['teacherEmail']);
    $department = mysqli_real_escape_string($conn, $_POST['teacherDepartment']);
    $status = mysqli_real_escape_string($conn, $_POST['teacherStatus']);
    $empCode = mysqli_real_escape_string($conn, $_POST['employeeID']);
    $avatarData = null;

    // Check if empCode is non-positive or empty
    if (empty($empCode) || $empCode <= 0) {
        $_SESSION['message'] = 'Employee ID cannot be 0, negative, or empty.';
        $_SESSION['message_type'] = 'danger';
        header('Location: ../teacher-list.php');
        exit();
    }

    // Check if email exists in any of the tables
    $tables = ['admin_account', 'staff_account', 'student_account', 'teacher_account'];
    foreach ($tables as $table) {
        $query = "SELECT email FROM $table WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $_SESSION['message'] = 'Email already in use. Please use a different email.';
                $_SESSION['message_type'] = 'danger';
                header('Location: ../teacher-list.php');
                exit();
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Check for duplicate empCode in both teacher_account and staff_account tables
    $empCodeTables = ['teacher_account', 'staff_account'];
    foreach ($empCodeTables as $table) {
        $query = "SELECT emp_code FROM $table WHERE emp_code = ?";
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, 'i', $empCode);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $_SESSION['message'] = 'Employee ID (emp_code) is already in use. Please use a unique Employee ID.';
                $_SESSION['message_type'] = 'danger';
                header('Location: ../teacher-list.php');
                exit();
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Handle the avatar upload if present
    if (isset($_FILES['teacherAvatar']) && $_FILES['teacherAvatar']['error'] === UPLOAD_ERR_OK) {
        $avatarData = file_get_contents($_FILES['teacherAvatar']['tmp_name']);
    }

    // Generate a random password
    $plainPassword = bin2hex(random_bytes(8)); // 8 bytes = 16 characters
    $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

    // Prepare the SQL statement to insert the new teacher data
    $query = "INSERT INTO teacher_account (firstName, middleName, lastName, email, password, department, status, emp_code, avatar, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    if ($stmt = mysqli_prepare($conn, $query)) {
        // Bind the parameters
        mysqli_stmt_bind_param($stmt, 'sssssssib', $firstName, $middleName, $lastName, $email, $hashedPassword, $department, $status, $empCode, $null);

        // Send the avatar data as a stream if available
        if (!empty($avatarData)) {
            mysqli_stmt_send_long_data($stmt, 8, $avatarData); // Parameter index 8 (zero-based)
        }

        // Execute the query
        if (mysqli_stmt_execute($stmt)) {
            // Send the plain password to the teacher's email using PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'maryjosetteacademy2004@gmail.com'; // Your Gmail email address
                $mail->Password = 'wzfg mllu gjzx gmuq';               // Gmail app password (app-specific password)
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                // Recipients
                $mail->setFrom('maryjosetteacademy2004@gmail.com', 'Evalu8 - Mary Josette Academy');
                $mail->addAddress($email); // Add a recipient

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Your Teacher Login Credentials';
                $mail->Body = "Hello $firstName, <br>Your login credentials are:<br>Email: $email<br>Password: $plainPassword<br>Please change your password after logging in.";
                $mail->AltBody = "Hello $firstName, \nYour login credentials are:\nEmail: $email\nPassword: $plainPassword\nPlease change your password after logging in.";

                $mail->send();
                $_SESSION['message'] = 'New teacher added successfully and credentials sent to their email!';
                $_SESSION['message_type'] = 'success';
            } catch (Exception $e) {
                $_SESSION['message'] = "Mailer Error: " . $mail->ErrorInfo;
                $_SESSION['message_type'] = 'danger';
            }
        } else {
            $_SESSION['message'] = 'Error: ' . mysqli_stmt_error($stmt);
            $_SESSION['message_type'] = 'danger';
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['message'] = 'Error preparing statement: ' . mysqli_error($conn);
        $_SESSION['message_type'] = 'danger';
    }

    header('Location: ../teacher-list.php');
    exit();
} else {
    header('Location: ../teacher-list.php');
    exit();
}
?>
