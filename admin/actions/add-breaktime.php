<?php
session_start();
include('../../db_conn.php');

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $break_name = trim($_POST['break_name']);
    $start_break = $_POST['start_break'];
    $end_break = $_POST['end_break'];

    // Validate inputs
    if (empty($break_name) || empty($start_break) || empty($end_break)) {
        $_SESSION['message'] = 'All fields are required.';
        $_SESSION['message_type'] = 'danger';
        header('Location: ../breaktime.php');
        exit();
    }

    // Validate that start_break and end_break are within 7:00 AM to 4:00 PM
    $startBreakTime = strtotime($start_break);
    $endBreakTime = strtotime($end_break);
    $workStartTime = strtotime('07:00:00');
    $workEndTime = strtotime('16:00:00');

    if ($startBreakTime < $workStartTime || $endBreakTime > $workEndTime) {
        $_SESSION['message'] = 'Break times must be between 7:00 AM and 4:00 PM.';
        $_SESSION['message_type'] = 'danger';
        header('Location: ../breaktime.php');
        exit();
    }

    // Validate that end_break is after start_break
    if ($start_break >= $end_break) {
        $_SESSION['message'] = 'End Break time must be after Start Break time.';
        $_SESSION['message_type'] = 'danger';
        header('Location: ../breaktime.php');
        exit();
    }

    // Check for duplicate break time
    $check_sql = "SELECT * FROM break_time_schedule WHERE break_name = ? AND start_break = ? AND end_break = ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        $_SESSION['message'] = 'Database error: ' . $conn->error;
        $_SESSION['message_type'] = 'danger';
        header('Location: ../breaktime.php');
        exit();
    }
    $check_stmt->bind_param("sss", $break_name, $start_break, $end_break);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $_SESSION['message'] = 'This break time already exists.';
        $_SESSION['message_type'] = 'danger';
        header('Location: ../breaktime.php');
        exit();
    }

    // Insert new break time
    $insert_sql = "INSERT INTO break_time_schedule (break_name, start_break, end_break) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    if (!$insert_stmt) {
        $_SESSION['message'] = 'Database error: ' . $conn->error;
        $_SESSION['message_type'] = 'danger';
        header('Location: ../breaktime.php');
        exit();
    }
    $insert_stmt->bind_param("sss", $break_name, $start_break, $end_break);

    if ($insert_stmt->execute()) {
        $_SESSION['message'] = 'Break Time added successfully!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error adding Break Time: ' . $conn->error;
        $_SESSION['message_type'] = 'danger';
    }

    // Close statements and connection
    $check_stmt->close();
    $insert_stmt->close();
    $conn->close();

    // Redirect back to breaktime.php
    header('Location: ../breaktime.php');
    exit();
} else {
    // Invalid request method
    $_SESSION['message'] = 'Invalid request.';
    $_SESSION['message_type'] = 'danger';
    header('Location: ../breaktime.php');
    exit();
}
?>
