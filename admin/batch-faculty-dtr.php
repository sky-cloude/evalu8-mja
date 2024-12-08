<?php
session_start(); // Start the session at the very beginning
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Batch Print Faculty DTR</title>
    <?php include('../header.php'); ?>
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/admin-elements.css" />
    <style>
        /* Your existing styles */
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
            font-size: 2.5rem; /* Adjust the size of the icon */
        }

        .btn-back:hover {
            color: #facc15;
        }

        /* Responsive Breadcrumbs */
        @media (max-width: 767px) { 
            .breadcrumbs-container {
                padding-top: 1%;
                margin-top: 15%;
                margin-bottom: 5%;
            }
            .breadcrumbs {
                flex-wrap: wrap;
                font-size: 0.85rem;
                padding: 5px 10px;
                height: auto;
            }
            .breadcrumb-item a, .breadcrumb-item.active {
                font-size: 0.8rem;
                padding: 0;
                margin-right: 5px;
            }
            .breadcrumb-item i {
                font-size: 0.9rem;
                margin-right: 3px;
            }
            .breadcrumb-item:not(:last-child)::after {
                content: '>';
                padding: 0 4px;
            }
        }

        /* Month Navigation and Print Button Styles */
        .month-navigation {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
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

        /* Wider Batch Print DTR Button */
        .btn-print {
            background-color: #281313;
            color: white;
            font-size: 1rem;
            padding: 12px 50px;
            border: none;
            border-radius: 50px;
            width: 250px;
            text-align: center;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .btn-print.enabled {
            cursor: pointer;
            opacity: 1;
        }

        .btn-print:hover {
            background-color: #facc15;
            color: #281313;
        }

        /* Additional Select Buttons */
        .select-buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }
        
        /* Rectangular Buttons with Rounded Corners */
        .btn-select {
            background-color: #281313;
            color: white;
            font-size: 1rem;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            white-space: nowrap;
        }

        .btn-select:hover {
            background-color: #facc15;
            color: #281313;
        }

        /* Table Styles */
        .faculty-table-container {
            max-width: 90%;
            margin: auto;
        }

        .faculty-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1rem;
            margin-top: 20px;
        }

        .faculty-table th, .faculty-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .faculty-table th {
            background-color: #f3f3f3;
            font-weight: bold;
        }

        .faculty-table td {
            vertical-align: middle;
        }

        .no-result {
            text-align: center;
            font-style: italic;
            color: #6c757d;
        }

        .checkbox-cell {
            width: 50px;
            text-align: center;
        }

        /* Container width for small screens */
        @media (max-width: 767px) {
            .container {
                max-width: 95%;
                margin: auto;
            }
        }
    </style>
</head>

