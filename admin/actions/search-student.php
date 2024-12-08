<?php
include('../../db_conn.php');

$term = isset($_GET['term']) ? $_GET['term'] : '';

$query = "SELECT student_id, CONCAT(firstName, ' ', lastName) AS student_name FROM student_account WHERE CONCAT(firstName, ' ', lastName) LIKE ? LIMIT 10";
$stmt = $conn->prepare($query);
$term = '%' . $term . '%';
$stmt->bind_param('s', $term);
$stmt->execute();
$result = $stmt->get_result();

$suggestions = array();

while ($row = $result->fetch_assoc()) {
    $suggestions[] = [
        'id' => $row['student_id'],
        'student_name' => $row['student_name']
    ];
}

echo json_encode($suggestions);
?>
