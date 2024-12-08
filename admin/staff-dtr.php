<!-- staff-dtr.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff DTR</title>
    <?php include('../header.php'); ?>
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/admin-elements.css" />
    <style>
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
        .print-icon {
            font-size: 1.2em;
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
    </style>
</head>

<body>
    <?php 
    session_start(); 
    include('../db_conn.php');
    include('navigation.php'); 
    ?>

    <!-- Breadcrumbs -->
    <div class="breadcrumbs-container">
        <nav aria-label="breadcrumb" class="breadcrumbs ms-4 bg-white border shadow rounded py-2 px-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="admin-dashboard.php" class="text-muted text-decoration-none">
                        <i class="fa-solid fa-house"></i> Home
                    </a>
                </li>
                <li class="breadcrumb-item active text-muted" aria-current="page">Staff DTR</li>
            </ol>
        </nav>
    </div>
    
    <p class="classTitle fs-4 fw-bold ms-5 mb-2">Staff DTR</p>
    <div class="mx-auto mb-4" style="height: 2px; background-color: #facc15; width:95%;"></div>
    
    <div class="container bg-white rounded border shadow mb-5 p-4">
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

        <div class="d-flex justify-content-end mb-3">
            <button id="batchPrintBtn" class="btn custom-dark-brown-btn me-3" onclick="window.location.href='breaktime-staff.php'">
                <i class="bi bi-cup-hot"></i> Break Time
            </button>
            <button id="batchPrintBtn" class="btn custom-dark-brown-btn" onclick="window.location.href='batch-staff-dtr.php'">
                <i class="bi bi-printer me-1"></i> Batch Print
            </button>
        </div>

        <hr class="bg-dark" style="border-width: 1px; margin-top: 0; margin-bottom: 20px;">

        <!-- Show Entries and Search -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label>Show 
                    <select class="form-select d-inline w-auto" name="entries" id="entriesSelect">
                        <option value="3">3</option>
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                    </select> entries
                </label>
            </div>
            <div class="col-md-6 text-end">
                <label>Search: <input type="search" class="form-control d-inline w-auto" id="tableSearch"></label>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Employee ID</th>
                        <th>Break Time</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php
                    // Fetch staff data with break time details
                    $query = "SELECT sa.staff_id, sa.firstName, sa.middleName, sa.lastName, sa.staff_role, sa.emp_code, 
                                     sa.break_time_id, bt.break_name, bt.start_break, bt.end_break
                              FROM staff_account sa
                              LEFT JOIN break_time_schedule bt ON sa.break_time_id = bt.break_time_id
                              ORDER BY sa.lastName ASC";
                    $result = mysqli_query($conn, $query);
                    if (!$result) {
                        echo "<tr><td colspan='6' class='text-center text-danger'>Error fetching data: " . mysqli_error($conn) . "</td></tr>";
                    } else {
                        $index = 1;
                        $totalEntries = mysqli_num_rows($result);

                        while ($row = mysqli_fetch_assoc($result)) {
                            $middleInitial = !empty($row['middleName']) ? substr($row['middleName'], 0, 1) . '.' : '';
                            $fullName = htmlspecialchars("{$row['firstName']} {$middleInitial} {$row['lastName']}");
                            $employeeID = !empty($row['emp_code']) 
                                ? htmlspecialchars($row['emp_code']) 
                                : '<span class="text-muted"><i>Assign the Employee ID to match the staff\'s ID in the biometric device in Staff List page.</i></span>';
                            $staffRole = htmlspecialchars($row['staff_role']);

                            // Determine Break Time display
                            if ($staffRole === 'Guard (Night Shift)') {
                                $breakTimeDisplay = '<span class="text-muted">No Break Time Needed</span>';
                            } elseif (is_null($row['break_time_id'])) {
                                $breakTimeDisplay = '<span class="text-danger">Break time not yet set.</span>';
                            } else {
                                // Format start and end times
                                $startTime = date("g:i a", strtotime($row['start_break']));
                                $endTime = date("g:i a", strtotime($row['end_break']));

                                // Calculate duration
                                $startDateTime = new DateTime($row['start_break']);
                                $endDateTime = new DateTime($row['end_break']);
                                $interval = $startDateTime->diff($endDateTime);
                                $hours = $interval->h;
                                $minutes = $interval->i;
                                $duration = "";
                                if ($hours > 0) {
                                    $duration .= $hours . " hr";
                                    if ($minutes > 0) {
                                        $duration .= " " . $minutes . " mins";
                                    }
                                } else {
                                    $duration .= $minutes . " mins";
                                }

                                // Corrected variable usage
                                $breakTimeDisplay = htmlspecialchars($row['break_name']) . " ({$startTime} to {$endTime}): {$duration}";
                            }
                            ?>
                            <tr id="row-<?= htmlspecialchars($row['staff_id']); ?>">
                                <td><?= $index++; ?></td>
                                <td><?= $fullName; ?></td>
                                <td><?= $staffRole; ?></td>
                                <td><?= $employeeID; ?></td>
                                <td id="break-time-<?= htmlspecialchars($row['staff_id']); ?>" data-break_time_id="<?= htmlspecialchars($row['break_time_id']); ?>">
                                    <?= $breakTimeDisplay; ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-outline-custom btn-sm" style="height:26px;" onclick="redirectToSingleDTR(<?= htmlspecialchars($row['staff_id']); ?>)">
                                        <i class="bi bi-printer print-icon"></i> Print DTR
                                    </button>
                                    <?php if ($staffRole !== 'Guard (Night Shift)'): ?>
                                        <button class="btn btn-outline-custom btn-sm me-2" 
                                                onclick="showBreakTimeModal(<?= htmlspecialchars($row['staff_id']); ?>)">
                                            <i class="bi bi-cup-hot"></i> Break Time
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if ($totalEntries === 0): ?>
                            <tr id="noResultRow">
                                <td colspan="6" class="text-center">No result.</td>
                            </tr>
                        <?php endif; ?>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Information -->
        <div class="d-flex justify-content-between align-items-center mt-3 pagination-controls">
            <p class="mb-0" id="pageInfo">Showing 1 to <?= min($index - 1, 10); ?> of <?= $totalEntries; ?> entries</p>
            <div class="d-flex">
                <button id="prevBtn" class="btn btn-outline-primary me-2" disabled>Previous</button>
                <button id="nextBtn" class="btn btn-outline-primary">Next</button>
            </div>
        </div>
    </div>

    <!-- Modal for Assigning Break Time -->
    <div class="modal fade" id="assignBreakTimeModal" tabindex="-1" aria-labelledby="assignBreakTimeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="assignBreakTimeForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assignBreakTimeModalLabel">Assign Staff Break Time</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="staff_id" id="modalStaffId" value="">
                        <label for="breakTimeSelect" class="form-label">Select a Break Time</label>
                        <select id="breakTimeSelect" name="break_time_id" class="form-select" required>
                            <option value="" disabled>- Select Break Time -</option>
                            <?php
                            // Fetch all break time schedules from the database
                            $breakQuery = "SELECT break_time_id, break_name, start_break, end_break 
                                           FROM break_time_schedule";
                            $breakResult = mysqli_query($conn, $breakQuery);

                            if ($breakResult) {
                                while ($breakRow = mysqli_fetch_assoc($breakResult)) {
                                    // Format start and end times to AM/PM
                                    $startTime = date("g:i a", strtotime($breakRow['start_break']));
                                    $endTime = date("g:i a", strtotime($breakRow['end_break']));
                                    
                                    // Calculate duration
                                    $startDateTime = new DateTime($breakRow['start_break']);
                                    $endDateTime = new DateTime($breakRow['end_break']);
                                    $interval = $startDateTime->diff($endDateTime);
                                    $hours = $interval->h;
                                    $minutes = $interval->i;
                                    $duration = "";
                                    if ($hours > 0) {
                                        $duration .= $hours . " hr";
                                        if ($minutes > 0) {
                                            $duration .= " " . $minutes . " mins";
                                        }
                                    } else {
                                        $duration .= $minutes . " mins";
                                    }

                                    echo "<option value='{$breakRow['break_time_id']}'>
                                            {$breakRow['break_name']} ({$startTime} to {$endTime}): {$duration}
                                        </option>";
                                }
                            } else {
                                echo "<option value='' disabled>No break times available</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <div id="modalMessage" class="me-auto"></div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Assign Break</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php include_once('footer.php'); ?>

    <!-- JavaScript for Table Functionality and Modal -->
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
            let rows = Array.from(tableBody.querySelectorAll('tr:not(#noResultRow)'));
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

                rows.forEach(row => row.style.display = 'none');
                filteredRows.forEach(row => row.style.display = '');

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

                filteredRows.forEach((row, index) => {
                    row.style.display = (index >= start && index < end) ? '' : 'none';
                });

                const showingStart = filteredRows.length > 0 ? start + 1 : 0;
                const showingEnd = Math.min(end, totalRows);
                pageInfo.textContent = `Showing ${showingStart} to ${showingEnd} of ${totalRows} entries`;

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

            paginateTable();
        });

        function redirectToSingleDTR(staffId) {
            window.location.href = `single-staff-dtr.php?staff_id=${staffId}`;
        }

        function showBreakTimeModal(staffId) {
            // Set the staff ID in the hidden input field within the modal
            document.getElementById('modalStaffId').value = staffId;

            // Retrieve the current break_time_id from the table
            const breakTimeCell = document.getElementById(`break-time-${staffId}`);
            const currentBreakTimeId = breakTimeCell.getAttribute('data-break_time_id');

            // Set the current break time in the select box
            const breakTimeSelect = document.getElementById('breakTimeSelect');
            if (currentBreakTimeId && !isNaN(currentBreakTimeId)) {
                breakTimeSelect.value = currentBreakTimeId;
            } else {
                breakTimeSelect.value = '';
            }

            document.getElementById('modalMessage').innerHTML = '';

            const modal = new bootstrap.Modal(document.getElementById('assignBreakTimeModal'));
            modal.show();
        }

        // Handle AJAX form submission for assigning break time
        document.getElementById('assignBreakTimeForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const staffId = document.getElementById('modalStaffId').value;
            const breakTimeId = document.getElementById('breakTimeSelect').value;
            const modalMessage = document.getElementById('modalMessage');

            if (!breakTimeId) {
                modalMessage.innerHTML = '<div class="alert alert-danger">Please select a break time.</div>';
                return;
            }

            const formData = new FormData();
            formData.append('staff_id', staffId);
            formData.append('break_time_id', breakTimeId);

            fetch('actions/assign-staff-break-time.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the break time display in the table
                    const breakTimeCell = document.getElementById(`break-time-${staffId}`);
                    breakTimeCell.innerHTML = data.break_time_display;
                    breakTimeCell.setAttribute('data-break_time_id', data.break_time_id);

                    // Optionally, show a success message
                    modalMessage.innerHTML = '<div class="alert alert-success">Break time assigned successfully.</div>';

                    // Close the modal after a short delay
                    setTimeout(() => {
                        bootstrap.Modal.getInstance(document.getElementById('assignBreakTimeModal')).hide();
                    }, 1000);
                } else {
                    // Show error message
                    modalMessage.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(error => {
                modalMessage.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
                console.error('Error:', error);
            });
        });
    </script>
</body>
</html>
