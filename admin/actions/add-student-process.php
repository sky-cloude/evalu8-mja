<?php
// actions/add-student-process.php

// Ensure no whitespace before this PHP tag

// Include database connection
include('../../db_conn.php');

// Ensure proper JSON header
header('Content-Type: application/json');

// Clear any previous output
ob_start();

// Disable error output to the browser
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['studentId'])) {
    $evaluation_id = intval($_POST['evaluation_id']);
    $student_id = intval($_POST['studentId']);
    $acad_year_id = intval($_POST['acad_year_id']); // Retrieve acad_year_id

    // Validate inputs
    if ($evaluation_id == 0 || $student_id == 0 || $acad_year_id == 0) {
        ob_end_clean();
        echo json_encode(['error' => 'Invalid input! Please provide all necessary information.']);
        exit;
    }

    // Check if the student exists in student_account
    $student_exists_query = "SELECT COUNT(*) AS student_exists FROM student_account WHERE student_id = $student_id";
    $student_exists_result = mysqli_query($conn, $student_exists_query);
    if (!$student_exists_result) {
        ob_end_clean();
        echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
        exit;
    }
    $student_exists_row = mysqli_fetch_assoc($student_exists_result);

    if ($student_exists_row['student_exists'] == 0) {
        // Student does not exist in the student_account table
        ob_end_clean();
        echo json_encode(['error' => 'This student does not exist in the student account database.']);
        exit;
    }

    // Check if the student is already added to any evaluation in the same academic year
    $check_query = "
        SELECT COUNT(*) AS student_count 
        FROM students_eval_restriction ser
        INNER JOIN evaluation_list el ON ser.evaluation_id = el.evaluation_id
        WHERE ser.student_id = $student_id AND el.acad_year_id = $acad_year_id
    ";

    $check_result = mysqli_query($conn, $check_query);
    if (!$check_result) {
        ob_end_clean();
        echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
        exit;
    }
    $check_row = mysqli_fetch_assoc($check_result);

    if ($check_row['student_count'] > 0) {
        // Student already exists in this academic year
        ob_end_clean();
        echo json_encode(['error' => 'The student is already enrolled in this academic year.']);
        exit;
    }

    // Insert the student into students_eval_restriction table with evaluation_id
    $insert_query = "
        INSERT INTO students_eval_restriction (evaluation_id, student_id) 
        VALUES ($evaluation_id, $student_id)";
    
    if (mysqli_query($conn, $insert_query)) {
        $new_students_eval_id = mysqli_insert_id($conn); // Get the newly created students_eval_id

        // Fetch the student's full name for confirmation
        $student_name_query = "SELECT CONCAT(firstName, ' ', lastName) AS full_name FROM student_account WHERE student_id = $student_id";
        $student_name_result = mysqli_query($conn, $student_name_query);
        if ($student_name_result && mysqli_num_rows($student_name_result) > 0) {
            $student_name_row = mysqli_fetch_assoc($student_name_result);
            $student_full_name = $student_name_row['full_name'];
        } else {
            $student_full_name = "Student ID $student_id";
        }

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'student_id' => $student_id,
            'students_eval_id' => $new_students_eval_id,
            'student_full_name' => $student_full_name
        ]);
    } else {
        ob_end_clean();
        echo json_encode(['error' => 'Failed to add student: ' . mysqli_error($conn)]);
    }

    exit;
} else {
    ob_end_clean();
    echo json_encode(['error' => 'Invalid request.']);
    exit;
}
?>
