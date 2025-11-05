<?php
// Payroll System Entry Point
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // User is logged in, redirect to dashboard
    header("Location: pages/dashboard.php");
    exit;
} else {
    // User is not logged in, redirect to login page
    header("Location: pages/login.php");
    exit;
}
?>