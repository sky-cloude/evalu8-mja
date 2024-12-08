<?php
// actions/delete-student-process.php

// Include the database connection file
include('../../db_conn.php');

// Set the response header to JSON
header('Content-Type: application/json');

// Enable detailed error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and decode JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    $students_eval_id = isset($data['students_eval_id']) ? intval($data['students_eval_id']) : 0;

    if ($students_eval_id > 0) {
        // Begin transaction
        mysqli_begin_transaction($conn);

        try {
            // Step 1: Retrieve associated evaluation_id and student_id
            $stmt = mysqli_prepare($conn, "SELECT evaluation_id, student_id FROM students_eval_restriction WHERE students_eval_id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $students_eval_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $evaluation_id, $student_id);
            if (!mysqli_stmt_fetch($stmt)) {
                throw new Exception("No student found with the provided evaluation ID.");
            }
            mysqli_stmt_close($stmt);

            // Step 2: Delete related records from evaluation_answers
            $delete_answers_query = "DELETE FROM evaluation_answers WHERE evaluation_id = ? AND student_id = ?";
            $stmt = mysqli_prepare($conn, $delete_answers_query);
            mysqli_stmt_bind_param($stmt, 'ii', $evaluation_id, $student_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Step 3: Delete related records from evaluation_sentiments
            $delete_sentiments_query = "DELETE FROM evaluation_sentiments WHERE evaluation_id = ? AND student_id = ?";
            $stmt = mysqli_prepare($conn, $delete_sentiments_query);
            mysqli_stmt_bind_param($stmt, 'ii', $evaluation_id, $student_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Step 4: Delete from students_eval_restriction
            $delete_student_query = "DELETE FROM students_eval_restriction WHERE students_eval_id = ?";
            $stmt = mysqli_prepare($conn, $delete_student_query);
            mysqli_stmt_bind_param($stmt, 'i', $students_eval_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Commit the transaction
            mysqli_commit($conn);

            // Return success response
            echo json_encode(['success' => true, 'message' => 'Student removed successfully.']);
        } catch (Exception $e) {
            // An error occurred, rollback the transaction
            mysqli_rollback($conn);

            // Return detailed error message
            echo json_encode(['success' => false, 'message' => 'Error removing student: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid student evaluation ID.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
