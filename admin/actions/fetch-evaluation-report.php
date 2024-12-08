<?php
// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection
include('../../db_conn.php');

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
if (isset($_POST['teacher_id'], $_POST['academicYearQuarter'], $_POST['subject_id'])) {
    // Get the POST parameters
    $teacher_id = $_POST['teacher_id'];
    $academicYearQuarter = $_POST['academicYearQuarter'];
    $subject_id = $_POST['subject_id'];
    $evaluation_id = $_POST['evaluation_id']; // Retrieve evaluation_id

    // Split the academic year and quarter
    list($year, $quarter) = explode('-Q', $academicYearQuarter);

    // Escape user inputs for security
    $teacher_id = mysqli_real_escape_string($conn, $teacher_id);
    $year = mysqli_real_escape_string($conn, $year);
    $quarter = mysqli_real_escape_string($conn, $quarter);
    $subject_id = mysqli_real_escape_string($conn, $subject_id);
    $evaluation_id = mysqli_real_escape_string($conn, $evaluation_id);

    // Query to fetch basic information (faculty name, class, academic year, course)
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
        WHERE ea.teacher_id = '$teacher_id'
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
        WHERE ea.teacher_id = '$teacher_id'
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
            WHERE es.teacher_id = '$teacher_id'
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
                /* Styling for the sentiment legend box */
                .legend-box {
                    border: 1px solid black;
                    background-color: white;
                    padding: 10px;
                    margin: 20px auto;
                    width: 90%; /* Same width as the table */
                    max-width: 800px; /* Optional: Set max width for larger screens */
                }

                .legend-item {
                    display: inline-block;
                    width: 30%;
                    text-align: center;
                    font-weight: bold;
                }

                .legend-box span {
                    display: inline-block;
                    width: 20px;
                    height: 10px;
                    margin-right: 10px;
                }

                .legend-title {
                    font-weight: bold;
                    margin-bottom: 10px;
                }

                /* Sentiment Classes (for display on web page) */
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
                        page-break-inside: avoid;
                        margin-bottom: 0;
                    }
                    .report-table, .report-table th, .report-table td {
                        border: 1px solid black !important;
                        border-collapse: collapse;
                    }

                    /* More specific selectors to ensure the color applies when printing */
                    li.positive {
                        color: green !important;
                    }
                    li.negative {
                        color: red !important;
                    }
                    li.neutral {
                        color: black !important;
                    }

                    /* Adjustments to legend */
                    .legend-box {
                        border: 1px solid black !important;
                        background-color: white !important;
                    }

                    .legend-box span {
                        display: inline-block;
                        width: 20px;
                        height: 20px;
                        margin-right: 10px;
                    }

                    .legend-item span {
                        /* Applying color to legend items during printing */
                        display: inline-block;
                        width: 20px;
                        height: 20px;
                        margin-right: 10px;
                    }

                    .positive span {
                        background-color: green !important;
                    }

                    .negative span {
                        background-color: red !important;
                    }

                    .neutral span {
                        background-color: black !important;
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
            <p class='report-title text-center' style='font-size:18px; font-weight:bold'>Faculty Evaluation Report</p>

            <table class='table info-table mb-1'>
                <tbody>
                    <tr>
                        <td><strong>Faculty:</strong> {$info['firstName']} {$info['lastName']}</td>
                        <td style=\"text-align: right; float: right;\"><strong>Academic Year:</strong> {$info['year']} - Quarter {$info['quarter']}</td>
                    </tr>
                    <tr>
                        <td><strong>Class:</strong> Grade {$info['grade_level']} - {$info['section']}</td>
                        <td style=\"text-align: right; float: right;\"><strong>Course:</strong> {$info['subject_title']}</td>
                    </tr>
                    <tr style=\"padding-bottom: 20px;\">
                        <td colspan='2' style=\"padding-bottom: 20px;\"><strong>Total Number of Students Evaluated:</strong> {$student_info['total_students']}</td>
                    </tr>
                </tbody>
            </table>

            <table class='table report-table'>
                <thead>
                    <tr class='text-center'>";

            foreach ($criteria_ratings as $criteria => $rating) {
                echo "<th>" . strtoupper($criteria) . "</th>";
            }

            echo "  <th>OVERALL RATING</th>
                    <th>EQUIVALENT</th>
                </tr>
                </thead>
                <tbody>
                    <tr>";

            foreach ($criteria_ratings as $rating) {
                echo "<td>{$rating}</td>";
            }

            echo "<td>{$overall_rating}</td>
                    <td>{$equivalent}</td>
                    </tr>
                </tbody>
            </table>";

            echo "<div>
                    <p><strong>Student Sentiments:</strong></p>
                    <ul>";

            // Sentiment Legend Box with color boxes
            echo "<div class='legend-box'>
                    <div class='legend-title'>Legend:</div>
                    <div class='legend-item positive'>
                        <span style='background-color: green; border-radius:1px;'></span> Positive
                    </div>
                    <div class='legend-item neutral'>
                        <span style='background-color: black; border-radius:1px;'></span> Neutral
                    </div>
                    <div class='legend-item negative'>
                        <span style='background-color: red; border-radius:1px;'></span> Negative
                    </div>
                </div>";

            if (empty($analyzed_sentiments)) {
                echo "<li><i>No sentiments posted.</i></li>";
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
                    echo "<li class='{$class}'>{$text}</li>";
                }
            }

            echo "</ul></div></body></html>";

    } else {
        // If no data is found for the selected subject
        echo "<p class='text-center' id='noData'><i>No data found for the selected subject.</i></p>";
    }

} else {
    // If required POST parameters are not set
    echo "<p>Invalid request.</p>";
}
?>
