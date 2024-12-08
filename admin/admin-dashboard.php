<?php
include('../db_conn.php');  
session_start();

// Initialize variables to prevent undefined variable errors
$total_evaluations = 0;
$multiple_active_years_error = null;
$no_active_year_error = null;
$active_year = 'Unknown';
$active_quarter = 'Unknown';
$evaluation_end = 'Unknown';
$status = 'Unknown';
$top_teachers = [];
$positive_percentage = 0;
$neutral_percentage = 0;
$negative_percentage = 0;
$likert_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$evaluation_counts = ['7' => 0, '8' => 0, '9' => 0, '10' => 0, '11' => 0, '12' => 0];
$total_students = 0;  // Initialize total_students variable
$total_teachers = 0;  // Initialize total_teachers variable

// Fetch the logged-in admin's details using the session user_id
$admin_first_name = 'Admin';  // Default to 'Admin' if no name is found
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $admin_query = "SELECT firstName FROM admin_account WHERE admin_id = '$user_id' LIMIT 1";
    $admin_result = mysqli_query($conn, $admin_query);

    if ($admin_result && mysqli_num_rows($admin_result) > 0) {
        $admin_row = mysqli_fetch_assoc($admin_result);
        $admin_first_name = $admin_row['firstName'];
    }
}

// Fetch Present Employees Today based on Check Out records
$present_employees_query = "
    SELECT COUNT(DISTINCT emp_code) AS present_employees_today 
    FROM employee_attendance 
    WHERE punch_state = 'Check Out' AND DATE(punch_date_time) = CURDATE()
";
$present_employees_result = mysqli_query($conn, $present_employees_query);

$present_employees_today = 0; // Default to 0 if query fails
if ($present_employees_result) {
    $row = mysqli_fetch_assoc($present_employees_result);
    $present_employees_today = $row['present_employees_today'];
}

// Fetch the total number of subjects
$subject_query = "SELECT COUNT(*) AS total_subjects FROM subject_list";
$subject_result = mysqli_query($conn, $subject_query);

$total_subjects = 0; // Default to 0 in case of error
if ($subject_result) {
    $subject_row = mysqli_fetch_assoc($subject_result);
    $total_subjects = $subject_row['total_subjects'];
}

// Fetch the total number of staff
$staff_query = "SELECT COUNT(*) AS total_staff FROM staff_account";
$staff_result = mysqli_query($conn, $staff_query);

$total_staff = 0; // Default to 0 in case of error
if ($staff_result) {
    $staff_row = mysqli_fetch_assoc($staff_result);
    $total_staff = $staff_row['total_staff'];
}

// Fetch active academic years and handle errors
$active_query = "SELECT * FROM academic_year WHERE is_active = 1";
$active_result = mysqli_query($conn, $active_query);

