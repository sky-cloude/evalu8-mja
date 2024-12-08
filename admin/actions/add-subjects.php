<?php
include '../../db_conn.php'; // Include the database connection file

// Start the session to use session variables
session_start();

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $code = $_POST['code'];
    $subject = $_POST['subject'];

    // Validate input
    if (empty($code) || empty($subject)) {
        $_SESSION['message'] = 'Please fill in all fields.';
        $_SESSION['message_type'] = 'danger';
        header('Location: ../admin-subjects.php');
        exit();
    }

    // Check if the code and subject combination already exists
    $checkQuery = "SELECT * FROM subject_list WHERE code = ? AND subject_title = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ss", $code, $subject);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Subject code and title combination already exists
        $_SESSION['message'] = 'This code and subject title combination already exists!';
        $_SESSION['message_type'] = 'danger';
    } else {
        // Prepare SQL query to insert new subject
        $insertQuery = "INSERT INTO subject_list (code, subject_title) VALUES (?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ss", $code, $subject);

        // Execute query and check if successful
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Subject added successfully!';
            $_SESSION['message_type'] = 'success'; // Success message
        } else {
            $_SESSION['message'] = 'Error: ' . $stmt->error;
            $_SESSION['message_type'] = 'danger'; // Error message
        }
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();

    // Redirect back to the subjects page
    header('Location: ../admin-subjects.php');
    exit();
} else {
    echo "Invalid request method.";
}

