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
    $studentId = $_POST['student_id'];
    $firstName = $_POST['studentFirstName'];
    $middleName = $_POST['studentMiddleName'];
    $lastName = $_POST['studentLastName'];
    $newEmail = $_POST['studentEmail'];

    // Check if the email is already used in admin_account, staff_account, teacher_account, or student_account
    $tables = ['admin_account', 'staff_account', 'teacher_account', 'student_account'];
    $emailExists = false;
    
    foreach ($tables as $table) {
        $query = "SELECT email FROM $table WHERE email = ? AND NOT EXISTS (SELECT 1 FROM student_account WHERE student_id = ? AND email = ?)";
        if ($stmt = mysqli_prepare($conn, $query)) {
            mysqli_stmt_bind_param($stmt, 'sis', $newEmail, $studentId, $newEmail);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $emailExists = true;
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

    // Proceed with the update if the email is not in use
    // Check if the email is changed
    $query = "SELECT email FROM student_account WHERE student_id = ?";
    if ($stmt = mysqli_prepare($conn, $query)) {
        mysqli_stmt_bind_param($stmt, 'i', $studentId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $currentEmail);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }

    // Handle file upload (optional avatar)
    if (isset($_FILES['studentAvatar']) && $_FILES['studentAvatar']['error'] === UPLOAD_ERR_OK) {
        $avatarData = file_get_contents($_FILES['studentAvatar']['tmp_name']);
        $query = "UPDATE student_account SET firstName = ?, middleName = ?, lastName = ?, email = ?, avatar = ? WHERE student_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'sssssi', $firstName, $middleName, $lastName, $newEmail, $avatarData, $studentId);
    } else {
        // No avatar uploaded, skip avatar in query
        $query = "UPDATE student_account SET firstName = ?, middleName = ?, lastName = ?, email = ? WHERE student_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'ssssi', $firstName, $middleName, $lastName, $newEmail, $studentId);
    }

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Student details updated successfully!";

        // If the email has changed, generate a new password and send it to the new email
        if ($newEmail !== $currentEmail) {
            $plainPassword = bin2hex(random_bytes(8)); // Generate a new password
            $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

            // Update the new password in the database
            $passwordQuery = "UPDATE student_account SET password = ? WHERE student_id = ?";
            if ($passwordStmt = mysqli_prepare($conn, $passwordQuery)) {
                mysqli_stmt_bind_param($passwordStmt, 'si', $hashedPassword, $studentId);
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
                $mail->Password = 'wzfg mllu gjzx gmuq';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;

                //Recipients
                $mail->setFrom('maryjosetteacademy2004@gmail.com', 'Evalu8 - Mary Josette Academy');
                $mail->addAddress($newEmail);

                //Content
                $mail->isHTML(true);
                $mail->Subject = 'Your Updated Student Login Credentials';
                $mail->Body    = "Hello $firstName, <br>Your login credentials have been updated:<br>Email: $newEmail<br>Password: $plainPassword<br>Please change your password after logging in.";
                $mail->AltBody = "Hello $firstName, \nYour login credentials have been updated:\nEmail: $newEmail\nPassword: $plainPassword\nPlease change your password after logging in.";

                $mail->send();
                $response['message'] .= ' A new password has been sent to the updated email.';
            } catch (Exception $e) {
                $response['message'] = "Mailer Error: " . $mail->ErrorInfo;
            }
        }
    } else {
        $response['success'] = false;
        $response['message'] = "Error updating student details: " . $conn->error;
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