if ($active_result && mysqli_num_rows($active_result) > 1) {
    $multiple_active_years_error = "There's more than 1 active academic year in the database. Please check the records.";
} elseif ($active_result && mysqli_num_rows($active_result) == 1) {
    $active_row = mysqli_fetch_assoc($active_result);
    $acad_year_id = $active_row['acad_year_id']; // Capture acad_year_id
    $active_year = $active_row['year'];
    $active_quarter = $active_row['quarter'];
    $active_evaluation_period = $active_row['evaluation_period'];
    $is_active = $active_row['is_active'];

    // Get current time and evaluation end time
    $current_time = time();
    $evaluation_time = strtotime($active_evaluation_period);

    // Set evaluation status based on whether the evaluation period is still open or has ended
    if ($is_active == 1 && $evaluation_time >= $current_time) {
        $evaluation_end = "Evaluation is open until " . date("F j, Y – h:i A", $evaluation_time);
        $status = "In Progress";
    } elseif ($is_active == 1 && $evaluation_time < $current_time) {
        $evaluation_end = "Evaluation ended at " . date("F j, Y – h:i A", $evaluation_time) . ", but it is still set as active.";
        $status = "In Progress";
    } elseif ($is_active == 0 && $evaluation_time >= $current_time) {
        $evaluation_end = "Evaluation is open until " . date("F j, Y – h:i A", $evaluation_time) . ", but it is set as closed.";
        $status = "Closed";
    } else {
        $evaluation_end = "Evaluation ended at " . date("F j, Y – h:i A", $evaluation_time);
        $status = "Closed";
    }

    // Fetch total number of students for the active academic year
    $total_students_query = "
        SELECT COUNT(DISTINCT ser.student_id) AS total_students
        FROM students_eval_restriction ser
        JOIN evaluation_list el ON ser.evaluation_id = el.evaluation_id
        WHERE el.acad_year_id = '$acad_year_id'
    ";
    $total_students_result = mysqli_query($conn, $total_students_query);

    if ($total_students_result) {
        $total_students_row = mysqli_fetch_assoc($total_students_result);
        $total_students = $total_students_row['total_students'];
    } else {
        $total_students = 0;
    }

    // Fetch total number of teacher-subject combos for the active academic year
    $total_teachers_query = "
        SELECT COUNT(DISTINCT CONCAT(ste.teacher_id, '-', ste.subject_id)) AS total_teachers
        FROM subject_to_eval ste
        JOIN evaluation_list el ON ste.evaluation_id = el.evaluation_id
        WHERE el.acad_year_id = '$acad_year_id'
    ";
    $total_teachers_result = mysqli_query($conn, $total_teachers_query);

    if ($total_teachers_result) {
        $total_teachers_row = mysqli_fetch_assoc($total_teachers_result);
        $total_teachers = $total_teachers_row['total_teachers'];
    } else {
        $total_teachers = 0;
    }

    // Fetch top 10 teachers for the active academic year
    $query = "
        SELECT 
            ta.teacher_id, 
            ta.firstName, 
            ta.lastName,
            AVG(ea.rating) AS average_rating
        FROM evaluation_answers ea
        JOIN evaluation_list el ON ea.evaluation_id = el.evaluation_id
        JOIN teacher_account ta ON ea.teacher_id = ta.teacher_id
        WHERE el.acad_year_id = '$acad_year_id'
        GROUP BY ta.teacher_id
        ORDER BY average_rating DESC
        LIMIT 10
    ";
    $result = mysqli_query($conn, $query);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $average_rating = $row['average_rating'];
            $rounded_rating = ceil($average_rating * 100) / 100;
            $row['average_rating'] = number_format($rounded_rating, 2);
            $top_teachers[] = $row;
        }
    }

    // Fetch sentiment data for the active academic year
    $sentiment_query = "
        SELECT sentiment_type, COUNT(*) as count 
        FROM evaluation_sentiments es
        JOIN evaluation_list el ON es.evaluation_id = el.evaluation_id
        WHERE el.acad_year_id = '$acad_year_id'
        GROUP BY sentiment_type";
    $sentiment_result = mysqli_query($conn, $sentiment_query);

    // Initialize counters
    $total_sentiments = 0;
    $positive_count = 0;
    $neutral_count = 0;
    $negative_count = 0;

    while ($row = mysqli_fetch_assoc($sentiment_result)) {
        $total_sentiments += $row['count'];
        if ($row['sentiment_type'] == 2) {
            $positive_count = $row['count'];
        } elseif ($row['sentiment_type'] == 1) {
            $neutral_count = $row['count'];
        } elseif ($row['sentiment_type'] == 0) {
            $negative_count = $row['count'];
        }
    }

    // Calculate percentages
    $positive_percentage = ($total_sentiments > 0) ? ($positive_count / $total_sentiments) * 100 : 0;
    $neutral_percentage = ($total_sentiments > 0) ? ($neutral_count / $total_sentiments) * 100 : 0;
    $negative_percentage = ($total_sentiments > 0) ? ($negative_count / $total_sentiments) * 100 : 0;

    // Fetch Likert scale data for the active academic year
    $likert_query = "
        SELECT ea.rating, COUNT(*) as count
        FROM evaluation_answers ea
        JOIN evaluation_list el ON ea.evaluation_id = el.evaluation_id
        WHERE el.acad_year_id = '$acad_year_id'
        GROUP BY ea.rating
    ";
    $likert_result = mysqli_query($conn, $likert_query);
    $likert_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];  // Initialize counts for each Likert rating

    while ($row = mysqli_fetch_assoc($likert_result)) {
        $rating = $row['rating'];
        $count = $row['count'];
        $likert_counts[$rating] = $count;  // Update counts
    }

    // Fetch evaluation counts by grade level for the selected academic year
    $evaluation_counts = [
        '7' => 0,
        '8' => 0,
        '9' => 0,
        '10' => 0,
        '11' => 0,
        '12' => 0
    ];

    $grade_query = "
        SELECT cl.grade_level, COUNT(DISTINCT ea.student_id) AS student_count
        FROM evaluation_answers ea
        JOIN evaluation_list el ON ea.evaluation_id = el.evaluation_id
        JOIN class_list cl ON el.class_id = cl.class_id
        WHERE el.acad_year_id = '$acad_year_id'
        GROUP BY cl.grade_level
    ";
    $grade_result = mysqli_query($conn, $grade_query);

    while ($row = mysqli_fetch_assoc($grade_result)) {
        $grade_level = $row['grade_level'];
        $student_count = $row['student_count'];
        if (isset($evaluation_counts[$grade_level])) {
            $evaluation_counts[$grade_level] = $student_count;
        }
    }

    // Fetch total evaluations for the selected academic year
    $total_evaluations_query = "
        SELECT COUNT(DISTINCT ea.student_id, ea.teacher_id, ea.subject_id) AS total_evaluations
        FROM evaluation_answers ea
        JOIN evaluation_list el ON ea.evaluation_id = el.evaluation_id
        WHERE el.acad_year_id = '$acad_year_id'
    ";
    $total_evaluations_result = mysqli_query($conn, $total_evaluations_query);

    if ($total_evaluations_result) {
        $total_evaluations_row = mysqli_fetch_assoc($total_evaluations_result);
        $total_evaluations = $total_evaluations_row['total_evaluations'];
    }

} else {
    // If no active academic year found
    $no_active_year_error = "There’s no active academic year in the database. Please review the data.";
}

