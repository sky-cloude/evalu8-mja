<?php
session_start();
include('../../db_conn.php');

if (isset($_GET['id'])) {
    $admin_id = mysqli_real_escape_string($conn, $_GET['id']);

    // Ensure that the session contains the logged-in admin's ID
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['message'] = 'Unauthorized action. Please log in.';
        $_SESSION['message_type'] = 'danger';
        echo json_encode(['success' => false, 'error' => 'Unauthorized action.']);
        exit;
    }

    // Get the currently logged-in admin's ID
    $current_admin_id = $_SESSION['user_id'];

    // Check if the admin being deleted is the logged-in admin
    if ($admin_id == $current_admin_id) {
        $_SESSION['message'] = 'You cannot delete your own account while logged in.';
        $_SESSION['message_type'] = 'danger';
        echo json_encode(['success' => false, 'error' => 'You cannot delete your own account while logged in.']);
        exit; // Stop the script to prevent deletion
    }

    // Proceed with deletion if it's not the logged-in admin
    $query = "DELETE FROM admin_account WHERE admin_id = '$admin_id'";

    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = 'Admin account deleted successfully.';
        $_SESSION['message_type'] = 'success';
        echo json_encode(['success' => true]);
    } else {
        // Capture MySQL error message
        $error_message = mysqli_error($conn);
        $_SESSION['message'] = 'Error deleting admin account: ' . $error_message;
        $_SESSION['message_type'] = 'danger';
        echo json_encode(['success' => false, 'error' => $error_message]);
    }
} else {
    $_SESSION['message'] = 'No admin ID provided.';
    $_SESSION['message_type'] = 'danger';
    echo json_encode(['success' => false, 'error' => 'No admin ID provided.']);
}

mysqli_close($conn);
