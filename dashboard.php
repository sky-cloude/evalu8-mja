<?php
session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['email'])) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="login-style.css">
    <title>HOME</title>
</head>
<body>
    <h1>Hello, <?php echo  ucfirst($_SESSION['firstName']); ?>!</h1>
    <h1>The <?php echo strtolower($_SESSION['user_type']); ?> of Mary Josette Academy!</h1>
    <?php 
    $current_year = date('Y');
    $id_formatted = $current_year . str_pad($_SESSION['user_id'], 3, '0', STR_PAD_LEFT); ;
    ?>
    <h1><?php echo ucfirst($_SESSION['user_type']); ?> ID Number: <?php echo $id_formatted; ?> </h1>
    <a href="logout.php">Logout</a>
</body>
</html>

<?php
} else {
    header("Location: login-form.php");
    exit();
}
?>
