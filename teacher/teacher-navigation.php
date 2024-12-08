<?php
include('../db_conn.php');

// Check if teacher ID is set in session and the user is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'teacher') {
    header("Location: ../login/login-form.php");
    exit();
}
?>

<div class="sidebar close">
    <ul class="nav-list" style="margin-top: 30px;">
        <li>
            <a href="teacher-dashboard.php">
                <i class="bi bi-house"></i>
                <span class="link-name d-none d-sm-inline">Dashboard</span> <!-- Show label only on small screens and above -->
            </a>
            <ul class="sub-menu blank">
                <li><a href="teacher-dashboard.php" class="link-name" style="font-size:12px;">Dashboard</a></li>
            </ul>
        </li>

        <li>
            <a href="teacher-evaluation-report.php">
                <i class="bi bi-file-ruled"></i>
                <span class="link-name d-none d-sm-inline">Evaluation Report</span>
            </a>
            <ul class="sub-menu blank">
                <li><a href="teacher-evaluation-report.php" class="link-name" style="font-size:12px;">Evaluation Report</a></li>
            </ul>
        </li>

        <li>
            <a href="teacher-dtr.php">
                <i class="bi bi-calendar-event"></i>
                <span class="link-name d-none d-sm-inline">Daily Time Record</span>
            </a>
            <ul class="sub-menu blank">
                <li><a href="teacher-dtr.php" class="link-name" style="font-size:12px;">Daily Time Record</a></li>
            </ul>
        </li>
    </ul>
</div>

