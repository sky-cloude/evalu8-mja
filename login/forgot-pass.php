<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <header>
        <div class="school-name">
            <img src="Logo\mja-logo.png" alt="MJA Logo">
            <h1>Mary Josette Academy</h1>
        </div>    
    </header>
    <main>
        <div class="forgot-pass-container">
            <div class="forgot-pass-form-container">
                <form action="password-reset.php" method="POST">
                    <h1 class="forgot-pass-header">Find your account</h1>
                        <div class="forgot-pass-input">
                            <p style="padding: 5px 0px 25px 0px;">Please enter your email or mobile number to search for your account.</p>
                            <input type="email" name="email" id="email" placeholder="Email" required>
                        </div>
                        <div class="forgot-pass-button">
                            <button type="button" class="left-button" id="cancelSearch" onclick="window.location.href='login-form.php';">Cancel</button>
                            <button type="submit" class="right-button" id="searchButton">Submit</button>
                        </div>
                </form>
            </div>
        </div> 
    </main>
</body>
</html>