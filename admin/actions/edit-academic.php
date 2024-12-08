<?php
session_start();
include '../../db_conn.php'; // Include the database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data from POST request
    $acadYearId = $_POST['acadYearId'];
    $acadYear = $_POST['acadYear'];
    $quarter = $_POST['quarter'];
    $status = ($_POST['status'] === 'In Progress') ? 1 : 0;
    $evaluationPeriod = $_POST['evaluationPeriod'];

    // Validate input
    if (empty($acadYearId) || empty($acadYear) || empty($quarter) || empty($evaluationPeriod)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all fields.'
        ]);
        exit();
    }

    // Convert the evaluation period to a DateTime object
    $evaluationEndDateTime = DateTime::createFromFormat('Y-m-d\TH:i', $evaluationPeriod);
    $currentDateTime = new DateTime(); // Get the current time

    // Begin transaction to ensure atomicity
    $conn->begin_transaction();

    try {
        // If the status is 'In Progress', check if there is another academic year already 'In Progress'
        if ($status === 1) {
            $checkQuery = "SELECT COUNT(*) FROM academic_year WHERE is_active = 1 AND acad_year_id != ?";
            $stmt = $conn->prepare($checkQuery);
            $stmt->bind_param("i", $acadYearId);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if ($count > 0) {
                throw new Exception('Another academic year is already In Progress. Only one academic year can be In Progress.');
            }

            // Set all other academic years to "Closed"
            $closeOthersQuery = "UPDATE academic_year SET is_active = 0 WHERE is_active = 1 AND acad_year_id != ?";
            $stmt = $conn->prepare($closeOthersQuery);
            $stmt->bind_param("i", $acadYearId);
            $stmt->execute();
            $stmt->close();
        }

        // Prepare the SQL query for updating academic year, quarter, and evaluation period
        // If the evaluation period has ended, do not allow updating the status
        if ($evaluationEndDateTime > $currentDateTime) {
            // Allow status to be updated because evaluation period is still open
            $updateQuery = "UPDATE academic_year SET year = ?, quarter = ?, is_active = ?, evaluation_period = ? WHERE acad_year_id = ?";
        } else {
            // If the evaluation period has ended, do not update the status
            $updateQuery = "UPDATE academic_year SET year = ?, quarter = ?, evaluation_period = ? WHERE acad_year_id = ?";
        }

        // Convert evaluationPeriod to 'Y-m-d H:i:s' format for storage in the database
        $evaluationDateTime = $evaluationEndDateTime->format('Y-m-d H:i:s');

        // Prepare the SQL statement
        $stmt = $conn->prepare($updateQuery);

        if ($evaluationEndDateTime > $currentDateTime) {
            // Bind parameters when status is allowed to be updated
            $stmt->bind_param("sissi", $acadYear, $quarter, $status, $evaluationDateTime, $acadYearId);
        } else {
            // Bind parameters when status is NOT allowed to be updated
            $stmt->bind_param("sssi", $acadYear, $quarter, $evaluationDateTime, $acadYearId);
        }

        // Execute the query and check if it was successful
        if ($stmt->execute()) {
            // Commit the transaction
            $conn->commit();

            // Convert the evaluation period back to 'Y-m-d\TH:i' format for frontend use
            $evaluationPeriodFormatted = DateTime::createFromFormat('Y-m-d H:i:s', $evaluationDateTime)->format('Y-m-d\TH:i');

            // Return JSON response to be used by the frontend for updating the table
            echo json_encode([
                'success' => true,
                'updatedAcademicYear' => [
                    'acad_year_id' => $acadYearId,
                    'year' => $acadYear,
                    'quarter' => $quarter,
                    'is_active' => $status, // Still returning status even if it hasn't changed
                    'evaluation_period' => $evaluationPeriodFormatted // Return in 'Y-m-d\TH:i' format for the datetime-local input
                ]
            ]);
        } else {
            throw new Exception('Error: ' . $stmt->error);
        }

    } catch (Exception $e) {
        // Rollback the transaction in case of an error
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

    // Close the statement and the connection
    $stmt->close();
    $conn->close();
} else {
    // Handle invalid request method
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.'
    ]);
}
