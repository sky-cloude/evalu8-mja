<?php
// Include necessary files and start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include('../../db_conn.php'); // Include your database connection file

// Check if student ID is set in session and the user is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: ../login/login-form.php");
    exit();
}

$student_id = $_SESSION['user_id']; // Use session variable for student ID

// Check if the request is POST (form submitted)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve and sanitize POST variables
    $evaluation_id = isset($_POST['evaluation_id']) ? intval($_POST['evaluation_id']) : 0;
    $teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
    $subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : 0;
    $student_sentiment = isset($_POST['student_sentiment']) ? mysqli_real_escape_string($conn, $_POST['student_sentiment']) : '';

    // Check if evaluation, teacher, and subject IDs are valid
    if ($evaluation_id <= 0 || $teacher_id <= 0 || $subject_id <= 0) {
        die("Invalid evaluation data.");
    }

    // Initialize an array to store question ratings
    $questions_ratings = [];

    // Iterate through POST data to get ratings for each question
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'question') === 0 && is_numeric($value)) {
            $question_id = str_replace('question', '', $key); // Extract question_id from POST key
            $rating = intval($value); // Get the rating value

            // Query to retrieve criteria_id from question_id
            $criteria_query = "SELECT criteria_id FROM question_list WHERE question_id = '$question_id'";
            $criteria_result = mysqli_query($conn, $criteria_query);

            if ($criteria_result && mysqli_num_rows($criteria_result) > 0) {
                $criteria_row = mysqli_fetch_assoc($criteria_result);
                $criteria_id = $criteria_row['criteria_id'];

                // Store question_id, rating, and criteria_id in the array
                $questions_ratings[] = [
                    'question_id' => $question_id,
                    'rating' => $rating,
                    'criteria_id' => $criteria_id
                ];
            }
        }
    }

    // Insert each rating into the evaluation_answers table
    foreach ($questions_ratings as $data) {
        $question_id = $data['question_id'];
        $rating = $data['rating'];
        $criteria_id = $data['criteria_id'];

        $insert_query = "
            INSERT INTO evaluation_answers (student_id, evaluation_id, teacher_id, subject_id, question_id, rating, criteria_id)
            VALUES ('$student_id', '$evaluation_id', '$teacher_id', '$subject_id', '$question_id', '$rating', '$criteria_id')
            ON DUPLICATE KEY UPDATE rating = '$rating', criteria_id = '$criteria_id'";

        if (!mysqli_query($conn, $insert_query)) {
            die("Error saving evaluation answer: " . mysqli_error($conn));
        }
    }

    // If a sentiment is provided, analyze it using the Flask API
    if (!empty($student_sentiment)) {
        $ch = curl_init('http://localhost:5000/analyze_batch'); // Flask API URL

        $data = json_encode(['texts' => [$student_sentiment]]); // Send as JSON

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            die("Error analyzing sentiment.");
        }

        $sentiment_data = json_decode($response, true);

       // Check if sentiment analysis result exists and handle unexpected responses
        if (isset($sentiment_data['results']) && !empty($sentiment_data['results'])) {
            // Extract sentiment label and type
            $sentiment_label = $sentiment_data['results'][0]['label'] ?? 'Neutral';
            $sentiment_type = 1; // Default to neutral

            if ($sentiment_label == 'Positive') {
                $sentiment_type = 2;
            } elseif ($sentiment_label == 'Negative') {
                $sentiment_type = 0;
            }

            // Insert or update sentiment and sentiment type in the evaluation_sentiments table
            $sentiment_query = "
                INSERT INTO evaluation_sentiments (student_id, evaluation_id, teacher_id, subject_id, sentiment, sentiment_type)
                VALUES ('$student_id', '$evaluation_id', '$teacher_id', '$subject_id', '$student_sentiment', '$sentiment_type')
                ON DUPLICATE KEY UPDATE sentiment = '$student_sentiment', sentiment_type = '$sentiment_type'";

            if (!mysqli_query($conn, $sentiment_query)) {
                die("Error saving sentiment: " . mysqli_error($conn));
            }
        }
        else {
            // Handle case where API returns no valid results
            die("Invalid sentiment analysis result.");
        }
    }

    // Redirect back to student dashboard or confirmation page
    header("Location: ../student-dashboard.php?status=success");
    exit();
} else {
    // If accessed directly, redirect to evaluation page
    header("Location: ../evaluate-teacher.php");
    exit();
}
?>
