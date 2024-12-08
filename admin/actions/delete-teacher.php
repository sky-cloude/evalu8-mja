<?php
header('Content-Type: application/json');
session_start();
include('../../db_conn.php');

$response = array('status' => 'Error', 'message' => 'Unknown error.');

if (isset($_POST['teacher_id'])) {
    $teacherId = intval($_POST['teacher_id']);

    // Begin transaction
    mysqli_begin_transaction($conn);

    try {
        // Fetch emp_code for the given teacher_id
        $empCode = null;
        $query = "SELECT emp_code FROM teacher_account WHERE teacher_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception('Failed to prepare emp_code retrieval query.');
        }
        mysqli_stmt_bind_param($stmt, 'i', $teacherId);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to execute emp_code retrieval query.');
        }
        mysqli_stmt_bind_result($stmt, $empCode);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // Delete from subject_to_eval
        $query = "DELETE FROM subject_to_eval WHERE teacher_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception('Failed to prepare subject_to_eval deletion query.');
        }
        mysqli_stmt_bind_param($stmt, 'i', $teacherId);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to execute subject_to_eval deletion.');
        }
        mysqli_stmt_close($stmt);

        // Delete from evaluation_sentiments
        $query = "DELETE FROM evaluation_sentiments WHERE teacher_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception('Failed to prepare evaluation_sentiments deletion query.');
        }
        mysqli_stmt_bind_param($stmt, 'i', $teacherId);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to execute evaluation_sentiments deletion.');
        }
        mysqli_stmt_close($stmt);

        // Delete from evaluation_answers
        $query = "DELETE FROM evaluation_answers WHERE teacher_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception('Failed to prepare evaluation_answers deletion query.');
        }
        mysqli_stmt_bind_param($stmt, 'i', $teacherId);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to execute evaluation_answers deletion.');
        }
        mysqli_stmt_close($stmt);

        // If emp_code exists, delete from employee_attendance
        if (!empty($empCode)) {
            $query = "DELETE FROM employee_attendance WHERE emp_code = ?";
            $stmt = mysqli_prepare($conn, $query);
            if (!$stmt) {
                throw new Exception('Failed to prepare employee_attendance deletion query.');
            }
            mysqli_stmt_bind_param($stmt, 's', $empCode);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to execute employee_attendance deletion.');
            }
            mysqli_stmt_close($stmt);
        }

        // Finally, delete from teacher_account
        $query = "DELETE FROM teacher_account WHERE teacher_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception('Failed to prepare teacher_account deletion query.');
        }
        mysqli_stmt_bind_param($stmt, 'i', $teacherId);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('Failed to execute teacher_account deletion.');
        }

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $response['status'] = 'Success';
            $response['message'] = 'Teacher account and related records deleted successfully.';
        } else {
            throw new Exception('Teacher not found or already deleted.');
        }

        mysqli_stmt_close($stmt);

        // Commit transaction
        mysqli_commit($conn);
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_roll_back($conn);
        $response['status'] = 'Error';
        $response['message'] = $e->getMessage();
    }
} else {
    $response['status'] = 'Error';
    $response['message'] = 'Invalid request. Teacher ID is missing.';
}

echo json_encode($response);
exit();
?>