<?php
// Start session
session_start();

// Include required files
require_once 'db_conn.php';
require_once 'header.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // If user is logged in, redirect to dashboard based on role
    $user_role = $_SESSION['user_role'] ?? 'guest';
    
    if ($user_role === 'admin') {
        header('Location: admin/admin-dashboard.php');
    } else {
        header('Location: landing-page.html');
    }
    exit();
} else {
    // If user is not logged in, show the landing page
    include 'landing-page.html';
}

