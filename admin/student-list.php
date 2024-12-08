<!-- student-list.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <title>Student List</title>
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

    <!-- Full-screen overlay -->
    <div id="overlay" class="overlay"></div>

    <!-- Loader Spinner -->
    <div id="loader" class="loader" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p>Sending Email...</p>
    </div>

    <p class="classTitle fs-4 fw-bold ms-5 mb-2">Student List</p>
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

        <div class="buttonContainer">
            <button id="addNewBtn" class="btn custom-dark-brown-btn mt-3 mb-3 float-end" data-bs-toggle="modal" data-bs-target="#addNewStudentModal"><i class="bi bi-plus-lg me-1"></i>Add New</button>
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
                <label>Search: <input type="search" class="form-control d-inline w-auto" id="tableSearch"></label>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered custom-table-border">
                <thead class="text-center">
                    <tr>
                        <th>#</th>
                        <th class="text-start">Name</th>
                        <th class="text-start">Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody class="text-center" id="tableBody">
                    <?php
                    // Query to join student_account with class_list based on class_id
                    $query = "
                        SELECT student_account.student_id, student_account.lastName, student_account.firstName, 
                        student_account.middleName, student_account.email, student_account.avatar, student_account.created_at
                        FROM student_account
                    ";

                    $result = mysqli_query($conn, $query);

                    if (!$result) {
                        die("Query failed: " . mysqli_error($conn));
                    }

                    $index = 1;
                    while ($student = mysqli_fetch_assoc($result)) {
                        $middleInitial = !empty($student['middleName']) ? strtoupper($student['middleName'][0]) . '.' : '';
                        $fullName = htmlspecialchars($student['firstName']) . ' ' . $middleInitial . ' ' . htmlspecialchars($student['lastName']);
                    ?>
                        <tr data-id="<?= $student['student_id']; ?>">
                            <td><?= $index++; ?></td>
                            <td class="text-start"><?= $fullName; ?></td>
                            <td class="text-start"><?= htmlspecialchars($student['email']); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-success btn-sm view-btn" 
                                        data-id="<?= $student['student_id']; ?>" 
                                        data-fullname="<?= $fullName; ?>" 
                                        data-email="<?= htmlspecialchars($student['email']); ?>" 
                                        data-avatar="<?= !empty($student['avatar']) ? 'data:image/jpeg;base64,' . base64_encode($student['avatar']) : ''; ?>"
                                        data-created="<?= !empty($student['created_at']) ? htmlspecialchars($student['created_at']) : ''; ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#viewStudentModal">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>

                                    <button class="btn btn-primary btn-sm edit-btn" 
                                        data-id="<?= $student['student_id']; ?>" 
                                        data-first-name="<?= htmlspecialchars($student['firstName']); ?>"
                                        data-middle-name="<?= htmlspecialchars($student['middleName']); ?>"
                                        data-last-name="<?= htmlspecialchars($student['lastName']); ?>"
                                        data-email="<?= htmlspecialchars($student['email']); ?>" 
                                        data-avatar="<?= !empty($student['avatar']) ? 'data:image/jpeg;base64,' . base64_encode($student['avatar']) : ''; ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editStudentModal">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <button class="btn btn-danger btn-sm delete-btn" data-id="<?= $student['student_id']; ?>" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal">
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
                        <td colspan="4">No result.</td>
                    </tr>
                </tbody>

            </table>
        </div>
        <div class="container mt-3 mb-3">
            <div class="row">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <p class="mb-0" id="pageInfo">Showing 1 to <?= mysqli_num_rows($result); ?> of <?= mysqli_num_rows($result); ?> entries</p>
                    <div class="d-flex">
                        <button id="prevBtn" class="btn btn-outline-primary me-2" disabled>Previous</button>
                        <button id="nextBtn" class="btn btn-outline-primary">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Student Modal -->
    <div class="modal fade" id="addNewStudentModal" tabindex="-1" aria-labelledby="addNewStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addNewStudentModalLabel">Add New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addNewStudentForm" method="POST" enctype="multipart/form-data" action="actions/add-student.php">
                        <div class="mb-3">
                            <label for="studentFirstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="studentFirstName" name="studentFirstName" required>
                        </div>
                        <div class="mb-3">
                            <label for="studentMiddleName" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="studentMiddleName" name="studentMiddleName">
                        </div>
                        <div class="mb-3">
                            <label for="studentLastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="studentLastName" name="studentLastName" required>
                        </div>
                        <div class="mb-3">
                            <label for="studentEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="studentEmail" name="studentEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="studentAvatar" class="form-label">Profile Picture (optional, must be 1x1 aspect ratio)</label>
                            <input type="file" class="form-control" id="studentAvatar" name="studentAvatar" accept="image/*">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Add Student</button>
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
                    Are you sure you want to delete this student's account?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteStudentForm" method="POST" action="actions/delete-student.php">
                        <input type="hidden" id="deleteStudentId" name="student_id">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStudentModalLabel">Edit Student Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editStudentForm" enctype="multipart/form-data">
                        <input type="hidden" id="editStudentId" name="student_id">

                        <div class="mb-3">
                            <label for="editStudentFirstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="editStudentFirstName" name="studentFirstName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editStudentMiddleName" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="editStudentMiddleName" name="studentMiddleName">
                        </div>
                        <div class="mb-3">
                            <label for="editStudentLastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="editStudentLastName" name="studentLastName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editStudentEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editStudentEmail" name="studentEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="editStudentAvatar" class="form-label">Profile Picture (optional, must be 1x1 aspect ratio))</label>
                            <input type="file" class="form-control" id="editStudentAvatar" name="studentAvatar" accept="image/*">

                            <div class="mb-3" id="editStudentAvatarContainer">
                                <div class="text-center">
                                    <img id="editStudentAvatarPreview" src="" alt="" class="rounded-circle mx-auto d-block mt-4" style="width: 100px; height: 100px; object-fit: cover; display: none;" />
                                </div>
                            </div>


                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Student Details Modal -->
    <div class="modal fade" id="viewStudentModal" tabindex="-1" aria-labelledby="viewStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewStudentModalLabel">Student Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="admin-modal-body">
                    <!-- Profile Icon Placeholder -->
                    <div id="studentProfileIconPlaceholder" class="profile-icon-container">
                        <img id="studentProfileImage" class="rounded-circle" alt="Profile Picture" style="width: 80px; height: 80px; object-fit: cover; display: none;" />
                        <i id="studentProfileIcon" class="bi bi-person-circle profile-icon" style="display: none;"></i>
                    </div>
                    <!-- Student Information -->
                    <h5 class="admin-name mb-1 mt-4" id="studentName"></h5>
                    <p class="admin-email mb-2 fs-6" style="font-size: 0.9em;"><strong>Email Address: </strong><span id="studentEmailed"></span></p>
                    <!-- Spacer for better visual separation -->
                    <div style="margin: 10px 0;"></div>
                    <p class="admin-info mt-4 mb-1">
                        <strong>Account created:</strong> <span id="studentCreationDate"></span>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include('footer.php');?>
    <!-- JavaScript for Modal and Pagination -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchBox = document.getElementById('tableSearch');
            const tableBody = document.getElementById('tableBody');
            const entriesSelect = document.getElementById('entriesSelect');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            const pageInfo = document.getElementById('pageInfo');
            let noResultRow = document.getElementById('noResultRow');

            // Pagination Variables
            let currentPage = 1;
            let rowsPerPage = parseInt(entriesSelect.value);
            let rows = Array.from(tableBody.querySelectorAll('tr:not(#noResultRow)'));
            let filteredRows = rows;  // Store filtered rows separately

            // Initialize total rows count
            let totalRows = rows.length;

            // Function to filter rows based on search input
            function filterTable(searchQuery) {
                filteredRows = rows.filter(function(row) {
                    let fullName = row.cells[1].textContent.toLowerCase();
                    let email = row.cells[2].textContent.toLowerCase();
                    return fullName.includes(searchQuery) || email.includes(searchQuery);
                });

                // Show only filtered rows
                rows.forEach(function(row) {
                    row.style.display = 'none';  // Hide all rows initially
                });

                filteredRows.forEach(function(row) {
                    row.style.display = '';  // Show only filtered rows
                });

                // If no rows match the search, show the "No result" row
                if (filteredRows.length === 0) {
                    noResultRow.style.display = '';
                } else {
                    noResultRow.style.display = 'none';
                }

                // Update the total visible rows and reset pagination
                totalRows = filteredRows.length;
                currentPage = 1;
                paginateTable();
            }

            // Function to paginate the visible rows
            function paginateTable() {
                const start = (currentPage - 1) * rowsPerPage;
                const end = start + rowsPerPage;

                filteredRows.forEach(function(row, index) {
                    if (index >= start && index < end) {
                        row.style.display = '';  // Show the rows within the page range
                    } else {
                        row.style.display = 'none';  // Hide the rows outside the page range
                    }
                });

                pageInfo.textContent = `Showing ${Math.min(start + 1, totalRows)} to ${Math.min(end, totalRows)} of ${totalRows} entries`;

                prevBtn.disabled = currentPage === 1;
                nextBtn.disabled = currentPage >= Math.ceil(totalRows / rowsPerPage);
            }

            // Attach pagination to the number of entries selected
            entriesSelect.addEventListener('change', function () {
                rowsPerPage = parseInt(this.value);
                currentPage = 1;
                paginateTable();
            });

            prevBtn.addEventListener('click', function () {
                if (currentPage > 1) {
                    currentPage--;
                    paginateTable();
                }
            });

            nextBtn.addEventListener('click', function () {
                if (currentPage < Math.ceil(totalRows / rowsPerPage)) {
                    currentPage++;
                    paginateTable();
                }
            });

            // Search functionality
            searchBox.addEventListener('keyup', function () {
                const searchQuery = searchBox.value.toLowerCase();
                filterTable(searchQuery);
            });

            // Initialize Pagination
            paginateTable();
        });

        // Attach click event to all delete buttons
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const studentId = this.getAttribute('data-id');
                document.getElementById('deleteStudentId').value = studentId;  // Pass student ID to hidden input in the form
            });
        });

        document.querySelectorAll('.view-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                // Clear previous modal content
                document.getElementById('studentName').textContent = '';
                document.getElementById('studentEmailed').textContent = '';
                document.getElementById('studentCreationDate').textContent = '';

                // Fetch data from the clicked button
                const fullName = this.getAttribute('data-fullname');
                const email = this.getAttribute('data-email');
                const avatar = this.getAttribute('data-avatar') || '';  // Default to an empty string if avatar is missing
                const creationDateRaw = this.getAttribute('data-created') || '';  // Default to an empty string if created_at is missing

                // Set modal content
                document.getElementById('studentName').textContent = fullName;
                document.getElementById('studentEmailed').textContent = email;

                // Format the creation date
                let formattedDate = 'N/A';
                if (creationDateRaw) {
                    const creationDate = new Date(creationDateRaw);
                    const options = { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true };
                    formattedDate = creationDate.toLocaleDateString('en-US', options);
                }
                document.getElementById('studentCreationDate').textContent = formattedDate;

                // Handle avatar/profile picture
                const profileIconPlaceholder = document.getElementById('studentProfileIconPlaceholder');
                if (avatar) {
                    // Display the avatar image if available
                    profileIconPlaceholder.innerHTML = `<img src="${avatar}" alt="Profile Picture" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">`;
                } else {
                    // Display default icon if no avatar is available
                    profileIconPlaceholder.innerHTML = `<i class="bi bi-person-circle profile-icon"></i>`;
                }

                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('viewStudentModal'));
                modal.show();
            });
        });

        document.getElementById('viewStudentModal').addEventListener('hidden.bs.modal', function () {
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

        document.querySelectorAll('.edit-btn').forEach(function(button) {
            button.addEventListener('click', function () {
                const studentId = this.getAttribute('data-id');
                const firstName = this.getAttribute('data-first-name');
                const middleName = this.getAttribute('data-middle-name');
                const lastName = this.getAttribute('data-last-name');
                const email = this.getAttribute('data-email');
                const avatar = this.getAttribute('data-avatar');

                document.getElementById('editStudentId').value = studentId;
                document.getElementById('editStudentFirstName').value = firstName;
                document.getElementById('editStudentMiddleName').value = middleName;
                document.getElementById('editStudentLastName').value = lastName;
                document.getElementById('editStudentEmail').value = email;

                const avatarPreview = document.getElementById('editStudentAvatarPreview');
                const avatarContainer = document.getElementById('editStudentAvatarContainer');

                if (avatar && avatar !== '') {
                    avatarPreview.src = avatar;
                    avatarPreview.alt = 'Student Avatar';
                    avatarPreview.style.display = 'block';  // Show the image
                } else {
                    avatarPreview.src = '../Logo/img/default-profile.png';  // Default avatar image
                    avatarPreview.alt = 'Default Avatar';
                    avatarPreview.style.display = 'block';   // Show the image
                }
            });
        });



        document.getElementById('editStudentAvatar').addEventListener('change', function (event) {
            const [file] = event.target.files;
            const avatarPreview = document.getElementById('editStudentAvatarPreview');

            if (file) {
                avatarPreview.src = URL.createObjectURL(file);
                avatarPreview.alt = 'Student Avatar';
                avatarPreview.style.display = 'block';
            } else {
                avatarPreview.src = '';
                avatarPreview.alt = '';
                avatarPreview.style.display = 'none';
            }
        });



        document.getElementById('editStudentForm').addEventListener('submit', function (event) {
            event.preventDefault(); // Prevent default form submission

            const formData = new FormData(this);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'actions/edit-student.php', true); // POST request to edit-student.php

            xhr.onload = function () {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Hide the edit modal after success
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editStudentModal'));
                        modal.hide();

                        // Update the row dynamically without page reload
                        const studentRow = document.querySelector(`tr[data-id="${formData.get('student_id')}"]`);
                        const updatedFullName = `${formData.get('studentFirstName')} ${formData.get('studentMiddleName')[0]}. ${formData.get('studentLastName')}`;
                        
                        // Update the table row
                        studentRow.cells[1].textContent = updatedFullName;
                        studentRow.cells[2].textContent = formData.get('studentEmail');

                        // Optionally update avatar
                        const avatarFile = document.getElementById('editStudentAvatar').files[0];
                        if (avatarFile) {
                            const avatarUrl = URL.createObjectURL(avatarFile);
                            studentRow.querySelector('.view-btn').setAttribute('data-avatar', avatarUrl);
                            studentRow.querySelector('.edit-btn').setAttribute('data-avatar', avatarUrl);
                        }

                        // Update button attributes with new values
                        const editButton = studentRow.querySelector('.edit-btn');
                        editButton.setAttribute('data-first-name', formData.get('studentFirstName'));
                        editButton.setAttribute('data-middle-name', formData.get('studentMiddleName'));
                        editButton.setAttribute('data-last-name', formData.get('studentLastName'));
                        editButton.setAttribute('data-email', formData.get('studentEmail'));

                        // Update the view modal's fields asynchronously
                        const viewButton = studentRow.querySelector('.view-btn');
                        viewButton.setAttribute('data-fullname', updatedFullName);
                        viewButton.setAttribute('data-email', formData.get('studentEmail'));

                        if (avatarFile) {
                            viewButton.setAttribute('data-avatar', avatarUrl);
                        }

                        // Update the view modal if it's currently open
                        const viewModal = document.getElementById('viewStudentModal');
                        if (viewModal.classList.contains('show')) {
                            document.getElementById('studentName').textContent = updatedFullName;
                            document.getElementById('studentEmailed').textContent = formData.get('studentEmail');

                            if (avatarFile) {
                                document.getElementById('studentProfileIconPlaceholder').innerHTML = `<img src="${avatarUrl}" alt="Profile Picture" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">`;
                            }
                        }

                        // Show the success message (similar to adding or deleting)
                        displaySuccessMessage(response.message);
                    } else {
                        displayErrorMessage(response.message || 'An error occurred while updating student details.');
                    }
                } else {
                    displayErrorMessage('An error occurred while updating student details.');
                }
            };

            xhr.send(formData); // Send the FormData object via AJAX
        });

        // Function to display the success message
        function displaySuccessMessage(message) {
            const messageContainer = document.createElement('div');
            messageContainer.classList.add('alert', 'alert-success', 'alert-dismissible', 'fade', 'show', 'mt-3');
            messageContainer.setAttribute('role', 'alert');
            messageContainer.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            const container = document.querySelector('.container'); // Adjust this selector to where you want the message to appear
            container.insertBefore(messageContainer, container.firstChild);

            // Auto-hide the message after 5 seconds
            setTimeout(() => {
                if (messageContainer) {
                    messageContainer.remove();
                }
            }, 5000);
        }

        // Function to display the error message
        function displayErrorMessage(message) {
            const messageContainer = document.createElement('div');
            messageContainer.classList.add('alert', 'alert-danger', 'alert-dismissible', 'fade', 'show', 'mt-3');
            messageContainer.setAttribute('role', 'alert');
            messageContainer.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            const container = document.querySelector('.container'); // Adjust this selector to where you want the message to appear
            container.insertBefore(messageContainer, container.firstChild);

            // Auto-hide the message after 5 seconds
            setTimeout(() => {
                if (messageContainer) {
                    messageContainer.remove();
                }
            }, 5000);
        }

        // Show loader and overlay when form is being submitted and disable pointer events on content
        document.getElementById('addNewStudentModal').addEventListener('submit', function (event) {
            // Show loader and overlay, and disable content interaction
            document.getElementById('loader').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('content').classList.add('loading-state');
        });
    </script>

</body>
</html>
