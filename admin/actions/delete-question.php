<?php
include('../../db_conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_id = $_POST['questionId'];

    // Fetch the criteria_id and order_by of the question being deleted
    $query = "SELECT criteria_id, order_by FROM question_list WHERE question_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $stmt->bind_result($criteria_id, $order_by);
    $stmt->fetch();
    $stmt->close();

    // Delete the question
    $delete_query = "DELETE FROM question_list WHERE question_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $question_id);

    if ($stmt->execute()) {
        // Adjust the order_by for all remaining questions in the same criteria
        $update_query = "UPDATE question_list 
                         SET order_by = order_by - 1 
                         WHERE criteria_id = ? 
                         AND order_by > ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ii", $criteria_id, $order_by);
        $update_stmt->execute();
        $update_stmt->close();

        session_start();
        $_SESSION['flash_message'] = "Question deleted successfully!";
        echo "Success";
    } else {
        session_start();
        $_SESSION['flash_message'] = "Error deleting question: " . $conn->error;
        echo "Error deleting question: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>
