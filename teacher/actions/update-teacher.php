<?php
session_start();
include('../../db_conn.php');

header('Content-Type: application/json'); // Ensure the response is in JSON

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Session expired. Please log in again.']);
        exit();
    }

    // Get teacher ID from session
    $id = intval($_SESSION['user_id']);

    // Retrieve form data
    $firstName = mysqli_real_escape_string($conn, $_POST['firstName']);
    $middleName = mysqli_real_escape_string($conn, $_POST['middleName']);
    $lastName = mysqli_real_escape_string($conn, $_POST['lastName']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

    // Initialize avatar as null
    $avatar = null;

    // Initialize MIME type
    $mimeType = null;

    // Check if a file was uploaded via file input
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        // Get file information
        $avatarTmpName = $_FILES['avatar']['tmp_name'];
        $avatarMimeType = mime_content_type($avatarTmpName); // Get MIME type of file

        // Check if the uploaded file is a valid image type
        if ($avatarMimeType == 'image/jpeg' || $avatarMimeType == 'image/png') {
            $avatar = file_get_contents($avatarTmpName); // Get file content
            $mimeType = $avatarMimeType;
        }
    }

    // Check if cropped image data is available and process it
    if (!empty($_POST['croppedImageData'])) {
        $avatarData = $_POST['croppedImageData'];
        
        // Handle both JPEG and PNG base64 strings
        if (strpos($avatarData, 'data:image/jpeg;base64,') !== false) {
            $avatarData = str_replace('data:image/jpeg;base64,', '', $avatarData);
            $mimeType = 'image/jpeg';
        } elseif (strpos($avatarData, 'data:image/png;base64,') !== false) {
            $avatarData = str_replace('data:image/png;base64,', '', $avatarData);
            $mimeType = 'image/png';
        } else {
            $mimeType = 'image/jpeg'; // Default to JPEG if MIME type not found
        }
        
        $avatarData = str_replace(' ', '+', $avatarData); // Ensure correct base64 format
        $avatar = base64_decode($avatarData); // Decode base64 to binary
    }

    // Check if the email already exists in other accounts
    $emailCheckQuery = "
        SELECT email FROM admin_account WHERE email = '$email'
        UNION
        SELECT email FROM teacher_account WHERE email = '$email' AND teacher_id != '$id'
        UNION
        SELECT email FROM staff_account WHERE email = '$email'
        UNION
        SELECT email FROM student_account WHERE email = '$email'
    ";
    $emailCheckResult = mysqli_query($conn, $emailCheckQuery);

    if (mysqli_num_rows($emailCheckResult) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'The email address is already in use by another account.']);
        exit();
    }

    // Build the SQL UPDATE query for the teacher account
    $updateQuery = "UPDATE teacher_account SET 
        firstName='$firstName', 
        middleName='$middleName', 
        lastName='$lastName', 
        email='$email',
        department='$department'";

    // Update password only if provided
    if ($password) {
        $updateQuery .= ", password='$password'";
    }

    // Update avatar only if a new image is uploaded and cropped
    if ($avatar) {
        $avatarEscaped = mysqli_real_escape_string($conn, $avatar); // Escape the binary data for safe SQL insertion
        $updateQuery .= ", avatar='$avatarEscaped'";
    }

    // Complete the SQL query
    $updateQuery .= " WHERE teacher_id='$id'";

    // Execute the query
    if (mysqli_query($conn, $updateQuery)) {
        // Get the updated data
        $selectQuery = "SELECT firstName, lastName, avatar FROM teacher_account WHERE teacher_id = '$id'";
        $selectResult = mysqli_query($conn, $selectQuery);
        if ($selectResult && mysqli_num_rows($selectResult) > 0) {
            $updatedRow = mysqli_fetch_assoc($selectResult);
            $updatedName = strtoupper($updatedRow['lastName']) . ", " . strtoupper($updatedRow['firstName']);

            // If avatar was updated, include the new avatar URL in the response
            if ($avatar) {
                $newAvatarUrl = 'data:' . $mimeType . ';base64,' . base64_encode($avatar);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Account updated successfully.',
                    'newAvatarUrl' => $newAvatarUrl,
                    'updatedName' => $updatedName
                ]);
            }
             else {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Account updated successfully.',
                    'updatedName' => $updatedName
                ]);
            }
            exit();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to retrieve updated data.']);
            exit();
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update account. Error: ' . mysqli_error($conn)]);
        exit();
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}
