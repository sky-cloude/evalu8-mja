<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <title>Evaluation Controls</title>
    <?php include('../header.php'); ?>
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/admin-elements.css" />
</head>
<body>
    <?php session_start(); ?>
    <?php include('navigation.php'); ?>
    
    <style>
        .breadcrumbs-container {
            margin-top: 3%;
            margin-left: 0%;
            margin-bottom: 1.5%;
            padding-top: 2%;
            width: 98%;
        }

        @media (max-width: 767px) { 
            .breadcrumbs {
                margin-top: 10%;
                padding-top: 6%;
            }
        }
    </style>

    <!-- Breadcrumbs -->
    <div class="breadcrumbs-container">
        <nav aria-label="breadcrumb" class="breadcrumbs ms-4 bg-white border shadow rounded py-2 px-3" style="height: 40px; align-items: center; display: flex;">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="admin-dashboard.php" class="text-muted text-decoration-none">
                        <i class="fa-solid fa-house"></i> Home
                    </a>
                </li>
                <li class="breadcrumb-item active text-muted" aria-current="page">Evaluation Controls</li>
            </ol>
        </nav>
    </div>

    
    <p class="classTitle fs-4 fw-bold ms-5 mb-3">Evaluation Controls</p>
    <div class="mx-auto mb-4" style="height: 2px; background-color: #facc15; width:95%;"></div>

    <div class="container bg-white rounded border shadow mb-5" style="width: 95%;">
        <?php if (isset($_SESSION['message'])): ?>
            <div class='alert alert-<?= $_SESSION['message_type']; ?> alert-dismissible fade show mt-3' role='alert'>
                <?= $_SESSION['message']; ?>
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>
            <?php 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>

        <div class="container mt-4">
            <div class="row mb-3">
                <div class="col-md-6 d-flex align-items-center mb-3 mb-md-0">
                    <label for="entriesCount" class="form-label me-2 mb-0">Show</label>
                    <select id="entriesCount" class="form-select form-select-sm w-auto d-inline-block">
                        <option value="5">5</option>
                        <option value="10">10</option>
                    </select>
                    <span class="ms-2 mb-0">entries</span>
                </div>
                <div class="col-md-6 d-flex align-items-center justify-content-md-end justify-content-center">
                    <p class="me-2 mb-0">Search:</p>
                    <input type="Search" id="searchInput" class="form-control form-control-sm w-auto" placeholder="">
                </div>
            </div>
        </div>

        <div class="table-responsive" style="margin-top:25px;">
            <table class="table table-striped table-hover table-bordered custom-table-border">
                <thead class="text-center">
                    <tr>
                        <th>#</th>
                        <th>Academic Year</th>
                        <th>Quarter</th>
                        <th>Evaluation Status</th>
                        <th>No. of Classes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                
                <tbody class="text-center" id="tableBody">
                    <?php
                    // Updated query to fetch academic year details along with the number of classes
                    $query = "
                        SELECT 
                            ay.acad_year_id, 
                            ay.year, 
                            ay.quarter, 
                            ay.is_active, 
                            COUNT(DISTINCT el.class_id) AS class_count
                        FROM 
                            academic_year ay
                        LEFT JOIN 
                            evaluation_list el ON ay.acad_year_id = el.acad_year_id
                        GROUP BY 
                            ay.acad_year_id, ay.year, ay.quarter, ay.is_active
                        ORDER BY 
                            CAST(SUBSTRING_INDEX(ay.year, '-', 1) AS UNSIGNED) DESC, 
                            ay.quarter DESC";

                    $result = mysqli_query($conn, $query);

                    if (!$result) {
                        die("Query failed: " . mysqli_error($conn));
                    }

                    $index = 1;
                    if (mysqli_num_rows($result) > 0) {
                        while ($subject = mysqli_fetch_assoc($result)) {
                            $classCount = $subject['class_count'];
                            $status = $subject['is_active'] == 1 ? 'In Progress' : 'Closed';
                            $statusClass = $subject['is_active'] == 1 ? 'bg-success' : 'bg-danger';
                    ?>
                        <tr data-id="<?php echo $subject['acad_year_id']; ?>">
                            <td><?php echo $index++; ?></td>
                            <td><?php echo htmlspecialchars($subject['year']); ?></td>
                            <td><?php echo htmlspecialchars($subject['quarter']); ?></td>
                            <td><span class="badge <?php echo $statusClass; ?> text-white"><?php echo $status; ?></span></td>
                            <td><?php echo $classCount; ?> Classes</td> 
                            <td>
                                <button class="btn btn-success btn-sm manageBtn" 
                                        data-id="<?php echo $subject['acad_year_id']; ?>" 
                                        data-year="<?php echo htmlspecialchars($subject['year']); ?>" 
                                        data-quarter="<?php echo htmlspecialchars($subject['quarter']); ?>">
                                    <i class="fa-solid fa-gear"></i> Manage
                                </button>
                            </td>
                        </tr>
                    <?php
                        }
                    } else {
                    ?>
                        <tr id="noResultRow">
                            <td colspan="6">No result.</td>
                        </tr>
                    <?php 
                    }
                    ?>
                </tbody>

            </table>
            <div class="container mt-3 mb-3">
                <div class="row">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <p class="mb-0" id="pageInfo">Showing 1 to 3 of 3 entries</p>
                        <div class="d-flex">
                            <button id="prevBtn" class="btn btn-outline-primary me-2" disabled>
                                Previous
                            </button>
                            <button id="nextBtn" class="btn btn-outline-primary" disabled>
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>

    <script>
        let currentPage = 1;
        let rowsPerPage = parseInt(document.getElementById('entriesCount').value);
        const tableBody = document.getElementById('tableBody');
        const allRows = Array.from(tableBody.getElementsByTagName('tr')); // Store all rows
        const noResultRow = document.getElementById('noResultRow') || createNoResultRow(); // Create the no-result row if not present
        const pageInfo = document.getElementById('pageInfo');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        let filteredRows = allRows; // Start with all rows visible

        // Function to create a "No Result" row if it doesn't exist
        function createNoResultRow() {
            const row = document.createElement('tr');
            row.id = 'noResultRow';
            row.innerHTML = '<td colspan="6">No result.</td>';
            row.style.display = 'none'; // Hide by default
            tableBody.appendChild(row);
            return row;
        }

        // Function to display the table rows based on the current page and rows per page
        function displayTableRows() {
            let start = (currentPage - 1) * rowsPerPage;
            let end = start + rowsPerPage;

            // Hide all rows first
            allRows.forEach((row) => {
                row.style.display = 'none'; // Hide all rows by default
            });

            // Show rows only within the current page range
            filteredRows.slice(start, end).forEach((row) => {
                row.style.display = ''; // Show only the relevant rows
            });

            // Update pagination info
            const showingStart = start + 1;
            const showingEnd = Math.min(end, filteredRows.length);
            pageInfo.textContent = `Showing ${showingStart} to ${showingEnd} of ${filteredRows.length} entries`;

            // Enable/Disable pagination buttons
            prevBtn.disabled = currentPage === 1;
            nextBtn.disabled = end >= filteredRows.length;
        }

        // Function to update the pagination when the number of rows per page changes
        function updatePagination() {
            rowsPerPage = parseInt(document.getElementById('entriesCount').value);
            currentPage = 1; // Reset to the first page
            displayTableRows();
        }

        // Event listener for changing the number of rows per page
        document.getElementById('entriesCount').addEventListener('change', updatePagination);

        // Event listeners for pagination buttons
        prevBtn.addEventListener('click', function () {
            if (currentPage > 1) {
                currentPage--;
                displayTableRows();
            }
        });

        nextBtn.addEventListener('click', function () {
            if ((currentPage * rowsPerPage) < filteredRows.length) {
                currentPage++;
                displayTableRows();
            }
        });

        // Adding search filter functionality
        document.getElementById('searchInput').addEventListener('keyup', function () {
            const input = this.value.toLowerCase(); // Get the search input
            let visibleRowIndex = 1; // Initialize visible row index
            filteredRows = []; // Reset filtered rows

            allRows.forEach(function (row) {
                const cells = row.getElementsByTagName('td');
                let match = false;

                // Check each cell (except the index and action cells) for a match
                for (let j = 1; j < cells.length - 1; j++) { 
                    if (cells[j].textContent.toLowerCase().includes(input)) {
                        match = true;
                        break;
                    }
                }

                // If a match is found, add the row to filteredRows
                if (match) {
                    row.getElementsByTagName('td')[0].textContent = visibleRowIndex++; // Update row number
                    filteredRows.push(row); // Add the matched row to filteredRows
                }
            });

            // Show "No Result" row if no rows are visible
            if (filteredRows.length === 0) {
                noResultRow.style.display = '';
            } else {
                noResultRow.style.display = 'none';
            }

            // Reset to the first page and display rows
            currentPage = 1;
            displayTableRows();
        });

        // Initial load of table rows
        displayTableRows();

        // JavaScript to handle the "Manage" button click
        document.addEventListener('DOMContentLoaded', function () {
            const manageButtons = document.querySelectorAll('.manageBtn');
    
            manageButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const acadYearId = this.getAttribute('data-id');
                    const year = this.getAttribute('data-year');
                    const quarter = this.getAttribute('data-quarter');
                    // Redirect to manage-class.php with the acad_year, year, and quarter as query parameters
                    window.location.href = `manage-class.php?acad_year_id=${acadYearId}&year=${year}&quarter=${quarter}`;
                });
            });
        });

    </script>

</body>
</html>
