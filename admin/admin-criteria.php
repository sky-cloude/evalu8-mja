<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <title>Evaluation Criteria</title>
    <?php include('../header.php'); ?>
    <!-- CSS files -->
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/admin-elements.css" />

    <style>
        /* Add visual feedback when dragging an item */
        .list-group-item.dragging {
            opacity: 0.5;
            background-color: #f8f9fa;
            transition: opacity 0.2s ease;
        }

        /* Add a border to indicate the drop zone */
        .list-group-item.over {
            border-top: 2px solid #007bff;
            transition: border 0.2s ease;
        }

        .list-group-item {
            cursor: move; /* Changes the cursor to the move icon */
        }

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
    </style>

</head>
<body>
    <?php session_start();
     include('navigation.php'); 
    
    // Display the flash message if set
    if (isset($_SESSION['flash_message'])) {
        echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    showFlashMessage("' . $_SESSION['flash_message'] . '");
                });
            </script>';
        unset($_SESSION['flash_message']); // Remove the message after displaying it
    }
    ?>

    <!-- Flash Message -->
    <div id="flashMessage" class="flash-message">
        Order saved successfully!
    </div>
    
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

    <!-- ADMIN CRITERIA CONTENTS -->
    <p class="classTitle fs-4 fw-bold ms-5 mb-2">Evaluation Criteria</p>
    <div class="mx-auto mb-4 bg-warning" style="height: 2px; width: 95%;"></div>

    <div class="container-fluid bg-white rounded border shadow mb-5 pt-4 pb-5" style="width: 90%;">
        <div class="row g-2">
            <!-- Criteria Form Column -->
            <div class="col-12 col-lg-5 px-sm-2 px-md-3">
                <div class="bg-white rounded border mt-4 mb-4 p-3">
                    <p class="fs-6 fw-bold ms-2 mb-2">Criteria Form</p>
                    <hr class="mx-auto bg-dark mb-4 mt-3" style="height: 1.5px; width: 95%;">
                    <p class="fs-6 fw-bold ms-2 mt-4 mb-2">Criteria</p>
                    <input type="text" class="form-control border border-secondary rounded-1 mb-4 border" id="criteriaInput">
                    <hr class="mx-auto mt-5 mb-6" style="height: 1.5px; width: 95%;">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-secondary w-auto" style="height: 40px;" id="cancelBtn">Cancel</button>
                        <button class="btn btn-primary w-auto" style="height: 40px;" id="saveBtn">Save</button>
                    </div>
                </div>
            </div>

            <!-- Criteria List Column -->
            <div class="col-12 col-lg-7 px-sm-2 px-md-1 ">
                <div class="bg-white rounded border mt-4 mb-4 p-3">
                    <p class="fs-6 fw-bold ms-2 mb-2">Criteria List</p>
                    <hr class="mx-auto bg-dark mb-4 mt-3" style="height: 1.5px; width: 95%;">
                    <ul class="list-group" id="criteriaList">
                        <?php
                        include '../db_conn.php';

                        // Fetch criteria from the database
                        $query = "SELECT criteria, criteria_id FROM criteria_list ORDER BY ordered_by ASC";
                        $result = $conn->query($query);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $criteria_id = $row['criteria_id'];  // Add the criteria ID for easy identification
                                echo '<li class="list-group-item d-flex justify-content-between align-items-center shadow" draggable="true">
                                        <i class="bi bi-list"></i>
                                        <div class="flex-grow-1 ms-2">' . htmlspecialchars($row['criteria']) . '</div>
                                        <div class="dropdown">
                                            <i class="bi bi-three-dots-vertical" id="dropdownMenuButton' . $criteria_id . '" role="button" data-bs-toggle="dropdown" aria-expanded="false"></i>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton' . $criteria_id . '">
                                                <li><a class="dropdown-item text-dark" href="#" onclick="editCriteria(' . $criteria_id . ', \'' . htmlspecialchars($row['criteria']) . '\')">Edit</a></li>
                                                <li><a class="dropdown-item text-dark" href="#" onclick="confirmDelete(' . $criteria_id . ')">Delete</a></li>
                                            </ul>
                                        </div>
                                    </li>';
                            }
                        }

                        $conn->close();
                        ?>
                    </ul>

                    <div class="d-flex justify-content-end mt-4">
                        <button class="btn btn-primary" id="saveOrderBtn">Save Order</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Edit -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Criteria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control" id="editCriteriaInput">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveEditBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Delete Confirmation -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this criterion and its questions?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>

    <script>
        let draggedItem = null;
        let currentOverItem = null;
        let editCriteriaId = null;  // Track the criteria being edited
        let initialOrder = [];

        // Enable drag-and-drop functionality
        const criteriaList = document.getElementById('criteriaList');

        // Store the initial order of the criteria list
        document.querySelectorAll('.list-group-item').forEach(item => {
            initialOrder.push(item.querySelector('.bi-three-dots-vertical').id.replace('dropdownMenuButton', ''));
        });

        // Start dragging
        criteriaList.addEventListener('dragstart', function(e) {
            draggedItem = e.target;
            e.target.classList.add('dragging');
            setTimeout(function() {
                draggedItem.style.display = 'none';
            }, 0);
        });

        // End dragging
        criteriaList.addEventListener('dragend', function(e) {
            e.target.classList.remove('dragging');
            setTimeout(function() {
                draggedItem.style.display = 'block';
                draggedItem = null;
                if (currentOverItem) {
                    currentOverItem.classList.remove('over');
                }
            }, 0);
            checkOrderChange();  // Check if the order has changed
        });

        // Handle dragover
        criteriaList.addEventListener('dragover', function(e) {
            e.preventDefault();
            const afterElement = getDragAfterElement(criteriaList, e.clientY);
            const draggable = document.querySelector('.dragging');
            if (afterElement == null) {
                criteriaList.appendChild(draggable);
            } else {
                criteriaList.insertBefore(draggable, afterElement);
            }
        });

        // Helper function to determine the drop location
        function getDragAfterElement(list, y) {
            const draggableElements = [...list.querySelectorAll('.list-group-item:not(.dragging)')];

            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }

        // Check if the order has changed and enable/disable the save button
        function checkOrderChange() {
            let currentOrder = [];
            document.querySelectorAll('.list-group-item').forEach(item => {
                currentOrder.push(item.querySelector('.bi-three-dots-vertical').id.replace('dropdownMenuButton', ''));
            });

            if (JSON.stringify(initialOrder) !== JSON.stringify(currentOrder)) {
                document.getElementById('saveOrderBtn').classList.remove('btn-disabled');
                document.getElementById('saveOrderBtn').disabled = false;
            } else {
                document.getElementById('saveOrderBtn').classList.add('btn-disabled');
                document.getElementById('saveOrderBtn').disabled = true;
            }
        }

        // Save the new order when the Save Order button is clicked
        document.getElementById('saveOrderBtn').addEventListener('click', function() {
            let criteriaItems = document.querySelectorAll('#criteriaList .list-group-item');
            let updatedOrder = [];

            // Collect the criteria ID and new order
            criteriaItems.forEach((item, index) => {
                let criteriaId = item.querySelector('.bi-three-dots-vertical').id.replace('dropdownMenuButton', '');
                updatedOrder.push({ criteria_id: criteriaId, ordered_by: index + 1 });
            });

            // Send the updated order to the server
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'actions/update_order.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onload = function() {
                if (xhr.status === 200 && xhr.responseText === 'Success') {
                    showFlashMessage('Order saved successfully!');
                    initialOrder = updatedOrder.map(item => item.criteria_id);
                    checkOrderChange();
                } else {
                    alert('Error updating order: ' + xhr.responseText);
                }
            };
            xhr.send(JSON.stringify(updatedOrder));
        });

        // Show the flash message
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

        // Function to handle saving a new criterion
        document.getElementById('saveBtn').addEventListener('click', function () {
            var criteria = document.getElementById('criteriaInput').value;
            if (criteria.trim() === '') {
                alert('Please enter a criterion.');
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'actions/save_criteria.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function () {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.status === 'Success') {
                        // Append the new criterion to the list dynamically
                        const criteriaList = document.getElementById('criteriaList');
                        const newCriterionHtml = `
                            <li class="list-group-item d-flex justify-content-between align-items-center" draggable="true">
                                <i class="bi bi-list"></i>
                                <div class="flex-grow-1 ms-2">${response.criteria.criteria}</div>
                                <div class="dropdown">
                                    <i class="bi bi-three-dots-vertical" id="dropdownMenuButton${response.criteria.criteria_id}" role="button" data-bs-toggle="dropdown" aria-expanded="false"></i>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton${response.criteria.criteria_id}">
                                        <li><a class="dropdown-item text-dark" href="#" onclick="editCriteria(${response.criteria.criteria_id}, '${response.criteria.criteria}')">Edit</a></li>
                                        <li><a class="dropdown-item text-dark" href="#" onclick="confirmDelete(${response.criteria.criteria_id})">Delete</a></li>
                                    </ul>
                                </div>
                            </li>`;

                        criteriaList.insertAdjacentHTML('afterbegin', newCriterionHtml); // Add to the top of the list

                        // Clear the input field
                        document.getElementById('criteriaInput').value = '';

                        // Show flash message dynamically
                        showFlashMessage(response.message);
                    } else {
                        alert('Error saving criterion: ' + response.message);
                    }
                } else {
                    alert('Request failed. Status: ' + xhr.status);
                }
            };
            xhr.send('criteria=' + encodeURIComponent(criteria));
        });


        // Function to handle editing a criterion
        function editCriteria(criteriaId, currentCriteria) {
            editCriteriaId = criteriaId;  // Track the ID of the criterion being edited

            // Fetch the updated criteria text from the list item
            const criteriaListItem = document.querySelector(`i[id='dropdownMenuButton${criteriaId}']`).closest('.list-group-item');
            const updatedCriteria = criteriaListItem.querySelector('.flex-grow-1').textContent;

            // Set the input value to the updated criterion (if available)
            document.getElementById('editCriteriaInput').value = updatedCriteria || currentCriteria;

            // Show the edit modal
            var editModal = new bootstrap.Modal(document.getElementById('editModal'), {});
            editModal.show();
        }


        // Save changes when the Save Changes button is clicked in the modal
        document.getElementById('saveEditBtn').addEventListener('click', function () {
            const updatedCriteria = document.getElementById('editCriteriaInput').value;
            if (updatedCriteria.trim() === '') {
                alert('Please enter a criteria.');
                return;
            }

            // Send the updated criteria to the server asynchronously
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'actions/edit-criteria.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function () {
                if (xhr.status === 200) {
                    if (xhr.responseText === 'Success') {
                        // Update the criteria list item in the DOM without refreshing the page
                        const criteriaListItem = document.querySelector(`i[id='dropdownMenuButton${editCriteriaId}']`).closest('.list-group-item');
                        criteriaListItem.querySelector('.flex-grow-1').textContent = updatedCriteria;

                        // Also update the modal input field with the new data
                        document.getElementById('editCriteriaInput').value = updatedCriteria;

                        // Hide the modal after successful update
                        var editModal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
                        editModal.hide();

                        // Show flash message to indicate success
                        showFlashMessage('Criteria updated successfully!');
                    } else {
                        alert('Error updating criteria: ' + xhr.responseText);
                    }
                } else {
                    alert('Request failed. Status: ' + xhr.status);
                }
            };

            xhr.send('criteria_id=' + encodeURIComponent(editCriteriaId) + '&criteria=' + encodeURIComponent(updatedCriteria));
        });

        function confirmDelete(criteria_id) {
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'), {});
            deleteModal.show();

            document.getElementById('confirmDeleteBtn').onclick = function() {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'actions/delete_criteria.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        if (xhr.responseText === 'Success') {
                            // Remove the deleted criterion from the list without reloading the page
                            document.querySelector(`i[id='dropdownMenuButton${criteria_id}']`).closest('.list-group-item').remove();

                            // Close the modal after deletion
                            var deleteModalInstance = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                            if (deleteModalInstance) {
                                deleteModalInstance.hide();
                            }

                            // Show flash message dynamically
                            showFlashMessage('Criteria deleted successfully!');
                        } else {
                            alert('Error deleting criteria: ' + xhr.responseText);
                        }
                    } else {
                        alert('Request failed. Status: ' + xhr.status);
                    }
                };
                xhr.send('criteria_id=' + encodeURIComponent(criteria_id));
            };
        }


        // Clear input when Cancel button is clicked
        document.getElementById('cancelBtn').addEventListener('click', function() {
            document.getElementById('criteriaInput').value = '';
        });

    </script>


</body>
</html>
