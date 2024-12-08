<?php
include('../../db_conn.php');

// Get student_id from query string
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if ($student_id > 0) {
    // Fetch student details
    $query = "SELECT firstName, lastName, email, avatar, created_at FROM student_account WHERE student_id = $student_id";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $student = mysqli_fetch_assoc($result);

        // Encode the avatar as base64 if it's not empty
        if (!empty($student['avatar'])) {
            $student['avatar'] = base64_encode($student['avatar']);
        }

        // Send the response as JSON
        echo json_encode($student);
    } else {
        // Send an error response
        echo json_encode(['error' => 'Student not found']);
    }
} else {
    // Send an error response if no valid student ID is provided
    echo json_encode(['error' => 'Invalid student ID']);
}

