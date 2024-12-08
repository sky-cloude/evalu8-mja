<?php
session_start();
include '../../db_conn.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data from POST request
    $classId = $_POST['classId'];
    $gradeLevel = $_POST['gradeLevel'];
    $section = $_POST['section'];

    // Validate input
    if (empty($classId) || empty($gradeLevel) || empty($section)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all fields.'
        ]);
        exit();
    }

    // Check if the class name already exists, excluding the current record
    $checkQuery = "SELECT COUNT(*) FROM class_list WHERE grade_level = ? AND section = ? AND class_id != ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ssi", $gradeLevel, $section, $classId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        // Class already exists
        echo json_encode([
            'success' => false,
            'message' => 'This class already exists!'
        ]);
    } else {
        // Prepare SQL query to update the existing class
        $updateQuery = "UPDATE class_list SET grade_level = ?, section = ? WHERE class_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssi", $gradeLevel, $section, $classId);

        // Execute query and check if successful
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'updatedClass' => [
                    'class_id' => $classId,
                    'grade_level' => $gradeLevel,
                    'section' => $section
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
