<?php
include('../db_conn.php');
session_start();

// Initialize variables
$multiple_active_years_error = null;
$no_active_year_error = null;
$active_year = 'Unknown';
$active_quarter = 'Unknown';
$evaluation_end = 'Unknown';
$status = 'Unknown';
$teacher_first_name = 'Teacher';
$top_teachers = [];
$sentiment_data = ['positive' => 0, 'neutral' => 0, 'negative' => 0];
$likert_scale_data = ['O' => 0, 'VS' => 0, 'S' => 0, 'F' => 0, 'NI' => 0];
$total_evaluations = 0;
$total_students = 0;
$total_classes = 0; // Initialize variable for total classes
$total_hours_worked_formatted = '0h 0m'; // Initialize total hours worked

// Fetch the logged-in teacher's first name and emp_code
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $teacher_query = "SELECT firstName, emp_code, status, break_time_id FROM teacher_account WHERE teacher_id = '$user_id' LIMIT 1";
    $teacher_result = mysqli_query($conn, $teacher_query);

    if ($teacher_result && mysqli_num_rows($teacher_result) > 0) {
        $teacher_row = mysqli_fetch_assoc($teacher_result);
        $teacher_first_name = $teacher_row['firstName'];
        $emp_code = $teacher_row['emp_code'];
        $teacher_status = $teacher_row['status'];
        $break_time_id = $teacher_row['break_time_id'];
    
    }
}

