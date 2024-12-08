<?php
session_start();
include('../../db_conn.php');

// Set header for JSON response
header('Content-Type: application/json');

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $break_time_id = $_POST['break_time_id'];
    $break_name = trim($_POST['break_name']);
    $start_break = $_POST['start_break'];
    $end_break = $_POST['end_break'];

    // Validate inputs
    if (empty($break_time_id) || empty($break_name) || empty($start_break) || empty($end_break)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit();
    }

    // Validate that start_break and end_break are within 7:00 AM to 4:00 PM
    $startBreakTime = strtotime($start_break);
    $endBreakTime = strtotime($end_break);
    $workStartTime = strtotime('07:00:00');
    $workEndTime = strtotime('16:00:00');

    if ($startBreakTime < $workStartTime || $endBreakTime > $workEndTime) {
        echo json_encode(['success' => false, 'message' => 'Break times must be between 7:00 AM and 4:00 PM.']);
        exit();
    }

    // Validate that end_break is after start_break
    if ($start_break >= $end_break) {
        echo json_encode(['success' => false, 'message' => 'End Break time must be after Start Break time.']);
        exit();
    }

    // Check if the break time exists
    $check_sql = "SELECT * FROM break_time_schedule WHERE break_time_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    $check_stmt->bind_param("i", $break_time_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Break Time not found.']);
        exit();
    }

    // Check for duplicate break time excluding current record
    $duplicate_sql = "SELECT * FROM break_time_schedule WHERE break_name = ? AND start_break = ? AND end_break = ? AND break_time_id != ?";
    $duplicate_stmt = $conn->prepare($duplicate_sql);
    if (!$duplicate_stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    $duplicate_stmt->bind_param("sssi", $break_name, $start_break, $end_break, $break_time_id);
    $duplicate_stmt->execute();
    $duplicate_result = $duplicate_stmt->get_result();

    if ($duplicate_result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Another Break Time with the same details already exists.']);
        exit();
    }

    // Update the break time
    $update_sql = "UPDATE break_time_schedule SET break_name = ?, start_break = ?, end_break = ? WHERE break_time_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    if (!$update_stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    $update_stmt->bind_param("sssi", $break_name, $start_break, $end_break, $break_time_id);

    if ($update_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'updatedBreakTime' => [
                'break_time_id' => $break_time_id,
                'break_name' => htmlspecialchars($break_name),
                'start_break' => htmlspecialchars($start_break),
                'end_break' => htmlspecialchars($end_break)
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating Break Time: ' . $conn->error]);
    }

    // Close statements and connection
    $check_stmt->close();
    $duplicate_stmt->close();
    $update_stmt->close();
    $conn->close();
    exit();
} else {
    // Invalid request method
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}
?>
