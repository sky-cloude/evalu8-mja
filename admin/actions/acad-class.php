<?php
// actions/acad-class.php

include('../../db_conn.php'); 

$acad_year_id = isset($_POST['acad_year_id']) ? intval($_POST['acad_year_id']) : 0;
$success = true;
$message = '';
$duplicate_classes = [];

if ($acad_year_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid academic year ID.']);
    exit;
}

// Initialize an array to hold class IDs to be inserted
$class_ids_to_insert = [];

// Check for duplicate classes
foreach ($_POST as $key => $value) {
    if (strpos($key, 'class_id_') === 0) {
        $class_id = intval($value);

        // Check if the class is already added to the evaluation_list for this academic year using prepared statements
        $check_query = "SELECT COUNT(*) as count FROM evaluation_list WHERE class_id = ? AND acad_year_id = ?";
        $stmt_check = mysqli_prepare($conn, $check_query);
        if (!$stmt_check) {
            $success = false;
            $message = "Database error during checking: " . mysqli_error($conn);
            break;
        }
        mysqli_stmt_bind_param($stmt_check, "ii", $class_id, $acad_year_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        if (!$result_check) {
            $success = false;
            $message = "Database error during checking: " . mysqli_error($conn);
            mysqli_stmt_close($stmt_check);
            break;
        }

        $row = mysqli_fetch_assoc($result_check);
        mysqli_stmt_close($stmt_check);

        if ($row['count'] > 0) {
            // Class is already added, mark as duplicate
            $duplicate_classes[] = $class_id;
        } else {
            // Class is valid to be inserted
            $class_ids_to_insert[] = $class_id;
        }
    }
}

if (count($duplicate_classes) > 0) {
    $success = false;
    $message = "Duplicate class IDs detected: " . implode(', ', $duplicate_classes) . ". Please select different classes.";
} else {
    if (empty($class_ids_to_insert)) {
        $success = false;
        $message = "No classes selected to add.";
    } else {
        // Begin transaction
        mysqli_begin_transaction($conn);

        try {
            // Prepare the INSERT statement using prepared statements
            $insert_query = "INSERT INTO evaluation_list (class_id, acad_year_id) VALUES (?, ?)";
            $stmt_insert = mysqli_prepare($conn, $insert_query);
            if (!$stmt_insert) {
                throw new Exception("Database error during preparation: " . mysqli_error($conn));
            }

            foreach ($class_ids_to_insert as $class_id) {
                mysqli_stmt_bind_param($stmt_insert, "ii", $class_id, $acad_year_id);
                if (!mysqli_stmt_execute($stmt_insert)) {
                    throw new Exception("Database error during insertion: " . mysqli_stmt_error($stmt_insert));
                }
            }

            mysqli_stmt_close($stmt_insert);

            // Commit the transaction
            mysqli_commit($conn);

            $message = "Classes successfully added!";
        } catch (Exception $e) {
            // Rollback the transaction on error
            mysqli_rollback($conn);
            $success = false;
            $message = "Failed to add classes: " . $e->getMessage();
        }
    }
}

echo json_encode(['success' => $success, 'message' => $message, 'duplicate_classes' => $duplicate_classes]);

// Close the database connection
mysqli_close($conn);
?>
