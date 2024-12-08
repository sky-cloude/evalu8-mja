<?php
include('../../db_conn.php'); // Include your database connection

if (isset($_POST['teacher_id']) && isset($_POST['academicYearQuarter'])) {
    $teacher_id = $_POST['teacher_id'];
    $selected = $_POST['academicYearQuarter'];
    list($year, $quarter) = explode('-Q', $selected);

    // Get the academic year ID based on year and quarter
    $yearQuery = "SELECT acad_year_id FROM academic_year WHERE year = '$year' AND quarter = '$quarter'";
    $yearResult = mysqli_query($conn, $yearQuery);

    if ($yearResult && mysqli_num_rows($yearResult) > 0) {
        $yearRow = mysqli_fetch_assoc($yearResult);
        $acad_year_id = $yearRow['acad_year_id'];

        // Get evaluation IDs from evaluation_list for the selected academic year
        $evalQuery = "SELECT evaluation_id, class_id FROM evaluation_list WHERE acad_year_id = '$acad_year_id'";
        $evalResult = mysqli_query($conn, $evalQuery);
        $classes = [];

        if ($evalResult && mysqli_num_rows($evalResult) > 0) {
            // Loop through evaluations to get class IDs
            while ($evalRow = mysqli_fetch_assoc($evalResult)) {
                $evaluation_id = $evalRow['evaluation_id'];
                $class_id = $evalRow['class_id'];

                // Query to get the subjects the teacher handles in that class
                $subjectQuery = "SELECT s.subject_title, c.grade_level, c.section, se.subject_id
                                FROM subject_to_eval se 
                                JOIN subject_list s ON se.subject_id = s.subject_id 
                                JOIN class_list c ON c.class_id = '$class_id'
                                WHERE se.evaluation_id = '$evaluation_id' AND se.teacher_id = '$teacher_id'";

                $subjectResult = mysqli_query($conn, $subjectQuery);

                if ($subjectResult && mysqli_num_rows($subjectResult) > 0) {
                    // Loop through the subjects and build the class/subject data
                    while ($subjectRow = mysqli_fetch_assoc($subjectResult)) {
                        $className = 'Grade ' . $subjectRow['grade_level'] . ' - ' . $subjectRow['section'];
                        $subjectName = $subjectRow['subject_title'];
                        $subject_id = $subjectRow['subject_id'];
                        $classes[] = [
                            'class' => $className . ' - ' . $subjectName,
                            'subject_id' => $subject_id,
                            'evaluation_id' => $evaluation_id // Include evaluation_id
                        ];
                        
                    }
                }
            }

            // Output the classes and subjects as list items
            if (!empty($classes)) {
                foreach ($classes as $class) {
                    echo '<a href="#" class="list-group-item list-group-item-action" data-subject-id="' . $class['subject_id'] . '" data-evaluation-id="' . $class['evaluation_id'] . '">' . $class['class'] . '</a>';
                }
            } else {
                echo '<a href="#" class="list-group-item list-group-item-action">No classes found</a>';
            }
        
        } else {
            echo '<a href="#" class="list-group-item list-group-item-action">No evaluations found for the selected year</a>';
        }
    } else {
        echo '<a href="#" class="list-group-item list-group-item-action">Invalid academic year</a>';
    }
} else {
    echo '<a href="#" class="list-group-item list-group-item-action">Missing parameters</a>';
}
?>
