<?php
session_start();
include('../../db_conn.php');

// Fetch admin's full name based on $_SESSION['user_id']
$adminFullName = '';
if (isset($_SESSION['user_id'])) {
    $admin_id = $_SESSION['user_id'];
    $queryAdmin = "SELECT firstName, middleName, lastName FROM admin_account WHERE admin_id = ?";
    $stmt = $conn->prepare($queryAdmin);
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $stmt->bind_result($adminFirstName, $adminMiddleName, $adminLastName);
    $stmt->fetch();
    $stmt->close();

    $adminMiddleInitial = !empty($adminMiddleName) ? ' ' . strtoupper(substr($adminMiddleName, 0, 1)) . '.' : '';
    $adminFullName = $adminFirstName . $adminMiddleInitial . ' ' . $adminLastName;
}

// Get data from POST request
$input = json_decode(file_get_contents('php://input'), true);
$staffIds = $input['staffIds'];
$currentMonth = intval($input['month']);
$currentYear = intval($input['year']);

// Get the number of days in the selected month and year
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);

// Initialize an array to hold DTR data for all staff
$allStaffDTR = [];

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

// Fetch DTR data for each staff
foreach ($staffIds as $staff_id) {
    // Fetch staff information
    $queryStaff = "SELECT firstName, middleName, lastName, staff_role, emp_code, break_time_id FROM staff_account WHERE staff_id = ?";
    $stmt = $conn->prepare($queryStaff);
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($conn->error));
    }
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $stmt->bind_result($staffFirstName, $staffMiddleName, $staffLastName, $staff_role, $emp_code, $break_time_id);
    $stmt->fetch();
    $stmt->close();

    $staffMiddleInitial = !empty($staffMiddleName) ? ' ' . strtoupper(substr($staffMiddleName, 0, 1)) . '.' : '';
    $staffFullName = $staffFirstName . $staffMiddleInitial . ' ' . $staffLastName;

    // Initialize total minutes for the month
    $totalMinutesForMonth = 0;

    // Query to fetch attendance records for the selected month and year
    $attendanceData = [];
    if (!empty($emp_code)) {
        $query = "SELECT DATE(punch_date_time) as day, punch_state, TIME(punch_date_time) as punch_time
                  FROM employee_attendance
                  WHERE emp_code = ? AND MONTH(punch_date_time) = ? AND YEAR(punch_date_time) = ?
                  ORDER BY punch_date_time";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            die('Prepare failed: ' . htmlspecialchars($conn->error));
        }
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
    }

    // Fetch break time for staff (except Guard (Night Shift))
    $breakTimeMinutes = 0;
    $breakStart = null;
    $breakEnd = null;
    if ($staff_role !== 'Guard (Night Shift)' && !empty($break_time_id)) {
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
        $date = sprintf("%04d-%02d-%02d", $currentYear, $currentMonth, $day);
        $arrival = isset($attendanceData[$date]['arrival']) ? $attendanceData[$date]['arrival'] : null;
        $departure = isset($attendanceData[$date]['departure']) ? $attendanceData[$date]['departure'] : null;

        // Define default start and end times based on staff_role
        if ($staff_role === 'Guard (Night Shift)') {
            $defaultStartTime = new DateTime("17:00:00");
            $defaultEndTime = new DateTime("06:00:00");
            $defaultEndTime->modify('+1 day'); // Next day
        } elseif ($staff_role === 'Primary School Teacher') {
            $defaultStartTime = new DateTime("07:30:00");
            $defaultEndTime = new DateTime("16:00:00");
        } else {
            $defaultStartTime = new DateTime("06:00:00");
            $defaultEndTime = new DateTime("17:00:00");
        }

        // Set date for default times
        $defaultStartTime->setDate($currentYear, $currentMonth, $day);
        $defaultEndTime->setDate($currentYear, $currentMonth, $day);
        if ($staff_role === 'Guard (Night Shift)') {
            $defaultEndTime->modify('+1 day');
        }

        // Handle missing arrival or departure
        if ($arrival === null && $departure !== null) {
            // Arrival is missing, assign default arrival time
            $arrivalTime = clone $defaultStartTime;
        } elseif ($arrival !== null) {
            $arrivalTime = new DateTime($arrival);
            $arrivalTime->setDate($currentYear, $currentMonth, $day);
        } else {
            $arrivalTime = null;
        }

        if ($departure === null && $arrival !== null) {
            // Departure is missing, assign default departure time
            $departureTime = clone $defaultEndTime;
        } elseif ($departure !== null) {
            $departureTime = new DateTime($departure);
            $departureTime->setDate($currentYear, $currentMonth, $day);
            if ($staff_role === 'Guard (Night Shift)' && $departureTime <= $arrivalTime) {
                $departureTime->modify('+1 day');
            }
        } else {
            $departureTime = null;
        }

        // Format arrival and departure times
        $arrivalFormatted = $arrivalTime ? $arrivalTime->format('H:i:s') : '-';
        $departureFormatted = $departureTime ? $departureTime->format('H:i:s') : '-';

        // Calculate total time
        if ($arrivalTime && $departureTime) {
            // Adjust times within the schedule range
            if ($arrivalTime < $defaultStartTime) {
                $arrivalTime = clone $defaultStartTime;
            }
            if ($departureTime > $defaultEndTime) {
                $departureTime = clone $defaultEndTime;
            }

            // Compute interval
            $interval = $arrivalTime->diff($departureTime);
            $minutesForDay = ($interval->h * 60) + $interval->i;

            // Cap the daily minutes based on staff role
            if ($staff_role === 'Guard (Night Shift)') {
                $minutesForDay = min($minutesForDay, 780); // 13 hours
            } else {
                $minutesForDay = min($minutesForDay, 660); // 11 hours
            }

            // Subtract break time, if applicable
            if ($staff_role !== 'Guard (Night Shift)' && isset($breakStart) && isset($breakEnd)) {
                // Compute overlap between working hours and break time
                $breakStartForDay = clone $breakStart;
                $breakEndForDay = clone $breakEnd;
                // Set date for break times
                $breakStartForDay->setDate($currentYear, $currentMonth, $day);
                $breakEndForDay->setDate($currentYear, $currentMonth, $day);

                $overlapMinutes = getTimeOverlapInMinutes($arrivalTime, $departureTime, $breakStartForDay, $breakEndForDay);

                // Subtract overlapMinutes from $minutesForDay
                $minutesForDay -= $overlapMinutes;
                if ($minutesForDay < 0) $minutesForDay = 0;
            }

            // Convert minutesForDay to hours and minutes
            $dailyHours = floor($minutesForDay / 60);
            $dailyMinutes = $minutesForDay % 60;
            $dailyTotal = "{$dailyHours}h {$dailyMinutes}m";

            // Add to total minutes for the month
            $totalMinutesForMonth += $minutesForDay;
        } else {
            $dailyTotal = '-';
        }

        $tableRows[] = [
            'day' => $day,
            'arrival' => $arrivalFormatted,
            'departure' => $departureFormatted,
            'total' => $dailyTotal
        ];
    }

    // Convert total minutes for the month into hours and minutes
    $totalHours = floor($totalMinutesForMonth / 60);
    $totalMinutes = $totalMinutesForMonth % 60;
    $totalHoursMinutes = "{$totalHours}h {$totalMinutes}m";

    // Prepare data for printing
    $allStaffDTR[$staff_role][] = [
        'staffFullName' => htmlspecialchars($staffFullName),
        'staff_role' => htmlspecialchars($staff_role),
        'tableRows' => $tableRows,
        'totalHoursMinutes' => $totalHoursMinutes
    ];
}

