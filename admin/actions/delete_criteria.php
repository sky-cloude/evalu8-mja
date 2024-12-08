<?php
include '../../db_conn.php';
session_start(); // Start session to use session variables

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['criteria_id'])) {
        $criteria_id = $_POST['criteria_id'];

        // First, delete all related rows in question_list that reference this criteria_id
        $stmt = $conn->prepare("DELETE FROM question_list WHERE criteria_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $criteria_id);
            if ($stmt->execute()) {
                $stmt->close();

                // Now, delete the criteria from criteria_list
                $stmt = $conn->prepare("DELETE FROM criteria_list WHERE criteria_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $criteria_id);
                    if ($stmt->execute()) {
                        $stmt->close();
                        reorderCriteria($conn);

                        // Set flash message in session
                        $_SESSION['flash_message'] = 'Criteria and its questions deleted successfully!';

                        // Send success response
                        echo 'Success';
                    } else {
                        echo 'Error: ' . $stmt->error;
                    }
                } else {
                    echo 'Prepare failed: ' . $conn->error;
                }
            } else {
                echo 'Error deleting related rows: ' . $stmt->error;
            }
        } else {
            echo 'Prepare failed: ' . $conn->error;
        }

        $conn->close();
    } else {
        echo 'No criteria_id provided.';
    }
} else {
    echo 'Invalid request method.';
}

// Function to reorder the 'ordered_by' column after deletion
function reorderCriteria($conn) {
    $result = $conn->query("SELECT criteria_id FROM criteria_list ORDER BY ordered_by ASC");

    if ($result->num_rows > 0) {
        $newOrder = 1;

        while ($row = $result->fetch_assoc()) {
            $criteria_id = $row['criteria_id'];

            // Update the 'ordered_by' field
            $stmt = $conn->prepare("UPDATE criteria_list SET ordered_by = ? WHERE criteria_id = ?");
            $stmt->bind_param("ii", $newOrder, $criteria_id);

            if ($stmt->execute()) {
                $stmt->close();
            } else {
                echo 'Error: ' . $stmt->error;
            }

            $newOrder++;
        }
    }
}
