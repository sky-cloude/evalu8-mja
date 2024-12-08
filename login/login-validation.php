<?php
session_start();
include "../db_conn.php";

if (isset($_POST['email']) && isset($_POST['password'])) {

    function validate($data){
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    $email = validate($_POST['email']);
    $pass = validate($_POST['password']);
    
    if (empty($email) && empty($pass)) {
        header("Location: login-form.php?error=Email and Password are required");
        exit();
    } else if (empty($pass)) {
        header("Location: login-form.php?error=Password is required");
        exit();
    } else if (empty($email)) {
        header("Location: login-form.php?error=Email is required");
        exit();
    } else {
        // List of tables and corresponding ID columns
        $user_types = [
            'admin' => ['table' => 'admin_account', 'id_column' => 'admin_id'],
            'student' => ['table' => 'student_account', 'id_column' => 'student_id'],
            'teacher' => ['table' => 'teacher_account', 'id_column' => 'teacher_id'],
            'staff' => ['table' => 'staff_account', 'id_column' => 'staff_id']
        ];

        // Variable to track if login is successful
        $login_successful = false;

        foreach ($user_types as $type => $data) {
            $table = $data['table'];
            $id_column = $data['id_column'];

            // Fetch the user data for the given email
            $sql = "SELECT * FROM $table WHERE email = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) === 1) {
                $row = mysqli_fetch_assoc($result);
                $hashedPassword = $row['password'];
                error_log("Fetched hashed password for $email: $hashedPassword"); 

                // Verify the hashed password
                if (password_verify($pass, $hashedPassword)) {
                    $_SESSION['email'] = $row['email'];
                    $_SESSION['firstName'] = $row['firstName'];
                    $_SESSION['user_id'] = $row[$id_column]; // Store the appropriate ID
                    $_SESSION['user_type'] = $type; // Store the user type
                    
                    // Redirect based on user type
                    switch ($type) {
                        case 'admin':
                            header("Location: ../admin/admin-dashboard.php");
                            break;
                        case 'student':
                            header("Location: ../student/student-dashboard.php");
                            break;
                        case 'teacher':
                            header("Location: ../teacher/teacher-dashboard.php");
                            break;
                        case 'staff':
                            header("Location: ../staff/staff-dashboard.php");
                            break;
                    }

                    // Set login success flag and exit the loop
                    $login_successful = true;
                    break;
                } else {
                    error_log("Password verification failed for $email."); 
                }
            }
            mysqli_stmt_close($stmt);
        }

        // If login was not successful
        if (!$login_successful) {
            header("Location: login-form.php?error=Incorrect Email or Password");
            exit();
        }
    }

} else {
    header("Location: login-form.php?error");
    exit();
}
