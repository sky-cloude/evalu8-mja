<?php
session_start();
include('../../db_conn.php');

// Set header for JSON response
header('Content-Type: application/json');

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $break_time_id = isset($_POST['break_time_id']) ? intval($_POST['break_time_id']) : 0;

    // Validate input
    if (empty($break_time_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid Break Time ID.']);
        exit();
    }

    // Start a transaction to ensure data integrity
    mysqli_begin_transaction($conn);

    try {
        // Optional: Check if the break time exists
        $check_sql = "SELECT * FROM break_time_schedule WHERE break_time_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        if (!$check_stmt) {
            throw new Exception('Database error: ' . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($check_stmt, "i", $break_time_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if ($check_result->num_rows === 0) {
            throw new Exception('Break Time not found.');
        }

        mysqli_stmt_close($check_stmt);

        // Delete the break time
        $delete_sql = "DELETE FROM break_time_schedule WHERE break_time_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        if (!$delete_stmt) {
            throw new Exception('Database error: ' . mysqli_error($conn));
        }
        mysqli_stmt_bind_param($delete_stmt, "i", $break_time_id);
        mysqli_stmt_execute($delete_stmt);

        if (mysqli_stmt_affected_rows($delete_stmt) === 0) {
            throw new Exception('No Break Time was deleted.');
        }

        mysqli_stmt_close($delete_stmt);

        // Commit the transaction
        mysqli_commit($conn);

        echo json_encode(['success' => true, 'message' => 'Break Time deleted successfully!']);
    } catch (Exception $e) {
        // Rollback the transaction on error
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

    // Close the database connection
    mysqli_close($conn);
    exit();
} else {
    // Invalid request method
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}
?>
