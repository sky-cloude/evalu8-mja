<?php
// Start the session to manage messages if necessary
session_start();

// Include database connection
include('../../db_conn.php');

// Set the response header to JSON
header('Content-Type: application/json');

// Initialize response array
$response = [];

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if staff_id is set in the POST request
    if (isset($_POST['staff_id']) && !empty($_POST['staff_id'])) {
        // Sanitize the staff_id input
        $staffId = mysqli_real_escape_string($conn, $_POST['staff_id']);

        // Prepare the SQL DELETE statement
        $query = "DELETE FROM staff_account WHERE staff_id = ?";
        $stmt = mysqli_prepare($conn, $query);

        if ($stmt) {
            // Bind the staff_id parameter as an integer
            mysqli_stmt_bind_param($stmt, 'i', $staffId);

            // Execute the DELETE query
            if (mysqli_stmt_execute($stmt)) {
                // Set success response
                $response = [
                    'status' => 'Success',
                    'message' => 'Staff account deleted successfully.'
                ];
            } else {
                // Handle query execution failure
                $response = [
                    'status' => 'Error',
                    'message' => 'Failed to delete the staff account. Please try again.'
                ];
            }

            // Close the prepared statement
            mysqli_stmt_close($stmt);
        } else {
            // Handle query preparation failure
            $response = [
                'status' => 'Error',
                'message' => 'Failed to prepare the deletion query.'
            ];
        }
    } else {
        // Handle missing or invalid staff_id
        $response = [
            'status' => 'Error',
            'message' => 'Invalid request. Staff ID is missing.'
        ];
    }
} else {
    // Handle invalid request method
    $response = [
        'status' => 'Error',
        'message' => 'Invalid request method. Only POST requests are allowed.'
    ];
}

// Output the response as JSON
echo json_encode($response);

// Close the database connection
mysqli_close($conn);
?>
