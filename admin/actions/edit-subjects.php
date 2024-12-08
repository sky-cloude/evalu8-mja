<?php
session_start();
include '../../db_conn.php'; // Include the database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data from POST request
    $subjectId = $_POST['subjectId'];
    $code = $_POST['code'];
    $subject = $_POST['subject'];

    // Validate input
    if (empty($subjectId) || empty($code) || empty($subject)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all fields.'
        ]);
        exit();
    }

    // Check if the code and subject combination already exists, excluding the current record
    $checkQuery = "SELECT COUNT(*) FROM subject_list WHERE code = ? AND subject_title = ? AND subject_id != ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ssi", $code, $subject, $subjectId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        // Subject code and title combination already exists
        echo json_encode([
            'success' => false,
            'message' => 'This code and subject title combination already exists!'
        ]);
    } else {
        // Prepare SQL query to update the existing subject
        $updateQuery = "UPDATE subject_list SET code = ?, subject_title = ? WHERE subject_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssi", $code, $subject, $subjectId);

        // Execute query and check if successful
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'updatedSubject' => [
                    'subject_id' => $subjectId,
                    'code' => $code,
                    'subject_title' => $subject
                ]
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $stmt->error
            ]);
        }

        // Close statement and connection
        $stmt->close();
        $conn->close();
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.'
    ]);
}