// Handle AJAX request for academic year and quarter change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['year']) && isset($_POST['quarter'])) {
    include('../db_conn.php');  // Include your database connection again for AJAX requests

    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $quarter = mysqli_real_escape_string($conn, $_POST['quarter']);

    $query = "SELECT acad_year_id, evaluation_period, is_active FROM academic_year WHERE year='$year' AND quarter='$quarter' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        $acad_year_id = $data['acad_year_id'];
        $evaluation_time = strtotime($data['evaluation_period']);
        $current_time = time();

        if ($data['is_active'] == 1 && $evaluation_time >= $current_time) {
            $evaluation_end = "Evaluation is open until " . date("F j, Y – h:i A", $evaluation_time);
            $status = "In Progress";
        } elseif ($data['is_active'] == 1 && $evaluation_time < $current_time) {
            $evaluation_end = "Evaluation ended at " . date("F j, Y – h:i A", $evaluation_time) . ", but it is still set as active.";
            $status = "In Progress";
        } elseif ($data['is_active'] == 0 && $evaluation_time >= $current_time) {
            $evaluation_end = "Evaluation is open until " . date("F j, Y – h:i A", $evaluation_time) . ", but it is set as closed.";
            $status = "Closed";
        } else {
            $evaluation_end = "Evaluation ended at " . date("F j, Y – h:i A", $evaluation_time);
            $status = "Closed";
        }

        // Fetch total number of students for the selected academic year
        $total_students_query = "
            SELECT COUNT(DISTINCT ser.student_id) AS total_students
            FROM students_eval_restriction ser
            JOIN evaluation_list el ON ser.evaluation_id = el.evaluation_id
            WHERE el.acad_year_id = '$acad_year_id'
        ";
        $total_students_result = mysqli_query($conn, $total_students_query);

        if ($total_students_result) {
            $total_students_row = mysqli_fetch_assoc($total_students_result);
            $total_students = $total_students_row['total_students'];
        } else {
            $total_students = 0;
        }

        // Fetch total number of teacher-subject combos for the selected academic year
        $total_teachers_query = "
            SELECT COUNT(DISTINCT CONCAT(ste.teacher_id, '-', ste.subject_id)) AS total_teachers
            FROM subject_to_eval ste
            JOIN evaluation_list el ON ste.evaluation_id = el.evaluation_id
            WHERE el.acad_year_id = '$acad_year_id'
        ";
        $total_teachers_result = mysqli_query($conn, $total_teachers_query);

        if ($total_teachers_result) {
            $total_teachers_row = mysqli_fetch_assoc($total_teachers_result);
            $total_teachers = $total_teachers_row['total_teachers'];
        } else {
            $total_teachers = 0;
        }

        // Fetch top 10 teachers for the selected academic year
        $top_teachers = [];
        $teacher_query = "
            SELECT 
                ta.teacher_id, 
                ta.firstName, 
                ta.lastName,
                AVG(ea.rating) AS average_rating
            FROM evaluation_answers ea
            JOIN evaluation_list el ON ea.evaluation_id = el.evaluation_id
            JOIN teacher_account ta ON ea.teacher_id = ta.teacher_id
            WHERE el.acad_year_id = '$acad_year_id'
            GROUP BY ta.teacher_id
            ORDER BY average_rating DESC
            LIMIT 10
        ";
        $teacher_result = mysqli_query($conn, $teacher_query);

        if ($teacher_result) {
            while ($teacher_row = mysqli_fetch_assoc($teacher_result)) {
                $average_rating = $teacher_row['average_rating'];
                $rounded_rating = ceil($average_rating * 100) / 100;
                $teacher_row['average_rating'] = number_format($rounded_rating, 2);
                $top_teachers[] = $teacher_row;
            }
        }

        // Fetch sentiment data for the selected academic year
        $sentiment_query = "
            SELECT sentiment_type, COUNT(*) as count 
            FROM evaluation_sentiments es
            JOIN evaluation_list el ON es.evaluation_id = el.evaluation_id
            WHERE el.acad_year_id = '$acad_year_id'
            GROUP BY sentiment_type";
        $sentiment_result = mysqli_query($conn, $sentiment_query);

        // Initialize counters
        $total_sentiments = 0;
        $positive_count = 0;
        $neutral_count = 0;
        $negative_count = 0;

        // Process sentiment result
        while ($row = mysqli_fetch_assoc($sentiment_result)) {
            $total_sentiments += $row['count'];
            if ($row['sentiment_type'] == 2) {
                $positive_count = $row['count'];
            } elseif ($row['sentiment_type'] == 1) {
                $neutral_count = $row['count'];
            } elseif ($row['sentiment_type'] == 0) {
                $negative_count = $row['count'];
            }
        }

        // Calculate percentages
        $positive_percentage = ($total_sentiments > 0) ? ($positive_count / $total_sentiments) * 100 : 0;
        $neutral_percentage = ($total_sentiments > 0) ? ($neutral_count / $total_sentiments) * 100 : 0;
        $negative_percentage = ($total_sentiments > 0) ? ($negative_count / $total_sentiments) * 100 : 0;

        // Fetch Likert scale data for the selected academic year
        $likert_query = "
            SELECT rating, COUNT(*) as count 
            FROM evaluation_answers ea
            JOIN evaluation_list el ON ea.evaluation_id = el.evaluation_id
            WHERE el.acad_year_id = '$acad_year_id'
            GROUP BY rating
        ";

        $likert_result = mysqli_query($conn, $likert_query);
        $likert_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];  // Initialize counts for each Likert rating

        while ($row = mysqli_fetch_assoc($likert_result)) {
            $rating = $row['rating'];
            $count = $row['count'];
            $likert_counts[$rating] = $count;  // Update counts
        }

        // Fetch evaluation counts by grade level for the selected academic year
        $evaluation_counts = [
            '7' => 0,
            '8' => 0,
            '9' => 0,
            '10' => 0,
            '11' => 0,
            '12' => 0
        ];

        $grade_query = "
            SELECT cl.grade_level, COUNT(DISTINCT ea.student_id) AS student_count
            FROM evaluation_answers ea
            JOIN evaluation_list el ON ea.evaluation_id = el.evaluation_id
            JOIN class_list cl ON el.class_id = cl.class_id
            WHERE el.acad_year_id = '$acad_year_id'
            GROUP BY cl.grade_level
        ";
        $grade_result = mysqli_query($conn, $grade_query);

        while ($row = mysqli_fetch_assoc($grade_result)) {
            $grade_level = $row['grade_level'];
            $student_count = $row['student_count'];
            if (isset($evaluation_counts[$grade_level])) {
                $evaluation_counts[$grade_level] = $student_count;
            }
        }

        // Fetch total evaluations for the selected academic year
        $total_evaluations_query = "
            SELECT COUNT(DISTINCT ea.student_id, ea.teacher_id, ea.subject_id) AS total_evaluations
            FROM evaluation_answers ea
            JOIN evaluation_list el ON ea.evaluation_id = el.evaluation_id
            WHERE el.acad_year_id = '$acad_year_id'
        ";
        $total_evaluations_result = mysqli_query($conn, $total_evaluations_query);

        if ($total_evaluations_result) {
            $total_evaluations_row = mysqli_fetch_assoc($total_evaluations_result);
            $total_evaluations = $total_evaluations_row['total_evaluations'];
        } else {
            $total_evaluations = 0;
        }

        // Send a JSON response
        echo json_encode([
            "evaluation_end" => $evaluation_end,
            "status" => $status,
            "positive_percentage" => round($positive_percentage, 2),
            "neutral_percentage" => round($neutral_percentage, 2),
            "negative_percentage" => round($negative_percentage, 2),
            "top_teachers" => $top_teachers,
            "likert_counts" => $likert_counts,  // Return Likert counts for updating the UI
            "evaluation_counts" => $evaluation_counts, // Return evaluation counts by grade level
            "total_evaluations" => $total_evaluations,  // Include total evaluations in the response
            "total_students" => $total_students,  // Include total students in the response
            "total_teachers" => $total_teachers   // Include total teachers in the response
        ]);
        
        exit;  // Stop further execution after handling the AJAX request
    } else {
        echo json_encode([
            "evaluation_end" => "Unknown",
            "status" => "Unknown",
            "positive_percentage" => 0,
            "neutral_percentage" => 0,
            "negative_percentage" => 0,
            "top_teachers" => [],
            "likert_counts" => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
            "evaluation_counts" => ['7' => 0, '8' => 0, '9' => 0, '10' => 0, '11' => 0, '12' => 0], // Default to zero counts
            "total_evaluations" => 0,
            "total_students" => 0,
            "total_teachers" => 0
        ]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <title>Dashboard</title>
    <?php include('../header.php'); ?>
    <link rel="stylesheet" href="../css/sidebar.css" />
</head>

<body>
    <?php include('navigation.php'); ?>
    <style>
        .container-fluid2 {
            padding-left: 30px;
            padding-right: 15px;
        }

        .classTitle {
            margin-top: 3%;
            padding-top: 2%;
        }

        @media (max-width: 767px) { 
            .classTitle {
                margin-top: 10%;
                padding-top: 6%;
            }
        }

        .equal-height {
            display: flex;
            align-items: flex-start;
        }

        .equal-height .card {
            flex: 1;
        }

        .card-center {
            text-align: center;
        }

        /* Styles for Top 10 Faculty Members */
        .top-teachers-card {
            background: #fff;
            color: #000; /* Set text color to black */
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.2s;
            height: auto;  
        }

        .top-teachers-card:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .top-teachers-table {
            margin-bottom: 0;
        }

        .top-teachers-table td, .top-teachers-table th {
            color: #000;
        }

        /* Adjust table row backgrounds for contrast */
        .top-teachers-table tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .top-teachers-table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.1);
        }

        .top-teachers-table td:first-child {
            font-weight: bold;
            font-size: 1.1em;
        }

        .hover-pop-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .hover-pop-card:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        
        /* Keyframes para sa bounce-in animation ng numbers */
        @keyframes bounceIn {
            0% {
                opacity: 0;          /* Naka-invisible sa simula */
                transform: scale(0.5); /* Mas maliit kesa actual size para may bounce effect */
            }
            60% {
                opacity: 1;          /* Nagfa-fade in para maging visible */
                transform: scale(1.2); /* Lumalaki ng konti para magmukhang tumatalbog */
            }
            100% {
                transform: scale(1);   /* Babalik sa normal na laki */
            }
        }

        /* Keyframes para sa rotate pop animation ng icons */
        @keyframes rotatePop {
            0% {
                opacity: 0;                /* Di muna kita sa umpisa */
                transform: scale(0.5) rotate(-15deg); /* Mas maliit at medyo naka-rotate */
            }
            60% {
                opacity: 1;                /* Biglang kita na */
                transform: scale(1.1) rotate(10deg);  /* Lumalaki ng konti at umiikot */
            }
            100% {
                transform: scale(1) rotate(0deg); /* Babalik sa normal size at position */
            }
        }

        /* Keyframes para sa fade-in animation ng titles */
        @keyframes fadeInTitle {
            0% {
                opacity: 0;                /* Di kita sa umpisa */
                transform: translateY(10px); /* Nasa baba ng kaunti */
            }
            100% {
                opacity: 1;                /* Kita na */
                transform: translateY(0);    /* Nasa tamang position na */
            }
        }

        /* Bounce-in effect para sa mga numbers */
        .bounce-in {
            animation: bounceIn 0.8s ease-out forwards; /* Gamitin ang bounceIn animation */
        }

        /* Rotating pop effect para sa mga icons, may konting delay */
        .rotate-pop {
            animation: rotatePop 0.8s ease-out 0.3s forwards; /* Gamitin ang rotatePop na may delay */
        }

        /* Fade-in effect para sa mga title na may delay rin */
        .fade-in-title {
            animation: fadeInTitle 0.8s ease-out 0.5s forwards; /* Gamitin ang fadeInTitle na may delay */
            opacity: 0; /* then Start hidden ang mga title para smooth yung pag-appear */
        }
    </style>
    
    <p class="classTitle fs-4 fw-bold ms-5 mb-2">Dashboard</p>
    <div class="mx-auto mb-4" style="height: 2px; background-color: #facc15; width:95%;"></div>
    
    <div class="container-fluid2">
        <div class="row g-2 equal-height d-flex">
            <div class="col-lg-9 col-md-12 d-flex"> 
                <div class="card p-4 mb-3 flex-fill shadow"> 
                    <div id="dynamicContent">
                        <div class="border shadow rounded p-3 mb-4">
                            <p class="mb-3 fs-4 fw-bold">Welcome back, <?php echo htmlspecialchars($admin_first_name); ?>!</p>
                            <?php if (isset($multiple_active_years_error)): ?>
                                <p class="text-danger"><?php echo $multiple_active_years_error; ?></p>
                            <?php elseif (isset($no_active_year_error)): ?>
                                <p class="text-danger"><?php echo $no_active_year_error; ?></p>
                            <?php else: ?>
                                <p class="mb-1" id="academicYearText">Academic Year: <?php echo $active_year; ?> - Quarter <?php echo $active_quarter; ?></p>
                                <p class="mb-1" id="evaluationEndText"><?php echo $evaluation_end; ?></p>
                                <p class="mb-3">Evaluation Status: <span id="evaluationStatus" class="<?php 
                                    if ($status === 'In Progress') echo 'text-success fw-bold';
                                    else if ($status === 'Closed') echo 'text-danger fw-bold';
                                    else echo 'text-muted';
                                ?>"><?php echo $status; ?></span></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center align-items-center mb-4">
                        <label for="academicYear" class="form-label me-2">Select Academic Year:</label>
                        <select id="academicYearSelect" class="form-select" style="width: 250px;" aria-label="Select Academic Year">
                            <?php
                                $query = "SELECT year, quarter, is_active FROM academic_year ORDER BY year DESC, quarter DESC";
                                $result = mysqli_query($conn, $query);

                                while ($row = mysqli_fetch_assoc($result)) {
                                    $year = $row['year'];
                                    $quarter = $row['quarter'];
                                    $option_value = $year . "|" . $quarter;
                                    $option_text = $year . " - Quarter " . $quarter;
                                    $selected = ($row['is_active'] == 1) ? ' selected' : ''; // Auto-select the active academic year

                                    echo "<option value='" . $option_value . "'" . $selected . ">" . $option_text . "</option>";
                                }
                            ?>
                        </select>
                    </div>


                    <div class="row text-center">
                        <!-- Student Sentiments Card -->
                        <div class="col-sm-4 mb-3">
                            <div class="card fade-in">
                                <div class="card-body">
                                    <div class="container border rounded p-2 text-center d-flex flex-column justify-content-center mb-4" style="height: 50px;">
                                        <h5 class="fw-bold">Student Sentiments</h5>
                                    </div>
                                    <p id="sentimentNoDataMessage" style="display: none;">No student sentiments available for this academic year.</p>
                                    <canvas id="sentimentDonutChart" width="300" height="300"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Likert Scale Analysis Card -->
                        <div class="col-sm-4 mb-3">
                            <div class="card fade-in">
                                <div class="card-body">
                                    <div class="container border rounded text-center d-flex flex-column justify-content-center mb-4" style="height: 50px;">
                                        <h5 class="fw-bold">Likert Scale Analysis</h5>
                                    </div>
                                    <p id="likertNoDataMessage" style="display: none;">No Likert scale data available for this academic year.</p>
                                    <canvas id="likertBarChart" width="300" height="300"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Student Evaluation Counts Pie Chart -->
                        <div class="col-sm-4 mb-3">
                            <div class="card fade-in">
                                <div class="card-body">
                                    <div class="container border rounded text-center d-flex flex-column justify-content-center mb-4" style="height: 50px;">
                                        <h5 class="fw-bold">Total Student Evaluation for Each Grade Level</h5>
                                    </div>
                                    <p id="evaluationNoDataMessage" style="display: none;">No student evaluation data available for this academic year.</p>
                                    <canvas id="evaluationPieChart" width="300" height="300"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Total Evaluations Card -->
                        <div class="col-sm-4 mb-3 d-flex align-items-stretch"> 
                            <div class="hover-pop-card" style="border: 1px solid #E0E0E0; border-radius: 10px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); padding: 10px; width: 100%;">
                                <div class="card-body d-flex justify-content-center align-items-center mt-4" style="padding: 0;">
                                    <div style="margin-right: 10px;">
                                        <p class="mb-0 bounce-in" id="totalEvaluations" style="font-weight: bold; font-size: 3rem; line-height: 1;"><?php echo $total_evaluations; ?></p>
                                        <p class="card-title fade-in-title" style="font-weight: bold; font-size: 1.2rem; margin-bottom: 0;">Total Evaluations</p>
                                    </div>
                                    <i class="fa-solid fa-clipboard-list rotate-pop" style="font-size: 3.5rem; color: #333;"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Total Teachers Card -->
                        <div class="col-sm-4 mb-3 d-flex align-items-stretch">
                            <div class="hover-pop-card" style="border: 1px solid #E0E0E0; border-radius: 10px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); padding: 10px; width: 100%;">
                                <div class="card-body d-flex justify-content-center align-items-center mt-4" style="padding: 0;">
                                    <div style="margin-right: 10px;">
                                        <p class="mb-0 bounce-in" id="totalTeachers" style="font-weight: bold; font-size: 3rem; line-height: 1;"><?php echo $total_teachers; ?></p> 
                                        <p class="card-title fade-in-title" style="font-weight: bold; font-size: 1.2rem; margin-bottom: 0;">Total Teachers</p>
                                    </div>
                                    <i class="fa-solid fa-chalkboard-user rotate-pop" style="font-size: 3.5rem; color: #333;"></i> 
                                </div>
                            </div>
                        </div>

                        <!-- Total Students Card -->
                        <div class="col-sm-4 mb-3 d-flex align-items-stretch">
                            <div class="hover-pop-card" style="border: 1px solid #E0E0E0; border-radius: 10px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); padding: 10px; width: 100%;">
                                <div class="card-body d-flex justify-content-center align-items-center" style="padding: 0;">
                                    <div class="mt-3" style="margin-right: 10px;">
                                        <p class="mb-0 bounce-in" id="totalStudents" style="font-weight: bold; font-size: 3rem; line-height: 1;"><?php echo $total_students; ?></p> 
                                        <p class="card-title mt-2 fade-in-title" style="font-weight: bold; font-size: 1.2rem; margin-bottom: 0;">Total Students</p> 
                                    </div>
                                    <i class="bi bi-backpack-fill rotate-pop" style="font-size: 3.5rem; color: #333;"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Present Employees Today Card -->
                        <div class="col-sm-4 mb-3 d-flex align-items-stretch">
                            <div class="hover-pop-card" style="border: 1px solid #E0E0E0; border-radius: 10px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); padding: 10px; width: 100%;">
                                <div class="card-body d-flex justify-content-center align-items-center" style="padding: 0;">
                                    <div class="mt-2" style="margin-right: 10px;">
                                        <p class="mb-0 bounce-in" id="presentEmployeesToday" style="font-weight: bold; font-size: 3rem; line-height: 1;"><?php echo $present_employees_today; ?></p> 
                                        <p class="card-title mt-2 fade-in-title" style="font-weight: bold; font-size: 1.2rem; margin-bottom: 0;">Present Employees Today</p> 
                                    </div>
                                    <i class="fa-solid fa-users rotate-pop" style="font-size: 3.5rem; color: #333;"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Total Subjects Card -->
                        <div class="col-sm-4 mb-3 d-flex align-items-stretch">
                            <div class="hover-pop-card" style="border: 1px solid #E0E0E0; border-radius: 10px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); padding: 10px; width: 100%;">
                                <div class="card-body d-flex justify-content-center align-items-center mt-3" style="padding: 0;">
                                    <div style="margin-right: 10px;">
                                        <p class="mb-0 bounce-in" style="font-weight: bold; font-size: 3rem; line-height: 1;"><?php echo $total_subjects; ?></p> 
                                        <p class="card-title fade-in-title" style="font-weight: bold; font-size: 1.2rem; margin-bottom: 0;">Total Subjects</p>
                                    </div>
                                    <i class="fa-solid fa-book rotate-pop" style="font-size: 3.5rem; color: #333;"></i> 
                                </div>
                            </div>
                        </div>

                        <!-- Total Staff Card  -->
                        <div class="col-sm-4 mb-3 d-flex align-items-stretch">
                            <div class="hover-pop-card" style="border: 1px solid #E0E0E0; border-radius: 10px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); padding: 10px; width: 100%;">
                                <div class="card-body d-flex justify-content-center align-items-center mt-3" style="padding: 0;">
                                    <div style="margin-right: 10px;">
                                        <p class="mb-0 bounce-in" style="font-weight: bold; font-size: 3rem; line-height: 1;"><?php echo $total_staff; ?></p> 
                                        <p class="card-title fade-in-title" style="font-weight: bold; font-size: 1.2rem; margin-bottom: 0;">Total Staff</p> 
                                    </div>
                                    <i class="fa-solid fa-id-card-clip rotate-pop" style="font-size: 3.5rem; color: #333;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Section: Top 10 Faculty Members -->
            <div class="col-lg-3 col-md-12 d-flex"> 
                <div class="card p-4 flex-fill shadow top-teachers-card">
                    <h5 class="card-center mb-4">
                        Top 10 Highest-Rated Faculties for  AY <?php echo $active_year; ?> - Quarter <?php echo $active_quarter; ?>
                    </h5>

                    <table class="table table-striped table-hover text-center align-middle top-teachers-table">
                        <tbody>
                            <?php
                            $rank = 1;
                            foreach ($top_teachers as $teacher):
                                $full_name = $teacher['firstName'] . ' ' . $teacher['lastName'];
                            ?>
                            <tr>
                                <td><?php echo $rank; ?></td>
                                <td>
                                    <?php 
                                    if ($rank == 1) {
                                        echo '<i class="fa-solid fa-crown text-warning"></i> ';
                                    }
                                    echo htmlspecialchars($full_name); 
                                    ?>
                                </td>

                                <td><?php echo $teacher['average_rating']; ?></td>
                            </tr>
                            <?php
                                $rank++;
                            endforeach;

                            // If there are less than 10 teachers, fill the rest with empty rows
                            for (; $rank <= 10; $rank++):
                            ?>
                            <tr>
                                <td><?php echo $rank; ?></td>
                                <td>-</td>
                                <td>-</td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

    <script>
        $(document).ready(function () {
            let sentimentChart;
            let likertChart;
            let evaluationPieChart;

            // Trigger data fetch on page load for the selected (active) academic year
            var selectedValue = $('#academicYearSelect').val();
            if (selectedValue) {
                var splitValue = selectedValue.split("|");
                var year = splitValue[0];
                var quarter = splitValue[1];

                fetchDataAndUpdateCharts(year, quarter);
            }

            // Handle change of academic year in the dropdown
            $('#academicYearSelect').change(function () {
                var selectedValue = $(this).val();
                var splitValue = selectedValue.split("|");
                var year = splitValue[0];
                var quarter = splitValue[1];

                fetchDataAndUpdateCharts(year, quarter);
            });

            // Function to fetch data and update charts
            function fetchDataAndUpdateCharts(year, quarter) {
                $.ajax({
                    url: 'admin-dashboard.php',
                    type: 'POST',
                    data: { year: year, quarter: quarter },
                    success: function (response) {
                        var data = JSON.parse(response);
                        $('#academicYearText').text("Academic Year: " + year + " - Quarter " + quarter);
                        $('#evaluationEndText').text(data.evaluation_end);
                        $('#evaluationStatus').text(data.status);

                        if (data.status === "In Progress") {
                            $('#evaluationStatus').attr('class', 'text-success fw-bold');
                        } else if (data.status === "Closed") {
                            $('#evaluationStatus').attr('class', 'text-danger fw-bold');
                        } else {
                            $('#evaluationStatus').attr('class', 'text-muted');
                        }

                        // Update the total evaluations card
                        $('#totalEvaluations').text(data.total_evaluations);
                        $('#totalStudents').text(data.total_students);  // Display total students for selected academic year
                        $('#totalTeachers').text(data.total_teachers);  // Display total teachers for selected academic year

                        // Handle Student Sentiments Chart
                        if (data.positive_percentage === 0 && data.neutral_percentage === 0 && data.negative_percentage === 0) {
                            if (sentimentChart) {
                                sentimentChart.destroy();
                            }
                            $('#sentimentDonutChart').hide();
                            $('#sentimentNoDataMessage').show();
                        } else {
                            $('#sentimentNoDataMessage').hide();
                            $('#sentimentDonutChart').show();
                            createDonutChart(data.positive_percentage, data.neutral_percentage, data.negative_percentage);
                        }

                        // Handle Likert Scale Analysis Chart
                        if (Object.values(data.likert_counts).every(count => count === 0)) {
                            if (likertChart) {
                                likertChart.destroy();
                            }
                            $('#likertBarChart').hide();
                            $('#likertNoDataMessage').show();
                        } else {
                            $('#likertNoDataMessage').hide();
                            $('#likertBarChart').show();
                            createLikertChart(data.likert_counts);
                        }

                        // Handle Total Student Evaluation Chart
                        if (Object.values(data.evaluation_counts).every(count => count === 0)) {
                            if (evaluationPieChart) {
                                evaluationPieChart.destroy();
                            }
                            $('#evaluationPieChart').hide();
                            $('#evaluationNoDataMessage').show();
                        } else {
                            $('#evaluationNoDataMessage').hide();
                            $('#evaluationPieChart').show();
                            createEvaluationPieChart(data.evaluation_counts);
                        }

                        // Update the top 10 teachers table
                        updateTopTeachers(data.top_teachers);
                    }
                });
            }

            // Function to create the donut chart for sentiment data
            function createDonutChart(positive, neutral, negative) {
                var ctx = document.getElementById('sentimentDonutChart').getContext('2d');

                if (sentimentChart) {
                    sentimentChart.destroy(); // Destroy previous chart if it exists
                }

                var labels = [];
                var dataValues = [];
                var colors = [];

                if (positive > 0) {
                    labels.push('Positive');
                    dataValues.push(positive);
                    colors.push('#582f0e'); // Chocolate Brown
                }
                if (neutral > 0) {
                    labels.push('Neutral');
                    dataValues.push(neutral);
                    colors.push('#d4a276'); // Saddle Brown
                }
                if (negative > 0) {
                    labels.push('Negative');
                    dataValues.push(negative);
                    colors.push('#99582a'); // Copper Brown
                }

                sentimentChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Sentiment',
                            data: dataValues,
                            backgroundColor: colors,
                            borderColor: colors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    font: {
                                        family: 'Poppins',
                                        size: 14
                                    }
                                }
                            },
                            datalabels: {
                                color: '#fff',
                                formatter: function (value) {
                                    return value.toFixed(1) + '%';
                                },
                                font: {
                                    family: 'Poppins',
                                    weight: 'bold',
                                    size: 14
                                },
                                anchor: 'center',
                                align: 'center'
                            }
                        }
                    },
                    plugins: [ChartDataLabels]
                });
            }

            // Function to create the bar chart for Likert Scale analysis
            function createLikertChart(likert_counts) {
                var ctx = document.getElementById('likertBarChart').getContext('2d');

                if (likertChart) {
                    likertChart.destroy();
                }

                // Map short labels to full descriptions
                const labelDescriptions = {
                    'NI': 'Needs Improvement',
                    'F': 'Fair',
                    'S': 'Satisfactory',
                    'VS': 'Very Satisfactory',
                    'O': 'Outstanding'
                };

                likertChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['NI', 'F', 'S', 'VS', 'O'],  // Shortened labels
                        datasets: [{
                            label: 'Number of Responses',
                            data: [
                                likert_counts[1],  // Needs Improvement
                                likert_counts[2],  // Fair
                                likert_counts[3],  // Satisfactory
                                likert_counts[4],  // Very Satisfactory
                                likert_counts[5]   // Outstanding
                            ],
                            backgroundColor: [
                                '#582f0e',  // Chocolate Brown for 'Needs Improvement'
                                '#d4a276',  // Saddle Brown for 'Fair'
                                '#99582a',  // Copper Brown for 'Satisfactory'
                                '#432818',  // Coffee Brown for 'Very Satisfactory'
                                '#bb9457'   // Warm Tan for 'Outstanding'
                            ],
                            borderColor: [
                                '#582f0e',
                                '#d4a276',
                                '#99582a',
                                '#432818',
                                '#bb9457'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    title: function (context) {
                                        const label = context[0].label;
                                        return labelDescriptions[label] || label;
                                    },
                                    label: function (context) {
                                        return `Number of Responses: ${context.raw}`;
                                    }
                                }
                            },
                            datalabels: {
                                color: '#000',
                                anchor: 'end',
                                align: 'top',
                                formatter: Math.round,
                                font: {
                                    family: 'Poppins',
                                    size: 12
                                }
                            }
                        }
                    },
                    plugins: [ChartDataLabels]
                });
            }

            // Function to create the pie chart for student evaluation counts by grade level
            function createEvaluationPieChart(evaluation_counts) {
                var ctx = document.getElementById('evaluationPieChart').getContext('2d');

                if (evaluationPieChart) {
                    evaluationPieChart.destroy();
                }

                evaluationPieChart = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'],
                        datasets: [{
                            label: 'Evaluation Counts',
                            data: [
                                evaluation_counts['7'],
                                evaluation_counts['8'],
                                evaluation_counts['9'],
                                evaluation_counts['10'],
                                evaluation_counts['11'],
                                evaluation_counts['12']
                            ],
                            backgroundColor: [
                                '#582f0e',  // Chocolate Brown for Grade 7
                                '#d4a276',  // Saddle Brown for Grade 8
                                '#99582a',  // Copper Brown for Grade 9
                                '#432818',  // Coffee Brown for Grade 10
                                '#bb9457',  // Warm Tan for Grade 11
                                '#6f1d1b'   // Dark Brown for Grade 12
                            ],
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom',
                                labels: {
                                    font: {
                                        family: 'Poppins',
                                        size: 14
                                    }
                                }
                            },
                            datalabels: {
                                color: '#fff',
                                formatter: function (value) {
                                    return value > 0 ? value : '';
                                },
                                font: {
                                    family: 'Poppins',
                                    size: 14
                                }
                            }
                        }
                    },
                    plugins: [ChartDataLabels]
                });
            }

            // Function to update the top teachers table
            function updateTopTeachers(top_teachers) {
                var tbody = '';
                var rank = 1;

                if (top_teachers && top_teachers.length > 0) {
                    top_teachers.forEach(function (teacher) {
                        var fullName = teacher.firstName + ' ' + teacher.lastName;
                        var nameCell = '';
                        if (rank === 1) {
                            nameCell += '<i class="fa-solid fa-crown text-warning"></i> ';
                        }
                        nameCell += fullName;
                        tbody += '<tr>' +
                            '<td>' + rank + '</td>' +
                            '<td>' + nameCell + '</td>' +
                            '<td>' + teacher.average_rating + '</td>' +
                            '</tr>';
                        rank++;
                    });
                }

                // If there are less than 10 teachers, fill the rest with empty rows
                for (; rank <= 10; rank++) {
                    tbody += '<tr>' +
                        '<td>' + rank + '</td>' +
                        '<td>-</td>' +
                        '<td>-</td>' +
                        '</tr>';
                }

                // Update the table body
                $('.top-teachers-table tbody').html(tbody);
            }
        });
    </script>
</body>
</html>
