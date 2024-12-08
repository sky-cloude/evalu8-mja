<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Year List</title>
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <?php include('../header.php'); ?>
    <!-- CSS files -->
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/admin-elements.css" />

    <style>
        /* Flash message styles */
        .flash-message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            color: white;
            border-radius: 5px;
            display: none;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }

        /* Flash message types */
        .flash-success {
            background-color: #28a745; /* Green */
        }

        .flash-danger {
            background-color: #dc3545; /* Red */
        }

        /* Titles */
        .classTitle {
            margin-top: 3%;
            padding-top: 2%;
        }

        /* Responsive adjustments for small devices */
        @media (max-width: 767px) { 
            .classTitle {
                margin-top: 10%;
                padding-top: 6%;
            }
        }
    </style>
</head>
<body>
    <?php 
    session_start(); 
    include('navigation.php'); 
    include '../db_conn.php'; // Ensure database connection is included

    date_default_timezone_set('Asia/Manila'); // Set your timezone

    // Get the current time in the same format as your database datetime values
    $current_time = date('Y-m-d H:i:s');

    // Single SQL query to update all active rows where evaluation period has ended
    $updateQuery = "UPDATE academic_year SET is_active = 0 WHERE is_active = 1 AND evaluation_period < '$current_time'";
    $result = mysqli_query($conn, $updateQuery);

    if (!$result) {
        $_SESSION['message'] = "Update failed: " . mysqli_error($conn);
        $_SESSION['message_type'] = 'danger';
    } else {
        if (mysqli_affected_rows($conn) > 0) {
            $_SESSION['message'] = "Academic years updated successfully!";
            $_SESSION['message_type'] = 'success';
        }
    }
    ?>

    <!-- Flash Message -->
    <div id="flashMessage" class="flash-message"></div>

    <?php 
    // Display the flash message if set
    if (isset($_SESSION['message'])) {
        // Escape the message to prevent XSS
        $message = addslashes($_SESSION['message']);
        $message_type = $_SESSION['message_type'];
        echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    showFlashMessage("' . $message . '", "' . $message_type . '");
                });
            </script>';
        unset($_SESSION['message']); // Clear the flash message after displaying
        unset($_SESSION['message_type']);
    }
    ?>

    <!-- CONTENTS HERE  -->
    <p class="classTitle fs-4 fw-bold ms-5 mb-2">Academic Year</p>
    <div class="mx-auto mb-4" style="height: 2px; background-color: #facc15; width:95%;"></div> <!-- YELLOW LINE -->

    <div class="container bg-white rounded border shadow mb-5" style="width: 95%;">
        <div class="buttonContainer">
            <button class="btn custom-dark-brown-btn mt-3 mb-3 float-end" data-bs-toggle="modal" data-bs-target="#addNewModal">
                <i class="bi bi-plus-lg me-1"></i>Add New
            </button>
        </div>

        <hr class="bg-dark" style="border-width: 1px; margin-top: 70px; margin-bottom: 20px;"><!-- GRAY LINE -->

        <div class="container">
            <div class="row mb-3">
                <div class="col-md-6 d-flex align-items-center mb-3 mb-md-0">
                    <label for="entriesCount" class="form-label me-2 mb-0">Show</label>
                    <select id="entriesCount" class="form-select form-select-sm w-auto d-inline-block">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="ms-2 mb-0">entries</span>
                </div>
                <div class="col-md-6 d-flex align-items-center justify-content-md-end justify-content-center">
                    <p class="me-2 mb-0">Search:</p>
                    <input type="search" id="searchInput" class="form-control form-control-sm w-auto">
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
                        <th>Evaluation Period</th>
                        <th>Action</th>
                    </tr>
                </thead>
                
                <tbody class="text-center" id="tableBody">
                    <?php
                    // Define the query to fetch data
                    $query = "SELECT acad_year_id, year, quarter, is_active, evaluation_period 
                            FROM academic_year 
                            ORDER BY CAST(SUBSTRING_INDEX(year, '-', 1) AS UNSIGNED) DESC, quarter DESC";

                    // Execute the query
                    $result = mysqli_query($conn, $query);

                    if (!$result) {
                        die("Query failed: " . mysqli_error($conn));
                    }

                    $index = 1; // Initialize index for rows
                    if (mysqli_num_rows($result) > 0) {
                        // Fetch the data and display in the table
                        while ($subject = mysqli_fetch_assoc($result)) {
                            // Prepare variables for evaluation period check
                            $evaluationPeriod = isset($subject['evaluation_period']) ? new DateTime($subject['evaluation_period']) : null;
                            $evaluationPeriodFormatted = $evaluationPeriod ? $evaluationPeriod->format('Y-m-d\TH:i') : '';
                            $current_time_dt = new DateTime(); // Get the current time
                            $evaluationMessage = '';

                            // Determine if the evaluation is open or ended
                            if ($evaluationPeriod) {
                                if ($evaluationPeriod < $current_time_dt) {
                                    $evaluationMessage = "Evaluation ended at " . $evaluationPeriod->format('F d, Y - h:i A');
                                } else {
                                    $evaluationMessage = "Evaluation is open until " . $evaluationPeriod->format('F d, Y - h:i A');
                                }
                            } else {
                                $evaluationMessage = 'N/A'; // Handle if there's no evaluation period
                            }
                    ?>
                        <tr data-id="<?php echo $subject['acad_year_id']; ?>">
                            <td class="text-center"><?php echo $index++; ?></td> <!-- Increment index for each row -->
                            <td><?php echo htmlspecialchars($subject['year']); ?></td>
                            <td><?php echo htmlspecialchars($subject['quarter']); ?></td>
                            <td>
                                <?php 
                                if ($subject['is_active']) {
                                    echo '<span class="badge bg-success text-white">In Progress</span>';
                                } else {
                                    echo '<span class="badge bg-danger text-white">Closed</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo $evaluationMessage; ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-primary btn-sm edit-btn" 
                                        data-id="<?php echo $subject['acad_year_id']; ?>" 
                                        data-year="<?php echo htmlspecialchars($subject['year']); ?>" 
                                        data-quarter="<?php echo htmlspecialchars($subject['quarter']); ?>" 
                                        data-status="<?php echo $subject['is_active'] ? 'In Progress' : 'Closed'; ?>"
                                        data-evaluation-period="<?php echo $evaluationPeriodFormatted; ?>" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editModal">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button class="btn btn-danger btn-sm delete-btn" 
                                            data-id="<?php echo $subject['acad_year_id']; ?>" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteConfirmationModal">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php
                        }
                    }
                    ?>
                    <!-- Always include the No Result row, hidden by default -->
                    <tr id="noResultRow" style="display: none;">
                        <td colspan="6" class="text-center">No result.</td>
                    </tr>
                </tbody>
            </table>
            <div class="container mt-3 mb-3">
                <div class="row">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <p class="mb-0" id="pageInfo">Showing 1 to <?php echo min(10, mysqli_num_rows($result)); ?> of <?php echo mysqli_num_rows($result); ?> entries</p>
                        <div class="d-flex">
                            <button id="prevBtn" class="btn btn-outline-primary me-2" disabled>
                                Previous
                            </button>
                            <button id="nextBtn" class="btn btn-outline-primary">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add New Modal -->
        <div class="modal fade" id="addNewModal" tabindex="-1" aria-labelledby="addNewModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addNewModalLabel">New Academic Year</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Form for adding new academic year -->
                        <form id="addAcademicForm" method="POST" action="actions/add-academic.php">
                            <div class="mb-3">
                                <label for="acadYear" class="form-label">Academic Year</label>
                                <input type="text" class="form-control" id="acadYear" name="acadYear" placeholder="2024-2025" required>
                            </div>
                            <div class="mb-3">
                                <label for="quarter" class="form-label">Quarter</label>
                                <input type="number" class="form-control" id="quarter" name="quarter" min="1" max="4" required>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="" selected hidden>Select Status</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>

                            <!-- New field for evaluation period -->
                            <div class="mb-3">
                                <label for="evaluationPeriod" class="form-label">Evaluation Period</label>
                                <input type="datetime-local" class="form-control" id="evaluationPeriod" name="evaluationPeriod" required>
                            </div>

                            <div class="mt-5">
                                <button type="submit" class="btn btn-primary float-end">Add Academic Year</button>
                                <button type="button" class="btn btn-secondary float-end me-3" data-bs-dismiss="modal">Close</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
             
        <!-- Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Academic Year</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Form for editing academic year -->
                        <form id="editAcademicForm" method="POST">
                            <input type="hidden" id="editAcadYearId" name="acadYearId">
                            <div class="mb-3">
                                <label for="editAcadYear" class="form-label">Academic Year</label>
                                <input type="text" class="form-control" id="editAcadYear" name="acadYear" required>
                            </div>
                            <div class="mb-3">
                                <label for="editQuarter" class="form-label">Quarter</label>
                                <input type="number" class="form-control" id="editQuarter" name="quarter" min="1" max="4" required>
                            </div>
                            <div class="mb-3">
                                <label for="editStatus" class="form-label">Status</label>
                                <select class="form-control" id="editStatus" name="status" required>
                                    <option value="" selected hidden>Select Status</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="editEvaluationPeriod" class="form-label">Evaluation Period</label>
                                <input type="datetime-local" class="form-control" id="editEvaluationPeriod" name="evaluationPeriod" required>
                            </div>
                            <div class="mt-5">
                                <button type="submit" class="btn btn-primary float-end" id="saveChangesBtn">Save Changes</button>
                                <button type="button" class="btn btn-secondary float-end me-3" data-bs-dismiss="modal">Close</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteConfirmationModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete this academic year?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    
    <?php include('footer.php'); ?>

    <script>
        // Flash Message Function
        function showFlashMessage(message, type) {
            const flashMessage = document.getElementById('flashMessage');
            flashMessage.textContent = message;

            // Remove existing type classes
            flashMessage.classList.remove('flash-success', 'flash-danger');

            // Add the appropriate type class
            if (type === 'success') {
                flashMessage.classList.add('flash-success');
            } else if (type === 'danger') {
                flashMessage.classList.add('flash-danger');
            } else {
                // Default styling if type is not recognized
                flashMessage.style.backgroundColor = '#6c757d'; // Gray
            }

            flashMessage.style.display = 'block'; // Show the flash message
            flashMessage.style.opacity = '1';     // Make it fully visible

            // Hide the flash message after 2 seconds with a fade-out effect
            setTimeout(() => {
                flashMessage.style.opacity = '0';
                setTimeout(() => {
                    flashMessage.style.display = 'none';
                }, 500); // Duration to match the CSS transition
            }, 2000); // Display duration for the flash message
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Pagination and Search Variables
            let currentPage = 1;
            let rowsPerPage = parseInt(document.getElementById('entriesCount').value);
            const tableBody = document.getElementById('tableBody');
            const pageInfo = document.getElementById('pageInfo');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const noResultRow = document.getElementById('noResultRow');

            // Collect all rows except 'No Result.' row
            const allRows = Array.from(tableBody.querySelectorAll('tr')).filter(row => row.id !== 'noResultRow');
            let filteredRows = allRows.slice(); // Initially, all rows are visible

            // Function to display table rows based on pagination
            function displayTableRows() {
                const totalRows = filteredRows.length;
                const totalPages = Math.ceil(totalRows / rowsPerPage) || 1;

                // Ensure currentPage is within bounds
                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                // Determine the start and end index for the current page
                const start = (currentPage - 1) * rowsPerPage;
                const end = start + rowsPerPage;

                // Hide all rows
                allRows.forEach(row => {
                    row.style.display = 'none';
                });

                // Show only the rows for the current page
                const rowsToShow = filteredRows.slice(start, end);
                rowsToShow.forEach((row, index) => {
                    row.style.display = '';
                    // Update the # column
                    row.querySelector('td:nth-child(1)').textContent = start + index + 1;
                });

                // Update page info
                if (totalRows === 0) {
                    pageInfo.textContent = `Showing 0 to 0 of 0 entries`;
                } else {
                    pageInfo.textContent = `Showing ${start + 1} to ${Math.min(end, totalRows)} of ${totalRows} entries`;
                }

                // Update button states
                prevBtn.disabled = currentPage === 1;
                nextBtn.disabled = currentPage === totalPages;
            }

            // Initial display
            displayTableRows();

            // Event listener for entries count change
            document.getElementById('entriesCount').addEventListener('change', function () {
                rowsPerPage = parseInt(this.value);
                currentPage = 1;
                displayTableRows();
            });

            // Event listeners for pagination buttons
            prevBtn.addEventListener('click', function () {
                if (currentPage > 1) {
                    currentPage--;
                    displayTableRows();
                }
            });

            nextBtn.addEventListener('click', function () {
                const totalRows = filteredRows.length;
                const totalPages = Math.ceil(totalRows / rowsPerPage) || 1;
                if (currentPage < totalPages) {
                    currentPage++;
                    displayTableRows();
                }
            });

            // Search Functionality
            document.getElementById('searchInput').addEventListener('keyup', function () {
                const input = this.value.toLowerCase().trim();
                if (input === '') {
                    filteredRows = allRows.slice();
                } else {
                    filteredRows = allRows.filter(row => {
                        const cells = Array.from(row.getElementsByTagName('td')).slice(1, -1); // Exclude # and Action columns
                        return cells.some(cell => cell.textContent.toLowerCase().includes(input));
                    });
                }

                // Show or hide 'No Result.' row
                if (filteredRows.length === 0) {
                    noResultRow.style.display = 'table-row';
                } else {
                    noResultRow.style.display = 'none';
                }

                // Reset to first page after search
                currentPage = 1;
                displayTableRows();
            });

            // Handle Deletion
            let deleteId = null;
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function () {
                    deleteId = this.getAttribute('data-id');
                });
            });

            document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
                if (deleteId) {
                    // Perform AJAX deletion
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'actions/delete-academic.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            // Assuming delete-academic.php returns JSON response
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response.status === 'Success') {
                                    // Remove the deleted row from the table
                                    const row = document.querySelector(`tr[data-id="${deleteId}"]`);
                                    if (row) {
                                        // Remove from allRows and filteredRows arrays
                                        const indexAll = allRows.indexOf(row);
                                        if (indexAll > -1) {
                                            allRows.splice(indexAll, 1);
                                        }

                                        const indexFiltered = filteredRows.indexOf(row);
                                        if (indexFiltered > -1) {
                                            filteredRows.splice(indexFiltered, 1);
                                        }

                                        row.remove();
                                    }

                                    // Hide the modal
                                    var deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmationModal'));
                                    deleteModal.hide();

                                    // Show flash message
                                    showFlashMessage('Academic year deleted successfully!', 'success');

                                    // Update pagination and numbering
                                    displayTableRows();
                                } else {
                                    showFlashMessage('Error deleting academic year: ' + response.message, 'danger');
                                }
                            } catch (e) {
                                showFlashMessage('Unexpected response from server.', 'danger');
                            }
                        } else {
                            showFlashMessage('Request failed. Status: ' + xhr.status, 'danger');
                        }
                    };
                    xhr.send('acad_year_id=' + encodeURIComponent(deleteId));
                }
            });

            // Handle Adding a New Academic Year
            document.getElementById('addAcademicForm').addEventListener('submit', function (e) {
                // Optional: You can handle form submission via AJAX here
                // For now, it's handled via standard form submission
            });

            // Handle Editing an Academic Year
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();

                    // Get the modal input elements
                    const acadYearInput = document.getElementById('editAcadYear');
                    const quarterInput = document.getElementById('editQuarter');
                    const statusInput = document.getElementById('editStatus');
                    const evaluationPeriodInput = document.getElementById('editEvaluationPeriod');
                    const acadYearIdInput = document.getElementById('editAcadYearId');

                    // Get the data from the clicked button
                    const acadYearId = this.getAttribute('data-id');
                    const year = this.getAttribute('data-year');
                    const quarter = this.getAttribute('data-quarter');
                    const status = this.getAttribute('data-status');
                    const evaluationPeriod = this.getAttribute('data-evaluation-period');

                    // Populate modal fields with the values from the selected row
                    acadYearIdInput.value = acadYearId;
                    acadYearInput.value = year;
                    quarterInput.value = quarter;
                    statusInput.value = status;

                    // Populate the evaluation period with the correct formatted value
                    if (evaluationPeriod) {
                        evaluationPeriodInput.value = evaluationPeriod; // Ensure it's in the correct datetime-local format
                    } else {
                        evaluationPeriodInput.value = ''; // Clear if no data
                    }

                    // Check if the evaluation period has already ended
                    const evaluationDate = new Date(evaluationPeriod);
                    const currentDate = new Date();

                    // If the evaluation period has ended, disable the status input
                    if (evaluationPeriod && evaluationDate < currentDate) {
                        statusInput.disabled = true;
                    } else {
                        statusInput.disabled = false;
                    }
                });
            });

            // Handle Saving Changes in Edit Modal
            document.getElementById('editAcademicForm').addEventListener('submit', function (e) {
                e.preventDefault();
                let formData = new FormData(this);

                fetch('actions/edit-academic.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const acadYearId = data.updatedAcademicYear.acad_year_id;
                        const row = document.querySelector(`tr[data-id="${acadYearId}"]`);

                        if (row) {
                            // Update the changed row with the new data
                            row.querySelector('td:nth-child(2)').textContent = data.updatedAcademicYear.year;
                            row.querySelector('td:nth-child(3)').textContent = data.updatedAcademicYear.quarter;
                            row.querySelector('td:nth-child(4)').innerHTML = data.updatedAcademicYear.is_active ? 
                                '<span class="badge bg-success text-white">In Progress</span>' : 
                                '<span class="badge bg-danger text-white">Closed</span>';

                            // Get the evaluation period and check if it's in the past or future
                            const evaluationDate = new Date(data.updatedAcademicYear.evaluation_period);
                            const currentDate = new Date();

                            let evaluationPeriodMessage = '';
                            if (evaluationDate < currentDate) {
                                evaluationPeriodMessage = `Evaluation ended at ${evaluationDate.toLocaleString('en-US', { 
                                    year: 'numeric', month: 'long', day: 'numeric', 
                                    hour: '2-digit', minute: '2-digit', hour12: true 
                                })}`;
                            } else {
                                evaluationPeriodMessage = `Evaluation is open until ${evaluationDate.toLocaleString('en-US', { 
                                    year: 'numeric', month: 'long', day: 'numeric', 
                                    hour: '2-digit', minute: '2-digit', hour12: true 
                                })}`;
                            }

                            row.querySelector('td:nth-child(5)').textContent = evaluationPeriodMessage;

                            const editButton = row.querySelector('.edit-btn');
                            editButton.setAttribute('data-year', data.updatedAcademicYear.year);
                            editButton.setAttribute('data-quarter', data.updatedAcademicYear.quarter);
                            editButton.setAttribute('data-status', data.updatedAcademicYear.is_active ? 'In Progress' : 'Closed');
                            editButton.setAttribute('data-evaluation-period', data.updatedAcademicYear.evaluation_period);

                            // If the updated academic year is set to "In Progress", update other rows to "Closed"
                            if (data.updatedAcademicYear.is_active) {
                                document.querySelectorAll('tr').forEach(function (otherRow) {
                                    const otherRowId = otherRow.getAttribute('data-id');
                                    if (otherRowId && otherRowId != acadYearId) {
                                        const statusCell = otherRow.querySelector('td:nth-child(4)');
                                        if (statusCell) {
                                            statusCell.innerHTML = '<span class="badge bg-danger text-white">Closed</span>';

                                            // Also update the status attribute in the edit button for that row
                                            const otherEditButton = otherRow.querySelector('.edit-btn');
                                            otherEditButton.setAttribute('data-status', 'Closed');
                                        }
                                    }
                                });
                            }
                        }

                        // Hide the modal after successful update
                        var editModal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                        editModal.hide();

                        // Show flash message to indicate success
                        showFlashMessage('Academic year updated successfully!', 'success');

                        // Update pagination and numbering
                        displayTableRows();
                    } else {
                        showFlashMessage('Error: ' + data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showFlashMessage('An error occurred while updating.', 'danger');
                });
            });

            // Clear input when Cancel button is clicked in Add Modal
            document.getElementById('addNewModal').addEventListener('hidden.bs.modal', function () {
                document.getElementById('addAcademicForm').reset();
            });

            // Client-side validation for the quarter field in Add Form
            const quarterInputAdd = document.getElementById('quarter');
            quarterInputAdd.addEventListener('input', function (e) {
                let value = e.target.value;
                if (isNaN(value) || value < 1 || value > 4) {
                    e.target.value = ''; 
                    alert("Please enter a number between 1 and 4.");
                }
            });

            quarterInputAdd.addEventListener('keydown', function (e) {
                const invalidChars = ["-", "+", "e", "E", "."];
                const allowedControlKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'];
                if (!allowedControlKeys.includes(e.key) && invalidChars.includes(e.key)) {
                    e.preventDefault();
                }
            });

            // Client-side validation for the quarter field in Edit Form
            const quarterInputEdit = document.getElementById('editQuarter');
            quarterInputEdit.addEventListener('input', function (e) {
                let value = e.target.value;
                if (isNaN(value) || value < 1 || value > 4) {
                    e.target.value = ''; 
                    alert("Please enter a number between 1 and 4.");
                }
            });

            quarterInputEdit.addEventListener('keydown', function (e) {
                const invalidChars = ["-", "+", "e", "E", "."];
                const allowedControlKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'];
                if (!allowedControlKeys.includes(e.key) && invalidChars.includes(e.key)) {
                    e.preventDefault();
                }
            });
        });
    </script>

</body>
</html>
