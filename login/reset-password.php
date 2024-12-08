<?php
session_start();
include('../db_conn.php');

// Set the correct timezone to Philippine Time
date_default_timezone_set('Asia/Manila');

// Clear previous messages
unset($_SESSION['error']);
unset($_SESSION['message']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    $email = $_SESSION['otp_email']; // Email from the OTP session
    $tables = ['admin_account', 'student_account', 'teacher_account', 'staff_account'];
    $correctTable = ''; // Variable to store the table name where OTP was found

    // Check if passwords match
    if ($newPassword !== $confirmPassword) {
        $_SESSION['error'] = "Passwords do not match. Please try again.";
    } else {
        $otpValid = false;

        // Verify OTP expiration for each user type
        foreach ($tables as $table) {
            $query = "SELECT reset_token_expires_at FROM $table WHERE email = ?";
            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->bind_result($resetTokenExpiresAt);
                $stmt->fetch();
                $stmt->close();

                // Check if the OTP has expired
                if ($resetTokenExpiresAt >= date('Y-m-d H:i:s')) {
                    $otpValid = true;
                    $correctTable = $table; // Store the table name
                    error_log("OTP valid for $email in table $table."); // Debug: Log the table where OTP was found
                    break;
                }
            }
        }

        if ($otpValid && $correctTable !== '') {
            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            error_log("New hashed password for $email: $hashedPassword"); // Debug: Log the hashed password
            $passwordUpdated = false;

            // Update the password in the correct table
            $query = "UPDATE $correctTable SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE email = ?";
            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param("ss", $hashedPassword, $email);
                if ($stmt->execute()) {
                    $passwordUpdated = true;
                    error_log("Password successfully updated in table $correctTable for $email."); // Debug: Log successful update
                } else {
                    error_log("Failed to update password in table $correctTable for $email: " . $stmt->error); // Debug: Log failed update
                }
                $stmt->close();
            }

            if ($passwordUpdated) {
                // Set a success message
                $_SESSION['message'] = "Your password has been successfully reset. You will be redirected to the login page shortly.";
                $_SESSION['success'] = true; // Set a flag for JavaScript to handle redirection
            } else {
                $_SESSION['error'] = "Failed to reset password. Please try again.";
            }
        } else {
            $_SESSION['error'] = "The OTP has expired. Please request a new one.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mary Josette Academy - Reset Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

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

        .form-control::placeholder {
            color: #f0f0f0;
            opacity: 1; 
        }

        .message {
            margin-top: 20px;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
            transition: opacity 1s ease-out;
        }

        .btn-reset {
            border-radius: 50px;
            padding: 10px 20px;
            font-weight: bold;
            width: 100%;
            font-size: 18px;
            background-color: #facc15;
            color: #281313;
            border: none;
        }

        .btn-reset:hover {
            background-color: #e0b814;
            color: #281313;
        }

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
            .btn-reset {
                padding: 10px 15px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>

    <div class="container">
        <div class="account-card">
            <img src="../Logo/mja-logo.png" alt="Mary Josette Academy Logo" class="logo">
            <div class="title">Reset Password</div>
            <p>Please enter your new password.</p>

            <form action="" method="post" id="resetForm">
                <div class="form-group">
                    <input type="password" name="new_password" class="form-control" placeholder="New Password" required>
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                </div>

                <div class="message">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-reset">Reset Password</button>
            </form>
        </div>
    </div>

    <!-- Custom Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="background: rgba(71, 47, 47, 0.9); border-radius: 20px; color: #FAFAFA;">
                <div class="modal-header" style="border-bottom: none;">
                    <h5 class="modal-title" id="statusModalLabel">Status</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #FAFAFA;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modalMessage">
                    <!-- Message content will be inserted here -->
                </div>
                <div class="modal-footer" style="border-top: none;">
                    <button type="button" class="btn-reset" data-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for particles.js, jQuery, and Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
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

        window.onload = function() {
            const errorMessage = <?php echo isset($_SESSION['error']) ? json_encode($_SESSION['error']) : 'null'; ?>;
            const successMessage = <?php echo isset($_SESSION['message']) ? json_encode($_SESSION['message']) : 'null'; ?>;
            const successFlag = <?php echo isset($_SESSION['success']) ? 'true' : 'false'; ?>;

            if (errorMessage || successMessage) {
                const modalMessageElement = document.getElementById('modalMessage');
                if (errorMessage) {
                    modalMessageElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + errorMessage;
                } else if (successMessage) {
                    modalMessageElement.innerHTML = '<i class="fas fa-check-circle"></i> ' + successMessage;
                }

                // Show the modal
                $('#statusModal').modal('show');

                // Automatically hide the modal after 3 seconds and redirect if success
                setTimeout(() => {
                    $('#statusModal').modal('hide');
                    if (successFlag) {
                        window.location.href = 'login-form.php';
                    }
                }, 3000);
            }
        };
    </script>
</body>
</html>
