<?php
// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection
include('../../db_conn.php'); // Adjust the path if necessary

// Function to perform sentiment analysis using the Flask API
function analyzeSentiment($student_sentiments) {
    // Flask API endpoint
    $api_url = 'http://localhost:5000/analyze_batch';

    // Prepare the data to send to the Flask API
    $data = json_encode(array('texts' => $student_sentiments));

    // Initialize cURL session
    $ch = curl_init($api_url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // Return the response as a string
    curl_setopt($ch, CURLOPT_POST, true);            // Use POST method
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);     // Attach the data
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));  // Set the content type to JSON

    // Execute the cURL request and get the response
    $response = curl_exec($ch);

    // Check for errors
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        die('cURL Error: ' . $error);
    }

    // Close the cURL session
    curl_close($ch);

    // Decode the JSON response from the Flask API
    $responseData = json_decode($response, true);

    // Check if the response is valid JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        die('Invalid JSON response from Flask API: ' . json_last_error_msg());
    }

    // Check if the 'results' key exists in the response
    if (!isset($responseData['results']) || !is_array($responseData['results'])) {
        die('The response from the Flask API does not contain the expected keys.');
    }

    // Return the analyzed sentiments
    return $responseData['results'];
}

// Check if the required POST parameters are set
if (isset($_POST['faculty_id'], $_POST['year_quarter'])) {
    // Get the POST parameters
    $faculty_id = $_POST['faculty_id'];
    $year_quarter = $_POST['year_quarter'];

    // Split the academic year and quarter
    list($year, $quarter) = explode('-Q', $year_quarter);

    // Escape user inputs for security
    $faculty_id = mysqli_real_escape_string($conn, $faculty_id);
    $year = mysqli_real_escape_string($conn, $year);
    $quarter = mysqli_real_escape_string($conn, $quarter);

    // Query to get the acad_year_id
    $query_acad_year = "SELECT acad_year_id FROM academic_year WHERE year = '$year' AND quarter = '$quarter'";
    $result_acad_year = mysqli_query($conn, $query_acad_year);

    if ($result_acad_year && mysqli_num_rows($result_acad_year) > 0) {
        $row_acad_year = mysqli_fetch_assoc($result_acad_year);
        $acad_year_id = $row_acad_year['acad_year_id'];

        // Get the evaluation_ids for this acad_year_id
        $query_evaluation_ids = "SELECT evaluation_id FROM evaluation_list WHERE acad_year_id = '$acad_year_id'";
        $result_evaluation_ids = mysqli_query($conn, $query_evaluation_ids);

        if ($result_evaluation_ids && mysqli_num_rows($result_evaluation_ids) > 0) {
            $evaluation_ids = [];
            while ($row_evaluation = mysqli_fetch_assoc($result_evaluation_ids)) {
                $evaluation_ids[] = $row_evaluation['evaluation_id'];
            }

            // Now, for each evaluation_id, get the subjects the teacher handles
            $evaluation_ids_str = "'" . implode("','", $evaluation_ids) . "'";

            $query_subjects = "
                SELECT DISTINCT ea.subject_id, ea.evaluation_id
                FROM evaluation_answers ea
                WHERE ea.teacher_id = '$faculty_id'
                AND ea.evaluation_id IN ($evaluation_ids_str)
            ";

            $result_subjects = mysqli_query($conn, $query_subjects);

            if ($result_subjects && mysqli_num_rows($result_subjects) > 0) {
                $reports = [];

                while ($row_subject = mysqli_fetch_assoc($result_subjects)) {
                    $subject_id = $row_subject['subject_id'];
                    $evaluation_id = $row_subject['evaluation_id'];

                    // Query to fetch basic information (faculty name, class, academic year, course)
                    $query_info = "
                    SELECT t.firstName, t.lastName, s.subject_title, ay.year, ay.quarter, c.grade_level, c.section
                    FROM evaluation_answers ea
                    JOIN evaluation_list el ON ea.evaluation_id = el.evaluation_id
                    JOIN academic_year ay ON el.acad_year_id = ay.acad_year_id
                    JOIN subject_list s ON ea.subject_id = s.subject_id
                    JOIN class_list c ON el.class_id = c.class_id
                    JOIN teacher_account t ON ea.teacher_id = t.teacher_id
                    WHERE ea.teacher_id = '$faculty_id'
                    AND ea.subject_id = '$subject_id'
                    AND ea.evaluation_id = '$evaluation_id'
                    LIMIT 1
                    ";

                    // Run the query
                    $result_info = mysqli_query($conn, $query_info);

                    if (!$result_info) {
                        die('Query Failed: ' . mysqli_error($conn));
                    }
                    
                    // Fetch the result as an associative array
                    $info = mysqli_fetch_assoc($result_info);

                    if ($info) {
                        // Query to get the total number of distinct students evaluated
                        $query_students = "
                        SELECT COUNT(DISTINCT ea.student_id) AS total_students
                        FROM evaluation_answers ea
                        JOIN evaluation_list el ON ea.evaluation_id = el.evaluation_id
                        WHERE ea.teacher_id = '$faculty_id'
                        AND ea.subject_id = '$subject_id'
                        AND ea.evaluation_id = '$evaluation_id'
                        ";
                        $result_students = mysqli_query($conn, $query_students);
                        $student_info = mysqli_fetch_assoc($result_students);

                        // Query to get average ratings per criteria
                        $query_criteria = "
                        SELECT c.criteria, ROUND(AVG(ea.rating), 2) AS avg_rating
                        FROM evaluation_answers ea
                        JOIN criteria_list c ON ea.criteria_id = c.criteria_id
                        WHERE ea.teacher_id = '$faculty_id'
                        AND ea.subject_id = '$subject_id'
                        AND ea.evaluation_id = '$evaluation_id'
                        GROUP BY c.criteria, c.ordered_by
                        ORDER BY c.ordered_by
                        ";
                        $result_criteria = mysqli_query($conn, $query_criteria);

                        $criteria_ratings = [];
                        $total_avg = 0;
                        $num_criteria = 0;

                        // Fetch criteria ratings
                        while ($row = mysqli_fetch_assoc($result_criteria)) {
                            $criteria_ratings[$row['criteria']] = $row['avg_rating'];
                            $total_avg += $row['avg_rating'];
                            $num_criteria++;
                        }

                        // Only proceed if there is data
                        if ($num_criteria > 0 && $student_info['total_students'] > 0) {
                            // Calculate the overall rating
                            $overall_rating = ($num_criteria > 0) ? round($total_avg / $num_criteria, 2) : 0;

                            // Determine the equivalent rating
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

                            // Query to fetch the sentiments from students
                            $query_sentiments = "
                                SELECT es.sentiment
                                FROM evaluation_sentiments es
                                WHERE es.teacher_id = '$faculty_id'
                                AND es.subject_id = '$subject_id'
                                AND es.evaluation_id = '$evaluation_id'
                            ";
                            $result_sentiments = mysqli_query($conn, $query_sentiments);

                            if (!$result_sentiments) {
                                die('Query Failed: ' . mysqli_error($conn));
                            }

                            $sentiments = [];
                            while ($row = mysqli_fetch_assoc($result_sentiments)) {
                                $sentiments[] = $row['sentiment'];
                            }

                            // Perform sentiment analysis if there are sentiments
                            if (!empty($sentiments)) {
                                $analyzed_sentiments = analyzeSentiment($sentiments);
                            } else {
                                $analyzed_sentiments = [];
                            }

                            // Now output the HTML structure for the report
                            // Append to $reports
                            $report_html = "
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
                                        .report-title {
                                            text-align: center;
                                            font-size: 18px;
                                            font-weight: bold;
                                        }
                                        .info-table td {
                                            padding: 5px;
                                        }
                                        .info-table .align-right {
                                            text-align: right;
                                        }
                                        .legend-container {
                                            margin-top: 10px;
                                            padding: 10px;
                                            border: 1px solid #000;
                                            background-color: #f9f9f9;
                                            width: 500px;  /* Increase the width */
                                            text-align: center;
                                            display: block;
                                            margin-left: auto;
                                            margin-right: auto;
                                        }
                                        .legend-title {
                                            font-weight: bold;
                                            margin-bottom: 10px;
                                            text-align: left;
                                        }
                                        .legend-description {
                                            font-size: 14px;
                                            display: flex;
                                            justify-content: space-evenly;
                                            font-weight: bold;
                                        }
                                        .sentiment-item {
                                            list-style-type: disc;
                                            margin-left: 20px;
                                        }
                                        .sentiment-item.positive {
                                            color: green;
                                        }
                                        .sentiment-item.negative {
                                            color: red;
                                        }
                                        .sentiment-item.neutral {
                                            color: black;
                                        }
                                        @media print {
                                            body {
                                                margin: 0;
                                                padding: 0;
                                            }
                                            .report-container {
                                                page-break-inside: avoid;
                                                margin-bottom: 0;
                                            }
                                            .report-table, .report-table th, .report-table td {
                                                border: 1px solid black !important;
                                                border-collapse: collapse;
                                            }
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
                                    <p class='report-title'>Faculty Evaluation Report</p>

                                    <table class='table info-table mb-1'>
                                        <tbody>
                                            <tr>
                                                <td><strong>Faculty:</strong> {$info['firstName']} {$info['lastName']}</td>
                                                <td class='align-right'><strong>Academic Year:</strong> {$info['year']} - Quarter {$info['quarter']}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Class:</strong> Grade {$info['grade_level']} - {$info['section']}</td>
                                                <td class='align-right'><strong>Course:</strong> {$info['subject_title']}</td>
                                            </tr>
                                            <tr>
                                                <td colspan='2' style=\"padding-bottom: 20px;\"><strong>Total Number of Students Evaluated:</strong> {$student_info['total_students']}</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <table class='table report-table'>
                                        <thead>
                                            <tr class='text-center'>
                                                ";
                                                // Dynamically generate criteria headers
                                                foreach ($criteria_ratings as $criteria => $rating) {
                                                    $report_html .= "<th>" . strtoupper($criteria) . "</th>";
                                                }

                                                $report_html .= "
                                                <th>OVERALL RATING</th>
                                                <th>EQUIVALENT</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>";

                                            // Display criteria ratings
                                            foreach ($criteria_ratings as $rating) {
                                                $report_html .= "<td>{$rating}</td>";
                                            }

                                            // Display overall rating and equivalent
                                            $report_html .= "<td>{$overall_rating}</td>
                                                <td>{$equivalent}</td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <!-- Student Sentiments Section -->
                                    <div>
                                        <p><strong>Student Sentiments:</strong></p>
                                            <!-- The Legend Box for Sentiments -->
                                            <div class='legend-container'>
                                                <div class='legend-title'>Legend:</div>
                                                <div class='legend-description'>
                                                    <span class='positive'>Positive</span>
                                                    <span class='neutral'>Neutral</span>
                                                    <span class='negative'>Negative</span>
                                                </div>
                                            </div>
                                        <ul>";

                                        if (empty($analyzed_sentiments)) {
                                            $report_html .= "<li><i>No sentiments posted.</i></li>";
                                        } else {
                                            foreach ($analyzed_sentiments as $sentiment) {
                                                $label = $sentiment['label'];
                                                $text = htmlspecialchars($sentiment['text'], ENT_QUOTES, 'UTF-8');

                                                // Determine the CSS class based on the sentiment label
                                                $class = '';
                                                if ($label === 'Positive') {
                                                    $class = 'positive';
                                                } elseif ($label === 'Negative') {
                                                    $class = 'negative';
                                                } else {
                                                    $class = 'neutral';
                                                }

                                                // Output the sentiment with appropriate color
                                                $report_html .= "<li class='sentiment-item {$class}'>{$text}</li>";
                                            }
                                        }

                                        $report_html .= "</ul>
                                    </div>

                                </body>
                                </html>";

                            // Append to reports
                            $reports[] = $report_html;
                        }
                    }
                }

                if (!empty($reports)) {
                    $all_reports = implode('<div style="page-break-before: always;"></div>', $reports);
                    // Output all reports
                    echo $all_reports;
                } else {
                    echo "<p>No evaluation data found for the selected faculty and academic year-quarter.</p>";
                }

            } else {
                echo "<p>No subjects found for the selected faculty and academic year-quarter.</p>";
            }

        } else {
            echo "<p>No evaluations found for the selected academic year-quarter.</p>";
        }
    } else {
        echo "<p>Invalid academic year or quarter.</p>";
    }

} else {
    echo "<p>Invalid request.</p>";
}
?>
