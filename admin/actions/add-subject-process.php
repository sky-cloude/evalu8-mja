<?php
include('../../db_conn.php');
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
    $evaluation_id = isset($_POST['evaluation_id']) ? intval($_POST['evaluation_id']) : 0;  // Get evaluation_id from POST
    $subject_id = isset($_POST['subjectTitle']) ? intval($_POST['subjectTitle']) : 0;
    $teacher_id = isset($_POST['teacher']) ? intval($_POST['teacher']) : 0;

    if ($class_id > 0 && $evaluation_id > 0 && $subject_id > 0 && $teacher_id > 0) {
        // Check if the subject is already assigned to the class (evaluation_id)
        $check_query = "SELECT * FROM subject_to_eval WHERE subject_id = $subject_id AND evaluation_id = $evaluation_id";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            // If the subject is already assigned to this class
            echo json_encode(['success' => false, 'message' => 'This subject is already added for this class.']);
        } else {
            // Insert the subject-teacher pair into the subject_to_eval table
            $insert_query = "INSERT INTO subject_to_eval (subject_id, teacher_id, evaluation_id) VALUES ($subject_id, $teacher_id, $evaluation_id)";
            
            if (mysqli_query($conn, $insert_query)) {
                // Get the last inserted subject_eval_id
                $subject_eval_id = mysqli_insert_id($conn);
            
                // Fetch the subject title and code
                $subject_query = "SELECT subject_title, code FROM subject_list WHERE subject_id = $subject_id";
                $subject_result = mysqli_query($conn, $subject_query);
                $subject = mysqli_fetch_assoc($subject_result);
            
                $teacher_query = "SELECT CONCAT(firstName, ' ', lastName) AS teacher_name FROM teacher_account WHERE teacher_id = $teacher_id";
                $teacher_result = mysqli_query($conn, $teacher_query);
                $teacher = mysqli_fetch_assoc($teacher_result);
            
                // Include code in subject_display
                $subject_display = $subject['code'] . ' - ' . $subject['subject_title'];
            
                echo json_encode([
                    'success' => true,
                    'message' => 'Subject added successfully.',
                    'subject_display' => $subject_display,
                    'teacher_name' => $teacher['teacher_name'],
                    'subject_eval_id' => $subject_eval_id,
                    'subject_id' => $subject_id,
                    'teacher_id' => $teacher_id
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error adding subject.']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
    }
}
?>
