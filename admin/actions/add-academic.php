<?php
// actions/add-academic.php

include '../../db_conn.php'; // Include the database connection file
session_start(); // Start the session to use session variables

if (isset($_POST['acadYear']) && isset($_POST['quarter']) && isset($_POST['status']) && isset($_POST['evaluationPeriod'])) {
    // Retrieve the form data
    $acadYear = $_POST['acadYear'];
    $quarter = $_POST['quarter'];
    $status = ($_POST['status'] == 'In Progress') ? 1 : 0;
    $evaluationPeriod = $_POST['evaluationPeriod'];

    // Begin transaction to ensure atomicity
    $conn->begin_transaction();

    try {
        // If the new academic year is set to "In Progress", update all other academic years to "Closed"
        if ($status === 1) {
            $closeOtherQuery = "UPDATE academic_year SET is_active = 0 WHERE is_active = 1";
            $conn->query($closeOtherQuery);
        }

        // Prepare the SQL query to insert the new academic year with evaluation period
        $insertQuery = "INSERT INTO academic_year (year, quarter, is_active, evaluation_period) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param('siis', $acadYear, $quarter, $status, $evaluationPeriod);

        if ($stmt->execute()) {
            $conn->commit();
            $_SESSION['message'] = 'Academic year added successfully.';
            $_SESSION['message_type'] = 'success';
        } else {
            throw new Exception('Error: ' . $stmt->error);
        }

        $stmt->close();
    } catch (Exception $e) {
        // Rollback transaction in case of an error
        $conn->rollback();
        $_SESSION['message'] = $e->getMessage();
        $_SESSION['message_type'] = 'danger';
    }

    $conn->close();

    header('Location: ../admin-acad-year.php');
    exit();
} else {
    $_SESSION['message'] = 'Invalid request.';
    $_SESSION['message_type'] = 'danger';
    header('Location: ../admin-acad-year.php');
    exit();
}
?>
