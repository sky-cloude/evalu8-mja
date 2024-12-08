<?php
include('../../db_conn.php');

header('Content-Type: application/json'); // Set header to return JSON

$response = array('success' => false, 'message' => '', 'question_id' => '', 'question' => '');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $criteria_id = $_POST['criteriaInput'];
    $question = $_POST['questionInput'];

    if ($criteria_id && $question) {
        // Step 1: Fetch the next `order_by` value
        $query_order = "SELECT IFNULL(MAX(order_by), 0) + 1 AS next_order FROM question_list WHERE criteria_id = '$criteria_id'";
        $result_order = mysqli_query($conn, $query_order);
        $row = mysqli_fetch_assoc($result_order);
        $next_order = $row['next_order'];

        // Step 2: Insert the new question into the `question_list` table
        $query_insert = "INSERT INTO question_list (criteria_id, question, order_by) 
                         VALUES ('$criteria_id', '$question', '$next_order')";
        if (mysqli_query($conn, $query_insert)) {
            $question_id = mysqli_insert_id($conn); // Fetch the inserted question ID

            // Return success response with the new question data
            $response['success'] = true;
            $response['message'] = "Question added successfully!";
            $response['question_id'] = $question_id;
            $response['question'] = $question;
        } else {
            $response['message'] = "Error adding question: " . mysqli_error($conn);
        }
    } else {
        $response['message'] = "Missing required fields!";
    }
} else {
    $response['message'] = "Invalid request method!";
}

echo json_encode($response);

