<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty List</title>
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <?php include('../header.php'); ?>
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/admin-elements.css" />
    <style>
        .profile-icon {
            font-size: 80px;
            color: #5a5a5a;
        }
        .admin-modal-body {
            text-align: center;
            background-color: #281313;
            color: white;
            padding: 20px;
        }
        .admin-modal-body .admin-name {
            font-size: 24px;
            font-weight: bold;
        }
        .admin-modal-body .admin-email {
            font-size: 18px;
        }
        .admin-modal-body .admin-info {
            font-size: 16px;
        }
        #cropImageBtn.btn-success, #cropImageBtn.btn-success:hover {
            background-color: #28a745;
            border-color: #28a745;
        }
        #cropImageBtn.btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .classTitle {
            margin-top: 3%;
            padding-top: 2%;
        }
        @media (max-width: 767px) { 
            .classTitle {
                margin-top: 10%;
                padding-top: 6%;
            }
        }
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
    </style>
</head>
<body>
    <?php 
    session_start(); 
    include('../db_conn.php');
    include('navigation.php'); 
    ?>

    <style>
        /* Loader Spinner Style */
        .loader {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                text-align: center;
                background: rgba(255, 255, 255, 0.8);
                padding: 20px;
                border-radius: 5px;
                z-index: 9999;
            }

            .spinner-border {
                width: 3rem;
                height: 3rem;
            }

            /* Full-screen overlay that blocks interaction */
            .overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9998;
                display: none; /* Hidden by default */
            }

            /* Disable pointer events on the background content */
            .loading-state {
                pointer-events: none;
            }
    </style>
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
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
    ?>
    
    <!-- Full-screen overlay -->
    <div id="overlay" class="overlay"></div>

    <!-- Loader Spinner -->
    <div id="loader" class="loader" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p>Sending Email...</p>
    </div>

    <p class="classTitle fs-4 fw-bold ms-5 mb-2">Faculty List</p>
    <div class="mx-auto mb-4" style="height: 2px; background-color: #facc15; width:95%;"></div>
    
    <div class="container bg-white rounded border shadow mb-5" style="width: 95%;">
        <div class="buttonContainer">
            <button id="addNewBtn" class="btn custom-dark-brown-btn mt-3 mb-3 float-end" data-bs-toggle="modal" data-bs-target="#addNewTeacherModal"><i class="bi bi-plus-lg me-1"></i>Add New</button>
        </div>
        <hr class="bg-dark" style="border-width: 1px; margin-top: 70px; margin-bottom: 20px;">
        
        <div class="row mb-3">
            <div class="col-md-6">
                <label>Show <select class="form-select d-inline w-auto" name="entries" id="entriesSelect">
                    <option value="3">3</option>
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                </select> entries</label>
            </div>
            <div class="col-md-6 text-end">
                <label>Search: <input type="search" class="form-control d-inline w-auto" id="tableSearch" ></label>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered custom-table-border">
            <thead class="text-center">
                <tr>
                    <th>#</th>
                    <th class="text-start">Name</th>
                    <th class="text-start">Email</th>
                    <th class="text-start">Department</th>
                    <th class="text-start">Status</th>
                    <th class="text-start">Employee ID</th>
                    <th style="width: 150px;">Action</th>
                </tr>
            </thead>
            <tbody class="text-center" id="tableBody">
                <?php
                $query = "SELECT teacher_id, lastName, firstName, middleName, email, department, emp_code, status, avatar, created_at FROM teacher_account";
                $result = mysqli_query($conn, $query);

                if (!$result) {
                    die("Query failed: " . mysqli_error($conn));
                }

                $index = 1;
                while ($teacher = mysqli_fetch_assoc($result)) {
                    $middleInitial = !empty($teacher['middleName']) ? strtoupper($teacher['middleName'][0]) . '.' : '';
                    $fullName = htmlspecialchars($teacher['firstName']) . ' ' . $middleInitial . ' ' . htmlspecialchars($teacher['lastName']);
                ?>
                    <tr data-id="<?= $teacher['teacher_id']; ?>">
                        <td class="text-center"><?= $index++; ?></td>
                        <td class="text-start"><?= $fullName; ?></td>
                        <td class="text-start"><?= htmlspecialchars($teacher['email']); ?></td>
                        <td class="text-start"><?= htmlspecialchars($teacher['department']); ?></td>
                        <td class="text-start"><?= htmlspecialchars($teacher['status']); ?></td>
                        <td class="text-start">
                            <?php if (!empty($teacher['emp_code'])): ?>
                                <?= htmlspecialchars($teacher['emp_code']); ?>
                            <?php else: ?>
                                <span class="text-muted"><i>Assign the Employee ID to match the teacher's ID in the biometric device.</i></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-success btn-sm view-btn" 
                                    data-id="<?= $teacher['teacher_id']; ?>" 
                                    data-fullname="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>" 
                                    data-email="<?= htmlspecialchars($teacher['email'], ENT_QUOTES, 'UTF-8'); ?>" 
                                    data-department="<?= htmlspecialchars($teacher['department'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-status="<?= htmlspecialchars($teacher['status'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-avatar="<?= !empty($teacher['avatar']) ? 'data:image/jpeg;base64,' . base64_encode($teacher['avatar']) : ''; ?>"
                                    data-created="<?= !empty($teacher['created_at']) ? htmlspecialchars($teacher['created_at'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                                    <i class="bi bi-eye-fill"></i>
                                </button>

                                <button class="btn btn-primary btn-sm edit-btn" 
                                    data-id="<?= $teacher['teacher_id']; ?>" 
                                    data-first-name="<?= htmlspecialchars($teacher['firstName']); ?>"
                                    data-middle-name="<?= htmlspecialchars($teacher['middleName']); ?>"
                                    data-last-name="<?= htmlspecialchars($teacher['lastName']); ?>"
                                    data-email="<?= htmlspecialchars($teacher['email']); ?>" 
                                    data-department="<?= htmlspecialchars($teacher['department']); ?>"
                                    data-status="<?= htmlspecialchars($teacher['status']); ?>"
                                    data-emp-code="<?= htmlspecialchars($teacher['emp_code']); ?>" 
                                    data-avatar="<?= !empty($teacher['avatar']) ? 'data:image/jpeg;base64,' . base64_encode($teacher['avatar']) : '../Logo/img/default-profile.png'; ?>"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editTeacherModal">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <button class="btn btn-danger btn-sm delete-btn" data-id="<?= $teacher['teacher_id']; ?>" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal">
                                    <i class="bi bi-trash3-fill"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php 
                }
                ?>
                <!-- No result row placeholder -->
                <tr id="noResultRow" style="display: none;">
                    <td colspan="7" class="text-center">No result.</td>
                </tr>
            </tbody>

            </table>
        </div>
        <div class="container mt-3 mb-3">
            <div class="row">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <p class="mb-0" id="pageInfo">Showing 1 to <?= min(10, mysqli_num_rows($result)); ?> of <?= mysqli_num_rows($result); ?> entries</p>
                    <div class="d-flex">
                        <button id="prevBtn" class="btn btn-outline-primary me-2" disabled>Previous</button>
                        <button id="nextBtn" class="btn btn-outline-primary">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Faculty Modal -->
    <div class="modal fade" id="addNewTeacherModal" tabindex="-1" aria-labelledby="addNewTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="addNewTeacherModalLabel">Add New Faculty</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Modal Body -->
                <div class="modal-body">
                    <form id="addNewTeacherForm" method="POST" action="actions/add-teacher.php" enctype="multipart/form-data">
                        <!-- First Name -->
                        <div class="mb-3">
                            <label for="teacherFirstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="teacherFirstName" name="teacherFirstName" required>
                        </div>
                        <!-- Middle Name -->
                        <div class="mb-3">
                            <label for="teacherMiddleName" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="teacherMiddleName" name="teacherMiddleName">
                        </div>
                        <!-- Last Name -->
                        <div class="mb-3">
                            <label for="teacherLastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="teacherLastName" name="teacherLastName" required>
                        </div>
                        <!-- Department -->
                        <div class="mb-3">
                            <label for="teacherDepartment" class="form-label">Department</label>
                            <select class="form-select" id="teacherDepartment" name="teacherDepartment" required>
                                <option value="">Select Department</option>
                                <option value="Junior High School">Junior High School</option>
                                <option value="Senior High School">Senior High School</option>
                                <option value="Both (JHS & SHS)">Both (JHS & SHS)</option>
                            </select>
                        </div>
                        <!-- Status -->
                        <div class="mb-3">
                            <label for="teacherStatus" class="form-label">Status</label>
                            <select class="form-select" id="teacherStatus" name="teacherStatus" required>
                                <option value="">Select Status</option>
                                <option value="Regular">Regular</option>
                                <option value="Part-timer">Part-timer</option>
                            </select>
                        </div>

                        <!-- Employee ID -->
                        <div class="mb-3">
                            <label for="employeeID" class="form-label">Employee ID (Faculty's ID in Biometrics device)</label>
                            <input type="number" class="form-control" id="employeeID" name="employeeID" required>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="teacherEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="teacherEmail" name="teacherEmail" required>
                        </div>
                        <!-- Profile Picture -->
                        <div class="mb-3">
                            <label for="teacherAvatar" class="form-label">Profile Picture (optional, must be 1x1 aspect ratio)</label>
                            <input type="file" class="form-control" id="teacherAvatar" name="teacherAvatar" accept="image/*">
                        </div>
                        <!-- Modal Footer -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Add Teacher</button>
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
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this faculty's account?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteTeacherForm" method="POST">
                        <input type="hidden" id="deleteTeacherId" name="teacher_id">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Teacher Details Modal -->
    <div class="modal fade" id="viewTeacherModal" tabindex="-1" aria-labelledby="viewTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Faculty Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="admin-modal-body">
                    <!-- Profile Icon Placeholder -->
                    <div id="teacherProfileIconPlaceholder" class="profile-icon-container">
                        <img id="teacherProfileImage" class="rounded-circle" alt="Profile Picture" style="width: 80px; height: 80px; object-fit: cover; display: none;" />
                        <i id="teacherProfileIcon" class="bi bi-person-circle profile-icon text-white"></i>
                    </div>
                    <!-- Teacher Information -->
                    <h5 class="admin-name mb-1 mt-4" id="teacherName"></h5>
                    <p class="admin-email mb-2 fs-6"><strong>Email Address: </strong><span id="teacherEmailed"></span></p>
                    <p class="admin-department mt-1 mb-1">
                        <strong>Department: </strong><span id="teacherDepartmento"></span>
                    </p>
                    <p class="admin-status mt-1 mb-4">
                        <strong>Status: </strong><span id="teacherStatuso"></span>
                    </p>
                    <!-- Spacer for better visual separation -->
                    <div style="margin: 10px 0;"></div>
                    <p class="admin-info mt-4 mb-1">
                        <strong>Account created:</strong> <span id="teacherCreationDate"></span>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Teacher Modal -->
    <div class="modal fade" id="editTeacherModal" tabindex="-1" aria-labelledby="editTeacherModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="editTeacherModalLabel">Edit Faculty Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Modal Body -->
                <div class="modal-body">
                    <form id="editTeacherForm" enctype="multipart/form-data">
                        <input type="hidden" id="editTeacherId" name="teacher_id">
                        <!-- First Name -->
                        <div class="mb-3">
                            <label for="editTeacherFirstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="editTeacherFirstName" name="teacherFirstName" required>
                        </div>
                        <!-- Middle Name -->
                        <div class="mb-3">
                            <label for="editTeacherMiddleName" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="editTeacherMiddleName" name="teacherMiddleName">
                        </div>
                        <!-- Last Name -->
                        <div class="mb-3">
                            <label for="editTeacherLastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="editTeacherLastName" name="teacherLastName" required>
                        </div>
                        <!-- Department -->
                        <div class="mb-3">
                            <label for="editTeacherDepartment" class="form-label">Department</label>
                            <select class="form-select" id="editTeacherDepartment" name="teacherDepartment" required>
                                <option value="">Select Department</option>
                                <option value="Junior High School">Junior High School</option>
                                <option value="Senior High School">Senior High School</option>
                                <option value="Both (JHS & SHS)">Both (JHS & SHS)</option>
                            </select>
                        </div>
                        <!-- Status -->
                        <div class="mb-3">
                            <label for="editTeacherStatus" class="form-label">Status</label>
                            <select class="form-select" id="editTeacherStatus" name="teacherStatus" required>
                                <option value="">Select Status</option>
                                <option value="Regular">Regular</option>
                                <option value="Part-timer">Part-timer</option>
                            </select>
                        </div>
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="editTeacherEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editTeacherEmail" name="teacherEmail" required>
                        </div>
                        <!-- Employee ID -->
                        <div class="mb-3">
                            <label for="editEmployeeID" class="form-label">Employee ID (Teacher's ID in Biometrics device)</label>
                            <input type="number" class="form-control" id="editEmployeeID" name="employeeID" required>
                        </div>
                        <!-- Profile Picture -->
                        <div class="mb-3">
                            <label for="editTeacherAvatar" class="form-label">Profile Picture (optional, must be 1x1 aspect ratio)</label>
                            <input type="file" class="form-control" id="editTeacherAvatar" name="teacherAvatar" accept="image/*">
                            <div class="mb-3" id="editTeacherAvatarContainer">
                                <div class="text-center">
                                    <img id="editTeacherAvatarPreview" src="" alt="" class="rounded-circle mx-auto d-block mt-4" style="width: 100px; height: 100px; object-fit: cover; display: none;" />
                                </div>
                            </div>
                        </div>
                        <!-- Modal Footer -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
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

        // Pagination and Search Variables
        let currentPage = 1;
        let rowsPerPage = parseInt(document.getElementById('entriesSelect').value);
        const tableBody = document.getElementById('tableBody');
        const pageInfo = document.getElementById('pageInfo');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        // Define allRows and filteredRows
        const allRows = Array.from(tableBody.querySelectorAll('tr:not(#noResultRow)'));
        let filteredRows = allRows;

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
        document.getElementById('entriesSelect').addEventListener('change', function () {
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
        document.getElementById('tableSearch').addEventListener('keyup', function () {
            const input = this.value.toLowerCase().trim();
            if (input === '') {
                filteredRows = allRows;
            } else {
                filteredRows = allRows.filter(row => {
                    const cells = row.getElementsByTagName('td');
                    // Check all relevant columns except the # and Action columns
                    for (let j = 1; j < cells.length - 1; j++) { 
                        if (cells[j].textContent.toLowerCase().includes(input)) {
                            return true;
                        }
                    }
                    return false;
                });
            }

            const noResultRow = document.getElementById('noResultRow');
            if (filteredRows.length === 0 && input !== '') {
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
                // Set the hidden input in the delete form
                document.getElementById('deleteTeacherId').value = deleteId;
            });
        });

        document.getElementById('deleteTeacherForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const deleteIdValue = document.getElementById('deleteTeacherId').value;

            if (deleteIdValue) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'actions/delete-teacher.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.status === 'Success') {
                                // Remove the deleted row from allRows and filteredRows
                                const row = document.querySelector(`tr[data-id="${deleteIdValue}"]`);
                                if (row) {
                                    row.remove();
                                    const rowIndex = allRows.indexOf(row);
                                    if (rowIndex > -1) {
                                        allRows.splice(rowIndex, 1);
                                    }
                                }

                                // Update filteredRows
                                filteredRows = filteredRows.filter(r => r.getAttribute('data-id') !== deleteIdValue);

                                // Hide the modal
                                const deleteModalEl = document.getElementById('deleteConfirmationModal');
                                const deleteModal = bootstrap.Modal.getInstance(deleteModalEl);
                                deleteModal.hide();

                                // Show success message
                                showFlashMessage(response.message, 'success');

                                // Update pagination and table display
                                displayTableRows();
                            } else {
                                showFlashMessage(response.message, 'danger');
                            }
                        } catch (e) {
                            showFlashMessage('Unexpected response from server.', 'danger');
                        }
                    } else {
                        showFlashMessage('Request failed. Status: ' + xhr.status, 'danger');
                    }
                };
                xhr.onerror = function () {
                    showFlashMessage('Network error occurred. Please try again.', 'danger');
                };
                xhr.send('teacher_id=' + encodeURIComponent(deleteIdValue));
            }
        });


        // JavaScript to handle the "View" button clicks
        document.querySelectorAll('.view-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                // Clear previous modal content
                document.getElementById('teacherName').textContent = '';
                document.getElementById('teacherEmailed').textContent = '';
                document.getElementById('teacherDepartmento').textContent = '';
                document.getElementById('teacherStatuso').textContent = '';
                document.getElementById('teacherCreationDate').textContent = '';

                // Fetch data from the clicked button
                const fullName = this.getAttribute('data-fullname');
                const email = this.getAttribute('data-email');
                const department = this.getAttribute('data-department');
                const status = this.getAttribute('data-status') || '';
                const avatar = this.getAttribute('data-avatar') || '';
                const creationDateRaw = this.getAttribute('data-created') || '';
                
                // Format the creation date
                let formattedDate = 'N/A';
                if (creationDateRaw) {
                    const creationDate = new Date(creationDateRaw);
                    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true };
                    formattedDate = creationDate.toLocaleDateString('en-US', options);
                }

                // Set modal content
                document.getElementById('teacherName').textContent = fullName;
                document.getElementById('teacherEmailed').textContent = email;
                document.getElementById('teacherDepartmento').textContent = department;
                document.getElementById('teacherStatuso').textContent = status;
                document.getElementById('teacherCreationDate').textContent = formattedDate;

                // Handle avatar/profile picture
                const profileIconPlaceholder = document.getElementById('teacherProfileIconPlaceholder');
                if (avatar && avatar.startsWith('data:image')) {
                    // Display the avatar image if available
                    profileIconPlaceholder.innerHTML = `<img src="${avatar}" alt="Profile Picture" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">`;
                } else {
                    // Display default icon if no avatar is available
                    profileIconPlaceholder.innerHTML = `<i class="bi bi-person-circle profile-icon text-white"></i>`;
                }

                // Show the modal after setting content
                const modal = new bootstrap.Modal(document.getElementById('viewTeacherModal'));
                modal.show();
            });
        });

        // Clean up modal and backdrop after closing
        document.getElementById('viewTeacherModal').addEventListener('hidden.bs.modal', function () {
            // Ensure that the modal and backdrop are removed after closing
            document.body.classList.remove('modal-open');
            
            // Remove modal backdrop
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            
            // Restore scrolling on the main body content
            document.body.style.overflow = 'auto';
        });

        // JavaScript to handle the "Edit" button clicks
        document.querySelectorAll('.edit-btn').forEach(function(button) {
            button.addEventListener('click', function () {
                const teacherId = this.getAttribute('data-id');
                const firstName = this.getAttribute('data-first-name');
                const middleName = this.getAttribute('data-middle-name');
                const lastName = this.getAttribute('data-last-name');
                const email = this.getAttribute('data-email');
                const department = this.getAttribute('data-department');
                const status = this.getAttribute('data-status');
                const empCode = this.getAttribute('data-emp-code');
                const avatar = this.getAttribute('data-avatar');

                document.getElementById('editTeacherId').value = teacherId;
                document.getElementById('editTeacherFirstName').value = firstName;
                document.getElementById('editTeacherMiddleName').value = middleName;
                document.getElementById('editTeacherLastName').value = lastName;
                document.getElementById('editTeacherEmail').value = email;
                document.getElementById('editTeacherDepartment').value = department;
                document.getElementById('editTeacherStatus').value = status;
                document.getElementById('editEmployeeID').value = empCode;

                const avatarPreview = document.getElementById('editTeacherAvatarPreview');

                // Set the avatar image to either the teacher's avatar or the default image
                if (avatar.startsWith('data:image')) {
                    avatarPreview.src = avatar;
                } else {
                    avatarPreview.src = avatar;
                }
                avatarPreview.alt = 'Teacher Avatar';
                avatarPreview.style.display = 'block';
            });
        });

        // Handle avatar preview in Edit Modal
        document.getElementById('editTeacherAvatar').addEventListener('change', function (event) {
            const [file] = event.target.files;
            const avatarPreview = document.getElementById('editTeacherAvatarPreview');

            if (file) {
                avatarPreview.src = URL.createObjectURL(file);
                avatarPreview.alt = 'Teacher Avatar';
                avatarPreview.style.display = 'block';
            } else {
                // If the file input is cleared, reset to the default image
                avatarPreview.src = '../Logo/img/default-profile.png';
                avatarPreview.alt = 'Default Avatar';
                avatarPreview.style.display = 'block';
            }
        });

        // Handle Saving Changes in Edit Modal
        document.getElementById('editTeacherForm').addEventListener('submit', function (event) {
            event.preventDefault(); // Prevent default form submission

            const empCode = document.getElementById('editEmployeeID').value;

            // Check if empCode is empty or 0
            if (!empCode || empCode === "0") {
                showFlashMessage('Employee ID cannot be 0 or empty.', 'danger');
                return; // Stop form submission if empCode is invalid
            }

            const formData = new FormData(this);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'actions/edit-teacher.php', true); // POST request to edit-teacher.php

            xhr.onload = function () {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Hide the edit modal after success
                            const modal = bootstrap.Modal.getInstance(document.getElementById('editTeacherModal'));
                            modal.hide();

                            // Find the table row corresponding to the teacher
                            const teacherId = formData.get('teacher_id');
                            const teacherRow = allRows.find(r => r.getAttribute('data-id') === teacherId);

                            if (teacherRow) {
                                // Update Full Name with middle initial
                                const firstName = formData.get('teacherFirstName');
                                const middleName = formData.get('teacherMiddleName');
                                const lastName = formData.get('teacherLastName');
                                const updatedFullName = `${firstName} ${middleName ? middleName[0] + '.' : ''} ${lastName}`;

                                // Update columns in the row
                                teacherRow.cells[1].textContent = updatedFullName; // Name
                                teacherRow.cells[2].textContent = formData.get('teacherEmail'); // Email
                                teacherRow.cells[3].textContent = formData.get('teacherDepartment'); // Department
                                teacherRow.cells[4].textContent = formData.get('teacherStatus'); // Status
                                teacherRow.cells[5].textContent = empCode; // Employee ID

                                // Optionally update avatar
                                const avatarFile = document.getElementById('editTeacherAvatar').files[0];
                                if (avatarFile) {
                                    const reader = new FileReader();
                                    reader.onload = function(e) {
                                        const avatarData = e.target.result;
                                        // Update avatar in view and edit buttons
                                        teacherRow.querySelector('.view-btn').setAttribute('data-avatar', avatarData);
                                        teacherRow.querySelector('.edit-btn').setAttribute('data-avatar', avatarData);
                                    }
                                    reader.readAsDataURL(avatarFile);
                                }

                                // Update button attributes with new values
                                const editButton = teacherRow.querySelector('.edit-btn');
                                editButton.setAttribute('data-first-name', firstName);
                                editButton.setAttribute('data-middle-name', middleName);
                                editButton.setAttribute('data-last-name', lastName);
                                editButton.setAttribute('data-email', formData.get('teacherEmail'));
                                editButton.setAttribute('data-department', formData.get('teacherDepartment'));
                                editButton.setAttribute('data-status', formData.get('teacherStatus'));
                                editButton.setAttribute('data-emp-code', empCode); // Update emp_code in button attributes

                                // Update view button attributes to reflect new data
                                const viewButton = teacherRow.querySelector('.view-btn');
                                viewButton.setAttribute('data-fullname', updatedFullName);
                                viewButton.setAttribute('data-email', formData.get('teacherEmail'));
                                viewButton.setAttribute('data-department', formData.get('teacherDepartment'));
                                viewButton.setAttribute('data-status', formData.get('teacherStatus'));

                                // Show the success message
                                showFlashMessage(response.message, 'success');

                                // Update filteredRows if the updated row matches the current search
                                const searchInput = document.getElementById('tableSearch').value.toLowerCase().trim();
                                if (searchInput !== '') {
                                    const cells = teacherRow.getElementsByTagName('td');
                                    let matches = false;
                                    for (let j = 1; j < cells.length - 1; j++) { 
                                        if (cells[j].textContent.toLowerCase().includes(searchInput)) {
                                            matches = true;
                                            break;
                                        }
                                    }
                                    if (!matches) {
                                        // Remove from filteredRows
                                        filteredRows = filteredRows.filter(r => r !== teacherRow);
                                        teacherRow.style.display = 'none';
                                    }
                                }

                                // Refresh displayTableRows
                                displayTableRows();
                            }

                        } else {
                            showFlashMessage(response.message || 'An error occurred while updating teacher details.', 'danger');
                        }
                    } catch (e) {
                        showFlashMessage('Invalid server response.', 'danger');
                    }
                } else {
                    showFlashMessage('An error occurred while updating teacher details.', 'danger');
                }
            };

            xhr.onerror = function() {
                showFlashMessage('An error occurred during the request.', 'danger');
            };

            xhr.send(formData); // Send the FormData object via AJAX
        });

        // JavaScript to handle the "View" button clicks
        document.querySelectorAll('.view-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                // Clear previous modal content
                document.getElementById('teacherName').textContent = '';
                document.getElementById('teacherEmailed').textContent = '';
                document.getElementById('teacherDepartmento').textContent = '';
                document.getElementById('teacherStatuso').textContent = '';
                document.getElementById('teacherCreationDate').textContent = '';

                // Fetch data from the clicked button
                const fullName = this.getAttribute('data-fullname');
                const email = this.getAttribute('data-email');
                const department = this.getAttribute('data-department');
                const status = this.getAttribute('data-status') || '';
                const avatar = this.getAttribute('data-avatar') || '';
                const creationDateRaw = this.getAttribute('data-created') || '';
                
                // Format the creation date
                let formattedDate = 'N/A';
                if (creationDateRaw) {
                    const creationDate = new Date(creationDateRaw);
                    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true };
                    formattedDate = creationDate.toLocaleDateString('en-US', options);
                }

                // Set modal content
                document.getElementById('teacherName').textContent = fullName;
                document.getElementById('teacherEmailed').textContent = email;
                document.getElementById('teacherDepartmento').textContent = department;
                document.getElementById('teacherStatuso').textContent = status;
                document.getElementById('teacherCreationDate').textContent = formattedDate;

                // Handle avatar/profile picture
                const profileIconPlaceholder = document.getElementById('teacherProfileIconPlaceholder');
                if (avatar && avatar.startsWith('data:image')) {
                    // Display the avatar image if available
                    profileIconPlaceholder.innerHTML = `<img src="${avatar}" alt="Profile Picture" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">`;
                } else {
                    // Display default icon if no avatar is available
                    profileIconPlaceholder.innerHTML = `<i class="bi bi-person-circle profile-icon text-white"></i>`;
                }

                // Show the modal after setting content
                const modal = new bootstrap.Modal(document.getElementById('viewTeacherModal'));
                modal.show();
            });
        });

        // Clean up modal and backdrop after closing
        document.getElementById('viewTeacherModal').addEventListener('hidden.bs.modal', function () {
            // Ensure that the modal and backdrop are removed after closing
            document.body.classList.remove('modal-open');
            
            // Remove modal backdrop
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            
            // Restore scrolling on the main body content
            document.body.style.overflow = 'auto';
        });

        // Functions to display success and error messages
        function displayMessage(type, message) {
            const messageContainer = document.getElementById('messageContainer');
            if (messageContainer) {
                // Clear any existing messages
                messageContainer.innerHTML = '';

                // Create a new alert div
                const alertDiv = document.createElement('div');
                alertDiv.classList.add('alert', `alert-${type}`, 'alert-dismissible', 'fade', 'show', 'mt-3');
                alertDiv.setAttribute('role', 'alert');
                alertDiv.innerHTML = `
                    ${message}
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                `;

                // Append the alert to the message container
                messageContainer.appendChild(alertDiv);

                // Optionally, remove the message after 5 seconds
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            }
        }

        // Handle Saving Changes in Edit Modal (Alternative Error Message Display)
        // (Removed in favor of using showFlashMessage for consistency)
         // Show loader and overlay when form is being submitted and disable pointer events on content
         document.getElementById('addNewTeacherModal').addEventListener('submit', function (event) {
            // Show loader and overlay, and disable content interaction
            document.getElementById('loader').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('content').classList.add('loading-state');
        });
    </script>

</body>
</html>
