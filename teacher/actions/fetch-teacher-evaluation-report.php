<?php
// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure the teacher is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'teacher') {
    die('Access Denied: Teacher not logged in.');
}

include('../../db_conn.php');

// Check if the required POST parameters are set
if (isset($_POST['acad_year_id'], $_POST['subject_id'])) {
    // Get the POST parameters
    $teacher_id = $_SESSION['user_id'];  // The logged-in teacher
    $acad_year_id = $_POST['acad_year_id'];
    $subject_id = $_POST['subject_id'];

    // Escape the inputs for security
    $teacher_id = mysqli_real_escape_string($conn, $teacher_id);
    $acad_year_id = mysqli_real_escape_string($conn, $acad_year_id);
    $subject_id = mysqli_real_escape_string($conn, $subject_id);

    // Retrieve the evaluation_id based on acad_year_id, teacher_id, and subject_id
    $query_evaluation = "
    SELECT DISTINCT ea.evaluation_id
    FROM evaluation_answers ea
    JOIN evaluation_list el ON ea.evaluation_id = el.evaluation_id
    WHERE el.acad_year_id = '$acad_year_id'
    AND ea.teacher_id = '$teacher_id'
    AND ea.subject_id = '$subject_id'
    LIMIT 1;
    ";

    $result_evaluation = mysqli_query($conn, $query_evaluation);
    if (!$result_evaluation) {
        die('Query Failed: ' . mysqli_error($conn));
    }
    $evaluation = mysqli_fetch_assoc($result_evaluation);
    if ($evaluation) {
        $evaluation_id = $evaluation['evaluation_id'];
    } else {
        die('<p class="text-center"><i>No data found for the selected subject.</i></p>');
    }

    // Now proceed with the rest of the code using $evaluation_id

    // Query to fetch basic information (faculty, class, academic year, course)
    $query_info = "
    SELECT t.firstName, t.lastName, s.subject_title, ay.year, ay.quarter, c.grade_level, c.section
    FROM evaluation_answers ea
    JOIN evaluation_list el ON ea.evaluation_id = el.evaluation_id
    JOIN academic_year ay ON el.acad_year_id = ay.acad_year_id
    JOIN subject_list s ON ea.subject_id = s.subject_id
    JOIN class_list c ON el.class_id = c.class_id
    JOIN teacher_account t ON ea.teacher_id = t.teacher_id
    WHERE ea.teacher_id = '$teacher_id'
    AND el.acad_year_id = '$acad_year_id'
    AND ea.subject_id = '$subject_id'
    AND ea.evaluation_id = '$evaluation_id'
    LIMIT 1;
    ";

    $result_info = mysqli_query($conn, $query_info);

    if (!$result_info) {
        die('Query Failed: ' . mysqli_error($conn));
    }

    $info = mysqli_fetch_assoc($result_info);

    if ($info) {
        // Query to get the total number of distinct students evaluated
        $query_students = "
        SELECT COUNT(DISTINCT ea.student_id) AS total_students
        FROM evaluation_answers ea
        JOIN evaluation_list el ON ea.evaluation_id = el.evaluation_id
        WHERE ea.teacher_id = '$teacher_id'
        AND el.acad_year_id = '$acad_year_id'
        AND ea.subject_id = '$subject_id'
        AND ea.evaluation_id = '$evaluation_id';
        ";

        $result_students = mysqli_query($conn, $query_students);
        if (!$result_students) {
            die('Query Failed: ' . mysqli_error($conn));
        }
        $student_info = mysqli_fetch_assoc($result_students);

        // Query to get average ratings per criteria
        $query_criteria = "
        SELECT c.criteria, ROUND(AVG(ea.rating), 2) AS avg_rating
        FROM evaluation_answers ea
        JOIN criteria_list c ON ea.criteria_id = c.criteria_id
        JOIN evaluation_list el ON ea.evaluation_id = el.evaluation_id
        WHERE ea.teacher_id = '$teacher_id'
        AND el.acad_year_id = '$acad_year_id'
        AND ea.subject_id = '$subject_id'
        AND ea.evaluation_id = '$evaluation_id'
        GROUP BY c.criteria, c.ordered_by
        ORDER BY c.ordered_by;
        ";

        $result_criteria = mysqli_query($conn, $query_criteria);
        if (!$result_criteria) {
            die('Query Failed: ' . mysqli_error($conn));
        }
        $criteria_ratings = [];
        $total_avg = 0;
        $num_criteria = 0;

        while ($row = mysqli_fetch_assoc($result_criteria)) {
            $criteria_ratings[$row['criteria']] = $row['avg_rating'];
            $total_avg += $row['avg_rating'];
            $num_criteria++;
        }

        $overall_rating = ($num_criteria > 0) ? round($total_avg / $num_criteria, 2) : 0;
        $equivalent = '';
        if ($overall_rating >= 4.5) {
            $equivalent = 'Outstanding';
        } elseif ($overall_rating >= 3.5) {
            $equivalent = 'Very Satisfactory';
        } elseif ($overall_rating >= 2.5) {
            $equivalent = 'Satisfactory';
        } elseif ($overall_rating >= 1.5) {
            $equivalent = 'Fair';
        } else {
            $equivalent = 'Needs Improvement';
        }

        // Query to fetch the sentiments and sentiment_type from the database
        $query_sentiments = "
        SELECT es.sentiment, es.sentiment_type
        FROM evaluation_sentiments es
        WHERE es.teacher_id = '$teacher_id'
        AND es.subject_id = '$subject_id'
        AND es.evaluation_id = '$evaluation_id';
        ";

        $result_sentiments = mysqli_query($conn, $query_sentiments);
        if (!$result_sentiments) {
            die('Query Failed: ' . mysqli_error($conn));
        }
        $sentiments = [];

        // Classify the sentiment based on the sentiment_type
        while ($row = mysqli_fetch_assoc($result_sentiments)) {
            $sentiment_text = htmlspecialchars($row['sentiment'], ENT_QUOTES, 'UTF-8');
            $sentiment_type = intval($row['sentiment_type']);

            // Set the class and label based on the sentiment_type value (0 = negative, 1 = neutral, 2 = positive)
            $class = '';
            $label = '';
            if ($sentiment_type === 2) {
                $class = 'positive'; // Green for positive
                $label = '(Positive)';
            } elseif ($sentiment_type === 1) {
                $class = 'neutral';  // Black for neutral
                $label = '(Neutral)';
            } elseif ($sentiment_type === 0) {
                $class = 'negative'; // Red for negative
                $label = '(Negative)';
            }

            // Store the sentiment with its classification and label
            $sentiments[] = [
                'text' => $sentiment_text,
                'class' => $class,
                'label' => $label
            ];
        }

        // Generate report HTML
        // Generate report HTML
echo "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
        }
        h3 {
            font-size: 1.75rem;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .text-center {
            text-align: center;
        }
        .report-table th, .report-table td {
            border: 1px solid black;
            padding: 10px;
            text-align: center;
        }
        .positive {
            color: green;
        }
        .negative {
            color: red;
        }
        .neutral {
            color: black;
        }
        /* Set page size and margins for printing on short bond paper */
        @page {
            size: 8in 11in;
            margin: 0.5in; /* Adjust margins as needed */
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .report-container {
                page-break-inside: avoid; /* Avoid page break inside the container */
                margin-bottom: 0; /* Remove bottom margin */
            }
            .report-table, .report-table th, .report-table td {
                border: 1px solid black !important; /* Ensure borders are applied during printing */
                border-collapse: collapse; /* Make sure borders don't overlap */
            }
        }

        /* Legend Box Styles */
        .legend-box {
            width: 85%; /* Slightly narrower than full width */
            border: 1px solid black;
            padding: 10px;
            margin-top: 20px;
            margin-left: auto;
            margin-right: auto;
        }
        .legend-title {
            font-weight: bold;
            margin-bottom: 10px;
            text-align: left;
        }
        .legend-items {
            display: flex;
            justify-content: space-between;
            text-align: center;
        }
        .legend-items div {
            width: 30%;
        }
        .legend-items span {
            display: inline-block;
            width: 30px; /* Increased width of color box */
            height: 10px;
            border-radius: 2px;
        }
        .positive-box {
            background-color: green;
        }
        .neutral-box {
            background-color: black;
        }
        .negative-box {
            background-color: red;
        }
        .legend-items span + span {
            margin-left: 10px; /* Space between color box and label */
        }
        .legend-items div span {
            font-weight: bold;
        }
    </style>
</head>
<body>
<div style='text-align: center; margin-bottom: 20px;'>
    <div style='display: flex; justify-content: center; align-items: center;'>
        <img src='../Logo/mja-logo.png' alt='MJA Logo' style='height: 50px; margin-right: 10px;'>
        <h2 style='margin: 0; font-weight:bold;'>Mary Josette Academy</h2>
    </div>
</div>
<p class='report-title' style='font-size:18px; font-weight:bold; text-align: center; margin-bottom: 20px;'>Faculty Evaluation Report</p>

<div class='table-responsive'>
    <table class='table info-table mb-1' style='margin-bottom: 20px;'>
        <tbody>
            <tr>
                <td><strong>Faculty:</strong> {$info['firstName']} {$info['lastName']}</td>
                <td style='text-align: right;'><strong>Academic Year:</strong> {$info['year']} - Quarter {$info['quarter']}</td>
            </tr>
            <tr>
                <td><strong>Class:</strong> Grade {$info['grade_level']} - {$info['section']}</td>
                <td style='text-align: right;'><strong>Course:</strong> {$info['subject_title']}</td>
            </tr>
            <tr>
                <td colspan='2' style='padding-bottom: 20px;'><strong>Total Number of Students Evaluated:</strong> {$student_info['total_students']}</td>
            </tr>
        </tbody>
    </table>
</div>

<div class='table-responsive'>
    <table class='table report-table'>
        <thead>
            <tr class='text-center'>
";

foreach ($criteria_ratings as $criteria => $rating) {
    echo "<th class='align-middle text-center'>" . strtoupper($criteria) . "</th>";
}

echo "
    <th>OVERALL RATING</th>
    <th>EQUIVALENT</th>
    </tr>
    </thead>
    <tbody>
        <tr class='text-center'>";

foreach ($criteria_ratings as $rating) {
    echo "<td>{$rating}</td>";
}

echo "
    <td>{$overall_rating}</td>
    <td>{$equivalent}</td>
    </tr>
    </tbody>
    </table>
</div>

<div style='margin-top: 20px;'>
    <p><strong>Student Sentiments:</strong></p>
    
    <!-- Legend Box -->
    <div class='legend-box mb-4'>
        <div class='legend-title'>Legend:</div>
        <div class='legend-items'>
            <div>
                <span class='positive-box'></span>
                <span style='color: green;'>Positive</span>
            </div>
            <div>
                <span class='neutral-box'></span>
                <span style='color: black;'>Neutral</span>
            </div>
            <div>
                <span class='negative-box'></span>
                <span style='color: red;'>Negative</span>
            </div>
        </div>
    </div>


    <ul style='list-style-type: disc; padding-left: 20px;'>
";

if (empty($sentiments)) {
    echo "<li><i>No sentiments posted.</i></li>";
} else {
    foreach ($sentiments as $sentiment) {
        // Apply color and label based on sentiment type
        $class = $sentiment['class'];
        $text = $sentiment['text'];

        echo "<li class='{$class}'>{$text}</li>";
    }
}

echo "</ul></div></body></html>";


    } else {
        echo "<p class='text-center'><i>No data found for the selected subject.</i></p>";
    }
} else {
    echo "<p>Invalid request.</p>";
}
?>
