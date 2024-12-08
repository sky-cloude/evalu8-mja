<?php
// Include necessary files and start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection file
include('../db_conn.php');

// Check if student ID is set in session and the user is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: ../login/login-form.php");
    exit();
}

$student_id = $_SESSION['user_id']; // Use session variable for student ID
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <?php include('../header.php'); ?>
    <link rel="stylesheet" href="../css/sidebar.css" />

    <style>
        .modal-header, .modal-footer {
            background-color: #281313;
            color: white;
        }
        .modal-body {
            background-color: #f8f9fa;
        }

        /* Ensure consistent avatar image and placeholder styling */
        .avatar-img, .icon-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
        }

        /* Styling for the placeholder icon */
        .icon-placeholder {
            font-size: 80px;
            background-color: #fafafa;
            color: #281313;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Ensure the container holding the icon has the same styling */
        .icon-container {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        /* Ensure consistent card body styling */
        .card-body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        /* Styling for the card itself */
        .card {
            height: 100%;
            background-color: #fafafa;
            border: 1px solid #dddddd;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Updated styling for custom-border to apply the same border as the card */
        .custom-border {
            border: 1px solid #dddddd;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Button styling for Evaluate button */
        .card .btn-evaluate {
            margin-top: 10px;
            background-color: #281313;
            color: white;
            width: 100px;
            height: 35px;
            margin-top: auto;
            margin-bottom: 10px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card .btn-evaluate:hover {
            background-color: #facc15;
            color: black;
        }

        .card .btn-evaluate.disabled, .card .btn-evaluate[disabled] {
            background-color: #dddddd;
            color: #666666;
            pointer-events: none;
            cursor: not-allowed;
        }

        /* Adjust spacing and font size for Average Evaluation Score and Submitted on text */
        .evaluation-info {
            margin-bottom: 0;
            line-height: 1.2;
            font-size: 0.875rem;
        }

        /* Spacing between evaluation information and the button */
        .evaluation-section {
            margin-bottom: 10px;
        }

        /* Adjust grid columns for laptop and above */
        @media (min-width: 992px) {
            .col-lg-3 {
                flex: 0 0 auto;
                width: 25%;
            }
        }
    </style>
</head>
<body>
    <?php include('student-navigation.php'); ?>

    <!-- Breadcrumbs Section -->
    <div class="container mt-6" style="margin-top:75px;">
        <nav aria-label="breadcrumb" class="bg-white custom-border rounded px-3 py-2">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="student-dashboard.php" class="text-muted text-decoration-none">
                        <i class="fa-solid fa-house"></i> Home
                    </a>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Success Modal -->
    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <!-- Bootstrap Modal Structure -->
        <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="successModalLabel">Evaluation Submitted</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Your evaluation has been submitted successfully!
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Welcome Section -->
    <div class="container my-3">
        <div class="card p-4 mb-4 custom-border">
            <h3 class="display-6">Welcome back, <strong><?php echo ucfirst(strtolower($_SESSION['firstName'])); ?></strong>!</h3>
            <?php
                // Fetch the active academic year from the database
                $year_query = "SELECT * FROM `academic_year` WHERE is_active = 1 LIMIT 1";
                $year_result = mysqli_query($conn, $year_query);

                if ($year_result && mysqli_num_rows($year_result) > 0) {
                    $year_row = mysqli_fetch_assoc($year_result);
                    $active_acad_year_id = $year_row['acad_year_id'];
                    $evaluation_end_date = strtotime($year_row['evaluation_period']);
                    $current_time = time();
                    $is_active = $year_row['is_active'];

                    // Check if the evaluation period is still ongoing
                    if ($is_active == 1 && $current_time <= $evaluation_end_date) {
                        $status = "In Progress";
                    } else {
                        $status = "Closed";
                    }

                    $evaluation_message = "Evaluation ends on " . date("F d, Y - h:i A", $evaluation_end_date);

                    echo '<p class="mb-0">Academic Year: ' . htmlspecialchars($year_row['year']) . ' - Quarter ' . htmlspecialchars($year_row['quarter']) . '</p>';
                    echo '<p class="mb-0">' . $evaluation_message . '</p>';
                    echo '<p class="mb-0">Status: <strong class="' . ($status == "In Progress" ? 'text-success' : 'text-danger') . '">' . $status . '</strong></p>';
                } else {
                    echo '<p class="text-danger">No active academic year found. Please contact the administrator.</p>';
                    $active_acad_year_id = null;
                }
            ?>
        </div>
    </div>

    <!-- Teacher Cards Section -->
    <div class="container my-3">
        <?php
            if (!empty($active_acad_year_id)) {
                // Fetch the student's evaluations for the active academic year
                $evaluation_query = "
                    SELECT 
                        el.evaluation_id,
                        el.class_id
                    FROM 
                        students_eval_restriction ser
                    INNER JOIN 
                        evaluation_list el ON ser.evaluation_id = el.evaluation_id
                    WHERE 
                        ser.student_id = '$student_id' 
                        AND el.acad_year_id = '$active_acad_year_id'";

                $evaluation_result = mysqli_query($conn, $evaluation_query);

                if ($evaluation_result && mysqli_num_rows($evaluation_result) > 0) {
                    echo "<div class='row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4'>";

                    while ($evaluation_row = mysqli_fetch_assoc($evaluation_result)) {
                        $evaluation_id = $evaluation_row['evaluation_id'];
                        $class_id = $evaluation_row['class_id'];

                        // Fetch the subjects and teachers for each evaluation
                        $subject_query = "
                        SELECT 
                            ta.avatar,
                            ta.teacher_id,
                            sl.subject_id,
                            CONCAT(ta.firstName, ' ', ta.lastName) AS teacher_name,
                            sl.subject_title, 
                            cl.grade_level, 
                            cl.section
                        FROM 
                            subject_to_eval ste
                        INNER JOIN 
                            teacher_account ta ON ste.teacher_id = ta.teacher_id
                        INNER JOIN 
                            subject_list sl ON ste.subject_id = sl.subject_id
                        INNER JOIN 
                            class_list cl ON cl.class_id = '$class_id'
                        WHERE 
                            ste.evaluation_id = '$evaluation_id'";

                        $subject_result = mysqli_query($conn, $subject_query);

                        if ($subject_result && mysqli_num_rows($subject_result) > 0) {
                            while ($subject_row = mysqli_fetch_assoc($subject_result)) {
                                $teacher_name = $subject_row['teacher_name'];
                                $subject_name = $subject_row['subject_title'];
                                $grade_level = $subject_row['grade_level'];
                                $section = $subject_row['section'];
                                $teacher_avatar = $subject_row['avatar'];
                                $teacher_id = $subject_row['teacher_id'];
                                $subject_id = $subject_row['subject_id'];

                                $avatar_html = $teacher_avatar 
                                    ? "<img src='data:image/jpeg;base64," . base64_encode($teacher_avatar) . "' class='rounded-circle avatar-img mb-3' alt='avatar'>" 
                                    : "<div class='icon-container'><i class='bi bi-person-circle icon-placeholder'></i></div>";

                                // Fetch per criteria average ratings
                                $evaluation_stats_query = "
                                    SELECT 
                                        c.criteria,
                                        AVG(ea.rating) AS avg_rating
                                    FROM 
                                        evaluation_answers ea
                                    JOIN 
                                        criteria_list c ON ea.criteria_id = c.criteria_id
                                    WHERE 
                                        ea.teacher_id = '$teacher_id' 
                                        AND ea.subject_id = '$subject_id' 
                                        AND ea.evaluation_id = '$evaluation_id'
                                        AND ea.student_id = '$student_id'
                                    GROUP BY 
                                        c.criteria, c.ordered_by
                                    ORDER BY 
                                        c.ordered_by";

                                $evaluation_stats_result = mysqli_query($conn, $evaluation_stats_query);

                                // Fetch latest submission date
                                $latest_submission_query = "
                                    SELECT 
                                        MAX(ea.created_at) AS latest_submission
                                    FROM 
                                        evaluation_answers ea
                                    WHERE 
                                        ea.teacher_id = '$teacher_id' 
                                        AND ea.subject_id = '$subject_id' 
                                        AND ea.evaluation_id = '$evaluation_id'
                                        AND ea.student_id = '$student_id'";

                                $latest_submission_result = mysqli_query($conn, $latest_submission_query);
                                $latest_submission = "N/A";
                                if ($latest_submission_result && mysqli_num_rows($latest_submission_result) > 0) {
                                    $latest_row = mysqli_fetch_assoc($latest_submission_result);
                                    if (!empty($latest_row['latest_submission'])) {
                                        $latest_submission = date("F j, Y \a\\t g:i a", strtotime($latest_row['latest_submission']));
                                    }
                                }

                                $evaluation_done = false;
                                $evaluation_info = "";
                                $total_avg = 0;
                                $num_criteria = 0;

                                if ($evaluation_stats_result && mysqli_num_rows($evaluation_stats_result) > 0) {
                                    while ($stats_row = mysqli_fetch_assoc($evaluation_stats_result)) {
                                        if (!empty($stats_row['avg_rating'])) {
                                            $total_avg += $stats_row['avg_rating'];
                                            $num_criteria++;
                                        }
                                    }

                                    if ($num_criteria > 0) {
                                        // Calculate overall average as the average of criteria averages
                                        $overall_rating = $total_avg / $num_criteria;
                                        // Round to two decimal places
                                        $overall_rating = number_format($overall_rating, 2);

                                        $evaluation_info = "<div class='evaluation-section'>
                                                            <p class='evaluation-info'>Average Evaluation Score: <strong>$overall_rating</strong></p>
                                                            <p class='evaluation-info'>Submitted on: <strong>$latest_submission</strong></p>
                                                        </div>";
                                        $evaluation_done = true;
                                    }
                                }

                                // Disable the button if the evaluation is already done
                                $button_disabled = $evaluation_done ? 'disabled' : '';

                                // Render the card with teacher and subject details
                                echo "
                                <div class='col'>
                                    <div class='card text-center h-100 custom-border'>
                                        <div class='card-body d-flex flex-column align-items-center justify-content-center'>
                                            $avatar_html
                                            <h5 class='card-title' style='font-size: 16px; margin-bottom: 5px;'>" . htmlspecialchars($teacher_name) . "</h5>
                                            <p class='card-text' style='font-size: 14px; margin-bottom: 1px;'>" . htmlspecialchars($subject_name) . "</p>
                                            <p class='card-text' style='font-size: 14px;'>Grade " . htmlspecialchars($grade_level) . " - " . htmlspecialchars($section) . "</p>
                                            $evaluation_info
                                            <a href='evaluate-teacher.php?evaluation_id=" . urlencode($evaluation_id) . "&teacher_id=" . urlencode($teacher_id) . "&subject_id=" . urlencode($subject_id) . "' class='btn btn-evaluate btn-sm' $button_disabled>
                                                Evaluate
                                            </a>
                                        </div>
                                    </div>
                                </div>";
                            }
                        }
                    }

                    echo "</div>"; // End row for teacher cards
                } else {
                    echo '<p class="ms-4 text-danger">No evaluations found for you in the current academic year.</p>';
                }
            } else {
                echo '<p class="ms-4 text-danger">No active academic year available.</p>';
            }
        ?>
    </div>

    <?php include('footer.php'); ?>

    <!-- Initiate the modal -->
    <script>
        // Show the success modal if the status is 'success'
        <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            var successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();

            // Remove the status parameter from the URL after closing the modal
            document.getElementById('successModal').addEventListener('hidden.bs.modal', function () {
                const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({ path: newUrl }, '', newUrl);
            });
        <?php endif; ?>
    </script>
</body>
</html>