<body>
    <?php 
    include('../db_conn.php');
    include('navigation.php'); 

    // Get the current month and year
    $currentMonth = isset($_GET['month']) ? intval($_GET['month']) : date("m");
    $currentYear = isset($_GET['year']) ? intval($_GET['year']) : date("Y");

    // Query to get teacher data ordered by department and last name
    $query = "SELECT teacher_id, lastName, firstName, middleName, department, status FROM teacher_account ORDER BY department ASC, lastName ASC";
    $result = mysqli_query($conn, $query);

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
                    <a href="faculty-dtr.php" class="text-muted text-decoration-none">Faculty DTR</a>
                </li>
                <li class="breadcrumb-item active text-muted" aria-current="page">Batch Print Faculty DTR</li>
            </ol>
        </nav>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 ms-5">
        <p class="classTitle fs-4 fw-bold">Batch Print Faculty DTR</p>
        <a href="faculty-dtr.php" class="btn btn-back">
            <i class="fa-solid fa-circle-chevron-left"></i>
        </a>
    </div>

    <div class="mx-auto mb-4" style="height: 2px; background-color: #facc15; width:95%;"></div>

    <div class="container bg-white rounded border shadow mb-5 p-4">
        <!-- Month Navigation -->
        <div class="month-navigation">
            <i class="bi bi-caret-left-square-fill" id="prevMonth"></i>
            <span class="fs-4 fw-bold" id="monthDisplay"><?= date("F Y", strtotime("$currentYear-$currentMonth-01")); ?></span>
            <i class="bi bi-caret-right-square-fill" id="nextMonth"></i>
        </div>

        <!-- Print Button -->
        <div class="print-dtr-btn">
            <button class="btn-print" id="batchPrintBtn" disabled>
                <i class="fa-solid fa-print me-2"></i>Batch Print DTR
            </button>
        </div>

        <!-- Additional Select Buttons -->
        <div class="select-buttons">
            <button class="btn-select" id="selectJHS">Select JHS</button>
            <button class="btn-select" id="selectSHS">Select SHS</button>
            <button class="btn-select" id="selectBoth">Select Both JHS & SHS</button>
            <button class="btn-select" id="selectPartTimer">Select Part-timer</button>
            <button class="btn-select" id="selectRegular">Select Regular</button>
        </div>

        <!-- Faculty List Table -->
        <div class="faculty-table-container">
            <table class="faculty-table mb-5" id="facultyTable">
                <thead>
                    <tr>
                        <th class="checkbox-cell"><input type="checkbox" id="selectAll"></th>
                        <th>Faculty Name</th>
                        <th>Department</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="facultyTableBody">
                    <?php 
                    if ($result && mysqli_num_rows($result) > 0) {
                        $index = 1;
                        while ($row = mysqli_fetch_assoc($result)) {
                            $fullName = $row['firstName'] . ' ' . $row['lastName'];
                            $department = $row['department'];
                            $status = $row['status'];
                            echo "<tr data-department='{$department}' data-status='{$status}' data-name='{$fullName}'>
                                    <td class='checkbox-cell'><input type='checkbox' class='row-checkbox' name='selected[]' value='{$row['teacher_id']}'></td>
                                    <td>{$index}. {$fullName}</td>
                                    <td>{$department}</td>
                                    <td>{$status}</td>
                                  </tr>";
                            $index++;
                        }
                    } else {
                        echo "<tr class='no-result-row'><td colspan='4' class='no-result'>No Result</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include('footer.php'); ?>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const facultyTableBody = document.getElementById("facultyTableBody");
            const selectAllCheckbox = document.getElementById("selectAll");
            const batchPrintBtn = document.getElementById("batchPrintBtn");
            const selectJHSBtn = document.getElementById("selectJHS");
            const selectSHSBtn = document.getElementById("selectSHS");
            const selectBothBtn = document.getElementById("selectBoth");
            const selectPartTimerBtn = document.getElementById("selectPartTimer");
            const selectRegularBtn = document.getElementById("selectRegular");
            const monthDisplay = document.getElementById("monthDisplay");
            const prevMonthBtn = document.getElementById("prevMonth");
            const nextMonthBtn = document.getElementById("nextMonth");

            let currentDate = new Date("<?= $currentYear; ?>", "<?= $currentMonth - 1; ?>");

            // Function to update month display
            function updateMonthDisplay() {
                monthDisplay.textContent = currentDate.toLocaleDateString("en-US", { month: "long", year: "numeric" });
            }

            // Event listener for previous month button
            prevMonthBtn.addEventListener("click", function () {
                currentDate.setMonth(currentDate.getMonth() - 1);
                updateMonthDisplay();
                // Reload the page with new month and year
                const newMonth = currentDate.getMonth() + 1;
                const newYear = currentDate.getFullYear();
                window.location.href = `?month=${newMonth}&year=${newYear}`;
            });

            // Event listener for next month button
            nextMonthBtn.addEventListener("click", function () {
                currentDate.setMonth(currentDate.getMonth() + 1);
                updateMonthDisplay();
                // Reload the page with new month and year
                const newMonth = currentDate.getMonth() + 1;
                const newYear = currentDate.getFullYear();
                window.location.href = `?month=${newMonth}&year=${newYear}`;
            });

            // Initial display of current month
            updateMonthDisplay();

            // Toggle batch print button state
            function toggleBatchPrintButton() {
                const checkedRows = facultyTableBody.querySelectorAll("input.row-checkbox:checked");
                batchPrintBtn.disabled = checkedRows.length === 0;
                batchPrintBtn.classList.toggle("enabled", checkedRows.length > 0);
            }

            // Select All functionality
            selectAllCheckbox.addEventListener("change", function () {
                const checkboxes = facultyTableBody.querySelectorAll("input.row-checkbox");
                checkboxes.forEach(checkbox => checkbox.checked = selectAllCheckbox.checked);
                toggleBatchPrintButton();
            });

            // Update button status when individual row checkboxes are clicked
            facultyTableBody.addEventListener("change", function(event) {
                if (event.target.classList.contains("row-checkbox")) {
                    toggleBatchPrintButton();
                }
            });

            // Function to toggle check/uncheck rows based on department or status
            function toggleCheckByAttribute(attribute, value) {
                const rows = facultyTableBody.querySelectorAll("tr");
                let shouldCheck = false;

                // Determine if we should check or uncheck based on current state
                rows.forEach(row => {
                    const checkbox = row.querySelector("input.row-checkbox");
                    if (checkbox && row.dataset[attribute] === value && !checkbox.checked) {
                        shouldCheck = true;
                    }
                });

                // Apply the determined check/uncheck state
                rows.forEach(row => {
                    const checkbox = row.querySelector("input.row-checkbox");
                    if (checkbox && row.dataset[attribute] === value) {
                        checkbox.checked = shouldCheck;
                    }
                });

                toggleBatchPrintButton();
            }

            // Event listeners for department-based selection with toggle functionality
            selectJHSBtn.addEventListener("click", () => toggleCheckByAttribute("department", "Junior High School"));
            selectSHSBtn.addEventListener("click", () => toggleCheckByAttribute("department", "Senior High School"));
            selectBothBtn.addEventListener("click", () => toggleCheckByAttribute("department", "Both (JHS & SHS)"));

            // Event listeners for status-based selection with toggle functionality
            selectPartTimerBtn.addEventListener("click", () => toggleCheckByAttribute("status", "Part-timer"));
            selectRegularBtn.addEventListener("click", () => toggleCheckByAttribute("status", "Regular"));

            // Batch Print DTR Functionality
            batchPrintBtn.addEventListener("click", function () {
                const checkedBoxes = facultyTableBody.querySelectorAll("input.row-checkbox:checked");
                if (checkedBoxes.length === 0) return;

                const teacherIds = Array.from(checkedBoxes).map(checkbox => checkbox.value);
                const month = currentDate.getMonth() + 1;
                const year = currentDate.getFullYear();

                // Send AJAX request to get the DTR data
                fetch('actions/batch-print-dtr-teacher.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        teacherIds: teacherIds,
                        month: month,
                        year: year
                    })
                })
                .then(response => response.text())
                .then(data => {
                    // Open a new window and print the content
                    const printWindow = window.open('', '_blank', 'width=800,height=600');
                    printWindow.document.write(data);
                    printWindow.document.close();
                    printWindow.focus();
                    printWindow.print();
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });
    </script>
</body>
</html>
