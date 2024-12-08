<?php
include '../../db_conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['criteria_id']) && isset($_POST['criteria'])) {
        $criteria_id = $_POST['criteria_id'];
        $criteria = $_POST['criteria'];

        // Prepare and execute the SQL query to update the criteria
        $stmt = $conn->prepare("UPDATE criteria_list SET criteria = ? WHERE criteria_id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $criteria, $criteria_id);

            if ($stmt->execute()) {
                echo 'Success';
            } else {
                echo 'Error: ' . $stmt->error;
            }

            $stmt->close();
        } else {
            echo 'Prepare failed: ' . $conn->error;
        }

        $conn->close();
    } else {
        echo 'Missing criteria_id or criteria.';
    }
} else {
    echo 'Invalid request method.';
}

