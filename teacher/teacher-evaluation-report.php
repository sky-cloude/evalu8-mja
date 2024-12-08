<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <title>Faculty Evaluation Report</title>
    <?php include('../header.php'); ?>
    <!-- CSS file for sidebar -->
    <link rel="stylesheet" href="../css/sidebar.css" />
    <style>
        .classTitle {
            margin-top: 5%;
        }

        /* Centering Select Faculty and Dropdown */
        .faculty-select-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            flex-wrap: wrap;
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
            width: 100%;
            max-width: 200px;
        }

        /* Align Print button to the right, below the combobox */
        .print-button-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            margin-right: 10%;
        }

        .custom-button {
            border-width: 1px !important;
            margin-bottom: -1px;
        }

        .list-group-item {
            transition: background-color 0.15s ease;
            cursor: pointer;
        }

        .list-group-item:hover {
            background-color: #facc15;
        }

        .list-group-item.active {
            background-color: #facc15 !important;
            color: black;
        }

        /* Responsive styles for small screens */
        @media (max-width: 767px) {
            .classTitle {
                margin-top: 15%;
                font-size: 1.5rem;
            }

            .faculty-select-container {
                justify-content: center;
                margin-top: 15px;
                flex-direction: column;
            }

            .faculty-select-container label {
                text-align: center;
                margin-bottom: 10px;
            }

            .print-button-container {
                justify-content: center;
                margin-right: 0;
                margin-top: 10px;
            }

            .print-button-container .dropdown-toggle {
                width: 100%;
                max-width: 200px;
            }

            .report-container {
                margin-top: 20px;
            }
        }

        /* Report Container Styling */
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

        .info-table {
            border: none;
        }

        .info-table td, .info-table th {
            border: none;
            padding: 5px; /* Adjust padding as needed */
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
            .table-bordered {
                border: 1px solid black !important;
            }
            .table-bordered th, .table-bordered td {
                border: 1px solid black !important;
            }
            /* Hide elements that should not appear in print */
            .faculty-select-container,
            .print-button-container,
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

        /* Ensure the print button is disabled by default */
        #printButton:disabled {
            pointer-events: none;
            opacity: 0.6;
        }

        /* Disable dropdown items when print is disabled */
        #printButtonContainer.disabled .dropdown-item {
            pointer-events: none;
            opacity: 0.6;
        }
    </style>

