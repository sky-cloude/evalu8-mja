<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Staff DTR</title>
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <?php include('../header.php'); ?>
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/admin-elements.css" />
    <style>
        .breadcrumbs-container {
            margin-top: 3%;
            margin-left: 0%;
            margin-bottom: 1.5%;
            padding-top: 2%;
            width: 98%;
        }
        .breadcrumbs {
            display: flex;
            align-items: center;
            height: 40px;
            background-color: #ffffff;
            border: 1px solid #ddd;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            padding: 8px 15px;
        }
        .breadcrumb-item a {
            text-decoration: none;
            color: #6c757d;
        }
        .breadcrumb-item.active {
            color: #6c757d;
        }
        .btn-back {
            background: none;
            border: none;
            padding: 0;
            color: #281313;
            margin-right: 35px;
        }
        .btn-back i {
            font-size: 2.5rem;
        }
        .btn-back:hover {
            color: #facc15;
        }

        .month-navigation {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        .month-navigation i {
            font-size: 2rem;
            cursor: pointer;
            color: #281313;
        }
        .month-navigation i:hover {
            color: #facc15;
        }

        .print-dtr-btn {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        .btn-print {
            background-color: #281313;
            color: white;
            font-size: 1rem;
            padding: 12px 40px;
            border: none;
            border-radius: 50px;
            width: 200px;
            text-align: center;
        }
        .btn-print:hover {
            background-color: #facc15;
            color: #281313;
        }

        /* Responsive adjustments */
        @media (max-width: 767px) { 
            .breadcrumbs {
                margin-top: 15%;
                padding-top: 6%;
                margin-bottom: 8%;
            }
            .container.bg-white {
                max-width: 90%;
                margin: auto;
            }
        }
    </style>
</head>

<body>
    <?php
    session_start();
    include('../db_conn.php');
    include('navigation.php');

    // Enable error reporting for debugging (Disable in production)
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);

    // Get the staff_id from the URL
    $staff_id = isset($_GET['staff_id']) ? intval($_GET['staff_id']) : 0;
    $staffFirstName = "";
    $staffMiddleName = "";
    $staffLastName = "";
    $emp_code = "";
    $staff_role = "";
    $break_time_id = null;

    if ($staff_id > 0) {
        // Query to get staff's full name, role, emp_code, and break_time_id based on staff_id
        $query = "SELECT firstName, middleName, lastName, staff_role, emp_code, break_time_id FROM staff_account WHERE staff_id = ?";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            die('Prepare failed: ' . htmlspecialchars($conn->error));
        }
        $stmt->bind_param("i", $staff_id);
        $stmt->execute();
        $stmt->bind_result($staffFirstName, $staffMiddleName, $staffLastName, $staff_role, $emp_code, $break_time_id);
        $stmt->fetch();
        $stmt->close();

        // Construct full name with middle initial
        $staffMiddleInitial = !empty($staffMiddleName) ? ' ' . strtoupper(substr($staffMiddleName, 0, 1)) . '.' : '';
        $staffFullName = $staffFirstName . $staffMiddleInitial . ' ' . $staffLastName;
    } else {
        die("Invalid staff ID.");
    }

    // Fetch admin's full name based on $_SESSION['user_id']
    $adminFullName = '';
    if (isset($_SESSION['user_id'])) {
        $admin_id = $_SESSION['user_id'];
        $query = "SELECT firstName, middleName, lastName FROM admin_account WHERE admin_id = ?";
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            die('Prepare failed: ' . htmlspecialchars($conn->error));
        }
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $stmt->bind_result($adminFirstName, $adminMiddleName, $adminLastName);
        $stmt->fetch();
        $stmt->close();

        // Construct admin's full name with middle initial
        $adminMiddleInitial = !empty($adminMiddleName) ? ' ' . strtoupper(substr($adminMiddleName, 0, 1)) . '.' : '';
        $adminFullName = $adminFirstName . $adminMiddleInitial . ' ' . $adminLastName;
    }

    // Get the current month and year
    $currentMonth = isset($_GET['month']) ? intval($_GET['month']) : date("m");
    $currentYear = isset($_GET['year']) ? intval($_GET['year']) : date("Y");

    // Get the number of days in the selected month and year
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);

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

    // Encode $tableRows as JSON
    $tableRowsJSON = json_encode($tableRows);
    ?>

    <!-- Breadcrumbs -->
    <div class="breadcrumbs-container">
        <nav aria-label="breadcrumb" class="breadcrumbs ms-4 bg-white border shadow rounded py-2 px-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="admin-dashboard.php" class="text-muted text-decoration-none">
                        <i class="fa-solid fa-house"></i> Home
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="staff-dtr.php" class="text-muted text-decoration-none">Staff DTR</a>
                </li>
                <li class="breadcrumb-item active text-muted" aria-current="page">Print Staff DTR</li>
            </ol>
        </nav>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="classTitle fs-4 fw-bold ms-5 mb-2">Print Staff DTR for <?= htmlspecialchars($staffFullName); ?></p>
        <a href="staff-dtr.php" class="btn btn-back">
            <i class="fa-solid fa-circle-chevron-left"></i>
        </a>
    </div>

    <div class="mx-auto mb-4" style="height: 2px; background-color: #facc15; width:95%;"></div>

    <!-- Container with white background -->
    <div class="container bg-white rounded border shadow mb-5 p-4">
        <!-- Month Navigation and Print Button -->
        <div class="month-navigation">
            <i class="bi bi-caret-left-square-fill" id="prevMonth"></i>
            <span class="fs-4 fw-bold" id="monthDisplay"><?= date("F Y", strtotime("$currentYear-$currentMonth-01")); ?></span>
            <i class="bi bi-caret-right-square-fill" id="nextMonth"></i>
        </div>
        <div class="print-dtr-btn">
            <button class="btn-print" onclick="openPrintDTR()"><i class="fa-solid fa-print me-2"></i>Print DTR</button>
        </div>
        <!-- DTR Table with attendance data -->
        <div class="table-responsive mt-4" style="max-width: 90%; margin: auto;">
            <table class="table table-bordered table-hover border-secondary">
                <thead class="fw-bold">
                    <tr>
                        <th class="text-start" style="background-color: #44311f; color: white; padding: 15px;">Day</th>
                        <th class="text-start" style="background-color: #44311f; color: white; padding: 15px;">Arrival Time</th>
                        <th class="text-start" style="background-color: #44311f; color: white; padding: 15px;">Departure Time</th>
                        <th class="text-start" style="background-color:#44311f; color: white; padding: 15px;">Total Time (Hours)</th> 
                    </tr>
                </thead>
                <tbody>
                <?php
                    foreach ($tableRows as $row) {
                        echo "<tr style='border-bottom: 1px solid #6c757d;'>
                                <td class='text-start'>{$row['day']}</td>
                                <td class='text-start'>{$row['arrival']}</td>
                                <td class='text-start'>{$row['departure']}</td>
                                <td class='text-start'>{$row['total']}</td>
                            </tr>";
                    }
                    ?>
                    <tr style='border-bottom: 1px solid #6c757d;'>
                        <td colspan="3" class="text-end fw-bold" style="padding: 20px;">T O T A L</td>
                        <td class="align-middle fw-bold" style="padding-left: 15px;"><?= "{$totalHours}h {$totalMinutes}m"; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <?php include('footer.php'); ?>

    <!-- Complete <script> Block -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const monthDisplay = document.getElementById("monthDisplay");
            const prevMonthBtn = document.getElementById("prevMonth");
            const nextMonthBtn = document.getElementById("nextMonth");
            let currentDate = new Date("<?= $currentYear; ?>", "<?= $currentMonth - 1; ?>");

            function updateMonthDisplay() {
                monthDisplay.textContent = currentDate.toLocaleDateString("en-US", { month: "long", year: "numeric" });
                const newMonth = currentDate.getMonth() + 1;
                const newYear = currentDate.getFullYear();
                window.location.href = `?staff_id=<?= $staff_id; ?>&month=${newMonth}&year=${newYear}`;
            }

            prevMonthBtn.addEventListener("click", function () {
                currentDate.setMonth(currentDate.getMonth() - 1);
                updateMonthDisplay();
            });

            nextMonthBtn.addEventListener("click", function () {
                currentDate.setMonth(currentDate.getMonth() + 1);
                updateMonthDisplay();
            });
        });

        function openPrintDTR() {
            const staffName = "<?= htmlspecialchars($staffFullName) ?>";
            const monthYear = "<?= date('F Y', strtotime("$currentYear-$currentMonth-01")); ?>";
            const role = "<?= htmlspecialchars($staff_role) ?>";
            const adminName = "<?= htmlspecialchars($adminFullName) ?>";
            const tableRows = <?= $tableRowsJSON ?>;
            const totalHoursMinutes = "<?= "{$totalHours}h {$totalMinutes}m"; ?>";

            const printableContent = `
                <html>
                <head>
                    <title>Print Staff DTR</title>
                    <style>
                        @media print {
                            @page {
                                size: 8.5in 11in; /* Set size to short bond paper */
                                margin: 10mm; /* Set margin to fit the content on short bond paper */
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
                        .staff-name {
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
                    <div class="print-container">
                        <div class="header">
                            <img src="../Logo/mja-logo.png" alt="School Logo">
                            <span class="header-title fs-4">Mary Josette Academy</span>
                        </div><br>
                        <div class="dtr-title">DAILY TIME RECORD</div><br>
                        
                        <div class="signature-section">
                            <p class="signature-name">${staffName}</p>
                            <div class="signature-line"></div>
                            <p class="signature-label">(Name)</p>
                        </div>

                        <div class="details">
                            <b>Month-Year:</b> <span>${monthYear}</span><br>
                            <b>Role:</b> <span>${role}</span>
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
                                ${tableRows.map(row => `
                                    <tr>
                                        <td>${row.day}</td>
                                        <td>${row.arrival}</td>
                                        <td>${row.departure}</td>
                                        <td>${row.total}</td>
                                    </tr>
                                `).join('')}
                                <tr>
                                    <td colspan="3" style="text-align: right; font-weight: bold;">T O T A L</td>
                                    <td style="font-weight: bold;">${totalHoursMinutes}</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="certify-text">
                            I CERTIFY on my honor that the above is a true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.
                        </div><br>

                        <!-- Staff's Signature Section -->
                        <div class="signature-section">
                            <p class="signature-name">${staffName}</p>
                            <div class="signature-line"></div>
                            <p class="signature-label">(Signature over Printed Name)</p><br>
                        </div>

                        <!-- Admin's Signature Section -->
                        <div class="signature-section">
                            <p class="verified-text">Verified as to prescribed office hours.</p><br>
                            <p class="signature-name">${adminName}</p>
                            <div class="signature-line"></div>
                            <p class="signature-label">(In-charge)</p>
                        </div>
                    </div>
                </body>
                </html>
            `;

            const printWindow = window.open('', '_blank', 'width=800,height=600');
            printWindow.document.write(printableContent);
            printWindow.document.close();

            // Add an event listener for the 'afterprint' event to close the window
            printWindow.addEventListener('afterprint', function () {
                printWindow.close();
            });

            printWindow.focus();
            printWindow.print();
        }
    </script>
</body>
</html>
