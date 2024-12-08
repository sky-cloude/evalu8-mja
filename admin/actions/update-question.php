<?php
include('../../db_conn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_id = $_POST['editQuestionId'];
    $updated_question = mysqli_real_escape_string($conn, $_POST['editQuestionInput']);

    // Update query to modify the question in the database
    $update_query = "UPDATE question_list SET question = '$updated_question' WHERE question_id = $question_id";

    if (mysqli_query($conn, $update_query)) {
        session_start();
        $_SESSION['flash_message'] = "Question updated successfully!";
        echo "Question updated successfully!";
    } else {
        session_start();
        $_SESSION['flash_message'] = "Error updating question: " . mysqli_error($conn);
        echo "Error updating question: " . mysqli_error($conn);
    }
}

