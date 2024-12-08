<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/src/Exception.php';
require '../phpmailer/src/PHPMailer.php';
require '../phpmailer/src/SMTP.php';

include('../../db_conn.php');

if (isset($_GET['id'])) {
    $adminId = $_GET['id'];

    // Fetch the admin's email and credentials from the database
    $query = "SELECT email, password, firstName FROM admin_account WHERE admin_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $adminId);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin) {
        $email = $admin['email'];
        $password = $admin['password']; // Plain text password (for experimentation)
        $firstName = $admin['firstName'];

        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'maryjosetteacademy2004@gmail.com'; // Your Gmail email address
            $mail->Password = 'wzfg mllu gjzx gmuq'; // Gmail app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            // Recipients
            $mail->setFrom('maryjosetteacademy2004@gmail.com'); // Your Gmail email address
            $mail->addAddress($email); // Admin's email address

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Login Credentials';
            $mail->Body    = "Hello $firstName,<br><br>Here are your login credentials:<br><br>Email: $email<br>Password: $password<br><br>Please keep this information safe.";
            $mail->AltBody = "Hello $firstName,\n\nHere are your login credentials:\n\nEmail: $email\nPassword: $password\n\nPlease keep this information safe.";

            // Send the email
            $mail->send();

            echo "<script>
                    alert('Login credentials sent successfully');
                    document.location.href = 'admin-list.php';
                  </script>";
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "<script>alert('Admin not found');</script>";
    }
} else {
    echo "<script>alert('Invalid request');</script>";
}

