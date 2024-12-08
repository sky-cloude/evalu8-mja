<?php
include '../../db_conn.php';
session_start(); // Start the session to use session variables

$response = array(); // Initialize the response array

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['criteria'])) {
        $criteria = $_POST['criteria'];

        // Start a transaction
        $conn->begin_transaction();

        try {
            // 1. Increment the ordered_by of all existing criteria by 1
            $update_query = "UPDATE criteria_list SET ordered_by = ordered_by + 1";
            if (!$conn->query($update_query)) {
                throw new Exception("Error updating ordered_by values: " . $conn->error);
            }

            // 2. Insert the new criterion with ordered_by = 1
            $insert_query = "INSERT INTO criteria_list (criteria, ordered_by) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_query);
            $ordered_by = 1;  // The new criterion should have ordered_by = 1
            if ($stmt) {
                $stmt->bind_param("si", $criteria, $ordered_by);
                if ($stmt->execute()) {
                    $new_criteria_id = $stmt->insert_id; // Get the ID of the newly inserted criterion
                    $stmt->close();
                } else {
                    throw new Exception("Error inserting new criterion: " . $stmt->error);
                }
            } else {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            // Commit the transaction
            $conn->commit();

            // Set the flash message
            $_SESSION['flash_message'] = 'New criterion added successfully!';

            // Prepare the response
            $response['status'] = 'Success';
            $response['message'] = 'New criterion added successfully!';
            $response['criteria'] = array(
                'criteria_id' => $new_criteria_id,
                'criteria' => $criteria,
                'ordered_by' => $ordered_by
            );
        } catch (Exception $e) {
            // Roll back the transaction on error
            $conn->rollback();
            $response['status'] = 'Error';
            $response['message'] = $e->getMessage();
        }

        $conn->close();
    } else {
        $response['status'] = 'Error';
        $response['message'] = 'No criteria provided.';
    }
} else {
    $response['status'] = 'Error';
    $response['message'] = 'Invalid request method.';
}

// Return the response as JSON
echo json_encode($response);
