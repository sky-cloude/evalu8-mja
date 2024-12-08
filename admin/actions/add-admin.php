<?php
session_start();
include('../../db_conn.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/src/Exception.php';
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = mysqli_real_escape_string($conn, $_POST['firstName']);
    $middleName = mysqli_real_escape_string($conn, $_POST['middleName']);
    $lastName = mysqli_real_escape_string($conn, $_POST['lastName']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $avatarData = null; // Default value for avatar

    // Check if the email is already in use across multiple tables
    $tables = ['admin_account', 'staff_account', 'student_account', 'teacher_account'];
    foreach ($tables as $table) {
        $query = "SELECT 1 FROM $table WHERE email = ?";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $_SESSION['message'] = "Email already in use. Please use a different email.";
                $_SESSION['message_type'] = "danger";
                $stmt->close();
                header("Location: ../admin-list.php");
                exit;
            }
            $stmt->close();
        }
    }

    // Generate a random password
    $plainPassword = bin2hex(random_bytes(8)); // Generate a 16 character random password
    $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

    // Handle avatar file upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatarTmpName = $_FILES['avatar']['tmp_name'];
        $avatarData = file_get_contents($avatarTmpName); // Get the binary data of the uploaded file
    }

    // Prepare SQL query to insert the new admin
    $stmt = $conn->prepare("INSERT INTO admin_account (firstName, middleName, lastName, email, password, avatar) 
                            VALUES (?, ?, ?, ?, ?, ?)");

    $null = NULL; // Placeholder for avatar data if it doesn't exist
    $stmt->bind_param("sssssb", $firstName, $middleName, $lastName, $email, $hashedPassword, $null);

    if ($avatarData !== null) {
        $stmt->send_long_data(5, $avatarData); // 5 is the index for the avatar BLOB
    }

    if ($stmt->execute()) {
        // Send the plain password to the user's email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'maryjosetteacademy2004@gmail.com'; // Your Gmail email address
            $mail->Password = 'wzfg mllu gjzx gmuq'; // Gmail app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('maryjosetteacademy2004@gmail.com', 'Evalu8 - Mary Josette Academy');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Your Login Credentials';
            $mail->Body = "Hello $firstName,<br><br>Here are your login credentials:<br><br>Email: $email<br>Password: $plainPassword<br><br>Please change your password after logging in.";
            $mail->AltBody = "Hello $firstName,\n\nHere are your login credentials:\n\nEmail: $email\nPassword: $plainPassword\n\nPlease change your password after logging in.";

            $mail->send();
            $_SESSION['message'] = "Admin added successfully and credentials sent to their email.";
            $_SESSION['message_type'] = "success";
        } catch (Exception $e) {
            $_SESSION['message'] = "Failed to send login credentials: {$mail->ErrorInfo}";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Failed to add admin: " . $stmt->error;
        $_SESSION['message_type'] = "danger";
    }

    $stmt->close();
    header("Location: ../admin-list.php");
}
?>
