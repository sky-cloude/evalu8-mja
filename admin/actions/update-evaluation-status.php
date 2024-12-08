<?php
include '../../db_conn.php'; // Include your database connection file
date_default_timezone_set('Asia/Manila'); // Set your timezone

// Get the current time in the same format as your database datetime values
$current_time = date('Y-m-d H:i:s');

// Single SQL query to update all active rows where evaluation period has ended
$updateQuery = "UPDATE academic_year SET is_active = 0 WHERE is_active = 1 AND evaluation_period < '$current_time'";
$result = mysqli_query($conn, $updateQuery);

if ($result) {
    echo "Updated successfully.<br>";
} else {
    die("Update failed: " . mysqli_error($conn));
}

mysqli_close($conn);
?>
