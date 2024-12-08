<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <title>Manage Subjects</title>
    <?php include('../header.php'); ?>
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/admin-elements.css" />
    <style>
        .breadcrumbs-container {
            margin-top: 3%;
            margin-bottom: 1.5%;
            padding-top: 2%;
            width: 98%;
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
        .btn-on {
            background: #281313;
            border: none;
            padding: 8px;
            color: #fafafa;
        }
        .btn-on:hover {
            background: #facc15;
            color: #281313;
        }
        .btn-back:hover {
            color: #facc15;
        }

        .flash-message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border-radius: 5px;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }
        /* small devices */
        @media (max-width: 767px) { 
            .breadcrumbs-container {
                margin-top: 10%;
                padding-top: 6%;
                margin-bottom: 1.5%;
            }
        }
    </style>

</head>
<body>
<?php 
    session_start(); 
    include('navigation.php'); 
    include('../db_conn.php');  

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $evaluation_id = isset($_GET['evaluation_id']) ? intval($_GET['evaluation_id']) : 0;
    $class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
    $year = isset($_GET['year']) ? htmlspecialchars($_GET['year']) : '';
    $quarter = isset($_GET['quarter']) ? htmlspecialchars($_GET['quarter']) : '';
    
    if ($evaluation_id == 0 || $class_id == 0) {
        die("Invalid evaluation ID or class ID.");
    }
    

    $query = "SELECT cl.grade_level, cl.section, ay.acad_year_id, ay.year AS acad_year
              FROM class_list cl
              LEFT JOIN evaluation_list el ON cl.class_id = el.class_id
              LEFT JOIN academic_year ay ON el.acad_year_id = ay.acad_year_id
              WHERE cl.class_id = $class_id";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }

    $class_details = mysqli_fetch_assoc($result);
    if (!$class_details) {
        die("No class found with the provided class ID.");
    }

    $grade_level = $class_details['grade_level'];
    $section = $class_details['section'];
    $acad_year_id = $class_details['acad_year_id'];
    $acad_year = $class_details['acad_year'];

    $subject_query = "
        SELECT ste.subject_eval_id, 
            ste.subject_id,
            ste.teacher_id,
            CONCAT(ta.firstName, ' ', ta.lastName) AS teacher_name, 
            sl.subject_title, sl.code
        FROM subject_to_eval ste
        JOIN teacher_account ta ON ste.teacher_id = ta.teacher_id
        JOIN subject_list sl ON ste.subject_id = sl.subject_id
        WHERE ste.evaluation_id = $evaluation_id
    ";

    $subjects_result = mysqli_query($conn, $subject_query);

    if (!$subjects_result) {
        die("Query failed: " . mysqli_error($conn));
    }

    $counter = 1;
