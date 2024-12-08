<?php
// Database connection
include('../../db_conn.php');

if (isset($_POST['teacher_id'], $_POST['academicYearQuarter'])) {
    $teacher_id = $_POST['teacher_id'];
    $academicYearQuarter = $_POST['academicYearQuarter'];

    // Split the academic year and quarter
    list($year, $quarter) = explode('-Q', $academicYearQuarter);

    // Query to get all subjects handled by the teacher in the selected academic year and quarter
    $query_subjects = "
        SELECT DISTINCT s.subject_id, s.subject_title, el.evaluation_id
        FROM evaluation_answers ea
        JOIN subject_list s ON ea.subject_id = s.subject_id
        JOIN evaluation_list el ON ea.evaluation_id = el.evaluation_id
        JOIN academic_year ay ON el.acad_year_id = ay.acad_year_id
        WHERE ea.teacher_id = '$teacher_id'
        AND ay.year = '$year'
        AND ay.quarter = '$quarter'
    ";

    $result_subjects = mysqli_query($conn, $query_subjects);

    // Loop through all the subjects and fetch their respective reports
    while ($subject = mysqli_fetch_assoc($result_subjects)) {
        $subject_id = $subject['subject_id'];
        $evaluation_id = $subject['evaluation_id'];

        // Query to fetch report details for each subject (similar to fetch-evaluation-report.php)
        $query_info = "
            SELECT t.firstName, t.lastName, s.subject_title, ay.year, ay.quarter, c.grade_level, c.section
            FROM evaluation_answers ea
            JOIN evaluation_list el ON ea.evaluation_id = el.evaluation_id
            JOIN academic_year ay ON el.acad_year_id = ay.acad_year_id
            JOIN subject_list s ON ea.subject_id = s.subject_id
            JOIN class_list c ON el.class_id = c.class_id
            JOIN teacher_account t ON ea.teacher_id = t.teacher_id
            WHERE ea.teacher_id = '$teacher_id'
            AND ea.subject_id = '$subject_id'
            AND ea.evaluation_id = '$evaluation_id'
            LIMIT 1
        ";

        $result_info = mysqli_query($conn, $query_info);
        $info = mysqli_fetch_assoc($result_info);

        if ($info) {
            // Output the report for this subject (same as fetch-evaluation-report.php)
            // ... Same logic for displaying the report goes here ...

            echo "
            <div style='text-align: center; margin-bottom: 20px;'>
                <div style='display: flex; justify-content: center; align-items: center;'>
                    <img src='../Logo/mja-logo.png' alt='MJA Logo' style='height: 50px; margin-right: 10px;'>
                    <h2 style='margin: 0; font-weight:bold;'>Mary Josette Academy</h2>
                </div>
            </div>

            <h4 class='report-title text-center'>Faculty Evaluation Report</h4>
            <table class='table info-table mb-1' style='border-spacing: 0;'>
                <tbody>
                    <tr>
                        <td style='border: none; padding: 0;'><strong>Faculty:</strong> {$info['firstName']} {$info['lastName']}</td>
                        <td class='text-end' style='border: none; padding: 0;'><strong>Academic Year:</strong> {$info['year']} - Quarter {$info['quarter']}</td>
                    </tr>
                    <tr>
                        <td style='border: none; padding: 0;'><strong>Class:</strong> Grade {$info['grade_level']} - {$info['section']}</td>
                        <td class='text-end' style='border: none; padding: 0;'><strong>Course:</strong> {$info['subject_title']}</td>
                    </tr>
                </tbody>
            </table>";

            // Query criteria and ratings
            // ... Same logic for criteria, sentiments, etc. goes here ...
        } else {
            // If no data found for this subject, skip this report
            continue;
        }
    }
} else {
    echo "<p>Invalid request.</p>";
}
?>
