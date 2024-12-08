<?php
session_start();
include('../db_conn.php');

// Set the correct timezone to Philippine Time
date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp = $_POST['otp1'] . $_POST['otp2'] . $_POST['otp3'] . $_POST['otp4'] . $_POST['otp5'] . $_POST['otp6'];
    $email = $_SESSION['otp_email']; // The email to identify the user
    $tables = ['admin_account', 'student_account', 'teacher_account', 'staff_account'];
    $otpValid = false;

    foreach ($tables as $table) {
        // Fetch the OTP hash and expiration time for the user
        $query = "SELECT reset_token_hash, reset_token_expires_at FROM $table WHERE email = ?";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($resetTokenHash, $resetTokenExpiresAt);
            $stmt->fetch();
            $stmt->close();

            // Check if OTP has expired
            if ($resetTokenExpiresAt >= date('Y-m-d H:i:s')) {
                // Verify OTP
                if (password_verify($otp, $resetTokenHash)) {
                    $otpValid = true;
                    break;
                }
            }
        }
    }

    if ($otpValid) {
        $_SESSION['message'] = "OTP verified successfully. You can now reset your password.";
        // Redirect to the password reset page (e.g., reset-password.php)
        header('Location: reset-password.php');
        exit;
    } else {
        $_SESSION['error'] = "Invalid or expired OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mary Josette Academy - OTP Verification</title>
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

        .otp-input {
            width: 50px;
            height: 50px;
            font-size: 24px;
            text-align: center;
            background-color: transparent !important;
            border: none;
            border-bottom: 2px solid #FAFAFA;
            color: #FAFAFA !important;
            margin: 0 5px;
        }

        .otp-input:focus {
            box-shadow: none;
            border-color: #facc15;
            outline: none;
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
        }

        .btn-verify {
            border-radius: 50px;
            padding: 10px 20px;
            font-weight: bold;
            width: 100%;
            font-size: 18px;
            background-color: #facc15;
            color: #281313;
            border: none;
        }

        .btn-verify:hover {
            background-color: #e0b814;
            color: #281313;
        }

        /* Add fade-out effect */
        .fade-out {
            opacity: 0;
            transition: opacity 1s ease-out;
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
            .otp-input {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }
            .btn-verify {
                padding: 10px 15px;
                font-size: 16px;
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
            <div class="title">OTP Verification</div>
            <p>Please enter the 6-digit OTP sent to your email.</p>

            <form action="" method="post" id="otpForm">
                <div class="form-group d-flex justify-content-center">
                    <input type="text" name="otp1" class="otp-input" maxlength="1" required>
                    <input type="text" name="otp2" class="otp-input" maxlength="1" required>
                    <input type="text" name="otp3" class="otp-input" maxlength="1" required>
                    <input type="text" name="otp4" class="otp-input" maxlength="1" required>
                    <input type="text" name="otp5" class="otp-input" maxlength="1" required>
                    <input type="text" name="otp6" class="otp-input" maxlength="1" required>
                </div>

                <div class="message">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div id="errorMessage" class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['message'])): ?>
                        <div id="successMessage" class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['message']); ?>
                        </div>
                        <?php unset($_SESSION['message']); ?>
                    <?php endif; ?>
                </div>


                <button type="submit" class="btn-verify">Verify OTP</button>
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

        // JavaScript to auto-focus the next input on key press and allow only numbers
        const inputs = document.querySelectorAll('.otp-input');

        inputs.forEach((input, index) => {
            input.addEventListener('input', () => {
                // Allow only numbers
                input.value = input.value.replace(/[^0-9]/g, '');

                // Move focus to the next input if a digit is entered
                if (input.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', (event) => {
                // Move to the previous input on backspace
                if (event.key === 'Backspace' && input.value === '' && index > 0) {
                    inputs[index - 1].focus();
                }
            });
        });

        // JavaScript to make the message disappear smoothly after 5 seconds
        window.onload = function() {
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');

            if (errorMessage) {
                setTimeout(() => {
                    errorMessage.classList.add('fade-out'); // Add the fade-out class
                    setTimeout(() => {
                        errorMessage.style.display = 'none'; // Hide after transition
                    }, 1000); // 1000 milliseconds = 1 second (match the CSS transition time)
                }, 5000); // Wait 5 seconds before starting the fade-out
            }

            if (successMessage) {
                setTimeout(() => {
                    successMessage.classList.add('fade-out'); // Add the fade-out class
                    setTimeout(() => {
                        successMessage.style.display = 'none'; // Hide after transition
                    }, 1000); // 1000 milliseconds = 1 second (match the CSS transition time)
                }, 4000); // Wait 5 seconds before starting the fade-out
            }
        };
    </script>

</body>
</html>
