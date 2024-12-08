<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Batch Print Faculties</title>
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
        /* for the titles  */
        .classTitle {
            margin-top: 1%;
        }

        .btn-back {
            background: none;
            border: none;
            padding: 0;
            padding-right: 2rem;
            color: #281313;
        }

        .btn-back i {
            font-size: 2.5rem; /* Adjust the size of the icon */
        }

        .btn-back:hover {
            color: #facc15;
        }

        /* Align title to the left and button to the right */
        .title-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1%; /* Adjust to match the title spacing */
        }

        /* Custom Checkbox */
        input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid #281313;
            border-radius: 4px;
            outline: none;
            cursor: pointer;
            background-color: transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        input[type="checkbox"]:checked {
            background-color: #281313;
        }

        input[type="checkbox"]:checked::after {
            content: "✔";
            color: white;
            font-weight: bold;
            font-size: 12px; /* Make the checkmark smaller */
            line-height: 1;
        }

        /* small devices */
        @media (max-width: 767px) { 
            .title-container {
                margin-top: 1%;
                padding-top: 1%;
            }

            .breadcrumbs {
                margin-top: 10%;
                padding-top: 6%;
                margin-bottom: 2%;
            }
        }

        /* Loader Styles */
        #loader {
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            background: rgba(255, 255, 255, 0.4);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .spinner {
            border: 16px solid #f3f3f3; /* Light grey */
            border-top: 16px solid #facc15; /* Yellow */
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php
    include('navigation.php'); 
    include('../db_conn.php'); // Include database connection

    if (isset($_GET['year_quarter'])) {
        $year_quarter = $_GET['year_quarter'];
        $parts = explode('-Q', $year_quarter);
        $academic_year = $parts[0];
        $quarter = $parts[1];
    } else {
        echo "<div class='title-container ms-5 mb-3'><p class='classTitle fs-4 fw-bold mb-2'>No Academic Year - Quarter Selected.</p></div>";
        exit(); // Exit if no year_quarter is provided
    }
    ?>

    <!-- Loader -->
    <div id="loader" style="display: none;">
        <div class="spinner"></div>
    </div>

    <!-- Breadcrumbs -->
    <div class="breadcrumbs-container">
        <nav aria-label="breadcrumb" class="breadcrumbs ms-4 bg-white border rounded shadow py-2 px-3" style="height: 40px; align-items: center; display: flex;">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="admin-dashboard.php" class="text-muted text-decoration-none">
                        <i class="fa-solid fa-house"></i> Home
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="admin-eval-report.php" class="text-muted text-decoration-none">
                        Evaluation Report
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="#" class="text-muted text-decoration-none">
                        Batch Print Faculties
                    </a>
                </li>
            </ol>
        </nav>
    </div>

    <div class="title-container ms-5 mb-3">
        <p class='classTitle fs-4 fw-bold mb-2'>Batch Print Faculties for A.Y. <?php echo htmlspecialchars($academic_year); ?> | Quarter <?php echo htmlspecialchars($quarter); ?></p>
        <a href="admin-eval-report.php" class="btn btn-back">
            <i class="fa-solid fa-circle-chevron-left"></i>
        </a>
    </div>

    <div class="mx-auto mb-4 bg-warning" style="height: 2px; width: 95%;"></div>

    <div class="d-flex justify-content-center mb-3 flex-wrap">
        <button type="button" class="btn mx-2 mb-2" onclick="filterCheck('Junior High School')" 
            style="background-color: #281313; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin: 0 10px 10px 10px;"
            onmouseover="this.style.backgroundColor='#facc15'; this.style.color='black';"
            onmouseout="this.style.backgroundColor='#281313'; this.style.color='white';">
            Select JHS Faculty
        </button>

        <button type="button" class="btn mx-2 mb-2" onclick="filterCheck('Senior High School')" 
            style="background-color: #281313; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;"
            onmouseover="this.style.backgroundColor='#facc15'; this.style.color='black';"
            onmouseout="this.style.backgroundColor='#281313'; this.style.color='white';">
            Select SHS Faculty
        </button>

        <button type="button" class="btn mx-2 mb-2" onclick="filterCheckBoth()" 
            style="background-color: #281313; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;"
            onmouseover="this.style.backgroundColor='#facc15'; this.style.color='black';"
            onmouseout="this.style.backgroundColor='#281313'; this.style.color='white';">
            Select Both JHS & SHS
        </button>
        
        <button type="button" class="btn mx-2 mb-2" onclick="batchPrintReports()"
            style="background-color: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;"
            onmouseover="this.style.backgroundColor='#218838'; this.style.color='white';"
            onmouseout="this.style.backgroundColor='#28a745'; this.style.color='white';">
            Print
        </button>
    </div>

    <?php 
        $yearQuery = "SELECT acad_year_id FROM academic_year WHERE year = '$academic_year' AND quarter = '$quarter'";
        $yearResult = mysqli_query($conn, $yearQuery);

        if ($yearResult && mysqli_num_rows($yearResult) > 0) {
            $row = mysqli_fetch_assoc($yearResult);
            $acad_year_id = $row['acad_year_id'];

            $evaluationQuery = "SELECT evaluation_id FROM evaluation_list WHERE acad_year_id = '$acad_year_id'";
            $evaluationResult = mysqli_query($conn, $evaluationQuery);

            if ($evaluationResult && mysqli_num_rows($evaluationResult) > 0) {
                $evaluationIds = [];
                while ($evaluationRow = mysqli_fetch_assoc($evaluationResult)) {
                    $evaluationIds[] = $evaluationRow['evaluation_id'];
                }

                $evaluationIdsStr = implode("','", $evaluationIds);
                $evaluationIdsStr = "'" . $evaluationIdsStr . "'"; 

                $teacherQuery = "
                    SELECT DISTINCT t.teacher_id, t.firstName, t.lastName, t.department
                    FROM subject_to_eval se
                    JOIN teacher_account t ON se.teacher_id = t.teacher_id
                    WHERE se.evaluation_id IN ($evaluationIdsStr)
                ";
                $teacherResult = mysqli_query($conn, $teacherQuery);

                if ($teacherResult && mysqli_num_rows($teacherResult) > 0) {
                    echo '<div class="d-flex justify-content-center px-4">
                            <table class="table" style="table-layout: auto; width: 50%; max-width: 100%; border: 1px solid black;">
                                <thead>
                                    <tr>
                                        <th class="text-end" style="padding-right: 10px;">
                                            <input type="checkbox" onclick="checkAll(this.checked)">
                                        </th>
                                        <th style="padding-left: 10px; padding-right: 10px;">Faculty Name</th>
                                        <th style="padding-left: 10px;">Department</th>
                                    </tr>
                                </thead>
                                <tbody>';
                    
                    // Counter variable to keep track of row numbers
                    $counter = 1;

                    while ($teacherRow = mysqli_fetch_assoc($teacherResult)) {
                        echo '<tr class="faculty-row" data-department="' . htmlspecialchars($teacherRow['department']) . '">
                                <td class="text-end" style="padding-right: 10px;">
                                    <input type="checkbox" name="faculty[]" value="' . htmlspecialchars($teacherRow['teacher_id']) . '">
                                </td>
                                <td style="padding-left: 10px; padding-right: 10px;">' . $counter . '. ' . htmlspecialchars($teacherRow['firstName']) . ' ' . htmlspecialchars($teacherRow['lastName']) . '</td>
                                <td style="padding-left: 10px;">' . htmlspecialchars($teacherRow['department']) . '</td>
                            </tr>';
                        $counter++; // Increment the counter for the next row
                    }
                
                    echo '</tbody></table></div>';
                } else {
                    echo '<p class="text-center">No teachers found for A.Y. ' . htmlspecialchars($academic_year) . ' | Quarter ' . htmlspecialchars($quarter) . '.</p>';
                }
            } else {
                echo '<p class="text-center">No evaluations found for A.Y. ' . htmlspecialchars($academic_year) . ' | Quarter ' . htmlspecialchars($quarter) . '.</p>';
            }
        } else {
            echo '<p class="text-center">Invalid Academic Year or Quarter.</p>';
        }
    ?>

    <?php include('footer.php'); ?>

    <script>
        function checkAll(isChecked) {
            let checkboxes = document.querySelectorAll('input[name="faculty[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
        }

        function filterCheck(department) {
            let rows = document.querySelectorAll('.faculty-row');
            rows.forEach(row => {
                let departmentCell = row.getAttribute('data-department');
                let checkbox = row.querySelector('input[type="checkbox"]');
                if (departmentCell === department) {
                    checkbox.checked = true;
                } else {
                    checkbox.checked = false;
                }
            });
        }

        function filterCheckBoth() {
            let rows = document.querySelectorAll('.faculty-row');
            rows.forEach(row => {
                let departmentCell = row.getAttribute('data-department');
                let checkbox = row.querySelector('input[type="checkbox"]');
                if (departmentCell === "Both (JHS & SHS)") {
                    checkbox.checked = true;
                } else {
                    checkbox.checked = false;
                }
            });
        }

        function batchPrintReports() {
            // Get all selected faculty checkboxes in the order they are displayed in the table
            const selectedFaculty = document.querySelectorAll('.faculty-row input[name="faculty[]"]:checked');

            // Check if no faculty is selected
            if (selectedFaculty.length === 0) {
                alert("Please select at least one faculty.");
                return;
            }

            let allReports = '';
            let facultyIds = [];

            // Collect selected faculty IDs in the displayed order
            selectedFaculty.forEach(checkbox => {
                facultyIds.push(checkbox.value);
            });

            // Show the loader
            document.getElementById("loader").style.display = "flex";

            // Function to fetch reports in sequence to maintain correct order
            function fetchReport(index) {
                if (index >= facultyIds.length) {
                    // Once all faculties are processed, trigger the print
                    printAllReports(allReports);
                    return;
                }

                // Log for debugging to ensure correct processing order
                console.log("Processing faculty ID:", facultyIds[index]);

                let xhr = new XMLHttpRequest();
                xhr.open('POST', 'actions/fetch-batch-reports.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onload = function () {
                    if (xhr.status == 200) {
                        // Append the report HTML for the current faculty
                        allReports += xhr.responseText;

                        // Add a page break after each faculty report, except for the last one
                        if (index < facultyIds.length - 1) {
                            allReports += '<div style="page-break-before: always;"></div>';
                        }

                        // Recursively fetch the next report
                        fetchReport(index + 1);
                    } else {
                        alert('Failed to fetch reports.');
                        // Hide the loader
                        document.getElementById("loader").style.display = "none";
                    }
                };

                // Send faculty_id and year_quarter to fetch reports for this faculty
                xhr.send('faculty_id=' + encodeURIComponent(facultyIds[index]) + '&year_quarter=' + encodeURIComponent('<?php echo $year_quarter; ?>'));
            }

            // Start fetching the first report in sequence
            fetchReport(0);

            function printAllReports(allReports) {
                // Hide the loader
                document.getElementById("loader").style.display = "none";

                let printWindow = window.open('', '', 'height=700,width=900');
                printWindow.document.write('<html><head><title>Print Reports</title></head><body>');
                printWindow.document.write(allReports);
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.print();
            }
        }
    </script>

</body>
</html>