?>
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
                    <a href="admin-eval-controls.php" class="text-muted text-decoration-none">
                        Evaluation Controls
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="manage-class.php?acad_year_id=<?php echo htmlspecialchars($_GET['acad_year_id']); ?>&year=<?php echo htmlspecialchars($_GET['year']); ?>&quarter=<?php echo htmlspecialchars($_GET['quarter']); ?>" class="text-muted text-decoration-none">
                        Manage Class
                    </a>
                </li>
                <li class="breadcrumb-item active text-muted" aria-current="page">Manage Subjects</li>
            </ol>
        </nav>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 ms-5">
        <p class="classTitle fs-4 fw-bold mb-0">
            Manage Subjects for Grade <?php echo $grade_level . " - " . $section; ?> of A.Y. <?php echo $acad_year; ?> | Quarter <?php echo $quarter; ?>
        </p>
        <a href="manage-class.php?acad_year_id=<?php echo isset($_GET['acad_year_id']) && !empty($_GET['acad_year_id']) ? htmlspecialchars($_GET['acad_year_id']) : 'DEFAULT_ACAD_YEAR_ID'; ?>&year=<?php echo isset($_GET['year']) ? htmlspecialchars($_GET['year']) : ''; ?>&quarter=<?php echo isset($_GET['quarter']) ? htmlspecialchars($_GET['quarter']) : ''; ?>" class="btn btn-back">
            <i class="fa-solid fa-circle-chevron-left"></i>
        </a>
    </div>

    <div class="mx-auto mb-5" style="height: 2px; background-color: #facc15; width:95%;"></div>

    <!-- Right: Add Subject button -->
    <div class="d-flex justify-content-end me-4 mb-4">
        <button type="button" class="btn-on rounded me-3" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
            <i class="fa-solid fa-plus"></i> Add Subject
        </button>
    </div>

    <!-- Table for Subjects -->
    <div class="container bg-white rounded shadow border mb-5" style="width: 95%;">
        <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
            <!-- Left: Show entries -->
            <div class="text-start mb-2 ms-4 d-flex align-items-center">
                <label for="entriesCount" class="form-label me-2 mb-0">Show</label>
                <select id="entriesCount" class="form-select form-select-sm w-auto d-inline-block">
                    <option value="3">3</option>
                    <option value="5">5</option>
                    <option value="8" selected>8</option>
                </select>
                <span class="ms-2 mb-0">entries</span>
            </div>

            <!-- Search input field and rows per page -->
            <div class="container mb-2">
                <div class="row justify-content-between align-items-center">
                    <div class="text-end">
                        <input type="search" id="searchInput" class="form-control w-25" placeholder="Search subjects..." style="width: 50%; float:right;" <?php echo (mysqli_num_rows($subjects_result) > 0) ? '' : 'disabled'; ?>>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive" style="margin-top:25px;">
            <table class="table table-striped table-hover table-bordered" id="subjectsTable">
                <thead class="text-center">
                    <tr>
                        <th>#</th>
                        <th>Subject</th>
                        <th>Teacher</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                
                <tbody class="text-center" id="subjectsBody">
                    <?php
                    if (mysqli_num_rows($subjects_result) > 0) {
                        while ($row = mysqli_fetch_assoc($subjects_result)) {
                            $subject_display = $row['code'] . " - " . $row['subject_title'];
                    ?>
                        <tr>
                            <td class="text-center align-middle"><?php echo $counter++; ?></td>
                            <td class="align-middle"><?php echo htmlspecialchars($subject_display); ?></td>
                            <td class="align-middle"><?php echo htmlspecialchars($row['teacher_name']); ?></td>
                            <td class="text-center align-middle">
                                <a href="#" class="btn btn-outline-dark btn-sm edit-subject" 
                                data-subject-eval-id="<?php echo $row['subject_eval_id']; ?>" 
                                data-subject-id="<?php echo $row['subject_id']; ?>" 
                                data-teacher-id="<?php echo $row['teacher_id']; ?>">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="#" class="btn btn-outline-dark btn-sm delete-subject" data-subject-id="<?php echo $row['subject_eval_id']; ?>">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php
                        }
                    } else {
                    ?>
                        <tr>
                            <td colspan="4">No subjects found for this evaluation.</td>
                        </tr>
                    <?php
                    }                    
                    ?>
                    <!-- Add this row for the "No result." message -->
                    <tr id="noResultRow" style="display: none;">
                        <td colspan="4">No result found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination controls -->
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
    
    <!-- Modal for Adding Subject -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1" aria-labelledby="addSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addSubjectModalLabel">Add Subject for This Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Form to add new subject -->
                    <form id="addSubjectForm">
                        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                        <input type="hidden" name="evaluation_id" value="<?php echo $evaluation_id; ?>"> 
                        <div class="mb-3">
                            <label for="subjectTitle" class="form-label">Subject Title</label>
                            <select class="form-select" id="subjectTitle" name="subjectTitle" required>
                                <option selected disabled>Select a subject</option>
                                <?php
                                    $subject_query = "SELECT subject_id, subject_title FROM subject_list";
                                    $subject_result = mysqli_query($conn, $subject_query);
                                    if (mysqli_num_rows($subject_result) > 0) {
                                        while ($subject_row = mysqli_fetch_assoc($subject_result)) {
                                            echo '<option value="' . $subject_row['subject_id'] . '">' . htmlspecialchars($subject_row['subject_title']) . '</option>';
                                        }
                                    } else {
                                        echo '<option disabled>No subjects available</option>';
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="teacher" class="form-label">Teacher</label>
                            <select class="form-select" id="teacher" name="teacher" required>
                                <option selected disabled>Select a teacher</option>
                                <?php
                                    $teacher_query = "SELECT teacher_id, CONCAT(firstName, ' ', lastName) AS teacher_name 
                                    FROM teacher_account 
                                    ORDER BY firstName ASC";
                  
                                    $teacher_result = mysqli_query($conn, $teacher_query);
                                    if (mysqli_num_rows($teacher_result) > 0) {
                                        while ($teacher_row = mysqli_fetch_assoc($teacher_result)) {
                                            echo '<option value="' . $teacher_row['teacher_id'] . '">' . htmlspecialchars($teacher_row['teacher_name']) . '</option>';
                                        }
                                    } else {
                                        echo '<option disabled>No teachers available</option>';
                                    }
                                ?>
                            </select>
                        </div>
                    </form>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" form="addSubjectForm" id="addSubjectBtn">Add Subject</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteSubjectModal" tabindex="-1" aria-labelledby="deleteSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteSubjectModalLabel">Confirm the Removal of Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to remove this subject from the class?
                    <input type="hidden" id="deleteSubjectId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteButton">Remove</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Subject Modal -->
    <div class="modal fade" id="editSubjectModal" tabindex="-1" aria-labelledby="editSubjectModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editSubjectForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editSubjectModalLabel">Edit Subject Assignment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Hidden input to store subject_eval_id -->
                        <input type="hidden" id="editSubjectEvalId" name="subject_eval_id">
                        <div class="mb-3">
                            <label for="editSubjectTitle" class="form-label">Subject Title</label>
                            <select class="form-select" id="editSubjectTitle" name="subjectTitle" required>
                                <option selected disabled>Select a subject</option>
                                <?php
                                    // Reuse the subject list options
                                    mysqli_data_seek($subject_result, 0); // Reset pointer to beginning
                                    while ($subject_row = mysqli_fetch_assoc($subject_result)) {
                                        echo '<option value="' . $subject_row['subject_id'] . '">' . htmlspecialchars($subject_row['subject_title']) . '</option>';
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editTeacher" class="form-label">Teacher</label>
                            <select class="form-select" id="editTeacher" name="teacher" required>
                                <option selected disabled>Select a teacher</option>
                                <?php
                                    // Reuse the teacher list options
                                    mysqli_data_seek($teacher_result, 0); // Reset pointer to beginning
                                    while ($teacher_row = mysqli_fetch_assoc($teacher_result)) {
                                        echo '<option value="' . $teacher_row['teacher_id'] . '">' . htmlspecialchars($teacher_row['teacher_name']) . '</option>';
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <?php include('footer.php'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let currentPage = 1;
            let rowsPerPage = parseInt(document.getElementById('entriesCount').value);
            const tableBody = document.getElementById('subjectsBody');
            const allRows = Array.from(tableBody.getElementsByTagName('tr')).filter(row => row.id !== 'noResultRow'); // Exclude the noResultRow
            const noResultRow = document.getElementById('noResultRow');
            const pageInfo = document.getElementById('pageInfo');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');

            let filteredRows = allRows; // Start with all rows visible

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

                // If no filtered rows, show the "No result" row
                if (filteredRows.length === 0) {
                    noResultRow.style.display = '';
                } else {
                    noResultRow.style.display = 'none'; // Hide "No result" row if there are matching rows
                }

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
                const input = this.value.toLowerCase();
                filteredRows = allRows.filter(row => {
                    const subject = row.getElementsByTagName('td')[1].textContent.toLowerCase();
                    const teacher = row.getElementsByTagName('td')[2].textContent.toLowerCase();
                    return subject.includes(input) || teacher.includes(input);
                });

                currentPage = 1; // Reset to the first page and display rows
                displayTableRows();
            });

            // Initial load of table rows
            displayTableRows();

            // Function to append a new subject to the table without refreshing
            function appendSubjectToTable(subjectDisplay, teacherName, subjectEvalId, subjectId, teacherId) {
                console.log('Appending new subject:', {
                    subjectDisplay,
                    teacherName,
                    subjectEvalId,
                    subjectId,
                    teacherId
                });

                const newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td class="text-center align-middle">${allRows.length + 1}</td>
                    <td class="align-middle">${subjectDisplay}</td>
                    <td class="align-middle">${teacherName}</td>
                    <td class="text-center align-middle">
                        <a href="#" class="btn btn-outline-dark btn-sm edit-subject" 
                        data-subject-eval-id="${subjectEvalId}" 
                        data-subject-id="${subjectId}" 
                        data-teacher-id="${teacherId}">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>
                        <a href="#" class="btn btn-outline-dark btn-sm delete-subject" 
                        data-subject-id="${subjectEvalId}">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </td>
                `;
                tableBody.appendChild(newRow);
                allRows.push(newRow); // Add the new row to the allRows array for filtering and pagination
                displayTableRows(); // Refresh the pagination
            }

            // Handle Add Subject Form Submission using AJAX
            document.getElementById('addSubjectForm').addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent the default form submission

                const formData = new FormData(this);
                formData.append('class_id', <?php echo $class_id; ?>); // Add class_id dynamically

                // AJAX request to add-subject-process.php
                fetch('actions/add-subject-process.php', {
                    method: 'POST',
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Server response:', data); // Log server response for debugging
                    if (data.success) {
                        // Close the modal
                        const modalElement = document.querySelector('#addSubjectModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalElement);
                        modalInstance.hide();

                        // Reset the form fields
                        document.getElementById('addSubjectForm').reset();

                        // Append new subject to the table without reloading the page
                        appendSubjectToTable(
                            data.subject_display,
                            data.teacher_name,
                            data.subject_eval_id,
                            data.subject_id,
                            data.teacher_id
                        );

                        // Flash the success message after the modal closes
                        setTimeout(() => {
                            displayFlashMessage('Subject added successfully.', true);
                        }, 500);
                    } else {
                        // Display error message
                        displayFlashMessage(data.message, false);
                    }
                })
                .catch(error => console.error('Error:', error));
            });

            // Flash Message Display Logic
            function displayFlashMessage(message, success = true) {
                let flashMessage = document.getElementById('flashMessage');
                
                if (!flashMessage) {
                    flashMessage = document.createElement('div');
                    flashMessage.id = 'flashMessage';
                    flashMessage.className = 'flash-message';
                    document.body.appendChild(flashMessage);
                }

                flashMessage.style.backgroundColor = success ? '#28a745' : '#dc3545'; // Green for success, red for error
                flashMessage.innerText = message;
                flashMessage.style.display = 'block';
                flashMessage.style.opacity = '1';

                setTimeout(() => {
                    flashMessage.style.opacity = '0';
                    setTimeout(() => {
                        flashMessage.style.display = 'none';
                    }, 500);
                }, 3000);
            }

            // Delete subject logic
            let subjectToDelete = null;

            document.addEventListener('click', function(e) {
                const deleteButton = e.target.closest('.delete-subject');
                if (deleteButton) {
                    e.preventDefault(); // Prevent default anchor behavior (e.g., adding # to the URL)
                    subjectToDelete = deleteButton.getAttribute('data-subject-id');
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteSubjectModal'));
                    deleteModal.show();
                }
            });

            // Confirm delete button logic
            document.getElementById('confirmDeleteButton').addEventListener('click', function () {
                if (subjectToDelete) {
                    const formData = new FormData();
                    formData.append('subject_eval_id', subjectToDelete);

                    fetch('actions/delete-subject-process.php', {
                        method: 'POST',
                        body: formData,
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the row from the table
                            const rowToDelete = document.querySelector(`.delete-subject[data-subject-id="${subjectToDelete}"]`).closest('tr');
                            rowToDelete.remove();
                            allRows.splice(allRows.indexOf(rowToDelete), 1);
                            displayTableRows();

                            // Close the modal
                            const deleteModalElement = document.getElementById('deleteSubjectModal');
                            const deleteModalInstance = bootstrap.Modal.getInstance(deleteModalElement);
                            deleteModalInstance.hide();

                            // Flash success message
                            displayFlashMessage('Subject removed successfully.', true);
                        } else {
                            displayFlashMessage(`Error removing subject: ${data.message}`, false);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        displayFlashMessage('An unexpected error occurred.', false);
                    });
                }
            });

            // Edit subject logic
            document.addEventListener('click', function(e) {
                const editButton = e.target.closest('.edit-subject');
                if (editButton) {
                    e.preventDefault(); // Prevent default anchor behavior
                    const subjectEvalId = editButton.getAttribute('data-subject-eval-id');
                    const subjectId = editButton.getAttribute('data-subject-id');
                    const teacherId = editButton.getAttribute('data-teacher-id');

                    console.log('Editing subject:', { subjectEvalId, subjectId, teacherId });

                    // Set the values in the modal
                    document.getElementById('editSubjectEvalId').value = subjectEvalId;

                    // For 'editSubjectTitle' select element
                    const subjectSelect = document.getElementById('editSubjectTitle');
                    subjectSelect.value = subjectId.toString();
                    if (subjectSelect.value != subjectId.toString()) {
                        // Try to set selectedIndex
                        for (let i = 0; i < subjectSelect.options.length; i++) {
                            if (subjectSelect.options[i].value == subjectId.toString()) {
                                subjectSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }

                    // For 'editTeacher' select element
                    const teacherSelect = document.getElementById('editTeacher');
                    teacherSelect.value = teacherId.toString();
                    if (teacherSelect.value != teacherId.toString()) {
                        for (let i = 0; i < teacherSelect.options.length; i++) {
                            if (teacherSelect.options[i].value == teacherId.toString()) {
                                teacherSelect.selectedIndex = i;
                                break;
                            }
                        }
                    }

                    // Show the modal
                    const editModal = new bootstrap.Modal(document.getElementById('editSubjectModal'));
                    editModal.show();
                }
            });

            // Handle Edit Subject Form Submission using AJAX
            document.getElementById('editSubjectForm').addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent the default form submission

                const formData = new FormData(this);

                // AJAX request to edit-subject-process.php
                fetch('actions/edit-subject-process.php', {
                    method: 'POST',
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Edit response:', data); // Log response for debugging
                    if (data.success) {
                        // Close the modal
                        const editModalElement = document.getElementById('editSubjectModal');
                        const editModalInstance = bootstrap.Modal.getInstance(editModalElement);
                        editModalInstance.hide();

                        // Update the table row without reloading the page
                        updateTableRow(data);

                        // Flash the success message after the modal closes
                        setTimeout(() => {
                            displayFlashMessage('Subject updated successfully.', true);
                        }, 500);
                    } else {
                        // Display error message
                        displayFlashMessage(data.message, false);
                    }
                })
                .catch(error => console.error('Error:', error));
            });

            // Function to update the table row after editing
            function updateTableRow(data) {
                const rowToUpdate = document.querySelector(`.edit-subject[data-subject-eval-id="${data.subject_eval_id}"]`).closest('tr');

                // Update the subject and teacher cells
                rowToUpdate.getElementsByTagName('td')[1].textContent = data.subject_display;
                rowToUpdate.getElementsByTagName('td')[2].textContent = data.teacher_name;

                // Update data attributes
                const editButton = rowToUpdate.querySelector('.edit-subject');
                editButton.setAttribute('data-subject-id', data.subject_id);
                editButton.setAttribute('data-teacher-id', data.teacher_id);
            }

        });
    </script>

</body>
</html>