// If the request is an AJAX request to fetch academic year details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['year']) && isset($_POST['quarter'])) {
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $quarter = mysqli_real_escape_string($conn, $_POST['quarter']);

    $query = "SELECT * FROM academic_year WHERE year = '$year' AND quarter = '$quarter' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $acad_year_id = $row['acad_year_id'];
        $evaluation_time = strtotime($row['evaluation_period']);
        $current_time = time();
        
        if ($row['is_active'] == 1) {
            if ($evaluation_time >= $current_time) {
                $evaluation_end = "Evaluation is open until " . date("F j, Y – h:i A", $evaluation_time);
                $status = 'In Progress';
            } else {
                $evaluation_end = "Evaluation ended at " . date("F j, Y – h:i A", $evaluation_time) . ", but it is still set as active.";
                $status = 'In Progress';
            }
        } else {
            $evaluation_end = "Evaluation ended at " . date("F j, Y – h:i A", $evaluation_time);
            $status = 'Closed';
        }

        // Fetch evaluation IDs based on the selected academic year
        $evaluation_ids = [];
        $eval_query = "SELECT evaluation_id FROM evaluation_list WHERE acad_year_id = '$acad_year_id'";
        $eval_result = mysqli_query($conn, $eval_query);

        if ($eval_result) {
            while ($eval_row = mysqli_fetch_assoc($eval_result)) {
                $evaluation_ids[] = $eval_row['evaluation_id'];
            }
        }

        // Fetch evaluation IDs where the teacher is assigned
        $teacher_eval_ids = [];
        if (!empty($evaluation_ids)) {
            $eval_ids_str = implode(',', $evaluation_ids);
            $teacher_eval_query = "
                SELECT evaluation_id 
                FROM subject_to_eval 
                WHERE teacher_id = '$user_id' AND evaluation_id IN ($eval_ids_str)";
            $teacher_eval_result = mysqli_query($conn, $teacher_eval_query);

            if ($teacher_eval_result) {
                while ($teacher_eval_row = mysqli_fetch_assoc($teacher_eval_result)) {
                    $teacher_eval_ids[] = $teacher_eval_row['evaluation_id'];
                }
            }
        }

        // Fetch the total number of unique subjects (classes) for the teacher in the selected academic year
        if (!empty($teacher_eval_ids)) {
            $teacher_eval_ids_str = implode(',', $teacher_eval_ids);
            $total_classes_query = "
                SELECT COUNT(DISTINCT subject_id) AS total_classes
                FROM subject_to_eval
                WHERE teacher_id = '$user_id' AND evaluation_id IN ($teacher_eval_ids_str)";
            $total_classes_result = mysqli_query($conn, $total_classes_query);

            if ($total_classes_result && mysqli_num_rows($total_classes_result) > 0) {
                $total_classes_row = mysqli_fetch_assoc($total_classes_result);
                $total_classes = $total_classes_row['total_classes'];
            }

            // Fetch the total number of unique students for the teacher's evaluations
            $student_count_query = "
                SELECT COUNT(DISTINCT student_id) AS total_students
                FROM students_eval_restriction 
                WHERE evaluation_id IN ($teacher_eval_ids_str)";
            $student_count_result = mysqli_query($conn, $student_count_query);

            if ($student_count_result && mysqli_num_rows($student_count_result) > 0) {
                $student_count_row = mysqli_fetch_assoc($student_count_result);
                $total_students = $student_count_row['total_students'];
            }
        }

        // Fetch total evaluations based on the academic year and teacher ID
        $total_evaluations_query = "
            SELECT COUNT(DISTINCT ea.subject_id, ea.student_id) AS total_evaluations
            FROM evaluation_answers ea
            JOIN evaluation_list el ON ea.evaluation_id = el.evaluation_id
            WHERE el.acad_year_id = '$acad_year_id' AND ea.teacher_id = '$user_id'
        ";
        $total_evaluations_result = mysqli_query($conn, $total_evaluations_query);

        if ($total_evaluations_result && mysqli_num_rows($total_evaluations_result) > 0) {
            $evaluations_row = mysqli_fetch_assoc($total_evaluations_result);
            $total_evaluations = $evaluations_row['total_evaluations'];
        }

        // Fetch top 10 teachers for the selected academic year and quarter
        $top_teachers_query = "
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
        $top_teachers_result = mysqli_query($conn, $top_teachers_query);
        $top_teachers = [];

        if ($top_teachers_result) {
            while ($teacher_row = mysqli_fetch_assoc($top_teachers_result)) {
                $teacher_row['average_rating'] = number_format($teacher_row['average_rating'], 2);
                $top_teachers[] = $teacher_row;
            }
        }

        // Fetch sentiment data based on the selected academic year
        $sentiment_query = "
            SELECT sentiment_type, COUNT(*) AS count 
            FROM evaluation_sentiments es
            JOIN evaluation_list el ON es.evaluation_id = el.evaluation_id
            WHERE el.acad_year_id = '$acad_year_id' AND es.teacher_id = '$user_id'
            GROUP BY sentiment_type
        ";
        $sentiment_result = mysqli_query($conn, $sentiment_query);
        $total_sentiments = 0;

        if ($sentiment_result) {
            $sentiments = [0 => 0, 1 => 0, 2 => 0];
            while ($sentiment_row = mysqli_fetch_assoc($sentiment_result)) {
                $sentiments[$sentiment_row['sentiment_type']] = $sentiment_row['count'];
                $total_sentiments += $sentiment_row['count'];
            }
            
            if ($total_sentiments > 0) {
                $sentiment_data['negative'] = ($sentiments[0] / $total_sentiments) * 100;
                $sentiment_data['neutral'] = ($sentiments[1] / $total_sentiments) * 100;
                $sentiment_data['positive'] = ($sentiments[2] / $total_sentiments) * 100;
            }
        }

        // Fetch Likert Scale data for the selected academic year and teacher
        $likert_query = "
            SELECT rating, COUNT(*) AS count 
            FROM evaluation_answers ea
            JOIN evaluation_list el ON ea.evaluation_id = el.evaluation_id
            WHERE el.acad_year_id = '$acad_year_id' AND ea.teacher_id = '$user_id'
            GROUP BY rating
        ";
        $likert_result = mysqli_query($conn, $likert_query);

        if ($likert_result) {
            while ($likert_row = mysqli_fetch_assoc($likert_result)) {
                switch ($likert_row['rating']) {
                    case 5:
                        $likert_scale_data['O'] = $likert_row['count'];
                        break;
                    case 4:
                        $likert_scale_data['VS'] = $likert_row['count'];
                        break;
                    case 3:
                        $likert_scale_data['S'] = $likert_row['count'];
                        break;
                    case 2:
                        $likert_scale_data['F'] = $likert_row['count'];
                        break;
                    case 1:
                        $likert_scale_data['NI'] = $likert_row['count'];
                        break;
                }
            }
        }

        // Calculate total hours worked for the current month
        $total_hours_worked_formatted = '0h 0m';
        if (!empty($emp_code)) {
            $currentMonth = date("m");
            $currentYear = date("Y");

            // Fetch attendance records
            $attendanceData = [];

            $query = "SELECT DATE(punch_date_time) as day, punch_state, TIME(punch_date_time) as punch_time
                    FROM employee_attendance
                    WHERE emp_code = ? AND MONTH(punch_date_time) = ? AND YEAR(punch_date_time) = ?
                    ORDER BY punch_date_time";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("sii", $emp_code, $currentMonth, $currentYear);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $day = $row['day'];
                $punchState = $row['punch_state'];
                $punchTime = $row['punch_time'];

                if (!isset($attendanceData[$day])) {
                    $attendanceData[$day] = ['arrival' => null, 'departure' => null];
                }

                if ($punchState === 'Check Out' && $attendanceData[$day]['arrival'] === null) {
                    $attendanceData[$day]['arrival'] = $punchTime;
                }

                if ($punchState === 'Break Out') {
                    $attendanceData[$day]['departure'] = $punchTime;
                }
            }
            $stmt->close();

            // For Part-timer teachers, fetch their schedule
            $teacherSchedule = [];
            if ($teacher_status === 'Part-timer') {
                $query = "SELECT subject_code, class_title, time_start, time_end, day_of_week
                        FROM teacher_schedule
                        WHERE teacher_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                // Map day names to integers
                $daysMap = [
                    'Monday'    => 1,
                    'Tuesday'   => 2,
                    'Wednesday' => 3,
                    'Thursday'  => 4,
                    'Friday'    => 5,
                    'Saturday'  => 6,
                    'Sunday'    => 7
                ];

                while ($row = $result->fetch_assoc()) {
                    $dayName = $row['day_of_week'];
                    $dayOfWeek = isset($daysMap[$dayName]) ? $daysMap[$dayName] : 0;

                    if ($dayOfWeek > 0) {
                        if (!isset($teacherSchedule[$dayOfWeek])) {
                            $teacherSchedule[$dayOfWeek] = [];
                        }
                        $teacherSchedule[$dayOfWeek][] = $row;
                    }
                }
                $stmt->close();
            }

            // Fetch break time for Regular teachers
            $breakTimeMinutes = 0;
            $breakStart = null;
            $breakEnd = null;
            if ($teacher_status === 'Regular' && !empty($break_time_id)) {
                $query = "SELECT start_break, end_break FROM break_time_schedule WHERE break_time_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $break_time_id);
                $stmt->execute();
                $stmt->bind_result($start_break, $end_break);
                $stmt->fetch();
                $stmt->close();

                if ($start_break && $end_break) {
                    $breakStart = new DateTime($start_break);
                    $breakEnd = new DateTime($end_break);
                    $breakInterval = $breakStart->diff($breakEnd);
                    $breakTimeMinutes = ($breakInterval->h * 60) + $breakInterval->i;
                }
            }

            // Function to compute overlap in minutes between two time intervals
            function getTimeOverlapInMinutes($startA, $endA, $startB, $endB) {
                $latestStart = max($startA->getTimestamp(), $startB->getTimestamp());
                $earliestEnd = min($endA->getTimestamp(), $endB->getTimestamp());
                $overlap = $earliestEnd - $latestStart;
                if ($overlap > 0) {
                    return floor($overlap / 60);
                } else {
                    return 0;
                }
            }

            // Calculate total minutes worked for the month
            $totalMinutesForMonth = 0;

            foreach ($attendanceData as $date => $times) {
                $arrival = $times['arrival'];
                $departure = $times['departure'];

                if ($arrival === null) {
                    continue; // Skip computation if no arrival time
                }

                $currentDayOfWeek = date('N', strtotime($date)); // 1 (Monday) to 7 (Sunday)

                if ($teacher_status === 'Regular') {
                    // For Regular teachers
                    $schoolStart = new DateTime('07:30:00');
                    $schoolEnd = new DateTime('16:00:00');

                    $arrivalTime = new DateTime($arrival);
                    $departureTime = $departure ? new DateTime($departure) : clone $schoolEnd;

                    // Adjust for computation
                    if ($arrivalTime < $schoolStart) {
                        $startTime = clone $schoolStart;
                    } else {
                        $startTime = clone $arrivalTime;
                    }

                    if ($departureTime > $schoolEnd) {
                        $endTime = clone $schoolEnd;
                    } else {
                        $endTime = clone $departureTime;
                    }

                    // Set date for $startTime and $endTime
                    $startTime->setDate($currentYear, $currentMonth, intval(date('d', strtotime($date))));
                    $endTime->setDate($currentYear, $currentMonth, intval(date('d', strtotime($date))));

                    // Compute total worked hours
                    $interval = $startTime->diff($endTime);
                    $minutesForDay = ($interval->h * 60) + $interval->i;

                    // Compute overlap between working hours and break time
                    $overlapMinutes = 0;
                    if ($breakStart && $breakEnd) {
                        $breakStartForDay = clone $breakStart;
                        $breakEndForDay = clone $breakEnd;

                        // Set date for break times
                        $breakStartForDay->setDate($currentYear, $currentMonth, intval(date('d', strtotime($date))));
                        $breakEndForDay->setDate($currentYear, $currentMonth, intval(date('d', strtotime($date))));

                        $overlapMinutes = getTimeOverlapInMinutes($startTime, $endTime, $breakStartForDay, $breakEndForDay);
                    }

                    // Subtract overlapMinutes from $minutesForDay
                    $minutesForDay -= $overlapMinutes;
                    if ($minutesForDay < 0) $minutesForDay = 0;

                    // Cap at 510 minutes (8h 30m)
                    $minutesForDay = min($minutesForDay, 510);

                    $totalMinutesForMonth += $minutesForDay;

                } elseif ($teacher_status === 'Part-timer') {
                    // For Part-timer teachers
                    $scheduledClasses = isset($teacherSchedule[$currentDayOfWeek]) ? $teacherSchedule[$currentDayOfWeek] : [];

                    if (empty($scheduledClasses)) {
                        continue; // No scheduled classes
                    }

                    // Determine earliest and latest scheduled times
                    $earliestScheduledStart = null;
                    $latestScheduledEnd = null;
                    $totalScheduledMinutes = 0;

                    foreach ($scheduledClasses as $class) {
                        $classStart = new DateTime($class['time_start']);
                        $classEnd = new DateTime($class['time_end']);

                        if ($earliestScheduledStart === null || $classStart < $earliestScheduledStart) {
                            $earliestScheduledStart = clone $classStart;
                        }
                        if ($latestScheduledEnd === null || $classEnd > $latestScheduledEnd) {
                            $latestScheduledEnd = clone $classEnd;
                        }

                        $classInterval = $classStart->diff($classEnd);
                        $classMinutes = ($classInterval->h * 60) + $classInterval->i;
                        $totalScheduledMinutes += $classMinutes;
                    }

                    $arrivalTime = new DateTime($arrival);
                    $departureTime = $departure ? new DateTime($departure) : clone $latestScheduledEnd;

                    // Adjust for computation
                    if ($arrivalTime < $earliestScheduledStart) {
                        $startTime = clone $earliestScheduledStart;
                    } else {
                        $startTime = clone $arrivalTime;
                    }

                    if ($departureTime > $latestScheduledEnd) {
                        $endTime = clone $latestScheduledEnd;
                    } else {
                        $endTime = clone $departureTime;
                    }

                    // Compute late arrival and early departure
                    $lateMinutes = 0;
                    if ($arrivalTime > $earliestScheduledStart) {
                        $lateInterval = $earliestScheduledStart->diff($arrivalTime);
                        $lateMinutes = ($lateInterval->h * 60) + $lateInterval->i;
                    }

                    $earlyLeaveMinutes = 0;
                    if ($departureTime < $latestScheduledEnd) {
                        $earlyLeaveInterval = $departureTime->diff($latestScheduledEnd);
                        $earlyLeaveMinutes = ($earlyLeaveInterval->h * 60) + $earlyLeaveInterval->i;
                    }

                    // Compute total worked minutes
                    $workedMinutes = $totalScheduledMinutes - $lateMinutes - $earlyLeaveMinutes;
                    if ($workedMinutes < 0) $workedMinutes = 0;

                    $totalMinutesForMonth += $workedMinutes;
                }
            }

            // Convert total minutes to hours
            $total_hours_worked = floor($totalMinutesForMonth / 60);
            $remaining_minutes = $totalMinutesForMonth % 60;

            // Format total hours worked as a string, e.g., "52h 30m"
            $total_hours_worked_formatted = "{$total_hours_worked}h {$remaining_minutes}m";
        }

        // Return data as JSON for the AJAX call
        echo json_encode([
            "evaluation_end" => $evaluation_end,
            "status" => $status,
            "status_class" => ($status === 'In Progress') ? 'text-success fw-bold' : (($status === 'Closed') ? 'text-danger fw-bold' : 'text-muted'),
            "top_teachers" => $top_teachers,
            "sentiment_data" => $sentiment_data,
            "likert_scale_data" => $likert_scale_data,
            "total_evaluations" => $total_evaluations,
            "total_students" => $total_students,
            "total_classes" => $total_classes,
            "total_hours_worked" => $total_hours_worked_formatted // Include total hours worked in the response
        ]);
        exit;
    } else {
        echo json_encode([
            "evaluation_end" => "Unknown",
            "status" => "Unknown",
            "status_class" => 'text-muted',
            "top_teachers" => [],
            "sentiment_data" => $sentiment_data,
            "likert_scale_data" => $likert_scale_data,
            "total_evaluations" => 0,
            "total_students" => 0,
            "total_classes" => 0,
            "total_hours_worked" => '0h 0m' // Default value when no data is available
        ]);
        exit;
    }
}

