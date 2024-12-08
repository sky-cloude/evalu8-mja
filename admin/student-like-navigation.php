<?php
session_start();
include('../db_conn.php');

// Check if the user is either a student or an admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'student' && $_SESSION['user_type'] != 'admin')) {
    header("Location: ../login/login-form.php");
    exit();
}

$id = intval($_SESSION['user_id']);
$user_type = $_SESSION['user_type'];

// Query the correct table based on user type
if ($user_type === 'admin') {
    $query = "SELECT * FROM `admin_account` WHERE `admin_id` = '$id'";
} else if ($user_type === 'student') {
    $query = "SELECT * FROM `student_account` WHERE `student_id` = '$id'";
}

$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
}


// Static values for teacher, subject, and academic year
$teacher_name = "John Doe";
$subject_title = "English 7";
$academic_year = "2024";
$quarter = "1st Quarter";

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
    <?php include('../header.php'); ?>
    <link rel="stylesheet" href="../css/admin-elements.css" />
    <title>Preview Questionnaire</title>

    <style>
        /* Import Poppins font from Google Fonts */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

        /* Apply Poppins font */
        body {
            font-family: 'Poppins', sans-serif;
        }

        nav .dropdown-menu .dropdown-item:hover,
        nav .dropdown-menu .dropdown-item:focus,
        nav .dropdown-menu .dropdown-item.active {
            background-color: #facc15;
            color: #000;
        }
        .custom-border {
            border: 1px solid #dddddd;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .btn-back {
            background: none;
            border: none;
            padding: 0;
            color: #281313;
            margin-top: 10px;
        }
        .btn-back i {
            font-size: 2.5rem;
        }
        .btn-back:hover {
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
        .custom-radio {
            position: relative;
            display: inline-flex; 
            justify-content: center; 
            align-items: center; 
            margin: 0 5px; 
        }
        .custom-radio input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }
        .custom-radio .checkmark {
            position: relative;
            height: 22px; 
            width: 22px; 
            background-color: #f5f5f5;
            border: 2px solid #281313;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.3s ease;
        }
        .custom-radio input:checked ~ .checkmark {
            background-color: #281313;
        }
        .custom-radio .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }
        .custom-radio input:checked ~ .checkmark:after {
            display: block;
        }
        .custom-radio .checkmark:after {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: white;
            position: absolute;
            top: 50%; 
            left: 50%; 
            transform: translate(-50%, -50%);
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
        @media (max-width: 576px) {
            .btn-back i {
                font-size: 1.5rem;
            }
            .academic-year-text {
                font-size: 0.575rem; 
            }
        }
    </style>
</head>
<body>

<div class="wrapper pb-5 pt-4" style="min-height: 100vh; height: auto; background-color: #fafafa;">
    <nav class="navbar navbar-expand-lg" style="background-color: #44311f; height: 65px; width: 100%; position: fixed; top: 0; z-index: 1000;">
        <div class="container-fluid d-flex align-items-center">
            <a href="<?php echo $user_type === 'admin' ? 'admin-dashboard.php' : 'student-dashboard.php'; ?>">
                <img src="../Logo/mja-logo.png" alt="Mary Josette Academy" style="width: 50px; height: auto; cursor: pointer;" class="img-fluid ms-4">
            </a>
            <a class="navbar-brand text-white fw-bold fs-6 ms-3" href="#">
                Evalu8: A Faculty Evaluation System
            </a>
            <div class="ms-auto me-4"></div>
        </div>
    </nav>

    
    <!-- Breadcrumbs and Instruction Section -->
    <div class="container" style="margin-top:65px;">
        <nav aria-label="breadcrumb" class="bg-white custom-border rounded px-3 py-2">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="#" class="text-muted text-decoration-none">
                        <i class="fa-solid fa-house"></i> Home
                    </a>
                </li>
                <li class="breadcrumb-item active text-muted" aria-current="page">Evaluate Teacher</li>
            </ol>
        </nav>
    </div>

    <div class="container">
        <div class="p-3 mt-2 d-flex flex-row align-items-center justify-content-between">
            <p class="classTitle mb-0" style="font-size:1.05rem;">
                <span class="fw-bold">Instruction: </span>Evaluate your teacher based on the criteria provided. Use the rating scale from 1 to 5, where 5 indicates strong agreement and 1 indicates strong disagreement. Your responses are valuable for assessing and enhancing the quality of instruction.
            </p>
            <a href="admin-questions.php" class="btn btn-back ms-3">
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
                <div class="border shadow rounded p-3 d-flex justify-content-center align-items-center" style="background-color: #281313; color: white; height: 80px;">
                    <p class="mb-0 fw-bold text-center"><?php echo htmlspecialchars($teacher_name); ?> - <?php echo htmlspecialchars($subject_title); ?></p>
                </div>
            </div>
            <div class="col-12 col-md-9 mb-5">
                <?php if ($are_questions_available): ?>
                    <!-- Form Start -->
                    <form action="#" method="post">
                        <div class="bg-white rounded border shadow p-3 mt-0">   
                            <div class="p-3 mt-0 d-flex flex-column flex-md-row justify-content-between align-items-center">
                                <p class="fs-6 fw-bold fs-4 mb-0 me-2 academic-year-text">
                                    Evaluation for Academic Year: <?php echo htmlspecialchars($academic_year); ?><?php if ($quarter) { echo ' - ' . htmlspecialchars($quarter); } ?>
                                </p>
                                <!-- Use Bootstrap classes for centering on small screens -->
                                <div class="col-12 col-md-auto text-center text-md-end mt-3 mt-md-0">
                                    <button type="button" class="btn btn-primary" id="submitEvaluationBtn">Submit Evaluation</button>
                                </div>
                            </div>

                            <!-- Hidden Inputs for Static Evaluation Data -->
                            <input type="hidden" name="evaluation_id" value="1"> <!-- Static evaluation_id -->
                            <input type="hidden" name="teacher_id" value="1"> <!-- Static teacher_id -->
                            <input type="hidden" name="subject_id" value="1"> <!-- Static subject_id -->

                            <fieldset class="border border rounded p-3 mb-4" style="border-width: 2px;">
                                <legend class="w-auto px-2 fs-6 fw-bold text-white mb-3 d-flex align-items-center justify-content-center" style="background-color: #343a40; height:30px;">Rating Legend</legend>
                                <div class="d-flex flex-wrap justify-content-center mt-2 mb-2">
                                    <span class="me-4 mb-2">5 = Strongly Agree</span>
                                    <span class="me-4 mb-2">4 = Agree</span>
                                    <span class="me-4 mb-2">3 = Uncertain</span>
                                    <span class="me-4 mb-2">2 = Disagree</span>
                                    <span class="mb-2">1 = Strongly Disagree</span>
                                </div>
                            </fieldset>

                            <!-- Loop through criteria and questions -->
                            <?php
                            if (mysqli_num_rows($result_criteria) > 0) {
                                mysqli_data_seek($result_criteria, 0);
                                $question_counter = 1;
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
                                                    <span class="me-2 fw-bold">' . $question_counter++ . '.</span>
                                                    <span class="ms-2">' . htmlspecialchars($question['question']) . '</span>
                                                </div>
                                            </td>';
                                            echo '<td class="text-center"><label class="custom-radio"><input type="radio" name="question' . $question['question_id'] . '" value="5" required><span class="checkmark"></span></label></td>';
                                            echo '<td class="text-center"><label class="custom-radio"><input type="radio" name="question' . $question['question_id'] . '" value="4"><span class="checkmark"></span></label></td>';
                                            echo '<td class="text-center"><label class="custom-radio"><input type="radio" name="question' . $question['question_id'] . '" value="3"><span class="checkmark"></span></label></td>';
                                            echo '<td class="text-center"><label class="custom-radio"><input type="radio" name="question' . $question['question_id'] . '" value="2"><span class="checkmark"></span></label></td>';
                                            echo '<td class="text-center"><label class="custom-radio"><input type="radio" name="question' . $question['question_id'] . '" value="1"><span class="checkmark"></span></label></td>';
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
                                <textarea id="studentSentiment" name="student_sentiment" class="form-control border-secondary" rows="4" placeholder="Share your thoughts, comments, or suggestions about your learning experience."></textarea>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="bg-white rounded border border-secondary p-3 mt-0">
                        <p class="text-center">There are currently no questions available for evaluation. Please check back later.</p>
                        <div class="d-flex justify-content-end gap-3 mb-4 mt-4">
                            <a href="#" class="btn btn-secondary">Back to Dashboard</a>
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
</div>

<script>
     // Scroll-to-Top Button functionality
     let scrollToTopBtn = document.getElementById("scrollToTopBtn");

    window.onscroll = function() {
        if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            scrollToTopBtn.style.display = "block";
        } else {
            scrollToTopBtn.style.display = "none";
        }
    };

    scrollToTopBtn.onclick = function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    // Allow radio button deselection
    document.querySelectorAll('input[type="radio"]').forEach(function(radio) {
        radio.addEventListener('click', function() {
            if (this.checked && this.dataset.clicked === "true") {
                this.checked = false; // Deselect the radio button
                this.dataset.clicked = "false";
            } else {
                this.dataset.clicked = "true";
            }
        });
    });
</script>
</body>
</html>
