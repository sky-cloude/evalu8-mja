<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../Logo/mja-logo.png">
    <title>Questionnaires</title>
    <?php include('../header.php'); ?>
    <link rel="stylesheet" href="../css/sidebar.css" />
    <link rel="stylesheet" href="../css/admin-elements.css" />

    <style>
        .flash-message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border-radius: 5px;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }
        .dragging {
            opacity: 0.5;
            background-color: #f8f9fa;
        }
        .table tbody tr {
            cursor: move;
        }
        .btn-disabled {
            pointer-events: none;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <?php 
        session_start(); 
        include('navigation.php'); 
        include('../db_conn.php'); 

        // Flash message display logic
        if (isset($_SESSION['flash_message'])) {
            echo '<div class="flash-message" id="flashMessage">' . $_SESSION['flash_message'] . '</div>';
            unset($_SESSION['flash_message']);
        }

        // Fetch active academic year
        $query_academic_year = "SELECT year, quarter FROM academic_year WHERE is_active = 1 LIMIT 1";
        $result_academic_year = mysqli_query($conn, $query_academic_year);
        $active_academic_year = mysqli_fetch_assoc($result_academic_year);
        
        $academic_year_text = $active_academic_year ? 
            "Evaluation Questionnaire for Academic: " . $active_academic_year['year'] . " - Quarter " . $active_academic_year['quarter'] : 
            "Evaluation Questionnaire for Academic: Year and Quarter not found";

        // Fetch criteria list
        $query_criteria = "SELECT criteria_id, criteria FROM criteria_list ORDER BY ordered_by ASC";
        $result_criteria = mysqli_query($conn, $query_criteria);

        // Fetch questions
        $query_questions = "
            SELECT q.question_id, q.criteria_id, q.question, q.order_by, c.criteria 
            FROM question_list q 
            INNER JOIN criteria_list c ON q.criteria_id = c.criteria_id
            ORDER BY c.ordered_by, q.order_by ASC";
        $result_questions = mysqli_query($conn, $query_questions);

        $questions_by_criteria = [];
        while ($row = mysqli_fetch_assoc($result_questions)) {
            $criteria_id = $row['criteria_id'];
            $questions_by_criteria[$criteria_id][] = $row;
        }
    ?>
    
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
    <p class="classTitle fs-4 fw-bold ms-5 mb-2">Questionnaires</p>
    <div class="mx-auto mb-4 bg-warning" style="height: 2px; width: 95%;"></div>

    <div class="container-fluid bg-white rounded border border shadow mb-5 p-3" style="width: 90%;">
        <div class="row g-2">
            <div class="col-12 col-lg-4 px-sm-2 px-md-3">
                <div class="bg-white rounded border border shadow mt-4 mb-4 p-3">
                    <p class="fs-6 fw-bold ms-2 mb-2">Question Form</p>
                    <hr class="mx-auto bg-dark mb-4" style="height: 1.5px; width: 95%;">

                    <!-- Updated Question Form with AJAX Submission -->
                    <form id="questionForm">
                        <p class="fs-6 fw-bold ms-2 mb-2">Criteria</p>
                        <select class="form-select border rounded-1 mb-4 border-dark" id="criteriaInput" name="criteriaInput" required>
                            <option value="" disabled selected>Please select here</option>
                            <?php 
                                if (mysqli_num_rows($result_criteria) > 0) {
                                    while ($row = mysqli_fetch_assoc($result_criteria)) {
                                        echo '<option value="' . $row['criteria_id'] . '">' . $row['criteria'] . '</option>';
                                    }
                                } else {
                                    echo '<option value="">No criteria available</option>';
                                }
                            ?>
                        </select>

                        <p class="fs-6 fw-bold ms-2 mb-2">Question</p>
                        <textarea class="form-control border rounded-1 mb-4 border-dark" id="questionInput" name="questionInput" rows="5" required></textarea>

                        <hr class="mx-auto mt-5 mb-6" style="height: 1.5px; width: 95%;">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary w-auto" id="cancelBtn">Cancel</button>
                            <button type="submit" class="btn btn-primary w-auto">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-12 col-lg-8 px-sm-2 px-md-3">
                <div class="bg-white rounded border shadow mt-4 mb-4 p-3">
                    <p class="fs-6 fw-bold ms-3 mb-2"><?php echo $academic_year_text; ?></p>
                    <hr class="mx-auto bg-dark mb-2 mt-3" style="height: 1.5px; width: 95%;">

                    <div class="d-flex justify-content-end gap-3 mb-4 mt-4">
                        <button class="btn btn-secondary" onclick="window.location.href='student-like-navigation.php'">Preview</button>
                        <!-- <button class="btn btn-primary">Evaluation Restriction</button> -->
                        <button class="btn btn-success btn-disabled" id="saveOrderBtn" disabled>Save Order</button>
                    </div>

                    <fieldset class="border rounded p-3 mb-4" style="border-width: 2px;">
                        <legend class="w-auto px-2 fs-6 fw-bold text-white mb-3" style="background-color: #343a40;">Rating Legend</legend>
                        
                        <div class="d-flex flex-wrap justify-content-center mt-2 mb-2">
                            <span class="me-4 mb-2">5 = Outstanding</span>
                            <span class="me-4 mb-2">4 = Very Satisfactory</span>
                            <span class="me-4 mb-2">3 = Satisfactory</span>
                            <span class="me-4 mb-2">2 = Fair</span>
                            <span class="mb-2">1 = Needs Improvement</span>
                        </div>
                    </fieldset>

                    <?php
                    if (mysqli_num_rows($result_criteria) > 0) {
                        mysqli_data_seek($result_criteria, 0);
                        while ($row_criteria = mysqli_fetch_assoc($result_criteria)) {
                            $criteria_id = $row_criteria['criteria_id'];
                            $criteria_title = $row_criteria['criteria'];
                            
                            echo '<table class="table table-bordered draggable-table">';
                            echo '<thead>';
                            echo '<tr>';
                            echo '<th class="bg-dark text-white text-left p-2">' . $criteria_title . '</th>';
                            echo '<th class="bg-dark text-white text-center" style="width: 8%;">5</th>';
                            echo '<th class="bg-dark text-white text-center" style="width: 8%;">4</th>';
                            echo '<th class="bg-dark text-white text-center" style="width: 8%;">3</th>';
                            echo '<th class="bg-dark text-white text-center" style="width: 8%;">2</th>';
                            echo '<th class="bg-dark text-white text-center" style="width: 8%;">1</th>';
                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody id="criteria-questions-' . $criteria_id . '">';

                            if (isset($questions_by_criteria[$criteria_id])) {
                                foreach ($questions_by_criteria[$criteria_id] as $question) {
                                    echo '<tr draggable="true" data-question-id="' . $question['question_id'] . '">';
                                    echo '<td class="text-left">
                                        <div class="d-flex align-items-center">
                                            <div class="dropdown">
                                                <i class="bi bi-three-dots-vertical" id="dropdownMenuButton' . $question['question_id'] . '" role="button" data-bs-toggle="dropdown" aria-expanded="false"></i>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton' . $question['question_id'] . '">
                                                    <li><a class="dropdown-item text-dark" href="javascript:void(0)" onclick="editQuestion(' . $question['question_id'] . ')">Edit</a></li>
                                                    <li><a class="dropdown-item text-dark" href="javascript:void(0)" onclick="openDeleteModal(' . $question['question_id'] . ')">Delete</a></li>
                                                </ul>
                                            </div>
                                            <span class="ms-2">' . htmlspecialchars($question['question']) . '</span>
                                        </div>
                                    </td>';
                                    echo '<td class="text-center"><input type="radio" name="question' . $question['question_id'] . '" value="5"></td>';
                                    echo '<td class="text-center"><input type="radio" name="question' . $question['question_id'] . '" value="4"></td>';
                                    echo '<td class="text-center"><input type="radio" name="question' . $question['question_id'] . '" value="3"></td>';
                                    echo '<td class="text-center"><input type="radio" name="question' . $question['question_id'] . '" value="2"></td>';
                                    echo '<td class="text-center"><input type="radio" name="question' . $question['question_id'] . '" value="1"></td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="6" class="text-center">No questions available for this criteria.</td></tr>';
                            }
                            
                            echo '</tbody>';
                            echo '</table>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Question Modal -->
    <div class="modal fade" id="editQuestionModal" tabindex="-1" aria-labelledby="editQuestionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editQuestionModalLabel">Edit Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editQuestionForm">
                        <div class="mb-3">
                            <label for="editQuestionInput" class="form-label">Question</label>
                            <textarea class="form-control" id="editQuestionInput" name="editQuestionInput" rows="4" required></textarea>
                            <input type="hidden" id="editQuestionId" name="editQuestionId">
                        </div>
                        <button type="submit" class="btn btn-primary float-end mt-4">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteQuestionModal" tabindex="-1" aria-labelledby="deleteQuestionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteQuestionModalLabel">Delete Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this question?</p>
                    <input type="hidden" id="deleteQuestionId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteButton">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>

    <script>
        // For AJAX-based adding of questions
        document.getElementById('questionForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent default form submission

            const criteriaInput = document.getElementById('criteriaInput').value;
            const questionInput = document.getElementById('questionInput').value;

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'actions/add-question.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Clear form inputs
                        document.getElementById('questionForm').reset();

                        // Display flash message
                        displayFlashMessage(response.message);

                        // Append the newly added question to the correct table dynamically
                        const criteriaId = criteriaInput;
                        const newQuestionHtml = `
                            <tr draggable="true" data-question-id="${response.question_id}">
                                <td class="text-left">
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <i class="bi bi-three-dots-vertical" id="dropdownMenuButton${response.question_id}" role="button" data-bs-toggle="dropdown" aria-expanded="false"></i>
                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton${response.question_id}">
                                                <li><a class="dropdown-item text-dark" href="javascript:void(0)" onclick="editQuestion(${response.question_id})">Edit</a></li>
                                                <li><a class="dropdown-item text-dark" href="javascript:void(0)" onclick="openDeleteModal(${response.question_id})">Delete</a></li>
                                            </ul>
                                        </div>
                                        <span class="ms-2">${response.question}</span>
                                    </div>
                                </td>
                                <td class="text-center"><input type="radio" name="question${response.question_id}" value="5"></td>
                                <td class="text-center"><input type="radio" name="question${response.question_id}" value="4"></td>
                                <td class="text-center"><input type="radio" name="question${response.question_id}" value="3"></td>
                                <td class="text-center"><input type="radio" name="question${response.question_id}" value="2"></td>
                                <td class="text-center"><input type="radio" name="question${response.question_id}" value="1"></td>
                            </tr>`;

                        // Find the correct criteria table and append the new question
                        const criteriaTableBody = document.getElementById(`criteria-questions-${criteriaId}`);
                        if (criteriaTableBody) {
                            criteriaTableBody.insertAdjacentHTML('beforeend', newQuestionHtml);
                        }
                    } else {
                        displayFlashMessage('Error: ' + response.message);
                    }
                }
            };

            // Send the form data via AJAX
            xhr.send('criteriaInput=' + encodeURIComponent(criteriaInput) + '&questionInput=' + encodeURIComponent(questionInput));
        });

        let initialOrder = [];
        document.querySelectorAll('.draggable-table tbody tr').forEach(row => {
            initialOrder.push(row.getAttribute('data-question-id'));
        });

        function checkOrderChange() {
            let currentOrder = [];
            document.querySelectorAll('.draggable-table tbody tr').forEach(row => {
                currentOrder.push(row.getAttribute('data-question-id'));
            });

            if (JSON.stringify(initialOrder) !== JSON.stringify(currentOrder)) {
                document.getElementById('saveOrderBtn').classList.remove('btn-disabled');
                document.getElementById('saveOrderBtn').disabled = false;
            } else {
                document.getElementById('saveOrderBtn').classList.add('btn-disabled');
                document.getElementById('saveOrderBtn').disabled = true;
            }
        }

        const tables = document.querySelectorAll('.draggable-table tbody');
        tables.forEach(table => {
            let draggedRow = null;

            table.addEventListener('dragstart', function (e) {
                draggedRow = e.target;
                e.target.classList.add('dragging');
            });

            table.addEventListener('dragover', function (e) {
                e.preventDefault();
                const afterElement = getDragAfterElement(table, e.clientY);
                const draggable = document.querySelector('.dragging');
                if (afterElement == null) {
                    table.appendChild(draggable);
                } else {
                    table.insertBefore(draggable, afterElement);
                }
            });

            table.addEventListener('dragend', function () {
                draggedRow.classList.remove('dragging');
                draggedRow = null;
                checkOrderChange(); 
            });
        });

        function getDragAfterElement(table, y) {
            const draggableElements = [...table.querySelectorAll('tr:not(.dragging)')];
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

        document.getElementById('saveOrderBtn').addEventListener('click', function () {
            const tables = document.querySelectorAll('.draggable-table tbody');
            let newOrder = [];
            tables.forEach(table => {
                const rows = table.querySelectorAll('tr');
                rows.forEach((row, index) => {
                    const questionId = row.getAttribute('data-question-id');
                    newOrder.push({ questionId: questionId, order: index + 1 });
                });
            });

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'actions/update-order-question.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json;charset=UTF-8');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    displayFlashMessage('Order saved successfully!');
                    initialOrder = newOrder.map(item => item.questionId);
                    checkOrderChange();
                }
            };

            xhr.send(JSON.stringify(newOrder));
        });

        function editQuestion(questionId) {
            // Fetch the updated question text from the DOM dynamically
            const questionRow = document.querySelector(`tr[data-question-id="${questionId}"] span`);
            const currentQuestion = questionRow ? questionRow.innerText : '';

            // Set the modal inputs with the updated values
            document.getElementById('editQuestionId').value = questionId;
            document.getElementById('editQuestionInput').value = currentQuestion;

            // Show the modal
            var editModal = new bootstrap.Modal(document.getElementById('editQuestionModal'));
            editModal.show();
        }

        document.getElementById('editQuestionForm').addEventListener('submit', function(event) {
            event.preventDefault();
            var questionId = document.getElementById('editQuestionId').value;
            var updatedQuestion = document.getElementById('editQuestionInput').value;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'actions/update-question.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    if (xhr.responseText.trim() === "Question updated successfully!") {
                        var editModal = bootstrap.Modal.getInstance(document.getElementById('editQuestionModal'));
                        editModal.hide();

                        // Update the question text in the table immediately after editing
                        document.querySelector('tr[data-question-id="' + questionId + '"] span').innerText = updatedQuestion;

                        displayFlashMessage('Question updated successfully!');
                    } else {
                        displayFlashMessage('Error: ' + xhr.responseText);
                    }
                }
            };

            xhr.send('editQuestionId=' + encodeURIComponent(questionId) + '&editQuestionInput=' + encodeURIComponent(updatedQuestion));
        });


        function openDeleteModal(questionId) {
            document.getElementById('deleteQuestionId').value = questionId;
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteQuestionModal'));
            deleteModal.show();
        }

        document.getElementById('confirmDeleteButton').addEventListener('click', function() {
            var questionId = document.getElementById('deleteQuestionId').value;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'actions/delete-question.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteQuestionModal'));
                    deleteModal.hide();
                    document.querySelector('tr[data-question-id="' + questionId + '"]').remove();
                    displayFlashMessage('Question deleted successfully!');
                }
            };

            xhr.send('questionId=' + encodeURIComponent(questionId));
        });

        function displayFlashMessage(message) {
            let flashMessage = document.getElementById('flashMessage');
            
            if (!flashMessage) {
                flashMessage = document.createElement('div');
                flashMessage.id = 'flashMessage';
                flashMessage.className = 'flash-message';
                document.body.appendChild(flashMessage);
            }

            flashMessage.innerText = message;
            flashMessage.style.display = 'block';
            setTimeout(() => {
                flashMessage.style.opacity = '1';
            }, 100);
            setTimeout(() => {
                flashMessage.style.opacity = '0';
                setTimeout(() => {
                    flashMessage.style.display = 'none';
                }, 500);
            }, 3000);
        }
    </script>
</body>
</html>