// Fetch active academic year details initially
$active_query = "SELECT * FROM academic_year WHERE is_active = 1";
$active_result = mysqli_query($conn, $active_query);

if ($active_result && mysqli_num_rows($active_result) > 1) {
    $multiple_active_years_error = "There's more than 1 active academic year in the database. Please check the records.";
} elseif ($active_result && mysqli_num_rows($active_result) == 1) {
    $active_row = mysqli_fetch_assoc($active_result);
    $acad_year_id = $active_row['acad_year_id'];
    $active_year = $active_row['year'];
    $active_quarter = $active_row['quarter'];
    $evaluation_time = strtotime($active_row['evaluation_period']);
    $current_time = time();

    if ($active_row['is_active'] == 1) {
        if ($evaluation_time >= $current_time) {
            $evaluation_end = "Evaluation is open until " . date("F j, Y – h:i A", $evaluation_time);
            $status = 'In Progress';
        } else {
            $evaluation_end = "Evaluation ended at " . date("F j, Y – h:i A", $evaluation_time) . ", but it is still set as active.";
            $status = 'In Progress';
        }
    } else {
        $evaluation_end = "Evaluation ended at " . date("F j, Y – h:i A", $evaluation_time);
        $status = 'Closed';
    }

    // Fetch total evaluations for the active academic year and teacher
    $total_evaluations_query = "
        SELECT COUNT(DISTINCT ea.subject_id, ea.student_id) AS total_evaluations
        FROM evaluation_answers ea
        JOIN evaluation_list el ON ea.evaluation_id = el.evaluation_id
        WHERE el.acad_year_id = '$acad_year_id' AND ea.teacher_id = '$user_id'
    ";
    $total_evaluations_result = mysqli_query($conn, $total_evaluations_query);

    if ($total_evaluations_result && mysqli_num_rows($total_evaluations_result) > 0) {
        $evaluations_row = mysqli_fetch_assoc($total_evaluations_result);
        $total_evaluations = $evaluations_row['total_evaluations'];
    }

    // Fetch total students handled
    $teacher_eval_ids = [];
    $evaluation_ids = []; // Initialize evaluation_ids array
    $eval_query = "SELECT evaluation_id FROM evaluation_list WHERE acad_year_id = '$acad_year_id'";
    $eval_result = mysqli_query($conn, $eval_query);

    if ($eval_result) {
        while ($eval_row = mysqli_fetch_assoc($eval_result)) {
            $evaluation_ids[] = $eval_row['evaluation_id'];
        }
        if (!empty($evaluation_ids)) {
            $eval_ids_str = implode(',', $evaluation_ids);
            $teacher_eval_query = "
                SELECT evaluation_id 
                FROM subject_to_eval 
                WHERE teacher_id = '$user_id' AND evaluation_id IN ($eval_ids_str)";
            $teacher_eval_result = mysqli_query($conn, $teacher_eval_query);

            if ($teacher_eval_result) {
                while ($teacher_eval_row = mysqli_fetch_assoc($teacher_eval_result)) {
                    $teacher_eval_ids[] = $teacher_eval_row['evaluation_id'];
                }
            }

            // Fetch the total number of unique subjects (classes) for the teacher
            if (!empty($teacher_eval_ids)) {
                $teacher_eval_ids_str = implode(',', $teacher_eval_ids);
                $total_classes_query = "
                    SELECT COUNT(DISTINCT subject_id) AS total_classes
                    FROM subject_to_eval
                    WHERE teacher_id = '$user_id' AND evaluation_id IN ($teacher_eval_ids_str)";
                $total_classes_result = mysqli_query($conn, $total_classes_query);

                if ($total_classes_result && mysqli_num_rows($total_classes_result) > 0) {
                    $total_classes_row = mysqli_fetch_assoc($total_classes_result);
                    $total_classes = $total_classes_row['total_classes'];
                }

                $student_count_query = "
                    SELECT COUNT(DISTINCT student_id) AS total_students
                    FROM students_eval_restriction 
                    WHERE evaluation_id IN ($teacher_eval_ids_str)";
                $student_count_result = mysqli_query($conn, $student_count_query);

                if ($student_count_result && mysqli_num_rows($student_count_result) > 0) {
                    $student_count_row = mysqli_fetch_assoc($student_count_result);
                    $total_students = $student_count_row['total_students'];
                }
            }
        }
    }

    // Calculate total hours worked for the current month
    $total_hours_worked_formatted = '0h 0m';
    if (!empty($emp_code)) {
        $currentMonth = date("m");
        $currentYear = date("Y");

        // Fetch attendance records
        $attendanceData = [];

        $query = "SELECT DATE(punch_date_time) as day, punch_state, TIME(punch_date_time) as punch_time
                  FROM employee_attendance
                  WHERE emp_code = ? AND MONTH(punch_date_time) = ? AND YEAR(punch_date_time) = ?
                  ORDER BY punch_date_time";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("sii", $emp_code, $currentMonth, $currentYear);
        $stmt->execute();
        $result = $stmt->get_result();

        // Loop to fetch the first Check Out and Break Out for each day
        while ($row = $result->fetch_assoc()) {
            $day = $row['day'];
            $punchState = $row['punch_state'];
            $punchTime = $row['punch_time'];

            if (!isset($attendanceData[$day])) {
                $attendanceData[$day] = ['arrival' => null, 'departure' => null];
            }

            // Capture the first Check Out as arrival time
            if ($punchState === 'Check Out' && $attendanceData[$day]['arrival'] === null) {
                $attendanceData[$day]['arrival'] = $punchTime;
            }

            // Capture the first Break Out as departure time
            if ($punchState === 'Break Out' && $attendanceData[$day]['departure'] === null) {
                $attendanceData[$day]['departure'] = $punchTime;
            }
        }
        $stmt->close();

        // Calculate total minutes worked for the month
        $totalMinutesForMonth = 0;
        $schoolStart = new DateTime("06:00");
        $schoolEnd = new DateTime("17:00");

        foreach ($attendanceData as $date => $times) {
            $arrival = $times['arrival'];
            $departure = $times['departure'];

            if ($arrival !== null && $departure !== null) {
                $arrivalTime = new DateTime($arrival);
                $departureTime = new DateTime($departure);

                // Adjust times within 06:00 to 17:00 range
                if ($arrivalTime < $schoolStart) {
                    $arrivalTime = clone $schoolStart;
                }
                if ($departureTime > $schoolEnd) {
                    $departureTime = clone $schoolEnd;
                }

                $interval = $arrivalTime->diff($departureTime);
                $dailyHours = $interval->h;
                $dailyMinutes = $interval->i;
                $minutesForDay = min(($dailyHours * 60) + $dailyMinutes, 660); // Cap at 11 hours
                $totalMinutesForMonth += $minutesForDay;
            }
        }

        // Convert total minutes to hours
        $total_hours_worked = floor($totalMinutesForMonth / 60);
        $remaining_minutes = $totalMinutesForMonth % 60;

        // Format total hours worked as a string, e.g., "52h 30m"
        $total_hours_worked_formatted = "{$total_hours_worked}h {$remaining_minutes}m";
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
<?php 
include('teacher-navigation.php'); ?>
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

        #sentimentDonutChart,
        #likertScaleChart {
            max-width: 100%;
            height: 200px; /* Set to the same height */
            max-height: 200px; /* Set to the same height */
        }

        /* Bounce-in effect para sa mga numbers */
        .bounce-in {
            animation: bounceIn 0.8s ease-out forwards; /* Gamitin ang bounceIn animation para sa talbog na effect */
        }
        @keyframes bounceIn {
            0% {
                opacity: 0;           /* Naka-invisible sa simula para smooth na pag-appear */
                transform: scale(0.5); /* Mas maliit kesa sa actual size para may talbog effect */
            }
            60% {
                opacity: 1;           /* Nagfa-fade in dito para visible na */
                transform: scale(1.2); /* Lumalaki ng konti para magmukhang tumatalbog */
            }
            100% {
                transform: scale(1);   /* Babalik sa normal na laki sa dulo ng animation */
            }
        }

        /* Rotating pop effect para sa icons */
        .rotate-pop {
            animation: rotatePop 0.8s ease-out 0.3s forwards; /* Gamitin ang rotatePop na may konting delay */
        }
        @keyframes rotatePop {
            0% {
                opacity: 0;               /* Naka-invisible sa umpisa */
                transform: scale(0.5) rotate(-15deg); /* Mas maliit at may kaunting rotation for pop effect */
            }
            60% {
                opacity: 1;               /* Biglang visible na */
                transform: scale(1.1) rotate(10deg); /* Lumalaki ng konti at umiikot para sa pop */
            }
            100% {
                transform: scale(1) rotate(0deg); /* Babalik sa normal size at position */
            }
        }

        /* Fade-in effect para sa mga title ng cards */
        .fade-in-title {
            animation: fadeInTitle 0.8s ease-out 0.5s forwards; /* Fade-in na may konting delay para di sabay-sabay */
            opacity: 0; /* Naka-hidden sa simula para smooth ang pag-appear */
        }
        @keyframes fadeInTitle {
            0% {
                opacity: 0;                /* Di pa kita sa umpisa */
                transform: translateY(10px); /* Nasa baba ng kaunti para may slide effect pataas */
            }
            100% {
                opacity: 1;                /* Fully visible na */
                transform: translateY(0);   /* Nasa tamang position na */
            }
        }
    </style>
    
    <p class="classTitle fs-4 fw-bold ms-5 mb-2">Dashboard</p>
    <div class="mx-auto mb-4" style="height: 2px; background-color: #facc15; width:95%;"></div>
    
    <div class="container-fluid2">
        <div class="row g-2 equal-height d-flex">
            <!-- Left Section  -->
            <div class="col-lg-9 col-md-12 d-flex"> 
                <div class="card p-4 mb-3 flex-fill shadow"> 
                    <div id="dynamicContent">
                        <div class="border shadow rounded p-3 mb-4">
                            <p class="mb-3 fs-4 fw-bold">Welcome back, <?php echo htmlspecialchars($teacher_first_name); ?>!</p>
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
                        <div class="col-sm-6 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="container border rounded p-2 text-center d-flex flex-column justify-content-center mb-4" style="height: 50px;">
                                        <h5 class="fw-bold">Student Sentiments</h5>
                                    </div>
                                    <p id="sentimentNoDataMessage" style="display: none;">No student sentiments available for this academic year.</p>
                                    <canvas id="sentimentDonutChart" width="200" height="200"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Likert Scale Card -->
                        <div class="col-sm-6 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="container border rounded p-2 text-center d-flex flex-column justify-content-center mb-4" style="height: 50px;">
                                        <h5 class="fw-bold">Likert Scale Analysis</h5>
                                    </div>
                                    <p id="likertNoDataMessage" style="display: none;">No Likert scale data available for this academic year.</p>
                                    <canvas id="likertScaleChart" width="200" height="200"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Total Submitted Evaluation Card -->
                        <div class="col-sm-6 mb-3 d-flex align-items-stretch"> 
                            <div class="hover-pop-card" style="border: 1px solid #E0E0E0; border-radius: 10px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); padding: 10px; width: 100%;">
                                <div class="card-body d-flex justify-content-center align-items-center mt-4" style="padding: 0;">
                                    <div style="margin-right: 10px;">
                                        <p class="mb-0 bounce-in" id="totalEvaluationText" style="font-weight: bold; font-size: 3rem; line-height: 1;"></p> <!-- Placeholder value -->
                                        <p class="card-title fade-in-title" style="font-weight: bold; font-size: 1.2rem; margin-bottom: 0;">Total Submitted Evaluation</p>
                                    </div>
                                    <div class="me-3"><i class="fa-solid fa-clipboard-list rotate-pop" style="font-size: 3.5rem; color: #333;"></i></div>
                                </div>
                            </div>
                        </div>

                        <!-- Hours Worked Card -->
                        <div class="col-sm-6 mb-3 d-flex align-items-stretch"> 
                            <div class="hover-pop-card" style="border: 1px solid #E0E0E0; border-radius: 10px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); padding: 10px; width: 100%;">
                                <div class="card-body d-flex justify-content-center align-items-center mt-4" style="padding: 0;">
                                    <div style="margin-right: 10px;">
                                        <p class="mb-0 bounce-in" id="totalHoursWorked" style="font-weight: bold; font-size: 2.5rem; line-height: 1;"><?php echo $total_hours_worked_formatted; ?></p>
                                        <p class="card-title fade-in-title" style="font-weight: bold; font-size: 1.2rem; margin-bottom: 0;">Total Time Worked for the Month</p>
                                    </div>
                                    <i class="fa-solid fa-hourglass-half rotate-pop" style="font-size: 3.5rem; color: #333;"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Total Students Handled Card -->
                        <div class="col-sm-6 mb-3 d-flex align-items-stretch"> 
                            <div class="hover-pop-card" style="border: 1px solid #E0E0E0; border-radius: 10px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); padding: 10px; width: 100%;">
                                <div class="card-body d-flex justify-content-center align-items-center mt-4" style="padding: 0;">
                                    <div style="margin-right: 10px;">
                                        <p class="mb-0 bounce-in" id="totalStudentsText" style="font-weight: bold; font-size: 3rem; line-height: 1;"></p> <!-- Placeholder value -->
                                        <p class="card-title fade-in-title" style="font-weight: bold; font-size: 1.2rem; margin-bottom: 0;">Total Students</p>
                                    </div>
                                    <i class="bi bi-people-fill rotate-pop" style="font-size: 3.5rem; color: #333;"></i> <!-- Icon representing students -->
                                </div>
                            </div>
                        </div>

                        <!-- Total Subjects Card -->
                        <div class="col-sm-6 mb-3 d-flex align-items-stretch"> 
                            <div class="hover-pop-card" style="border: 1px solid #E0E0E0; border-radius: 10px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); padding: 10px; width: 100%;">
                                <div class="card-body d-flex justify-content-center align-items-center mt-4" style="padding: 0;">
                                    <div style="margin-right: 10px;">
                                        <p class="mb-0 bounce-in" id="totalClassesText" style="font-weight: bold; font-size: 3rem; line-height: 1;"></p> <!-- Placeholder value -->
                                        <p class="card-title fade-in-title" style="font-weight: bold; font-size: 1.2rem; margin-bottom: 0;">Total Subjects</p>
                                    </div>
                                    <i class="fa-solid fa-book rotate-pop" style="font-size: 3.5rem; color: #333;"></i> <!-- Icon for subjects -->
                                </div>
                            </div>
                        </div>
                    </div>             
                </div>
            </div>

            <!-- Right Section: Top 10 Faculty Members -->
            <div class="col-lg-3 col-md-12 d-flex"> 
                <div class="card p-4 flex-fill shadow top-teachers-card">
                    <h5 class="card-center mb-4" id="topTeachersTitle">
                        Top 10 Highest-Rated Faculties for AY <?php echo $active_year; ?> - Quarter <?php echo $active_quarter; ?>
                    </h5>
                    <table class="table table-striped table-hover text-center align-middle top-teachers-table" id="topTeachersTable">
                        <tbody>
                            <?php
                            $rank = 1;
                            foreach ($top_teachers as $teacher):
                                $full_name = $teacher['firstName'] . ' ' . $teacher['lastName'];
                            ?>
                            <tr>
                                <td><?php echo $rank; ?></td>
                                <td class="text-start">
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

                            for (; $rank <= 10; $rank++):
                            ?>
                            <tr>
                                <td><?php echo $rank; ?></td>
                                <td class="text-start">-</td>
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
        $(document).ready(function() {
            // Fetch the active academic year details when the page loads
            fetchAcademicYearDetails($('#academicYearSelect').val());

            $('#academicYearSelect').on('change', function() {
                var selectedValue = $(this).val();
                fetchAcademicYearDetails(selectedValue);
            });

            var sentimentChart;
            var likertChart;

            function fetchAcademicYearDetails(value) {
                var yearQuarter = value.split('|');
                var year = yearQuarter[0];
                var quarter = yearQuarter[1];

                $.ajax({
                    url: '', // URL is the same file
                    type: 'POST',
                    data: { year: year, quarter: quarter },
                    success: function(response) {
                        var data = JSON.parse(response);
                        $('#academicYearText').text('Academic Year: ' + year + ' - Quarter ' + quarter);
                        $('#evaluationEndText').text(data.evaluation_end);
                        $('#evaluationStatus').text(data.status).removeClass().addClass(data.status_class);
                        $('#topTeachersTitle').text('Top 10 Highest-Rated Faculties for AY ' + year + ' - Quarter ' + quarter);
                        updateTopTeachersTable(data.top_teachers);
                        $('#totalEvaluationText').text(data.total_evaluations);
                        $('#totalStudentsText').text(data.total_students);
                        $('#totalHoursWorked').text(data.total_hours_worked);
                        $('#totalClassesText').text(data.total_classes); // Update the Total Subjects card

                        if (data.sentiment_data.positive === 0 && data.sentiment_data.neutral === 0 && data.sentiment_data.negative === 0) {
                            $('#sentimentNoDataMessage').show();
                            $('#sentimentDonutChart').hide();
                        } else {
                            $('#sentimentNoDataMessage').hide();
                            $('#sentimentDonutChart').show();
                            createDonutChart(data.sentiment_data.positive, data.sentiment_data.neutral, data.sentiment_data.negative);
                        }

                        if (Object.values(data.likert_scale_data).every(value => value === 0)) {
                            $('#likertNoDataMessage').show();
                            $('#likertScaleChart').hide();
                        } else {
                            $('#likertNoDataMessage').hide();
                            $('#likertScaleChart').show();
                            createLikertScaleChart(data.likert_scale_data);
                        }
                    },
                    error: function() {
                        console.error('Error fetching academic year details.');
                    }
                });
            }

            function updateTopTeachersTable(teachers) {
                var tableBody = $('#topTeachersTable tbody');
                tableBody.empty();
                var rank = 1;

                if (teachers.length > 0) {
                    $.each(teachers, function(index, teacher) {
                        var crown = (index === 0) ? '<i class="fa-solid fa-crown text-warning"></i> ' : '';
                        var row = '<tr>' +
                            '<td>' + (rank) + '</td>' +
                            '<td>' + crown + teacher.firstName + ' ' + teacher.lastName + '</td>' +
                            '<td>' + teacher.average_rating + '</td>' +
                        '</tr>';
                        tableBody.append(row);
                        rank++;
                    });
                }

                for (; rank <= 10; rank++) {
                    var emptyRow = '<tr>' +
                        '<td>' + rank + '</td>' +
                        '<td>-</td>' +
                        '<td>-</td>' +
                    '</tr>';
                    tableBody.append(emptyRow);
                }
            }

            function createDonutChart(positive, neutral, negative) {
                var ctx = document.getElementById('sentimentDonutChart').getContext('2d');

                if (sentimentChart) {
                    sentimentChart.destroy(); // Destroy previous chart if it exists
                }

                var labels = ['Positive', 'Neutral', 'Negative'];
                var dataValues = [positive, neutral, negative];
                var colors = ['#582f0e', '#d4a276', '#99582a']; // Chocolate Brown, Saddle Brown, Copper Brown

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
                            tooltip: {
                                callbacks: {
                                    label: function(tooltipItem) {
                                        var label = tooltipItem.label || '';
                                        var value = tooltipItem.raw || 0;
                                        return `${label}: ${value.toFixed(2)}%`; // Display value rounded to 2 decimal places
                                    }
                                }
                            },
                            datalabels: {
                                color: '#fff',
                                formatter: function(value, context) {
                                    // Display labels rounded to 2 decimal places
                                    return value > 0 ? value.toFixed(2) + '%' : '';
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
            
            function createLikertScaleChart(likertData) {
                var ctx = document.getElementById('likertScaleChart').getContext('2d');

                if (likertChart) {
                    likertChart.destroy(); // Destroy previous chart if it exists
                }

                var labels = ['NI', 'F', 'S', 'VS', 'O'];
                var fullNames = {
                    'NI': 'Needs Improvement',
                    'F': 'Fair',
                    'S': 'Satisfactory',
                    'VS': 'Very Satisfactory',
                    'O': 'Outstanding'
                };
                var dataValues = [
                    likertData['NI'] || 0,
                    likertData['F'] || 0,
                    likertData['S'] || 0,
                    likertData['VS'] || 0,
                    likertData['O'] || 0
                ];
                var colors = ['#4b2e2e', '#b87333', '#d4a276', '#8b4513', '#f4a460']; // Color palette for the bars

                likertChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Number of Responses',
                            data: dataValues,
                            backgroundColor: colors,
                            borderColor: colors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 20,
                                    font: {
                                        family: 'Poppins',
                                        size: 12
                                    }
                                }
                            },
                            x: {
                                ticks: {
                                    font: {
                                        family: 'Poppins',
                                        size: 12
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            datalabels: {
                                color: '#000',
                                anchor: 'end',
                                align: 'top',
                                formatter: function(value) {
                                    return value;
                                },
                                font: {
                                    family: 'Poppins',
                                    weight: 'bold',
                                    size: 14
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(tooltipItem) {
                                        var label = labels[tooltipItem.dataIndex];
                                        var fullName = fullNames[label];
                                        var value = tooltipItem.raw;
                                        return `${fullName}: ${value}`;
                                    }
                                }
                            }
                        }
                    },
                    plugins: [ChartDataLabels]
                });
            }

        });
    </script>
</html>