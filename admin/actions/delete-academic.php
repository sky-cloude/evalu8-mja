<?php
// Include the database connection file
include '../../db_conn.php'; 
session_start(); // Start the session to use session variables

header('Content-Type: application/json'); // Set the response type to JSON

// Check if the ID is set in the POST data
if (isset($_POST['acad_year_id'])) {
    $acad_year_id = intval($_POST['acad_year_id']);

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Step 1: Fetch all evaluation_ids associated with the acad_year_id
        $fetchEvalIdsQuery = "SELECT evaluation_id FROM evaluation_list WHERE acad_year_id = ?";
        $stmtFetch = $conn->prepare($fetchEvalIdsQuery);
        if (!$stmtFetch) {
            throw new Exception("Error preparing fetch query: " . $conn->error);
        }
        $stmtFetch->bind_param('i', $acad_year_id);
        $stmtFetch->execute();
        $resultFetch = $stmtFetch->get_result();

        $evaluation_ids = [];
        while ($row = $resultFetch->fetch_assoc()) {
            $evaluation_ids[] = $row['evaluation_id'];
        }
        $stmtFetch->close();

        if (!empty($evaluation_ids)) {
            // Convert evaluation_ids array to a comma-separated string for SQL IN clause
            $eval_ids_str = implode(',', array_map('intval', $evaluation_ids));

            // Step 2: Delete from evaluation_answers
            $deleteEvalAnswersQuery = "DELETE FROM evaluation_answers WHERE evaluation_id IN ($eval_ids_str)";
            if (!$conn->query($deleteEvalAnswersQuery)) {
                throw new Exception("Error deleting from evaluation_answers: " . $conn->error);
            }

            // Step 3: Delete from subject_to_eval
            $deleteSubjectToEvalQuery = "DELETE FROM subject_to_eval WHERE evaluation_id IN ($eval_ids_str)";
            if (!$conn->query($deleteSubjectToEvalQuery)) {
                throw new Exception("Error deleting from subject_to_eval: " . $conn->error);
            }

            // Step 4: Delete from evaluation_sentiments
            $deleteEvalSentimentsQuery = "DELETE FROM evaluation_sentiments WHERE evaluation_id IN ($eval_ids_str)";
            if (!$conn->query($deleteEvalSentimentsQuery)) {
                throw new Exception("Error deleting from evaluation_sentiments: " . $conn->error);
            }

            // Step 5: Delete from students_eval_restriction
            $deleteStudentsEvalRestrictionQuery = "DELETE FROM students_eval_restriction WHERE evaluation_id IN ($eval_ids_str)";
            if (!$conn->query($deleteStudentsEvalRestrictionQuery)) {
                throw new Exception("Error deleting from students_eval_restriction: " . $conn->error);
            }

            // Step 6: Delete from evaluation_list
            $deleteEvaluationListQuery = "DELETE FROM evaluation_list WHERE evaluation_id IN ($eval_ids_str)";
            if (!$conn->query($deleteEvaluationListQuery)) {
                throw new Exception("Error deleting from evaluation_list: " . $conn->error);
            }
        }

        // Step 7: Delete from academic_year
        $deleteAcademicYearQuery = "DELETE FROM academic_year WHERE acad_year_id = ?";
        $stmtDeleteAcad = $conn->prepare($deleteAcademicYearQuery);
        if (!$stmtDeleteAcad) {
            throw new Exception("Error preparing delete query: " . $conn->error);
        }
        $stmtDeleteAcad->bind_param('i', $acad_year_id);
        $stmtDeleteAcad->execute();

        if ($stmtDeleteAcad->affected_rows > 0) {
            // Commit the transaction
            $conn->commit();

            echo json_encode([
                'status' => 'Success',
                'message' => 'Academic year and all related records deleted successfully.'
            ]);
        } else {
            throw new Exception("No academic year found with the provided ID.");
        }

        $stmtDeleteAcad->close();
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();

        echo json_encode([
            'status' => 'Error',
            'message' => $e->getMessage()
        ]);
    }

    // Close the connection
    $conn->close();
} else {
    // If no ID is set, show an error message
    echo json_encode([
        'status' => 'Error',
        'message' => 'Invalid request. Academic Year ID is missing.'
    ]);
    exit();
}
?>
