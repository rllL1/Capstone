<?php
// Include database connection
require_once '../config/db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if it's a POST request and CSRF token is valid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    
    // Sanitize and validate inputs
    $emp_id = filter_var($_POST['emp_id'], FILTER_VALIDATE_INT);
    $pay_date = htmlspecialchars($_POST['pay_date'], ENT_QUOTES, 'UTF-8');
    $basic_pay = filter_var($_POST['basic_pay'], FILTER_VALIDATE_FLOAT);
    $work_hours = filter_var($_POST['work_hours'], FILTER_VALIDATE_FLOAT);
    $overtime_hours = filter_var($_POST['overtime_hours'] ?? 0, FILTER_VALIDATE_FLOAT);
    $late_minutes = filter_var($_POST['late_minutes'] ?? 0, FILTER_VALIDATE_INT);
    $absent_hours = filter_var($_POST['absent_hours'] ?? 0, FILTER_VALIDATE_FLOAT);
    
    // Get pay period dates
    $pay_period_start = filter_var($_POST['pay_period_start'], FILTER_SANITIZE_STRING) ?? date('Y-m-01');
    $pay_period_end = filter_var($_POST['pay_period_end'], FILTER_SANITIZE_STRING) ?? date('Y-m-t');

    // Validate required fields
    if (!$emp_id || !$pay_date || !$basic_pay || !$work_hours) {
        $_SESSION['error_msg'] = 'All required fields must be filled out';
        header('Location: payroll.php');
        exit();
    }

    try {
        // Begin transaction
        $conn->begin_transaction();

        // Payroll calculation constants
        $STANDARD_HOURS = 176;     // 22 days * 8 hours
        $OVERTIME_RATE = 1.25;
        $SSS_RATE = 0.045;         // 4.5%
        $PHILHEALTH_RATE = 0.03;   // 3%
        $PAGIBIG_FIXED = 100;
        
        // Calculate rates
        $hourly_rate = $basic_pay / $STANDARD_HOURS;
        $minute_rate = $hourly_rate / 60;
        
        // Calculate pay components
        $regular_pay = ($work_hours - $absent_hours) * $hourly_rate;
        $overtime_pay = $overtime_hours * $hourly_rate * $OVERTIME_RATE;
        $late_deduction = $late_minutes * $minute_rate;
        
        // Calculate gross pay
        $gross_pay = max(0, $regular_pay + $overtime_pay - $late_deduction);
        
        // Get tax rate from POST or calculate
        $tax_rate = isset($_POST['tax_rate']) ? floatval($_POST['tax_rate']) / 100 : 0.10;
        
        // Calculate deductions
        $sss = $gross_pay * $SSS_RATE;
        $philhealth = $gross_pay * $PHILHEALTH_RATE;
        $pagibig = $PAGIBIG_FIXED;
        $tax = $gross_pay * $tax_rate;
        $total_deductions = $sss + $philhealth + $pagibig + $tax;
        
        // Calculate net pay
        $net_pay = $gross_pay - $total_deductions;
        
        // Calculate working days and hours
        $days_worked = $work_hours / 8;
        $hours_per_day = 8;
        $total_hours = $work_hours;
        $actual_hours = $work_hours - $absent_hours;

        // Insert into payroll_records table
        $stmt = $conn->prepare("INSERT INTO payroll_records (
            employee_id, pay_period_start, pay_period_end, days_worked,
            hours_per_day, total_hours, absent_hours, actual_hours,
            hourly_rate, late_minutes, tardiness_deduction,
            gross_pay, tax_deduction, sss_deduction,
            philhealth_deduction, pagibig_deduction,
            total_deductions, net_pay, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // Bind parameters
        $stmt->bind_param("issddddddddddddddd",
            $emp_id,
            $pay_period_start,
            $pay_period_end,
            $days_worked,
            $hours_per_day,
            $total_hours,
            $absent_hours,
            $actual_hours,
            $hourly_rate,
            $late_minutes,
            $late_deduction,
            $gross_pay,
            $tax,
            $sss,
            $philhealth,
            $pagibig,
            $total_deductions,
            $net_pay
        );

        if (!$stmt->execute()) {
            throw new Exception("Error saving payroll: " . $stmt->error);
        }

        $record_id = $conn->insert_id;
        $stmt->close();

        // Commit transaction
        $conn->commit();
        
        // Set success message
        $_SESSION['success_msg'] = "Payroll record saved successfully! Record ID: " . $record_id;
        header('Location: payroll.php');
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error_msg'] = "Error saving payroll: " . $e->getMessage();
        error_log("Payroll Error: " . $e->getMessage());
        header('Location: payroll.php');
        exit();
    }
    
} else {
    $_SESSION['error_msg'] = "Invalid request or CSRF token mismatch";
    header('Location: payroll.php');
    exit();
}
?>