<div class="wrapper pb-5 pt-4" style="min-height: 100vh; height: auto; background-color: #fafafa;">
    <nav class="navbar navbar-expand-lg" style="background-color: #44311f; display: flex; align-items: center; height: 65px; width: 100%; position: fixed; top: 0; z-index: 1000;">
        <div class="container-fluid">
            <!-- <i class="bi bi-list text-white" style="font-size: 1.5rem; cursor: pointer;"></i> -->
            <img src="../Logo/mja-logo.png" alt="Mary Josette Academy" style="width: 50px; height: auto; cursor: pointer;" class="img-fluid ms-4">
            <a class="navbar-brand text-white fw-bold fs-6 d-none ms-2 d-lg-block" href="teacher-dashboard.php">Evalu8: A Faculty Evaluation System</a>
            <div class="dropdown ms-auto me-lg-4" style="position: relative;">
                <a class="nav-link dropdown-toggle d-flex align-items-center text-white-50" style="margin-right: 75px; float: right;" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php
                    $id = intval($_SESSION['user_id']);
                    $query = "SELECT firstName, middleName, lastName, email, department, avatar FROM teacher_account WHERE teacher_id = '$id'";
                    $result = mysqli_query($conn, $query);

                    if ($result && mysqli_num_rows($result) > 0) {
                        $row = mysqli_fetch_assoc($result);
                        if (!empty($row['avatar'])): ?>
                            <img src="data:image/jpg;charset=utf8;base64,<?php echo base64_encode($row['avatar']); ?>" alt="User" class="rounded-circle" width="40" height="40">
                        <?php else: ?>
                            <i class="bi bi-person-circle" style="font-size: 40px;"></i>
                        <?php endif; ?>
                        <span class="fname ms-2 text-white fw-bold"><?php echo strtoupper($row['lastName']) . ", " . strtoupper($row['firstName']); ?></span>
                    <?php } ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuLink" style="position: fixed; top: 8%; right: 1%; z-index: 1050;">
                    <li><a class="dropdown-item text-dark" href="#" data-bs-toggle="modal" data-bs-target="#manageAccountModal">Manage Account</a>
                    </li>
                    <li><a class="dropdown-item text-dark" href="../login/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>


    <!-- Flash Message -->
    <div id="flashMessage" class="flash-message mt-5">
        Account updated successfully!
    </div>

    <!-- MANAGE ACCOUNT MODAL -->
    <div class="modal fade" id="manageAccountModal" tabindex="-1" aria-labelledby="manageAccountLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #281313;">
                    <h5 class="modal-title" id="manageAccountLabel" style="color: white;">Manage Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="manageAccountForm" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-12 col-sm-6">
                                <label for="firstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo isset($row['firstName']) ? htmlspecialchars($row['firstName']) : ''; ?>" required>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="middleName" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middleName" name="middleName" value="<?php echo isset($row['middleName']) ? htmlspecialchars($row['middleName']) : ''; ?>">
                            </div>
                        </div>

                        <!-- Last Name Field -->
                        <div class="mb-3">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo isset($row['lastName']) ? htmlspecialchars($row['lastName']) : ''; ?>" required>
                        </div>
                        <!-- Department Field -->
                        <div class="mb-3">
                            <label for="department" class="form-label">Department</label>
                            <select class="form-select" id="department" name="department" required>
                                <option value="Junior High School" <?php echo (isset($row['department']) && $row['department'] == 'Junior High School') ? 'selected' : ''; ?>>Junior High School</option>
                                <option value="Senior High School" <?php echo (isset($row['department']) && $row['department'] == 'Senior High School') ? 'selected' : ''; ?>>Senior High School</option>
                                <option value="Both (JHS & SHS)" <?php echo (isset($row['department']) && $row['department'] == 'Both (JHS & SHS)') ? 'selected' : ''; ?>>Both (JHS & SHS)</option>
                            </select>
                        </div>

                        <!-- Email Field -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($row['email']) ? htmlspecialchars($row['email']) : ''; ?>" required>
                        </div>
                        <!-- Password Field (Leave blank if not changing) -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank if not changing" value="">
                        </div>
                        <!-- Avatar Input Field -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="avatarInput" class="form-label" style="font-size:0.875rem;">Avatar (Only use square image, 1:1 aspect ratio)</label>
                                <input type="file" class="form-control" id="avatarInput" name="avatar" accept="image/*">
                            </div>
                        </div>


                        <!-- Hidden Input for Cropped Image Data -->
                        <input type="hidden" id="croppedImageData" name="croppedImageData">

                        <div class="modal-footer">
                            <button type="button" id="saveButton" class="btn btn-primary">Save</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Confirm Save Modal -->
    <div class="modal fade" id="confirmSaveModal" tabindex="-1" aria-labelledby="confirmSaveLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmSaveLabel">Confirm Save</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to save these changes?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmSaveButton">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Disable the Save button by default
        const saveButton = document.getElementById('saveButton');
        saveButton.disabled = true;

        // Store original values of form fields when the modal is opened
        let originalValues = {};

        $('#manageAccountModal').on('show.bs.modal', function () {
            const form = document.getElementById('manageAccountForm');
            const inputs = form.querySelectorAll('input, select');

            document.getElementById('password').value = ''; // Clear password field

            // Capture initial values of each input field
            inputs.forEach(input => {
                originalValues[input.name] = input.value;
            });

            // Disable the Save button by default when the modal opens
            saveButton.disabled = true;
        });

        // Function to check if any form field has changed
        function checkForChanges() {
            const form = document.getElementById('manageAccountForm');
            const inputs = form.querySelectorAll('input, select'); // Include select fields

            let formChanged = false;

            // Compare current input values with the original ones
            inputs.forEach(input => {
                if (input.value !== originalValues[input.name]) {
                    formChanged = true;
                }
            });

            // Enable the Save button only if form has changed
            saveButton.disabled = !formChanged;
        }

        // Add event listeners to detect changes in each input and select field
        document.getElementById('manageAccountForm').querySelectorAll('input, select').forEach(input => {
            input.addEventListener('input', checkForChanges);
        });

        // Handle avatar input change separately and ensure it triggers the check
        document.getElementById('avatarInput').addEventListener('change', function(event) {
            checkForChanges();  // Recheck for form changes after avatar change
        });

        // Show confirmation modal when Save button is clicked
        saveButton.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent the form from submitting normally
            $('#confirmSaveModal').modal('show');  // Show confirmation modal
        });

        // Handle confirmation modal 'Confirm' button click to submit the form
        document.getElementById('confirmSaveButton').addEventListener('click', function() {
            const formData = new FormData(document.getElementById('manageAccountForm'));

            $.ajax({
                url: 'actions/update-teacher.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response.status === 'success') {
                        showFlashMessage(response.message);

                        // Update the avatar if a new one was uploaded
                        if (response.newAvatarUrl) {
                            const navLink = document.querySelector('.nav-link.dropdown-toggle');
                            if (navLink) {
                                const existingAvatar = navLink.querySelector('img, i');
                                if (existingAvatar) {
                                    existingAvatar.remove();
                                }

                                const imgElement = document.createElement('img');
                                imgElement.src = response.newAvatarUrl;
                                imgElement.alt = 'User';
                                imgElement.classList.add('rounded-circle');
                                imgElement.width = 40;
                                imgElement.height = 40;

                                navLink.insertBefore(imgElement, navLink.firstChild);
                            }
                        }

                        // Update the name in the navbar
                        const nameElement = document.querySelector('.fname');
                        nameElement.textContent = response.updatedName;

                        // Hide the modals
                        $('#manageAccountModal').modal('hide');
                        $('#confirmSaveModal').modal('hide');
                    } else {
                        showFlashMessage(response.message);
                    }
                },
                error: function() {
                    showFlashMessage('There was an error updating your profile. Please try again.');
                }
            });
        });

        // Function to display the flash message
        function showFlashMessage(message) {
            const flashMessage = document.getElementById('flashMessage');
            flashMessage.textContent = message;
            flashMessage.style.display = 'block';
            flashMessage.style.opacity = '1';

            setTimeout(() => {
                flashMessage.style.opacity = '0';
                setTimeout(() => {
                    flashMessage.style.display = 'none';
                }, 500); // Adjust to sync with the transition duration
            }, 2000); // Flash message will be visible for 2 seconds
        }
    </script>



    <style>
        /* Flash message styles */
        .flash-message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border-radius: 5px;
            display: none;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }

        @media (max-width: 400px) {
            /* Ensure the modal content fits well in smaller screens */
            .modal-dialog {
                width: 100%;
                margin: 0;
            }
            
            .modal-content {
                width: 100%;
                height: auto;
                max-height: 100vh; /* Max height should not exceed the viewport height */
                overflow-y: auto; /* Scroll the modal content if it overflows */
            }
            
            .modal-body {
                max-height: 60vh; /* Keep body height reasonable and scrollable */
                overflow-y: auto;
            }
            
            /* Ensure no padding issues */
            .modal-header, .modal-footer {
                padding: 1rem;
            }

            .modal-header {
                border-bottom: 1px solid #dee2e6;
            }

            .modal-footer {
                border-top: 1px solid #dee2e6;
            }
        }


    </style>
