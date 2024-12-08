<?php
session_start();
include('../../db_conn.php');

// Ensure the teacher is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    die('Access Denied: Teacher not logged in.');
}

// Get the logged-in teacher ID
$teacher_id = $_SESSION['user_id']; // Assuming the teacher's ID is stored in the session

if (isset($_POST['acad_year_id'])) {
    $acad_year_id = $_POST['acad_year_id'];

    // Prepare the SQL statement to prevent SQL injection
    $sql = "SELECT sub.subject_title, cl.section, cl.grade_level, sub.subject_id
            FROM subject_to_eval s
            JOIN evaluation_list e ON s.evaluation_id = e.evaluation_id
            JOIN class_list cl ON e.class_id = cl.class_id
            JOIN subject_list sub ON s.subject_id = sub.subject_id
            WHERE e.acad_year_id = ? AND s.teacher_id = ?
            ORDER BY sub.subject_title ASC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $acad_year_id, $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Output the subjects and classes in the sidebar
            while ($row = $result->fetch_assoc()) {
                echo '<button class="list-group-item" 
                            data-subject-id="' . htmlspecialchars($row['subject_id'], ENT_QUOTES, 'UTF-8') . '" 
                            style="border: 1px solid black; margin-bottom: -1px;">'
                         . htmlspecialchars($row['subject_title'], ENT_QUOTES, 'UTF-8') . ' - Grade ' 
                         . htmlspecialchars($row['grade_level'], ENT_QUOTES, 'UTF-8') . ' ' 
                         . htmlspecialchars($row['section'], ENT_QUOTES, 'UTF-8') . 
                         '</button>';
            }
        } else {
            echo '<p class="text-center">No subjects or classes available for the selected academic year.</p>';
        }

        $stmt->close();
    } else {
        // Handle SQL preparation error
        echo '<p class="text-center">Failed to prepare the query. Please try again later.</p>';
    }

    $conn->close();
}
?>
