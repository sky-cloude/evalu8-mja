<?php
session_start();
include '../../db_conn.php';

if (isset($_GET['id'])) {
    $classId = $_GET['id'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Step 1: Retrieve all evaluation_ids linked to this class
        $stmtEval = $conn->prepare("SELECT evaluation_id FROM evaluation_list WHERE class_id = ?");
        $stmtEval->bind_param("i", $classId);
        if (!$stmtEval->execute()) {
            throw new Exception("Failed to retrieve evaluations from evaluation_list.");
        }
        $resultEval = $stmtEval->get_result();
        $evaluationIds = [];
        while ($row = $resultEval->fetch_assoc()) {
            $evaluationIds[] = $row['evaluation_id'];
        }
        $stmtEval->close();

        // Step 2: For each evaluation_id, delete related records
        if (!empty($evaluationIds)) {
            // Prepare statements for deletion
            $stmtSubjectToEval = $conn->prepare("DELETE FROM subject_to_eval WHERE evaluation_id = ?");
            $stmtStudentsEvalRestriction = $conn->prepare("DELETE FROM students_eval_restriction WHERE evaluation_id = ?");
            $stmtEvaluationSentiments = $conn->prepare("DELETE FROM evaluation_sentiments WHERE evaluation_id = ?");
            $stmtEvaluationAnswers = $conn->prepare("DELETE FROM evaluation_answers WHERE evaluation_id = ?");

            foreach ($evaluationIds as $evaluationId) {
                // Delete from subject_to_eval
                $stmtSubjectToEval->bind_param("i", $evaluationId);
                if (!$stmtSubjectToEval->execute()) {
                    throw new Exception("Failed to delete from subject_to_eval for evaluation_id: $evaluationId.");
                }

                // Delete from students_eval_restriction
                $stmtStudentsEvalRestriction->bind_param("i", $evaluationId);
                if (!$stmtStudentsEvalRestriction->execute()) {
                    throw new Exception("Failed to delete from students_eval_restriction for evaluation_id: $evaluationId.");
                }

                // Delete from evaluation_sentiments
                $stmtEvaluationSentiments->bind_param("i", $evaluationId);
                if (!$stmtEvaluationSentiments->execute()) {
                    throw new Exception("Failed to delete from evaluation_sentiments for evaluation_id: $evaluationId.");
                }

                // Delete from evaluation_answers
                $stmtEvaluationAnswers->bind_param("i", $evaluationId);
                if (!$stmtEvaluationAnswers->execute()) {
                    throw new Exception("Failed to delete from evaluation_answers for evaluation_id: $evaluationId.");
                }
            }

            // Close prepared statements
            $stmtSubjectToEval->close();
            $stmtStudentsEvalRestriction->close();
            $stmtEvaluationSentiments->close();
            $stmtEvaluationAnswers->close();
        }

        // Step 3: Delete from evaluation_list
        $stmtDeleteEvalList = $conn->prepare("DELETE FROM evaluation_list WHERE class_id = ?");
        $stmtDeleteEvalList->bind_param("i", $classId);
        if (!$stmtDeleteEvalList->execute()) {
            throw new Exception("Failed to delete from evaluation_list.");
        }
        $stmtDeleteEvalList->close();

        // Step 4: Finally, delete from class_list
        $stmtDeleteClass = $conn->prepare("DELETE FROM class_list WHERE class_id = ?");
        $stmtDeleteClass->bind_param("i", $classId);
        if (!$stmtDeleteClass->execute()) {
            throw new Exception("Failed to delete from class_list.");
        }
        $stmtDeleteClass->close();

        // Commit transaction
        $conn->commit();

        $_SESSION['message'] = "Class and all related records deleted successfully.";
        $_SESSION['message_type'] = "success";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['message'] = "Failed to delete class. " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }

    $conn->close();
    header("Location: ../admin-class.php");
    exit();
} else {
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../admin-class.php");
    exit();
}
?>