// Now generate the printable content
?>
<!DOCTYPE html>
<html>
<head>
    <title>Batch Print Staff DTR</title>
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
        .certify-text {
            font-style: italic;
            text-align: justify;
            margin-top: 10px;
            margin-bottom: 10px;
            font-size: 10px;
        }
        .verified-text {
            text-align: left;
            margin-top: 10px;
            font-size: 10px;
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
        /* Prevent page breaks inside the table */
        table, tr, td, th, tbody, thead, tfoot {
            page-break-inside: avoid !important;
        }
    </style>
</head>
<body>
    <?php foreach ($allStaffDTR as $staff_role => $staffMembers): ?>
        <?php foreach ($staffMembers as $staffData): ?>
            <div class="print-container">
                <div class="header">
                    <img src="../Logo/mja-logo.png" alt="School Logo">
                    <span class="header-title fs-4">Mary Josette Academy</span>
                </div><br>
                <div class="dtr-title mt-5">DAILY TIME RECORD</div><br>
                
                <div class="signature-section mt-5">
                    <p class="signature-name"><?= $staffData['staffFullName']; ?></p>
                    <div class="signature-line"></div>
                    <p class="signature-label">(Name)</p>
                </div>

                <div class="details">
                    <b>Month-Year:</b> <span><?= date('F Y', strtotime("$currentYear-$currentMonth-01")); ?></span><br>
                    <b>Role:</b> <span><?= $staff_role; ?></span>
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
                        <?php foreach ($staffData['tableRows'] as $row): ?>
                            <tr>
                                <td><?= $row['day']; ?></td>
                                <td><?= $row['arrival']; ?></td>
                                <td><?= $row['departure']; ?></td>
                                <td><?= $row['total']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: bold;">T O T A L</td>
                            <td style="font-weight: bold;"><?= $staffData['totalHoursMinutes']; ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="certify-text mt-4">
                    I CERTIFY on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.
                </div><br>

                <!-- Staff's Signature Section -->
                <div class="signature-section mt-5">
                    <p class="signature-name"><?= $staffData['staffFullName']; ?></p>
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
