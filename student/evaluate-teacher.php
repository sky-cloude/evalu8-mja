<?php
// Include necessary files and start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include('../db_conn.php'); // Include your database connection file

// Check if student ID is set in session and the user is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: ../login/login-form.php");
    exit();
}

$student_id = $_SESSION['user_id']; // Use session variable for student ID

// Retrieve and sanitize GET parameters
$evaluation_id = isset($_GET['evaluation_id']) ? intval($_GET['evaluation_id']) : 0;
$teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0;
$subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : 0;

// Check if the student is allowed to evaluate this evaluation_id
$eval_check_query = "
    SELECT ser.evaluation_id
    FROM students_eval_restriction ser
    WHERE ser.student_id = '$student_id' AND ser.evaluation_id = '$evaluation_id'";
$eval_check_result = mysqli_query($conn, $eval_check_query);

if (!$eval_check_result || mysqli_num_rows($eval_check_result) == 0) {
    // The student is not allowed to access this evaluation
    die("Unauthorized access.");
}

// Query to get teacher's name and subject title
$info_query = "
    SELECT 
        CONCAT(ta.firstName, ' ', ta.lastName) AS teacher_name,
        sl.subject_title
    FROM 
        subject_to_eval ste
    INNER JOIN 
        teacher_account ta ON ste.teacher_id = ta.teacher_id
    INNER JOIN 
        subject_list sl ON ste.subject_id = sl.subject_id
    WHERE 
        ste.evaluation_id = $evaluation_id AND ta.teacher_id = $teacher_id AND sl.subject_id = $subject_id";
$info_result = mysqli_query($conn, $info_query);

if ($info_result && mysqli_num_rows($info_result) > 0) {
    $info_row = mysqli_fetch_assoc($info_result);
    $teacher_name = $info_row['teacher_name'];
    $subject_title = $info_row['subject_title'];
} else {
    // Handle error or set default values
    $teacher_name = "Unknown Teacher";
    $subject_title = "Unknown Subject";
}

// Query to get the academic year and quarter associated with the evaluation_id
$acad_year_query = "
    SELECT 
        ay.year, 
        ay.quarter 
    FROM 
        evaluation_list el
    INNER JOIN 
        academic_year ay ON el.acad_year_id = ay.acad_year_id
    WHERE 
        el.evaluation_id = $evaluation_id";
$acad_year_result = mysqli_query($conn, $acad_year_query);

if ($acad_year_result && mysqli_num_rows($acad_year_result) > 0) {
    $acad_year_row = mysqli_fetch_assoc($acad_year_result);
    $academic_year = $acad_year_row['year'];
    $quarter = $acad_year_row['quarter'];
} else {
    // Handle error or set default values
    $academic_year = "Unknown Academic Year";
    $quarter = "";
}

// Fetch criteria list
$query_criteria = "SELECT criteria_id, criteria FROM criteria_list ORDER BY ordered_by ASC";
$result_criteria = mysqli_query($conn, $query_criteria);

// Fetch questions grouped by criteria
$query_questions = "
    SELECT q.question_id, q.criteria_id, q.question, q.order_by, c.criteria 
    FROM question_list q 
    INNER JOIN criteria_list c ON q.criteria_id = c.criteria_id
    ORDER BY c.ordered_by, q.order_by ASC";
$result_questions = mysqli_query($conn, $query_questions);

$questions_by_criteria = [];
while ($row = mysqli_fetch_assoc($result_questions)) {
    $criteria_id = $row['criteria_id'];
    $questions_by_criteria[$criteria_id][] = $row;
}

