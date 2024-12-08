<?php
// Start the session at the very beginning
session_start();

// Include database connection
include('../db_conn.php');

// Get teacher_id from GET parameter and ensure it's an integer
if (isset($_GET['teacher_id'])) {
    $teacher_id = intval($_GET['teacher_id']);
} else {
    // Redirect to faculty-dtr.php if no teacher_id is provided
    header('Location: faculty-dtr.php');
    exit;
}

// Prepare the SQL statement to prevent SQL injection
$stmt = $conn->prepare("SELECT firstName, middleName, lastName FROM teacher_account WHERE teacher_id = ? AND status = 'Part-timer'");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows == 0) {
    // Teacher not found or not a part-timer, redirect to faculty-dtr.php with an error message
    $_SESSION['message'] = "Unauthorized access or teacher is not a Part-timer.";
    $_SESSION['message_type'] = "danger";
    header('Location: faculty-dtr.php');
    exit;
}

$teacher = $result->fetch_assoc();
$middleInitial = !empty($teacher['middleName']) ? substr($teacher['middleName'], 0, 1) . '.' : '';
$fullName = htmlspecialchars("{$teacher['firstName']} {$middleInitial} {$teacher['lastName']}");

// Close the statement
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Add the favicon -->
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <title>Part-timer Faculty Schedule</title>
    <?php include('../header.php'); ?>
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/admin-elements.css" />
    <style>
        /* Custom styles */
        .custom-dark-brown-btn {
            background-color: #281313;
            color: white;
            border: none;
            font-size: 1rem;
            padding: 8px 12px;
            display: flex;
            align-items: center;
        }
        .custom-dark-brown-btn i {
            font-size: 1.1em;
        }
        .custom-dark-brown-btn:hover {
            background-color: #facc15;
            color: #281313;
        }
        .btn-outline-custom {
            border-color: #281313;
            color: #281313;
        }
        .btn-outline-custom:hover {
            background-color: #281313;
            color: white;
        }
        .breadcrumbs-container {
            margin-top: 3%;
            margin-left: 0%;
            margin-bottom: 1.5%;
            padding-top: 2%;
            width: 98%;
        }
        .breadcrumbs {
            display: flex;
            align-items: center;
            height: 40px;
            background-color: #ffffff;
            border: 1px solid #ddd;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            padding: 8px 15px;
        }
        .breadcrumb-item a {
            text-decoration: none;
            color: #6c757d;
        }
        .breadcrumb-item.active {
            color: #6c757d;
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

        .btn-back:hover {
            color: #facc15;
        }
        
        /* Responsive styles for small screens */
        @media (max-width: 767px) { 
            .custom-dark-brown-btn {
                width: 100%;
                margin-bottom: 1rem;
            }
            .container.bg-white {
                max-width: 90%;
            }
            .pagination-controls {
                flex-wrap: nowrap;
            }
            .col-md-6.text-end label {
                display: flex;
                align-items: center;
                gap: 5px;
                margin-top: 20px;
            }
            #tableSearch {
                max-width: 220px;
                flex: 1;
            }
            .breadcrumbs {
                margin-top: 15%;
                padding-top: 6%;
                margin-bottom:8%;
            }
        }

        /* Additional styles for entries and search */
        .entries-search-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        .entries-search-container .form-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .pagination-controls {
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <?php include('navigation.php'); ?>
     <!-- Breadcrumbs -->
     <div class="breadcrumbs-container">
        <nav aria-label="breadcrumb" class="breadcrumbs ms-4 bg-white border shadow rounded py-2 px-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="admin-dashboard.php" class="text-muted text-decoration-none">
                        <i class="fa-solid fa-house"></i> Home
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="faculty-dtr.php" class="text-muted text-decoration-none">
                        Faculty DTR
                    </a>
                </li>
                <li class="breadcrumb-item active text-muted" aria-current="page">Faculty Schedule</li>
            </ol>
        </nav>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="classTitle fs-4 fw-bold ms-5 mb-2">Part-timer Schedule for <?php echo $fullName; ?></p>
        <a href="faculty-dtr.php" class="btn btn-back">
            <i class="fa-solid fa-circle-chevron-left"></i>
        </a>
    </div>

    <div class="mx-auto mb-4" style="height: 2px; background-color: #facc15; width:95%;"></div>
    
    <div class="container bg-white rounded border shadow mb-5 p-4">
        <!-- Display messages if any -->
        <div id="messageContainer">
            <?php if (isset($_SESSION['message'])): ?>
                <div class='alert alert-<?= htmlspecialchars($_SESSION['message_type']); ?> alert-dismissible fade show' role='alert' id="autoHideMessage">
                    <?= htmlspecialchars($_SESSION['message']); ?>
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>
                <?php 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            <?php endif; ?>
        </div>

        <!-- Add Schedule Button -->
        <div class="d-flex justify-content-end mb-4">
            <button class="btn custom-dark-brown-btn" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                <i class="bi bi-plus-lg me-1"></i> Add Schedule
            </button>
        </div>

        <!-- Show Entries and Search -->
        <div class="entries-search-container">
            <div class="form-group">
                <label for="entriesSelect">Show 
                    <select class="form-select d-inline w-auto" name="entries" id="entriesSelect">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select> entries
                </label>
            </div>
            <div class="form-group">
                <label for="tableSearch">Search: 
                    <input type="search" class="form-control d-inline w-auto" id="tableSearch" placeholder="Search schedules">
                </label>
            </div>
        </div>

        <!-- Schedule Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>Day</th>
                        <th>Subject</th>
                        <th>Class</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php
                    // Fetch schedules for the teacher
                    $schedule_query = "SELECT teacher_sched_id, day_of_week, subject_code, class_title, time_start, time_end 
                                       FROM teacher_schedule 
                                       WHERE teacher_id = $teacher_id 
                                       ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday','Sunday'), time_start ASC";
                    $schedule_result = mysqli_query($conn, $schedule_query);

                    if (!$schedule_result) {
                        echo "<tr><td colspan='6' class='text-center text-danger'>Error fetching schedules: " . mysqli_error($conn) . "</td></tr>";
                    } else {
                        if (mysqli_num_rows($schedule_result) == 0) {
                            echo "<tr id='noResultRow'><td colspan='6' class='text-center'>No schedules found.</td></tr>";
                        } else {
                            while ($schedule = mysqli_fetch_assoc($schedule_result)) {
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($schedule['day_of_week']); ?></td>
                                    <td><?= htmlspecialchars($schedule['subject_code']); ?></td>
                                    <td><?= htmlspecialchars($schedule['class_title']); ?></td>
                                    <td><?= htmlspecialchars(date('H:i', strtotime($schedule['time_start']))); ?></td>
                                    <td><?= htmlspecialchars(date('H:i', strtotime($schedule['time_end']))); ?></td>
                                    <td class="text-center">
                                        <button class="btn btn-outline-custom btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editScheduleModal<?= $schedule['teacher_sched_id']; ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <form method="POST" action="actions/delete-faculty-schedule.php" style="display:inline-block;">
                                            <input type="hidden" name="teacher_sched_id" value="<?= $schedule['teacher_sched_id']; ?>">
                                            <input type="hidden" name="teacher_id" value="<?= $teacher_id; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this schedule?');">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Edit Schedule Modal -->
                                <div class="modal fade" id="editScheduleModal<?= $schedule['teacher_sched_id']; ?>" tabindex="-1" aria-labelledby="editScheduleModalLabel<?= $schedule['teacher_sched_id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form method="POST" action="actions/edit-faculty-schedule.php">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editScheduleModalLabel<?= $schedule['teacher_sched_id']; ?>">Edit Schedule</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label='Close'></button>
                                                </div>
                                                <div class="modal-body">
                                                    <!-- Form fields -->
                                                    <input type="hidden" name="teacher_sched_id" value="<?= $schedule['teacher_sched_id']; ?>">
                                                    <input type="hidden" name="teacher_id" value="<?= $teacher_id; ?>">

                                                    <div class="mb-3">
                                                        <label class="form-label">Day</label>
                                                        <select class="form-select" name="day_of_week" required>
                                                            <option value="">Select Day</option>
                                                            <?php
                                                            $days = [ 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                                            foreach ($days as $day) {
                                                                $selected = ($schedule['day_of_week'] == $day) ? 'selected' : '';
                                                                echo "<option value='" . htmlspecialchars($day) . "' $selected>" . htmlspecialchars($day) . "</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Subject</label>
                                                        <select class="form-select" name="subject_code" required>
                                                            <option value="">Select Subject</option>
                                                            <?php
                                                            $subject_query = "SELECT code, subject_title FROM subject_list ORDER BY code ASC";
                                                            $subject_result = mysqli_query($conn, $subject_query);
                                                            while ($subject = mysqli_fetch_assoc($subject_result)) {
                                                                $selected = ($schedule['subject_code'] == $subject['code']) ? 'selected' : '';
                                                                echo "<option value='" . htmlspecialchars($subject['code']) . "' $selected>" . htmlspecialchars($subject['code']) . "</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Class</label>
                                                        <select class="form-select" name="class_title" required>
                                                            <option value="">Select Class</option>
                                                            <?php
                                                            $class_query = "SELECT grade_level, section FROM class_list ORDER BY grade_level DESC, section ASC";
                                                            $class_result = mysqli_query($conn, $class_query);
                                                            while ($class = mysqli_fetch_assoc($class_result)) {
                                                                $class_display = 'Grade ' . htmlspecialchars($class['grade_level']) . ' - ' . htmlspecialchars($class['section']);
                                                                $selected = ($schedule['class_title'] == $class_display) ? 'selected' : '';
                                                                echo "<option value='" . htmlspecialchars($class_display) . "' $selected>" . $class_display . "</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>

                                                    <!-- Start Time -->
                                                    <div class="mb-3">
                                                        <label class="form-label">Start Time</label>
                                                        <!-- 24-hour format without AM/PM and without seconds -->
                                                        <input type="time" class="form-control" name="time_start" value="<?= htmlspecialchars(date('H:i', strtotime($schedule['time_start']))); ?>" step="60" required>
                                                    </div>

                                                    <!-- End Time -->
                                                    <div class="mb-3">
                                                        <label class="form-label">End Time</label>
                                                        <!-- 24-hour format without AM/PM and without seconds -->
                                                        <input type="time" class="form-control" name="time_end" value="<?= htmlspecialchars(date('H:i', strtotime($schedule['time_end']))); ?>" step="60" required>
                                                    </div>

                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" class="btn custom-dark-brown-btn">Save Changes</button>
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Information -->
        <div class="d-flex justify-content-between align-items-center mt-3 pagination-controls">
            <p class="mb-0" id="pageInfo">Showing 1 to <?= min(mysqli_num_rows($schedule_result), 10); ?> of <?= mysqli_num_rows($schedule_result); ?> entries</p>
            <div class="d-flex">
                <button id="prevBtn" class="btn btn-outline-primary me-2" disabled>Previous</button>
                <button id="nextBtn" class="btn btn-outline-primary">Next</button>
            </div>
        </div>
    </div>
    
    <!-- Add Schedule Modal -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="actions/add-faculty-schedule.php">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addScheduleModalLabel">Add Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label='Close'></button>
                    </div>
                    <div class="modal-body">
                        <!-- Hidden field to pass teacher_id -->
                        <input type="hidden" name="teacher_id" value="<?= $teacher_id; ?>">

                        <!-- Day of the Week -->
                        <div class="mb-3">
                            <label class="form-label">Day</label>
                            <select class="form-select" name="day_of_week" required>
                                <option value="">Select Day</option>
                                <?php
                                $days = [ 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday','Sunday'];
                                foreach ($days as $day) {
                                    echo "<option value='" . htmlspecialchars($day) . "'>" . htmlspecialchars($day) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Subject Selection -->
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <select class="form-select" name="subject_code" required>
                                <option value="">Select Subject</option>
                                <?php
                                $subject_query = "SELECT code, subject_title FROM subject_list ORDER BY code ASC";
                                $subject_result = mysqli_query($conn, $subject_query);
                                if ($subject_result && mysqli_num_rows($subject_result) > 0) {
                                    while ($subject = mysqli_fetch_assoc($subject_result)) {
                                        echo "<option value='" . htmlspecialchars($subject['code']) . "'>" . htmlspecialchars($subject['code']) . "</option>";
                                    }
                                } else {
                                    echo "<option value='' disabled>No subjects available</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Class Selection -->
                        <div class="mb-3">
                            <label class="form-label">Class</label>
                            <select class="form-select" name="class_title" required>
                                <option value="">Select Class</option>
                                <?php
                                $class_query = "SELECT grade_level, section FROM class_list ORDER BY grade_level DESC, section ASC";
                                $class_result = mysqli_query($conn, $class_query);
                                if ($class_result && mysqli_num_rows($class_result) > 0) {
                                    while ($class = mysqli_fetch_assoc($class_result)) {
                                        $class_display = 'Grade ' . htmlspecialchars($class['grade_level']) . ' - ' . htmlspecialchars($class['section']);
                                        echo "<option value='" . $class_display . "'>" . $class_display . "</option>";
                                    }
                                } else {
                                    echo "<option value='' disabled>No classes available</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Start Time -->
                        <div class="mb-3">
                            <label class="form-label">Start Time</label>
                            <!-- 24-hour format without AM/PM and without seconds -->
                            <input type="time" class="form-control" name="time_start" step="60" required>
                        </div>

                        <!-- End Time -->
                        <div class="mb-3">
                            <label class="form-label">End Time</label>
                            <!-- 24-hour format without AM/PM and without seconds -->
                            <input type="time" class="form-control" name="time_end" step="60" required>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn custom-dark-brown-btn">Add Schedule</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <?php include('footer.php'); ?>
</body>
</html>

<!-- JavaScript for Table Functionality -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchBox = document.getElementById('tableSearch');
        const tableBody = document.getElementById('tableBody');
        const entriesSelect = document.getElementById('entriesSelect');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const pageInfo = document.getElementById('pageInfo');
        let noResultRow = document.getElementById('noResultRow');

        let currentPage = 1;
        let rowsPerPage = parseInt(entriesSelect.value);
        let rows = Array.from(tableBody.querySelectorAll('tr')).filter(row => row.id !== 'noResultRow');
        let filteredRows = rows;

        let totalRows = rows.length;

        function filterTable(searchQuery) {
            if (searchQuery.trim() === '') {
                filteredRows = rows;
            } else {
                filteredRows = rows.filter(function(row) {
                    // Check if any cell in the row includes the search query
                    return Array.from(row.cells).some(cell => cell.textContent.toLowerCase().includes(searchQuery));
                });
            }

            // Hide all rows
            rows.forEach(row => row.style.display = 'none');
            // Show filtered rows
            filteredRows.forEach(row => row.style.display = '');

            // Handle no results
            if (filteredRows.length === 0) {
                if (noResultRow) {
                    noResultRow.style.display = '';
                } else {
                    // Create noResultRow if it doesn't exist
                    noResultRow = document.createElement('tr');
                    noResultRow.id = 'noResultRow';
                    const td = document.createElement('td');
                    td.colSpan = 6;
                    td.className = 'text-center';
                    td.textContent = 'No result.';
                    noResultRow.appendChild(td);
                    tableBody.appendChild(noResultRow);
                }
            } else {
                if (noResultRow) {
                    noResultRow.style.display = 'none';
                }
            }

            totalRows = filteredRows.length;
            currentPage = 1;
            paginateTable();
        }

        function paginateTable() {
            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            // First hide all filtered rows
            filteredRows.forEach((row, index) => {
                row.style.display = (index >= start && index < end) ? '' : 'none';
            });

            // Update page info
            const showingStart = filteredRows.length > 0 ? start + 1 : 0;
            const showingEnd = Math.min(end, totalRows);
            pageInfo.textContent = `Showing ${showingStart} to ${showingEnd} of ${totalRows} entries`;

            // Enable/disable buttons
            prevBtn.disabled = currentPage === 1;
            nextBtn.disabled = currentPage >= Math.ceil(totalRows / rowsPerPage);
        }

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

        searchBox.addEventListener('keyup', function () {
            filterTable(searchBox.value.toLowerCase());
        });

        // Initial pagination
        paginateTable();
    });

    function redirectToSingleDTR(teacherId) {
        window.location.href = `single-faculty-dtr.php?teacher_id=${teacherId}`;
    }

    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            const message = document.getElementById('autoHideMessage');
            if (message) {
                message.classList.remove('show'); 
            }
        }, 5000); // 5000ms = 5 seconds
    });
</script>
