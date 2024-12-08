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
        /* Responsive styles for small screens */
        media (max-width: 767px) { 
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

    <p class="classTitle fs-4 fw-bold ms-5 mb-2">Part-timer Schedule for <?php echo $fullName; ?></p>
    <div class="mx-auto mb-4" style="height: 2px; background-color: #facc15; width:95%;"></div>

    <div class="container bg-white rounded border shadow mb-5 p-4">
        <!-- Display messages if any -->
        <div id="messageContainer">
            <?php if (isset($_SESSION['message'])): ?>
                <div class='alert alert-<?= htmlspecialchars($_SESSION['message_type']); ?> alert-dismissible fade show' role='alert'>
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
        <div class="d-flex justify-content-end mb-3">
            <button class="btn custom-dark-brown-btn" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
                <i class="bi bi-plus-lg me-1"></i> Add Schedule
            </button>
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
                        <th class="text-start">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch schedules for the teacher
                    $schedule_query = "SELECT teacher_sched_id, day_of_week, subject_code, class_title, time_start, time_end 
                                       FROM teacher_schedule 
                                       WHERE teacher_id = $teacher_id 
                                       ORDER BY FIELD(day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), time_start ASC";
                    $schedule_result = mysqli_query($conn, $schedule_query);

                    if (!$schedule_result) {
                        echo "<tr><td colspan='6' class='text-center text-danger'>Error fetching schedules: " . mysqli_error($conn) . "</td></tr>";
                    } else {
                        if (mysqli_num_rows($schedule_result) == 0) {
                            echo "<tr><td colspan='6' class='text-center'>No schedules found.</td></tr>";
                        } else {
                            while ($schedule = mysqli_fetch_assoc($schedule_result)) {
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($schedule['day_of_week']); ?></td>
                                    <td><?= htmlspecialchars($schedule['subject_code']); ?></td>
                                    <td><?= htmlspecialchars($schedule['class_title']); ?></td>
                                    <td><?= htmlspecialchars(date('h:i A', strtotime($schedule['time_start']))); ?></td>
                                    <td><?= htmlspecialchars(date('h:i A', strtotime($schedule['time_end']))); ?></td>
                                    <td class="text-start">
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
                                                    <h5 class="modal-title">Edit Schedule</h5>
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
                                                            $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                                            foreach ($days as $day) {
                                                                $selected = ($schedule['day_of_week'] == $day) ? 'selected' : '';
                                                                echo "<option value='$day' $selected>$day</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Subject</label>
                                                        <select class="form-select" name="subject_code" required>
                                                            <option value="">Select Subject</option>
                                                            <?php
                                                            $subject_query = "SELECT subject_code, subject_title FROM subject_list ORDER BY subject_title ASC";
                                                            $subject_result = mysqli_query($conn, $subject_query);
                                                            while ($subject = mysqli_fetch_assoc($subject_result)) {
                                                                $selected = ($schedule['subject_code'] == $subject['subject_code']) ? 'selected' : '';
                                                                echo "<option value='" . htmlspecialchars($subject['subject_code']) . "' $selected>" . htmlspecialchars($subject['subject_title']) . "</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Class</label>
                                                        <select class="form-select" name="class_title" required>
                                                            <option value="">Select Class</option>
                                                            <?php
                                                            $class_query = "SELECT class_title FROM class_list ORDER BY class_title ASC";
                                                            $class_result = mysqli_query($conn, $class_query);
                                                            while ($class = mysqli_fetch_assoc($class_result)) {
                                                                $selected = ($schedule['class_title'] == $class['class_title']) ? 'selected' : '';
                                                                echo "<option value='" . htmlspecialchars($class['class_title']) . "' $selected>" . htmlspecialchars($class['class_title']) . "</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Start Time</label>
                                                        <input type="time" class="form-control" name="time_start" value="<?= htmlspecialchars($schedule['time_start']); ?>" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">End Time</label>
                                                        <input type="time" class="form-control" name="time_end" value="<?= htmlspecialchars($schedule['time_end']); ?>" required>
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
    </div>

    <!-- Add Schedule Modal -->
    <div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="actions/add-faculty-schedule.php">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label='Close'></button>
                    </div>
                    <div class="modal-body">
                        <!-- Form fields -->
                        <input type="hidden" name="teacher_id" value="<?= $teacher_id; ?>">

                        <div class="mb-3">
                            <label class="form-label">Day</label>
                            <select class="form-select" name="day_of_week" required>
                                <option value="">Select Day</option>
                                <?php
                                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                foreach ($days as $day) {
                                    echo "<option value='$day'>$day</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <select class="form-select" name="subject_code" required>
                                <option value="">Select Subject</option>
                                <?php
                                $subject_query = "SELECT subject_code, subject_title FROM subject_list ORDER BY subject_title ASC";
                                $subject_result = mysqli_query($conn, $subject_query);
                                while ($subject = mysqli_fetch_assoc($subject_result)) {
                                    echo "<option value='" . htmlspecialchars($subject['subject_code']) . "'>" . htmlspecialchars($subject['subject_title']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Class</label>
                            <select class="form-select" name="class_title" required>
                                <option value="">Select Class</option>
                                <?php
                                $class_query = "SELECT class_title FROM class_list ORDER BY class_title ASC";
                                $class_result = mysqli_query($conn, $class_query);
                                while ($class = mysqli_fetch_assoc($class_result)) {
                                    echo "<option value='" . htmlspecialchars($class['class_title']) . "'>" . htmlspecialchars($class['class_title']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Start Time</label>
                            <input type="time" class="form-control" name="time_start" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">End Time</label>
                            <input type="time" class="form-control" name="time_end" required>
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
