<?php
// actions/delete-student.php
session_start();
include('../../db_conn.php');

if (isset($_POST['student_id'])) {
    $studentId = intval($_POST['student_id']);

    // Begin transaction
    mysqli_begin_transaction($conn);

    try {
        // Delete from evaluation_answers
        $stmt = mysqli_prepare($conn, "DELETE FROM evaluation_answers WHERE student_id = ?");
        if (!$stmt) {
            throw new Exception("Failed to prepare evaluation_answers deletion.");
        }
        mysqli_stmt_bind_param($stmt, 'i', $studentId);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to delete from evaluation_answers.");
        }
        mysqli_stmt_close($stmt);

        // Delete from evaluation_sentiments
        $stmt = mysqli_prepare($conn, "DELETE FROM evaluation_sentiments WHERE student_id = ?");
        if (!$stmt) {
            throw new Exception("Failed to prepare evaluation_sentiments deletion.");
        }
        mysqli_stmt_bind_param($stmt, 'i', $studentId);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to delete from evaluation_sentiments.");
        }
        mysqli_stmt_close($stmt);

        // Delete from students_eval_restriction
        $stmt = mysqli_prepare($conn, "DELETE FROM students_eval_restriction WHERE student_id = ?");
        if (!$stmt) {
            throw new Exception("Failed to prepare students_eval_restriction deletion.");
        }
        mysqli_stmt_bind_param($stmt, 'i', $studentId);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to delete from students_eval_restriction.");
        }
        mysqli_stmt_close($stmt);

        // Delete from student_account
        $stmt = mysqli_prepare($conn, "DELETE FROM student_account WHERE student_id = ?");
        if (!$stmt) {
            throw new Exception("Failed to prepare student_account deletion.");
        }
        mysqli_stmt_bind_param($stmt, 'i', $studentId);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to delete from student_account.");
        }
        mysqli_stmt_close($stmt);

        // Commit transaction
        mysqli_commit($conn);

        // Set success message
        $_SESSION['message'] = 'Student account and all related data deleted successfully.';
        $_SESSION['message_type'] = 'success';
    } catch (Exception $e) {
        // Rollback transaction
        mysqli_roll_back($conn);

        // Set error message
        $_SESSION['message'] = 'Error deleting student account: ' . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }

    // Close the connection
    mysqli_close($conn);

    // Redirect back to student list
    header("Location: ../student-list.php");
    exit();
} else {
    // Set error message
    $_SESSION['message'] = 'Invalid request.';
    $_SESSION['message_type'] = 'danger';

    // Redirect back to student list
    header("Location: ../student-list.php");
    exit();
}
?>
