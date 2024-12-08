<?php
include '../../db_conn.php'; // Include the database connection file

// Start the session to use session variables
session_start();

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $gradeLevel = $_POST['gradeLevel'];
    $section = $_POST['section'];

    // Validate input
    if (empty($gradeLevel) || empty($section)) {
        $_SESSION['message'] = 'Please fill in all fields.';
        $_SESSION['message_type'] = 'danger';
        header('Location: ../admin-class.php');
        exit();
    }

    // Check if the grade level and section combination already exists
    $checkQuery = "SELECT * FROM class_list WHERE grade_level = ? AND section = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ss", $gradeLevel, $section);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Grade level and section combination already exists
        $_SESSION['message'] = 'This grade level and section combination already exists!';
        $_SESSION['message_type'] = 'danger';
    } else {
        // Prepare SQL query to insert new class
        $insertQuery = "INSERT INTO class_list (grade_level, section) VALUES (?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ss", $gradeLevel, $section);

        // Execute query and check if successful
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Class added successfully!';
            $_SESSION['message_type'] = 'success'; // Success message
        } else {
            $_SESSION['message'] = 'Error: ' . $stmt->error;
            $_SESSION['message_type'] = 'danger'; // Error message
        }
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();

    // Redirect back to the class page
    header('Location: ../admin-class.php');
    exit();
} else {
    echo "Invalid request method.";
}

