<?php
// staff-list.php
session_start();
include('../db_conn.php'); // Ensure the path is correct
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff List</title>
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <?php include('../header.php'); ?>
    <!-- CSS files -->
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

        #editStaffAvatarPreview {
            margin: 0 auto; /* Horizontally center the image */
            display: block; /* Ensures it takes up its own line */
        }

    </style>
</head>
<body>
    <?php 
    include('navigation.php'); 
    ?>

    <!-- Flash Message -->
    <div id="flashMessage" class="flash-message"></div>

    <?php 
    // Display the flash message if set
    // if (isset($_SESSION['message'])) {
    //     // Escape the message to prevent XSS
    //     $message = addslashes($_SESSION['message']);
    //     $message_type = $_SESSION['message_type'];
    //     echo '<script>
    //             document.addEventListener("DOMContentLoaded", function() {
    //                 showFlashMessage("' . $message . '", "' . $message_type . '");
    //             });
    //         </script>';
    //     unset($_SESSION['message']);
    //     unset($_SESSION['message_type']);
    // }
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

    <p class="classTitle fs-4 fw-bold ms-5 mb-2">Staff List</p>
    <div class="mx-auto mb-4" style="height: 2px; background-color: #facc15; width:95%;"></div>
    
    <div class="container bg-white rounded border shadow mb-5" style="width: 95%;">
        <div class="buttonContainer">
            <button id="addNewBtn" class="btn custom-dark-brown-btn mt-3 mb-3 float-end" data-bs-toggle="modal" data-bs-target="#addNewStaffModal"><i class="bi bi-plus-lg me-1"></i>Add New</button>
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
                        <th class="text-start">Role</th>
                        <th class="text-start">Employee ID</th>
                        <th style="width: 150px;">Action</th>
                    </tr>
                </thead>
                <tbody class="text-center" id="tableBody">
                    <?php
                    $query = "SELECT staff_id, lastName, firstName, middleName, email, avatar, staff_role, emp_code, created_at FROM staff_account";
                    $result = mysqli_query($conn, $query);

                    if (!$result) {
                        die("Query failed: " . mysqli_error($conn));
                    }

                    $index = 1;
                    while ($staff = mysqli_fetch_assoc($result)) {
                        $middleInitial = !empty($staff['middleName']) ? strtoupper($staff['middleName'][0]) . '.' : '';
                        $fullName = htmlspecialchars($staff['firstName']) . ' ' . $middleInitial . ' ' . htmlspecialchars($staff['lastName']);
                        $firstName = htmlspecialchars($staff['firstName']);
                        $middleName = htmlspecialchars($staff['middleName']);
                        $lastName = htmlspecialchars($staff['lastName']);
                        $role = htmlspecialchars($staff['staff_role']); // Retrieve the role

                        $avatarData = $staff['avatar'];
                        $avatarBase64 = !empty($avatarData) ? 'data:image/jpeg;base64,' . base64_encode($avatarData) : '';
                    ?>
                        <tr data-id="<?= $staff['staff_id']; ?>">
                            <td class="text-center"><?= $index++; ?></td>
                            <td class="text-start"><?= $fullName; ?></td> 
                            <td class="text-start"><?= htmlspecialchars($staff['email']); ?></td> 
                            <td class="text-start"><?= $role; ?></td> 
                            <td class="text-start">
                                <?php if (!empty($staff['emp_code'])): ?>
                                    <?= htmlspecialchars($staff['emp_code']); ?>
                                <?php else: ?>
                                    <span class="text-muted"><i>Assign the Employee ID to match the staff's ID in the biometric device.</i></span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <button class="btn btn-success btn-sm view-btn" 
                                        data-id="<?= $staff['staff_id']; ?>" 
                                        data-fullname="<?= $fullName; ?>" 
                                        data-email="<?= htmlspecialchars($staff['email']); ?>" 
                                        data-avatar="<?= $avatarBase64; ?>" 
                                        data-role="<?= $role; ?>"  
                                        data-created="<?= htmlspecialchars($staff['created_at']); ?>" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#viewStaffModal">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>

                                    <button class="btn btn-primary btn-sm edit-btn" 
                                        data-id="<?= $staff['staff_id']; ?>" 
                                        data-first-name="<?= $firstName; ?>"
                                        data-middle-name="<?= $middleName; ?>"
                                        data-last-name="<?= $lastName; ?>"
                                        data-email="<?= htmlspecialchars($staff['email']); ?>" 
                                        data-avatar="<?= $avatarBase64; ?>"
                                        data-role="<?= $role; ?>"  
                                        data-emp-code="<?= htmlspecialchars($staff['emp_code']); ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editStaffModal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <button class="btn btn-danger btn-sm delete-btn" data-id="<?= $staff['staff_id']; ?>" data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"><i class="bi bi-trash3-fill"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php 
                    }
                    ?>
                    <!-- No result row placeholder -->
                    <tr id="noResultRow" style="display: none;">
                        <td colspan="6" class="text-center">No result.</td> <!-- Adjust colspan to match number of columns -->
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

    <!-- Add New Staff Modal -->
    <div class="modal fade" id="addNewStaffModal" tabindex="-1" aria-labelledby="addNewStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="addNewStaffModalLabel">Add New Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Modal Body -->
                <div class="modal-body">
                    <form id="addNewStaffForm" method="POST" enctype="multipart/form-data" action="actions/add-staff.php">
                        <div class="mb-3">
                            <label for="staffFirstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="staffFirstName" name="staffFirstName" required>
                        </div>
                        <div class="mb-3">
                            <label for="staffMiddleName" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="staffMiddleName" name="staffMiddleName">
                        </div>
                        <div class="mb-3">
                            <label for="staffLastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="staffLastName" name="staffLastName" required>
                        </div>
                        <div class="mb-3">
                            <label for="staffRole" class="form-label">Role</label>
                            <select class="form-select" id="staffRole" name="staffRole" required>
                                <option value="" disabled selected>Select a role</option>
                                <option value="Guard (Day Shift)">Guard (Day Shift)</option>
                                <option value="Guard (Night Shift)">Guard (Night Shift)</option>
                                <option value="Janitor">Janitor</option>
                                <option value="Office Staff">Office Staff</option>
                                <option value="Assistant Principal">Assistant Principal</option>
                                <option value="Primary School Teacher">Primary School Teacher</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="employeeID" class="form-label">Employee ID (Staff's ID in Biometrics device)</label>
                            <input type="number" class="form-control" id="employeeID" name="employeeID" required>
                        </div>
                        <div class="mb-3">
                            <label for="staffEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="staffEmail" name="staffEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="staffAvatar" class="form-label">Profile Picture (optional, must be 1x1 aspect ratio)</label>
                            <input type="file" class="form-control" id="staffAvatar" name="staffAvatar" accept="image/*">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Add Staff</button>
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
                    Are you sure you want to delete this staff member's account?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteStaffForm">
                        <input type="hidden" id="deleteStaffId" name="staff_id">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

   <!-- Staff Details Modal -->
    <div class="modal fade" id="viewStaffModal" tabindex="-1" aria-labelledby="viewStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewStaffModalLabel">Staff Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="admin-modal-body">
                    <!-- Profile Icon Placeholder -->
                    <div id="staffProfileIconPlaceholder" class="profile-icon-container">
                        <img id="staffProfileImage" class="rounded-circle" alt="Profile Picture" style="width: 80px; height: 80px; object-fit: cover; display: none;" />
                        <i id="staffProfileIcon" class="bi bi-person-circle profile-icon text-white"></i>
                    </div>
                    <!-- Staff Information -->
                    <h5 class="admin-name mb-1 mt-4" id="staffName"></h5>
                    <p class="admin-email mb-2 fs-6"><strong>Email Address: </strong><span id="staffEmailed"></span></p>
                    <p class="admin-role mt-1 mb-4">
                        <strong>Staff Role: </strong><span id="staffRoled"></span>
                    </p>
                    <!-- Spacer for better visual separation -->
                    <div style="margin: 10px 0;"></div>
                    <p class="admin-info mt-4 mb-1">
                        <strong>Account created:</strong> <span id="staffCreationDate"></span>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Staff Modal -->
    <div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="editStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStaffModalLabel">Edit Staff Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editStaffForm" enctype="multipart/form-data">
                        <input type="hidden" id="editStaffId" name="staff_id">

                        <div class="mb-3">
                            <label for="editStaffFirstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="editStaffFirstName" name="staffFirstName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editStaffMiddleName" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="editStaffMiddleName" name="staffMiddleName">
                        </div>
                        <div class="mb-3">
                            <label for="editStaffLastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="editStaffLastName" name="staffLastName" required>
                        </div>
                        <div class="mb-3">
                            <label for="editStaffRole" class="form-label">Role</label>
                            <select class="form-select" id="editStaffRole" name="staffRole" required>
                                <option value="" disabled>Select a role</option>
                                <option value="Guard (Day Shift)">Guard (Day Shift)</option>
                                <option value="Guard (Night Shift)">Guard (Night Shift)</option>
                                <option value="Janitor">Janitor</option>
                                <option value="Office Staff">Office Staff</option>
                                <option value="Assistant Principal">Assistant Principal</option>
                                <option value="Primary School Teacher">Primary School Teacher</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editEmployeeID" class="form-label">Employee ID (Staff's ID in Biometrics device)</label>
                            <input type="number" class="form-control" id="editEmployeeID" name="employeeID" required>
                        </div>
                        <div class="mb-3">
                            <label for="editStaffEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="editStaffEmail" name="staffEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="editStaffAvatar" class="form-label">Profile Picture (optional, must be 1x1 aspect ratio)</label>
                            <input type="file" class="form-control" id="editStaffAvatar" name="staffAvatar" accept="image/*">
                            <!-- Avatar preview -->
                            <div class="mb-3 mt-4 text-center">
                                <img id="editStaffAvatarPreview" src="" alt="Avatar Preview" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;" />
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
                document.getElementById('deleteStaffId').value = deleteId;
            });
        });

        document.getElementById('deleteStaffForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const deleteId = document.getElementById('deleteStaffId').value;

            if (deleteId) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'actions/delete-staff.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.status === 'Success') {
                                // Remove the deleted row from allRows and filteredRows
                                const row = document.querySelector(`tr[data-id="${deleteId}"]`);
                                if (row) {
                                    row.remove();
                                    const rowIndex = allRows.indexOf(row);
                                    if (rowIndex > -1) {
                                        allRows.splice(rowIndex, 1);
                                    }
                                }

                                // Update filteredRows
                                filteredRows = filteredRows.filter(r => r.getAttribute('data-id') !== deleteId);

                                // Hide modal
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
                xhr.send('staff_id=' + encodeURIComponent(deleteId));
            }
        });


        // JavaScript to handle the "View" button clicks
        document.querySelectorAll('.view-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                // Clear previous modal content
                document.getElementById('staffRoled').textContent = '';
                document.getElementById('staffName').textContent = '';
                document.getElementById('staffEmailed').textContent = '';
                document.getElementById('staffCreationDate').textContent = '';

                // Fetch data from the clicked row
                const fullName = this.getAttribute('data-fullname');
                const email = this.getAttribute('data-email');
                const role = this.getAttribute('data-role');
                const creationDateRaw = this.getAttribute('data-created');
                const avatar = this.getAttribute('data-avatar');

                const creationDate = new Date(creationDateRaw);
                const options = { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true };
                const formattedDate = creationDate.toLocaleDateString('en-US', options);

                document.getElementById('staffName').textContent = fullName;
                document.getElementById('staffEmailed').textContent = email;
                document.getElementById('staffRoled').textContent = role;
                document.getElementById('staffCreationDate').textContent = formattedDate;

                const profileIconPlaceholder = document.getElementById('staffProfileIconPlaceholder');
                if (avatar && avatar !== '') {
                    profileIconPlaceholder.innerHTML = `<img src="${avatar}" alt="Profile Picture" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">`;
                } else {
                    profileIconPlaceholder.innerHTML = `<i class="bi bi-person-circle profile-icon text-white"></i>`;
                }

                // Show the modal after setting content
                const modal = new bootstrap.Modal(document.getElementById('viewStaffModal'));
                modal.show();
            });
        });

        // Clean up modal and backdrop after closing
        document.getElementById('viewStaffModal').addEventListener('hidden.bs.modal', function () {
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

        // Edit Modal setup
        document.querySelectorAll('.edit-btn').forEach(function(button) {
            button.addEventListener('click', function () {
                const staffId = this.getAttribute('data-id');
                const firstName = this.getAttribute('data-first-name');
                const middleName = this.getAttribute('data-middle-name');
                const lastName = this.getAttribute('data-last-name');
                const email = this.getAttribute('data-email');
                const role = this.getAttribute('data-role');
                const empCode = this.getAttribute('data-emp-code'); // Get Employee ID
                const avatar = this.getAttribute('data-avatar');

                document.getElementById('editStaffId').value = staffId;
                document.getElementById('editStaffFirstName').value = firstName;
                document.getElementById('editStaffMiddleName').value = middleName;
                document.getElementById('editStaffLastName').value = lastName;
                document.getElementById('editStaffEmail').value = email;
                document.getElementById('editEmployeeID').value = empCode; // Set Employee ID in modal

                // Set the role in the select dropdown
                const roleSelect = document.getElementById('editStaffRole');
                roleSelect.value = role;

                const avatarPreview = document.getElementById('editStaffAvatarPreview');
                if (avatar && avatar !== '') {
                    avatarPreview.src = avatar;
                } else {
                    avatarPreview.src = '../Logo/img/default-profile.png';
                }
                avatarPreview.alt = 'Staff Avatar';
                avatarPreview.style.display = 'block';
            });
        });

        // Image preview when a new avatar is uploaded
        document.getElementById('editStaffAvatar').addEventListener('change', function (event) {
            const [file] = event.target.files;
            const avatarPreview = document.getElementById('editStaffAvatarPreview');

            if (file) {
                avatarPreview.src = URL.createObjectURL(file);
                avatarPreview.alt = 'Staff Avatar';
                avatarPreview.style.display = 'block';
            } else {
                // If the file input is cleared, reset to the default image
                avatarPreview.src = '../Logo/img/default-profile.png';
                avatarPreview.alt = 'Default Avatar';
                avatarPreview.style.display = 'block';
            }
        });

        // Handle the edit form submission using AJAX
        document.getElementById('editStaffForm').addEventListener('submit', function (event) {
            event.preventDefault(); // Prevent default form submission

            const empCode = document.getElementById('editEmployeeID').value;

            // Check if empCode is empty or 0
            if (!empCode || empCode === "0") {
                showFlashMessage('Employee ID cannot be 0 or empty.', 'danger');
                return; // Stop form submission if empCode is invalid
            }

            const formData = new FormData(this);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'actions/edit-staff.php', true); // POST request to edit-staff.php

            xhr.onload = function () {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Hide the edit modal after success
                            const modal = bootstrap.Modal.getInstance(document.getElementById('editStaffModal'));
                            modal.hide();

                            // Find the table row corresponding to the staff
                            const staffId = formData.get('staff_id');
                            const staffRow = allRows.find(r => r.getAttribute('data-id') === staffId);

                            if (staffRow) {
                                // Update Full Name with middle initial
                                const firstName = formData.get('staffFirstName');
                                const middleName = formData.get('staffMiddleName');
                                const lastName = formData.get('staffLastName');
                                const updatedFullName = `${firstName} ${middleName ? middleName[0] + '.' : ''} ${lastName}`;

                                // Update columns in the row
                                staffRow.cells[1].textContent = updatedFullName; // Name
                                staffRow.cells[2].textContent = formData.get('staffEmail'); // Email
                                staffRow.cells[3].textContent = formData.get('staffRole'); // Role
                                staffRow.cells[4].textContent = empCode; // Employee ID

                                // Optionally update avatar
                                const avatarFile = document.getElementById('editStaffAvatar').files[0];
                                if (avatarFile) {
                                    const reader = new FileReader();
                                    reader.onload = function(e) {
                                        const avatarData = e.target.result;
                                        // Update avatar in view and edit buttons
                                        staffRow.querySelector('.view-btn').setAttribute('data-avatar', avatarData);
                                        staffRow.querySelector('.edit-btn').setAttribute('data-avatar', avatarData);
                                    }
                                    reader.readAsDataURL(avatarFile);
                                }

                                // Update button attributes with new values
                                const editButton = staffRow.querySelector('.edit-btn');
                                editButton.setAttribute('data-first-name', firstName);
                                editButton.setAttribute('data-middle-name', middleName);
                                editButton.setAttribute('data-last-name', lastName);
                                editButton.setAttribute('data-email', formData.get('staffEmail'));
                                editButton.setAttribute('data-role', formData.get('staffRole'));
                                editButton.setAttribute('data-emp-code', empCode); // Update Employee ID attribute

                                // Update view button attributes to reflect new data
                                const viewButton = staffRow.querySelector('.view-btn');
                                viewButton.setAttribute('data-fullname', updatedFullName);
                                viewButton.setAttribute('data-email', formData.get('staffEmail'));
                                viewButton.setAttribute('data-role', formData.get('staffRole'));

                                // Update filteredRows if the updated row matches the current search
                                const searchInput = document.getElementById('tableSearch').value.toLowerCase().trim();
                                if (searchInput !== '') {
                                    const cells = staffRow.getElementsByTagName('td');
                                    let matches = false;
                                    for (let j = 1; j < cells.length - 1; j++) { 
                                        if (cells[j].textContent.toLowerCase().includes(searchInput)) {
                                            matches = true;
                                            break;
                                        }
                                    }
                                    if (!matches) {
                                        // Remove from filteredRows
                                        filteredRows = filteredRows.filter(r => r !== staffRow);
                                        staffRow.style.display = 'none';
                                    }
                                }

                                // Show the success message
                                showFlashMessage(response.message, 'success');
                                // Refresh displayTableRows
                                displayTableRows();
                            }

                        } else {
                            showFlashMessage(response.message || 'An error occurred while updating staff details.', 'danger');
                        }
                    } catch (e) {
                        showFlashMessage('Invalid server response.', 'danger');
                    }
                } else {
                    showFlashMessage('An error occurred while updating staff details.', 'danger');
                }
            };

            xhr.onerror = function() {
                showFlashMessage('An error occurred during the request.', 'danger');
            };

            xhr.send(formData); // Send the FormData object via AJAX
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

        // Show loader and overlay when form is being submitted and disable pointer events on content
        document.getElementById('addNewStaffForm').addEventListener('submit', function (event) {
            // Show loader and overlay, and disable content interaction
            document.getElementById('loader').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('content').classList.add('loading-state');
        });
    </script>

</body>
</html>
