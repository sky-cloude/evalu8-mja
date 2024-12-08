<?php
include('../../db_conn.php');
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_eval_id = isset($_POST['subject_eval_id']) ? intval($_POST['subject_eval_id']) : 0;
    $subject_id = isset($_POST['subjectTitle']) ? intval($_POST['subjectTitle']) : 0;
    $teacher_id = isset($_POST['teacher']) ? intval($_POST['teacher']) : 0;

    if ($subject_eval_id > 0 && $subject_id > 0 && $teacher_id > 0) {
        // Check if the subject is already assigned to the class
        $check_query = "SELECT ste.subject_eval_id
                        FROM subject_to_eval ste
                        WHERE ste.subject_id = $subject_id
                        AND ste.evaluation_id = (SELECT evaluation_id FROM subject_to_eval WHERE subject_eval_id = $subject_eval_id)
                        AND ste.subject_eval_id != $subject_eval_id";

        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            echo json_encode(['success' => false, 'message' => 'This subject is already assigned to this class.']);
        } else {
            // Update the subject assignment
            $update_query = "UPDATE subject_to_eval SET subject_id = $subject_id, teacher_id = $teacher_id WHERE subject_eval_id = $subject_eval_id";

            if (mysqli_query($conn, $update_query)) {
                // Fetch updated data to return
                $fetch_query = "SELECT ste.subject_eval_id, 
                                       ste.subject_id,
                                       ste.teacher_id,
                                       CONCAT(ta.firstName, ' ', ta.lastName) AS teacher_name, 
                                       sl.subject_title, sl.code
                                FROM subject_to_eval ste
                                JOIN teacher_account ta ON ste.teacher_id = ta.teacher_id
                                JOIN subject_list sl ON ste.subject_id = sl.subject_id
                                WHERE ste.subject_eval_id = $subject_eval_id";

                $fetch_result = mysqli_query($conn, $fetch_query);
                $updated_row = mysqli_fetch_assoc($fetch_result);

                echo json_encode([
                    'success' => true,
                    'subject_eval_id' => $subject_eval_id,
                    'subject_id' => $updated_row['subject_id'],
                    'teacher_id' => $updated_row['teacher_id'],
                    'subject_display' => $updated_row['code'] . ' - ' . $updated_row['subject_title'],
                    'teacher_name' => $updated_row['teacher_name'],
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error updating subject assignment.']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    }
}
?>
