<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Break Time List</title>
    <?php include('../header.php'); ?>
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
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

        .buttonContainer {
            display: flex;
            justify-content: flex-end;
        }

        .custom-dark-brown-btn {
            background-color: #281313;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
        }

        .custom-dark-brown-btn:hover {
            background-color: #facc15;
            color: #281313;
        }

        .custom-table-border {
            border: 1px solid #dee2e6;
        }

        /* Responsive adjustments for smaller screens */
        @media (max-width: 767px) { 
            .breadcrumbs {
                margin-top: 15%;
                padding-top: 6%;
                margin-bottom:8%;
            }
            .container.bg-white {
                max-width: 90%;
                margin: auto;
            }
            .buttonContainer {
                justify-content: center;
            }
            .search-box {
                flex-direction: column;
                align-items: flex-start;
            }
            .search-box form, .search-box select {
                width: 100%;
            }
            .pagination {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <?php
    session_start();
    include('../db_conn.php');
    include('navigation.php');

    // Handle Flash Messages
    $message = '';
    $message_type = '';

    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $message_type = $_SESSION['message_type'];
        unset($_SESSION['message'], $_SESSION['message_type']);
    }

    // Fetch all break times
    $query = "SELECT break_time_id, break_name, start_break, end_break FROM break_time_schedule ORDER BY break_time_id ASC";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }

    $total_records = mysqli_num_rows($result);
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
                <li class="breadcrumb-item">
                    <a href="staff-dtr.php" class="text-muted text-decoration-none">Staff DTR</a>
                </li>
                <li class="breadcrumb-item active text-muted" aria-current="page">Break Time List</li>
            </ol>
        </nav>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="classTitle fs-4 fw-bold ms-5 mb-2">Break Time List</p>
        <a href="staff-dtr.php" class="btn btn-back">
            <i class="fa-solid fa-circle-chevron-left"></i>
        </a>
    </div>

    <div class="mx-auto mb-4" style="height: 2px; background-color: #facc15; width:95%;"></div>

    <div class="container bg-white rounded border shadow mb-5 p-4">
        <!-- Alert Container for Session Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Alert Container for AJAX Messages -->
        <div id="alertContainer"></div>

        <div class="buttonContainer">
            <button class="btn custom-dark-brown-btn mt-3 mb-3 float-end" data-bs-toggle="modal" data-bs-target="#addNewModal">
                <i class="bi bi-plus-lg me-1"></i>Add New
            </button>
        </div>

        <hr class="bg-dark" style="border-width: 1px; margin-top: 20px; margin-bottom: 20px;"><!-- GRAY LINE -->

        <div class="container">
            <div class="row mb-3 search-box">
                <div class="col-md-6 d-flex align-items-center mb-3 mb-md-0">
                    <label for="entriesCount" class="form-label me-2 mb-0">Show</label>
                    <select class="form-select d-inline w-auto" name="entries" id="entriesCount">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="ms-2 mb-0">entries</span>
                </div>
                <div class="col-md-6 d-flex align-items-center justify-content-md-end justify-content-center">
                    <p class="me-2 mb-0">Search:</p>
                    <input type="search" id="searchInput" class="form-control form-control-sm w-auto" placeholder="">
                </div>
            </div>
        </div>

        <!-- Break Time Table -->
        <div class="table-container" style="margin-top:25px;">
            <table class="table table-striped table-hover table-bordered custom-table-border">
                <thead class="text-center">
                    <tr>
                        <th>#</th>
                        <th>Break Time Name</th>
                        <th>Start Break</th>
                        <th>End Break</th>
                        <th>Duration</th>
                        <th>Action</th>
                    </tr>
                </thead>
                
                <tbody class="text-center" id="tableBody">
                    <?php
                    if ($total_records > 0):
                        $index = 1;
                        while ($break = mysqli_fetch_assoc($result)):
                            // Assign unique row IDs for easier manipulation
                            $row_id = 'row-' . $break['break_time_id'];

                            // Calculate Duration
                            $startTime = new DateTime($break['start_break']);
                            $endTime = new DateTime($break['end_break']);
                            $interval = $startTime->diff($endTime);
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
                    ?>
                        <tr id="<?php echo $row_id; ?>" data-id="<?php echo $break['break_time_id']; ?>">
                            <td><?php echo $index++; ?></td>
                            <td><?php echo htmlspecialchars($break['break_name']); ?></td>
                            <td><?php echo htmlspecialchars($break['start_break']); ?></td>
                            <td><?php echo htmlspecialchars($break['end_break']); ?></td>
                            <td><?php echo $duration; ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-primary btn-sm edit-btn" 
                                            data-id="<?php echo $break['break_time_id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($break['break_name']); ?>" 
                                            data-start="<?php echo htmlspecialchars($break['start_break']); ?>" 
                                            data-end="<?php echo htmlspecialchars($break['end_break']); ?>" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editBreakTimeModal">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-danger btn-sm delete-btn" 
                                            data-id="<?php echo $break['break_time_id']; ?>" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteBreakTimeModal">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                        <tr id="noResultRow">
                            <td colspan="6">No result.</td>
                        </tr>
                    <?php 
                    endif; 
                    ?>
                </tbody>
            </table>

            <!-- Pagination Info and Controls -->
            <div class="container mt-3 mb-3">
                <div class="row">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <p class="mb-0" id="pageInfo">
                            Showing 1 to <?php echo $total_records < 10 ? $total_records : 10; ?> of <?php echo $total_records; ?> entries
                        </p>
                        <div class="d-flex">
                            <button id="prevBtn" class="btn btn-outline-primary me-2" disabled>
                                Previous
                            </button>
                            <button id="nextBtn" class="btn btn-outline-primary" <?php echo $total_records <= 10 ? 'disabled' : ''; ?>>
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add New Break Time Modal -->
        <div class="modal fade" id="addNewModal" tabindex="-1" aria-labelledby="addNewModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form action="actions/add-staff-breaktime.php" method="POST" id="addBreakTimeForm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addNewModalLabel">Add New Break Time</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Break Name -->
                            <div class="mb-3">
                                <label for="break_name" class="form-label">Break Time Name</label>
                                <input type="text" class="form-control" id="break_name" name="break_name" required>
                            </div>
                            <!-- Start Break -->
                            <div class="mb-3">
                                <label for="start_break" class="form-label">Start Break</label>
                                <input type="time" class="form-control" id="start_break" name="start_break" required>
                            </div>
                            <!-- End Break -->
                            <div class="mb-3">
                                <label for="end_break" class="form-label">End Break</label>
                                <input type="time" class="form-control" id="end_break" name="end_break" required>
                            </div>
                            <!-- Error Message -->
                            <?php if (isset($_SESSION['add_error'])): ?>
                                <div class="alert alert-danger">
                                    <?= $_SESSION['add_error']; unset($_SESSION['add_error']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Add Break Time</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Break Time Modal -->
        <div class="modal fade" id="editBreakTimeModal" tabindex="-1" aria-labelledby="editBreakTimeModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form id="editBreakTimeForm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editBreakTimeModalLabel">Edit Break Time</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Hidden Field for Break Time ID -->
                            <input type="hidden" id="edit_break_time_id" name="break_time_id">
                            <!-- Break Time Name -->
                            <div class="mb-3">
                                <label for="edit_break_name" class="form-label">Break Time Name</label>
                                <input type="text" class="form-control" id="edit_break_name" name="break_name" required>
                            </div>
                            <!-- Start Break -->
                            <div class="mb-3">
                                <label for="edit_start_break" class="form-label">Start Break</label>
                                <input type="time" class="form-control" id="edit_start_break" name="start_break" required>
                            </div>
                            <!-- End Break -->
                            <div class="mb-3">
                                <label for="edit_end_break" class="form-label">End Break</label>
                                <input type="time" class="form-control" id="edit_end_break" name="end_break" required>
                            </div>
                            <!-- Error Message -->
                            <div id="editErrorMsg" class="alert alert-danger d-none" role="alert">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteBreakTimeModal" tabindex="-1" aria-labelledby="deleteBreakTimeModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form id="deleteBreakTimeForm" method="POST" action="actions/delete-staff-breaktime.php">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteBreakTimeModalLabel">Confirm Deletion</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to delete this break time?
                            <input type="hidden" id="delete_break_time_id" name="break_time_id">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php include('footer.php'); ?>
        <!-- Include jQuery before custom scripts -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            // Initialize pagination variables
            let currentPage = 1;
            let rowsPerPage = parseInt($('#entriesCount').val());
            let totalRows = $('#tableBody tr').not('#noResultRow').length;
            let totalPages = Math.ceil(totalRows / rowsPerPage);

            // Function to paginate the table
            function paginateTable() {
                const tableBody = $('#tableBody');
                let rows = tableBody.find('tr').not('#noResultRow');

                // Get search term
                const searchTerm = $('#searchInput').val().toLowerCase();

                // Filter rows if search term is present
                if (searchTerm) {
                    rows = rows.filter(function() {
                        const cells = $(this).find('td');
                        for (let i = 1; i < 4; i++) { // Break Time Name, Start Break, End Break
                            if ($(cells[i]).text().toLowerCase().includes(searchTerm)) {
                                return true;
                            }
                        }
                        return false;
                    });
                }

                totalRows = rows.length;
                totalPages = Math.ceil(totalRows / rowsPerPage);

                // Ensure currentPage is within bounds
                if (currentPage > totalPages) {
                    currentPage = totalPages;
                }
                if (currentPage < 1) {
                    currentPage = 1;
                }

                let start = (currentPage - 1) * rowsPerPage;
                let end = start + rowsPerPage;
                let visibleRowIndex = start + 1;

                // Hide all rows
                tableBody.find('tr').not('#noResultRow').hide();

                // Show the current page's rows
                rows.slice(start, end).show().each(function() {
                    $(this).find('td:first').text(visibleRowIndex++);
                });

                // Handle the "No result." row
                let visibleCount = rows.slice(start, end).length;
                if (visibleCount === 0) {
                    if ($('#noResultRow').length === 0) {
                        $('<tr id="noResultRow"><td colspan="6">No result.</td></tr>').appendTo('#tableBody');
                    } else {
                        $('#noResultRow').show();
                    }
                } else {
                    $('#noResultRow').hide();
                }

                updatePageInfo();
                toggleButtons();
            }

            // Function to update pagination info
            function updatePageInfo() {
                const start = (currentPage - 1) * rowsPerPage + 1;
                const end = Math.min(currentPage * rowsPerPage, totalRows);
                if (totalRows === 0) {
                    $('#pageInfo').text('Showing 0 to 0 of 0 entries');
                } else {
                    $('#pageInfo').text(`Showing ${start} to ${end} of ${totalRows} entries`);
                }
            }

            // Function to toggle pagination buttons
            function toggleButtons() {
                $('#prevBtn').prop('disabled', currentPage === 1);
                $('#nextBtn').prop('disabled', currentPage >= totalPages);
            }

            // Handle Entries Count Change
            $('#entriesCount').on('change', function() {
                rowsPerPage = parseInt($(this).val());
                currentPage = 1;
                paginateTable();
            });

            // Handle Previous Button Click
            $('#prevBtn').on('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    paginateTable();
                }
            });

            // Handle Next Button Click
            $('#nextBtn').on('click', function() {
                if (currentPage < totalPages) {
                    currentPage++;
                    paginateTable();
                }
            });

            // Handle Edit Break Time Modal Show Event
            $('#editBreakTimeModal').on('show.bs.modal', function(event) {
                const button = $(event.relatedTarget); // Button that triggered the modal
                const breakTimeId = button.attr('data-id');
                const breakName = button.attr('data-name');
                const startBreak = button.attr('data-start');
                const endBreak = button.attr('data-end');

                // Populate the Edit Modal fields
                $('#edit_break_time_id').val(breakTimeId);
                $('#edit_break_name').val(breakName);
                $('#edit_start_break').val(startBreak);
                $('#edit_end_break').val(endBreak);
                $('#editErrorMsg').addClass('d-none').text('');
            });

            // Handle Edit Form Submission via AJAX
            $('#editBreakTimeForm').on('submit', function(e) {
                e.preventDefault();

                const formData = $(this).serialize();

                $.ajax({
                    url: 'actions/edit-staff-breaktime.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Update the table row with new data
                            const row = $('#row-' + response.updatedBreakTime.break_time_id);
                            row.find('td:eq(1)').text(response.updatedBreakTime.break_name);
                            row.find('td:eq(2)').text(response.updatedBreakTime.start_break);
                            row.find('td:eq(3)').text(response.updatedBreakTime.end_break);

                            // Recalculate Duration
                            const startTime = new Date('1970-01-01T' + response.updatedBreakTime.start_break + 'Z');
                            const endTime = new Date('1970-01-01T' + response.updatedBreakTime.end_break + 'Z');
                            let diffInMs = endTime - startTime;
                            if (diffInMs < 0) diffInMs += 24 * 60 * 60 * 1000; // Adjust for times crossing midnight
                            let diffInMinutes = Math.floor(diffInMs / 60000);
                            let hours = Math.floor(diffInMinutes / 60);
                            let minutes = diffInMinutes % 60;
                            let duration = "";
                            if (hours > 0) {
                                duration += hours + " hr";
                                if (minutes > 0) {
                                    duration += " " + minutes + " mins";
                                }
                            } else {
                                duration += minutes + " mins";
                            }
                            row.find('td:eq(4)').text(duration);

                            // Update the data attributes of the edit button
                            const editButton = row.find('.edit-btn');
                            editButton.attr('data-name', response.updatedBreakTime.break_name);
                            editButton.attr('data-start', response.updatedBreakTime.start_break);
                            editButton.attr('data-end', response.updatedBreakTime.end_break);

                            // Remove cached data
                            editButton.removeData('name').removeData('start').removeData('end');

                            // Hide the modal
                            $('#editBreakTimeModal').modal('hide');

                            // Show success message in alertContainer
                            $('#alertContainer').html(`
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    Break Time updated successfully!
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            `);

                            // Recalculate totalRows and paginate
                            paginateTable();
                        } else {
                            // Show error message
                            $('#editErrorMsg').removeClass('d-none').text(response.message);
                        }
                    },
                    error: function() {
                        $('#editErrorMsg').removeClass('d-none').text('An error occurred while updating the break time.');
                    }
                });
            });

            // Handle Delete Button Click
            $(document).on('click', '.delete-btn', function() {
                const breakTimeId = $(this).attr('data-id');

                // Set the break_time_id in the Delete Confirmation Modal
                $('#delete_break_time_id').val(breakTimeId);

                // Show the Delete Confirmation Modal
                $('#deleteBreakTimeModal').modal('show');
            });

            // Handle Delete Form Submission via AJAX
            $('#deleteBreakTimeForm').on('submit', function(e) {
                e.preventDefault();

                const breakTimeId = $('#delete_break_time_id').val();

                $.ajax({
                    url: 'actions/delete-breaktime.php',
                    type: 'POST',
                    data: { break_time_id: breakTimeId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Remove the table row
                            $('#row-' + breakTimeId).remove();

                            // Hide the modal
                            $('#deleteBreakTimeModal').modal('hide');

                            // Show success message in alertContainer
                            $('#alertContainer').html(`
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    Break Time deleted successfully!
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            `);

                            // Recalculate totalRows and paginate
                            paginateTable();
                        } else {
                            // Show error message in alertContainer
                            $('#alertContainer').html(`
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    Error: ${response.message}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            `);
                        }
                    },
                    error: function() {
                        // Show generic error message in alertContainer
                        $('#alertContainer').html(`
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                An error occurred while deleting the break time.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `);
                    }
                });
            });

            // Search functionality
            $('#searchInput').on('keyup', function() {
                currentPage = 1;
                paginateTable();
            });

            // Initialize pagination on page load
            $(document).ready(function() {
                paginateTable();
            });
        </script>
    </div>
</body>
</html>
