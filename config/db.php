<?php
/**
 * Database Configuration and Helper Functions
 * SDSC Payroll System
 */

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'payroll_db');

/**
 * Database connection
 */
try {
    // Set connection options
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Enable exception throwing
    
    $conn = mysqli_init();
    if (!$conn) {
        throw new Exception("mysqli_init failed");
    }

    // Set timeout options
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    $conn->options(MYSQLI_OPT_READ_TIMEOUT, 60);
    
    // Establish connection
    $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Set character set
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

/**
 * Helper Functions
 */

// Sanitize input data
function sanitize($conn, $data) {
    if (is_array($data)) {
        return array_map(function($item) use ($conn) {
            return sanitize($conn, $item);
        }, $data);
    }
    return $conn->real_escape_string(trim($data));
}

// Format currency
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

/**
 * Employee Functions
 */

// Get employee details with position and department
function getEmployeeDetails($conn, $emp_id) {
    $query = "SELECT 
                e.*, 
                p.name as position_name, 
                p.salary as base_salary,
                d.name as department_name
              FROM employees e
              JOIN positions p ON e.position_id = p.id
              JOIN departments d ON e.department_id = d.id
              WHERE e.id = ?";
    
    try {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $emp_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (Exception $e) {
        error_log("Error in getEmployeeDetails: " . $e->getMessage());
        return false;
    }
}

/**
 * Payroll Calculation Functions
 */

// Calculate hourly rate from monthly salary
function calculateHourlyRate($monthlySalary) {
    $workingDaysPerMonth = 22; // Standard working days
    $hoursPerDay = 8; // Standard hours per day
    return $monthlySalary / ($workingDaysPerMonth * $hoursPerDay);
}

// Calculate total work hours
function calculateWorkHours($daysWorked, $absentHours = 0) {
    $hoursPerDay = 8;
    $totalHours = $daysWorked * $hoursPerDay;
    return max(0, $totalHours - $absentHours);
}

// Calculate payroll
function calculatePayroll($employeeId, $daysWorked, $absentHours = 0, $lateMinutes = 0) {
    global $conn;
    
    // Get employee details
    $employee = getEmployeeDetails($conn, $employeeId);
    if (!$employee) {
        return false;
    }

    // Calculate rates and hours
    $hourlyRate = calculateHourlyRate($employee['base_salary']);
    $actualHours = calculateWorkHours($daysWorked, $absentHours);
    
    // Calculate gross pay
    $grossPay = $hourlyRate * $actualHours;
    
    // Deduct tardiness (₱1 per minute)
    $tardinessDeduction = $lateMinutes * 1;
    $grossPay = max(0, $grossPay - $tardinessDeduction);
    
    // Calculate deductions
    $tax = $grossPay * 0.10; // 10% tax
    $sss = $grossPay * 0.045; // 4.5% SSS
    $philhealth = $grossPay * 0.03; // 3% PhilHealth
    $pagibig = 100; // Fixed Pag-IBIG
    
    $totalDeductions = $tax + $sss + $philhealth + $pagibig;
    $netPay = $grossPay - $totalDeductions;
    
    return [
        'employee' => $employee,
        'computation' => [
            'days_worked' => $daysWorked,
            'hours_per_day' => 8,
            'total_hours' => $daysWorked * 8,
            'absent_hours' => $absentHours,
            'actual_hours' => $actualHours,
            'hourly_rate' => $hourlyRate,
            'late_minutes' => $lateMinutes,
            'tardiness_deduction' => $tardinessDeduction,
            'gross_pay' => $grossPay,
            'deductions' => [
                'tax' => $tax,
                'sss' => $sss,
                'philhealth' => $philhealth,
                'pagibig' => $pagibig,
                'total' => $totalDeductions
            ],
            'net_pay' => $netPay
        ]
    ];
}

/**
 * Database Error Logging
 */
function logDatabaseError($error, $query = '') {
    $logFile = __DIR__ . '/database_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $message = "[$timestamp] Error: $error" . ($query ? "\nQuery: $query" : "") . "\n\n";
    error_log($message, 3, $logFile);
}
?>