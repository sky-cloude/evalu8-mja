<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <title>Admin List</title>
    
    <?php include('../header.php'); ?>

    <!-- CSS Files -->
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

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

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
</head>
<body>
    <?php 
    session_start(); 
    include('../db_conn.php');
    ?>
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
    
    <!-- Full-screen overlay -->
    <div id="overlay" class="overlay"></div>

    <!-- Loader Spinner -->
    <div id="loader" class="loader" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p>Sending Email...</p>
    </div>

    <!-- ADMIN LIST CONTENTS -->
    <p class="classTitle fs-4 fw-bold ms-5 mb-2">Admin List</p>
    <div class="mx-auto mb-4" style="height: 2px; background-color: #facc15; width:95%;"></div> <!-- YELLOW LINE -->

    <div class="container bg-white rounded border shadow mb-5" style="width: 95%;">
        <!-- Display Bootstrap alert if message is set -->
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

        <?php
            // Count the number of admin accounts in the database
            $query = "SELECT COUNT(*) AS total_admins FROM admin_account";
            $result = mysqli_query($conn, $query);
            $row = mysqli_fetch_assoc($result);
            $totalAdmins = $row['total_admins'];
        ?>

        <div class="buttonContainer">
            <button 
                id="addNewBtn"
                class="btn custom-dark-brown-btn mt-3 mb-3 float-end"
                data-bs-toggle="modal"
                data-bs-target="#addNewModal"
                <?php if ($totalAdmins >= 3) { echo 'disabled'; } ?>
            > <i class="bi bi-plus-lg me-1"></i>Add New </button>
        </div>

        <hr class="bg-dark" style="border-width: 1px; margin-top: 70px; margin-bottom: 20px;"><!-- GRAY LINE -->

        <!-- Show Entries and Search Bar -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label>
                    Show 
                    <select class="form-select d-inline w-auto" name="entries" id="entriesSelect">
                        <option value="3">3</option>
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                    </select> entries
                </label>
            </div>
            <div class="col-md-6 text-end">
                <label>Search:
                    <input type="search" class="form-control d-inline w-auto" id="tableSearch">
                </label>
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
                    $query = "SELECT admin_id, lastName, firstName, middleName, email, avatar, created_at FROM admin_account";
                    $result = mysqli_query($conn, $query);

                    if (!$result) {
                        die("Query failed: " . mysqli_error($conn));
                    }

                    $index = 1;
                    if (mysqli_num_rows($result) > 0) {
                        while ($admin = mysqli_fetch_assoc($result)):
                            $middleInitial = !empty($admin['middleName']) ? strtoupper($admin['middleName'][0]) . '.' : '';
                            $fullName = htmlspecialchars($admin['firstName']) . ' ' . $middleInitial . ' ' . htmlspecialchars($admin['lastName']);

                            // Convert BLOB data to base64-encoded image
                            $avatarData = $admin['avatar'];
                            $avatarBase64 = !empty($avatarData) ? 'data:image/jpeg;base64,' . base64_encode($avatarData) : '';
                    ?>
                        <tr data-id="<?php echo $admin['admin_id']; ?>">
                            <td><?php echo $index++; ?></td>
                            <td class="text-start"><?php echo $fullName; ?></td>
                            <td class="text-start"><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-success btn-sm edit-btn" 
                                        data-id="<?php echo $admin['admin_id']; ?>" 
                                        data-fullname="<?php echo $fullName; ?>" 
                                        data-email="<?php echo $admin['email']; ?>" 
                                        data-avatar="<?php echo $avatarBase64; ?>" 
                                        data-created="<?php echo htmlspecialchars($admin['created_at']); ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#viewModal">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm delete-btn" 
                                            data-id="<?php echo $admin['admin_id']; ?>" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteConfirmationModal">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php 
                        endwhile; 
                    } else { 
                    ?>
                        <tr id="noResultRow">
                            <td colspan="4">No result.</td>
                        </tr>
                    <?php 
                    } 
                    ?>
                </tbody>

            </table>
        </div>

        <div class="container mt-3 mb-3">
            <div class="row">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <p class="mb-0" id="pageInfo">Showing 1 to <?php echo mysqli_num_rows($result); ?> of <?php echo mysqli_num_rows($result); ?> entries</p>
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

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmationLabel">Delete Admin Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this admin account?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Modal -->
    <div class="modal fade" id="addNewModal" tabindex="-1" aria-labelledby="addNewModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addAdminForm" action="actions/add-admin.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addNewModalLabel">Add New Admin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- First Name -->
                        <div class="mb-3">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName" name="firstName" required>
                        </div>
                        <!-- Middle Name -->
                        <div class="mb-3">
                            <label for="middleName" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="middleName" name="middleName">
                        </div>
                        <!-- Last Name -->
                        <div class="mb-3">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastName" name="lastName" required>
                        </div>
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <!-- Profile Picture (File Input) -->
                        <div class="mb-3">
                            <label for="avatarInput" class="form-label">Profile Picture (optional, must be 1x1 aspect ratio)</label>
                            <input type="file" class="form-control" id="avatarInput" name="avatar" accept="image/*">
                            <small id="avatarError" class="text-danger"></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Admin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Admin Details Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewModalLabel">Admin Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="admin-modal-body">
                    <!-- Placeholder for profile picture or icon -->
                    <div id="profileIconPlaceholder">
                        <i id="profileIcon" class="bi bi-person-circle profile-icon"></i>
                    </div>
                    <h5 class="admin-name mb-1 mt-4" id="adminName"></h5>
                    <p class="admin-email mb-2" id="adminEmail"></p>
                    <p class="admin-info mt-4 mb-1"><strong>Account created:</strong> <span id="adminCreationDate"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Send Email Confirmation Modal -->
    <div class="modal fade" id="emailConfirmationModal" tabindex="-1" aria-labelledby="emailConfirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="emailConfirmationModalLabel">Send Login Credentials</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to send the login credentials to <span id="adminEmailToConfirm"></span>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmSendEmailBtn">Send</button>
                </div>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>

    <script>
        // Password match validation
        function checkPasswords() {
            let password = document.getElementById('passwordUnique').value.trim();
            let confirmPassword = document.getElementById('confirmPassword').value.trim();
            const matchMessage = document.getElementById('passwordMatchMessage');

            matchMessage.textContent = ''; // Clear previous messages

            if (password !== confirmPassword) {
                matchMessage.textContent = 'Passwords do not match';
                matchMessage.style.color = 'red';
            } else {
                matchMessage.textContent = 'Passwords match';
                matchMessage.style.color = 'green';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('passwordUnique').addEventListener('input', checkPasswords);
            document.getElementById('confirmPassword').addEventListener('input', checkPasswords);
        });

        // Delete admin functionality
        let adminToDelete = null; // Store the admin ID to be deleted

        // When delete button is clicked, open the modal and store the admin ID
        document.querySelectorAll('.delete-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                adminToDelete = this.getAttribute('data-id');
                const modal = new bootstrap.Modal(document.getElementById('deleteConfirmationModal'));
                modal.show();
            });
        });

        // When the confirm delete button is clicked, delete the admin account
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (adminToDelete) {
                fetch(`actions/delete-admin.php?id=${adminToDelete}`, {
                    method: 'GET',
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload(); // Reload to display the message from session
                    } else {
                        alert('Failed to delete admin account: ' + (data.error || 'Unknown error occurred.'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting admin account.');
                });
            }
        });


        // Search Functionality
        document.getElementById('tableSearch').addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            let table = document.getElementById('tableBody');
            let rows = table.getElementsByTagName('tr');
            let hasResult = false;

            let noResultRow = document.getElementById('noResultRow');
            if (noResultRow) {
                noResultRow.remove();
            }

            for (let i = 0; i < rows.length; i++) {
                let cells = rows[i].getElementsByTagName('td');
                let rowContainsFilter = false;
                
                for (let j = 0; j < cells.length; j++) {
                    if (cells[j]) {
                        let cellText = cells[j].textContent || cells[j].innerText;
                        if (cellText.toLowerCase().indexOf(filter) > -1) {
                            rowContainsFilter = true;
                            break;
                        }
                    }
                }

                if (rowContainsFilter) {
                    rows[i].style.display = '';
                    hasResult = true;
                } else {
                    rows[i].style.display = 'none';
                }
            }

            if (!hasResult) {
                let newRow = document.createElement('tr');
                newRow.id = 'noResultRow';
                newRow.innerHTML = '<td colspan="4" class="text-center">No result.</td>';
                table.appendChild(newRow);
            }
        });

        let currentPage = 1; // The current page being viewed
        let rowsPerPage = 3; // Default number of rows per page
        let totalPages; // Total number of pages

        // Function to display a subset of rows based on the current page
        function displayPage(page) {
            const table = document.getElementById('tableBody');
            const rows = table.getElementsByTagName('tr');
            const totalRows = rows.length;
            totalPages = Math.ceil(totalRows / rowsPerPage); // Calculate total pages
            
            for (let i = 0; i < rows.length; i++) {
                rows[i].style.display = 'none';
            }

            let start = (page - 1) * rowsPerPage;
            let end = page * rowsPerPage;
            for (let i = start; i < end && i < totalRows; i++) {
                rows[i].style.display = '';
            }

            document.getElementById('prevBtn').disabled = (page === 1);
            document.getElementById('nextBtn').disabled = (page === totalPages);
            
            document.getElementById('pageInfo').innerText = `Showing ${start + 1} to ${Math.min(end, totalRows)} of ${totalRows} entries`;
        }

        document.getElementById('nextBtn').addEventListener('click', function() {
            if (currentPage < totalPages) {
                currentPage++;
                displayPage(currentPage);
            }
        });

        document.getElementById('prevBtn').addEventListener('click', function() {
            if (currentPage > 1) {
                currentPage--;
                displayPage(currentPage);
            }
        });

        document.getElementById('entriesSelect').addEventListener('change', function() {
            rowsPerPage = parseInt(this.value);
            currentPage = 1; // Reset to the first page when changing the number of entries
            displayPage(currentPage);
        });

        document.addEventListener('DOMContentLoaded', function() {
            displayPage(currentPage);
        });

        // View admin functionality (showing details in the modal)
        document.querySelectorAll('.edit-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                const fullName = this.getAttribute('data-fullname');
                const email = this.getAttribute('data-email');
                const creationDateRaw = this.getAttribute('data-created');
                const avatar = this.getAttribute('data-avatar');

                // Format the creation date
                const creationDate = new Date(creationDateRaw);
                const options = { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true };
                const formattedDate = creationDate.toLocaleDateString('en-US', options);

                // Update modal content
                document.getElementById('adminName').textContent = fullName;
                document.getElementById('adminEmail').textContent = email;
                document.getElementById('adminCreationDate').textContent = formattedDate;

                // Update avatar (check if it's base64 data)
                const profileIconPlaceholder = document.getElementById('profileIconPlaceholder');
                if (avatar && avatar !== '') {
                    profileIconPlaceholder.innerHTML = `<img src="${avatar}" alt="Profile Picture" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">`;
                } else {
                    profileIconPlaceholder.innerHTML = `<i class="bi bi-person-circle profile-icon"></i>`;
                }

                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('viewModal'));
                modal.show();
            });
        });

        // Ensure proper dismissal of modals and cleanup of gray backdrop
        document.addEventListener('DOMContentLoaded', function() {
            const viewModal = document.getElementById('viewModal');
            const deleteModal = document.getElementById('deleteConfirmationModal');

            viewModal.addEventListener('hidden.bs.modal', function () {
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
                document.body.style.overflow = 'auto';
            });

            deleteModal.addEventListener('hidden.bs.modal', function () {
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            });
        });

        document.getElementById('avatarInput').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const img = new Image();
            const errorElement = document.getElementById('avatarError');
            const reader = new FileReader();

            reader.onload = function(e) {
                img.src = e.target.result;
                img.onload = function() {
                    if (img.width !== img.height) {
                        errorElement.textContent = "The image must be a 1x1 square aspect ratio.";
                        event.target.value = ''; // Reset the file input
                    } else {
                        errorElement.textContent = ''; // Clear the error message
                    }
                };
            };

            reader.readAsDataURL(file);
        });

        // Show loader and overlay when form is being submitted and disable pointer events on content
        document.getElementById('addAdminForm').addEventListener('submit', function (event) {
            // Show loader and overlay, and disable content interaction
            document.getElementById('loader').style.display = 'block';
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('content').classList.add('loading-state');
        });
    </script>
</body>
</html>
