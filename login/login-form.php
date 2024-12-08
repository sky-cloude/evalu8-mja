<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mary Josette Academy Login</title>
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <!-- Viewport Meta Tag -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link
        rel="stylesheet"
        href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
    />
    <!-- Poppins Font -->
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap"
        rel="stylesheet"
    />
    <!-- FontAwesome CSS -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
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

        .login-card {
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

        .login-card .logo {
            display: block;
            margin: 0 auto 10px;
            width: 70px;
        }

        .login-card .title {
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
            padding-left: 45px;
            padding-right: 45px;
            font-size: 16px;
            width: 100%;
            box-sizing: border-box;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #facc15;
            outline: none;
        }

        .form-group label {
            position: absolute;
            top: 5px;
            left: 45px;
            color: #FAFAFA;
            opacity: 0.7;
            transition: all 0.2s ease-in-out;
            pointer-events: none;
            font-size: 16px;
        }

        .form-group input:focus + label,
        .form-group input.has-content + label {
            top: -15px;
            font-size: 12px;
            color: #facc15;
            opacity: 1;
        }

        .btn-login {
            background-color: #facc15;
            color: #281313;
            border: none;
            border-radius: 50px;
            padding: 12px 20px;
            font-weight: bold;
            width: 100%;
            transition: background-color 0.3s;
            font-size: 18px;
        }

        .btn-login:hover {
            background-color: #e0b814;
            color: #281313;
        }

        .forgot-password {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #FAFAFA;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        .forgot-password:hover {
            color: #facc15;
            text-decoration: none;
        }

        /* Icon styling */
        .icon {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            color: #FAFAFA;
            font-size: 18px;
            width: 20px;
            text-align: center;
        }

        .left-icon {
            left: 10px;
            pointer-events: none;
            cursor: default;
        }

        .right-icon {
            right: 10px;
            cursor: pointer;
        }

        /* Styles for the error message */
        .alert {
            margin-top: 20px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 16px;
        }

        .alert i {
            margin-right: 10px;
        }

        /* Autofill Styles */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active  {
            -webkit-box-shadow: 0 0 0 30px rgba(0,0,0,0) inset !important;
            -webkit-text-fill-color: #FAFAFA !important;
            background-color: transparent !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* For Mozilla Firefox */
        input:-moz-autofill {
            background-color: transparent !important;
            color: #FAFAFA !important;
        }

        /* Responsive Adjustments */
        @media (max-width: 576px) {
            .login-card {
                padding: 25px;
                max-width: 90%;
            }
            .login-card .title {
                font-size: 22px;
            }
            .login-card .logo {
                width: 60px;
            }
            .form-control {
                font-size: 14px;
                padding: 8px 5px;
                padding-left: 40px;
                padding-right: 40px;
            }
            .btn-login {
                padding: 10px 15px;
                font-size: 16px;
            }
            .forgot-password {
                font-size: 12px;
            }
            .icon {
                font-size: 16px;
                width: 18px;
            }
        }

        @media (max-width: 400px) {
            .login-card {
                padding: 20px;
                max-width: 100%;
            }
            .login-card .title {
                font-size: 20px;
            }
            .login-card .logo {
                width: 50px;
            }
            .form-control {
                font-size: 12px;
                padding: 8px 5px;
                padding-left: 35px;
                padding-right: 35px;
            }
            .btn-login {
                padding: 8px 10px;
                font-size: 14px;
            }
            .forgot-password {
                font-size: 10px;
            }
            .icon {
                font-size: 14px;
                width: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Particles.js container -->
    <div id="particles-js"></div>

    <div class="container">
        <div class="login-card">
            <img src="../Logo/mja-logo.png" alt="Mary Josette Academy Logo" class="logo">
            <div class="title">Mary Josette Academy</div>

            <!-- Display error message -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <form action="login-validation.php" method="post">
                <div class="form-group">
                    <i class="fas fa-envelope icon left-icon"></i>
                    <input type="email" name="email" class="form-control" required>
                    <label>Email</label>
                </div>
                <div class="form-group">
                    <i class="fas fa-lock icon left-icon"></i>
                    <input id="password" type="password" name="password" class="form-control" required>
                    <label>Password</label>
                    <i class="fas fa-eye-slash icon toggle-password right-icon"></i>
                </div>
                <button type="submit" class="btn-login">Log In</button>
                <a href="find-account.php" class="forgot-password">Forgot Password?</a>
            </form>
        </div>
    </div>
    <!-- Bootstrap JS and dependencies -->
    <script
        src="https://code.jquery.com/jquery-3.5.1.slim.min.js"
    ></script>
    <script
        src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"
    ></script>
    <script
        src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"
    ></script>
    <!-- Include particles.js -->
    <script src="https://cdn.jsdelivr.net/npm/particles.js"></script>
    <!-- Initialize particles.js -->
    <script>
        particlesJS('particles-js',
            {
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
            }
        );
    </script>
    <!-- Show/Hide Password Script -->
    <script>
        document.querySelector('.toggle-password').addEventListener('click', function () {
            const password = document.getElementById('password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            
            // Toggle the password field type
            password.setAttribute('type', type);
            
            // Toggle the icon classes based on the password field type
            if (type === 'password') {
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });


        // Floating Label Script
        document.querySelectorAll('.form-control').forEach(function(input) {
            // Initialize has-content class on page load
            if (input.value) {
                input.classList.add('has-content');
            }
            // Listen for input changes
            input.addEventListener('input', function() {
                if (this.value) {
                    input.classList.add('has-content');
                } else {
                    input.classList.remove('has-content');
                }
            });
        });
    </script>
</body>
</html>
