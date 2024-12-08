<?php
// session_start();
include('../db_conn.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: ../login/login-form.php");
    exit();
}

$id = intval($_SESSION['user_id']);
$query = "SELECT * FROM `student_account` WHERE `student_id` = '$id'";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
}

// Flash message display logic
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    $flash_message_type = $_SESSION['flash_message_type'];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_message_type']);
} else {
    $flash_message = '';
    $flash_message_type = '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include('../header.php'); ?>

    <title>Student Dashboard</title>

    <style>
    .flash-message {
        position: fixed;
        top: 70px;
        right: 20px;
        padding: 10px 20px;
        color: white;
        border-radius: 5px;
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.5s ease-in-out;
    }
    .flash-message.success {
        background-color: #28a745;
    }
    .flash-message.error {
        background-color: #dc3545;
    }
    nav .dropdown-menu .dropdown-item:hover,
    nav .dropdown-menu .dropdown-item:focus,
    nav .dropdown-menu .dropdown-item.active {
        background-color: #facc15;
        color: #000;
    }

    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg" style="background-color: #44311f; height: 65px; width: 100%; position: fixed; top: 0; z-index: 1000;">
    <div class="container-fluid">
        <a href="student-dashboard.php">
            <img src="../Logo/mja-logo.png" alt="Mary Josette Academy" style="width: 50px; height: auto; cursor: pointer;" class="img-fluid ms-4">
        </a>
        <a class="navbar-brand text-white fw-bold fs-6 d-none ms-2 d-lg-block" href="student-dashboard.php">Evalu8: A Faculty Evaluation System</a>
        <div class="dropdown ms-auto me-4">
            <a class="nav-link dropdown-toggle d-flex align-items-center text-white-50" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                <?php
                if (isset($row)) {
                    if (!empty($row['avatar'])) { ?>
                        <img src="data:image/jpg;charset=utf8;base64,<?php echo base64_encode($row['avatar']); ?>" alt="User" class="rounded-circle" width="40" height="40">
                    <?php } else { ?>
                        <i class="bi bi-person-circle" style="font-size: 40px;"></i>
                    <?php } ?>
                    <span class="fname ms-2 text-white fw-bold"><?php echo strtoupper($row['lastName']) . ", " . strtoupper($row['firstName']); ?></span>
                <?php }
                ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuLink">
                <li><a class="dropdown-item text-dark" href="#" data-bs-toggle="modal" data-bs-target="#manageAccountModal">Manage Account</a></li>
                <li><a class="dropdown-item text-dark" href="../login/logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Flash Message HTML -->
<?php if (!empty($flash_message)): ?>
    <div class="flash-message <?php echo $flash_message_type; ?>" id="flashMessage"><?php echo htmlspecialchars($flash_message); ?></div>
<?php endif; ?>

<!-- Manage Account Modal -->
<div class="modal fade" id="manageAccountModal" tabindex="-1" aria-labelledby="manageAccountLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md"> <!-- Default to modal-md for balanced appearance -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manageAccountLabel">Manage Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="manageAccountForm" method="POST" action="actions/edit-student-account.php" enctype="multipart/form-data">
                    <!-- Container for form content -->
                    <div class="container">
                        <!-- First Name Field -->
                        <div class="row mb-3">
                            <div class="col-12 col-sm-6">
                                <label for="firstName" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" value="<?php echo isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName']) : (isset($row['firstName']) ? htmlspecialchars($row['firstName']) : ''); ?>" required>
                            </div>
                            <!-- Middle Name Field -->
                            <div class="col-12 col-sm-6">
                                <label for="middleName" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middleName" name="middleName" value="<?php echo isset($_POST['middleName']) ? htmlspecialchars($_POST['middleName']) : (isset($row['middleName']) ? htmlspecialchars($row['middleName']) : ''); ?>">
                            </div>
                        </div>

                        <!-- Last Name Field -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="lastName" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="lastName" name="lastName" value="<?php echo isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName']) : (isset($row['lastName']) ? htmlspecialchars($row['lastName']) : ''); ?>" required>
                            </div>
                        </div>

                        <!-- Email Field -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : (isset($row['email']) ? htmlspecialchars($row['email']) : ''); ?>" required>
                            </div>
                        </div>

                        <!-- Password Field -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank if not changing">
                            </div>
                        </div>

                        <!-- Avatar Upload Field -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="avatarInput" class="form-label"  style="font-size:0.875rem;">Avatar (Only use square image, 1:1 aspect ratio)</label>
                                <input type="file" class="form-control" id="avatarInput" name="avatar" accept="image/*">
                            </div>
                        </div>

                        <!-- Avatar Display (if available) -->
                        <?php if (isset($row) && !empty($row['avatar'])) { ?>
                            <div class="row mb-3">
                                <div class="col-12 text-center">
                                    <img src="data:image/jpg;charset=utf8;base64,<?php echo base64_encode($row['avatar']); ?>" alt="User Avatar" class="rounded-circle img-fluid" style="width: 150px; height: 150px;">
                                </div>
                            </div>
                        <?php } ?>

                        <!-- Modal Footer -->
                        <div class="row">
                            <div class="col-12 text-end">
                                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="saveButton" data-bs-toggle="modal" data-bs-target="#confirmSaveModal" disabled>Save</button>
                            </div>
                        </div>
                    </div> <!-- End container -->
                </form>
            </div> <!-- End modal-body -->
        </div> <!-- End modal-content -->
    </div> <!-- End modal-dialog -->
</div> <!-- End modal -->

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
                <button type="button" class="btn btn-primary" onclick="document.getElementById('manageAccountForm').submit();">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('manageAccountForm');
    const saveButton = document.getElementById('saveButton');

    let originalValues = {};

    // Function to get the original values when the modal is opened
    function captureOriginalValues() {
        originalValues = {
            firstName: form.elements['firstName'].value.trim(),
            middleName: form.elements['middleName'].value.trim(),
            lastName: form.elements['lastName'].value.trim(),
            email: form.elements['email'].value.trim(),
            password: '', // Password field starts empty
            avatar: ''    // Avatar field starts empty
        };
        saveButton.disabled = true; // Disable the save button when modal is opened
    }

    // Function to check if any field has changed
    function checkForChanges() {
        const isChanged =
            form.elements['firstName'].value.trim() !== originalValues.firstName ||
            form.elements['middleName'].value.trim() !== originalValues.middleName ||
            form.elements['lastName'].value.trim() !== originalValues.lastName ||
            form.elements['email'].value.trim() !== originalValues.email ||
            form.elements['password'].value !== '' || // If password field is not empty
            form.elements['avatar'].files.length > 0; // If a file is selected

        saveButton.disabled = !isChanged;
    }

    // Initialize the modal using Bootstrap's JavaScript API
    const manageAccountModalElement = document.getElementById('manageAccountModal');
    const manageAccountModal = new bootstrap.Modal(manageAccountModalElement);

    // Event listener when the modal is shown
    manageAccountModalElement.addEventListener('shown.bs.modal', captureOriginalValues);

    // Listen for changes in any of the form fields
    form.addEventListener('input', checkForChanges);
    form.addEventListener('change', checkForChanges);

    // Handle the flash message display
    const flashMessage = document.getElementById('flashMessage');
    if (flashMessage) {
        // Show the flash message
        setTimeout(() => {
            flashMessage.style.opacity = '1';
        }, 100);

        // If it's an error message, reopen the modal
        <?php if ($flash_message_type === 'error'): ?>
            manageAccountModal.show();
        <?php endif; ?>

        // Hide the flash message after a few seconds
        setTimeout(() => {
            flashMessage.style.opacity = '0';
            setTimeout(() => {
                flashMessage.style.display = 'none';
            }, 500);
        }, 3000);
    }
});
</script>

</body>
</html>
