<?php
include '../../db_conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data) {
        // Begin a transaction to ensure all updates are processed together
        $conn->begin_transaction();
        try {
            // Get the total number of rows in the table
            $result = $conn->query("SELECT COUNT(*) as count FROM criteria_list");
            $row = $result->fetch_assoc();
            $total_rows = $row['count'];

            foreach ($data as $item) {
                $criteria_id = $item['criteria_id'];
                $ordered_by = $item['ordered_by'];

                // Ensure ordered_by does not exceed the total number of rows
                $ordered_by = min($ordered_by, $total_rows);

                // Update the ordered_by value for each criterion
                $stmt = $conn->prepare("UPDATE criteria_list SET ordered_by = ? WHERE criteria_id = ?");
                $stmt->bind_param("ii", $ordered_by, $criteria_id);

                if (!$stmt->execute()) {
                    throw new Exception("Error updating order: " . $stmt->error);
                }
            }

            // Commit the transaction
            $conn->commit();
            echo 'Success';
        } catch (Exception $e) {
            // Rollback the transaction in case of an error
            $conn->rollback();
            echo 'Error: ' . $e->getMessage();
        }
    } else {
        echo 'No data received.';
    }

    $conn->close();
} else {
    echo 'Invalid request method.';
}