</head>
<body>
    <?php 
    include('teacher-navigation.php'); 
    include('../db_conn.php');

    // Fetch academic years and quarters
    $sql = "SELECT acad_year_id, year, quarter, is_active FROM academic_year ORDER BY year DESC, quarter DESC";
    $result = $conn->query($sql);

    // Initialize the options variable with a blank option
    $options = '<option value="">-- Select Academic Year --</option>';

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $options .= '<option value="' . htmlspecialchars($row['acad_year_id'], ENT_QUOTES, 'UTF-8') . '">' 
                      . htmlspecialchars($row['year'], ENT_QUOTES, 'UTF-8') . ' - Quarter ' 
                      . htmlspecialchars($row['quarter'], ENT_QUOTES, 'UTF-8') . '</option>';
        }
    } else {
        $options = '<option>No data available</option>';
    }

    $conn->close();
    ?>

    <!-- Loader -->
    <div id="loader" style="display: none;">
        <div class="spinner"></div>
    </div>

    <p class="classTitle fs-4 fw-bold ms-5 mb-2">Evaluation Report</p>
    <div class="mx-auto mb-4 bg-warning" style="height: 2px; width: 95%;"></div>

    <!-- Centered Select Faculty and Academic Year Quarter Combobox -->
    <div class="faculty-select-container">
        <label for="academicYearQuarter">Select Academic Year - Quarter:</label>
        <select id="academicYearQuarter" class="me-4" name="academicYearQuarter" onchange="loadSubjects()">
            <?php echo $options; ?>
        </select>
    </div>

    <!-- Print button with dropdown, aligned to the right below the combobox -->
    <div class="print-button-container w-100 text-end text-md-end text-sm-center" style="display: none;" id="printButtonContainer">
        <div class="d-flex justify-content-end justify-content-md-end justify-content-center mt-4">
            <button class="btn btn-success dropdown-toggle me-4" type="button" id="printButton" data-bs-toggle="dropdown" aria-expanded="false" style="width:10rem;" disabled>
                Print
            </button>
            <ul class="dropdown-menu" aria-labelledby="printButton">
                <li><a class="dropdown-item text-dark" href="#" onclick="printSubjectOnly()">This subject only</a></li>
                <li><a class="dropdown-item text-dark" href="#" onclick="printAllSubjects()">All subjects</a></li>
            </ul>
        </div>
    </div>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Left Sidebar with Classes -->
            <div class="col-lg-3 col-md-4 col-12 button-group mb-md-0 mb-3">
                <!-- Dynamically populated list of classes -->
                <div class="list-group border-dark">
                    <!-- More buttons will be dynamically generated based on subjects -->
                </div>
            </div>

            <!-- Right Main Content for Dynamic Evaluation Report -->
            <div class="col-lg-9 col-md-8 col-12">
                <div id="evaluationReportContainer" class="report-container bg-white rounded border border-dark p-4 mt-md-0 mt-3">
                    <!-- The evaluation report will be dynamically loaded here -->
                    <p class="text-center"><i>Select a subject to view the evaluation report.</i></p>
                </div>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>

    <script>
        let activeButton = null; // To track the currently active button
        let selectedSubjectId = null; // Track the selected subject ID for printing
        const printButton = document.getElementById('printButton'); // Reference to the print button
        const printButtonContainer = document.getElementById('printButtonContainer'); // Reference to print button container

        function loadSubjects() {
            const acadYear = document.getElementById('academicYearQuarter').value;

            // Reset selectedSubjectId and disable print button
            selectedSubjectId = null;
            printButton.disabled = true;
            printButton.classList.add('disabled');
            printButtonContainer.classList.add('disabled');

            // Hide print button container if no academic year is selected
            if (!acadYear) {
                printButtonContainer.style.display = 'none';
                document.querySelector('.list-group').innerHTML = '';
                document.getElementById('evaluationReportContainer').innerHTML = '<p class="text-center"><i>Select a subject to view the evaluation report.</i></p>';
                return;
            }

            if (acadYear) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'actions/fetch-classes.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                // Show the loader
                document.getElementById("loader").style.display = "flex";

                xhr.onload = function() {
                    const evaluationReportContainer = document.getElementById('evaluationReportContainer');
                    const listGroupContainer = document.querySelector('.list-group');

                    // Hide the loader
                    document.getElementById("loader").style.display = "none";

                    if (this.status === 200) {
                        listGroupContainer.innerHTML = this.responseText;

                        if (this.responseText.includes("No subjects or classes available for the selected academic year.")) {
                            evaluationReportContainer.style.display = 'none';
                            printButtonContainer.style.display = 'none';

                            // Display the no data message with Bootstrap classes
                            listGroupContainer.innerHTML = `
                                <div class="border border-dark rounded bg-white text-center p-4">
                                    No subjects or classes available for the selected academic year.
                                </div>
                            `;
                        } else {
                            // Show the evaluation report container with a default message
                            evaluationReportContainer.innerHTML = '<p class="text-center"><i>Select a subject to view the evaluation report.</i></p>';
                            evaluationReportContainer.style.display = 'block';
                            printButtonContainer.style.display = 'flex';

                            // Ensure the print button is disabled until a subject is selected
                            printButton.disabled = true;
                            printButton.classList.add('disabled');
                            printButtonContainer.classList.add('disabled');

                            // Add event listeners to the list items
                            document.querySelectorAll('.list-group-item').forEach(function(button) {
                                button.addEventListener('click', function() {
                                    const subjectId = this.getAttribute('data-subject-id');
                                    loadEvaluationReport(subjectId, this);
                                });
                            });
                        }
                    } else {
                        alert("Failed to load subjects. Please try again.");
                    }
                };
                xhr.send('acad_year_id=' + encodeURIComponent(acadYear));
            }
        }

        function loadEvaluationReport(subjectId, button) {
            selectedSubjectId = subjectId; // Track the selected subject ID
            console.log("Selected Subject ID:", selectedSubjectId); // Debugging

            const acadYear = document.getElementById('academicYearQuarter').value;

            if (acadYear && subjectId) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'actions/fetch-teacher-evaluation-report.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                // Show the loader
                document.getElementById("loader").style.display = "flex";

                xhr.onload = function() {
                    const evaluationReportContainer = document.getElementById('evaluationReportContainer');

                    // Hide the loader
                    document.getElementById("loader").style.display = "none";

                    if (this.status === 200) {
                        evaluationReportContainer.innerHTML = this.responseText;
                        evaluationReportContainer.style.display = 'block';

                        if (this.responseText.includes("No data found for the selected subject.")) {
                            printButton.disabled = true;
                            printButton.classList.add('disabled');
                            printButtonContainer.classList.add('disabled');
                        } else {
                            printButton.disabled = false;
                            printButton.classList.remove('disabled');
                            printButtonContainer.classList.remove('disabled');
                        }

                        // Reset the background color of the previously active button, if any
                        if (activeButton) {
                            activeButton.classList.remove('active');
                            activeButton.style.backgroundColor = ''; // Reset to default background
                        }

                        // Set the clicked button as active
                        button.classList.add('active');
                        button.style.backgroundColor = '#facc15'; // Set active background color
                        activeButton = button; // Set the clicked button as the active one
                    } else {
                        alert("Failed to load the evaluation report. Please try again.");
                    }
                };
                xhr.send('acad_year_id=' + encodeURIComponent(acadYear) + '&subject_id=' + encodeURIComponent(subjectId));
            }
        }

        function printSubjectOnly() {
            console.log("Attempting to print subject with ID:", selectedSubjectId); // Debugging
            if (selectedSubjectId) {
                const evaluationReportContainer = document.getElementById('evaluationReportContainer').innerHTML;

                // Create an iframe for printing
                const printFrame = document.createElement('iframe');
                printFrame.name = "printFrame";
                printFrame.style.position = "absolute";
                printFrame.style.top = "-1000px";
                document.body.appendChild(printFrame);

                const printDocument = printFrame.contentWindow || printFrame.contentDocument.document || printFrame.contentDocument;

                // Write the contents to the iframe document
                printDocument.document.open();
                printDocument.document.write('<html><head><title>Print</title>');
                // Optionally include CSS here if needed
                printDocument.document.write('<style>body { font-family: Arial, sans-serif; }</style>');
                printDocument.document.write('</head><body>');
                printDocument.document.write(evaluationReportContainer);
                printDocument.document.write('</body></html>');
                printDocument.document.close();

                // Wait for the content to load and then print
                setTimeout(function() {
                    printFrame.contentWindow.focus();
                    printFrame.contentWindow.print();

                    // Remove the iframe after printing
                    document.body.removeChild(printFrame);
                }, 500);
            } else {
                alert("Please select a subject to print.");
            }
        }

        // Function to handle printing all subjects
        function printAllSubjects() {
            const acadYear = document.getElementById('academicYearQuarter').value;

            if (acadYear) {
                // Show the loader
                document.getElementById("loader").style.display = "flex";

                // Make an AJAX call to fetch all subjects for the teacher in the selected academic year
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'actions/fetch-all-teacher-evaluation-reports.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    // Hide the loader
                    document.getElementById("loader").style.display = "none";

                    if (this.status === 200) {
                        const evaluationReportContent = this.responseText;

                        // Create an iframe for printing
                        const printFrame = document.createElement('iframe');
                        printFrame.name = "printFrame";
                        printFrame.style.position = "absolute";
                        printFrame.style.top = "-1000px";
                        document.body.appendChild(printFrame);

                        const printDocument = printFrame.contentWindow || printFrame.contentDocument.document || printFrame.contentDocument;

                        // Write the contents to the iframe document
                        printDocument.document.open();
                        printDocument.document.write('<html><head><title>Print All Subjects</title>');
                        // Optionally include CSS here if needed
                        printDocument.document.write('<style>body { font-family: Arial, sans-serif; }</style>');
                        printDocument.document.write('</head><body>');
                        printDocument.document.write(evaluationReportContent);
                        printDocument.document.write('</body></html>');
                        printDocument.document.close();

                        // Wait for the content to load and then print
                        setTimeout(function() {
                            printFrame.contentWindow.focus();
                            printFrame.contentWindow.print();

                            // Remove the iframe after printing
                            document.body.removeChild(printFrame);
                        }, 500);
                    } else {
                        alert("Failed to load the evaluation reports.");
                    }
                };
                xhr.send('acad_year_id=' + encodeURIComponent(acadYear));
            } else {
                alert("Please select an academic year to print all subjects.");
            }
        }
    </script>
</body>
</html>
