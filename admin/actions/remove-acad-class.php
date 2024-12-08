<?php
// actions/remove-acad-class.php

// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the database connection file
include('../../db_conn.php');

// Set the response header to JSON
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input data
    $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
    $acad_year_id = isset($_POST['acad_year_id']) ? intval($_POST['acad_year_id']) : 0;

    // Validate inputs
    if ($class_id === 0 || $acad_year_id === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid class ID or academic year ID.']);
        exit;
    }

    // Begin transaction
    mysqli_begin_transaction($conn);

    try {
        // Step 1: Retrieve all evaluation_ids associated with the class and academic year
        $eval_ids = [];
        $stmt = mysqli_prepare($conn, "SELECT evaluation_id FROM evaluation_list WHERE class_id = ? AND acad_year_id = ?");
        if (!$stmt) {
            throw new Exception("Preparation failed: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, 'ii', $class_id, $acad_year_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $evaluation_id);
        while (mysqli_stmt_fetch($stmt)) {
            $eval_ids[] = $evaluation_id;
        }
        mysqli_stmt_close($stmt);

        if (!empty($eval_ids)) {
            // Convert the evaluation_ids array to a comma-separated string for SQL IN clause
            $eval_ids_str = implode(',', array_map('intval', $eval_ids));

            // Step 2: Delete from evaluation_answers
            $delete_answers_query = "DELETE FROM evaluation_answers WHERE evaluation_id IN ($eval_ids_str)";
            if (!mysqli_query($conn, $delete_answers_query)) {
                throw new Exception("Failed to delete from evaluation_answers: " . mysqli_error($conn));
            }

            // Step 3: Delete from evaluation_sentiments
            $delete_sentiments_query = "DELETE FROM evaluation_sentiments WHERE evaluation_id IN ($eval_ids_str)";
            if (!mysqli_query($conn, $delete_sentiments_query)) {
                throw new Exception("Failed to delete from evaluation_sentiments: " . mysqli_error($conn));
            }

            // Step 4: Delete from students_eval_restriction
            $delete_restrictions_query = "DELETE FROM students_eval_restriction WHERE evaluation_id IN ($eval_ids_str)";
            if (!mysqli_query($conn, $delete_restrictions_query)) {
                throw new Exception("Failed to delete from students_eval_restriction: " . mysqli_error($conn));
            }

            // Step 5: Delete from subject_to_eval
            $delete_subjects_query = "DELETE FROM subject_to_eval WHERE evaluation_id IN ($eval_ids_str)";
            if (!mysqli_query($conn, $delete_subjects_query)) {
                throw new Exception("Failed to delete from subject_to_eval: " . mysqli_error($conn));
            }

            // Step 6: Delete from evaluation_list
            $delete_evaluation_list_query = "DELETE FROM evaluation_list WHERE evaluation_id IN ($eval_ids_str)";
            if (!mysqli_query($conn, $delete_evaluation_list_query)) {
                throw new Exception("Failed to delete from evaluation_list: " . mysqli_error($conn));
            }
        }

        // Step 7: Optionally delete the class from class_list if that's intended
        // **Uncomment the following lines if you want to delete the class entirely**
        /*
        $stmt = mysqli_prepare($conn, "DELETE FROM class_list WHERE class_id = ?");
        if (!$stmt) {
            throw new Exception("Preparation failed: " . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($stmt, 'i', $class_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to delete from class_list: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        */

        // Commit the transaction
        mysqli_commit($conn);

        // Return success response
        echo json_encode(['success' => true, 'message' => 'Class and all related evaluations removed successfully from the evaluation list.']);
    } catch (Exception $e) {
        // An error occurred, rollback the transaction
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => 'Failed to remove class from evaluation list: ' . $e->getMessage()]);
    }
} else {
    // Invalid request method
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
