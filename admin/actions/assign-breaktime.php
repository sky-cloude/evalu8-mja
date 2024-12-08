<?php
// actions/assign-breaktime.php
session_start();
include('../../db_conn.php');
header('Content-Type: application/json');

// Function to sanitize input
function sanitize($data, $conn) {
    return mysqli_real_escape_string($conn, trim($data));
}

// Check if teacher_id and break_time_id are set in the POST request
if (isset($_POST['teacher_id']) && isset($_POST['break_time_id'])) {
    $teacher_id = sanitize($_POST['teacher_id'], $conn);
    $break_time_id = sanitize($_POST['break_time_id'], $conn);

    // Verify that the break_time_id exists
    $checkBreakQuery = "SELECT break_name, start_break, end_break FROM break_time_schedule WHERE break_time_id = ?";
    $stmtCheck = mysqli_prepare($conn, $checkBreakQuery);
    if ($stmtCheck) {
        mysqli_stmt_bind_param($stmtCheck, "i", $break_time_id);
        mysqli_stmt_execute($stmtCheck);
        mysqli_stmt_store_result($stmtCheck);
        if (mysqli_stmt_num_rows($stmtCheck) === 1) {
            mysqli_stmt_bind_result($stmtCheck, $break_name, $start_break, $end_break);
            mysqli_stmt_fetch($stmtCheck);
            mysqli_stmt_close($stmtCheck);

            // Update the teacher_account table to set the break_time_id
            $updateQuery = "UPDATE teacher_account SET break_time_id = ? WHERE teacher_id = ?";
            $stmtUpdate = mysqli_prepare($conn, $updateQuery);

            if ($stmtUpdate) {
                // Bind parameters
                mysqli_stmt_bind_param($stmtUpdate, "ii", $break_time_id, $teacher_id);

                // Execute the query
                if (mysqli_stmt_execute($stmtUpdate)) {
                    // Prepare break time display
                    $startTime = date("g:i a", strtotime($start_break));
                    $endTime = date("g:i a", strtotime($end_break));

                    // Calculate duration
                    $startDateTime = new DateTime($start_break);
                    $endDateTime = new DateTime($end_break);
                    $interval = $startDateTime->diff($endDateTime);
                    $hours = $interval->h;
                    $minutes = $interval->i;
                    $duration = "";
                    if ($hours > 0) {
                        $duration .= $hours . " hr";
                        if ($minutes > 0) {
                            $duration .= " " . $minutes . " mins";
                        }
                    } else {
                        $duration .= $minutes . " mins";
                    }

                    $break_time_display = htmlspecialchars($break_name) . " ({$startTime} to {$endTime}): {$duration}";

                    echo json_encode([
                        'success' => true,
                        'break_time_display' => $break_time_display,
                        'break_time_id' => $break_time_id
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => "Error assigning break time. Please try again."
                    ]);
                }

                // Close the statement
                mysqli_stmt_close($stmtUpdate);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => "Error preparing the update query. Please try again."
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Invalid break time selected."
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => "Error preparing the verification query. Please try again."
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => "Invalid request. Teacher ID and Break Time are required."
    ]);
}
?>
