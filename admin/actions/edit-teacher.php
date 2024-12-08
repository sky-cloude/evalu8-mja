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
    $teacherId = $_POST['teacher_id'];
    $firstName = $_POST['teacherFirstName'];
    $middleName = $_POST['teacherMiddleName'];
    $lastName = $_POST['teacherLastName'];
    $department = $_POST['teacherDepartment'];
    $status = $_POST['teacherStatus'];
    $newEmail = $_POST['teacherEmail'];
    $empCode = $_POST['employeeID'];

    // Check if emp_code is non-positive or empty
    if (empty($empCode) || $empCode <= 0) {
        $response['success'] = false;
        $response['message'] = 'Employee ID cannot be 0, negative, or empty.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    // Check if the email is already used in any account tables
    $tables = ['admin_account', 'staff_account', 'teacher_account', 'student_account'];
    $emailExists = false;

    foreach ($tables as $table) {
        $query = "SELECT email FROM $table WHERE email = ?";
        if ($table === 'teacher_account') {
            $query .= " AND teacher_id != ?";
        }
        if ($stmt = mysqli_prepare($conn, $query)) {
            if ($table === 'teacher_account') {
                mysqli_stmt_bind_param($stmt, 'si', $newEmail, $teacherId);
            } else {
                mysqli_stmt_bind_param($stmt, 's', $newEmail);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $emailExists = true;
                mysqli_stmt_close($stmt);
                break;
            }
            mysqli_stmt_close($stmt);
        }
    }

    if ($emailExists) {
        $response['success'] = false;
        $response['message'] = 'Email is already in use. Please use a different email.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    // Check for duplicate emp_code in both teacher_account and staff_account tables
    $empCodeExists = false;
    $empCodeTables = ['teacher_account', 'staff_account'];
    foreach ($empCodeTables as $table) {
        $query = "SELECT emp_code FROM $table WHERE emp_code = ?";
        if ($table === 'teacher_account') {
            $query .= " AND teacher_id != ?";
        }
        if ($stmt = mysqli_prepare($conn, $query)) {
            if ($table === 'teacher_account') {
                mysqli_stmt_bind_param($stmt, 'ii', $empCode, $teacherId);
            } else {
                mysqli_stmt_bind_param($stmt, 'i', $empCode);
            }
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $empCodeExists = true;
                mysqli_stmt_close($stmt);
                break;
            }
            mysqli_stmt_close($stmt);
        }
    }

    if ($empCodeExists) {
        $response['success'] = false;
        $response['message'] = 'Employee ID is already in use. Please use a unique Employee ID.';
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }

    // Check if email has changed
    $query = "SELECT email FROM teacher_account WHERE teacher_id = ?";
    if ($stmt = mysqli_prepare($conn, $query)) {
        mysqli_stmt_bind_param($stmt, 'i', $teacherId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $currentEmail);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }

    // Handle file upload (optional avatar)
    if (isset($_FILES['teacherAvatar']) && $_FILES['teacherAvatar']['error'] === UPLOAD_ERR_OK) {
        $avatarData = file_get_contents($_FILES['teacherAvatar']['tmp_name']);
        $query = "UPDATE teacher_account SET firstName = ?, middleName = ?, lastName = ?, email = ?, department = ?, status = ?, emp_code = ?, avatar = ? WHERE teacher_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'ssssssibi', $firstName, $middleName, $lastName, $newEmail, $department, $status, $empCode, $avatarData, $teacherId);
    } else {
        // No avatar uploaded, skip avatar in query
        $query = "UPDATE teacher_account SET firstName = ?, middleName = ?, lastName = ?, email = ?, department = ?, status = ?, emp_code = ? WHERE teacher_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'ssssssii', $firstName, $middleName, $lastName, $newEmail, $department, $status, $empCode, $teacherId);
    }

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Teacher details updated successfully!";

        // If the email has changed, generate a new password and send it to the updated email
        if ($newEmail !== $currentEmail) {
            $plainPassword = bin2hex(random_bytes(8)); // Generate a new password
            $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

            // Update the new password in the database
            $passwordQuery = "UPDATE teacher_account SET password = ? WHERE teacher_id = ?";
            if ($passwordStmt = mysqli_prepare($conn, $passwordQuery)) {
                mysqli_stmt_bind_param($passwordStmt, 'si', $hashedPassword, $teacherId);
                mysqli_stmt_execute($passwordStmt);
                mysqli_stmt_close($passwordStmt);
            }

            // Send the new password to the updated email using PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'maryjosetteacademy2004@gmail.com';
                $mail->Password = 'wzfg mllu gjzx gmuq'; // Use the same Gmail account and app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                // Recipients
                $mail->setFrom('maryjosetteacademy2004@gmail.com', 'Evalu8 - Mary Josette Academy');
                $mail->addAddress($newEmail);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Your Updated Teacher Login Credentials';
                $mail->Body = "Hello $firstName, <br>Your login credentials have been updated:<br>Email: $newEmail<br>Password: $plainPassword<br>Please change your password after logging in.";
                $mail->AltBody = "Hello $firstName, \nYour login credentials have been updated:\nEmail: $newEmail\nPassword: $plainPassword\nPlease change your password after logging in.";

                $mail->send();
                $response['message'] .= ' A new password has been sent to the updated email.';
            } catch (Exception $e) {
                $response['message'] = "Mailer Error: " . $mail->ErrorInfo;
            }
        }
    } else {
        error_log("Database error: " . $stmt->error);
        $response['success'] = false;
        $response['message'] = "An error occurred while updating teacher details. Please try again later.";
    }

    $stmt->close();
    $conn->close();

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
} else {
    $response['success'] = false;
    $response['message'] = "Invalid request.";
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>
