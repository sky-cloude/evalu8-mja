<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes</title>
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <?php include('../header.php'); ?>
    <!-- CSS files -->
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/admin-elements.css" />

    <style>
        .breadcrumbs-container {
            margin-top: 3%;
            margin-left: 0%;
            margin-bottom: 1.5%;
            padding-top: 2%;
            width: 98%;
        }

        .btn-manage-subjects, .btn-manage-students {
            background-color: #281313;
            color: white;
            height: 30px;
            margin-right: 10px; /* Add some space between the two buttons */
            align-items: center; /* Center items vertically */
        }

        /* Remove hover effect */
        .btn-manage-students:hover, .btn-manage-subjects:hover {
            background-color: #facc15; /* Keep the same background on hover */
            color: black;
        }

        .btn-back {
            background: none;
            border: none;
            padding: 0;
            color: #281313;
            margin-right: 35px;
        }

        .btn-back i {
            font-size: 2.5rem; /* Adjust the size of the icon */
        }

        .btn-back:hover {
            color: #facc15;
        }

        .flash-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            min-width: 200px;
            max-width: 400px;
            padding: 15px 20px;
            background-color: #38c172; /* Success green color */
            color: white;
            border-radius: 5px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            opacity: 0; /* Initially hidden */
            transition: opacity 0.5s ease, transform 0.5s ease;
            transform: translateY(-20px); /* Start with a slight upward offset */
        }

        .flash-message.show {
            opacity: 1;
            transform: translateY(0); /* Slide into view */
        }

        .flash-message .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
        }

        .flash-message .close-btn:hover {
            color: #ffffffb3; /* Lighter white on hover */
        }

        /* small devices */
        @media (max-width: 767px) { 
            .breadcrumbs {
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
    include('../db_conn.php');

    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $acad_year_id = isset($_GET['acad_year_id']) ? intval($_GET['acad_year_id']) : 0;
    $year = isset($_GET['year']) ? htmlspecialchars($_GET['year']) : 'N/A';
    $quarter = isset($_GET['quarter']) ? htmlspecialchars($_GET['quarter']) : 'N/A';

    if ($acad_year_id == 0) {
        die("Invalid or missing academic year ID.");
    }

    // Updated Query: Removed the condition that filters out classes with zero subjects and students
    $query = "
    SELECT cl.class_id, cl.grade_level, cl.section AS class_name, el.evaluation_id,
            (SELECT COUNT(DISTINCT ste.subject_id) FROM subject_to_eval ste WHERE ste.evaluation_id = el.evaluation_id) AS subject_count,
            (SELECT COUNT(DISTINCT ser.student_id) FROM students_eval_restriction ser WHERE ser.evaluation_id = el.evaluation_id) AS student_count
        FROM class_list cl
        JOIN evaluation_list el ON cl.class_id = el.class_id
        JOIN academic_year ay ON el.acad_year_id = ay.acad_year_id
        WHERE el.acad_year_id = $acad_year_id AND ay.quarter = '$quarter'
        ORDER BY CAST(SUBSTRING_INDEX(cl.grade_level, ' ', -1) AS UNSIGNED) ASC, cl.section ASC;
    ";


    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }
    ?>

    <!-- Breadcrumbs -->
    <div class="breadcrumbs-container">
        <nav aria-label="breadcrumb" class="breadcrumbs ms-4 bg-white border shadow rounded py-2 px-3" style="height: 40px; align-items: center; display: flex;">
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
                <li class="breadcrumb-item active text-muted" aria-current="page">
                    <a href="manage-class.php?acad_year_id=<?php echo $acad_year_id; ?>&year=<?php echo $year; ?>&quarter=<?php echo $quarter; ?>" class="text-muted text-decoration-none">Manage Class</a>
                </li>
            </ol>
        </nav>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 ms-5">
        <p class="classTitle fs-4 fw-bold mb-0">
            Manage Classes for A.Y. <?php echo $year; ?> | Quarter <?php echo $quarter; ?>
        </p>
        <a href="admin-eval-controls.php" class="btn btn-back">
            <i class="fa-solid fa-circle-chevron-left"></i>
        </a>
    </div>

    <div class="mx-auto mb-4" style="height: 2px; background-color: #facc15; width:95%;"></div>

    <div class="d-flex justify-content-end mb-4" style="width: 95%; margin-left: auto; margin-right: auto;">
        <button class="btn btn-manage-subjects btn-sm" data-bs-toggle="modal" data-bs-target="#addClassModal">
            <i class="fa-solid fa-plus"></i> Add Class
        </button>
    </div>


    <div class="container bg-white rounded border shadow mb-5" style="width: 95%;">
        <div class="container mt-4">
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
                    <input type="Search" id="searchInput" class="form-control form-control-sm w-auto" placeholder="">
                </div>
            </div>
        </div>

        <div class="table-responsive mb-3" style="margin-top:5px;">
            <table class="table table-striped table-hover table-bordered custom-table-border">
                <thead class="text-center">
                    <tr>
                        <th>#</th>
                        <th>Class Name</th>
                        <th>No. of Class Subjects</th>
                        <th>No. of Students</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody class="text-center" id="tableBody" data-empty="<?php echo (mysqli_num_rows($result) === 0) ? 'true' : 'false'; ?>">
                    <?php
                    if (mysqli_num_rows($result) > 0) {
                        $index = 1;
                        while ($row = mysqli_fetch_assoc($result)) {
                            // Prepare class name
                            $className = "Grade {$row['grade_level']} - {$row['class_name']}";
                            ?>
                            <tr>
                                <td><?php echo $index; ?></td>
                                <td><?php echo htmlspecialchars($className); ?></td>
                                <td><?php echo $row['subject_count']; ?></td>
                                <td><?php echo $row['student_count']; ?></td>
                                <td>
                                    <a href="manage-subjects.php?evaluation_id=<?php echo $row['evaluation_id']; ?>&class_id=<?php echo $row['class_id']; ?>&acad_year_id=<?php echo $acad_year_id; ?>&year=<?php echo $year; ?>&quarter=<?php echo $quarter; ?>" class="btn btn-manage-subjects btn-sm">
                                        <i class="fa-solid fa-book"></i> Manage Subjects
                                    </a>
                                    <a href="manage-student.php?evaluation_id=<?php echo $row['evaluation_id']; ?>&class_id=<?php echo $row['class_id']; ?>&acad_year_id=<?php echo $acad_year_id; ?>&year=<?php echo $year; ?>&quarter=<?php echo $quarter; ?>" class="btn btn-manage-students btn-sm">
                                        <i class="bi bi-backpack-fill"></i> Manage Students
                                    </a>
                                    <button type="button" class="btn btn-manage-subjects btn-sm" data-bs-toggle="modal" data-bs-target="#removeClassModal" data-class-id="<?php echo $row['class_id']; ?>">
                                        <i class="fa-solid fa-trash"></i> Remove Class
                                    </button>
                                </td>
                            </tr>
                            <?php
                            $index++;
                        }
                    } else {
                        echo "<tr><td colspan='5'>No classes found for this academic year.</td></tr>";
                    }
                    ?>
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
    </div>

    <!-- Modal for Adding Class -->
    <div class="modal fade" id="addClassModal" tabindex="-1" aria-labelledby="addClassModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="addClassForm" action="actions/acad-class.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addClassModalLabel">Add Classes to the Academic Year</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Hidden input field to pass acad_year_id -->
                        <input type="hidden" name="acad_year_id" value="<?php echo $acad_year_id; ?>">
                        
                        <div class="mb-3">
                            <label for="numClasses" class="form-label">How many classes do you want to add?</label>
                            <div class="input-group">
                                <input type="number" id="numClasses" class="form-control" min="1" max="10" value="1" required>
                                <button type="button" id="proceedBtn" class="btn btn-primary">Proceed</button>
                            </div>
                        </div>
                        <div id="classSelectors">
                            <!-- Dynamic combo boxes will be added here -->
                        </div>
                        <div id="error-message" class="text-danger" style="display:none;">
                            <!-- Error message will be displayed here -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" id="addButton" class="btn btn-primary" disabled>Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal for Removing Class -->
    <div class="modal fade" id="removeClassModal" tabindex="-1" aria-labelledby="removeClassModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="removeClassModalLabel">Confirm Removal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to remove this class from the evaluation list?
                </div>
                <div class="modal-footer">
                    <form id="removeClassForm" action="actions/remove-acad-class.php" method="POST">
                        <input type="hidden" name="class_id" id="modalClassId">
                        <input type="hidden" name="acad_year_id" value="<?php echo $acad_year_id; ?>">
                        <input type="hidden" name="year" value="<?php echo $year; ?>">
                        <input type="hidden" name="quarter" value="<?php echo $quarter; ?>">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Remove</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const tableBody = document.getElementById('tableBody');
        const dataEmpty = tableBody.dataset.empty === 'true';
        const addButton = document.getElementById('addButton');
        const classSelectorsContainer = document.getElementById('classSelectors');
        const proceedButton = document.getElementById('proceedBtn');
        const addClassForm = document.getElementById('addClassForm');
        const removeClassModal = document.getElementById('removeClassModal');
        const removeClassForm = document.getElementById('removeClassForm');

        if (!dataEmpty) {
            let currentPage = 1;
            let rowsPerPage = parseInt(document.getElementById('entriesCount').value);
            const allRows = Array.from(tableBody.getElementsByTagName('tr'));
            const pageInfo = document.getElementById('pageInfo');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');

            let filteredRows = allRows;

            // Disable Add button initially
            addButton.disabled = true;

            // Disable Add button until Proceed is clicked and classes are selected
            let proceedClicked = false;

            // Function to display the table rows based on the current page and rows per page
            function displayTableRows() {
                let start = (currentPage - 1) * rowsPerPage;
                let end = start + rowsPerPage;

                // Hide all rows first
                allRows.forEach((row) => {
                    row.style.display = 'none';
                });

                // Show rows only within the current page range
                filteredRows.slice(start, end).forEach((row, index) => {
                    row.style.display = '';
                    // Update row numbers
                    const rowNumberCell = row.getElementsByTagName('td')[0];
                    rowNumberCell.textContent = start + index + 1;
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
                filteredRows = allRows.filter(function (row) {
                    const cells = row.getElementsByTagName('td');
                    let match = false;

                    // Check each cell (except the index and action cells) for a match
                    for (let j = 1; j < cells.length - 1; j++) { 
                        if (cells[j].textContent.toLowerCase().includes(input)) {
                            match = true;
                            break;
                        }
                    }

                    return match;
                });

                // Reset to the first page and display rows
                currentPage = 1;
                displayTableRows();
            });

            // Initial load of table rows
            displayTableRows();
        } else {
            // Hide pagination controls if no data
            const paginationContainers = document.querySelectorAll('.container.mt-3.mb-3, #pageInfo, #prevBtn, #nextBtn');
            paginationContainers.forEach(container => {
                if (container) {
                    container.style.display = 'none';
                }
            });
        }

        // Flash message functionality
        function showFlashMessage(type, message) {
            // Remove any existing flash messages
            const existingFlashMessage = document.getElementById('flashMessage');
            if (existingFlashMessage) {
                existingFlashMessage.remove();
            }

            const flashMessageContainer = document.createElement('div');
            flashMessageContainer.className = `flash-message bg-${type}`;
            flashMessageContainer.id = 'flashMessage';

            flashMessageContainer.innerHTML = `
                <span id="flashMessageText">${message}</span>
                <button class="close-btn" onclick="document.getElementById('flashMessage').remove();">&times;</button>
            `;
            document.body.appendChild(flashMessageContainer);

            // Add the show class to trigger the animation
            setTimeout(() => {
                flashMessageContainer.classList.add('show');
            }, 10); // Small delay to allow the initial setup

            // Automatically remove the flash message after 5 seconds
            setTimeout(() => {
                flashMessageContainer.classList.remove('show');
                setTimeout(() => {
                    flashMessageContainer.remove();
                }, 500); // Wait for the transition to finish before removing
            }, 5000);
        }

        // Function to check if all class selectors have a value selected
        function checkSelections() {
            const selectors = classSelectorsContainer.querySelectorAll('select');
            let allSelected = true;
            selectors.forEach(select => {
                if (!select.value) {
                    allSelected = false;
                }
            });
            addButton.disabled = !allSelected || !proceedClicked;
        }

        // Event listener for Proceed button in Add Class Modal
        proceedButton.addEventListener('click', function () {
            const numClasses = parseInt(document.getElementById('numClasses').value);
            if (isNaN(numClasses) || numClasses < 1 || numClasses > 10) {
                alert('Please enter a valid number of classes between 1 and 10.');
                return;
            }
            classSelectorsContainer.innerHTML = ''; // Clear previous inputs
            proceedClicked = true;

            for (let i = 1; i <= numClasses; i++) {
                const selectElement = document.createElement('select');
                selectElement.classList.add('form-select', 'mb-3');
                selectElement.name = 'class_id_' + i;
                selectElement.required = true;

                // Add placeholder option
                const placeholderOption = document.createElement('option');
                placeholderOption.textContent = 'Select a class';
                placeholderOption.value = '';
                placeholderOption.disabled = true;
                placeholderOption.selected = true;
                selectElement.appendChild(placeholderOption);

                <?php
                // Modify the SQL query to extract the numeric part of grade_level for proper sorting
                $class_query = "
                    SELECT class_id, grade_level, section 
                    FROM class_list 
                    ORDER BY CAST(SUBSTRING_INDEX(grade_level, ' ', -1) AS UNSIGNED) ASC, section ASC
                ";
                $class_result = mysqli_query($conn, $class_query);
                $options = '';
                while ($class_row = mysqli_fetch_assoc($class_result)) {
                    $options .= "<option value='{$class_row['class_id']}'>{$class_row['grade_level']} - {$class_row['section']}</option>";
                }
                ?>
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = `<?php echo $options; ?>`;


                // Append each option to the select element
                while (tempDiv.firstChild) {
                    selectElement.appendChild(tempDiv.firstChild);
                }

                // Append the select element to the container
                classSelectorsContainer.appendChild(selectElement);

                // Add event listener to the select element to check selections
                selectElement.addEventListener('change', checkSelections);
            }

            // Initially disable the Add button after Proceed is clicked until all classes are selected
            addButton.disabled = true;
        });

        // Event listener for the form submission (Add Class)
        addClassForm.addEventListener('submit', function (e) {
            e.preventDefault(); // Prevent the default form submission

            const formData = new FormData(addClassForm);

            fetch(addClassForm.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json()) // Parse JSON response
            .then(data => {
                if (data.success) {
                    // Show the flash message
                    showFlashMessage('success', data.message);
                    // Reload or update the page content
                    setTimeout(() => location.reload(), 1000); // Reload after 1 second
                } else {
                    // If there are duplicate classes, highlight them or inform the user
                    if (data.duplicate_classes && data.duplicate_classes.length > 0) {
                        showFlashMessage('danger', data.message);
                    } else {
                        showFlashMessage('danger', data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showFlashMessage('danger', 'An error occurred. Please try again.');
            });
        });

        // Remove class functionality
        removeClassModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Button that triggered the modal
            const classId = button.getAttribute('data-class-id'); // Extract class ID from data-* attribute

            // Update the modal's hidden input with the class ID
            const modalClassIdInput = document.getElementById('modalClassId');
            modalClassIdInput.value = classId;
        });

        removeClassForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(removeClassForm);

            fetch('actions/remove-acad-class.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success flash message
                    showFlashMessage('success', data.message);
                    // Remove the deleted row from the table
                    const classId = formData.get('class_id');
                    const rowToRemove = document.querySelector(`button[data-class-id="${classId}"]`).closest('tr');
                    if (rowToRemove) {
                        rowToRemove.remove();
                    }
                    // Close the modal
                    const removeClassModalInstance = bootstrap.Modal.getInstance(removeClassModal);
                    removeClassModalInstance.hide();
                } else {
                    // Show error flash message
                    showFlashMessage('danger', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFlashMessage('danger', 'An error occurred while processing your request.');
            });
        });

    });
    </script>


</body>
</html>
