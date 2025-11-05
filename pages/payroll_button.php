<?php
include '../config/db.php';
include '../includes/holidays.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
?>

<!-- Submit Button Container -->
<div class="submit-container" style="text-align: right; margin: 20px 0;">
    <button id="submitPayroll" class="btn" type="button">
        <i class="fas fa-paper-plane"></i> Submit Payroll
    </button>
</div>

<!-- Script for submit functionality -->
<script src="payroll_submit.js"></script>