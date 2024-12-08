<?php
session_start();
include '../../db_conn.php';

if (isset($_GET['id'])) {
    $subjectId = $_GET['id'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete from subject_to_eval
        $stmt1 = $conn->prepare("DELETE FROM subject_to_eval WHERE subject_id = ?");
        $stmt1->bind_param("i", $subjectId);
        if (!$stmt1->execute()) {
            throw new Exception("Failed to delete from subject_to_eval.");
        }
        $stmt1->close();

        // Delete from evaluation_sentiments
        $stmt2 = $conn->prepare("DELETE FROM evaluation_sentiments WHERE subject_id = ?");
        $stmt2->bind_param("i", $subjectId);
        if (!$stmt2->execute()) {
            throw new Exception("Failed to delete from evaluation_sentiments.");
        }
        $stmt2->close();

        // Delete from evaluation_answers
        $stmt3 = $conn->prepare("DELETE FROM evaluation_answers WHERE subject_id = ?");
        $stmt3->bind_param("i", $subjectId);
        if (!$stmt3->execute()) {
            throw new Exception("Failed to delete from evaluation_answers.");
        }
        $stmt3->close();

        // Finally, delete from subject_list
        $stmt4 = $conn->prepare("DELETE FROM subject_list WHERE subject_id = ?");
        $stmt4->bind_param("i", $subjectId);
        if (!$stmt4->execute()) {
            throw new Exception("Failed to delete from subject_list.");
        }
        $stmt4->close();

        // Commit transaction
        $conn->commit();

        $_SESSION['message'] = "Subject and all related records deleted successfully.";
        $_SESSION['message_type'] = "success";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['message'] = "Failed to delete subject. " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
    }

    $conn->close();
    header("Location: ../admin-subjects.php");
    exit();
} else {
    $_SESSION['message'] = "Invalid request.";
    $_SESSION['message_type'] = "danger";
    header("Location: ../admin-subjects.php");
    exit();
}
?>
