<?php
session_start();
include('../../db_conn.php');

// Fetch admin's full name based on $_SESSION['user_id']
$adminFullName = '';
if (isset($_SESSION['user_id'])) {
    $admin_id = $_SESSION['user_id'];
    $queryAdmin = "SELECT firstName, middleName, lastName FROM admin_account WHERE admin_id = ?";
    $stmt = $conn->prepare($queryAdmin);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $stmt->bind_result($adminFirstName, $adminMiddleName, $adminLastName);
    $stmt->fetch();
    $stmt->close();

    $adminMiddleInitial = !empty($adminMiddleName) ? ' ' . substr($adminMiddleName, 0, 1) . '.' : '';
    $adminFullName = $adminFirstName . $adminMiddleInitial . ' ' . $adminLastName;
}

// Get data from POST request
$input = json_decode(file_get_contents('php://input'), true);
$teacherIds = $input['teacherIds'];
$currentMonth = $input['month'];
$currentYear = $input['year'];

// Get the number of days in the selected month and year
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);

// Initialize an array to hold DTR data for all teachers
$allTeachersDTR = [];

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

// Fetch DTR data for each teacher
foreach ($teacherIds as $teacher_id) {
    // Fetch teacher information
    $queryTeacher = "SELECT firstName, middleName, lastName, department, emp_code, status, break_time_id FROM teacher_account WHERE teacher_id = ?";
    $stmt = $conn->prepare($queryTeacher);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $stmt->bind_result($facultyFirstName, $facultyMiddleName, $facultyLastName, $department, $emp_code, $teacher_status, $break_time_id);
    $stmt->fetch();
    $stmt->close();

    $facultyMiddleInitial = !empty($facultyMiddleName) ? ' ' . substr($facultyMiddleName, 0, 1) . '.' : '';
    $teacherFullName = $facultyFirstName . $facultyMiddleInitial . ' ' . $facultyLastName;

    // Initialize total minutes for the month
    $totalMinutesForMonth = 0;

    // Fetch attendance data for the teacher
    $attendanceData = [];

    if (!empty($emp_code)) {
        $query = "SELECT DATE(punch_date_time) as day, punch_state, TIME(punch_date_time) as punch_time
                  FROM employee_attendance
                  WHERE emp_code = ? AND MONTH(punch_date_time) = ? AND YEAR(punch_date_time) = ?
                  ORDER BY punch_date_time";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            die("Preparation failed: (" . $conn->errno . ") " . $conn->error);
        }
        $stmt->bind_param("sii", $emp_code, $currentMonth, $currentYear);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $day = $row['day']; // Format: YYYY-MM-DD
            $punchState = $row['punch_state'];
            $punchTime = $row['punch_time']; // Format: HH:MM:SS

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
    }

    // For Part-timer teachers, fetch their schedule
    $teacherSchedule = [];
    if ($teacher_status === 'Part-timer') {
        $query = "SELECT subject_code, class_title, time_start, time_end, day_of_week
                  FROM teacher_schedule
                  WHERE teacher_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            die("Preparation failed: (" . $conn->errno . ") " . $conn->error);
        }
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // Define the mapping from day names to integers
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
            $dayName = $row['day_of_week']; // e.g., "Monday"
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
        if (!$stmt) {
            die("Preparation failed: (" . $conn->errno . ") " . $conn->error);
        }
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

    // Prepare table rows and calculate total minutes
    $tableRows = [];

    // Process each day of the month
    for ($day = 1; $day <= $daysInMonth; $day++) {
        // Create date string in YYYY-MM-DD format
        $date = sprintf("%04d-%02d-%02d", $currentYear, $currentMonth, $day);
        $dayOfWeekNumber = date('N', strtotime($date)); // 1 (Monday) to 7 (Sunday)

        // Get attendance data for the day
        if (isset($attendanceData[$date])) {
            $arrival = $attendanceData[$date]['arrival'];
            $departure = $attendanceData[$date]['departure'];
        } else {
            $arrival = null;
            $departure = null;
        }

        if ($teacher_status === 'Regular') {
            // For Regular teachers
            $schoolStart = new DateTime('07:30:00');
            $schoolEnd = new DateTime('16:00:00');

            // Arrival Time
            if ($arrival === null) {
                $arrivalFormatted = '-';
                $dailyTotal = '-';
                $departureFormatted = $departure ? (new DateTime($departure))->format('H:i:s') : '-';
                // Skip computation if no arrival time
                $tableRows[] = [
                    'day' => $day,
                    'arrival' => $arrivalFormatted,
                    'departure' => $departureFormatted,
                    'total' => $dailyTotal
                ];
                continue;
            } else {
                $arrivalTime = new DateTime($arrival);
                $arrivalFormatted = $arrivalTime->format('H:i:s');

                // Adjust for computation
                if ($arrivalTime < $schoolStart) {
                    $startTime = clone $schoolStart;
                } else {
                    $startTime = clone $arrivalTime;
                }
            }

            // Departure Time
            if ($departure === null) {
                $departureTime = clone $schoolEnd;
                $departureFormatted = $departureTime->format('H:i:s');
            } else {
                $departureTime = new DateTime($departure);
                $departureFormatted = $departureTime->format('H:i:s');

                // Adjust for computation
                if ($departureTime > $schoolEnd) {
                    $departureTime = clone $schoolEnd;
                }
            }

            // Set date for $startTime and $departureTime
            $startTime->setDate($currentYear, $currentMonth, $day);
            $departureTime->setDate($currentYear, $currentMonth, $day);

            // Compute total worked hours
            $interval = $startTime->diff($departureTime);
            $minutesForDay = ($interval->h * 60) + $interval->i;

            // Compute overlap between working hours and break time
            $overlapMinutes = 0;
            if ($breakStart && $breakEnd) {
                $breakStartForDay = clone $breakStart;
                $breakEndForDay = clone $breakEnd;

                // Set date for break times
                $breakStartForDay->setDate($currentYear, $currentMonth, $day);
                $breakEndForDay->setDate($currentYear, $currentMonth, $day);

                $overlapMinutes = getTimeOverlapInMinutes($startTime, $departureTime, $breakStartForDay, $breakEndForDay);
            }

            // Subtract overlapMinutes from $minutesForDay
            $minutesForDay -= $overlapMinutes;
            if ($minutesForDay < 0) $minutesForDay = 0;

            // Cap at 510 minutes (8h 30m)
            $minutesForDay = min($minutesForDay, 510);

            $dailyHours = floor($minutesForDay / 60);
            $dailyMinutes = $minutesForDay % 60;
            $dailyTotal = "{$dailyHours}h {$dailyMinutes}m";

            $totalMinutesForMonth += $minutesForDay;

            $tableRows[] = [
                'day' => $day,
                'arrival' => $arrivalFormatted,
                'departure' => $departureFormatted,
                'total' => $dailyTotal
            ];
        } elseif ($teacher_status === 'Part-timer') {
            // For Part-time teachers
            $scheduledClasses = isset($teacherSchedule[$dayOfWeekNumber]) ? $teacherSchedule[$dayOfWeekNumber] : [];

            if (empty($scheduledClasses)) {
                // No scheduled classes
                $arrivalFormatted = '-';
                $departureFormatted = '-';
                $dailyTotal = '-';
                $tableRows[] = [
                    'day' => $day,
                    'arrival' => $arrivalFormatted,
                    'departure' => $departureFormatted,
                    'total' => $dailyTotal
                ];
                continue;
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

            // Arrival Time
            if ($arrival === null) {
                $arrivalFormatted = '-';
                $dailyTotal = '-';
                $departureFormatted = $departure ? (new DateTime($departure))->format('H:i:s') : '-';
                // Skip computation if no arrival time
                $tableRows[] = [
                    'day' => $day,
                    'arrival' => $arrivalFormatted,
                    'departure' => $departureFormatted,
                    'total' => $dailyTotal
                ];
                continue;
            } else {
                $arrivalTime = new DateTime($arrival);
                $arrivalFormatted = $arrivalTime->format('H:i:s');

                // Adjust for computation
                if ($arrivalTime < $earliestScheduledStart) {
                    $startTime = clone $earliestScheduledStart;
                } else {
                    $startTime = clone $arrivalTime;
                }
            }

            // Departure Time
            if ($departure === null) {
                $departureTime = clone $latestScheduledEnd;
                $departureFormatted = $departureTime->format('H:i:s');
            } else {
                $departureTime = new DateTime($departure);
                $departureFormatted = $departureTime->format('H:i:s');

                // Adjust for computation
                if ($departureTime > $latestScheduledEnd) {
                    $departureTime = clone $latestScheduledEnd;
                }
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

            $dailyHours = floor($workedMinutes / 60);
            $dailyMinutes = $workedMinutes % 60;
            $dailyTotal = "{$dailyHours}h {$dailyMinutes}m";

            $totalMinutesForMonth += $workedMinutes;

            $tableRows[] = [
                'day' => $day,
                'arrival' => $arrivalFormatted,
                'departure' => $departureFormatted,
                'total' => $dailyTotal
            ];
        } else {
            // For other statuses (if any)
            $arrivalFormatted = '-';
            $departureFormatted = '-';
            $dailyTotal = '-';
            $tableRows[] = [
                'day' => $day,
                'arrival' => $arrivalFormatted,
                'departure' => $departureFormatted,
                'total' => $dailyTotal
            ];
        }
    }

    // Convert total minutes for the month into hours and minutes
    $totalHours = floor($totalMinutesForMonth / 60);
    $totalMinutes = $totalMinutesForMonth % 60;
    $totalHoursMinutes = "{$totalHours}h {$totalMinutes}m";

    // Prepare data for printing
    $allTeachersDTR[$department][] = [
        'teacherFullName' => htmlspecialchars($teacherFullName),
        'department' => htmlspecialchars($department),
        'teacher_status' => htmlspecialchars($teacher_status),
        'tableRows' => $tableRows,
        'totalHoursMinutes' => $totalHoursMinutes
    ];
}

// Now generate the printable content
?>
<!DOCTYPE html>
<html>
<head>
    <title>Batch Print Faculty DTR</title>
    <style>
        @media print {
            @page {
                size: 8.5in 11in;
                margin: 10mm;
            }
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 5px;
            font-size: 11px;
        }
        .print-container {
            max-width: 700px;
            margin: auto;
        }
        /* Apply page-break-after to all but the last .print-container */
        .print-container:not(:last-child) {
            page-break-after: always;
        }
        .header {
            text-align: center;
            margin-bottom: 5px;
        }
        .header img {
            height: 35px;
            vertical-align: middle;
        }
        .header-title {
            font-size: 1rem;
            font-weight: bold;
            display: inline-block;
            margin-left: 5px;
            vertical-align: middle;
        }
        .dtr-title {
            font-size: 0.9rem;
            font-weight: bold;
            margin-top: 5px;
            margin-bottom: 5px;
            text-align: center;
        }
        .teacher-name {
            text-align: center;
            font-style: italic;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        .details {
            text-align: left;
            margin-bottom: 10px;
        }
        .details b {
            font-weight: bold;
        }
        .details span {
            font-weight: normal;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-top: 5px;
        }
        th, td {
            border: 1px solid #000;
            padding: 2px;
            text-align: center;
        }
        th {
            background-color: #f3f3f3;
        }
        .certify-text {
            font-style: italic;
            text-align: justify;
            margin-top: 10px;
            margin-bottom: 10px;
            font-size: 10px;
        }
        .signature-section {
            margin-top: 10px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 60%;
            margin: 5px auto 2px auto;
        }
        .signature-name {
            margin-bottom: 2px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .signature-label {
            margin-top: 0;
            font-size: 10px;
        }
        .verified-text {
            text-align: left;
            margin-top: 10px;
            font-size: 10px;
        }
        /* Prevent page breaks inside the table */
        table, tr, td, th, tbody, thead, tfoot {
            page-break-inside: avoid !important;
        }
    </style>
</head>
<body>
    <?php foreach ($allTeachersDTR as $department => $teachers): ?>
        <?php foreach ($teachers as $teacherData): ?>
            <div class="print-container">
                <div class="header">
                    <img src="../Logo/mja-logo.png" alt="School Logo">
                    <span class="header-title fs-4">Mary Josette Academy</span>
                </div><br>
                <div class="dtr-title mt-5">DAILY TIME RECORD</div><br>
                
                <div class="signature-section mt-5">
                    <p class="signature-name"><?= $teacherData['teacherFullName']; ?></p>
                    <div class="signature-line"></div>
                    <p class="signature-label">(Name)</p>
                </div>

                <div class="details">
                    <b>Month-Year:</b> <span><?= date('F Y', strtotime("$currentYear-$currentMonth-01")); ?></span><br>
                    <b>Department:</b> <span><?= $department; ?></span><br>
                    <b>Status:</b> <span><?= $teacherData['teacher_status']; ?></span>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Arrival Time</th>
                            <th>Departure Time</th>
                            <th>Total Time (Hours)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teacherData['tableRows'] as $row): ?>
                            <tr>
                                <td><?= $row['day']; ?></td>
                                <td><?= $row['arrival']; ?></td>
                                <td><?= $row['departure']; ?></td>
                                <td><?= $row['total']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: bold;">T O T A L</td>
                            <td style="font-weight: bold;"><?= $teacherData['totalHoursMinutes']; ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="certify-text mt-4">
                    I CERTIFY on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.
                </div><br>

                <!-- Teacher's Signature Section -->
                <div class="signature-section mt-5">
                    <p class="signature-name"><?= $teacherData['teacherFullName']; ?></p>
                    <div class="signature-line"></div>
                    <p class="signature-label">(Signature over Printed Name)</p>
                </div><br>

                <!-- Admin's Signature Section -->
                <div class="signature-section">
                    <p class="verified-text">Verified as to prescribed office hours.</p><br>
                    <p class="signature-name"><?= htmlspecialchars($adminFullName); ?></p>
                    <div class="signature-line"></div>
                    <p class="signature-label">(In-charge)</p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
</body>
</html>
