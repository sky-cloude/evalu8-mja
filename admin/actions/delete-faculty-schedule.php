<?php
session_start();
include('../../db_conn.php');

// Function to sanitize and validate input
function sanitize_input($data) {
    return htmlspecialchars(trim($data));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if required POST parameters are set
    if (isset($_POST['teacher_sched_id'], $_POST['teacher_id'])) {
        $teacher_sched_id = intval($_POST['teacher_sched_id']);
        $teacher_id = intval($_POST['teacher_id']);

        // Prepare the DELETE statement to prevent SQL injection
        $stmt = mysqli_prepare($conn, "DELETE FROM teacher_schedule WHERE teacher_sched_id = ? AND teacher_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $teacher_sched_id, $teacher_id);
            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $_SESSION['message'] = 'Schedule deleted successfully.';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'No schedule found to delete or already deleted.';
                $_SESSION['message_type'] = 'warning';
            }

            mysqli_stmt_close($stmt);
        } else {
            // Log the error internally and set a generic error message for the user
            error_log("Failed to prepare DELETE statement: " . mysqli_error($conn));
            $_SESSION['message'] = 'An unexpected error occurred while deleting the schedule.';
            $_SESSION['message_type'] = 'danger';
        }
    } else {
        $_SESSION['message'] = 'Invalid request parameters.';
        $_SESSION['message_type'] = 'danger';
    }

    // Redirect back to the schedule page with the teacher_id
    header("Location: ../part-timer-faculty.php?teacher_id=$teacher_id");
    exit;
} else {
    // If accessed without POST data, redirect to the main Faculty DTR page
    header("Location: ../faculty-dtr.php");
    exit;
}
?>