// Check if there are any questions available
$are_questions_available = false;
foreach ($questions_by_criteria as $criteria_questions) {
    if (!empty($criteria_questions)) {
        $are_questions_available = true;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluate Teacher</title>
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <?php include('../header.php'); ?>
    <link rel="stylesheet" href="../css/sidebar.css" />

    <style>
        /* Additional styling for the success modal */
        .modal-header, .modal-footer {
            background-color: #281313;
            color: white;
        }
        .modal-body {
            background-color: #f8f9fa;
        }
        
        /* Styling for breadcrumb section */
        .breadcrumb {
            background-color: transparent;
            margin-bottom: 0;
            padding: 8px 15px;
            display: block;
            width: 100%;
        }

        .breadcrumb-item + .breadcrumb-item::before {
            content: ">";
            color: #6c757d;
        }

        .breadcrumb a {
            text-decoration: none;
        }

        .breadcrumb .breadcrumb-item a {
            color: #6c757d;
        }

        .breadcrumb .breadcrumb-item.active {
            color: #343a40;
            font-weight: normal;
        }

        .custom-border {
            border: 1px solid #dddddd;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        a.btn-back {
            background: none;
            border: none;
            padding: 0;
            color: #281313;
            margin-top: 10px;
        }

        a.btn-back i {
            font-size: 2rem;
        }

        a.btn-back:hover {
            color: #facc15;
        }
        
        #submitEvaluationBtn {
            background-color: #281313 !important; /* Apply base color */
            color: white !important; /* Set text color */
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s, color 0.3s ease;
        }

        #submitEvaluationBtn:hover {
            background-color: #facc15 !important; /* Hover background color */
            color: #281313 !important; /* Hover text color */
            cursor: pointer;
        }

        #scrollToTopBtn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: none;
            background-color: #281313; /* Change to your desired color */
            color: white;
            border: none;
            border-radius: 50%; /* Makes the button a perfect circle */
            width: 50px; /* Set a fixed width */
            height: 50px; /* Set a fixed height to make it a circle */
            cursor: pointer;
            font-size: 24px; /* Increase the font size for better visibility */
            text-align: center;
            line-height: 50px; /* Center the text vertically */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Add a subtle shadow */
            transition: background-color 0.3s, transform 0.3s; /* Smooth transition for background and transform */
        }

        #scrollToTopBtn:hover {
            background-color: #44311f; /* Darken the button on hover */
            transform: scale(1.1); /* Slightly enlarge the button on hover */
        }

        /* Custom styling for radio buttons */
        .custom-radio {
            position: relative;
            display: inline-flex; /* Use inline-flex for better alignment */
            justify-content: center; /* Center the radio button */
            align-items: center; /* Align radio button vertically */
            margin: 0 5px; /* Adjust horizontal spacing */
        }

        .custom-radio input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .custom-radio .checkmark {
            position: relative;
            height: 22px; /* Adjusted height */
            width: 22px; /* Adjusted width */
            background-color: #f5f5f5;
            border: 2px solid #281313; /* Set border color to match */
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.3s ease;
        }

        .custom-radio input:checked ~ .checkmark {
            background-color: #281313; /* Background color when checked */
        }

        .custom-radio .checkmark:after {
            content: "";
            position: absolute;
            display: none; /* Hidden by default */
        }

        .custom-radio input:checked ~ .checkmark:after {
            display: block;
        }

        .custom-radio .checkmark:after {
            width: 10px; /* Adjusted width of inner circle */
            height: 10px; /* Adjusted height of inner circle */
            border-radius: 50%; /* Make inner circle round */
            background: white; /* Color of inner circle */
            position: absolute; /* Position relative to the checkmark */
            top: 50%; /* Center vertically */
            left: 50%; /* Center horizontally */
            transform: translate(-50%, -50%); /* Use transform to perfectly center */
        }

        /* Adjust icon size for small screens */
        @media (max-width: 576px) {
            a.btn-back i {
                font-size: 1.5rem;
            }
            .academic-year-text {
                font-size: 0.575rem; 
            }
        }

        /* Adjustments for submit button */
        #submitEvaluationBtn {
            background-color: #007bff; 
            border: none;
            padding: 10px 20px;
            color: white;
            border-radius: 4px;
        }

        #submitEvaluationBtn:hover {
            background-color: #0056b3; 
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include('student-navigation.php'); ?>

    <!-- Breadcrumbs Section -->
    <div class="container" style="margin-top:75px;">
        <nav aria-label="breadcrumb" class="bg-white custom-border rounded px-3 py-2">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="student-dashboard.php" class="text-muted text-decoration-none">
                        <i class="fa-solid fa-house"></i> Home
                    </a>
                </li>
                <li class="breadcrumb-item active text-muted" aria-current="page">Evaluate Teacher</li>
            </ol>
        </nav>
    </div>

    <!-- Instruction and Back Button -->
    <div class="container">
        <div class="p-3 mt-2 d-flex flex-row align-items-center justify-content-between">
            <p class="classTitle fs-6 mb-0">
                <span class="fw-bold">Instruction: </span>Evaluate your teacher based on the criteria provided. Use the rating scale from 1 to 5, where 5 indicates strong agreement and 1 indicates strong disagreement. Your responses are valuable for assessing and enhancing the quality of instruction.
            </p>
            <a href="student-dashboard.php" class="btn btn-back ms-3">
                <i class="fa-solid fa-circle-chevron-left"></i>
            </a>
        </div>

    </div>

    <!-- Yellow Line Separator -->
    <div class="container">
        <div class="mx-auto mb-4" style="height: 2px; background-color: #facc15;"></div>
    </div>
    
    <div class="container">
        <div class="row">
            <div class="col-12 col-md-3 mb-3 mb-md-0">
                <div class="border border-secondary shadow rounded p-3 d-flex justify-content-center align-items-center" style="background-color: #281313; color: white; height: 80px;">
                    <p class="mb-0 fw-bold text-center"><?php echo htmlspecialchars($teacher_name); ?> - <?php echo htmlspecialchars($subject_title); ?></p>
                </div>
            </div>
            <div class="col-12 col-md-9 mb-5">
                <?php if ($are_questions_available): ?>
                    <!-- Form Start -->
                    <form action="actions/submit-evaluation.php" method="post">
                        <div class="bg-white rounded border shadow p-3 mt-0">   
                            <div class="p-3 mt-0 d-flex flex-column flex-md-row justify-content-between align-items-center">
                                <p class="fs-6 fw-bold fs-4 mb-0 me-2 academic-year-text">
                                    Evaluation for Academic Year: <?php echo htmlspecialchars($academic_year); ?><?php if ($quarter) { echo ' - Quarter ' . htmlspecialchars($quarter); } ?>
                                </p>
                                <button type="submit" class="btn mt-3 mt-md-0 ms-auto" id="submitEvaluationBtn">Submit Evaluation</button>
                            </div>

                            <!-- Pass necessary hidden inputs -->
                            <input type="hidden" name="evaluation_id" value="<?php echo htmlspecialchars($evaluation_id); ?>">
                            <input type="hidden" name="teacher_id" value="<?php echo htmlspecialchars($teacher_id); ?>">
                            <input type="hidden" name="subject_id" value="<?php echo htmlspecialchars($subject_id); ?>">

                            <fieldset class="border rounded p-3 mb-4" style="border-width: 2px;">
                                <legend class="w-auto px-2 fs-6 fw-bold text-white mb-3" style="background-color: #343a40;">Rating Legend</legend>
                                <div class="d-flex flex-wrap justify-content-center mt-2 mb-2">
                                    <span class="me-4 mb-2">5 = Outstanding</span>
                                    <span class="me-4 mb-2">4 = Very Satisfactory</span>
                                    <span class="me-4 mb-2">3 = Satisfactory</span>
                                    <span class="me-4 mb-2">2 = Fair</span>
                                    <span class="mb-2">1 = Needs Improvement</span>
                                </div>
                            </fieldset>

                            <!-- Loop through the criteria and questions to generate the form fields -->
                            <?php
                            if (mysqli_num_rows($result_criteria) > 0) {
                                mysqli_data_seek($result_criteria, 0);
                                $question_counter = 1; // Counter variable to track question numbers
                                while ($row_criteria = mysqli_fetch_assoc($result_criteria)) {
                                    $criteria_id = $row_criteria['criteria_id'];
                                    $criteria_title = $row_criteria['criteria'];

                                    echo '<div class="table-responsive">';
                                    echo '<table class="table table-bordered">';
                                    echo '<thead>';
                                    echo '<tr>';
                                    echo '<th class="bg-dark text-white text-left p-2">' . htmlspecialchars($criteria_title) . '</th>';
                                    echo '<th class="bg-dark text-white text-center" style="width: 8%;">5</th>';
                                    echo '<th class="bg-dark text-white text-center" style="width: 8%;">4</th>';
                                    echo '<th class="bg-dark text-white text-center" style="width: 8%;">3</th>';
                                    echo '<th class="bg-dark text-white text-center" style="width: 8%;">2</th>';
                                    echo '<th class="bg-dark text-white text-center" style="width: 8%;">1</th>';
                                    echo '</tr>';
                                    echo '</thead>';
                                    echo '<tbody>';

                                    if (isset($questions_by_criteria[$criteria_id]) && !empty($questions_by_criteria[$criteria_id])) {
                                        foreach ($questions_by_criteria[$criteria_id] as $question) {
                                            echo '<tr>';
                                            echo '<td class="text-left">
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2 fw-bold">' . $question_counter++ . '.</span> <!-- Display question number -->
                                                    <span class="ms-2">' . htmlspecialchars($question['question']) . '</span>
                                                </div>
                                            </td>';
                                            // Custom styled radio buttons with the `class="radio-btn"` added
                                            echo '<td class="text-center"><label class="custom-radio"><input type="radio" class="radio-btn" name="question' . $question['question_id'] . '" value="5" required><span class="checkmark"></span></label></td>';
                                            echo '<td class="text-center"><label class="custom-radio"><input type="radio" class="radio-btn" name="question' . $question['question_id'] . '" value="4"><span class="checkmark"></span></label></td>';
                                            echo '<td class="text-center"><label class="custom-radio"><input type="radio" class="radio-btn" name="question' . $question['question_id'] . '" value="3"><span class="checkmark"></span></label></td>';
                                            echo '<td class="text-center"><label class="custom-radio"><input type="radio" class="radio-btn" name="question' . $question['question_id'] . '" value="2"><span class="checkmark"></span></label></td>';
                                            echo '<td class="text-center"><label class="custom-radio"><input type="radio" class="radio-btn" name="question' . $question['question_id'] . '" value="1"><span class="checkmark"></span></label></td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="6" class="text-center">No questions available for this criterion.</td></tr>';
                                    }

                                    echo '</tbody>';
                                    echo '</table>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p class="text-center">No criteria available for evaluation at this time. Please check back later.</p>';
                            }
                            ?>

                            <!-- Student Sentiment Input -->
                            <div class="mb-4 mt-4">
                                <label for="studentSentiment" class="form-label fw-bold">Student Sentiments (Optional):</label>
                                <textarea id="studentSentiment" name="student_sentiment" class="form-control border-dark" rows="4" placeholder="Share your thoughts, comments, or suggestions about your learning experience."></textarea>
                            </div>
                        </div>
                    </form>
                    <!-- Form End -->

                <?php else: ?>
                    <div class="bg-white rounded border border-secondary p-3 mt-0">
                        <p class="text-center">There are currently no questions available for evaluation. Please check back later.</p>
                        <div class="d-flex justify-content-end gap-3 mb-4 mt-4">
                            <a href="student-dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Scroll-to-Top Button -->
    <button id="scrollToTopBtn" title="Go to top">
        &#x25B2;
    </button>

    <?php include('footer.php'); ?>
    <script>
        // Get the button
        let scrollToTopBtn = document.getElementById("scrollToTopBtn");

        // Show the button when the user scrolls down 20px from the top
        window.onscroll = function() {
            if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
                scrollToTopBtn.style.display = "block";
            } else {
                scrollToTopBtn.style.display = "none";
            }
        };

        // When the user clicks on the button, scroll to the top of the document
        scrollToTopBtn.onclick = function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        };

        // Handles radio button deselection
        document.querySelectorAll('.radio-btn').forEach(radio => {
            radio.addEventListener('click', function () {
                if (this.checked) {
                    this.dataset.clicked = this.dataset.clicked === "true" ? "false" : "true";
                }
                if (this.dataset.clicked === "false") {
                    this.checked = false;
                }
            });
        });
    </script>

</body>
</html>
