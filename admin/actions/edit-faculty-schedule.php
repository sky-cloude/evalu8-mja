<?php
session_start();
include('../../db_conn.php');

// Function to sanitize and validate input
function sanitize_input($data) {
    return htmlspecialchars(trim($data));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if all required POST parameters are set
    if (
        isset($_POST['teacher_sched_id'], $_POST['teacher_id'], $_POST['day_of_week'], $_POST['subject_code'], $_POST['class_title'], $_POST['time_start'], $_POST['time_end'])
        && !empty($_POST['day_of_week'])
        && !empty($_POST['subject_code'])
        && !empty($_POST['class_title'])
        && !empty($_POST['time_start'])
        && !empty($_POST['time_end'])
    ) {
        $teacher_sched_id = intval($_POST['teacher_sched_id']);
        $teacher_id = intval($_POST['teacher_id']);
        $day_of_week = sanitize_input($_POST['day_of_week']);
        $subject_code = sanitize_input($_POST['subject_code']);
        $class_title = sanitize_input($_POST['class_title']);
        $time_start = sanitize_input($_POST['time_start']);
        $time_end = sanitize_input($_POST['time_end']);

        // Validate that start time is before end time
        if (strtotime($time_start) >= strtotime($time_end)) {
            $_SESSION['message'] = 'Start time must be before end time.';
            $_SESSION['message_type'] = 'danger';
            header("Location: ../part-timer-faculty.php?teacher_id=$teacher_id");
            exit;
        }

        // Check for overlapping schedules, excluding the current schedule
        $overlap_query = "SELECT * FROM teacher_schedule WHERE teacher_id = ? AND day_of_week = ? AND teacher_sched_id != ? AND ((time_start < ? AND time_end > ?) OR (time_start = ? AND time_end = ?))";

        $overlap_stmt = mysqli_prepare($conn, $overlap_query);
        if ($overlap_stmt) {
            mysqli_stmt_bind_param($overlap_stmt, "isissss", $teacher_id, $day_of_week, $teacher_sched_id, $time_end, $time_start, $time_start, $time_end);
            mysqli_stmt_execute($overlap_stmt);
            $result = mysqli_stmt_get_result($overlap_stmt);

            if (mysqli_num_rows($result) > 0) {
                $_SESSION['message'] = 'The time slot overlaps with an existing schedule.';
                $_SESSION['message_type'] = 'danger';
                mysqli_stmt_close($overlap_stmt);
                header("Location: ../part-timer-faculty.php?teacher_id=$teacher_id");
                exit;
            }
            mysqli_stmt_close($overlap_stmt);
        } else {
            error_log("Failed to prepare overlap check statement: " . mysqli_error($conn));
            $_SESSION['message'] = 'An unexpected error occurred while checking the schedule.';
            $_SESSION['message_type'] = 'danger';
            header("Location: ../part-timer-faculty.php?teacher_id=$teacher_id");
            exit;
        }

        // Prepare the UPDATE statement to prevent SQL injection
        $stmt = mysqli_prepare($conn, "UPDATE teacher_schedule SET day_of_week = ?, subject_code = ?, class_title = ?, time_start = ?, time_end = ? WHERE teacher_sched_id = ? AND teacher_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssssii", $day_of_week, $subject_code, $class_title, $time_start, $time_end, $teacher_sched_id, $teacher_id);
            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $_SESSION['message'] = 'Schedule updated successfully.';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'No changes made to the schedule or schedule not found.';
                $_SESSION['message_type'] = 'warning';
            }

            mysqli_stmt_close($stmt);
        } else {
            // Log the error internally and set a generic error message for the user
            error_log("Failed to prepare UPDATE statement: " . mysqli_error($conn));
            $_SESSION['message'] = 'An unexpected error occurred while updating the schedule.';
            $_SESSION['message_type'] = 'danger';
        }
    } else {
        $_SESSION['message'] = 'Please fill in all required fields.';
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
