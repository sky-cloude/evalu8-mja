<!-- PHP code to fetch academic year data and populate the combobox -->
<?php 
    session_start(); 
    // Connect to the database
    include('../db_conn.php'); 
    
    // Query to get academic years, sorted by year (earliest to latest)
    $query = "SELECT acad_year_id, year, quarter, is_active FROM academic_year ORDER BY year DESC, quarter DESC";
    $result = mysqli_query($conn, $query);

    // Prepare the combobox options
    $options = '';
    $selected = '';  // To hold the selected option where is_active = 1

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Construct the option value and label
            $value = $row['year'] . '-Q' . $row['quarter'];
            $label = $row['year'] . ' - Quarter ' . $row['quarter'];

            // Check if the current row is active
            if ($row['is_active'] == 1) {
                $selected = 'selected';
            } else {
                $selected = '';
            }

            // Append each option to the options string
            $options .= '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
        }
    }
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation Report</title>
    <?php include('../header.php'); ?>
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/admin-elements.css" />

    <style>
        /* for the titles  */
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

        /* small devices */
        @media (max-width: 767px) { 
            .classTitle {
                margin-top: 1%;
                padding-top: 1%;
            }

            .breadcrumbs {
                margin-top: 10%;
                padding-top: 6%;
                margin-bottom: 2%;
            }
        }

        /* Centering Select Faculty and Dropdown */
        .faculty-select-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
        }

        .faculty-select-container label {
            margin-right: 10px;
        }

        .faculty-select-container select {
            padding: 5px;
            font-size: 1rem;
            background-color: #fafafa;
            border: 1px solid black;  
            border-radius: 5px;      
            width: 200px;  
            margin-right: 20px;        
        }

        /* Right Content Styling */
        .report-container {
            background-color: #fff; /* White background */
            border-radius: 8px; /* Curved border */
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid black; /* Black border */
            font-size: 0.85rem; /* Smaller font size */
            max-width: 95%; /* Make sure it fits like in the first image */
            margin: 0 auto;
        }

        .report-title {
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 10px;
        }

        .report-table {
            margin-top: 15px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .report-table thead {
            background-color: #343a40;
            color: #fff;
        }

        .report-table tbody tr:nth-child(odd) {
            background-color: #f9f9f9;
        }

        .report-table tbody td {
            padding: 8px;
            font-size: 0.8rem; /* Smaller font size */
            text-align: center;
        }

        /* Alternating colors and black border for class list buttons */
        .list-group-item {
            border: 1px solid black; /* Black border */
            font-size: 1rem;
            padding: 10px; /* Optional padding for better look */
            color: #000; /* Default text color */
            text-align: left; /* Center the text */
        }

        .list-group-item:nth-child(odd) {
            background-color: #fafafa; /* Dark background for odd items */
            color: #000; /* White text for dark background */
        }

        .list-group-item:nth-child(odd):hover {
            background-color: #facc15; /* Dark background for odd items */
            color: #000; /* White text for dark background */
        }

        .list-group-item:nth-child(even) {
            background-color: #fafafa; /* Light background for even items */
            color: #000; /* Black text for light background */
        }

        .list-group-item:nth-child(even):hover {
            background-color: #facc15; /* Light background for even items */
            color: #000; /* Black text for light background */
        }

        @media (max-width: 767px) {
            .report-container {
                padding: 10px;
            }

            .report-title {
                font-size: 1rem;
            }

            .report-table tbody td {
                padding: 6px;
                font-size: 0.75rem;
            }

            .button-group {
                margin-bottom: 2.5rem;
            }

            .faculty-select-container {
                flex-direction: column;
            }

            .faculty-select-container label {
                margin-right: 0;
                margin-bottom: 5px;
            }

            .faculty-select-container select {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }

        /* Set page size and margins for printing on short bond paper */
        @page {
            size: 8in 11in;
            margin: 0.5in; /* Adjust margins as needed */
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .report-container {
                page-break-inside: avoid; /* Avoid page break inside the container */
                margin-bottom: 0; /* Remove bottom margin */
            }
            .report-table, .report-table th, .report-table td {
                border: 1px solid black !important; /* Ensure borders are applied during printing */
                border-collapse: collapse; /* Make sure borders don't overlap */
            }
            /* Hide elements that should not appear in print */
            .breadcrumbs-container,
            .faculty-select-container,
            .button-group,
            .list-group {
                display: none;
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
        // include('../db_conn.php'); 
    ?>

    <!-- Loader -->
    <div id="loader" style="display: none;">
        <div class="spinner"></div>
    </div>

     <!-- Breadcrumbs -->
     <div class="breadcrumbs-container">
        <nav aria-label="breadcrumb" class="breadcrumbs ms-4 bg-white border rounded py-2 px-3 shadow" style="height: 40px; align-items: center; display: flex;">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="admin-dashboard.php" class="text-muted text-decoration-none">
                        <i class="fa-solid fa-house"></i> Home
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="#" class="text-muted text-decoration-none">
                        Evaluation Report
                    </a>
                </li>
            </ol>
        </nav>
    </div>

    <p class="classTitle fs-4 fw-bold ms-5 mb-2">Evaluation Report</p>
    <div class="mx-auto mb-4 bg-warning" style="height: 2px; width: 95%;"></div>

    <!-- Centered Select Faculty and Academic Year Quarter Combobox -->
    <div class="faculty-select-container">
        <!-- Select Academic Year - Quarter -->
        <label for="academicYearQuarter">Select Academic Year - Quarter:</label>
        <select id="academicYearQuarter" class="me-4" name="academicYearQuarter" onchange="loadTeachers()">
            <?php echo $options; // Output the dynamically generated options ?>
        </select>

        <!-- Select Faculty -->
        <label for="faculty">Select Faculty:</label>
        <select id="faculty" name="faculty">
            <option value="">Select a faculty</option>
        </select>
    </div>

    <div class="text-end mt-3">
        <!-- Batch Print Button (Initially hidden) -->
        <button id="batchPrintBtn" type="button" class="btn me-2" 
            style="width: 200px; display: none; background-color: #281313; color: white;" 
            onclick="redirectToBatchPrint()" 
            onmouseover="this.style.backgroundColor='#facc15'; this.style.color='black';" 
            onmouseout="this.style.backgroundColor='#281313'; this.style.color='white';">
            Batch Print Faculties
        </button>

        <!-- Dropdown for Print Button (Initially hidden) -->
        <div id="printBtnGroup" class="btn-group" style="display: none;">
            <button id="printButton" type="button" class="btn btn-success dropdown-toggle me-5" style="width: 100px;" data-bs-toggle="dropdown" aria-expanded="false" disabled>
                Print
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item text-dark" href="#" onclick="printSubjectReport()">This subject only</a></li>
                <li><a class="dropdown-item text-dark" href="#" onclick="printAllSubjects()">All subjects</a></li>
            </ul>
        </div>
    </div>

    
    <div class="container mt-4">
        <div class="row">
            <!-- Left Sidebar with Classes -->
            <div class="col-md-3 col-12 button-group">
                <div class="list-group">
                    <!-- Dynamically populated list of classes -->
                </div>
            </div>

            <!-- Right Main Content for Dynamic Evaluation Report -->
            <div class="col-md-9 col-12">
                <div id="evaluationReportContainer" class="report-container">
                    <!-- The evaluation report will be dynamically loaded here -->
                     <p class="text-center"><i>Select a subject handled by the faculty from the panel on the left.</i></p>
                </div>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>

    <script>
        // Variable to keep track of the currently active button
        let activeButton = null;

        // Hide the evaluation report container and buttons initially
        document.getElementById("evaluationReportContainer").style.display = "none";
        document.getElementById("batchPrintBtn").style.display = "none";
        document.getElementById("printBtnGroup").style.display = "none";

        // Function to handle button click
        function handleClassButtonClick(button, subjectId, evaluationId) {
            // Reset the previously active button's background color
            if (activeButton) {
                if (activeButton.classList.contains('odd-item')) {
                    activeButton.style.backgroundColor = '#44311f';  // Restore odd item color
                    activeButton.style.color = '#fff';  // Restore odd item text color
                } else {
                    activeButton.style.backgroundColor = '#fafafa';  // Restore even item color
                    activeButton.style.color = '#000';  // Restore even item text color
                }
            }

            // Highlight the clicked button
            button.style.backgroundColor = '#facc15';
            button.style.color = '#000';  // Black text on yellow background
            activeButton = button;

            document.getElementById("printButton").disabled = false;
            
            // Get the selected faculty and academic year-quarter
            var teacher_id = document.getElementById("faculty").value;
            var academicYearQuarter = document.getElementById("academicYearQuarter").value;

            // Show the evaluation report container when selections are made
            if (teacher_id && academicYearQuarter) {
                document.getElementById("evaluationReportContainer").style.display = "block";
            }

            // Show the loader
            document.getElementById("loader").style.display = "flex";

            // Send AJAX request to fetch evaluation report for the selected subject
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "actions/fetch-evaluation-report.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    // Hide the loader
                    document.getElementById("loader").style.display = "none";

                    // Update the report container with the returned HTML
                    document.getElementById("evaluationReportContainer").innerHTML = xhr.responseText;

                    // Check if 'No data' message is present and disable the 'This subject only' link
                    var noDataElement = document.getElementById('noData');
                    var printLink = document.querySelector("a[onclick='printSubjectReport()']");
                    if (noDataElement) {
                        printLink.classList.add('disabled');  // Disable the link
                        printLink.style.pointerEvents = 'none';  // Make it unclickable
                    } else {
                        printLink.classList.remove('disabled');  // Enable the link
                        printLink.style.pointerEvents = 'auto';  // Make it clickable
                    }
                }
            };
            xhr.send("teacher_id=" + teacher_id + "&academicYearQuarter=" + academicYearQuarter + "&subject_id=" + subjectId + "&evaluation_id=" + evaluationId);
        }

        // Function to load teachers based on the selected academic year-quarter
        function loadTeachers() {
            var selectedYearQuarter = document.getElementById("academicYearQuarter").value;

            // Send an AJAX request to fetch the teachers
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "actions/fetch-teachers.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            // Show the loader
            document.getElementById("loader").style.display = "flex";

            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    // Hide the loader
                    document.getElementById("loader").style.display = "none";

                    document.getElementById("faculty").innerHTML = '<option value="">Select a faculty</option>' + xhr.responseText;
                    document.querySelector(".list-group").innerHTML = '';  // Clear the class list as no teacher has been selected yet

                    // Hide the evaluation report container and buttons if no selection is made
                    toggleEvaluationContainer();
                }
            };
            xhr.send("academicYearQuarter=" + selectedYearQuarter);
        }

        // Function to load classes based on the selected teacher and academic year-quarter
        function loadClasses() {
            var teacher_id = document.getElementById("faculty").value;
            var selectedYearQuarter = document.getElementById("academicYearQuarter").value;

            if (teacher_id) {
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "actions/fetch-classes.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                // Show the loader
                document.getElementById("loader").style.display = "flex";

                xhr.onreadystatechange = function () {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        // Hide the loader
                        document.getElementById("loader").style.display = "none";

                        document.querySelector(".list-group").innerHTML = xhr.responseText;

                        // Re-add event listeners for the new buttons with their subject IDs
                        document.querySelectorAll('.list-group-item').forEach(function (button) {
                            button.addEventListener('click', function () {
                                var subjectId = button.getAttribute("data-subject-id");
                                var evaluationId = button.getAttribute("data-evaluation-id");
                                handleClassButtonClick(button, subjectId, evaluationId);
                            });
                        });

                        // Show the evaluation container and buttons if a faculty is selected
                        toggleEvaluationContainer();
                    }
                };
                xhr.send("teacher_id=" + teacher_id + "&academicYearQuarter=" + selectedYearQuarter);
            } else {
                document.querySelector(".list-group").innerHTML = '';  // Clear the class list if no teacher is selected
            }
        }

        // Function to toggle the evaluation report container and buttons visibility
        function toggleEvaluationContainer() {
            var academicYearQuarter = document.getElementById("academicYearQuarter").value;
            var faculty = document.getElementById("faculty").value;

            if (academicYearQuarter !== "" && faculty !== "") {
                // Show the evaluation report container and the print buttons
                document.getElementById("evaluationReportContainer").style.display = "block";
                document.getElementById("batchPrintBtn").style.display = "inline-block";
                document.getElementById("printBtnGroup").style.display = "inline-block";
            } else {
                // Hide the evaluation report container and the print buttons
                document.getElementById("evaluationReportContainer").style.display = "none";
                document.getElementById("batchPrintBtn").style.display = "none";
                document.getElementById("printBtnGroup").style.display = "none";
            }
        }

        function printSubjectReport() {
            var printContents = document.getElementById("evaluationReportContainer").innerHTML;

            // Create an iframe for printing
            var printFrame = document.createElement('iframe');
            printFrame.name = "printFrame";
            printFrame.style.position = "absolute";
            printFrame.style.top = "-1000px";
            document.body.appendChild(printFrame);

            var printDocument = printFrame.contentWindow || printFrame.contentDocument.document || printFrame.contentDocument;

            // Write the contents to the iframe document
            printDocument.document.open();
            printDocument.document.write('<html><head><title>Print</title>');
            printDocument.document.write('</head><body>');
            printDocument.document.write(printContents);
            printDocument.document.close();

            // Wait for the content to load and then print
            setTimeout(function() {
                printFrame.contentWindow.focus();
                printFrame.contentWindow.print();

                // Remove the iframe after printing
                document.body.removeChild(printFrame);
            }, 500);
        }

        // Function to print all subjects for a specific teacher and academic year
        function printAllSubjects() {
            var allReports = '';
            var hasData = false;
            var buttons = document.querySelectorAll('.list-group-item');
            var totalButtons = buttons.length;
            var processedButtons = 0;

            // Show the loader
            document.getElementById("loader").style.display = "flex";

            buttons.forEach(function (button, index) {
                var subjectId = button.getAttribute("data-subject-id");
                var evaluationId = button.getAttribute("data-evaluation-id");

                var xhr = new XMLHttpRequest();
                xhr.open("POST", "actions/fetch-evaluation-report.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                xhr.onload = function () {
                    if (xhr.status == 200 && !xhr.responseText.includes('No data found') && xhr.responseText.trim() !== "") {
                        allReports += xhr.responseText;

                        // Only add a page break if this is not the last report
                        if (index < buttons.length - 1) {
                            allReports += '<div style="page-break-before: always;"></div>';
                        }

                        hasData = true;
                    }
                    processedButtons++;

                    // Once all buttons are processed, proceed to print
                    if (processedButtons === totalButtons) {
                        // Hide the loader
                        document.getElementById("loader").style.display = "none";

                        if (hasData && allReports !== '') {
                            // Create an iframe for printing
                            var printFrame = document.createElement('iframe');
                            printFrame.name = "printFrame";
                            printFrame.style.position = "absolute";
                            printFrame.style.top = "-1000px";
                            document.body.appendChild(printFrame);

                            var printDocument = printFrame.contentWindow || printFrame.contentDocument.document || printFrame.contentDocument;
                            printDocument.document.open();
                            printDocument.document.write('<html><head><title>Print</title>');
                            printDocument.document.write('</head><body>');
                            printDocument.document.write(allReports);
                            printDocument.document.close();

                            // Wait for the content to load and then print
                            setTimeout(function () {
                                printFrame.contentWindow.focus();
                                printFrame.contentWindow.print();

                                // Remove the iframe after printing
                                document.body.removeChild(printFrame);
                            }, 500);
                        } else {
                            alert("No subjects found with evaluation data to print.");
                        }
                    }
                };

                // Send the request with necessary parameters for each subject
                xhr.send("teacher_id=" + encodeURIComponent(document.getElementById("faculty").value) +
                    "&academicYearQuarter=" + encodeURIComponent(document.getElementById("academicYearQuarter").value) +
                    "&subject_id=" + encodeURIComponent(subjectId) +
                    "&evaluation_id=" + encodeURIComponent(evaluationId));
            });
        }

        // Function to redirect to batch-print-faculty.php
        function redirectToBatchPrint() {
            var academicYearQuarter = document.getElementById("academicYearQuarter").value;
            var faculty = document.getElementById("faculty").value;

            if (academicYearQuarter && faculty) {
                // Redirect to batch-print-faculty.php and pass the selected values as GET parameters
                window.location.href = 'batch-print-faculty.php?year_quarter=' + encodeURIComponent(academicYearQuarter);
            } else {
                alert("Please select both an academic year-quarter and a faculty.");
            }
        }


        // Trigger loadTeachers function on page load to populate the faculty combobox
        window.onload = function () {
            loadTeachers();
        };

        // Add event listeners for the change events to update classes and toggle buttons
        document.getElementById("faculty").addEventListener("change", function () {
            loadClasses();
            toggleEvaluationContainer();  // Check if both fields have valid values
            document.getElementById("printButton").disabled = true;
        });

        document.getElementById("academicYearQuarter").addEventListener("change", function () {
            loadTeachers();
            toggleEvaluationContainer();  // Check if both fields have valid values
            document.getElementById("printButton").disabled = true;
        });
    </script>


</body>
</html>
