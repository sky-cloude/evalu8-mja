<?php
include('../../db_conn.php'); // Include your database connection

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data) && is_array($data)) {
    foreach ($data as $item) {
        $questionId = $item['questionId'];
        $order = $item['order'];

        // Update the order_by for each question based on the new order
        $query = "UPDATE question_list SET order_by = ? WHERE question_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'ii', $order, $questionId);
        mysqli_stmt_execute($stmt);
    }

    // Set flash message for successful order save
    session_start();
    $_SESSION['flash_message'] = "Order saved successfully!";
    echo json_encode(['status' => 'success']);
} else {
    session_start();
    $_SESSION['flash_message'] = "Error saving order!";
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
}
?>
