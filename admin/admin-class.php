<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <title>Class List</title>
    <?php include('../header.php'); ?>
    <!--css file-->
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/admin-elements.css" />
</head>
<body>
    <?php session_start(); ?>
    <?php include('navigation.php'); ?>
    <style>
        /* for the titles  */
        .classTitle {
            margin-top: 3%;
            padding-top: 2%;
        }

        /* small devices */
        @media (max-width: 767px) { 
            .classTitle {
                margin-top: 10%;
                padding-top: 6%;
            }
        }
    </style>
    <!-- ADMIN CLASSES CONTENTS   -->
    <p class="classTitle fs-4 fw-bold ms-5 mb-2">Classes</p>
    <div class="mx-auto mb-4" style="height: 2px; background-color: #facc15; width:95%;"></div> <!-- YELLOW LINE -->

    <div class="container bg-white rounded border shadow mb-5" style="width: 95%;">

    
    <!-- Display Bootstrap alert if message is set -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class='alert alert-<?= $_SESSION['message_type']; ?> alert-dismissible fade show mt-3' role='alert'>
            <?= $_SESSION['message']; ?>
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>
        <?php 
        // Clear the message after displaying
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>

    <div class="buttonContainer">
        <button class="btn custom-dark-brown-btn mt-3 mb-3 float-end" data-bs-toggle="modal" data-bs-target="#addNewModal"><i class="bi bi-plus-lg me-1"></i>Add New</button>
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
                        <option value="100">100</option>
                    </select>
                    <span class="ms-2 mb-0">entries</span>
                </div>
                <div class="col-md-6 d-flex align-items-center justify-content-md-end justify-content-center">
                    <p class="me-2 mb-0">Search:</p>
                    <input type="Search" id="searchInput" class="form-control form-control-sm w-auto" placeholder="">
                </div>
            </div>
        </div>

        <div class="table-container" style="margin-top:25px;">
            <table class="table table-striped table-hover table-bordered custom-table-border">
                <thead class="text-center">
                    <tr>
                        <th>#</th>
                        <th>Class Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                
                <tbody class="text-center" id="tableBody">
                    <?php
                    // Fetch data from class_list table
                    $query = "SELECT class_id, grade_level, section FROM class_list";
                    $result = mysqli_query($conn, $query);

                    if (!$result) {
                        die("Query failed: " . mysqli_error($conn));
                    }

                    $index = 1; 
                    if (mysqli_num_rows($result) > 0) {
                        while ($class = mysqli_fetch_assoc($result)):
                    ?>
                            <tr data-id="<?php echo $class['class_id']; ?>"> <!-- Add the data-id attribute -->
                                <td><?php echo $index++; ?></td>
                                <td><?php echo htmlspecialchars("Grade {$class['grade_level']} - {$class['section']}"); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-primary btn-md edit-btn" 
                                                data-id="<?php echo $class['class_id']; ?>" 
                                                data-grade="<?php echo $class['grade_level']; ?>" 
                                                data-section="<?php echo $class['section']; ?>" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-md delete-btn" 
                                                data-id="<?php echo $class['class_id']; ?>" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteConfirmationModal">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    
                                </td>
                            </tr>
                    <?php 
                        endwhile; 
                    } else { 
                    ?>
                        <tr id="noResultRow">
                            <td colspan="3">No result.</td>
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

    <!-- Add New Modal -->
    <div class="modal fade" id="addNewModal" tabindex="-1" aria-labelledby="addNewModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addNewModalLabel">New Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Form for adding new class -->
                    <form id="addClassForm" method="POST" action="actions/add-class.php">
                        <div class="mb-3">
                            <label for="gradeLevel" class="form-label">Grade Level</label>
                            <select class="form-select" id="gradeLevel" name="gradeLevel" required>
                                <option value="" disabled selected>Select Grade Level</option>
                                <option value="7">7</option>
                                <option value="8">8</option>
                                <option value="9">9</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="section" class="form-label">Section</label>
                            <input type="text" class="form-control" id="section" name="section" required>
                        </div>
                        <div class="mt-5">
                            <button type="submit" class="btn btn-primary float-end">Add Class</button>
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
                    <h5 class="modal-title" id="editModalLabel">Edit Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Form for editing class -->
                    <form id="editClassForm" method="POST">
                        <input type="hidden" id="editClassId" name="classId">
                        <div class="mb-3">
                            <label for="editGradeLevel" class="form-label">Grade Level</label>
                            <select class="form-select" id="editGradeLevel" name="gradeLevel" required>
                                <option value="" disabled>Select Grade Level</option>
                                <option value="7">7</option>
                                <option value="8">8</option>
                                <option value="9">9</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editSection" class="form-label">Section</label>
                            <input type="text" class="form-control" id="editSection" name="section" required>
                        </div>
                        <div class="mt-5">
                            <button type="submit" class="btn btn-primary float-end">Save Changes</button>
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
                    <h5 class="modal-title" id="deleteConfirmationModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this class?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
                </div>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>
    <script>
        // Handle click event on Edit buttons to populate the Edit Modal
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault(); // Prevent the page from scrolling to the top

                const classId = this.getAttribute('data-id');
                const gradeLevel = this.getAttribute('data-grade');
                const section = this.getAttribute('data-section');

                // Populate the form fields in the modal with data from the clicked row
                document.getElementById('editClassId').value = classId;
                document.getElementById('editGradeLevel').value = gradeLevel;
                document.getElementById('editSection').value = section;
            });
        });

        // Edit Class form submission using AJAX
        document.getElementById('editClassForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent form from submitting traditionally

            const form = this;
            const formData = new FormData(form);

            // Send the form data via AJAX
            fetch('actions/edit-class.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json()) // Assuming the server returns a JSON response
            .then(data => {
                if (data.success) {
                    // Update the row in the table dynamically
                    updateTableRow(data.updatedClass); 
                    
                    // Close the modal after success
                    const editModalElement = document.getElementById('editModal');
                    const editModal = bootstrap.Modal.getInstance(editModalElement);
                    editModal.hide(); // Close the modal
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });

        // Function to dynamically update the row in the table after edit
        function updateTableRow(updatedClass) {
            const row = document.querySelector(`tr[data-id="${updatedClass.class_id}"]`);
            if (row) {
                // Update the visible table cells with the new data
                row.querySelector('td:nth-child(2)').textContent = `Grade ${updatedClass.grade_level} - ${updatedClass.section}`;

                // Update the data attributes so that future edits use the updated values
                row.querySelector('.edit-btn').setAttribute('data-grade', updatedClass.grade_level);
                row.querySelector('.edit-btn').setAttribute('data-section', updatedClass.section);
            } else {
                console.error('Row not found');
            }
        }

        // Handle click event on Delete buttons to set the delete link in the modal
        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault(); // Prevent the page from scrolling to the top

                const classId = this.getAttribute('data-id');
                const deleteUrl = `actions/delete-class.php?id=${classId}`;
                document.getElementById('confirmDeleteBtn').setAttribute('href', deleteUrl);
            });
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            var input = this.value.toLowerCase();
            var tableBody = document.getElementById('tableBody');
            var rows = tableBody.getElementsByTagName('tr');
            var visibleRowIndex = 1;
            var anyVisible = false;

            // Iterate over the rows to find matches
            for (var i = 0; i < rows.length; i++) {
                var cells = rows[i].getElementsByTagName('td');
                var match = false;

                for (var j = 1; j < cells.length - 1; j++) {
                    if (cells[j].textContent.toLowerCase().includes(input)) {
                        match = true;
                        break;
                    }
                }

                if (match) {
                    rows[i].style.display = ''; // Show row if it matches
                    rows[i].getElementsByTagName('td')[0].textContent = visibleRowIndex++; // Update the row number
                    anyVisible = true;
                } else {
                    rows[i].style.display = 'none'; // Hide row if it doesn't match
                }
            }

            // Handle the "No result." row
            var noResultRow = document.getElementById('noResultRow');
            if (!anyVisible) {
                if (!noResultRow) {
                    var newRow = document.createElement('tr');
                    newRow.id = 'noResultRow';
                    newRow.innerHTML = '<td colspan="3">No result.</td>';
                    tableBody.appendChild(newRow);
                } else {
                    noResultRow.style.display = '';
                }
            } else if (noResultRow) {
                noResultRow.style.display = 'none';
            }
        });

        // Pagination
        let currentPage = 1;
        let rowsPerPage = parseInt(document.getElementById('entriesCount').value); // Default rows per page from dropdown
        let totalRows = <?php echo mysqli_num_rows($result); ?>; // Total rows from the server-side

        function paginateTable() {
        const tableBody = document.getElementById('tableBody');
        const rows = tableBody.getElementsByTagName('tr');
        let start = (currentPage - 1) * rowsPerPage;
        let end = start + rowsPerPage;
        let visibleRowIndex = start + 1; // Start counting based on the current page

        // Show only the rows that are within the current page
        for (let i = 0; i < rows.length; i++) {
            if (i >= start && i < end) {
                rows[i].style.display = '';
                rows[i].getElementsByTagName('td')[0].textContent = visibleRowIndex++; // Continuous row number
            } else {
                rows[i].style.display = 'none';
            }
        }

        updatePageInfo();
        toggleButtons();
    }


        function updatePageInfo() {
            const pageInfo = document.getElementById('pageInfo');
            const start = (currentPage - 1) * rowsPerPage + 1;
            const end = Math.min(currentPage * rowsPerPage, totalRows);
            pageInfo.textContent = `Showing ${start} to ${end} of ${totalRows} entries`;
        }

        function toggleButtons() {
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');

            prevBtn.disabled = currentPage === 1;
            nextBtn.disabled = currentPage * rowsPerPage >= totalRows;
        }

        document.getElementById('entriesCount').addEventListener('change', function() {
            rowsPerPage = parseInt(this.value);
            currentPage = 1;
            paginateTable();
        });

        document.getElementById('prevBtn').addEventListener('click', function() {
            if (currentPage > 1) {
                currentPage--;
                paginateTable();
            }
        });

        document.getElementById('nextBtn').addEventListener('click', function() {
            if (currentPage * rowsPerPage < totalRows) {
                currentPage++;
                paginateTable();
            }
        });

        // Initialize the pagination on page load
        window.onload = function() {
            rowsPerPage = parseInt(document.getElementById('entriesCount').value); // Initialize based on default selected value
            paginateTable();
        };
    </script>
</body>
</html>

