<?php
session_start();
include('../db_conn.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../admin/phpmailer/src/Exception.php';
require '../admin/phpmailer/src/PHPMailer.php';
require '../admin/phpmailer/src/SMTP.php';

unset($_SESSION['message']);

// Set the correct timezone to Philippine Time
date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Check if the email exists in any of the specified tables
    $tables = ['admin_account', 'student_account', 'teacher_account', 'staff_account'];
    $emailExists = false;
    $targetTable = ''; // To store which table the email was found in

    foreach ($tables as $table) {
        $query = "SELECT 1 FROM $table WHERE email = ?";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $emailExists = true;
                $targetTable = $table; // Store the table where the email was found
                break;
            }
            $stmt->close();
        }
    }

    if ($emailExists) {
        // Generate a 6-digit OTP
        $otp = rand(100000, 999999);

        // Hash the OTP before storing it in the database
        $hashedOtp = password_hash($otp, PASSWORD_BCRYPT);

        // Set OTP expiration time (e.g., 15 minutes from now)
        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // Store the OTP hash and expiration time in the database
        $updateQuery = "UPDATE $targetTable SET reset_token_hash = ?, reset_token_expires_at = ? WHERE email = ?";
        if ($stmt = $conn->prepare($updateQuery)) {
            $stmt->bind_param("sss", $hashedOtp, $expiresAt, $email);
            $stmt->execute();
            $stmt->close();
        }

        // Store the OTP in the session for verification in the next step
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_email'] = $email;

        // Send OTP to the user's email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'maryjosetteacademy2004@gmail.com'; 
            $mail->Password = 'wzfg mllu gjzx gmuq'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('maryjosetteacademy2004@gmail.com', 'Mary Josette Academy');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Your OTP Code';
            // Add the expiry time information to the email body
            $mail->Body = "Hello,<br><br>Your OTP code is: <strong>$otp</strong>.<br><br>This OTP is valid for 15 minutes. Please use this code to proceed.";
            $mail->AltBody = "Hello,\n\nYour OTP code is: $otp.\n\nThis OTP is valid for 15 minutes. Please use this code to proceed.";

            $mail->send();
            $_SESSION['message'] = "OTP sent to your email. Please check your inbox.";
            header('Location: one-time-pin.php'); // Redirect to OTP verification page
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = "Failed to send OTP: {$mail->ErrorInfo}";
        }
    } else {
        $_SESSION['error'] = "Email not found in our records. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mary Josette Academy - Find Your Account</title>
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <!-- Viewport Meta Tag -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Poppins Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <!-- FontAwesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Internal CSS -->
    <style>
        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        /* Particles.js container */
        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            background-color: #281313;
            background-size: cover;
            background-position: 50% 50%;
            z-index: -1;
            top: 0;
            left: 0;
        }

        .container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FAFAFA;
            padding: 20px;
        }

        .account-card {
            background: rgba(71, 47, 47, 0.6); 
            border-radius: 20px;
            padding: 30px;
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            backdrop-filter: blur(10px);
            text-align: center;
            margin: auto;
        }

        .account-card .logo {
            display: block;
            margin: 0 auto 10px;
            width: 70px;
        }

        .account-card .title {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 25px;
            color: #facc15;
        }

        .form-group {
            position: relative;
            margin-bottom: 25px;
        }

        .form-control {
            background-color: transparent !important;
            border: none;
            border-bottom: 2px solid #FAFAFA;
            border-radius: 0;
            color: #FAFAFA !important;
            padding: 10px 5px;
            padding-left: 15px;
            font-size: 16px;
            width: 100%;
            box-sizing: border-box;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #facc15;
            outline: none;
        }

        .message {
            margin-top: 20px;
        }

        .form-control::placeholder {
            color: #f0f0f0;
            opacity: 1; 
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
        }

        .btn-search, .btn-cancel {
            border-radius: 50px;
            padding: 10px 20px;
            font-weight: bold;
            width: 48%;
            font-size: 18px;
        }

        .btn-search {
            background-color: #facc15;
            color: #281313;
            border: none;
        }

        .btn-search:hover {
            background-color: #e0b814;
            color: #281313;
        }

        .btn-cancel {
            background-color: #d1d1d1;
            color: #281313;
            border: none;
        }

        .btn-cancel:hover {
            background-color: #b5b5b5;
            color: #281313;
        }

        .btn-container {
            display: flex;
            justify-content: space-between;
        }

        /* Responsive Adjustments */
        @media (max-width: 576px) {
            .account-card {
                padding: 25px;
                max-width: 90%;
            }
            .account-card .title {
                font-size: 22px;
            }
            .account-card .logo {
                width: 60px;
            }
            .form-control {
                font-size: 14px;
                padding: 8px 5px;
            }
            .btn-search, .btn-cancel {
                padding: 10px 15px;
                font-size: 16px;
            }
        }

        @media (max-width: 400px) {
            .account-card {
                padding: 20px;
                max-width: 100%;
            }
            .account-card .title {
                font-size: 20px;
            }
            .account-card .logo {
                width: 50px;
            }
            .form-control {
                font-size: 12px;
                padding: 8px 5px;
            }
            .btn-search, .btn-cancel {
                padding: 8px 10px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Particles.js container -->
    <div id="particles-js"></div>

    <div class="container">
        <div class="account-card">
            <img src="../Logo/mja-logo.png" alt="Mary Josette Academy Logo" class="logo">
            <div class="title">Find Your Account</div>
            <p>Please enter your email to search for your account.</p>

            <form action="" method="post">
                <div class="form-group">
                    <input type="email" name="email" class="form-control" placeholder="Email" required>
                </div>

                <!-- Display error/success messages -->
                <div class="message">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
                        </div>
                        <?php unset($_SESSION['error']); // Clear the error after displaying ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['message']); ?>
                        </div>
                        <?php unset($_SESSION['message']); // Clear the message after displaying ?>
                    <?php endif; ?>
                </div>

                <div class="btn-container">
                    <button type="button" class="btn-cancel" onclick="window.location.href='login-form.php';">Cancel</button>
                    <button type="submit" class="btn-search">Search</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- Include particles.js -->
    <script src="https://cdn.jsdelivr.net/npm/particles.js"></script>
    <!-- Initialize particles.js -->
    <script>
        particlesJS('particles-js', {
            "particles": {
                "number": {
                    "value": 40,
                    "density": {
                        "enable": true,
                        "value_area": 800
                    }
                },
                "color": {
                    "value": "#facc15"
                },
                "shape": {
                    "type": "circle",
                    "stroke": {
                        "width": 0,
                        "color": "#000000"
                    }
                },
                "opacity": {
                    "value": 0.5,
                    "random": true
                },
                "size": {
                    "value": 3,
                    "random": true
                },
                "line_linked": {
                    "enable": true,
                    "distance": 150,
                    "color": "#facc15",
                    "opacity": 0.4,
                    "width": 1
                },
                "move": {
                    "enable": true,
                    "speed": 2,
                    "direction": "none",
                    "random": false,
                    "straight": false
                }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": {
                    "onhover": {
                        "enable": true,
                        "mode": "repulse"
                    },
                    "onclick": {
                        "enable": true,
                        "mode": "push"
                    }
                },
                "modes": {
                    "repulse": {
                        "distance": 100,
                        "duration": 0.4
                    },
                    "push": {
                        "particles_nb": 4
                    }
                }
            },
            "retina_detect": true
        });
    </script>
</body>
</html>