<?php
// actions/delete-subject-process.php

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the database connection file
include('../../db_conn.php');

// Set the response header to JSON
header('Content-Type: application/json');

// Enable detailed error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input data
    $subject_eval_id = isset($_POST['subject_eval_id']) ? intval($_POST['subject_eval_id']) : 0;

    if ($subject_eval_id > 0) {
        // Begin transaction
        mysqli_begin_transaction($conn);

        try {
            // Step 1: Retrieve associated evaluation_id and subject_id
            $stmt = mysqli_prepare($conn, "SELECT evaluation_id, subject_id FROM subject_to_eval WHERE subject_eval_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $subject_eval_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $evaluation_id, $subject_id);
            if (!mysqli_stmt_fetch($stmt)) {
                throw new Exception("No subject found with the provided ID.");
            }
            mysqli_stmt_close($stmt);

            // Step 2: Delete related records from evaluation_answers
            $delete_answers_query = "DELETE FROM evaluation_answers WHERE evaluation_id = ? AND subject_id = ?";
            $stmt = mysqli_prepare($conn, $delete_answers_query);
            mysqli_stmt_bind_param($stmt, 'ii', $evaluation_id, $subject_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Step 3: Delete related records from evaluation_sentiments
            $delete_sentiments_query = "DELETE FROM evaluation_sentiments WHERE evaluation_id = ? AND subject_id = ?";
            $stmt = mysqli_prepare($conn, $delete_sentiments_query);
            mysqli_stmt_bind_param($stmt, 'ii', $evaluation_id, $subject_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Step 4: Delete related records from other dependent tables if any
            // Example: If you have a table like subject_feedback
            // $delete_feedback_query = "DELETE FROM subject_feedback WHERE evaluation_id = ? AND subject_id = ?";
            // $stmt = mysqli_prepare($conn, $delete_feedback_query);
            // mysqli_stmt_bind_param($stmt, 'ii', $evaluation_id, $subject_id);
            // mysqli_stmt_execute($stmt);
            // mysqli_stmt_close($stmt);

            // Step 5: Delete from subject_to_eval
            $delete_subject_query = "DELETE FROM subject_to_eval WHERE subject_eval_id = ?";
            $stmt = mysqli_prepare($conn, $delete_subject_query);
            mysqli_stmt_bind_param($stmt, 'i', $subject_eval_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Commit the transaction
            mysqli_commit($conn);

            // Return success response
            echo json_encode(['success' => true, 'message' => 'Subject removed successfully.']);
        } catch (Exception $e) {
            // An error occurred, rollback the transaction
            mysqli_rollback($conn);

            // Return detailed error message
            echo json_encode(['success' => false, 'message' => 'Error removing subject: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid subject ID.']);
    }
} else {
    // Invalid request method
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
