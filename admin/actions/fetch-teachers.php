<?php
include('../../db_conn.php'); // Include database connection

if (isset($_POST['academicYearQuarter'])) {
    // Parse the selected year and quarter
    $selected = $_POST['academicYearQuarter'];
    list($year, $quarter) = explode('-Q', $selected); // Split the value by 'Q'

    // Query to get the academic year ID based on year and quarter
    $yearQuery = "SELECT acad_year_id FROM academic_year WHERE year = '$year' AND quarter = '$quarter'";
    $yearResult = mysqli_query($conn, $yearQuery);
    
    if ($yearResult && mysqli_num_rows($yearResult) > 0) {
        $row = mysqli_fetch_assoc($yearResult);
        $acad_year_id = $row['acad_year_id'];

        // Query to get evaluation IDs from evaluation_list where academic year matches
        $evaluationQuery = "SELECT evaluation_id FROM evaluation_list WHERE acad_year_id = '$acad_year_id'";
        $evaluationResult = mysqli_query($conn, $evaluationQuery);
        
        if ($evaluationResult && mysqli_num_rows($evaluationResult) > 0) {
            // Create an array to hold evaluation IDs
            $evaluationIds = [];
            while ($evaluationRow = mysqli_fetch_assoc($evaluationResult)) {
                $evaluationIds[] = $evaluationRow['evaluation_id'];
            }

            // Convert array of evaluation_ids to a comma-separated string with quotes
            $evaluationIdsStr = implode("','", $evaluationIds);

            // Wrap the string in single quotes for SQL query
            $evaluationIdsStr = "'" . $evaluationIdsStr . "'";

            // Query to get the distinct teachers from the subject_to_eval table based on evaluation IDs
            $teacherQuery = "
                SELECT DISTINCT t.teacher_id, t.firstName, t.lastName
                FROM subject_to_eval se
                JOIN teacher_account t ON se.teacher_id = t.teacher_id
                WHERE se.evaluation_id IN ($evaluationIdsStr)
            ";
            
            $teacherResult = mysqli_query($conn, $teacherQuery);
            
            if ($teacherResult && mysqli_num_rows($teacherResult) > 0) {
                // Output options for the select element
                while ($teacherRow = mysqli_fetch_assoc($teacherResult)) {
                    echo '<option value="' . $teacherRow['teacher_id'] . '">' . $teacherRow['firstName'] . ' ' . $teacherRow['lastName'] . '</option>';
                }
            } else {
                echo '<option value="">No teachers found for the selected year and quarter</option>';
            }
        } else {
            echo '<option value="">No teachers found for the selected year and quarter</option>';
        }
    } else {
        echo '<option value="">Invalid Academic Year</option>';
    }
}
?>
