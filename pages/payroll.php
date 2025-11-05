<?php 
include '../config/db.php';
include '../includes/holidays.php';
session_start();

?>
<style>
    /* Salary computation input styles */
    #gross_pay {
        width: 100%;
        padding: 12px;
        margin-bottom: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 18px;
        text-align: right;
        font-family: 'Courier New', monospace;
        background-color: #f9f9f9;
        color: #333;
        font-weight: bold;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
    }

    #gross_pay:focus {
        border-color: #4CAF50;
        outline: none;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.1), 0 0 5px rgba(76,175,80,0.5);
    }

    /* Salary Computation Styles */
    .submit-container {
        margin-top: 1rem;
        display: flex;
        justify-content: flex-end;
    }

    .submit-btn {
        padding: 0.75rem 1.25rem;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: #ffffff;
        border: none;
        border-radius: 0.375rem;
        font-size: 0.95rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .submit-btn i {
        color: #ffffff;
        opacity: 0.9;
    }

    .submit-btn:hover {
        background: linear-gradient(135deg, var(--primary-dark), var(--primary-dark));
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.15);
    }

    .submit-btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .submit-btn:hover {
        background-color: #45a049;
    }

    .submit-btn i {
        font-size: 0.9rem;
    }
    .calculation-box {
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 1rem;
        margin-bottom: 0.5rem;
    }

    .calculation-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #eee;
    }

    .calculation-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .calc-label {
        color: #666;
        font-weight: 500;
    }

    .calc-value {
        text-align: right;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .calc-value span {
        display: block;
    }

    .calc-value .formula {
        font-size: 0.85rem;
        color: #666;
        margin-bottom: 0.25rem;
    }

    .amount {
        font-family: 'Courier New', monospace;
        font-weight: bold;
        color: #2c3e50;
    }

    .total-computation {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 2px solid #ddd;
    }

    .total-computation .calc-label {
        font-weight: bold;
        color: #2c3e50;
    }

    .total-computation input {
        width: 150px;
        text-align: right;
        font-family: 'Courier New', monospace;
        font-weight: bold;
        background-color: white;
    }

    .form-group label {
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 8px;
        display: block;
    }

    .form-group .input-help {
        color: #7f8c8d;
        font-size: 0.85em;
        margin-top: 5px;
        display: block;
    }
</style>

<!-- Records Modal -->
<!-- Records Modal -->
<div id="recordsModal" class="modal" style="display: none;">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payroll Records</h5>
                <button type="button" class="close-modal" onclick="closeRecordsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table" id="recordsTable">
                        <thead>
                            <tr>
                                <th>Reference No.</th>
                                <th>Employee Name</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Pay Date</th>
                                <th>Net Pay</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="recordsTableBody">
                            <?php
                            // Fetch payroll records with employee details
                            $records_query = "
                                SELECT p.*, e.emp_name, e.department, e.position 
                                FROM payrolls p 
                                JOIN employees e ON e.id = p.emp_id 
                                ORDER BY p.pay_date DESC, p.id DESC
                            ";
                            $records_result = $conn->query($records_query);

                            while ($record = $records_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $record['id'] ?></td>
                                    <td><?= htmlspecialchars($record['emp_name']) ?></td>
                                    <td><?= htmlspecialchars($record['department']) ?></td>
                                    <td><?= htmlspecialchars($record['position']) ?></td>
                                    <td><?= date('M d, Y', strtotime($record['pay_date'])) ?></td>
                                    <td>₱<?= number_format($record['net_pay'], 2) ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="#" onclick="viewPayslip(<?= $record['id'] ?>)" class="btn-view">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="#" onclick="deleteRecord(<?= $record['id'] ?>)" class="btn-delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payslip Modal -->
<div class="modal fade" id="payslipModal" tabindex="-1" aria-labelledby="payslipModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="payslipModalLabel">Payslip Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="payslip-details">
                    <div class="row mb-3">
                        <div class="col">
                            <strong>Reference No:</strong> <span id="payslip_ref_no"></span>
                        </div>
                        <div class="col">
                            <strong>Pay Date:</strong> <span id="payslip_date"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <strong>Employee Name:</strong> <span id="payslip_name"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <strong>Department:</strong> <span id="payslip_department"></span>
                        </div>
                        <div class="col">
                            <strong>Position:</strong> <span id="payslip_position"></span>
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col">
                            <strong>Basic Pay:</strong> <span id="payslip_basic"></span>
                        </div>
                        <div class="col">
                            <strong>Hours Worked:</strong> <span id="payslip_hours"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <strong>Overtime Hours:</strong> <span id="payslip_ot"></span>
                        </div>
                        <div class="col">
                            <strong>Gross Pay:</strong> <span id="payslip_gross"></span>
                        </div>
                    </div>
                    <hr>
                    <div class="deductions">
                        <h6 class="mb-3">Deductions:</h6>
                        <div class="row mb-2">
                            <div class="col">SSS:</div>
                            <div class="col text-end" id="payslip_sss"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col">PhilHealth:</div>
                            <div class="col text-end" id="payslip_philhealth"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col">Pag-IBIG:</div>
                            <div class="col text-end" id="payslip_pagibig"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col">Tax:</div>
                            <div class="col text-end" id="payslip_tax"></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col"><strong>Total Deductions:</strong></div>
                            <div class="col text-end" id="payslip_deductions"></div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col">
                            <strong>Net Pay:</strong> <span id="payslip_net" class="text-success fs-5"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// Security checks
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch unique departments directly from employees table
$departments = $conn->query("SELECT DISTINCT TRIM(REPLACE(department, '\r\n', '')) as name FROM employees WHERE deleted_at IS NULL ORDER BY name ASC");
if (!$departments) {
    error_log("Error fetching departments: " . $conn->error);
}

// Initialize the employee array
$employeesByDept = array();

error_log("Starting employee data collection...");

// Debug: Print all table names
$tables_query = "SHOW TABLES";
$tables_result = $conn->query($tables_query);
error_log("Available tables in database:");
while ($table = $tables_result->fetch_array()) {
    error_log($table[0]);
}

// Debug: Check employees table structure
$structure_query = "DESCRIBE employees";
$structure_result = $conn->query($structure_query);
error_log("\nEmployees table structure:");
while ($column = $structure_result->fetch_assoc()) {
    error_log(print_r($column, true));
}

// Get employees with their department and position info
$query = "
    SELECT 
        e.*,
        d.name as department_name,
        p.name as position_name,
        p.salary as position_salary
    FROM employees e
    LEFT JOIN departments d ON d.id = e.department_id
    LEFT JOIN positions p ON p.id = e.position_id
    WHERE e.deleted_at IS NULL 
    ORDER BY d.name ASC, e.emp_name ASC
";

error_log("Executing query: " . $query);

try {
    // Execute the main query
    $result = $conn->query($query);
    if (!$result) {
        throw new Exception($conn->error);
    }

    error_log("Found " . $result->num_rows . " employees");
    
    // Debug: Print first employee's data
    if ($first_emp = $result->fetch_assoc()) {
        error_log("First employee data: " . print_r($first_emp, true));
        $result->data_seek(0); // Reset pointer
    } else {
        error_log("No employees found in result set");
    }

    // Process employees
    while ($row = $result->fetch_assoc()) {
        $dept_id = $row['department_id'];
        
        // Initialize department array if needed
        if (!isset($employeesByDept[$dept_id])) {
            $employeesByDept[$dept_id] = array();
        }
        
        // Add employee to department array
        $employeesByDept[$dept_id][] = array(
            'id' => $row['id'],
            'name' => $row['emp_name'],
            'position' => $row['position_name'],
            'salary' => $row['salary']
        );
        
        error_log("Added employee: {$row['emp_name']} to department {$dept_id}");
    }
    
    error_log("Processed departments: " . implode(", ", array_keys($employeesByDept)));
    error_log("Total employees by department: " . print_r($employeesByDept, true));
    
} catch (Exception $e) {
    error_log("Error processing employees: " . $e->getMessage());
    $employeesByDept = array();
}

// --- HELPER FUNCTIONS ---
function sanitize_numeric($value, $min = 0, $default = 0) {
    $value = filter_var($value, FILTER_VALIDATE_FLOAT);
    return ($value !== false && $value >= $min) ? $value : $default;
}

function get_working_days($month, $year) {
    $working_days = 0;
    $holidays = get_holidays($month, $year);
    $num_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    for ($day = 1; $day <= $num_days; $day++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $weekday = date('N', strtotime($date));
        
        // Check if it's a weekday (1-5 represents Monday-Friday)
        if ($weekday <= 5 && !in_array($date, array_column($holidays, 'date'))) {
            $working_days++;
        }
    }
    
    return $working_days;
}

function get_holiday_pay($basic_pay, $holidays) {
    $daily_rate = $basic_pay / 22; // Standard monthly days
    $holiday_pay = 0;
    
    foreach ($holidays as $holiday) {
        switch ($holiday['type']) {
            case 'regular':
                $holiday_pay += $daily_rate * 2; // 200% of daily rate
                break;
            case 'special':
                $holiday_pay += $daily_rate * 1.3; // 130% of daily rate
                break;
        }
    }
    
    return round($holiday_pay, 2);
}

// --- PAYROLL COMPUTATION FUNCTIONS ---

// Constants for payroll calculations
const WORKING_DAYS_PER_MONTH = 26;  // Standard working days per month
const HOURS_PER_DAY = 8;            // Standard hours per day
const MINUTES_PER_HOUR = 60;        // Minutes in an hour
const OVERTIME_RATE = 1.25;         // 25% additional for overtime
const SSS_RATE = 0.045;             // 4.5% SSS
const PHILHEALTH_RATE = 0.025;      // 2.5% PhilHealth
const PAGIBIG_RATE = 0.02;          // 2% Pag-IBIG
const PAGIBIG_MAX = 100;            // Maximum Pag-IBIG contribution
const TAX_THRESHOLD = 20000;        // Monthly salary threshold for tax
const TAX_RATE = 0.10;              // 10% tax for salaries above threshold

// Helper function to calculate hourly rate
function calculate_hourly_rate($monthly_salary) {
    return $monthly_salary / (WORKING_DAYS_PER_MONTH * HOURS_PER_DAY);
}

// Helper function to calculate days from hours
function calculate_working_days($hours) {
    return round($hours / HOURS_PER_DAY, 2);
}

// Main computation function with improved validation and calculations
function compute_payroll($monthly_salary, $hours_worked, $absent_hours = 0, $late_minutes = 0, $overtime_hours = 0) {
    // Sanitize and validate all inputs
    $monthly_salary = sanitize_numeric($monthly_salary, 0.01);
    $hours_worked = sanitize_numeric($hours_worked);
    $absent_hours = sanitize_numeric($absent_hours);
    $late_minutes = sanitize_numeric($late_minutes);
    $overtime_hours = sanitize_numeric($overtime_hours);

    // Return error if basic validation fails
    if ($monthly_salary <= 0 || $hours_worked < 0) {
        return [
            'status' => 'error',
            'message' => 'Invalid salary or work hours',
            'gross' => 0,
            'work_days' => 0,
            'deductions' => 0,
            'net_pay' => 0
        ];
    }

    // Calculate rates using new formulas
    $daily_rate = $monthly_salary / WORKING_DAYS_PER_MONTH;
    $hourly_rate = $daily_rate / HOURS_PER_DAY;
    $minute_rate = $hourly_rate / MINUTES_PER_HOUR;
    
    // 1. Calculate Working Days
    $work_days = $hours_worked / HOURS_PER_DAY;
    
    // 2. Regular Hours Pay Calculation
    $regular_hours = $hours_worked;
    $regular_pay = round($regular_hours * $hourly_rate, 2);
    
    // 3. Overtime Pay Calculation
    $overtime_pay = 0;
    if ($overtime_hours > 0) {
        $overtime_rate = $hourly_rate * OVERTIME_RATE;
        $overtime_pay = round($overtime_hours * $overtime_rate, 2);
    }
    
    // 4. Calculate Gross Pay (regular + overtime)
    $gross_pay = $regular_pay + $overtime_pay;
    
    // 5. Calculate Attendance Deductions using new formulas
    // Initialize deduction variables
    $late_deduction = 0;
    $absent_deduction = 0;
    $attendance_deductions = 0;
    error_log("Initializing deduction calculations...");

    // Calculate late minutes deduction if any
    if ($late_minutes > 0) {
        // Late deduction = (Monthly Salary/26/8/60) * late_minutes
        $per_minute = ($monthly_salary / WORKING_DAYS_PER_MONTH / HOURS_PER_DAY / MINUTES_PER_HOUR);
        $late_deduction = $per_minute * $late_minutes;
        $late_deduction = round($late_deduction, 2);
        error_log("Late calculation: Rate per minute: $per_minute, Minutes: $late_minutes, Deduction: $late_deduction");
    }

    // Calculate absent hours deduction if any
    if ($absent_hours > 0) {
        // Absent deduction = (Monthly Salary/26/8) * absent_hours
        $per_hour = ($monthly_salary / WORKING_DAYS_PER_MONTH / HOURS_PER_DAY);
        $absent_deduction = $per_hour * $absent_hours;
        $absent_deduction = round($absent_deduction, 2);
        error_log("Absent calculation: Rate per hour: $per_hour, Hours: $absent_hours, Deduction: $absent_deduction");
    }

    // Sum up attendance deductions
    $attendance_deductions = $late_deduction + $absent_deduction;
    error_log("Total Attendance Deductions: Late($late_deduction) + Absent($absent_deduction) = $attendance_deductions");

    // 6. Calculate Mandatory Deductions
    // Calculate prorated monthly salary based on working days
    $total_working_days = WORKING_DAYS_PER_MONTH;
    $actual_working_days = $hours_worked / HOURS_PER_DAY;
    $working_days_percentage = $actual_working_days / $total_working_days;
    $prorated_monthly_salary = $monthly_salary * $working_days_percentage;
    
    // First determine tax based on prorated monthly salary
    $tax = 0;
    $tax_status = "EXEMPT (₱0-₱20,000)";
    
    // Check prorated monthly salary for tax calculation
    if ($prorated_monthly_salary <= 20000) {
        $tax = 0;
        $tax_status = "EXEMPT (₱0-₱20,000)";
    } else {
        $tax = round($prorated_monthly_salary * TAX_RATE, 2);
        $tax_status = "TAXABLE (Above ₱20,000)";
    }
    
    error_log("Tax Calculation Based on Working Days:");
    error_log("Total Working Days: " . WORKING_DAYS_PER_MONTH);
    error_log("Actual Working Days: " . $actual_working_days);
    error_log("Working Days Percentage: " . ($working_days_percentage * 100) . "%");
    error_log("Base Monthly Salary: ₱" . number_format($monthly_salary, 2));
    error_log("Prorated Monthly Salary: ₱" . number_format($prorated_monthly_salary, 2));
    
    error_log("Tax Calculation:");
    error_log("Monthly Base Salary: ₱" . number_format($monthly_salary, 2));
    error_log("Tax Status: $tax_status");
    error_log("Tax Amount: ₱" . number_format($tax, 2));    // SSS (4.5%)
    $sss = round($gross_pay * SSS_RATE, 2);
    
    // PhilHealth (2.5%)
    $philhealth = round($gross_pay * PHILHEALTH_RATE, 2);
    
    // Pag-IBIG (2% with 100 peso maximum)
    $pagibig = round($gross_pay * PAGIBIG_RATE, 2);
    $pagibig = min($pagibig, PAGIBIG_MAX);
    
    // 7. Calculate Total Deductions
    // Sum up government deductions
    $statutory_deductions = $tax + $sss + $philhealth + $pagibig;
    
    // Calculate final total deductions (government + attendance)
    $total_deductions = $statutory_deductions + $attendance_deductions;
    $total_deductions = round($total_deductions, 2);
    
    error_log("\nDETAILED DEDUCTIONS BREAKDOWN:");
    error_log("----------------------------------------");
    error_log("1. Government Deductions:");
    error_log("   - Tax: ₱" . number_format($tax, 2) . " ($tax_status)");
    error_log("   - SSS (4.5%): ₱" . number_format($sss, 2));
    error_log("   - PhilHealth (2.5%): ₱" . number_format($philhealth, 2));
    error_log("   - Pag-IBIG (2%): ₱" . number_format($pagibig, 2));
    error_log("   Subtotal (Government): ₱" . number_format($statutory_deductions, 2));
    error_log("----------------------------------------");
    error_log("2. Attendance Deductions:");
    error_log("   - Late ($late_minutes mins): ₱" . number_format($late_deduction, 2));
    error_log("   - Absent ($absent_hours hrs): ₱" . number_format($absent_deduction, 2));
    error_log("   Subtotal (Attendance): ₱" . number_format($attendance_deductions, 2));
    error_log("----------------------------------------");
    error_log("TOTAL DEDUCTIONS: ₱" . number_format($total_deductions, 2));
    
    // 8. Calculate Net Pay
    $net_pay = round($gross_pay - $total_deductions, 2);
    error_log("Net Pay Calculation: Gross Pay ($gross_pay) - Total Deductions ($total_deductions) = $net_pay");

    return [
        'status' => 'success',
        'salary_info' => [
            'monthly_salary' => $monthly_salary,
            'daily_rate' => $daily_rate,
            'hourly_rate' => $hourly_rate,
            'minute_rate' => $minute_rate
        ],
        'work_info' => [
            'work_days' => $work_days,
            'hours_worked' => $hours_worked,
            'overtime_hours' => $overtime_hours,
            'absent_hours' => $absent_hours,
            'late_minutes' => $late_minutes
        ],
        'earnings' => [
            'regular_pay' => $regular_pay,
            'overtime_pay' => $overtime_pay,
            'gross_pay' => $gross_pay
        ],
        'deductions' => [
            'attendance' => [
                'late_deduction' => $late_deduction,
                'absent_deduction' => $absent_deduction,
                'total_attendance_deductions' => $attendance_deductions
            ],
            'government' => [
                'tax' => $tax,
                'tax_status' => $tax_status,
                'sss' => $sss,
                'philhealth' => $philhealth,
                'pagibig' => $pagibig,
                'total_statutory_deductions' => $statutory_deductions
            ],
            'total_deductions' => $total_deductions
        ],
        'net_pay' => $net_pay
    ];
}

// --- ADD PAYROLL ---
if (isset($_POST['add_payroll']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    // Sanitize inputs
    $emp_id = filter_var($_POST['emp_id'], FILTER_VALIDATE_INT);
    $pay_date = htmlspecialchars($_POST['pay_date'], ENT_QUOTES, 'UTF-8');
    $basic_pay = sanitize_numeric($_POST['basic_pay'], 0.01);
    $hours_worked = sanitize_numeric($_POST['work_hours'], 0);
    $absent_hours = sanitize_numeric($_POST['absent_hours'] ?? 0);
    $late_minutes = sanitize_numeric($_POST['late_minutes'] ?? 0);
    $overtime_hours = sanitize_numeric($_POST['overtime_hours'] ?? 0);

    if ($emp_id && $pay_date && strtotime($pay_date)) {
        try {
            // Begin transaction
            $conn->begin_transaction();

            // Compute payroll
            $calc = compute_payroll($basic_pay, $hours_worked, $absent_hours, $late_minutes, $overtime_hours);
            
            if ($calc['status'] === 'error') {
                throw new Exception($calc['message']);
            }

            // Prepare and execute insert statement
            $stmt = $conn->prepare("INSERT INTO payrolls 
                (emp_id, pay_date, basic_pay, hours_worked, absent_hours, late_minutes, overtime_hours,
                 overtime_pay, late_deduction, absent_deduction, gross_pay, tax, sss, philhealth, 
                 pagibig, deductions, net_pay, working_days)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param("isdddddddddddddddd",
                $emp_id, $pay_date, $basic_pay, $hours_worked, $absent_hours, $late_minutes, $overtime_hours,
                $calc['earnings']['overtime_pay'], 
                $calc['deductions']['attendance']['late_deduction'], 
                $calc['deductions']['attendance']['absent_deduction'], 
                $calc['earnings']['gross_pay'],
                $calc['deductions']['government']['tax'], 
                $calc['deductions']['government']['sss'], 
                $calc['deductions']['government']['philhealth'], 
                $calc['deductions']['government']['pagibig'],
                $calc['deductions']['total_deductions'], 
                $calc['net_pay'], 
                $calc['work_info']['work_days']
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $stmt->close();
            $conn->commit();

            $_SESSION['success_msg'] = "Payroll record added successfully.";
            header("Location: payroll.php");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_msg'] = "Error: " . $e->getMessage();
            header("Location: payroll.php");
            exit;
        }
    } else {
        $_SESSION['error_msg'] = "Invalid input data provided.";
        header("Location: payroll.php");
        exit;
    }
}

// --- UPDATE PAYROLL ---
if (isset($_POST['update_payroll']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    // Sanitize inputs
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $emp_id = filter_var($_POST['emp_id'], FILTER_VALIDATE_INT);
    $pay_date = filter_var($_POST['pay_date'], FILTER_SANITIZE_STRING);
    $basic_pay = sanitize_numeric($_POST['basic_pay'], 0.01);
    $hours_worked = sanitize_numeric($_POST['work_hours'], 0);
    $absent_hours = sanitize_numeric($_POST['absent_hours'] ?? 0);
    $late_minutes = sanitize_numeric($_POST['late_minutes'] ?? 0);
    $overtime_hours = sanitize_numeric($_POST['overtime_hours'] ?? 0);

    if ($id && $emp_id && $pay_date && strtotime($pay_date)) {
        try {
            // Begin transaction
            $conn->begin_transaction();

            // Compute payroll
            $calc = compute_payroll($basic_pay, $hours_worked, $absent_hours, $late_minutes, $overtime_hours);
            
            if ($calc['status'] === 'error') {
                throw new Exception($calc['message']);
            }

            // Prepare and execute update statement
            $stmt = $conn->prepare("UPDATE payrolls 
                SET emp_id=?, pay_date=?, basic_pay=?, hours_worked=?, absent_hours=?, 
                    late_minutes=?, overtime_hours=?, overtime_pay=?, late_deduction=?, 
                    absent_deduction=?, gross_pay=?, tax=?, sss=?, philhealth=?, pagibig=?, 
                    deductions=?, net_pay=?, working_days=?
                WHERE id=?");
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param("isdddddddddddddddddi",
                $emp_id, $pay_date, $basic_pay, $hours_worked, $absent_hours, $late_minutes, 
                $overtime_hours, 
                $calc['earnings']['overtime_pay'], 
                $calc['deductions']['attendance']['late_deduction'], 
                $calc['deductions']['attendance']['absent_deduction'], 
                $calc['earnings']['gross_pay'],
                $calc['deductions']['government']['tax'], 
                $calc['deductions']['government']['sss'], 
                $calc['deductions']['government']['philhealth'], 
                $calc['deductions']['government']['pagibig'],
                $calc['deductions']['total_deductions'], 
                $calc['net_pay'], 
                $calc['work_info']['work_days'], 
                $id
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $stmt->close();
            $conn->commit();

            $_SESSION['success_msg'] = "Payroll record updated successfully.";
            header("Location: payroll.php");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_msg'] = "Error: " . $e->getMessage();
            header("Location: payroll.php");
            exit;
        }
    } else {
        $_SESSION['error_msg'] = "Invalid input data provided.";
        header("Location: payroll.php");
        exit;
    }
}

// --- DELETE PAYROLL ---
if (isset($_GET['delete']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    
    if ($id) {
        try {
            $conn->begin_transaction();
            
            // Use prepared statement for delete
            $stmt = $conn->prepare("DELETE FROM payrolls WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $stmt->close();
            $conn->commit();
            
            $_SESSION['success_msg'] = "Payroll record deleted successfully.";
            
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_msg'] = "Error deleting record: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_msg'] = "Invalid payroll record ID.";
    }
    
    header("Location: payroll.php");
    exit;
}

// --- EDIT PAYROLL ---
$edit = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    if ($id) $edit = $conn->query("SELECT * FROM payrolls WHERE id=$id")->fetch_assoc();
}

// --- FETCH DATA ---
$payrolls = $conn->query("
    SELECT p.*, e.emp_name, d.name as department 
    FROM payrolls p 
    JOIN employees e ON e.id = p.emp_id
    JOIN departments d ON d.id = e.department 
    ORDER BY p.id DESC
");

// Get employees with their department and position information
$query = "
    SELECT 
        e.id,
        e.emp_name,
        e.department,
        e.position as position_name,
        e.salary as basic_salary
    FROM employees e
    WHERE e.deleted_at IS NULL
    ORDER BY e.department ASC, e.emp_name ASC
";

error_log("Executing employee query: " . $query);
$employees = $conn->query($query);

if (!$employees) {
    error_log("Error fetching employees: " . $conn->error);
}

// Debug: Print the first employee's data
if ($firstEmployee = $employees->fetch_assoc()) {
    error_log("First employee data: " . print_r($firstEmployee, true));
    // Reset the pointer back to the beginning
    $employees->data_seek(0);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Payroll - SDSC</title>
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="green_theme.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="js/payroll_employee_select.js"></script>
<script src="js/payroll_submit_new.js" defer></script>
<!-- Add CSRF token -->
<meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
<!-- Add sidebar handler -->
<script src="js/sidebar_handler.js" defer></script>
<script src="js/payroll.js" defer></script>
<script src="js/payroll_submit_new.js" defer></script>
<script src="js/form-validation.js" defer></script>
<script src="js/employee-validation.js" defer></script>
<script src="js/view_records.js" defer></script>

<!-- Add custom functions -->
<script>
// Function to submit payroll
function submitPayroll() {
    // Get form data
    const form = document.getElementById('payroll_form');
    
    // Validate required fields
    const requiredFields = ['emp_id', 'pay_date', 'basic_pay', 'work_hours'];
    let isValid = true;
    
    requiredFields.forEach(field => {
        const input = form.querySelector(`[name="${field}"]`);
        if (!input || !input.value.trim()) {
            isValid = false;
            // Add visual feedback
            input?.classList.add('is-invalid');
        }
    });

    if (!isValid) {
        Swal.fire({
            icon: 'error',
            title: 'Required Fields Missing',
            text: 'Please fill in all required fields.',
            confirmButtonColor: '#2e7d32'
        });
        return;
    }

    // Show confirmation dialog
    Swal.fire({
        title: 'Confirm Submission',
        text: 'Are you sure you want to submit this payroll?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2e7d32',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, submit it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Add the submit flag
            const formData = new FormData(form);
            formData.append('add_payroll', '1');
            
            // Show loading state
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait while we submit the payroll',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Submit the form
            fetch('save_payroll.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if (data.includes('success')) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Payroll has been submitted successfully.',
                        confirmButtonColor: '#2e7d32'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'There was an error submitting the payroll. Please try again.',
                        confirmButtonColor: '#2e7d32'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'There was an error submitting the payroll. Please try again.',
                    confirmButtonColor: '#2e7d32'
                });
            });
        }
    });
}

// Function to handle view records
function openRecordsModal() {
    const modal = document.getElementById('recordsModal');
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    document.body.appendChild(modalOverlay);
    
    // Show modal with fade-in effect
    modal.style.display = 'block';
    modal.style.opacity = '0';
    modalOverlay.style.display = 'block';
    modalOverlay.style.opacity = '0';
    
    setTimeout(() => {
        modal.style.opacity = '1';
        modalOverlay.style.opacity = '1';
    }, 50);
}

// Function to close records modal
function closeRecordsModal() {
    const modal = document.getElementById('recordsModal');
    const modalOverlay = document.querySelector('.modal-overlay');
    
    // Fade-out effect
    modal.style.opacity = '0';
    if (modalOverlay) {
        modalOverlay.style.opacity = '0';
        setTimeout(() => {
            modal.style.display = 'none';
            modalOverlay.remove();
        }, 300);
    }
}

// Function to delete record
function deleteRecord(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#2e7d32',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            
            // Show loading state
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait while we delete the record',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send delete request
            fetch(`payroll.php?delete=${id}&csrf_token=${csrfToken}`)
            .then(response => response.text())
            .then(data => {
                if (data.includes('success')) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'The payroll record has been deleted.',
                        confirmButtonColor: '#2e7d32'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to delete the record.',
                        confirmButtonColor: '#2e7d32'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to delete the record.',
                    confirmButtonColor: '#2e7d32'
                });
            });
        }
    });
}

// Add event listeners when the document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener for submit button
    const submitBtn = document.querySelector('[data-action="submit-payroll"]');
    if (submitBtn) {
        submitBtn.addEventListener('click', submitPayroll);
    }

    // Add event listener for view records button
    const viewRecordsBtn = document.querySelector('[data-action="view-records"]');
    if (viewRecordsBtn) {
        viewRecordsBtn.addEventListener('click', openRecordsModal);
    }

    // Add event listener for clicking outside modal
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('recordsModal');
        if (event.target === modal) {
            closeRecordsModal();
        }
    });
});</script>
<style>
/* Modal Dialog Styles */
.modal-dialog {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 2rem;
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    z-index: 1000;
    width: 90%;
    max-width: 1100px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 999;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.modal-title {
    margin: 0;
    font-size: 1.25rem;
    color: #2e7d32;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #666;
    cursor: pointer;
    padding: 0.25rem;
    line-height: 1;
}

.modal-body {
    margin-bottom: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
}
    /* Grid Layout for Pay Sections */
    .form-row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 2rem;
        margin-bottom: 1.5rem;
    }

    /* Common Styles for Both Sections */
    .form-group {
        background: white;
        padding: 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        border: 1px solid #e9ecef;
    }

    /* Section Headers */
    .form-group label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #1b5e20;
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 1rem;
    }

    .form-group label i {
        background: #e8f5e9;
        padding: 0.5rem;
        border-radius: 0.375rem;
        color: #2e7d32;
    }

    /* Gross Pay Input and Breakdown Styles */
    #gross_pay {
        width: 100%;
        background-color: #ffffff !important;
        border: 2px solid #2e7d32 !important;
        color: #1b5e20 !important;
        font-weight: 600 !important;
        opacity: 1 !important;
        font-size: 1.2rem !important;
        text-align: right;
        padding: 0.75rem 1rem;
        border-radius: 0.375rem;
        font-family: monospace;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        margin-bottom: 0.5rem;
    }

    .pay-breakdown {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-top: 0.5rem;
        font-size: 0.9rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .breakdown-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        color: #2e7d32;
    }

    .breakdown-item:not(:last-child) {
        border-bottom: 1px dashed #e9ecef;
        margin-bottom: 0.5rem;
        padding-bottom: 0.5rem;
    }

    .breakdown-item.total {
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        border-top: 2px solid #e9ecef;
        font-weight: 600;
        color: #1b5e20;
        font-size: 1rem;
    }

    .breakdown-item span:last-child,
    .deduction-list span {
        font-family: monospace;
        background: #e8f5e9;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-weight: 500;
    }

    /* Deductions Styling */
    .deduction-list {
        width: 100%;
        margin-top: 0.5rem;
        font-size: 0.9rem;
    }

    .deduction-list div {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.25rem 0;
        color: #666;
    }

    .breakdown-item.deductions {
        flex-direction: column;
        align-items: stretch;
    }

    .breakdown-item.subtotal {
        background: #f8f9fa;
        margin: 0.5rem -1rem;
        padding: 0.5rem 1rem;
        border-top: 1px dashed #e9ecef;
        border-bottom: 1px dashed #e9ecef;
    }

    /* Common input styling */
    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid #2e7d32;
        border-radius: 0.375rem;
    }
    
    .is-invalid {
        border-color: #dc3545 !important;
        background-color: #fff8f8 !important;
        box-shadow: 0 0 0 0.2rem rgba(220,53,69,.25) !important;
    }
    
    .is-invalid + .input-help,
    .is-invalid + .invalid-feedback {
        color: #dc3545;
        display: block;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    
    select.is-invalid {
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23dc3545' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e") !important;
        font-size: 1.2rem;
        color: #1b5e20;
        font-weight: 600;
        text-align: right;
        background: white;
        font-family: monospace;
        margin-bottom: 0.5rem;
    }

    .form-control:read-only {
        background: white !important;
        opacity: 1 !important;
    }

    /* Responsive adjustments */
    @media (max-width: 1024px) {
        .form-row {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
    }
</style>
<style>
:root {
  --primary: #1e8f4a;
  --primary-dark: #166c36;
  --secondary: #6c757d;
  --secondary-dark: #545b62;
  --light: #f8f9fa;
  --border: #dee2e6;
  --danger: #dc3545;
  --danger-dark: #c82333;
  --info: #17a2b8;
  --info-dark: #138496;
  --warning: #ffc107;
  --warning-dark: #e0a800;
  --success: #28a745;
  --success-dark: #218838;
  --card-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

body { 
  font-family: "Poppins", sans-serif; 
  background: var(--light); 
  margin: 0; 
  min-height: 100vh;
}

.content-card { 
  background: #fff;
  border-radius: 0.5rem;
  padding: 1.5rem;
  box-shadow: var(--card-shadow);
  margin-bottom: 1.5rem;
  transition: box-shadow 0.3s ease;
}

.content-card:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.section-card {
  padding: 1.25rem;
  margin-bottom: 1.25rem;
}

.section-title {
  font-size: 1.1rem;
  color: #333;
  margin: 0 0 1rem 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #ddd;
  font-weight: 500;
}

.section-title i {
  font-size: 1.1rem;
  color: #333;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
  margin-bottom: 1rem;
}

.form-row:last-child {
  margin-bottom: 0;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

/* Gross Pay Breakdown Styles */
.pay-breakdown {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 6px;
  padding: 12px;
  margin-top: 8px;
  font-size: 0.9rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.breakdown-item {
  display: flex;
  justify-content: space-between;
  padding: 4px 0;
  color: #2e7d32;
}

.breakdown-item:not(:last-child) {
  border-bottom: 1px dashed #e9ecef;
  margin-bottom: 4px;
  padding-bottom: 8px;
}

.breakdown-item.total {
  border-top: 2px solid #e9ecef;
  margin-top: 4px;
  padding-top: 8px;
  font-weight: 600;
  color: #1b5e20;
}

.breakdown-item span:last-child {
  font-family: monospace;
  font-weight: 500;
}

/* Make readonly inputs more visible */
.input-with-breakdown {
      position: relative;
      margin-bottom: 0.5rem;
    }

    #gross_pay {
      width: 100%;
      background-color: #ffffff !important;
      border: 1px solid #2e7d32 !important;
      color: #1b5e20 !important;
      font-weight: 500 !important;
      opacity: 1 !important;
      font-size: 1.1rem;
      text-align: right;
      padding: 0.75rem 1rem;
      border-radius: 0.375rem;
      font-family: monospace;
      transition: all 0.2s ease;
    }

    #gross_pay:focus {
      outline: none;
      box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
    }

    .pay-breakdown {
      margin-top: 0.5rem;
      padding: 1rem;
      background: #f8f9fa;
      border: 1px solid #e9ecef;
      border-radius: 0.375rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .breakdown-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.5rem 0;
      font-size: 0.9rem;
      color: #2e7d32;
    }

    .breakdown-item:not(:last-child) {
      border-bottom: 1px dashed #e9ecef;
    }

    .breakdown-item.total {
      margin-top: 0.5rem;
      padding-top: 0.75rem;
      border-top: 2px solid #e9ecef;
      font-weight: 600;
      color: #1b5e20;
      font-size: 1rem;
    }

    .breakdown-item span:last-child {
      font-family: 'Consolas', monospace;
      font-weight: 500;
      background: #e8f5e9;
      padding: 0.25rem 0.5rem;
      border-radius: 0.25rem;
    }.form-grid input, 
.form-grid select { 
  padding: 0.75rem 1rem;
  border-radius: 0.375rem;
  border: 1px solid var(--border);
  transition: all 0.2s ease-in-out;
  font-size: 0.875rem;
  width: 100%;
}

.form-grid input:focus,
.form-grid select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 0.2rem rgba(30, 143, 74, 0.25);
}

.form-grid label {
  font-size: 0.9rem;
  color: #2e7d32;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 8px;
}

.form-grid label i {
  font-size: 1rem;
}

.input-help {
  font-size: 0.75rem;
  color: var(--secondary);
  margin-top: 0.25rem;
}

.form-grid input:disabled,
.form-grid select:disabled {
  background-color: var(--light);
  cursor: not-allowed;
  opacity: 0.7;
}

    .content-card { 
      background: #fff;
      border-radius: 0.5rem;
      padding: 1.5rem;
      box-shadow: var(--card-shadow);
      margin-bottom: 1.5rem;
      transition: box-shadow 0.3s ease;
    }

    .content-card:hover {
      box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .form-grid { 
      display: grid; 
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
      gap: 1rem; 
      margin-top: 1rem;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      position: relative;
    }

    .form-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-bottom: 1rem;
    }

    @media (max-width: 1200px) {
      .form-row {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 768px) {
      .form-row {
        grid-template-columns: 1fr;
      }
    }

    #employee_group {
      transition: all 0.3s ease;
    }

    .form-group select:disabled {
      background-color: #f5f5f5;
      cursor: not-allowed;
      opacity: 0.5;
    }

    .form-group select {
      height: 42px;
      padding: 0 1rem;
      border: 1px solid var(--border);
      border-radius: 0.375rem;
      background-color: white;
      cursor: pointer;
      font-size: 0.9rem;
      transition: all 0.2s ease;
    }

    .form-group select:not(:disabled):hover {
      border-color: var(--primary);
    }

    .form-group select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(46,125,50,0.1);
    }

    .form-group label {
      font-size: 0.9rem;
      color: #333;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 0.5rem;
    }

    .form-group label i {
      font-size: 0.9rem;
      color: #333;
    }

    .form-group:hover label i {
      color: #666;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 0.95rem;
      transition: border-color 0.2s;
      background: white;
      color: #2e7d32;
      height: 40px;
    }

    .form-group input:hover,
    .form-group select:hover {
      border-color: #999;
    }

    .form-group input:focus,
    .form-group select:focus {
      border-color: #666;
      outline: none;
    }

    .form-group select {
      appearance: none;
      background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232e7d32' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
      background-repeat: no-repeat;
      background-position: right 15px center;
      background-size: 15px;
      padding-right: 45px;
    }

    .form-group select:disabled {
      background-color: #f5f5f5;
      cursor: not-allowed;
      opacity: 0.7;
    }

    #employee_group {
      opacity: 0;
      transform: translateY(-10px);
      transition: all 0.3s ease;
    }

    #employee_group.visible {
      opacity: 1;
      transform: translateY(0);
    }

    .input-help {
      color: #666;
      font-size: 0.8rem;
      margin-top: 4px;
    }

    .computation-type-selector {
      display: flex;
      gap: 1rem;
      margin-bottom: 1.5rem;
      border-bottom: 2px solid #e8f5e9;
      padding-bottom: 0.5rem;
    }

    .comp-type-btn {
      background: none;
      border: none;
      padding: 0.75rem 1.5rem;
      font-size: 1rem;
      color: #666;
      cursor: pointer;
      position: relative;
      transition: all 0.3s ease;
      border-radius: 4px;
    }

    .comp-type-btn.active {
      color: #2e7d32;
      font-weight: 500;
      background: #e8f5e9;
    }

    .comp-type-btn:hover:not(.active) {
      background: #f5f5f5;
      color: #333;
    }

    .calculation-section {
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .section-header th {
      background: #e8f5e9;
      color: #2e7d32;
      padding: 0.75rem;
      font-size: 0.9rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .summary-table .subtotal {
      background: #f8f9fa;
      font-weight: 500;
    }

    .summary-table .subtotal th,
    .summary-table .subtotal td {
      color: #2e7d32;
      border-top: 1px solid #e8f5e9;
      border-bottom: 1px solid #e8f5e9;
      padding: 1rem 0.75rem;
    }

    /* Deductions Section Styles */
    .deductions-section {
      margin-top: 2rem;
      background: #fff;
      border-radius: 0.5rem;
      padding: 1.5rem;
    }

    .deduction-box {
      background: #f8f9fa;
      border: 1px solid #e9ecef;
      border-radius: 0.5rem;
      padding: 1.25rem;
      margin-bottom: 1.25rem;
    }

    /* Tax Switch Styles */
    .tax-controls {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-left: auto;
    }

    .switch {
      position: relative;
      display: inline-block;
      width: 48px;
      height: 24px;
    }

    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: .4s;
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 18px;
      width: 18px;
      left: 3px;
      bottom: 3px;
      background-color: white;
      transition: .4s;
    }

    .switch input:checked + .slider {
      background-color: #2e7d32;
    }

    .switch input:checked + .slider:before {
      transform: translateX(24px);
    }

    .slider.round {
      border-radius: 24px;
    }

    .slider.round:before {
      border-radius: 50%;
    }

    .tax-rate-input {
      width: 60px;
      padding: 2px 4px;
      border: 1px solid #ced4da;
      border-radius: 4px;
      text-align: center;
      font-size: 0.9rem;
    }

    .tax-rate-input:disabled {
      background-color: #e9ecef;
      cursor: not-allowed;
    }

    .tax-rate-symbol {
      font-size: 0.9rem;
      color: #666;
    }

    .deduction-box h4 {
      color: #2e7d32;
      font-size: 1rem;
      margin: 0 0 1rem 0;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .deduction-box h4 i {
      background: #e8f5e9;
      padding: 0.5rem;
      border-radius: 0.375rem;
      font-size: 0.9rem;
    }

    .input-group {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .deduction-display {
      background: #e8f5e9;
      color: #2e7d32;
      padding: 0.5rem 0.75rem;
      border-radius: 0.375rem;
      font-weight: 500;
      min-width: 100px;
      text-align: right;
    }

    .deductions-summary {
      background: #e8f5e9;
      border-radius: 0.5rem;
      padding: 1.25rem;
      margin-top: 1.25rem;
    }

    .summary-item {
      display: flex;
      justify-content: space-between;
      padding: 0.5rem 0;
      color: #2e7d32;
    }

    .summary-item + .summary-item {
      border-top: 1px dashed #b7dfb9;
    }

    .summary-item.total {
      border-top: 2px solid #2e7d32;
      font-weight: 600;
      font-size: 1.1rem;
      margin-top: 0.5rem;
      padding-top: 0.75rem;
    }

    .info-box {
      background: #e8f5e9;
      padding: 12px;
      border-radius: 8px;
      color: #2e7d32;
      font-size: 0.9rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .info-box i {
      font-size: 1.1rem;
    }

    input[readonly], input:disabled {
      background-color: #f8f9fa;
      cursor: not-allowed;
      border-color: #dee2e6 !important;
      opacity: 0.7;
    }

    input:disabled + .input-help {
      color: #6c757d;
      font-style: italic;
    }

    .form-group.disabled label {
      color: #6c757d;
    }

    .form-group.disabled label i {
      background: #f8f9fa;
      color: #6c757d;
    }

    /* Pay Breakdown Styling */
    .pay-breakdown {
      background: #f8f9fa;
      border: 1px solid #e9ecef;
      border-radius: 6px;
      padding: 8px 12px;
      margin: 8px 0;
      font-size: 0.9rem;
    }

    .breakdown-item {
      display: flex;
      justify-content: space-between;
      padding: 4px 0;
      color: #2e7d32;
    }

    .breakdown-item + .breakdown-item {
      border-top: 1px dashed #e9ecef;
      margin-top: 4px;
      padding-top: 8px;
    }

    .breakdown-item span:last-child {
      font-weight: 500;
    }

    .form-actions {
      display: flex;
      gap: 10px;
      margin-top: 10px;
    }

    .btn { 
  background: var(--primary);
  color: #fff;
  border: none;
  padding: 0.75rem 1.25rem;
  border-radius: 0.375rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s ease-in-out;
  font-size: 0.875rem;
  font-weight: 500;
  text-decoration: none;
  box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}

.btn:hover { 
  background: var(--primary-dark);
  transform: translateY(-1px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.12);
}

.btn:active {
  transform: translateY(0);
  box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}

    .btn i {
      font-size: 0.9rem;
    }

    .btn.ghost { 
      background: transparent; 
      border: 1px solid var(--primary); 
      color: var(--primary);
      box-shadow: none;
    }

    .btn.ghost:hover { 
      background: var(--primary); 
      color: white;
    }

    /* Updated styles for green backgrounds with white text */
    .table th,
    .section-title i,
    .btn-primary,
    .form-group label i[class*="fa-"],
    .breakdown-item span:last-child,
    .deduction-display {
        color: white !important;
    }

    .section-title i,
    .form-group label i[class*="fa-"] {
        background-color: #2e7d32 !important;
    }

    .breakdown-item span:last-child,
    .deduction-display {
        background-color: #2e7d32 !important;
        color: white !important;
    }    .table-container {
      overflow-x: auto;
      margin-top: 20px;
    }

    .table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      background: white;
      border-radius: 12px;
      overflow: hidden;
    }

    .table th,
    .table td {
      padding: 14px 16px;
      text-align: left;
      border-bottom: 1px solid #e8f5e9;
    }

    .table th {
      background: linear-gradient(135deg, #2e7d32, #1b5e20);
      color: white;
      font-weight: 600;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .table th i {
      margin-left: 4px;
      opacity: 0.8;
    }

    .table tbody tr {
      transition: all 0.2s ease;
    }

    .table tbody tr:hover {
      background: #f1f8e9;
      transform: scale(1.002);
    }

    .table td {
      font-size: 0.95rem;
      color: #333;
    }

    .table td b {
      color: #2e7d32;
      font-weight: 600;
    }

    .table td:first-child {
      font-weight: 500;
      color: #1b5e20;
    }

    .table td .actions {
      display: flex;
      gap: 8px;
      opacity: 0;
      transition: opacity 0.2s ease;
    }

    .table tr:hover .actions {
      opacity: 1;
    }

    .table td .actions a {
      padding: 6px;
      border-radius: 8px;
      color: white;
      text-decoration: none;
      font-size: 1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
      transition: all 0.2s ease;
    }

    .table td .actions .edit {
      background: #1976d2;
    }

    .table td .actions .delete {
      background: #d32f2f;
    }

    .table td .actions a:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .table td .actions {
      display: flex;
      gap: 8px;
    }

    .table td .actions a {
      padding: 6px 10px;
      border-radius: 4px;
      color: white;
      text-decoration: none;
      font-size: 0.9rem;
      transition: all 0.2s;
    }

    .table td .actions a:hover {
      transform: translateY(-1px);
    }

    .table td .actions .edit {
      background: var(--blue);
    }

    .table td .actions .delete {
      background: var(--red);
    }

    /* Modal Popup Styles */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
      backdrop-filter: blur(4px);
    }

    .modal {
      background: #ffffff;
      border-radius: 12px;
      padding: 2rem;
      width: 90%;
      max-width: 900px;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
      box-shadow: 0 10px 25px rgba(0,0,0,0.2);
      animation: modalFadeIn 0.3s ease;
    }

    .modal .close-modal {
      position: absolute;
      top: 1rem;
      right: 1rem;
      background: none;
      border: none;
      font-size: 1.5rem;
      color: #666;
      cursor: pointer;
      padding: 0.5rem;
      border-radius: 4px;
      transition: all 0.2s;
    }

    .modal .close-modal:hover {
      background: #f8f9fa;
      color: #333;
    }

    #modalOverlay {
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }

    #modalOverlay.active {
      opacity: 1;
      visibility: visible;
    }

    .records-modal {
      max-width: 1100px !important;
    }

    #recordsModalOverlay {
      z-index: 1100;
    }

    .records-modal .table {
      margin-bottom: 0;
      background: white;
      border-radius: 8px;
      overflow: hidden;
    }

    .records-modal .table th {
      background: #f8f9fa;
      color: #2e7d32;
      font-weight: 600;
      padding: 1rem;
      border-bottom: 2px solid #e9ecef;
    }

    .records-modal .table td {
      padding: 0.75rem 1rem;
      vertical-align: middle;
    }

    .records-modal .table tbody tr:hover {
      background-color: rgba(46, 125, 50, 0.05);
    }

    .records-modal .actions {
      display: flex;
      gap: 0.5rem;
      justify-content: flex-end;
    }

        .records-modal .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 4px;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            text-decoration: none;
            margin: 0 0.25rem;
        }

        .records-modal .btn-primary {
            background-color: #1976d2;
            color: white;
        }

        .records-modal .btn-info {
            background-color: #17a2b8;
            color: white;
        }

        .records-modal .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .records-modal .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .records-modal .btn-sm:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            opacity: 0.9;
        }    .records-modal {
      width: 95%;
      max-width: 1200px;
    }

    .modal h3 {
      margin: 0 0 1.5rem;
      color: #1b5e20;
      font-size: 1.5rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .modal h3 i {
      background: #e8f5e9;
      color: #2e7d32;
      padding: 0.75rem;
      border-radius: 8px;
      font-size: 1.1rem;
    }

    @keyframes modalFadeIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .computation-section {
      background: white;
      border-radius: 12px;
      padding: 20px;
      margin: 15px 0;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .computation-section h4 {
      margin: 0 0 15px;
      color: #2e7d32;
      font-size: 1.1rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .computation-section h4 i {
      background: #e8f5e9;
      padding: 8px;
      border-radius: 8px;
      font-size: 1rem;
    }

    .summary-table {
      width: 100%;
      border-collapse: collapse;
    }

    .summary-table th,
    .summary-table td {
      padding: 10px;
      border-bottom: 1px solid #e8f5e9;
    }

    .summary-table th {
      text-align: left;
      color: #666;
      font-weight: 500;
      width: 60%;
    }

    .summary-table td {
      text-align: right;
      font-weight: 600;
      color: #2e7d32;
    }

    .total-row th,
    .total-row td {
      font-size: 1.2rem;
      color: #1b5e20;
      border-top: 2px solid #2e7d32;
    }

    @keyframes popup {
      from { transform: scale(0.9); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }

    .modal h3 {
      margin: 0 0 20px;
      font-size: 24px;
      color: #1b5e20;
      font-weight: 700;
      text-align: center;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .modal h3 i {
      font-size: 1.2rem;
      background: #e8f5e9;
      color: #2e7d32;
      padding: 10px;
      border-radius: 12px;
    }

    .modal table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    }

    .modal tr {
      transition: all 0.2s ease;
    }

    .modal tr:hover {
      background: #f1f8e9;
    }

    .modal th, .modal td {
      text-align: left;
      padding: 12px 16px;
      font-size: 15px;
      border-bottom: 1px solid #e8f5e9;
    }

    .modal th { 
      width: 60%; 
      color: #2e7d32;
      font-weight: 600;
    }

    .modal td {
      font-weight: 500;
      color: #333;
    }

    .modal tr:last-child {
      background: #e8f5e9;
      font-weight: 700;
    }

    .modal tr:last-child th,
    .modal tr:last-child td {
      color: #1b5e20;
      font-size: 1.1rem;
    }

    .modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      margin-top: 20px;
    }
  </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<div class="main-area">
  <?php include '../includes/header.php'; ?>

  <section class="content">
    <div class="content-card">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
          <div style="background: #e8f5e9; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-file-invoice-dollar" style="font-size: 1.2rem; color: #2e7d32;"></i>
          </div>
          <h2 style="margin: 0; color: #1b5e20;"><?= $edit ? 'Edit Payroll Record' : 'Create New Payroll' ?></h2>
        </div>
        <input type="hidden" id="basic_pay" name="basic_pay">
        <input type="hidden" id="final_net_pay" name="net_pay">
        <input type="hidden" id="sss_input" name="sss">
        <input type="hidden" id="philhealth_input" name="philhealth">
        <input type="hidden" id="pagibig_input" name="pagibig">
        <input type="hidden" id="tax_input" name="tax">
        <input type="hidden" id="total_deductions_input" name="total_deductions">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
      </div>
      <!-- Success Message Container -->
      <div id="successMessage" style="display:none;" class="alert alert-success">
        <i class="fas fa-check-circle"></i> Payroll submitted successfully!
        <div class="mt-2">
          <a href="#" id="viewRecordBtn" class="btn btn-sm btn-primary">
            <i class="fas fa-file-alt"></i> View Record
          </a>
          <a href="#" id="viewReportBtn" class="btn btn-sm btn-info ml-2">
            <i class="fas fa-chart-bar"></i> View Report
          </a>
        </div>
      </div>

      <!-- Error Message Container -->
      <div id="errorMessage" style="display:none;" class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <span id="errorText"></span>
      </div>

      <form method="POST" id="payrollForm" action="save_payroll.php">
        <!-- Hidden fields for form data -->
        <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="add_payroll" value="1">
        
        <!-- Hidden fields for computed values -->
        <input type="hidden" id="basic_pay_input" name="basic_pay">
        <input type="hidden" id="final_net_pay_input" name="net_pay">
        <input type="hidden" id="gross_pay_input" name="gross_pay_computed">
        <input type="hidden" id="work_hours_input" name="work_hours_computed">
        <input type="hidden" id="overtime_hours_input" name="overtime_hours">
        <input type="hidden" id="absent_hours_input" name="absent_hours">
        <input type="hidden" id="late_minutes_input" name="late_minutes">
        <input type="hidden" id="sss_input" name="sss">
        <input type="hidden" id="philhealth_input" name="philhealth">
        <input type="hidden" id="pagibig_input" name="pagibig">
        <input type="hidden" id="tax_input" name="tax">
        <input type="hidden" id="total_deductions_input" name="total_deductions">
        
        <!-- Employee Selection Section -->
        <div class="section-card">
          <h3 class="section-title">
            <i class="fas fa-user-tie"></i> Employee Information
          </h3>
          <div class="form-row">
            <!-- Department Selection -->
            <div class="form-group">
              <label for="dept_select">
                <i class="fas fa-building"></i> Department
              </label>
              <select name="department" id="dept_select" required>
                <option value="">Select Department</option>
                <?php
                while($d = $departments->fetch_assoc()): ?>
                  <option value="<?= htmlspecialchars($d['name']) ?>"><?= htmlspecialchars($d['name']) ?></option>
                <?php endwhile; ?>
              </select>
              <small class="input-help">Select a department first</small>
            </div>

            <!-- Employee Selection -->
            <div class="form-group" id="employee_group">
              <label for="emp_select">
                <i class="fas fa-user"></i> Employee
              </label>
              <select name="emp_select" id="emp_select" required>
                <option value="">Select Employee</option>
              </select>
              <input type="hidden" name="emp_name" id="emp_name">
              <input type="hidden" name="department" id="department">
              <input type="hidden" name="position" id="position">
              <small class="input-help">Choose from department employees</small>
            </div>
          </div>

    

          <script>
          // Get employees data from PHP with error handling
// Employee selection handling
document.addEventListener('DOMContentLoaded', function() {
    const deptSelect = document.getElementById('dept_select');
    const empSelect = document.getElementById('emp_select');
    const employeeGroup = document.getElementById('employee_group');
    const employeeDetails = document.getElementById('employee_details');
    const positionInput = document.getElementById('emp_position');
    const salaryInput = document.getElementById('basic_pay');

    // Function to fetch and update employee dropdown
    function updateEmployeeDropdown(departmentName) {
        console.log('Updating employees for department:', departmentName);
        
        if (!departmentName) {
            empSelect.innerHTML = '<option value="">Select Department First</option>';
            empSelect.disabled = true;
            return;
        }

        // Show loading state
        empSelect.innerHTML = '<option value="">Loading...</option>';
        empSelect.disabled = true;

        // Fetch employees from server
        fetch(`get_dept_pos.php?department=${encodeURIComponent(departmentName)}`)
            .then(response => response.json())
            .then(data => {
                console.log('Received data:', data);
                
                empSelect.innerHTML = '<option value="">Select Employee</option>';
                
                if (data.data && data.data.length > 0) {
                    data.data.forEach(emp => {
                        const option = document.createElement('option');
                        option.value = emp.id;
                        option.textContent = emp.name;
                        option.dataset.position = emp.position_name;
                        option.dataset.salary = emp.salary;
                        empSelect.appendChild(option);
                    });
                    
                    empSelect.disabled = false;
                } else {
                    empSelect.innerHTML = '<option value="">No employees found</option>';
                }
            })
            .catch(error => {
                console.error('Error fetching employees:', error);
                empSelect.innerHTML = '<option value="">Error loading employees</option>';
            });
    }

    // Handle department selection
    deptSelect.addEventListener('change', function() {
        const departmentId = this.value;
        updateEmployeeDropdown(departmentId);
        employeeDetails.style.display = 'none';
    });

    // Handle employee selection
    empSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const displaySalary = document.getElementById('display_salary');
        const salaryDetails = document.getElementById('salary_details');
        const basicPayInput = document.getElementById('basic_pay_input');
        const submitBtn = document.getElementById('submitPayrollBtn');
        
        if (selected && selected.value) {
            const salary = selected.dataset.salary || '';
            positionInput.value = selected.dataset.position || '';
            
            // Update displayed salary with formatting
            if (displaySalary && salary) {
                displaySalary.value = new Intl.NumberFormat('en-PH', {
                    style: 'currency',
                    currency: 'PHP'
                }).format(salary);
                salaryDetails.style.display = 'block';
            }
            
            // Update hidden input for basic pay
            if (basicPayInput) {
                basicPayInput.value = salary;
            }
            
            employeeDetails.style.display = 'grid';
            submitBtn.disabled = false;
        } else {
            employeeDetails.style.display = 'none';
            salaryDetails.style.display = 'none';
            positionInput.value = '';
            if (basicPayInput) basicPayInput.value = '';
            if (displaySalary) displaySalary.value = '';
            submitBtn.disabled = true;
        }
    });

    // Initial load if department is pre-selected
    if (deptSelect.value) {
        updateEmployeeDropdown(deptSelect.value);
    }
});

// Function to update employee select based on department
function updateEmployeeSelect(deptId) {
    const empSelect = document.getElementById('emp_select');
    const employeeGroup = document.getElementById('employee_group');
    const employeeDetails = document.getElementById('employee_details');
    
    // Reset the employee select
    empSelect.innerHTML = '<option value="">Select Employee</option>';
    empSelect.disabled = true;
    employeeGroup.style.opacity = '0.5';
    employeeDetails.style.display = 'none';
    
    if (!deptId) {
        return;
    }
    
    const employees = employeesByDept[deptId] || [];
    console.log(`Found ${employees.length} employees for department ${deptId}`);
    
    if (employees.length > 0) {
        employees.forEach(emp => {
            const option = document.createElement('option');
            option.value = emp.id;
            option.textContent = emp.name;
            option.dataset.position = emp.position;
            option.dataset.salary = emp.salary;
            empSelect.appendChild(option);
        });
        
        empSelect.disabled = false;
        employeeGroup.style.opacity = '1';
    } else {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'No employees in this department';
        empSelect.appendChild(option);
    }
}          // When page loads
          document.addEventListener('DOMContentLoaded', function() {
              const deptSelect = document.getElementById('dept_select');
              const empSelect = document.getElementById('emp_select');
              
              // When department is selected
              deptSelect.addEventListener('change', function() {
                  const deptId = this.value;
                  
                  // Clear employee select
                  empSelect.innerHTML = '<option value="">Select Employee</option>';
                  empSelect.disabled = true;
                  
                  if (!deptId) return;
                  
                  // Get employees for selected department
                  const employees = employeesByDept[deptId] || [];
                  
                  if (employees.length > 0) {
                      // Add employee options
                      employees.forEach(function(emp) {
                          const option = new Option(emp.name, emp.id);
                          option.dataset.position = emp.position;
                          option.dataset.salary = emp.salary;
                          empSelect.add(option);
                      });
                      empSelect.disabled = false;
                  }
              });
              
              // When employee is selected
              empSelect.addEventListener('change', function() {
                  const selected = this.options[this.selectedIndex];
                  const details = document.getElementById('employee_details');
                  const position = document.getElementById('emp_position');
                  const salary = document.getElementById('basic_pay');
                  
                  if (selected && selected.value) {
                      position.value = selected.dataset.position || '';
                      salary.value = selected.dataset.salary || '';
                      details.style.display = 'block';
                  } else {
                      details.style.display = 'none';
                  }
              });
          });
          document.addEventListener('DOMContentLoaded', function() {
              const employeeGroup = document.getElementById('employee_group');
              const empSelect = document.getElementById('emp_select');

              // Add transition class for smooth opacity changes
              employeeGroup.style.transition = 'opacity 0.3s ease';
              
              // Function to enable/disable employee select
              function toggleEmployeeSelect(enabled) {
                  empSelect.disabled = !enabled;
                  employeeGroup.style.opacity = enabled ? '1' : '0.5';
              }

              // Initialize the state
              toggleEmployeeSelect(false);

              // Handle department changes
              document.getElementById('dept_select').addEventListener('change', function() {
                  toggleEmployeeSelect(this.value !== '');
              });
          });
          </script>

          <!-- Employee Details Section -->
          <div id="employee_details" style="display: none;" class="details-row">
            <div class="form-row">
              <div class="form-group">
                <label>
                  <i class="fas fa-briefcase"></i> Position
                </label>
                <input type="text" id="emp_position" readonly>
                <small class="input-help">Employee's current position</small>
              </div>
          <div class="form-group" id="salary_details" style="display:none;">
            <label for="display_salary">
              <i class="fas fa-money-bill"></i> Monthly Base Salary
            </label>
            <input type="text" id="display_salary" class="form-control" readonly>
            <small class="input-help">Employee's base monthly salary</small>
          </div>

        
            

          <style>
          /* Selection containers alignment */
          .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
          }

          .form-group {
            display: flex;
            flex-direction: column;
            min-height: 90px;
          }

          .form-group select {
            width: 100%;
            height: 45px;
            padding: 0 1rem;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            font-size: 0.9rem;
            transition: all 0.2s;
            background-color: white;
          }

          .form-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: #2e7d32;
            font-weight: 500;
            height: 32px;
          }

          .form-group label i {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e8f5e9;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.2s;
          }

          .form-group:hover label i {
            background: #2e7d32;
            color: white;
            transform: scale(1.05);
          }

          .form-group select:hover:not(:disabled) {
            border-color: #2e7d32;
          }

          .form-group select:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 0 0.2rem rgba(46,125,50,0.25);
            outline: none;
          }

          .form-group select:disabled {
            background-color: #f8f9fa;
            cursor: not-allowed;
            opacity: 0.75;
          }

          .form-group select:not(:disabled) {
            background-color: #ffffff;
            cursor: pointer;
            border-color: #2e7d32;
            animation: enableSelect 0.3s ease;
          }

          @keyframes enableSelect {
            from { opacity: 0.75; transform: translateY(-2px); }
            to { opacity: 1; transform: translateY(0); }
          }

          .input-help {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.25rem;
            line-height: 1.2;
          }

          /* Details row styling */
          .details-row {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px dashed #dee2e6;
            animation: fadeIn 0.3s ease;
          }

          .details-row input[readonly] {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            color: #495057;
            cursor: not-allowed;
          }

          @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
          }

#employee_group {
  opacity: 0.5;
  transition: all 0.3s ease;
}

#employee_group.active {
  opacity: 1;
}

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deptSelect = document.getElementById('dept_select');
    const empSelect = document.getElementById('emp_select');
    const employeeGroup = document.getElementById('employee_group');
    const employeeDetails = document.getElementById('employee_details');
    const positionInput = document.getElementById('emp_position');
    const salaryInput = document.getElementById('basic_pay');

    // Function to fetch employees for a department
    function fetchEmployees(departmentId) {
        if (!departmentId) {
            empSelect.innerHTML = '<option value="">Select Employee</option>';
            empSelect.disabled = true;
            employeeGroup.style.opacity = '0.5';
            return;
        }

        // Show loading state
        empSelect.innerHTML = '<option value="">Loading...</option>';
        empSelect.disabled = true;

        // Fetch employees using AJAX
        fetch(`get_dept_pos.php?department_id=${departmentId}`)
            .then(response => response.json())
            .then(employees => {
                empSelect.innerHTML = '<option value="">Select Employee</option>';
                
                if (employees.length > 0) {
                    employees.forEach(emp => {
                        const option = document.createElement('option');
                        option.value = emp.id;
                        option.textContent = emp.emp_name;
                        option.dataset.position = emp.position_name;
                        option.dataset.salary = emp.base_salary;
                        empSelect.appendChild(option);
                    });
                    empSelect.disabled = false;
                    employeeGroup.style.opacity = '1';
                } else {
                    empSelect.innerHTML = '<option value="">No employees found</option>';
                }
                
                console.log('Loaded employees:', employees);
            })
            .catch(error => {
                console.error('Error fetching employees:', error);
                empSelect.innerHTML = '<option value="">Error loading employees</option>';
            });
    }

    // Department change handler
    deptSelect.addEventListener('change', function() {
        const departmentId = this.value;
        employeeDetails.style.display = 'none';
        fetchEmployees(departmentId);
    });

    // Employee change handler
    empSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        
        if (selected && selected.value) {
            positionInput.value = selected.dataset.position || '';
            salaryInput.value = selected.dataset.salary || '';
            employeeDetails.style.display = 'grid';
            
            // Update calculations if the function exists
            if (typeof calculateDeductionsAndNetPay === 'function') {
                calculateDeductionsAndNetPay();
            }
        } else {
            employeeDetails.style.display = 'none';
            positionInput.value = '';
            salaryInput.value = '';
        }
    });

    // Initialize if department is pre-selected
    if (deptSelect.value) {
        fetchEmployees(deptSelect.value);
    }
});</script>          .form-row {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1rem;
          }

          .form-group {
            flex: 1;
          }

          .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            font-size: 0.9rem;
            transition: all 0.2s;
          }

          .form-group select:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
          }

          .form-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: #2e7d32;
            font-weight: 500;
          }

          .form-group label i {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e8f5e9;
            border-radius: 6px;
            font-size: 0.8rem;
            transition: all 0.2s;
          }

          .form-group:hover label i {
            background: #2e7d32;
            color: white;
          }

          .input-help {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.25rem;
          }
          </style>

          <script>
          document.addEventListener('DOMContentLoaded', function() {
              // Get DOM elements
              const deptSelect = document.getElementById('dept_select');
              const empSelect = document.getElementById('emp_select');
              const employeeGroup = document.getElementById('employee_group');
              const salaryDetails = document.getElementById('salary_details');
              const displaySalary = document.getElementById('display_salary');
              const basicPayInput = document.getElementById('basic_pay');
              const empPosition = document.getElementById('emp_position');
              
              // Validate elements
              if (!deptSelect) console.error('Department select not found!');
              if (!empSelect) console.error('Employee select not found!');
              if (!employeeGroup) console.error('Employee group not found!');
              if (!salaryDetails) console.error('Salary details div not found!');
              if (!displaySalary) console.error('Display salary input not found!');
              if (!basicPayInput) console.error('Basic pay input not found!');
              if (!empPosition) console.error('Employee position input not found!');
              
              // Function to format currency
              function formatCurrency(amount) {
                  return '₱ ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
              }
              
              // Debug the initial state
              console.group('Initial State');
              console.log('Department Select Options:', Array.from(deptSelect.options).map(opt => ({
                  value: opt.value,
                  text: opt.text
              })));
              console.log('Employees By Department:', employeesByDept);
              console.log('Department IDs:', Object.keys(employeesByDept));
              
              // Log each department's employees
              Object.entries(employeesByDept).forEach(([deptId, employees]) => {
                  console.log(`Department ${deptId} has ${employees.length} employees:`, 
                      employees.map(e => `${e.name} (${e.position})`));
              });
              console.groupEnd();

              // Remove any existing event listeners (if page reloaded)
              deptSelect.removeEventListener('change', handleDepartmentChange);
              empSelect.removeEventListener('change', handleEmployeeChange);

              // Department change handler
              function handleDepartmentChange(event) {
                  const selectedDeptId = this.value;
                  
                  console.group('Department Change Event');
                  console.log('Selected Department ID:', selectedDeptId);
                  
                  // Reset all sections
                  empSelect.innerHTML = '<option value="">Select employee</option>';
                  empSelect.disabled = true;
                  salaryDetails.style.display = 'none';
                  displaySalary.value = '';
                  basicPayInput.value = '';
                  empPosition.value = '';
                  
                  // Reset employee group visibility
                  employeeGroup.style.opacity = '0.5';
                  
                  // If no department selected, just return
                  if (!selectedDeptId) {
                      console.log('No department selected');
                      console.groupEnd();
                      return;
                  }
                  
                  // Show loading state
                  empSelect.innerHTML = '<option value="">Loading employees...</option>';
                  
                  // Fetch employees for this department
                  fetch(`get_dept_pos_new.php?department_id=${selectedDeptId}`)
                      .then(response => response.json())
                      .then(employees => {
                          console.log('Fetched employees:', employees);
                          
                          // Reset dropdown
                          empSelect.innerHTML = '<option value="">Select employee</option>';
                          
                          if (Array.isArray(employees) && employees.length > 0) {
                              // Add employees to dropdown
                              employees.forEach(emp => {
                                  const option = document.createElement('option');
                                  option.value = emp.id;
                                  option.textContent = emp.name;
                                  option.dataset.position = emp.position;
                                  option.dataset.salary = emp.salary;
                                  empSelect.appendChild(option);
                          
                          console.log('Added employee option:', {
                              id: emp.id,
                              name: emp.name,
                              position: emp.position,
                              salary: emp.salary
                          });
                      });
                      
                      // Update the UI
                      empSelect.style.opacity = '1';
                      empSelect.focus();
                  } else {
                      const option = document.createElement('option');
                      option.value = '';
                      option.textContent = 'No employees in this department';
                      empSelect.appendChild(option);
                      empSelect.disabled = true;
                  }
                  
                  console.log('Final employee select state:', {
                      disabled: empSelect.disabled,
                      optionCount: empSelect.options.length,
                      isVisible: empSelect.offsetParent !== null
                  });
                  console.groupEnd();
              }
              }

              // Employee change handler
              function handleEmployeeChange() {
                  const selectedOption = this.options[this.selectedIndex];
                  
                  console.group('Employee Change');
                  console.log('Selected Employee:', selectedOption ? {
                      value: selectedOption.value,
                      text: selectedOption.text,
                      position: selectedOption.dataset.position,
                      salary: selectedOption.dataset.salary
                  } : 'none');
                  
                  if (selectedOption && selectedOption.value) {
                      // Update employee details
                      const salary = parseFloat(selectedOption.dataset.salary) || 0;
                      
                      // Update display fields
                      empPosition.value = selectedOption.dataset.position || '';
                      basicPay.value = salary.toFixed(2);
                      
                      // Update hidden fields
                      document.getElementById('emp_id').value = selectedOption.value;
                      document.getElementById('basic_pay').value = salary.toFixed(2);
                      
                      // Show employee details with animation
                      employeeDetails.style.display = 'grid';
                      employeeDetails.style.opacity = '0';
                      setTimeout(() => {
                          employeeDetails.style.opacity = '1';
                      }, 10);
                      
                      // Calculate salary
                      if (typeof calculatePayroll === 'function') {
                          calculatePayroll();
                      }
                  } else {
                      // Hide employee details
                      employeeDetails.style.display = 'none';
                      basicPay.value = '0';
                      empPosition.value = '';
                      
                      // Clear hidden fields
                      document.getElementById('emp_id').value = '';
                      document.getElementById('basic_pay').value = '0';
                  }
                  console.groupEnd();
              }

              // Add event listeners
              // Utility function to calculate net pay
              function calculateNetPay() {
                  const grossPay = parseFloat(document.getElementById('gross_pay').value) || 0;
                  const basicPay = parseFloat(document.getElementById('basic_pay').value) || 0;
                  
                  // Calculate deductions
                  const sss = Math.round(grossPay * 0.045 * 100) / 100; // 4.5% SSS
                  const philhealth = Math.round(grossPay * 0.025 * 100) / 100; // 2.5% PhilHealth
                  const pagibig = Math.min(100, Math.round(grossPay * 0.02 * 100) / 100); // 2% Pag-IBIG, max 100
                  
                  // Calculate tax (10% if above 20000)
                  const tax = grossPay > 20000 ? Math.round(grossPay * 0.10 * 100) / 100 : 0;
                  
                  // Total deductions
                  const totalDeductions = sss + philhealth + pagibig + tax;

                  // Store values in hidden inputs for form submission
                  document.getElementById('sss_input').value = sss.toFixed(2);
                  document.getElementById('philhealth_input').value = philhealth.toFixed(2);
                  document.getElementById('pagibig_input').value = pagibig.toFixed(2);
                  document.getElementById('tax_input').value = tax.toFixed(2);
                  document.getElementById('total_deductions_input').value = totalDeductions.toFixed(2);
                  
                  // Calculate net pay
                  const netPay = grossPay - totalDeductions;
                  
                  // Update form fields if they exist
                  if (document.getElementById('net_pay')) {
                      document.getElementById('net_pay').value = netPay.toFixed(2);
                  }
                  if (document.getElementById('final_net_pay')) {
                      document.getElementById('final_net_pay').value = netPay.toFixed(2);
                  }
                  
                  // Update deductions breakdown if the elements exist
                  if (document.getElementById('sss_deduction')) {
                      document.getElementById('sss_deduction').textContent = sss.toFixed(2);
                  }
                  if (document.getElementById('philhealth_deduction')) {
                      document.getElementById('philhealth_deduction').textContent = philhealth.toFixed(2);
                  }
                  if (document.getElementById('pagibig_deduction')) {
                      document.getElementById('pagibig_deduction').textContent = pagibig.toFixed(2);
                  }
                  if (document.getElementById('tax_deduction')) {
                      document.getElementById('tax_deduction').textContent = tax.toFixed(2);
                  }
                  if (document.getElementById('total_deductions')) {
                      document.getElementById('total_deductions').textContent = totalDeductions.toFixed(2);
                  }
                  
                  console.log('Net Pay Calculation:', {
                      grossPay,
                      basicPay,
                      deductions: {
                          sss,
                          philhealth,
                          pagibig,
                          tax,
                          total: totalDeductions
                      },
                      netPay
                  });
              }

              deptSelect.addEventListener('change', async function() {
                  const selectedDeptId = deptSelect.value;
                  console.log('Selected department:', selectedDeptId);
                  
                  // Show loading state
                  empSelect.innerHTML = '<option value="">Loading employees...</option>';
                  empSelect.disabled = true;
                  employeeGroup.style.opacity = '0.5';
                  
                  try {
                      // Fetch employees for this department
                      const response = await fetch(`get_dept_pos_new.php?department_id=${selectedDeptId}`);
                      const employees = await response.json();
                      console.log('Fetched employees:', employees);
                      
                      // Reset dropdown
                      empSelect.innerHTML = '<option value="">Select employee</option>';
                      
                      if (Array.isArray(employees) && employees.length > 0) {
                          // Add employees to dropdown
                          employees.forEach(emp => {
                              const option = document.createElement('option');
                              option.value = emp.id;
                              option.textContent = emp.emp_name;
                              option.dataset.position = emp.position;
                              option.dataset.salary = emp.salary;
                              empSelect.appendChild(option);
                          });
                          
                          // Enable employee selection
                          empSelect.disabled = false;
                          employeeGroup.classList.add('active');
                          employeeGroup.style.opacity = '1';
                      } else {
                          // No employees found
                          empSelect.innerHTML = '<option value="">No employees found</option>';
                          empSelect.disabled = true;
                          employeeGroup.classList.remove('active');
                          employeeGroup.style.opacity = '0.5';
                      }
                  } catch (error) {
                      console.error('Error fetching employees:', error);
                      empSelect.innerHTML = '<option value="">Error loading employees</option>';
                      empSelect.disabled = true;
                      employeeGroup.classList.remove('active');
                      employeeGroup.style.opacity = '0.5';
                  }
              });

              empSelect.addEventListener('change', function() {
                  const selectedOption = empSelect.selectedOptions[0];
                  console.log('Selected employee:', selectedOption);
                  
                  if (selectedOption) {
                      const salary = selectedOption.dataset.salary;
                      console.log('Employee salary:', salary);
                      
                      // Update form fields
                      if (salary) {
                          const parsedSalary = parseFloat(salary);
                          document.getElementById('basic_pay').value = parsedSalary;
                          document.getElementById('gross_pay').value = parsedSalary.toFixed(2);
                          calculateNetPay(); // Update calculations if you have this function
                      }
                  }
              });

              // Add event listener to gross pay input for live calculations
              const grossPayInput = document.getElementById('gross_pay');
              if (grossPayInput) {
                  grossPayInput.addEventListener('input', calculateNetPay);
                  // Calculate initial values
                  calculateNetPay();
              }

              // Initial setup if department is preselected (edit mode)
              if (deptSelect.value) {
                  handleDepartmentChange.call(deptSelect);
              }
              });

              // If department is preselected (edit mode)
              if (deptSelect.value) {
                  deptSelect.dispatchEvent(new Event('change'));
              }
          });
          </script>

          <style>
            .selection-container {
              display: flex;
              gap: 1.5rem;
              margin-bottom: 1rem;
            }

            .selection-box {
              flex: 1;
              display: flex;
              align-items: flex-start;
              gap: 1rem;
              min-width: 0;
            }

            .selection-box .icon-wrapper {
              flex-shrink: 0;
              width: 40px;
              height: 40px;
              display: flex;
              align-items: center;
              justify-content: center;
            }

            .selection-box:hover .icon-wrapper {
              background: #2e7d32;
            }

            .selection-box:hover .icon-wrapper i {
              color: white;
            }

            .selection-box .icon-wrapper i {
              font-size: 1.1rem;
              color: #2e7d32;
              transition: all 0.2s ease;
            }

            .selection-content {
              flex: 1;
              min-width: 0;
            }

            .selection-content label {
              display: block;
              font-size: 0.85rem;
              font-weight: 500;
              color: #2e7d32;
              margin-bottom: 0.35rem;
            }

            .selection-content select {
              width: 100%;
              padding: 0.5rem;
              border: 1px solid #ced4da;
              border-radius: 0.375rem;
              font-size: 0.9rem;
              transition: all 0.2s;
            }

            .selection-content select:hover {
              border-color: #2e7d32;
            }

            .selection-content select:focus {
              border-color: #2e7d32;
              box-shadow: 0 0 0 0.2rem rgba(46,125,50,0.25);
              outline: none;
            }
          </style>

          <form method="POST" id="payrollForm" action="save_payroll.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="add_payroll" value="1">
            <input type="hidden" name="emp_id" id="emp_id" value="" required>
            <input type="hidden" name="department" id="department" value="">
            <input type="hidden" name="position" id="position" value="">
            <!-- Employee Details (Position and ID) -->
          <div class="form-row" id="employee_details" style="display: none;">
            <div class="form-group">
              <label>
                <div class="icon-wrapper">
                  <i class="fas fa-briefcase"></i>
                </div>
                Position
              </label>
              <input type="text" id="emp_position" readonly>
            </div>
            <div class="form-group">
              <label>
                <div class="icon-wrapper">
                  <i class="fas fa-hashtag"></i>
                </div>
                Employee ID
              </label>
              <input type="text" id="emp_id_display" readonly>
            </div>
          </div>
        </div>

        <style>
        .icon-wrapper {
          width: 32px;
          height: 32px;
          background: #e8f5e9;
          border-radius: 8px;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          margin-right: 8px;
          transition: all 0.2s ease;
        }

        .icon-wrapper i {
          color: #2e7d32;
          font-size: 1rem;
        }

        .form-group:hover .icon-wrapper {
          background: #2e7d32;
        }

        .form-group:hover .icon-wrapper i {
          color: white;
        }

        .input-with-icon {
          position: relative;
        }

        .input-with-icon select {
          width: 100%;
          padding-right: 40px;
        }

        #employee_details {
          margin-top: 1rem;
          padding-top: 1rem;
          border-top: 1px dashed #e0e0e0;
          animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
          from { opacity: 0; transform: translateY(-10px); }
          to { opacity: 1; transform: translateY(0); }
        }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const empSelect = document.getElementById('emp_select');
            const empDetails = document.getElementById('employee_details');
            const empPosition = document.getElementById('emp_position');
            const empIdDisplay = document.getElementById('emp_id_display');

            empSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption && selectedOption.value) {
                    empPosition.value = selectedOption.getAttribute('data-position');
                    empIdDisplay.value = selectedOption.value;
                    empDetails.style.display = 'grid';
                } else {
                    empDetails.style.display = 'none';
                }
            });

            // Show details if employee is already selected (edit mode)
            if (empSelect.value) {
                empSelect.dispatchEvent(new Event('change'));
            }
        });
        </script>

          <!-- Work Period Section -->
        <div class="section-card">
          <h3 class="section-title"><i class="fas fa-calendar-alt"></i> Work Period Details</h3>
          <div class="form-row">
            <!-- Pay Period -->
            <div class="form-group">
              <label for="pay_date">
                <div class="icon-wrapper">
                  <i class="fas fa-calendar"></i>
                </div>
                Pay Period Start
              </label>
              <input type="date" id="pay_date" name="pay_date" required 
                     value="<?php echo date('Y-m-d'); ?>" class="form-control">
            </div>

            <!-- Total Work Days -->
            <div class="form-group">
              <label for="work_days">
                <i class="fas fa-calendar-check"></i> Working Days
              </label>
              <input type="text" id="work_days" name="work_days" readonly
                     value="0.00"
                     style="background-color: #f8f9fa;"
                     title="Automatically calculated from work hours">
              <small class="input-help">Auto-calculated: 8h = 1 day, 4h = 0.5 day, 2h = 0.25 day, 1h = 0.125 day</small>
            </div>
          </div>

          <div class="form-row">
            <!-- Work Hours -->
            <div class="form-group">
              <label for="work_hours">
                <i class="fas fa-clock"></i> Total Hours of Work
              </label>
              <input type="number" id="work_hours" name="work_hours" step="1" min="0" max="744" required
                     value="<?= htmlspecialchars($edit['work_hours'] ?? '') ?>" required
                     onchange="calculateWorkingDays(this.value)"
                     oninput="calculateWorkingDays(this.value)"
                     title="Enter total hours worked">
              <small class="input-help">Enter total hours worked (8 hours = 1 working day)</small>
            </div>

            <script>
            function calculateWorkingDays(hours) {
                const workDaysInput = document.getElementById('work_days');
                if (workDaysInput) {
                    const hoursNum = parseFloat(hours) || 0;
                    const days = hoursNum / 8;
                    workDaysInput.value = days.toFixed(2);
                }
            }
            
            // Calculate initial value
            document.addEventListener('DOMContentLoaded', function() {
                const workHours = document.getElementById('work_hours');
                if (workHours) {
                    calculateWorkingDays(workHours.value);
                }
            });
            </script>            <!-- Overtime Hours -->
            <div class="form-group">
              <label for="overtime_hours">
                <i class="fas fa-business-time"></i> Overtime Hours
              </label>
              <input type="number" id="overtime_hours" name="overtime_hours" step="0.5" min="0" 
                     value="<?= htmlspecialchars($edit['overtime_hours'] ?? '0') ?>"
                     onchange="handleOvertimeChange()"
                     oninput="handleOvertimeChange()"
                     title="Cannot input overtime when there are late minutes or absences">
              <small class="input-help">Additional hours (disabled if late/absent)</small>
            </div>

            <script>
            function handleOvertimeChange() {
                const overtimeHours = parseFloat(document.getElementById('overtime_hours').value) || 0;
                const lateMinutesInput = document.getElementById('late_minutes');
                const absentHoursInput = document.getElementById('absent_hours');

                // If there's overtime, disable and clear tardiness inputs
                if (overtimeHours > 0) {
                    lateMinutesInput.value = '0';
                    absentHoursInput.value = '0';
                    lateMinutesInput.disabled = true;
                    absentHoursInput.disabled = true;
                } else {
                    lateMinutesInput.disabled = false;
                    absentHoursInput.disabled = false;
                }
            }

            // Initialize on page load
            document.addEventListener('DOMContentLoaded', function() {
                handleOvertimeChange();
            });
            </script>
          </div>
        </div>

          <!-- Attendance Section -->
        <div class="section-card">
          <h3 class="section-title"><i class="fas fa-user-clock"></i> Tardiness</h3>
          <div class="form-row">
            <!-- Late Minutes -->
            <div class="form-group">
              <label for="late_minutes">
                <i class="fas fa-hourglass-half"></i> Late Minutes
              </label>
              <input type="number" id="late_minutes" name="late_minutes" min="0" 
                     value="<?= htmlspecialchars($edit['late_minutes'] ?? '0') ?>"
                     onchange="handleTardinessChange()"
                     oninput="handleTardinessChange()"
                     title="Cannot input late minutes when overtime is recorded">
              <small class="input-help">Total minutes of tardiness (disabled if overtime exists)</small>
            </div>

            <!-- Absent Hours -->
            <div class="form-group">
              <label for="absent_hours">
                <i class="fas fa-user-clock"></i> Absent Hours
              </label>
              <input type="number" id="absent_hours" name="absent_hours" step="0.5" min="0" 
                     value="<?= htmlspecialchars($edit['absent_hours'] ?? '0') ?>"
                     onchange="handleTardinessChange()"
                     oninput="handleTardinessChange()"
                     title="Cannot input absent hours when overtime is recorded">
              <small class="input-help">Total hours of absence (disabled if overtime exists)</small>
            </div>
          </div>

          <script>
          function handleTardinessChange() {
              const lateMinutes = parseFloat(document.getElementById('late_minutes').value) || 0;
              const absentHours = parseFloat(document.getElementById('absent_hours').value) || 0;
              const overtimeInput = document.getElementById('overtime_hours');

              // If there's any tardiness, disable overtime
              if (lateMinutes > 0 || absentHours > 0) {
                  overtimeInput.value = '0';
                  overtimeInput.disabled = true;
              } else {
                  overtimeInput.disabled = false;
              }
          }
          
          // Initialize on page load
          document.addEventListener('DOMContentLoaded', function() {
              handleTardinessChange();
          });
          </script>
        </div>

        <!-- Deductions Box Section -->
        <div class="section-card">
          <h3 class="section-title"><i class="fas fa-calculator"></i> Total Deductions</h3>
          <div class="deductions-grid">
            <div class="deduction-box">
              <div class="deduction-label">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>SSS (4.5%)</span>
                <div class="tax-controls">
                  <label class="switch" title="Toggle SSS">
                    <input type="checkbox" id="sss_status" name="sss_status" checked>
                    <span class="slider round"></span>
                  </label>
                </div>
              </div>
              <input type="text" id="sss_deduction" readonly>
            </div>
            <div class="deduction-box">
              <div class="deduction-label">
                <i class="fas fa-hospital"></i>
                <span>PhilHealth (3%)</span>
                <div class="tax-controls">
                  <label class="switch" title="Toggle PhilHealth">
                    <input type="checkbox" id="philhealth_status" name="philhealth_status" checked>
                    <span class="slider round"></span>
                  </label>
                </div>
              </div>
              <input type="text" id="philhealth_deduction" readonly>
            </div>
            <div class="deduction-box">
              <div class="deduction-label">
                <i class="fas fa-home"></i>
                <span>Pag-IBIG</span>
                <div class="tax-controls">
                  <label class="switch" title="Toggle Pag-IBIG">
                    <input type="checkbox" id="pagibig_status" name="pagibig_status" checked>
                    <span class="slider round"></span>
                  </label>
                </div>
              </div>
              <input type="text" id="pagibig_deduction" readonly>
            </div>
            <div class="deduction-box">
              <div class="deduction-label">
                <i class="fas fa-receipt"></i>
                <span>Tax</span>
                <div class="tax-controls">
                  <label class="switch" title="Toggle tax status">
                    <input type="checkbox" id="tax_status" name="tax_status" checked>
                    <span class="slider round"></span>
                  </label>
                  <input type="number" id="tax_rate" name="tax_rate" value="10" min="0" max="100" step="0.1" class="tax-rate-input" title="Tax rate percentage">
                  <span class="tax-rate-symbol">%</span>
                </div>
              </div>
              <input type="text" id="tax_deduction" readonly>
            </div>
            <div class="deduction-box">
              <div class="deduction-label">
                <i class="fas fa-user-clock"></i>
                <span>Absent Deduction</span>
              </div>
              <input type="text" id="absent_deduction_total" readonly>
            </div>
          </div>
          <div class="total-deductions">
            <span>Total Deductions:</span>
            <input type="text" id="total_deductions" readonly>
          </div>
        </div>

        <!-- Salary Computation Section -->
        <div class="section-card">
          <h3 class="section-title"><i class="fas fa-calculator"></i> Salary of Employee</h3>
          <div class="form-row">
            <!-- Gross Pay Group -->
            <div class="form-group">
              <label for="gross_pay_total">
                <i class="fas fa-money-bill-wave"></i> Gross Pay
              </label>
              <input type="text" id="gross_pay_total" name="gross_pay" class="form-control" readonly value="₱0.00">
              <small class="input-help">Total of regular hours and overtime pay</small>
            </div>

            <!-- Net Pay Group -->
            <div class="form-group">
              <label for="net_pay_total">
                <i class="fas fa-hand-holding-usd"></i> Net Pay
              </label>
              <input type="text" id="net_pay_total" name="net_pay" class="form-control" readonly>
              <small class="input-help">Gross pay minus total deductions</small>
              
            <!-- Hidden fields for form submission -->
            <input type="hidden" name="emp_select" id="emp_select_hidden">
            <input type="hidden" name="basic_pay" id="basic_pay">
            <input type="hidden" name="overtime_hours" id="overtime_hours">
            <input type="hidden" name="absent_hours" id="absent_hours">
            <input type="hidden" name="late_minutes" id="late_minutes">
            <input type="hidden" name="gross_pay_hidden" id="gross_pay">
            <input type="hidden" name="final_net_pay" id="net_pay">
            <input type="hidden" name="total_deductions" id="total_deductions">
            <input type="hidden" name="sss_input" id="sss">
            <input type="hidden" name="philhealth_input" id="philhealth">
            <input type="hidden" name="pagibig_input" id="pagibig">
            <input type="hidden" name="tax_input" id="tax">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="add_payroll" value="1">
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="form-actions" style="margin-top: 2rem; display: flex; justify-content: flex-start; gap: 1rem; align-items: center;">
            <button type="button" class="btn btn-primary" data-action="submit-payroll">
                <i class="fas fa-save"></i> Submit Payroll
            </button>
            <button type="button" class="btn btn-secondary" id="viewRecordsBtn">
                <i class="fas fa-list"></i> View Records
            </button>
        </div>

        <!-- Records Modal -->
        <div id="recordsModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-file-invoice"></i> Payroll Records</h2>
                    <button class="close-modal"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body">
                    <div class="records-list"></div>
                </div>
            </div>
        </div>

        <!-- Payslip Modal -->
        <div id="payslipModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-file-invoice-dollar"></i> Payslip Details</h2>
                    <button class="close-modal"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body">
                    <div id="payslipContent"></div>
                </div>
                <div class="modal-footer">
                    <button id="exportPayslipBtn" class="btn btn-primary">
                        <i class="fas fa-download"></i> Export Payslip
                    </button>
                </div>
            </div>
        </div>

        <!-- Notification Message -->
        <div id="submitMessage" style="display: none; position: fixed; top: 20px; right: 20px; padding: 15px 25px; border-radius: 4px; text-align: center; font-weight: 500; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.15);"></div>

        <script>
        // Global notification function
        function showNotification(message, isSuccess) {
            const submitMessage = document.getElementById('submitMessage');
            submitMessage.style.backgroundColor = isSuccess ? '#4caf50' : '#f44336';
            submitMessage.style.color = 'white';
            submitMessage.textContent = message;
            submitMessage.style.display = 'block';
            
            setTimeout(() => {
                submitMessage.style.display = 'none';
            }, 3000);
        }

        // Form validation function
        function validateForm(event) {
            event.preventDefault();
            
            const form = event.target;
            const submitBtn = document.getElementById('submitPayrollBtn');
            
            // Get all required fields
            const payDate = form.querySelector('#pay_date');
            const empSelect = form.querySelector('#emp_select');
            const workHours = form.querySelector('#work_hours');

            // Validate pay date
            if (!payDate || !payDate.value) {
                showNotification('Please select a pay date', false);
                payDate?.focus();
                return false;
            }

            // Validate employee selection
            if (!empSelect || !empSelect.value) {
                showNotification('Please select an employee', false);
                empSelect?.focus();
                return false;
            }

            // Validate work hours
            if (!workHours || !workHours.value) {
                showNotification('Please enter work hours', false);
                workHours?.focus();
                return false;
            }

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

            // Submit form
            const formData = new FormData(form);
            
            fetch('save_payroll.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Payroll submitted successfully!', true);
                    form.reset();
                    if (document.getElementById('recordsModal').style.display === 'block') {
                        viewEmployeeRecords();
                    }
                } else {
                    showNotification(data.message || 'Error submitting payroll', false);
                }
            })
            .catch(error => {
                showNotification(error.message || 'Error submitting payroll', false);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> Submit Payroll';
            });

            return false;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const submitBtn = document.getElementById('submitPayrollBtn');
            const submitMessage = document.getElementById('submitMessage');
            
            // Function to show notification
            function showNotification(message, isSuccess) {
                submitMessage.style.backgroundColor = isSuccess ? '#4caf50' : '#f44336';
                submitMessage.style.color = 'white';
                submitMessage.textContent = message;
                submitMessage.style.display = 'block';
                
                setTimeout(() => {
                    submitMessage.style.display = 'none';
                }, 3000);
            }

            if (form) {
                // Remove any existing submit event listeners
                const newForm = form.cloneNode(true);
                form.parentNode.replaceChild(newForm, form);
                
                // Add the submit event listener
                newForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Get required field values
                    const payDate = document.getElementById('pay_date');
                    const empSelect = document.getElementById('emp_select');
                    const workHours = document.getElementById('work_hours');
                    const basicPay = document.getElementById('basic_pay');

                    // Validate pay date
                    if (!payDate || !payDate.value) {
                        showNotification('Please select a pay date', false);
                        payDate.focus();
                        return;
                    }

                    // Validate employee selection
                    if (!empSelect || !empSelect.value) {
                        showNotification('Please select an employee', false);
                        empSelect.focus();
                        return;
                    }

                    // Validate work hours
                    if (!workHours || !workHours.value || parseFloat(workHours.value) <= 0) {
                        showNotification('Please enter work hours greater than 0', false);
                        workHours.focus();
                        return;
                    }

                    // Validate basic pay
                    if (!basicPay || !basicPay.value || parseFloat(basicPay.value) <= 0) {
                        showNotification('Please ensure employee has valid salary', false);
                        return;
                    }

                    // Validate gross pay is calculated
                    const grossPayField = document.getElementById('gross_pay_total');
                    const grossPayValue = grossPayField?.value?.replace(/[^\d.-]/g, '') || '0';
                    if (parseFloat(grossPayValue) <= 0) {
                        showNotification('Please ensure gross pay is calculated. Try selecting an employee and entering work hours.', false);
                        return;
                    }

                    // Show loading state
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

                    // Prepare form data with proper field mapping
                    const formData = new FormData();
                    formData.append('add_payroll', '1');
                    formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                    formData.append('emp_select', empSelect.value);
                    formData.append('pay_date', payDate.value);
                    formData.append('basic_pay', basicPay.value);
                    formData.append('work_hours', workHours.value);
                    formData.append('overtime_hours_input', document.getElementById('overtime_hours')?.value || '0');
                    formData.append('absent_hours_input', document.getElementById('absent_hours')?.value || '0');
                    formData.append('late_minutes_input', document.getElementById('late_minutes')?.value || '0');
                    formData.append('gross_pay', document.getElementById('gross_pay_total')?.value.replace(/[^\d.-]/g, '') || '0');
                    formData.append('total_deductions', document.getElementById('total_deductions')?.value.replace(/[^\d.-]/g, '') || '0');
                    formData.append('final_net_pay', document.getElementById('net_pay_total')?.value.replace(/[^\d.-]/g, '') || '0');
                    formData.append('sss_input', document.getElementById('sss_deduction')?.value.replace(/[^\d.-]/g, '') || '0');
                    formData.append('philhealth_input', document.getElementById('philhealth_deduction')?.value.replace(/[^\d.-]/g, '') || '0');
                    formData.append('pagibig_input', document.getElementById('pagibig_deduction')?.value.replace(/[^\d.-]/g, '') || '0');
                    formData.append('tax_input', document.getElementById('tax_deduction')?.value.replace(/[^\d.-]/g, '') || '0');

                    // Submit the form
                    fetch('save_payroll.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Payroll submitted successfully!',
                                confirmButtonColor: '#2e7d32'
                            }).then(() => {
                                // Reset form
                                newForm.reset();
                                // Reset calculated values
                                resetCalculatedValues();
                                // Refresh records if modal is open
                                if (document.getElementById('recordsModal').style.display === 'block') {
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Error submitting payroll',
                                html: data.errors ? data.errors.join('<br>') : undefined,
                                confirmButtonColor: '#2e7d32'
                            });
                        }
                                loadPayrollRecords();
                            }
                        } else {
                            showNotification(data.message || 'Error submitting payroll', false);
                        }
                    })
                    .catch(error => {
                        console.error('Submission error:', error);
                        showNotification('Error submitting payroll. Please try again.', false);
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-save"></i> Submit Payroll';
                    });
                });

                // Function to reset calculated values
                function resetCalculatedValues() {
                    const resetFields = [
                        'gross_pay_total', 'net_pay_total', 'total_deductions',
                        'sss_deduction', 'philhealth_deduction', 'pagibig_deduction', 'tax_deduction'
                    ];
                    
                    resetFields.forEach(fieldId => {
                        const element = document.getElementById(fieldId);
                        if (element) element.value = '₱0.00';
                    });
                }
        });
        </script>
        </script>

        <!-- Records Modal -->
        <div id="recordsModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-history"></i> Payroll Records</h2>
                    <button class="close-modal" onclick="closeRecordsModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table" id="recordsTable">
                            <thead>
                                <tr>
                                    <th>Reference No.</th>
                                    <th>Employee Name</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Pay Date</th>
                                    <th>Net Pay</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Records will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                    <div id="noRecordsMessage" style="display: none; text-align: center; padding: 2rem;">
                        <i class="fas fa-info-circle" style="font-size: 2rem; color: #666; margin-bottom: 1rem;"></i>
                        <p style="color: #666; margin: 0;">No payroll records found.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        // Function to show/hide modals
        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Function to close records modal
        function closeRecordsModal() {
            closeModal('recordsModal');
        }

        // Function to close payslip modal and show records modal
        function closePayslipModal() {
            closeModal('payslipModal');
            showModal('recordsModal');
        }

        // Function to load and view payroll records
        function loadPayrollRecords() {
            fetch('get_employee_records.php')
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
                .then(data => {
                    const tableBody = document.querySelector('#recordsTable tbody');
                    const noRecordsMessage = document.getElementById('noRecordsMessage');
                    
                    tableBody.innerHTML = '';
                    
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(record => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${record.ref_no}</td>
                                <td>${record.emp_name}</td>
                                <td>${record.department}</td>
                                <td>${record.position}</td>
                                <td>${record.pay_date}</td>
                                <td>₱${parseFloat(record.net_pay).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                                <td>
                                    <button onclick="viewPayslip(${record.id})" class="btn btn-info btn-sm">
                                        <i class="fas fa-file-alt"></i> View
                                    </button>
                                    <button onclick="editPayroll(${record.id})" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button onclick="deletePayroll(${record.id})" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            `;
                            tableBody.appendChild(row);
                        });
                        
                        tableBody.style.display = 'table-row-group';
                        noRecordsMessage.style.display = 'none';
                    } else {
                        tableBody.style.display = 'none';
                        noRecordsMessage.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const tableBody = document.querySelector('#recordsTable tbody');
                    const noRecordsMessage = document.getElementById('noRecordsMessage');
                    
                    tableBody.style.display = 'none';
                    noRecordsMessage.style.display = 'block';
                    noRecordsMessage.innerHTML = `
                        <i class="fas fa-exclamation-circle" style="font-size: 2rem; color: #dc3545; margin-bottom: 1rem;"></i>
                        <p style="color: #dc3545; margin: 0;">Error loading payroll records. Please try again.</p>
                    `;
                });
        }

        // Function to open records modal and load data
        function openRecordsModal() {
            document.getElementById('recordsModal').style.display = 'block';
            loadPayrollRecords();
        }

        // Function to close records modal
        function closeRecordsModal() {
            document.getElementById('recordsModal').style.display = 'none';
        }

        // Function to view payslip
        function viewPayslip(payrollId) {
           fetch(`get_payslip.php?id=${payrollId}`)
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
                    
                    // Generate payslip HTML
                    const payslipHTML = generatePayslipHTML(data);
                    
                    // Show payslip modal
                    document.getElementById('payslipContent').innerHTML = payslipHTML;
                    document.getElementById('recordsModal').style.display = 'none';
                    document.getElementById('payslipModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading payslip. Please try again.');
                });
        }

        // Function to edit payroll
        function editPayroll(payrollId) {
            window.location.href = `payroll.php?edit=${payrollId}`;
        }

        // Function to delete payroll
        function deletePayroll(payrollId) {
            if (confirm('Are you sure you want to delete this payroll record?')) {
                fetch(`delete_payroll.php?id=${payrollId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        csrf_token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Payroll record deleted successfully');
                        loadPayrollRecords(); // Refresh the records
                    } else {
                        alert('Error deleting record: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting record. Please try again.');
                });
            }
        }

        // Function to generate payslip HTML
        function generatePayslipHTML(data) {
            return `
                <div class="payslip">
                    <div class="payslip-header">
                        <h3>Employee Payslip</h3>
                        <p>Reference No: ${data.ref_no || 'N/A'}</p>
                        <p>Pay Period: ${data.pay_date}</p>
                    </div>
                    <div class="payslip-body">
                        <div class="employee-info">
                            <p><strong>Employee:</strong> ${data.emp_name}</p>
                            <p><strong>Department:</strong> ${data.department}</p>
                            <p><strong>Position:</strong> ${data.position}</p>
                        </div>
                        <div class="salary-details">
                            <div class="detail-row">
                                <span>Basic Pay</span>
                                <span>₱${parseFloat(data.basic_pay).toFixed(2)}</span>
                            </div>
                            <div class="detail-row">
                                <span>Hours Worked</span>
                                <span>${data.hours_worked} hrs</span>
                            </div>
                            <div class="detail-row">
                                <span>Overtime Hours</span>
                                <span>${data.overtime_hours || 0} hrs</span>
                            </div>
                            <div class="detail-row">
                                <span>Gross Pay</span>
                                <span>₱${parseFloat(data.gross_pay).toFixed(2)}</span>
                            </div>
                        </div>
                        <div class="deductions">
                            <h4>Deductions</h4>
                            <div class="detail-row">
                                <span>SSS</span>
                                <span>₱${parseFloat(data.sss).toFixed(2)}</span>
                            </div>
                            <div class="detail-row">
                                <span>PhilHealth</span>
                                <span>₱${parseFloat(data.philhealth).toFixed(2)}</span>
                            </div>
                            <div class="detail-row">
                                <span>Pag-IBIG</span>
                                <span>₱${parseFloat(data.pagibig).toFixed(2)}</span>
                            </div>
                            <div class="detail-row">
                                <span>Tax</span>
                                <span>₱${parseFloat(data.tax).toFixed(2)}</span>
                            </div>
                            <div class="detail-row total">
                                <span>Total Deductions</span>
                                <span>₱${parseFloat(data.deductions).toFixed(2)}</span>
                            </div>
                        </div>
                        <div class="net-pay">
                            <div class="detail-row">
                                <span><strong>Net Pay</strong></span>
                                <span><strong>₱${parseFloat(data.net_pay).toFixed(2)}</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Make functions available globally
        window.openRecordsModal = openRecordsModal;
        window.closeRecordsModal = closeRecordsModal;
        window.loadPayrollRecords = loadPayrollRecords;
        window.viewPayslip = viewPayslip;
        window.editPayroll = editPayroll;
        window.deletePayroll = deletePayroll;
        window.generatePayslipHTML = generatePayslipHTML;
        window.viewEmployeeRecords = viewEmployeeRecords;
                });
        }
        </script>

        <!-- Payslip Modal -->
        <div id="payslipModal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="fas fa-file-invoice-dollar"></i> Employee Payslip</h2>
                    <button class="close-modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="payslipContent">
                        <!-- Payslip content will be loaded here -->
                    </div>
                    <div class="modal-footer">
                        <button id="exportPayslipBtn" class="btn" style="background: #dc3545; color: white;">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
            overflow-y: auto;
            padding: 2rem;
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            animation: slideIn 0.3s ease;
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.25rem;
            color: #2e7d32;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            padding: 0.5rem;
            transition: color 0.2s ease;
        }

        .close-modal:hover {
            color: #dc3545;
        }

        /* Records List Styles */
        .records-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .record-item {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
        }

        .record-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-color: #2e7d32;
        }

        .record-info {
            flex: 1;
        }

        .record-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2e7d32;
            margin-bottom: 0.5rem;
        }

        .record-details {
            display: flex;
            gap: 1.5rem;
            color: #666;
            font-size: 0.9rem;
        }

        .record-details span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .record-details i {
            color: #2e7d32;
        }

        .loading-spinner {
            text-align: center;
            padding: 3rem;
        }

        .loading-spinner i {
            font-size: 2rem;
            color: #2e7d32;
            margin-bottom: 1rem;
        }

        .no-records,
        .error-message {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .error-message {
            color: #dc3545;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-content {
            position: relative;
            background: white;
            width: 90%;
            max-width: 800px;
            margin: 50px auto;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease;
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8f9fa;
            border-radius: 8px 8px 0 0;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.25rem;
            color: #2e7d32;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-body {
            padding: 1.5rem;
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }

        .records-list {
            display: grid;
            gap: 1rem;
        }

        .record-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            transition: all 0.2s ease;
            border: 1px solid #e9ecef;
        }

        .record-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-color: #2e7d32;
        }

        .record-info {
            flex: 1;
        }

        .record-name {
            font-weight: 600;
            color: #2e7d32;
            margin-bottom: 0.25rem;
        }

        .record-details {
            font-size: 0.875rem;
            color: #666;
        }

        .record-actions {
            display: flex;
            gap: 0.5rem;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            padding: 0.5rem;
            transition: color 0.2s ease;
        }

        .close-modal:hover {
            color: #dc3545;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        </style>
        
        <!-- Hidden inputs to store calculated values -->
        <input type="hidden" id="calculated_gross_pay" name="calculated_gross_pay" value="0">
        <input type="hidden" id="calculated_net_pay" name="net_pay" value="0">
        <input type="hidden" id="sss" name="sss" value="0">
        <input type="hidden" id="philhealth" name="philhealth" value="0">
        <input type="hidden" id="pagibig" name="pagibig" value="0">
        <input type="hidden" id="tax" name="tax" value="0">
        <input type="hidden" id="total_deductions" name="total_deductions" value="0">
          
        <!-- Payroll Calculation Script -->
          <script>
            // Format currency with ₱ symbol
            function formatMoney(amount) {
                return '₱' + (parseFloat(amount) || 0).toFixed(2);
            }

            // Parse currency value from string
            function parseCurrencyValue(value) {
                return parseFloat(value.replace(/[^0-9.-]+/g, '')) || 0;
            }

            // Main payroll calculation function
            function calculatePayroll() {
                // Get input values
                const basicPayInput = document.getElementById('basic_pay');
                const basicPay = basicPayInput ? parseCurrencyValue(basicPayInput.value) : 0;

                // Update display field
                if (basicPayInput) {
                    basicPayInput.value = basicPay.toFixed(2);
                }
                const workHours = parseFloat(document.getElementById('work_hours').value) || 0;
                const overtimeHours = parseFloat(document.getElementById('overtime_hours').value) || 0;
                const absentHours = parseFloat(document.getElementById('absent_hours').value) || 0;
                const lateMinutes = parseFloat(document.getElementById('late_minutes').value) || 0;
                
                // Get switch states
                const sss_status = document.getElementById('sss_status').checked;
                const philhealth_status = document.getElementById('philhealth_status').checked;
                const pagibig_status = document.getElementById('pagibig_status').checked;
                
                // Constants
                const DAYS_PER_MONTH = 22;
                const HOURS_PER_DAY = 8;
                const STANDARD_HOURS = DAYS_PER_MONTH * HOURS_PER_DAY;
                
                // Calculate hourly rate and gross pay
                const hourlyRate = basicPay / STANDARD_HOURS;
                const grossPay = workHours * hourlyRate;
                
                // Initialize deductions
                let sssDeduction = 0;
                let philhealthDeduction = 0;
                let pagibigAmount = 0;
                
                // Calculate SSS (4.5%) if enabled
                if (sss_status) {
                    sssDeduction = grossPay * 0.045;
                }
                
                // Calculate PhilHealth (3%) if enabled
                if (philhealth_status) {
                    philhealthDeduction = grossPay * 0.03;
                }
                
                // Set Pag-IBIG (Fixed 100) if enabled
                if (pagibig_status) {
                    pagibigAmount = 100;
                }
                
                // Calculate tax if applicable
                let taxDeduction = 0;
                if (document.getElementById('tax_status').checked) {
                    const taxPercentage = parseFloat(document.getElementById('tax_rate').value) || 0;
                    taxDeduction = grossPay * (taxPercentage / 100);
                }
                
                // Calculate total deductions
                const totalDeductions = sssDeduction + philhealthDeduction + pagibigAmount + taxDeduction;
                
                // Calculate net pay
                const netPay = grossPay - totalDeductions;
                
                // Update all display fields with formatted values
                document.getElementById('gross_pay_total').value = formatMoney(grossPay);
                document.getElementById('sss_deduction').value = formatMoney(sssDeduction);
                document.getElementById('philhealth_deduction').value = formatMoney(philhealthDeduction);
                document.getElementById('pagibig_deduction').value = formatMoney(pagibigAmount);
                document.getElementById('tax_deduction').value = formatMoney(taxDeduction);
                document.getElementById('total_deductions').value = formatMoney(totalDeductions);
                document.getElementById('net_pay_total').value = formatMoney(netPay);

                // Update hidden fields with actual numeric values for form submission
                document.getElementById('gross_pay').value = grossPay.toFixed(2);
                document.getElementById('net_pay').value = netPay.toFixed(2);
                document.getElementById('basic_pay').value = basicPay.toFixed(2);
                document.getElementById('work_hours').value = workHours.toString();
                document.getElementById('overtime_hours').value = overtimeHours.toString();
                document.getElementById('absent_hours').value = absentHours.toString();
                document.getElementById('late_minutes').value = lateMinutes.toString();

                // Update other hidden fields for form submission
                document.getElementById('sss').value = sssDeduction.toFixed(2);
                document.getElementById('philhealth').value = philhealthDeduction.toFixed(2);
                document.getElementById('pagibig').value = pagibigAmount.toFixed(2);
                document.getElementById('tax').value = taxDeduction.toFixed(2);
                document.getElementById('total_deductions').value = totalDeductions.toFixed(2);
                
                // Enable/disable submit button based on calculations
                const submitBtn = document.getElementById('submitPayrollBtn');
                if (submitBtn) {
                    submitBtn.disabled = (grossPay <= 0 || netPay <= 0);
                }
                
                console.log('Calculation complete:', {
                    basicPay,
                    workHours,
                    hourlyRate,
                    grossPay,
                    deductions: {
                        sss: sssDeduction,
                        philhealth: philhealthDeduction,
                        pagibig: pagibigAmount,
                        tax: taxDeduction,
                        total: totalDeductions
                    },
                    netPay
                });
            }

            // Add event listeners when document is ready
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize all numeric displays to 0.00
                [
                    'gross_pay_total',
                    'sss_deduction',
                    'philhealth_deduction',
                    'pagibig_deduction',
                    'tax_deduction',
                    'total_deductions',
                    'net_pay_total'
                ].forEach(id => {
                    const element = document.getElementById(id);
                    if (element) element.value = formatMoney(0);
                });
                
                // Add calculation triggers
                [
                    'basic_pay',
                    'work_hours',
                    'tax_status',
                    'tax_rate',
                    'sss_status',
                    'philhealth_status',
                    'pagibig_status'
                ].forEach(id => {
                    const element = document.getElementById(id);
                    if (element) {
                        ['input', 'change'].forEach(event => {
                            element.addEventListener(event, calculatePayroll);
                        });
                    }
                });
                
                // Initial calculation
                calculatePayroll();
            });

            // Function to submit payroll
            function submitPayroll() {
                // Validate required fields
                if (!document.getElementById('calculated_gross_pay').value || 
                    parseFloat(document.getElementById('calculated_gross_pay').value) <= 0) {
                    alert('Please calculate the payroll before submitting.');
                    return;
                }

                // Submit the form
                document.getElementById('payrollForm').submit();
            }

            // Function to calculate gross pay
            function calculateGrossPay() {
                // Get the input values and ensure they are numbers
                const workHours = parseFloat(document.getElementById('work_hours').value) || 0;
                const overtimeHours = parseFloat(document.getElementById('overtime_hours').value) || 0;
                const basicPay = parseCurrencyValue(document.getElementById('basic_pay').value);

                // Calculate hourly rate and overtime rate
                const hourlyRate = basicPay / 176; // 22 days * 8 hours
                const overtimeRate = hourlyRate * 1.25; // 25% overtime premium

                // Calculate regular pay and overtime pay
                const regularPay = workHours * hourlyRate;
                const overtimePay = overtimeHours * overtimeRate;

                // Calculate total gross pay
                const totalGrossPay = regularPay + overtimePay;

                // Update the gross pay display
                const grossPayInput = document.getElementById('gross_pay_total');
                if (grossPayInput) {
                    grossPayInput.value = formatMoney(totalGrossPay);
                }

                return totalGrossPay;
            }

            // Function to calculate net pay
            function calculateNetPay() {
                // First calculate gross pay
                const grossPay = calculateGrossPay();

                // Get all deduction values and ensure they are numbers
                const deductions = {
                    philhealth: parseCurrencyValue(document.getElementById('philhealth').value),
                    gsis: parseCurrencyValue(document.getElementById('gsis').value),
                    pagibig: parseCurrencyValue(document.getElementById('pagibig').value),
                    tax: parseCurrencyValue(document.getElementById('tax').value),
                    sss: parseCurrencyValue(document.getElementById('sss').value)
                };

                // Calculate total deductions
                const totalDeductions = Object.values(deductions).reduce((sum, value) => sum + value, 0);

                // Calculate net pay (gross pay - total deductions)
                const netPay = Math.max(0, grossPay - totalDeductions);

                // Update the net pay display
                const netPayInput = document.getElementById('net_pay_total');
                if (netPayInput) {
                    netPayInput.value = formatMoney(netPay);
                }

                return netPay;
            }

            // Add event listeners when the document is ready
            document.addEventListener('DOMContentLoaded', function() {
                // List of all input fields that should trigger recalculation
                const inputIds = [
                    'work_hours',
                    'overtime_hours',
                    'basic_pay',
                    'philhealth',
                    'gsis',
                    'pagibig',
                    'tax',
                    'sss'
                ];

                // Add event listeners to all inputs
                inputIds.forEach(id => {
                    const input = document.getElementById(id);
                    if (input) {
                        ['input', 'change'].forEach(eventType => {
                            input.addEventListener(eventType, calculateNetPay);
                        });
                    }
                });

                // Initial calculation
                calculateNetPay();
            });
            // Function to format currency
            function formatMoney(amount) {
                return '₱' + parseFloat(amount).toFixed(2);
            }

            // Function to parse currency value
            function parseCurrencyValue(value) {
                return parseFloat(value.replace(/[^0-9.-]+/g, '')) || 0;
            }

            // Function to calculate gross pay
            function calculateGrossPay() {
                // Get the input values
                const workHours = parseFloat(document.getElementById('work_hours').value) || 0;
                const overtimeHours = parseFloat(document.getElementById('overtime_hours').value) || 0;
                const basicPay = parseCurrencyValue(document.getElementById('basic_pay').value);

                // Calculate hourly rate
                const hourlyRate = basicPay / 176; // 22 days * 8 hours
                const overtimeRate = hourlyRate * 1.25;

                // Calculate pays
                const regularPay = workHours * hourlyRate;
                const overtimePay = overtimeHours * overtimeRate;
                const totalGrossPay = regularPay + overtimePay;

                // Update the gross pay display
                const grossPayInput = document.getElementById('gross_pay_total');
                if (grossPayInput) {
                    grossPayInput.value = formatMoney(totalGrossPay);
                }

                return totalGrossPay;
            }

            // Add event listeners
            document.addEventListener('DOMContentLoaded', function() {
                const workHoursInput = document.getElementById('work_hours');
                const overtimeInput = document.getElementById('overtime_hours');
                const basicPayInput = document.getElementById('basic_pay');

                // Function to update all calculations
                function updateValues() {
                    calculateNetPay(); // This will calculate both gross pay and net pay
                }

                // Get all input elements that affect calculations
                const inputIds = [
                    'work_hours',
                    'overtime_hours',
                    'basic_pay',
                    'philhealth',
                    'gsis',
                    'pagibig',
                    'tax',
                    'sss'
                ];

                // Add listeners to all inputs
                inputIds.forEach(id => {
                    const input = document.getElementById(id);
                    if (input) {
                        input.addEventListener('input', updateValues);
                        input.addEventListener('change', updateValues);
                    }
                });

                // Initial calculation
                updateValues();
            });
          </script>
        </div>
          </div>
        </div>

            <script>
            function calculateGrossPay() {
                console.log('Calculating gross pay...'); // Debug log
                
                // Get input elements
                const basicPay = parseFloat(document.getElementById('basic_pay').value) || 0;
                const workHours = parseFloat(document.getElementById('work_hours').value) || 0;
                const overtimeHours = parseFloat(document.getElementById('overtime_hours').value) || 0;
                
                // Calculate rates
                const STANDARD_MONTHLY_HOURS = 176; // 22 days * 8 hours
                const hourlyRate = basicPay / STANDARD_MONTHLY_HOURS;
                const overtimeRate = hourlyRate * 1.25; // 25% overtime premium
                
                // Calculate regular pay
                const regularPay = workHours * hourlyRate;
                
                // Calculate overtime pay
                const overtimePay = overtimeHours * overtimeRate;
                
                // Calculate total gross pay
                const grossPay = regularPay + overtimePay;
                
                console.log('Calculation details:', {
                    basicPay,
                    workHours,
                    overtimeHours,
                    hourlyRate,
                    overtimeRate,
                    regularPay,
                    overtimePay,
                    grossPay
                });
                
                // Format currency
                const formatter = new Intl.NumberFormat('en-PH', {
                    style: 'currency',
                    currency: 'PHP',
                    minimumFractionDigits: 2
                });
                
                // Update displays
                document.getElementById('gross_pay').value = formatter.format(grossPay).replace('PHP', '₱');
                document.getElementById('regular_hours_text').textContent = 
                    `${workHours} hrs × ${formatter.format(hourlyRate).replace('PHP', '₱')} = ${formatter.format(regularPay).replace('PHP', '₱')}`;
                document.getElementById('overtime_hours_text').textContent = 
                    overtimeHours > 0 
                        ? `${overtimeHours} hrs × ${formatter.format(overtimeRate).replace('PHP', '₱')} = ${formatter.format(overtimePay).replace('PHP', '₱')}`
                        : 'No overtime';
                document.getElementById('total_gross_text').textContent = formatter.format(grossPay).replace('PHP', '₱');

                // Trigger calculation of deductions and net pay
                calculateDeductionsAndNetPay();

                // Return gross pay for other calculations
                return grossPay;
            }

            // Add event listeners to trigger calculation
            document.addEventListener('DOMContentLoaded', function() {
                const inputs = ['work_hours', 'overtime_hours', 'basic_pay'];
                inputs.forEach(id => {
                    const input = document.getElementById(id);
                    if (input) {
                        input.addEventListener('input', function() {
                            calculateGrossPay();
                            // Update working days when work hours change
                            if (id === 'work_hours') {
                                const hours = parseFloat(this.value) || 0;
                                const days = hours / 8;
                                document.getElementById('work_days').value = days.toFixed(2);
                            }
                        });
                        input.addEventListener('change', calculateGrossPay);
                    }
                });
                
                // Initial calculation
                calculateGrossPay();
              
          <script>
          document.addEventListener('DOMContentLoaded', function() {
              // Function to calculate net pay
              function calculateNetPay() {
                  // Get all required values
                  const basicPay = parseFloat(document.getElementById('basic_pay').value) || 0;
                  const workHours = parseFloat(document.getElementById('work_hours').value) || 0;
                  const overtimeHours = parseFloat(document.getElementById('overtime_hours').value) || 0;
                  const lateMinutes = parseFloat(document.getElementById('late_minutes').value) || 0;
                  const absentHours = parseFloat(document.getElementById('absent_hours').value) || 0;
                  
                  // Calculate hourly rates
                  const STANDARD_MONTHLY_HOURS = 176; // 22 days * 8 hours
                  const hourlyRate = basicPay / STANDARD_MONTHLY_HOURS;
                  const overtimeRate = hourlyRate * 1.25; // 25% overtime premium
                  const minuteRate = hourlyRate / 60;
                  
                  // Calculate regular and overtime pay
                  const regularPay = workHours * hourlyRate;
                  const overtimePay = overtimeHours * overtimeRate;
                  
                  // Calculate gross pay
                  const grossPay = regularPay + overtimePay;
                  
                  // Calculate attendance deductions
                  const lateDeduction = lateMinutes * minuteRate;
                  const absentDeduction = absentHours * hourlyRate;
                  const attendanceDeductions = lateDeduction + absentDeduction;
                  
                  // Calculate government deductions
                  const taxEnabled = document.getElementById('tax_status').checked;
                  const taxRate = taxEnabled ? (parseFloat(document.getElementById('tax_rate').value) || 0) / 100 : 0;
                  const tax = grossPay * taxRate;
                  const sss = grossPay * 0.045;  // 4.5% SSS
                  const philHealth = grossPay * 0.03;  // 3% PhilHealth
                  const pagibig = 100;  // Fixed ₱100
                  
                  // Calculate total deductions
                  const governmentDeductions = tax + sss + philHealth + pagibig;
                  const totalDeductions = governmentDeductions + attendanceDeductions;
                  
                  // Calculate net pay
                  const netPay = grossPay - totalDeductions;
                  
                  // Format currency values
                  const formatter = new Intl.NumberFormat('en-PH', {
                      style: 'currency',
                      currency: 'PHP',
                      minimumFractionDigits: 2
                  }).format;
                  
                  // Update all displays
                  document.getElementById('gross_pay').value = grossPay.toFixed(2);
                  document.getElementById('sss_deduction').value = formatter(sss).replace('PHP', '₱');
                  document.getElementById('philhealth_deduction').value = formatter(philHealth).replace('PHP', '₱');
                  document.getElementById('tax_deduction').value = formatter(tax).replace('PHP', '₱');
                  document.getElementById('pagibig_deduction').value = formatter(pagibig).replace('PHP', '₱');
                  document.getElementById('total_deductions').value = totalDeductions.toFixed(2);
                  document.getElementById('net_pay').value = netPay.toFixed(2);
                  
                  // Update breakdown displays
                  document.getElementById('regular_hours_text').textContent = 
                      `${workHours} hrs × ₱${hourlyRate.toFixed(2)} = ${formatter(regularPay).replace('PHP', '₱')}`;
                  document.getElementById('overtime_hours_text').textContent = 
                      overtimeHours > 0 
                          ? `${overtimeHours} hrs × ₱${overtimeRate.toFixed(2)} = ${formatter(overtimePay).replace('PHP', '₱')}`
                          : 'No overtime';
                  document.getElementById('net_gross_text').textContent = formatter(grossPay).replace('PHP', '₱');
                  document.getElementById('net_deductions_text').textContent = formatter(totalDeductions).replace('PHP', '₱');
                  document.getElementById('net_calculation_text').textContent = formatter(netPay).replace('PHP', '₱');
                  
                  return { grossPay, totalDeductions, netPay };
              }
                  
                  console.log('Net Pay Calculation:', {
                      grossPay: grossPay.toFixed(2),
                      totalDeductions: totalDeductions.toFixed(2),
                      netPay: netPay.toFixed(2)
                  });
              }

              // Add event listeners for all input fields that affect calculations
              const inputFields = [
                  'basic_pay',
                  'work_hours',
                  'overtime_hours',
                  'late_minutes',
                  'absent_hours',
                  'tax_status',
                  'tax_rate'
              ];
              
              inputFields.forEach(fieldId => {
                  const element = document.getElementById(fieldId);
                  if (element) {
                      element.addEventListener('input', calculateNetPay);
                      element.addEventListener('change', calculateNetPay);
                  }
              });

              // Add listeners for all inputs that affect the calculation
              ['work_hours', 'overtime_hours', 'late_minutes', 'absent_hours', 'basic_pay'].forEach(fieldId => {
                  const element = document.getElementById(fieldId);
                  if (element) {
                      element.addEventListener('change', calculateNetPay);
                      element.addEventListener('input', calculateNetPay);
                  }
              });

              // Add listener for tax switch and rate
              const taxSwitch = document.getElementById('tax_status');
              const taxRate = document.getElementById('tax_rate');
              if (taxSwitch) taxSwitch.addEventListener('change', calculateNetPay);
              if (taxRate) {
                  taxRate.addEventListener('change', calculateNetPay);
                  taxRate.addEventListener('input', calculateNetPay);
              }

              // Initial calculation
              calculateNetPay();
          });

          // Records Modal Functionality
          document.addEventListener('DOMContentLoaded', function() {
              const viewRecordsBtn = document.getElementById('viewRecordsBtn');
              const recordsModal = document.getElementById('recordsModal');
              const payslipModal = document.getElementById('payslipModal');
              const closeButtons = document.querySelectorAll('.close-modal');

              // View Records Button Click
              viewRecordsBtn.addEventListener('click', async function() {
                  const employeeId = document.getElementById('emp_select').value;
                  if (!employeeId) {
                      Swal.fire({
                          icon: 'error',
                          title: 'Error',
                          text: 'Please select an employee first',
                          confirmButtonColor: '#2e7d32'
                      });
                      return;
                  }

                  try {
                      // Show loading state
                      recordsModal.style.display = 'block';
                      const recordsList = recordsModal.querySelector('.records-list');
                      recordsList.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading records...</div>';

                      // Fetch employee's payroll records
                      const response = await fetch(`get_employee_records.php?emp_id=${employeeId}`);
                      const records = await response.json();

                      // Display records
                      if (records.length === 0) {
                          recordsList.innerHTML = '<div class="no-records">No payroll records found for this employee.</div>';
                          return;
                      }

                      recordsList.innerHTML = records.map(record => `
                          <div class="record-item">
                              <div class="record-info">
                                  <div class="record-name">Payroll #${record.ref_no}</div>
                                  <div class="record-details">
                                      <span><i class="fas fa-calendar"></i> ${record.pay_date}</span>
                                      <span><i class="fas fa-money-bill-wave"></i> ₱${parseFloat(record.net_pay).toFixed(2)}</span>
                                  </div>
                              </div>
                              <div class="record-actions">
                                  <button class="btn view-payslip" data-id="${record.id}" 
                                          style="background: #2e7d32; color: white; border: none; border-radius: 4px; padding: 8px 16px;">
                                      <i class="fas fa-file-invoice"></i> View Payslip
                                  </button>
                              </div>
                          </div>
                      `).join('');

                      // Add event listeners for payslip buttons
                      recordsList.querySelectorAll('.view-payslip').forEach(button => {
                          button.addEventListener('click', async function() {
                              const payrollId = this.dataset.id;
                              try {
                                  const response = await fetch(`get_payslip.php?id=${payrollId}`);
                                  const payslipData = await response.json();

                                  // Show payslip modal with data
                                  document.getElementById('payslipContent').innerHTML = generatePayslipHTML(payslipData);
                                  recordsModal.style.display = 'none';
                                  payslipModal.style.display = 'block';

                                  // Handle export button
                                  document.getElementById('exportPayslipBtn').onclick = () => {
                                      window.location.href = `export_payslip.php?id=${payrollId}`;
                                  };
                              } catch (error) {
                                  console.error('Error fetching payslip:', error);
                                  Swal.fire('Error', 'Failed to load payslip', 'error');
                              }
                          });
                      });

                  } catch (error) {
                      console.error('Error fetching records:', error);
                      recordsList.innerHTML = '<div class="error">Failed to load records. Please try again.</div>';
                  }
              });

              // Close Modal Functionality
              closeButtons.forEach(button => {
                  button.addEventListener('click', function() {
                      recordsModal.style.display = 'none';
                      payslipModal.style.display = 'none';
                  });
              });

              // Close modal when clicking outside
              window.addEventListener('click', function(event) {
                  if (event.target === recordsModal) {
                      recordsModal.style.display = 'none';
                  }
                  if (event.target === payslipModal) {
                      payslipModal.style.display = 'none';
                  }
              });
          });

          // Function to generate payslip HTML
          function generatePayslipHTML(data) {
              return `
                  <div class="payslip">
                      <div class="payslip-header">
                          <h3>Employee Payslip</h3>
                          <p>Pay Period: ${data.pay_date}</p>
                      </div>
                      <div class="payslip-body">
                          <div class="employee-info">
                              <p><strong>Employee:</strong> ${data.emp_name}</p>
                              <p><strong>Department:</strong> ${data.department}</p>
                              <p><strong>Position:</strong> ${data.position}</p>
                          </div>
                          <div class="salary-details">
                              <div class="detail-row">
                                  <span>Basic Pay</span>
                                  <span>₱${parseFloat(data.basic_pay).toFixed(2)}</span>
                              </div>
                              <div class="detail-row">
                                  <span>Regular Hours</span>
                                  <span>${data.hours_worked} hrs</span>
                              </div>
                              <div class="detail-row">
                                  <span>Overtime Hours</span>
                                  <span>${data.overtime_hours} hrs</span>
                              </div>
                              <div class="detail-row">
                                  <span>Gross Pay</span>
                                  <span>₱${parseFloat(data.gross_pay).toFixed(2)}</span>
                              </div>
                          </div>
                          <div class="deductions">
                              <h4>Deductions</h4>
                              <div class="detail-row">
                                  <span>SSS</span>
                                  <span>₱${parseFloat(data.sss).toFixed(2)}</span>
                              </div>
                              <div class="detail-row">
                                  <span>PhilHealth</span>
                                  <span>₱${parseFloat(data.philhealth).toFixed(2)}</span>
                              </div>
                              <div class="detail-row">
                                  <span>Pag-IBIG</span>
                                  <span>₱${parseFloat(data.pagibig).toFixed(2)}</span>
                              </div>
                              <div class="detail-row">
                                  <span>Tax</span>
                                  <span>₱${parseFloat(data.tax).toFixed(2)}</span>
                              </div>
                              <div class="detail-row total">
                                  <span>Total Deductions</span>
                                  <span>₱${parseFloat(data.deductions).toFixed(2)}</span>
                              </div>
                          </div>
                          <div class="net-pay">
                              <div class="detail-row">
                                  <span><strong>Net Pay</strong></span>
                                  <span><strong>₱${parseFloat(data.net_pay).toFixed(2)}</strong></span>
                              </div>
                          </div>
                      </div>
                  </div>
              `;
          }
          </script>

          <style>
          .pay-breakdown {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 12px;
            margin: 8px 0;
            font-size: 0.9rem;
          }

          .breakdown-item {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            color: #2e7d32;
          }

          .breakdown-item + .breakdown-item {
            border-top: 1px dashed #e9ecef;
            margin-top: 4px;
            padding-top: 8px;
          }

          .breakdown-item.total {
            border-top: 2px solid #2e7d32;
            margin-top: 8px;
            padding-top: 8px;
            font-weight: 600;
          }

          /* Detailed Computation Styles */
          .computation-breakdown {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
          }

          .computation-details {
            font-size: 0.9rem;
          }

          .computation-section {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px dashed #dee2e6;
          }

          .computation-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
          }

          .computation-section h4 {
            color: #2e7d32;
            font-size: 0.95rem;
            margin: 0 0 0.5rem 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
          }

          .computation-section h4 i {
            font-size: 0.9rem;
            background: #e8f5e9;
            padding: 0.5rem;
            border-radius: 4px;
          }

          .computation-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.25rem;
            padding: 0.25rem 0;
          }

          .computation-item span:first-child {
            color: #555;
          }

          .computation-item span:last-child {
            font-family: monospace;
            color: #2e7d32;
            font-weight: 500;
          }

          .computation-section.total {
            background: #e8f5e9;
            padding: 1rem;
            border-radius: 6px;
            margin-top: 1rem;
          }

          .computation-item.final {
            font-weight: 600;
            color: #1b5e20;
            font-size: 1.1rem;
            border-top: 2px solid #2e7d32;
            padding-top: 0.5rem;
            margin-top: 0.5rem;
          }
          </style>



          <script>
          function calculateDeductionsAndNetPay() {
              // Get base values
              const basicPay = parseFloat(document.getElementById('basic_pay').value) || 0;
              const workHours = parseFloat(document.getElementById('work_hours').value) || 0;
              const overtimeHours = parseFloat(document.getElementById('overtime_hours').value) || 0;
              const lateMinutes = parseFloat(document.getElementById('late_minutes').value) || 0;
              const absentHours = parseFloat(document.getElementById('absent_hours').value) || 0;
              
              // Constants for calculations
              const STANDARD_MONTHLY_HOURS = 176; // 22 days * 8 hours
              const OVERTIME_RATE = 1.25;         // 25% overtime premium
              
              // Calculate rates
              const hourlyRate = basicPay / STANDARD_MONTHLY_HOURS;
              const minuteRate = hourlyRate / 60;
              const overtimeHourlyRate = hourlyRate * OVERTIME_RATE;
              
              // Calculate regular and overtime pay
              const regularPay = workHours * hourlyRate;
              const overtimePay = overtimeHours * overtimeHourlyRate;
              
              // Calculate gross pay
              const grossPay = regularPay + overtimePay;
              
              // Attendance deductions
              const lateDeduction = lateMinutes * minuteRate;
              const absentDeduction = absentHours * hourlyRate;
              const attendanceDeductions = lateDeduction + absentDeduction;
              
              // Government mandated deductions
              const taxEnabled = document.getElementById('tax_status').checked;
              const taxRateInput = document.getElementById('tax_rate');
              const taxRatePercentage = taxEnabled ? (parseFloat(taxRateInput.value) || 0) / 100 : 0;
              
              const tax = taxEnabled ? (grossPay * taxRatePercentage) : 0;
              const sss = grossPay * 0.045;         // 4.5% SSS
              const philHealth = grossPay * 0.03;   // 3% PhilHealth
              const pagibig = 100;                  // Fixed ₱100
              
              // Calculate attendance-related deductions
              const lateHours = lateMinutes / 60; // Convert late minutes to hours
              const totalAbsentHours = absentHours + lateHours; // Combine both absences
              const totalAttendanceDeduction = totalAbsentHours * hourlyRate; // Calculate total deduction
              
              // Log attendance calculations for debugging
              console.log('Attendance Deductions:', {
                  lateMinutes,
                  lateHours: lateHours.toFixed(2),
                  absentHours,
                  totalAbsentHours: totalAbsentHours.toFixed(2),
                  hourlyRate: hourlyRate.toFixed(2),
                  totalAttendanceDeduction: totalAttendanceDeduction.toFixed(2)
              });
              
              // Update absent deduction box with detailed breakdown on hover
              const absentDeductionElement = document.getElementById('absent_deduction_total');
              absentDeductionElement.value = '₱' + totalAttendanceDeduction.toFixed(2);
              absentDeductionElement.title = `Absences Breakdown:\nLate Minutes: ${lateMinutes} mins (${lateHours.toFixed(2)} hrs)\nAbsent Hours: ${absentHours} hrs\nTotal Hours: ${totalAbsentHours.toFixed(2)} hrs\nRate per Hour: ₱${hourlyRate.toFixed(2)}\nTotal Deduction: ₱${totalAttendanceDeduction.toFixed(2)}`;
              
              // Calculate total deductions
              const mandatoryDeductions = tax + sss + philHealth + pagibig;
              const totalDeductions = mandatoryDeductions + totalAttendanceDeduction;
              
              // Calculate net pay
              const netPay = grossPay - totalDeductions;
              
              // Update deduction box displays with proper formatting and hover details
              const sssElement = document.getElementById('sss_deduction');
              sssElement.value = '₱' + sss.toFixed(2);
              sssElement.title = `4.5% of Gross Pay (₱${grossPay.toFixed(2)})`;
              
              const philhealthElement = document.getElementById('philhealth_deduction');
              philhealthElement.value = '₱' + philHealth.toFixed(2);
              philhealthElement.title = `3% of Gross Pay (₱${grossPay.toFixed(2)})`;
              
              const pagibigElement = document.getElementById('pagibig_deduction');
              pagibigElement.value = '₱' + pagibig.toFixed(2);
              pagibigElement.title = 'Fixed Pag-IBIG contribution';
              
              const taxElement = document.getElementById('tax_deduction');
              taxElement.value = '₱' + tax.toFixed(2);
              taxElement.title = `10% of Gross Pay (₱${grossPay.toFixed(2)})`;
              
              // Total deductions box with complete breakdown on hover
              const totalDeductionsElement = document.getElementById('total_deductions');
              totalDeductionsElement.value = '₱' + totalDeductions.toFixed(2);
              totalDeductionsElement.title = 
                  `SSS: ₱${sss.toFixed(2)}\n` +
                  `PhilHealth: ₱${philHealth.toFixed(2)}\n` +
                  `Pag-IBIG: ₱${pagibig.toFixed(2)}\n` +
                  `Tax: ₱${tax.toFixed(2)}\n` +
                  `Late Deduction: ₱${lateDeduction.toFixed(2)}\n` +
                  `Absent Deduction: ₱${absentDeduction.toFixed(2)}`;
              
              document.getElementById('net_pay').value = netPay.toFixed(2);
              
              // Comprehensive debugging log
              console.log('Payroll Calculation Details:', {
                  input: {
                      basicPay: basicPay.toFixed(2),
                      workHours,
                      lateMinutes,
                      absentHours,
                      grossPay: grossPay.toFixed(2)
                  },
                  rates: {
                      hourlyRate: hourlyRate.toFixed(2),
                      minuteRate: minuteRate.toFixed(2)
                  },
                  deductions: {
                      government: {
                          sss: sss.toFixed(2),
                          philHealth: philHealth.toFixed(2),
                          pagibig: pagibig.toFixed(2),
                          tax: tax.toFixed(2),
                          total: mandatoryDeductions.toFixed(2)
                      },
                      attendance: {
                          late: lateDeduction.toFixed(2),
                          absent: absentDeduction.toFixed(2),
                          total: totalAttendanceDeduction.toFixed(2)
                      },
                      total: totalDeductions.toFixed(2)
                  },
                  final: {
                      grossPay: grossPay.toFixed(2),
                      totalDeductions: totalDeductions.toFixed(2),
                      netPay: netPay.toFixed(2)
                  }
              });
          }

          // Add event listeners to trigger calculations
          document.addEventListener('DOMContentLoaded', function() {
              const inputs = ['work_hours', 'overtime_hours', 'basic_pay', 'late_minutes', 'absent_hours'];
              
              inputs.forEach(id => {
                  const input = document.getElementById(id);
                  if (input) {
                      // Add real-time calculation on input
                      input.addEventListener('input', function() {
                          validateAndCalculate(this);
                          calculateDeductionsAndNetPay();
                      });
                      
                      // Add final calculation on change
                      input.addEventListener('change', function() {
                          validateAndCalculate(this);
                          calculateDeductionsAndNetPay();
                      });
                  }
              });

              // Function to validate inputs and ensure proper calculations
              function validateAndCalculate(input) {
                  const value = parseFloat(input.value) || 0;
                  
                  switch(input.id) {
                      case 'late_minutes':
                          // Ensure late minutes don't exceed 480 (8 hours * 60 minutes)
                          if (value > 480) {
                              input.value = 480;
                              alert('Late minutes cannot exceed 480 (8 hours)');
                          }
                          // Ensure non-negative value
                          if (value < 0) {
                              input.value = 0;
                          }
                          break;
                          
                      case 'absent_hours':
                          // Ensure absent hours don't exceed total work hours
                          const workHours = parseFloat(document.getElementById('work_hours').value) || 0;
                          if (value > workHours) {
                              input.value = workHours;
                              alert('Absent hours cannot exceed total work hours');
                          }
                          // Ensure non-negative value
                          if (value < 0) {
                              input.value = 0;
                          }
                          break;
                          
                      case 'work_hours':
                          // Validate work hours (max 744 hours per month - 31 days × 24 hours)
                          if (value > 744) {
                              input.value = 744;
                              alert('Work hours cannot exceed 744 hours per month');
                          }
                          // Ensure non-negative value
                          if (value < 0) {
                              input.value = 0;
                          }
                          break;
                  }
              }

              // Tax switch and rate input event listeners
              const taxSwitch = document.getElementById('tax_status');
              const taxRateInput = document.getElementById('tax_rate');

              taxSwitch.addEventListener('change', function() {
                  taxRateInput.disabled = !this.checked;
                  if (!this.checked) {
                      taxRateInput.value = '0';
                  } else {
                      taxRateInput.value = '10';
                  }
                  calculateDeductionsAndNetPay();
              });

              taxRateInput.addEventListener('input', calculateDeductionsAndNetPay);
              
              // Initialize tax rate input state
              taxRateInput.disabled = !taxSwitch.checked;
              
              // Initial calculation
              calculateDeductionsAndNetPay();
          });
          </script>


        </div>
        
          <!-- Summary and Action Buttons -->
        <div class="action-section">
          <!-- Payroll Summary -->
          <div class="computation-preview" id="summaryBox" style="display: none;">
            <div class="summary-row">
              <span>Gross Pay:</span>
              <strong id="summary_gross">₱0.00</strong>
            </div>
            <div class="summary-row">
              <span>Total Deductions:</span>
              <strong id="summary_deductions">₱0.00</strong>
            </div>
            <div class="summary-row total">
              <span>Net Pay:</span>
              <strong id="summary_net">₱0.00</strong>
            </div>
          </div>

          <!-- Removed original button group -->

          <script>
          function submitPayroll() {
              // Get all the form values
              const formData = {
                  emp_id: document.getElementById('emp_select').value,
                  pay_date: document.getElementById('pay_date').value,
                  basic_pay: document.getElementById('basic_pay').value,
                  work_hours: document.getElementById('work_hours').value,
                  overtime_hours: document.getElementById('overtime_hours').value,
                  late_minutes: document.getElementById('late_minutes').value,
                  absent_hours: document.getElementById('absent_hours').value,
                  gross_pay: document.getElementById('gross_pay').value,
                  total_deductions: document.getElementById('total_deductions').value,
                  net_pay: document.getElementById('net_pay').value
              };

              // Validate required fields
              if (!formData.emp_id || !formData.pay_date || !formData.work_hours) {
                  alert('Please fill in all required fields');
                  return;
              }

              // Submit the form
              fetch('save_payroll.php', {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/json',
                  },
                  body: JSON.stringify(formData)
              })
              .then(response => response.json())
              .then(data => {
                  if (data.success) {
                      alert('Payroll submitted successfully!');
                      window.location.href = 'reports.php';
                  } else {
                      alert('Error: ' + data.message);
                  }
              })
              .catch(error => {
                  console.error('Error:', error);
                  alert('Error submitting payroll');
              });
          }
          </script>
          </div>
        </div>

        <script>
            // Store employees data for filtering
            const employeesByDept = <?= json_encode($employeesByDept) ?>;

            // Wait for the document to be ready
            document.addEventListener('DOMContentLoaded', function() {
                // Get the input elements
                const workHoursInput = document.getElementById('work_hours');
                const workDaysInput = document.getElementById('work_days');

                // Simple function to calculate and update working days
                function calculateDays() {
                    // Get hours value and convert to number
                    let hours = workHoursInput.value;
                    hours = parseFloat(hours) || 0;
                    
                    // Calculate days (8 hours = 1 day)
                    let days = hours / 8;
                    
                    // Update the working days input with 2 decimal places
                    workDaysInput.value = days.toFixed(2);
                }

                // Add the event listener to work hours input
                if (workHoursInput) {
                    workHoursInput.addEventListener('input', calculateDays);
                    
                    // Calculate initial value if exists
                    calculateDays();
                } else {
                    console.error('Work hours input not found!');
                }
            });

            // Calculation functions and event handlers
            document.addEventListener('DOMContentLoaded', function() {
                // Main input elements
                const basicPayInput = document.getElementById('basic_pay');
                const workHoursInput = document.getElementById('work_hours');
                const workDaysInput = document.getElementById('work_days');
                const dailyRateInput = document.getElementById('daily_rate');
                const hourlyRateInput = document.getElementById('hourly_rate');
                const overtimeInput = document.getElementById('overtime_hours');
                const lateInput = document.getElementById('late_minutes');
                const absentInput = document.getElementById('absent_hours');

                // Handle conflicts between overtime and tardiness
                overtimeInput.addEventListener('input', function() {
                    const overtimeValue = parseFloat(this.value) || 0;
                    // Can't have overtime if there are late minutes or absences
                    if (overtimeValue > 0) {
                        lateInput.value = '0';
                        absentInput.value = '0';
                        lateInput.disabled = true;
                        absentInput.disabled = true;
                    } else {
                        lateInput.disabled = false;
                        absentInput.disabled = false;
                    }
                    calculateSalary();
                });

                lateInput.addEventListener('input', function() {
                    const lateValue = parseFloat(this.value) || 0;
                    if (lateValue > 0) {
                        // Disable and reset overtime
                        overtimeInput.value = '0';
                        overtimeInput.disabled = true;
                        // Keep absent hours enabled
                        absentInput.disabled = false;
                    } else if (parseFloat(absentInput.value || 0) === 0) {
                        // Enable overtime only if both late and absent are zero
                        overtimeInput.disabled = false;
                    }
                    calculateSalary();
                });

                absentInput.addEventListener('input', function() {
                    const absentValue = parseFloat(this.value) || 0;
                    if (absentValue > 0) {
                        // Disable and reset overtime
                        overtimeInput.value = '0';
                        overtimeInput.disabled = true;
                        // Keep late minutes enabled
                        lateInput.disabled = false;
                    } else if (parseFloat(lateInput.value || 0) === 0) {
                        // Enable overtime only if both late and absent are zero
                        overtimeInput.disabled = false;
                    }
                    calculateSalary();
                });

                // Add event listener for work hours input
                workHoursInput.addEventListener('input', function() {
                    updateWorkingDays();
                    calculateSalary();
                });

                // Calculate rates whenever basic pay changes
                basicPayInput.addEventListener('input', calculateRates);
                
          // Add input handlers for all fields that affect the calculation
          const calculationFields = ['work_hours', 'overtime_hours', 'late_minutes', 'absent_hours', 'basic_pay'];
          calculationFields.forEach(fieldId => {
              const element = document.getElementById(fieldId);
              if (element) {
                  element.addEventListener('input', function() {
                      computePayroll();
                  });
                  element.addEventListener('change', function() {
                      computePayroll();
                  });
              }
          });

          // Add handler for tax switch
          document.getElementById('tax_status').addEventListener('change', computePayroll);
          document.getElementById('tax_rate').addEventListener('input', computePayroll);                // Initial calculation if value exists
                if (workHoursInput.value) {
                    workHoursInput.dispatchEvent(new Event('input'));
                }

                // Function to format currency
                function formatCurrency(amount) {
                    return '₱' + parseFloat(amount).toFixed(2);
                }

                // Function to handle overtime and attendance conflicts
                function handleAttendanceConflicts() {
                    const overtimeInput = document.getElementById('overtime_hours');
                    const lateInput = document.getElementById('late_minutes');
                    const absentInput = document.getElementById('absent_hours');

                    // Function to reset input value
                    function resetInput(input) {
                        input.value = '0';
                        input.disabled = true;
                    }

                    // Function to enable input
                    function enableInput(input) {
                        input.disabled = false;
                    }

                    // When overtime is entered
                    overtimeInput.addEventListener('input', function() {
                        if (parseFloat(this.value) > 0) {
                            // If overtime has value, disable and reset both late and absent
                            resetInput(lateInput);
                            resetInput(absentInput);
                        } else {
                            // If overtime is empty/zero, enable both
                            enableInput(lateInput);
                            enableInput(absentInput);
                        }
                        calculateSalary();
                    });

                    // When late minutes are entered
                    lateInput.addEventListener('input', function() {
                        if (parseFloat(this.value) > 0) {
                            // If late minutes has value, disable overtime but keep absent enabled
                            resetInput(overtimeInput);
                            enableInput(absentInput);
                        } else if (parseFloat(absentInput.value) === 0) {
                            // Only enable overtime if both late and absent are zero
                            enableInput(overtimeInput);
                        }
                        calculateSalary();
                    });

                    // When absent hours are entered
                    absentInput.addEventListener('input', function() {
                        if (parseFloat(this.value) > 0) {
                            // If absent hours has value, disable overtime but keep late enabled
                            resetInput(overtimeInput);
                            enableInput(lateInput);
                        } else if (parseFloat(lateInput.value) === 0) {
                            // Only enable overtime if both late and absent are zero
                            enableInput(overtimeInput);
                        }
                        calculateSalary();
                    });
                }

          // Function to calculate all salary components
          function calculateSalary() {
            // Constants
            const HOURS_PER_DAY = 8;
            const STANDARD_MONTHLY_HOURS = 176; // 22 days * 8 hours
            const OVERTIME_RATE = 1.25; // 25% additional for overtime
            const PAGIBIG_FIXED = 100;
            const SSS_RATE = 0.045; // 4.5%
            const PHILHEALTH_RATE = 0.03; // 3%
            
            // Get all input values
            const monthlySalary = parseFloat(document.getElementById('basic_pay').value) || 0;
            const workHours = parseFloat(document.getElementById('work_hours').value) || 0;
            const absentHours = parseFloat(document.getElementById('absent_hours').value) || 0;
            const lateMinutes = parseFloat(document.getElementById('late_minutes').value) || 0;
            const overtimeHours = parseFloat(document.getElementById('overtime_hours').value) || 0;
            
            // Calculate rates
            const hourlyRate = monthlySalary / STANDARD_MONTHLY_HOURS;
            const minuteRate = hourlyRate / 60; // For late minutes calculation                    // Calculate hourly rate from monthly salary
                    const hourlyRate = monthlySalary / STANDARD_MONTHLY_HOURS;
                    const minuteRate = hourlyRate / 60;
                    
                    // Get work hours for calculation
                    const totalWorkHours = parseFloat(workHoursInput.value) || 0;
                    
                    // Log for debugging
                    console.log('Work Hours:', totalWorkHours, 'Days:', calculatedWorkDays.toFixed(2));
                    
                    // Calculate regular hours pay
                    const regularPay = hourlyRate * workHours;
                    
                    // Calculate overtime pay (only if no tardiness)
                    let overtimePay = 0;
                    // Update gross pay display after calculation
                    document.getElementById('gross_pay').value = grossPay.toFixed(2);
                    
                    // Update breakdown display
                    document.getElementById('regular_hours_text').textContent = 
                        `${workHours} hrs × ₱${hourlyRate.toFixed(2)} = ₱${regularHoursPay.toFixed(2)}`;
                    
                    document.getElementById('overtime_hours_text').textContent = 
                        overtimeHours > 0 
                            ? `${overtimeHours} hrs × ₱${(hourlyRate * 1.25).toFixed(2)} = ₱${overtimePay.toFixed(2)}`
                            : 'No overtime';

              // Calculate regular pay and overtime pay
              const regularPay = workHours * hourlyRate;
              const overtimePay = overtimeHours * hourlyRate * OVERTIME_RATE;
              
              // Calculate gross pay
              const grossPay = regularPay + overtimePay;
              
              // Calculate attendance deductions
              const lateDeduction = lateMinutes * minuteRate;
              const absentDeduction = absentHours * hourlyRate;
              const totalAttendanceDeduction = lateDeduction + absentDeduction;
              
              // Get tax switch status and rate
              const isTaxable = document.getElementById('tax_status').checked;
              const taxRate = isTaxable ? (parseFloat(document.getElementById('tax_rate').value) || 0) / 100 : 0;
              
              // Calculate statutory deductions
              const tax = isTaxable ? grossPay * taxRate : 0;
              const sss = grossPay * SSS_RATE;
              const philHealth = grossPay * PHILHEALTH_RATE;
              
              // Calculate total deductions
              const totalDeductions = tax + sss + philHealth + PAGIBIG_FIXED + totalAttendanceDeduction;
              
              // Calculate net pay
              const netPay = grossPay - totalDeductions;
              
              // Update all displays with formatted values
              const formatter = new Intl.NumberFormat('en-PH', {
                  style: 'currency',
                  currency: 'PHP',
                  minimumFractionDigits: 2
              });
              
              // Update gross pay display
              document.getElementById('gross_pay').value = grossPay.toFixed(2);
              
              // Update deductions displays
              document.getElementById('sss_deduction').value = formatter.format(sss).replace('PHP', '₱');
              document.getElementById('philhealth_deduction').value = formatter.format(philHealth).replace('PHP', '₱');
              document.getElementById('tax_deduction').value = formatter.format(tax).replace('PHP', '₱');
              document.getElementById('absent_deduction_total').value = formatter.format(totalAttendanceDeduction).replace('PHP', '₱');
              document.getElementById('total_deductions').value = formatter.format(totalDeductions).replace('PHP', '₱');
              
              // Update net pay and its breakdown
              document.getElementById('net_pay').value = netPay.toFixed(2);
              document.getElementById('net_gross_text').textContent = formatter.format(grossPay).replace('PHP', '₱');
              document.getElementById('net_deductions_text').textContent = formatter.format(totalDeductions).replace('PHP', '₱');
              document.getElementById('net_calculation_text').textContent = formatter.format(netPay).replace('PHP', '₱');
              
              // Update absent deduction display with breakdown
              const absentBreakdown = `Late (${lateMinutes} mins): ₱${lateDeduction.toFixed(2)}\nAbsent (${absentHours} hrs): ₱${absentDeduction.toFixed(2)}`;
              const absentDisplay = document.getElementById('absent_deduction_total');
              absentDisplay.value = `₱${totalAbsentDeduction.toFixed(2)}`;
              absentDisplay.title = absentBreakdown; // Show breakdown on hover                    // Calculate net pay
                    const netPay = grossPay - totalDeductions;

                    // Update all display fields with proper formatting
                    document.getElementById('gross_pay').value = grossPay.toFixed(2);
                    document.getElementById('total_deductions').value = totalDeductions.toFixed(2);
                    document.getElementById('net_pay').value = netPay.toFixed(2);
                    
                    // Update working days display
                    const workDays = workHours / HOURS_PER_DAY;
                    workDaysInput.value = workDays.toFixed(2);
                    
                    // Update breakdown display
                    document.getElementById('regular_hours_text').textContent = 
                        `${workHours} hrs × ₱${hourlyRate.toFixed(2)} = ₱${regularPay.toFixed(2)}`;
                    
                    document.getElementById('overtime_hours_text').textContent = 
                        overtimeHours > 0 
                            ? `${overtimeHours} hrs × ₱${(hourlyRate * OVERTIME_RATE).toFixed(2)} = ₱${overtimePay.toFixed(2)}`
                            : 'No overtime';

                    // Update computation preview if elements exist
                    const previewElements = {
                        'p_workdays': workDays.toFixed(2) + ' days',
                        'p_std_hours': workHours.toFixed(2) + ' hours',
                        'p_absent_hours': absentHours.toFixed(2) + ' hours',
                        'p_late_mins': lateMinutes + ' mins',
                        'p_actual_hours': (workHours - absentHours).toFixed(2) + ' hours',
                        'p_hourly_rate': formatCurrency(hourlyRateBasic),
                        'p_gross': formatCurrency(grossPay),
                        'p_tax': formatCurrency(tax),
                        'p_sss': formatCurrency(sss),
                        'p_ph': formatCurrency(philHealth),
                        'p_pagibig': formatCurrency(PAGIBIG_FIXED),
                        'p_deduct': formatCurrency(totalDeductions),
                        'p_net': formatCurrency(netPay)
                    };

                    // Update each preview element if it exists
                    Object.entries(previewElements).forEach(([id, value]) => {
                        const element = document.getElementById(id);
                        if (element) {
                            element.textContent = value;
                        }
                    });

                    // Enable/disable submit button based on required fields
                    const submitBtn = document.getElementById('submitBtn');
                    if(submitBtn) {
                        submitBtn.disabled = !(basicPay > 0 && workHours > 0);
                    }
                }

                // Initialize attendance conflict handling
                handleAttendanceConflicts();

                // Attach event listeners for real-time calculation
                const inputFields = ['work_hours', 'basic_pay'];
                inputFields.forEach(fieldId => {
                    const element = document.getElementById(fieldId);
                    if(element) {
                        element.addEventListener('input', calculateSalary);
                    }
                });

                // Calculate on employee selection change
                document.getElementById('emp_select').addEventListener('change', calculateSalary);

                // Initial calculation
                calculateSalary();

                // Show/hide records table
                document.getElementById('viewRecordsBtn').addEventListener('click', function() {
                    document.querySelector('.table-container').style.display = 'block';
                    document.querySelector('.table-container').scrollIntoView({ behavior: 'smooth' });
                });
            });
        </script>

        <style>
        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-left: auto;
        }

        #viewRecordsBtn {
            background: #1976d2;
        }

        #viewRecordsBtn:hover {
            background: #1565c0;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }

        .btn.primary {
            background: #2e7d32;
        }

        .btn.primary:hover {
            background: #1b5e20;
        }

        .computation-preview {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.375rem;
            border: 1px solid var(--border);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.25rem 0;
            font-size: 0.875rem;
        }

        .summary-row.total {
            border-top: 1px solid var(--border);
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            font-size: 1rem;
            font-weight: 500;
        }

        .summary-row strong {
            color: var(--primary);
        }

        .summary-row.total strong {
            font-size: 1.1rem;
        }

        /* Deductions Grid Styles */
        .deductions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .deduction-box:last-child {
            grid-column: 1 / -1;
            background-color: #fff3e0;
        }
        
        .deduction-label small {
            font-size: 0.75rem;
            color: #666;
            margin-left: 0.25rem;
        }

        .deduction-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            transition: all 0.2s ease;
        }

        .deduction-box:hover {
            border-color: #2e7d32;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .deduction-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: #2e7d32;
            font-weight: 500;
        }

        .deduction-label i {
            font-size: 1rem;
        }

        .deduction-box input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            background-color: #fff;
            text-align: right;
            font-family: monospace;
            font-size: 1rem;
            color: #2e7d32;
        }

        /* Tax Controls Styles */
        .tax-controls {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-left: 1rem;
        }

        .deduction-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
            color: #2e7d32;
            font-weight: 500;
            position: relative;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 46px;
            height: 22px;
            margin: 0 4px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .3s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
        }

        .slider.round {
            border-radius: 22px;
        }

        .slider.round:before {
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #2e7d32;
        }

        input:checked + .slider:before {
            transform: translateX(24px);
        }

        .tax-rate-input {
            width: 50px !important;
            padding: 0.25rem !important;
            text-align: center !important;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            font-size: 0.9rem;
            margin: 0 2px;
        }

        .tax-rate-input:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
            border-color: #ced4da;
        }

        .tax-label {
            position: absolute;
            left: 54px;
            white-space: nowrap;
            font-size: 0.75rem;
            color: #666;
            transform: translateY(1px);
        }

        .tax-rate-symbol {
            font-size: 0.85rem;
            color: #666;
            margin-left: 1px;
        }

        .total-deductions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }

        .total-deductions span {
            font-weight: 600;
            color: #2e7d32;
        }

        .total-deductions input {
            width: 200px;
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            background-color: #f8f9fa;
            text-align: right;
            font-family: monospace;
            font-size: 1.1rem;
            font-weight: 600;
            color: #2e7d32;
        }
        </style>


      </form>
    </div>

    <div class="table-container content-card" style="display: none;">
      <div class="table-responsive">
        <table class="table" id="payrollTable">
          <thead>
            <tr>
              <th onclick="sortTable(0)">Employee <i class="fas fa-sort"></i></th>
              <th onclick="sortTable(1)">Department <i class="fas fa-sort"></i></th>
              <th onclick="sortTable(2)">Date <i class="fas fa-sort"></i></th>
              <th>Rate Type</th>
              <th>Days</th>
              <th>Hours</th>
              <th onclick="sortTable(6)">Gross <i class="fas fa-sort"></i></th>
              <th>Tax</th>
              <th>SSS</th>
              <th>PhilHealth</th>
              <th>Pag-IBIG</th>
              <th onclick="sortTable(11)">Deductions <i class="fas fa-sort"></i></th>
              <th onclick="sortTable(12)">Net Pay <i class="fas fa-sort"></i></th>
              <th>Action</th>
            </tr>
          </thead>
        <tbody>
          <?php 
          $total_gross = $total_deductions = $total_net = 0;
          if($payrolls->num_rows): 
            while($r=$payrolls->fetch_assoc()):
              $total_gross += $r['basic_pay'];
              $total_deductions += $r['deductions'];
              $total_net += $r['net_pay'];
          ?>
            <tr>
              <td><?= htmlspecialchars($r['emp_name']) ?></td>
              <td><?= htmlspecialchars($r['department']) ?></td>
              <td><?= htmlspecialchars($r['pay_date']) ?></td>
              <td><?= htmlspecialchars(ucfirst($r['rate_type'])) ?></td>
              <td><?= htmlspecialchars($r['days_worked']) ?></td>
              <td><?= htmlspecialchars($r['hours_worked']) ?></td>
              <td>₱<?= number_format($r['basic_pay'],2) ?></td>
              <td>₱<?= number_format($r['tax'],2) ?></td>
              <td>₱<?= number_format($r['sss'],2) ?></td>
              <td>₱<?= number_format($r['philhealth'],2) ?></td>
              <td>₱<?= number_format($r['pagibig'],2) ?></td>
              <td>₱<?= number_format($r['deductions'],2) ?></td>
              <td><b>₱<?= number_format($r['net_pay'],2) ?></b></td>
              <td>
                <a href="payroll.php?edit=<?= $r['id'] ?>">✏️</a>
                <a href="payroll.php?delete=<?= $r['id'] ?>" onclick="return confirm('Delete this payroll?')">🗑️</a>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="14">No payroll records found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>

<!-- Payroll Records Modal -->
<div class="modal-overlay" id="recordsModalOverlay">
  <div class="modal records-modal">
    <h3><i class="fas fa-history"></i> Payroll Records</h3>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Employee</th>
            <th>Department</th>
            <th>Date</th>
            <th>Net Pay</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="modalPayrollRecords">
          <!-- Records will be populated dynamically -->
        </tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary close-records-modal">
        <i class="fas fa-times"></i> Close
      </button>
    </div>
  </div>
</div>

<!-- Computation Preview Modal -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <h3><i class="fas fa-file-invoice-dollar"></i> Payroll Computation Preview</h3>
    
    <div class="computation-section">
      <h4><i class="fas fa-clock"></i> Attendance Summary</h4>
      <table class="summary-table">
        <tr><th>Total Working Days:</th><td id="p_workdays">0 days</td></tr>
        <tr><th>Standard Hours:</th><td id="p_std_hours">0 hours</td></tr>
        <tr><th>Absent Hours:</th><td id="p_absent_hours">0 hours</td></tr>
        <tr><th>Late Minutes:</th><td id="p_late_mins">0 mins</td></tr>
        <tr><th>Actual Hours Worked:</th><td id="p_actual_hours">0 hours</td></tr>
        <tr><th>Hourly Rate:</th><td id="p_hourly_rate">₱0.00</td></tr>
      </table>
    </div>

    <div class="computation-section">
      <h4><i class="fas fa-calculator"></i> Salary Computation</h4>
      
      <div class="computation-type-selector">
        <button type="button" class="comp-type-btn active" data-type="gross">Gross Pay</button>
        <button type="button" class="comp-type-btn" data-type="net">Net Pay</button>
      </div>

      <!-- Gross Pay Section -->
      <div id="gross-pay-section" class="calculation-section">
        <table class="summary-table">
          <tr>
            <th>Regular Hours</th>
            <td id="total_hours">0.00</td>
          </tr>
          <tr>
            <th>Hourly Rate</th>
            <td id="hourly_rate">₱0.00</td>
          </tr>
          <tr>
            <th>Regular Pay</th>
            <td id="regular_pay">₱0.00</td>
          </tr>
          <tr>
            <th>Overtime Hours</th>
            <td id="total_overtime">0.00</td>
          </tr>
          <tr>
            <th>Overtime Rate</th>
            <td id="overtime_rate">₱0.00</td>
          </tr>
          <tr>
            <th>Overtime Pay</th>
            <td id="overtime_pay">₱0.00</td>
          </tr>
          <tr class="total-row">
            <th>Gross Pay</th>
            <td id="gross_pay">₱0.00</td>
          </tr>
        </table>
      </div>

      <!-- Net Pay Section -->
      <div id="net-pay-section" class="calculation-section" style="display: none;">
        <table class="summary-table">
          <!-- Earnings Section -->
          <tr class="section-header">
            <th colspan="2">Earnings</th>
          </tr>
          <tr>
            <th>Basic Pay</th>
            <td id="net_basic_pay">₱0.00</td>
          </tr>
          <tr>
            <th>Overtime Pay</th>
            <td id="net_overtime_pay">₱0.00</td>
          </tr>
          <tr class="subtotal">
            <th>Gross Pay</th>
            <td id="net_gross_pay">₱0.00</td>
          </tr>

          <!-- Deductions Section -->
          <tr class="section-header">
            <th colspan="2">Deductions</th>
          </tr>
          <tr>
            <th>SSS (4.5%)</th>
            <td id="sss_deduction">₱0.00</td>
          </tr>
          <tr>
            <th>PhilHealth (2.5%)</th>
            <td id="philhealth_deduction">₱0.00</td>
          </tr>
          <tr>
            <th>Pag-IBIG (2%)</th>
            <td id="pagibig_deduction">₱0.00</td>
          </tr>
          <tr>
            <th>Tax</th>
            <td id="tax_deduction">₱0.00</td>
          </tr>
          <tr class="subtotal">
            <th>Total Deductions</th>
            <td id="total_deductions">₱0.00</td>
          </tr>

          <!-- Final Net Pay -->
          <tr class="total-row">
            <th>Net Pay</th>
            <td id="net_pay">₱0.00</td>
          </tr>
        </table>
      </div>
    </div>
    </div>
    <div class="modal-footer">
      <button class="btn ghost" id="closeModal">Exit</button>
      <button class="btn" id="savePayroll">Save</button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing payroll calculations...'); // Debug log
    
    // Get all input elements
    const workHoursInput = document.getElementById('work_hours');
    const overtimeInput = document.getElementById('overtime_hours');
    const basicPayInput = document.getElementById('basic_pay');
    
    // Add event listeners for real-time calculation updates
    const updateCalculation = () => {
        console.log('Input changed, updating calculations...');
        calculateAll();
    };
    
    // Add input event listeners for real-time updates
    const addInputListeners = (element) => {
        if (element) {
            ['input', 'change', 'keyup'].forEach(event => {
                element.addEventListener(event, () => {
                    console.log(`${element.id} changed, updating calculations...`);
                    calculateAll();
                });
            });
        }
    };

    // Add listeners to all relevant inputs
    addInputListeners(workHoursInput);
    addInputListeners(overtimeInput);
    addInputListeners(basicPayInput);
    
    // Initial calculation
    calculateAll();
    
    console.log('Event listeners added for payroll calculation');

    // Get computation type buttons and sections
    const compButtons = document.querySelectorAll('.comp-type-btn');
    const grossPaySection = document.getElementById('gross-pay-section');
    const netPaySection = document.getElementById('net-pay-section');

    // Add click handlers to the computation type buttons
    compButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            compButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            // Show/hide appropriate section
            if (this.dataset.type === 'gross') {
                grossPaySection.style.display = 'block';
                netPaySection.style.display = 'none';
            } else {
                grossPaySection.style.display = 'none';
                netPaySection.style.display = 'block';
            }
            
            // Recalculate values
            calculateAll();
        });
    });

    // Function to format currency
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(amount);
    }

    // Function to format currency consistently
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    }
    
    // Function to format currency for input values (without currency symbol)
    function formatCurrencyValue(amount) {
        return amount.toFixed(2);
    }

    // Format currency for display
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    }

    // Format number for input fields
    function formatNumber(amount) {
        return new Intl.NumberFormat('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(amount);
    }

    // Function to calculate all values
    // Removed duplicate calculateAll function - using handlePayrollCalculation instead
            
            // Get input values
            const workHours = parseFloat(workHoursInput?.value) || 0;
            const overtimeHours = parseFloat(overtimeInput?.value) || 0;
            const lateMinutes = parseFloat(lateMinutesInput?.value) || 0;
            const absentHours = parseFloat(absentHoursInput?.value) || 0;
            const basicPay = parseFloat(basicPayInput?.value.replace(/[^\d.-]/g, '')) || 0;
            
            console.log('Input values:', { workHours, overtimeHours, lateMinutes, absentHours, basicPay }); // Debug log

            // Calculate working days
            const workDays = workHours / 8;
            if (workDaysInput) {
                workDaysInput.value = workDays.toFixed(2);
            }

            // Calculate total hours worked
            const totalHoursWorked = workHours + overtimeHours;
            console.log('Total Hours:', { workHours, overtimeHours, totalHoursWorked });

            // Calculate hourly rate from basic pay
            const standardHours = 176; // 22 days * 8 hours
            const hourlyRate = basicPay / standardHours;

            // Calculate pay for regular hours
            const regularPay = workHours * hourlyRate;

            // Calculate pay for overtime hours (1.25x rate)
            const overtimeRate = hourlyRate * 1.25;
            const overtimePay = overtimeHours * overtimeRate;

            // Calculate total gross pay
            const grossPay = regularPay + overtimePay;
            
            // Log all calculations for verification
            console.log('Pay Calculations:', {
                totalHoursWorked,
                hourlyRate,
                regularPay,
                overtimeRate,
                overtimePay,
                grossPay
            });
            
            console.log('Gross Pay Calculation:', {
                totalHoursWorked,
                regularPay,
                overtimePay,
                grossPay
            });

            // Update gross pay display
            if (grossPayTotal) {
                if (totalHoursWorked > 0) {
                    // Show the calculated gross pay
                    grossPayTotal.value = formatCurrency(grossPay);
                    // Add a tooltip showing the breakdown
                    grossPayTotal.title = `Regular Hours (${workHours}h): ${formatCurrency(regularPay)}\nOvertime (${overtimeHours}h): ${formatCurrency(overtimePay)}`;
                } else {
                    // Clear the display if no hours entered
                    grossPayTotal.value = '';
                    grossPayTotal.title = 'Enter work hours and overtime hours to see gross pay';
                }
                console.log('Updated gross pay:', grossPayTotal.value);
            }

            // Calculate all deductions
            const sssRate = 0.045; // 4.5%
            const philhealthRate = 0.03; // 3%
            const pagibigAmount = 100; // Fixed amount
            const taxStatus = document.getElementById('tax_status');
            const taxRateInput = document.getElementById('tax_rate');
            
            const taxRate = (taxStatus && taxStatus.checked && taxRateInput) ? 
                          (parseFloat(taxRateInput.value) || 0) / 100 : 0;

            const sssDeduction = grossPay * sssRate;
            const philhealthDeduction = grossPay * philhealthRate;
            const taxDeduction = grossPay * taxRate;
            
            // Update deduction input displays
            const sssInput = document.getElementById('sss_deduction');
            const philhealthInput = document.getElementById('philhealth_deduction');
            const taxInput = document.getElementById('tax_deduction');
            const totalDeductionsInput = document.getElementById('total_deductions');
            
            if (sssInput) sssInput.value = formatCurrency(sssDeduction);
            if (philhealthInput) philhealthInput.value = formatCurrency(philhealthDeduction);
            if (taxInput) taxInput.value = formatCurrency(taxDeduction);
            
            // Calculate and update total deductions and net pay
            const totalDeductions = sssDeduction + philhealthDeduction + pagibigAmount + taxDeduction;
            const netPay = grossPay - totalDeductions;
            
            if (totalDeductionsInput) totalDeductionsInput.value = formatCurrency(totalDeductions);
            if (netPayTotal) {
                netPayTotal.value = formatCurrency(netPay);
                console.log('Updated net pay:', netPayTotal.value);
            }

            // Calculate net pay
            const netPay = grossPay - totalDeductions;

            console.log('Calculated values:', {
                hourlyRate,
                overtimeRate,
                regularPay,
                overtimePay,
                grossPay,
                netPay,
                totalDeductions
            });

            // Update Gross Pay calculation display
            const regHoursFormula = document.getElementById('reg_hours_formula');
            const regHoursResult = document.getElementById('reg_hours_result');
            const otHoursFormula = document.getElementById('ot_hours_formula');
            const otHoursResult = document.getElementById('ot_hours_result');
            const grossPayTotal = document.getElementById('gross_pay_total');

            if (regHoursFormula) regHoursFormula.textContent = `${workHours} hrs × ${formatCurrency(hourlyRate)}/hr`;
            if (regHoursResult) regHoursResult.textContent = formatCurrency(regularPay);
            if (otHoursFormula) otHoursFormula.textContent = `${overtimeHours} hrs × ${formatCurrency(overtimeRate)}/hr`;
            if (otHoursResult) otHoursResult.textContent = formatCurrency(overtimePay);
            if (grossPayTotal) grossPayTotal.value = formatNumber(grossPay);

            // Update Net Pay calculation display
            const netGrossAmount = document.getElementById('net_gross_amount');
            const netDeductionsAmount = document.getElementById('net_deductions_amount');
            const netPayTotal = document.getElementById('net_pay_total');

            if (netGrossAmount) netGrossAmount.textContent = formatCurrency(grossPay);
            if (netDeductionsAmount) netDeductionsAmount.textContent = formatCurrency(totalDeductions);
            if (netPayTotal) netPayTotal.value = formatNumber(netPay);

            // Format values for display
            const formattedGrossPay = formatCurrency(grossPay);
            const formattedRegularHours = formatCurrency(regularPay);
            const formattedOvertimeHours = formatCurrency(overtimePay);
            const formattedHourlyRate = formatCurrency(hourlyRate);
            const formattedOvertimeRate = formatCurrency(overtimeRate);

            // Update the gross pay input
            if (grossPayInput) {
                // For the input field, show the numerical value without currency symbol
                grossPayInput.value = formatCurrencyValue(grossPay);
                console.log('Updated gross pay input:', grossPayInput.value);
            }

            // Update the breakdown details
            if (regularHoursText) {
                regularHoursText.textContent = `${workHours} hrs × ${formattedHourlyRate} = ${formattedRegularHours}`;
                console.log('Updated regular hours text:', regularHoursText.textContent);
            }

            if (overtimeHoursText) {
                overtimeHoursText.textContent = `${overtimeHours} hrs × ${formattedOvertimeRate} = ${formattedOvertimeHours}`;
                console.log('Updated overtime hours text:', overtimeHoursText.textContent);
            }

            if (totalGrossText) {
                totalGrossText.textContent = formattedGrossPay;
                console.log('Updated total gross text:', totalGrossText.textContent);
            }

            // Update net pay calculation area
            const netGrossDisplay = document.getElementById('net_gross_display');
            if (netGrossDisplay) {
                netGrossDisplay.textContent = formattedGrossPay;
            }

        // Update gross pay input and breakdown
        const grossPayInput = document.getElementById('gross_pay');
        const regularHoursText = document.getElementById('regular_hours_text');
        const overtimeHoursText = document.getElementById('overtime_hours_text');
        const totalGrossText = document.getElementById('total_gross_text');

        console.log('Elements found:', { 
            grossPayInput: !!grossPayInput, 
            regularHoursText: !!regularHoursText, 
            overtimeHoursText: !!overtimeHoursText, 
            totalGrossText: !!totalGrossText 
        }); // Debug log

        // Update gross pay input
        if (grossPayInput) {
            grossPayInput.value = formatCurrencyValue(grossPay);
            console.log('Updated gross pay input:', grossPayInput.value); // Debug log
        }

        // Update breakdown texts
        if (regularHoursText) {
            const regularPayText = `${workHours} hrs × ${formatCurrency(hourlyRate)} = ${formatCurrency(regularPay)}`;
            regularHoursText.textContent = regularPayText;
            console.log('Updated regular hours text:', regularPayText); // Debug log
        }

        if (overtimeHoursText) {
            const overtimePayText = `${overtimeHours} hrs × ${formatCurrency(overtimeRate)} = ${formatCurrency(overtimePay)}`;
            overtimeHoursText.textContent = overtimePayText;
            console.log('Updated overtime hours text:', overtimePayText); // Debug log
        }

        if (totalGrossText) {
            totalGrossText.textContent = formatCurrency(grossPay);
            console.log('Updated total gross text:', formatCurrency(grossPay)); // Debug log
        }

        // Calculate deductions
        const sssRate = 0.045; // 4.5%
        const philhealthRate = 0.025; // 2.5%
        const pagibigRate = 0.02; // 2%
        const taxRate = 0.1; // 10%

        const sssDeduction = grossPay * sssRate;
        const philhealthDeduction = grossPay * philhealthRate;
        const pagibigDeduction = Math.min(grossPay * pagibigRate, 100);
        let taxDeduction = 0;
        if (grossPay > 20000) { // Tax threshold
            taxDeduction = (grossPay - 20000) * taxRate;
        }

        // Calculate late/absent deductions
        const lateDeduction = (lateMinutes / 60) * hourlyRate;
        const absentDeduction = absentHours * hourlyRate;

        const totalDeductions = sssDeduction + philhealthDeduction + pagibigDeduction + taxDeduction + lateDeduction + absentDeduction;
        const netPay = grossPay - totalDeductions;

        // Update displays
        // Gross Pay Section
        document.getElementById('total_hours').textContent = workHours.toFixed(2);
        document.getElementById('hourly_rate').textContent = formatCurrency(hourlyRate);
        document.getElementById('regular_pay').textContent = formatCurrency(regularPay);
        document.getElementById('total_overtime').textContent = overtimeHours.toFixed(2);
        document.getElementById('overtime_rate').textContent = formatCurrency(overtimeRate);
        document.getElementById('overtime_pay').textContent = formatCurrency(overtimePay);
        document.getElementById('gross_pay').textContent = formatCurrency(grossPay);

        // Net Pay Section
        document.getElementById('net_basic_pay').textContent = formatCurrency(regularPay);
        document.getElementById('net_overtime_pay').textContent = formatCurrency(overtimePay);
        document.getElementById('net_gross_pay').textContent = formatCurrency(grossPay);
        document.getElementById('sss_deduction').textContent = formatCurrency(sssDeduction);
        document.getElementById('philhealth_deduction').textContent = formatCurrency(philhealthDeduction);
        document.getElementById('pagibig_deduction').textContent = formatCurrency(pagibigDeduction);
        document.getElementById('tax_deduction').textContent = formatCurrency(taxDeduction);
        document.getElementById('total_deductions').textContent = formatCurrency(totalDeductions);
        document.getElementById('net_pay').textContent = formatCurrency(netPay);
    }

    // Handle form input restrictions
    function handleInputRestrictions() {
        const overtimeHours = parseFloat(overtimeInput.value) || 0;
        const lateMinutes = parseFloat(lateMinutesInput.value) || 0;
        const absentHours = parseFloat(absentHoursInput.value) || 0;

        // If overtime exists, disable late/absent
        if (overtimeHours > 0) {
            lateMinutesInput.value = '0';
            absentHoursInput.value = '0';
            lateMinutesInput.disabled = true;
            absentHoursInput.disabled = true;
        } else {
            lateMinutesInput.disabled = false;
            absentHoursInput.disabled = false;
        }

        // If late/absent exists, disable overtime
        if (lateMinutes > 0 || absentHours > 0) {
            overtimeInput.value = '0';
            overtimeInput.disabled = true;
        } else {
            overtimeInput.disabled = false;
        }
    }

    // Function to handle input changes
    function handleInputChange(input) {
        console.log(`Input change on ${input.id}`, input.value); // Debug log
        handleInputRestrictions();
        calculateAll();
    }

    // Add event listeners
    const inputs = [workHoursInput, overtimeInput, lateMinutesInput, absentHoursInput];
    inputs.forEach(input => {
        if (input) {
            console.log(`Setting up listeners for ${input.id}`); // Debug log
            ['input', 'change', 'blur'].forEach(eventType => {
                input.addEventListener(eventType, () => handleInputChange(input));
            });
        }
    });

    // Add special handling for basic pay input
    if (basicPayInput) {
        console.log('Setting up basic pay listeners'); // Debug log
        basicPayInput.addEventListener('change', calculateAll);
    }

    // Initial calculation
    console.log('Running initial calculation'); // Debug log
    calculateAll();

    // Add event listener for employee selection
    const employeeSelect = document.getElementById('emp_select');
    if (employeeSelect) {
        employeeSelect.addEventListener('change', calculateAll);
    }

    // Initial calculation
    calculateAll();
});
    const workHoursInput = document.getElementById('work_hours');
    const workDaysInput = document.getElementById('work_days');

    // Function to calculate working days from hours
    function updateWorkingDays() {
        const hours = parseFloat(workHoursInput.value) || 0;
        const workDays = hours / 8; // 8 hours = 1 day
        workDaysInput.value = workDays.toFixed(2);
        console.log('Hours:', hours, 'Days:', workDays.toFixed(2));
        calculateSalary(); // Update salary calculations
    }

    // Add event listeners for all input fields that affect calculation
    const inputFields = [
        'work_hours',
        'overtime_hours',
        'late_minutes',
        'absent_hours',
        'basic_pay'
    ];
    
    inputFields.forEach(fieldId => {
        const element = document.getElementById(fieldId);
        if (element) {
            ['input', 'change', 'keyup'].forEach(eventType => {
                element.addEventListener(eventType, calculateSalary);
            });
        }
    });
    
    // Add listener for tax status changes
    const taxStatus = document.getElementById('tax_status');
    if (taxStatus) {
        taxStatus.addEventListener('change', calculateSalary);
    }
    
    // Initial calculation
    calculateSalary();
}

// Initialize everything when the document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tax controls
    const taxStatusCheckbox = document.getElementById('tax_status');
    const taxRateInput = document.getElementById('tax_rate');
    
    // Function to update tax rate input state
    function updateTaxControls() {
        const isTaxable = taxStatusCheckbox.checked;
        taxRateInput.disabled = !isTaxable;
        if (!isTaxable) {
            taxRateInput.value = "0";
        } else if (taxRateInput.value === "0") {
            taxRateInput.value = "10";
        }
        // Update tax label
        const taxLabel = taxStatusCheckbox.parentElement.querySelector('.tax-label');
        taxLabel.textContent = isTaxable ? 'Taxable' : 'Exempt';
        // Recalculate payroll
        calculateSalary();
    }
    
    // Add event listeners for tax controls
    taxStatusCheckbox.addEventListener('change', updateTaxControls);
    taxRateInput.addEventListener('input', calculateSalary);
    
    // Initial setup
    updateTaxControls();
    initializePayrollCalculations();
    
    // Test the working days calculation
    const workHoursInput = document.getElementById('work_hours');
    workHoursInput.value = "8";
    workHoursInput.dispatchEvent(new Event('input'));
});

function calculateSalary() {
    // Constants for calculations
    const STANDARD_MONTHLY_HOURS = 176; // 22 days × 8 hours
    const SSS_RATE = 0.045;            // 4.5%
    const PHILHEALTH_RATE = 0.03;      // 3%
    const PAGIBIG_FIXED = 100;         // Fixed amount
    const OVERTIME_RATE = 1.25;        // 25% overtime premium

    // Get form input values
    const basicPay = parseFloat(document.getElementById('basic_pay').value.replace(/[^\d.-]/g, '')) || 0;
    const workHours = parseFloat(document.getElementById('work_hours').value) || 0;
    const overtimeHours = parseFloat(document.getElementById('overtime_hours').value) || 0;
    const lateMinutes = parseFloat(document.getElementById('late_minutes').value) || 0;
    const absentHours = parseFloat(document.getElementById('absent_hours').value) || 0;
    
    // Calculate rates
    const hourlyRate = basicPay / STANDARD_MONTHLY_HOURS;
    const overtimeRate = hourlyRate * OVERTIME_RATE;
    const minuteRate = hourlyRate / 60;

    // Calculate pay components
    const regularPay = workHours * hourlyRate;
    const overtimePay = overtimeHours * overtimeRate;
    const lateDeduction = lateMinutes * minuteRate;
    const absentDeduction = absentHours * hourlyRate;

    // Calculate gross pay
    const grossPay = regularPay + overtimePay - lateDeduction - absentDeduction;

    // Calculate government deductions
    const sssDeduction = grossPay * SSS_RATE;
    const philhealthDeduction = grossPay * PHILHEALTH_RATE;
    const pagibigDeduction = PAGIBIG_FIXED;
    
    // Calculate tax based on tax status
    const isTaxable = document.getElementById('tax_status').checked;
    const taxRate = isTaxable ? (parseFloat(document.getElementById('tax_rate').value) || 0) / 100 : 0;
    const taxDeduction = grossPay * taxRate;
    
    // Calculate total deductions
    const totalDeductions = sssDeduction + philhealthDeduction + pagibigDeduction + taxDeduction + absentDeduction;
    
    // Calculate net pay
    const netPay = grossPay - totalDeductions;

    // Format numbers for display
    const formatter = new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    });

    // Update display fields
    document.getElementById('gross_pay_total').value = formatter.format(grossPay).replace('PHP', '₱');
    document.getElementById('net_pay_total').value = formatter.format(netPay).replace('PHP', '₱');

    // Update deductions
    document.getElementById('sss_deduction').value = formatter.format(sssDeduction).replace('PHP', '₱');
    document.getElementById('philhealth_deduction').value = formatter.format(philhealthDeduction).replace('PHP', '₱');
    document.getElementById('pagibig_deduction').value = formatter.format(pagibigDeduction).replace('PHP', '₱');
    document.getElementById('tax_deduction').value = formatter.format(taxDeduction).replace('PHP', '₱');
    document.getElementById('absent_deduction_total').value = formatter.format(absentDeduction).replace('PHP', '₱');
    document.getElementById('total_deductions').value = formatter.format(totalDeductions).replace('PHP', '₱');

    // Update working days
    const workDays = workHours / 8;
    document.getElementById('work_days').value = workDays.toFixed(2);

    // Store values in hidden inputs for form submission
    document.getElementById('calculated_gross_pay').value = grossPay.toFixed(2);
    document.getElementById('calculated_net_pay').value = netPay.toFixed(2);
    document.getElementById('sss').value = sssDeduction.toFixed(2);
    document.getElementById('philhealth').value = philhealthDeduction.toFixed(2);
    document.getElementById('pagibig').value = pagibigDeduction.toFixed(2);
    document.getElementById('tax').value = taxDeduction.toFixed(2);
    
    // Update displays with 2 decimal places
    document.getElementById('gross_pay').value = grossPay.toFixed(2);
    document.getElementById('total_deductions').value = totalDeductions.toFixed(2);
    document.getElementById('net_pay').value = netPay.toFixed(2);
    
    // Update deduction fields
    document.getElementById('sss_deduction').value = sss.toFixed(2);
    document.getElementById('philhealth_deduction').value = philhealth.toFixed(2);
    document.getElementById('pagibig_deduction').value = pagibig.toFixed(2);
    
    // Calculate working days
    const workDays = workHours / 8;
    document.getElementById('work_days').value = formatInput(workDays);

    // Calculate rates
    const hourlyRate = basicPay / 176; // 22 days * 8 hours
    const overtimeRate = hourlyRate * 1.25;
    const minuteRate = hourlyRate / 60;

    // Calculate pays
    const regularPay = (workHours - absentHours) * hourlyRate;
    const overtimePay = overtimeHours * overtimeRate;
    const lateDeduction = lateMinutes * minuteRate;

    // Calculate gross pay
    const grossPay = regularPay + overtimePay - lateDeduction;
    
    // Calculate deductions
    const sssRate = 0.045; // 4.5%
    const philhealthRate = 0.03; // 3%
    const pagibigFixed = 100;
    const isTaxable = document.getElementById('tax_status').checked;
    const taxRate = isTaxable ? (parseFloat(document.getElementById('tax_rate').value) || 0) / 100 : 0;

    const sss = grossPay * sssRate;
    const philhealth = grossPay * philhealthRate;
    const tax = grossPay * taxRate;
    
    // Calculate total deductions
    const totalDeductions = sss + philhealth + pagibigFixed + tax;
    
    // Calculate net pay
    const netPay = grossPay - totalDeductions;

    // Update all displays with proper formatting
    document.getElementById('gross_pay').value = formatInput(grossPay);
    document.getElementById('total_deductions').value = formatInput(totalDeductions);
    document.getElementById('net_pay').value = formatInput(netPay);
    
    // Update deductions breakdown
    document.getElementById('sss_deduction').value = formatInput(sss);
    document.getElementById('philhealth_deduction').value = formatInput(philhealth);
    document.getElementById('pagibig_deduction').value = formatInput(pagibigFixed);
    document.getElementById('tax_deduction').value = formatInput(tax);
    
    // Return calculated values
    return {
        grossPay,
        totalDeductions,
        netPay
    };
}

// Employee selection handling
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tax controls
    const taxStatusCheckbox = document.getElementById('tax_status');
    const taxRateInput = document.getElementById('tax_rate');
    
    function updateTaxControls() {
        const isTaxable = taxStatusCheckbox.checked;
        taxRateInput.disabled = !isTaxable;
        if (!isTaxable) {
            taxRateInput.value = "0";
        } else if (taxRateInput.value === "0") {
            taxRateInput.value = "10";
        }
        const taxLabel = taxStatusCheckbox.parentElement.querySelector('.tax-label');
        if (taxLabel) taxLabel.textContent = isTaxable ? 'Taxable' : 'Exempt';
        // Recalculate payroll
        if (typeof computePayroll === 'function') {
            computePayroll();
        }
    }
    
    // Add event listeners for tax controls
    if (taxStatusCheckbox && taxRateInput) {
        taxStatusCheckbox.addEventListener('change', updateTaxControls);
        taxRateInput.addEventListener('input', computePayroll);
        // Initial setup
        updateTaxControls();
    }

    const deptSelect = document.getElementById('dept_select');
    const empSelect = document.getElementById('emp_select');
    const employeeGroup = document.getElementById('employee_group');
    const basicPayInput = document.getElementById('basic_pay');
    
    // Initially show but disable employee selection
    employeeGroup.style.opacity = '0.5';
    empSelect.disabled = true;
    
    // Department change handler
    deptSelect.addEventListener('change', function() {
        const selectedDeptId = this.value;
        empSelect.innerHTML = '<option value="">Select employee</option>';
        
        if (!selectedDeptId) {
            employeeGroup.style.opacity = '0.5';
            empSelect.disabled = true;
            basicPayInput.value = '';
            return;
        }

        // Get employees for selected department
        const employees = employeesByDept[selectedDeptId] || [];
        if (employees.length > 0) {
            employees.forEach(emp => {
                const option = document.createElement('option');
                option.value = emp.id;
                option.text = `${emp.name} - ${emp.position}`;
                option.setAttribute('data-salary', emp.salary);
                empSelect.appendChild(option);
            });
            
            // Enable and show employee selection with animation
            empSelect.disabled = false;
            employeeGroup.style.opacity = '1';
            empSelect.focus();
        }
    });
    
    // Employee change handler
    empSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption && selectedOption.value) {
            const salary = selectedOption.getAttribute('data-salary');
            if (salary) {
                basicPayInput.value = parseFloat(salary).toFixed(2);
                
                // Calculate working days
                const today = new Date();
                const year = today.getFullYear();
                const month = today.getMonth() + 1;
                const daysInMonth = new Date(year, month, 0).getDate();
                let workingDays = 0;
                
                // Count Monday to Friday only
                for (let day = 1; day <= daysInMonth; day++) {
                    const date = new Date(year, month - 1, day);
                    if (date.getDay() !== 0 && date.getDay() !== 6) { // 0 = Sunday, 6 = Saturday
                        workingDays++;
                    }
                }
                
                // Update working days display
                document.getElementById('working_days_display').value = workingDays + ' days';
                
                // Calculate and display hourly rate
                const hourlyRate = salary / (workingDays * 8); // 8 hours per day
                document.getElementById('hourly_rate_display').value = '₱' + hourlyRate.toFixed(2);
                
                // Trigger computation
                computePayroll();
            }
        } else {
            basicPayInput.value = '';
        }
    });
    
    // Calculate working days in the current month
    const daysInMonth = new Date(year, month, 0).getDate();
    let workingDays = 0;
    
    for(let day = 1; day <= daysInMonth; day++) {
      const date = new Date(year, month - 1, day);
      if(date.getDay() !== 0 && date.getDay() !== 6) { // Exclude weekends
        workingDays++;
      }
    }
    
    document.getElementById('working_days_display').value = workingDays + ' days';
    computePayroll();
    
    // Calculate and display hourly rate
    const hourlyRate = salary / (workingDays * 8); // 8 hours per day
    document.getElementById('hourly_rate_display').value = '₱' + hourlyRate.toFixed(2);
  }
});

// Auto-compute when any relevant field changes
['rate_type', 'basic_pay', 'days_worked', 'hours_worked', 'late_minutes', 'absent_hours'].forEach(id => {
  document.getElementById(id).addEventListener('input', computePayroll);
});

          function computePayroll() {
            // Constants
            const WORKING_DAYS = 22;
            const HOURS_PER_DAY = 8;
            const STANDARD_HOURS = WORKING_DAYS * HOURS_PER_DAY;
            
            // Get input values
            const basicPay = parseFloat(document.getElementById('basic_pay').value.replace(/[^\d.-]/g, '')) || 0;
            const workHours = parseFloat(document.getElementById('work_hours').value) || 0;
            
            if (basicPay <= 0 || workHours <= 0) {
                document.getElementById('gross_pay').value = '0.00';
                document.getElementById('sss_deduction').value = '0.00';
                document.getElementById('philhealth_deduction').value = '0.00';
                document.getElementById('pagibig_deduction').value = '0.00';
                document.getElementById('total_deductions').value = '0.00';
                document.getElementById('net_pay').value = '0.00';
                return;
            }
            
            // Calculate rates and gross pay
            const hourlyRate = basicPay / STANDARD_HOURS;
            const grossPay = workHours * hourlyRate;
            
            // Calculate deductions
            const sss = grossPay * 0.045; // 4.5%
            const philhealth = grossPay * 0.03; // 3%
            const pagibig = 100; // fixed amount
            
            // Calculate totals
            const totalDeductions = sss + philhealth + pagibig;
            const netPay = grossPay - totalDeductions;
            
            // Update all displays with proper formatting
            document.getElementById('gross_pay').value = grossPay.toFixed(2);
            document.getElementById('sss_deduction').value = sss.toFixed(2);
            document.getElementById('philhealth_deduction').value = philhealth.toFixed(2);
            document.getElementById('pagibig_deduction').value = pagibig.toFixed(2);
            document.getElementById('total_deductions').value = totalDeductions.toFixed(2);
            document.getElementById('net_pay').value = netPay.toFixed(2);
            
            // Log for debugging
            console.log('Calculation results:', {
                basicPay,
                workHours,
                hourlyRate,
                grossPay,
                deductions: { sss, philhealth, pagibig, total: totalDeductions },
                netPay
            });
          
            for (const [fieldId, message] of Object.entries(requiredFields)) {
              const field = document.getElementById(fieldId);
              if (!field?.value) {
                alert(message);
                field.focus();
                return null;
              }
            }
            
            // Constants for calculations
            const STANDARD_MONTHLY_HOURS = 176; // 22 days × 8 hours
            const OVERTIME_RATE = 1.25;
            const SSS_RATE = 0.045; // 4.5%
            const PHILHEALTH_RATE = 0.03; // 3%
            const PAGIBIG_FIXED = 100;

            // Constants
            const STANDARD_MONTHLY_HOURS = 176; // 22 days × 8 hours
            const OVERTIME_RATE = 1.25;
            const SSS_RATE = 0.045; // 4.5%
            const PHILHEALTH_RATE = 0.03; // 3%
            const PAGIBIG_FIXED = 100;

            // Get all input values with proper currency handling
            const basicPay = parseFloat(document.getElementById('basic_pay').value.replace(/[^\d.-]/g, '')) || 0;
            const workHours = parseFloat(document.getElementById('work_hours').value) || 0;
            const overtimeHours = parseFloat(document.getElementById('overtime_hours').value) || 0;
            const lateMinutes = parseFloat(document.getElementById('late_minutes').value) || 0;
            const absentHours = parseFloat(document.getElementById('absent_hours').value) || 0;

            // Calculate rates
            const hourlyRate = basicPay / STANDARD_MONTHLY_HOURS;
            const overtimeRate = hourlyRate * OVERTIME_RATE;
            const minuteRate = hourlyRate / 60;
            
            // Calculate regular pay and overtime pay
            const regularPay = (workHours - absentHours) * hourlyRate;
            const overtimePay = overtimeHours * overtimeRate;
            const lateDeduction = lateMinutes * minuteRate;
            
            // Calculate gross pay (regular + overtime - attendance deductions)
            const grossPay = regularPay + overtimePay - lateDeduction;
            
            // Get tax settings
            const isTaxable = document.getElementById('tax_status').checked;
            const taxRate = isTaxable ? (parseFloat(document.getElementById('tax_rate').value) || 0) / 100 : 0;
            
            // Calculate all deductions
            const tax = grossPay * taxRate;
            const sss = grossPay * SSS_RATE;
            const philhealth = grossPay * PHILHEALTH_RATE;
            const pagibig = PAGIBIG_FIXED;
            const lateDeduction = lateMinutes * (hourlyRate / 60); // Per minute rate
            const absentDeduction = absentHours * hourlyRate;
            
            // Calculate total deductions
            const totalDeductions = tax + sss + philhealth + pagibig + lateDeduction + absentDeduction;
            
            // Get all input values
            const basicPay = parseFloat(document.getElementById('basic_pay').value) || 0;
            const workHours = parseFloat(document.getElementById('work_hours').value) || 0;
            const overtimeHours = parseFloat(document.getElementById('overtime_hours').value) || 0;
            const lateMinutes = parseFloat(document.getElementById('late_minutes').value) || 0;
            const absentHours = parseFloat(document.getElementById('absent_hours').value) || 0;
            
            // Calculate rates
            const standardMonthlyHours = 176; // 22 days × 8 hours
            const hourlyRate = basicPay / standardMonthlyHours;
            const minuteRate = hourlyRate / 60;
            
            // Calculate regular pay and overtime
            const regularPay = workHours * hourlyRate;
            const overtimePay = overtimeHours * (hourlyRate * 1.25);
            
            // Calculate gross pay
            const grossPay = regularPay + overtimePay;
            
            // Calculate attendance deductions
            const lateDeduction = lateMinutes * minuteRate;
            const absentDeduction = absentHours * hourlyRate;
            const attendanceDeductions = lateDeduction + absentDeduction;
            
            // Calculate government deductions
            const isTaxable = document.getElementById('tax_status').checked;
            const taxRate = isTaxable ? (parseFloat(document.getElementById('tax_rate').value) || 0) / 100 : 0;
            const tax = grossPay * taxRate;
            const sss = grossPay * 0.045;
            const philhealth = grossPay * 0.03;
            const pagibig = 100;
            
            // Calculate total deductions
            const totalDeductions = tax + sss + philhealth + pagibig + attendanceDeductions;
            
            // Calculate net pay
            const netPay = grossPay - totalDeductions;
            
            // Format currency
            const formatter = new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP',
                minimumFractionDigits: 2
            });
            
            // Update all displays
            document.getElementById('gross_pay').value = grossPay.toFixed(2);
            document.getElementById('total_deductions').value = totalDeductions.toFixed(2);
            document.getElementById('net_pay').value = netPay.toFixed(2);
            
            // Update deductions breakdown
            document.getElementById('sss_deduction').value = formatter.format(sss).replace('PHP', '₱');
            document.getElementById('philhealth_deduction').value = formatter.format(philhealth).replace('PHP', '₱');
            document.getElementById('pagibig_deduction').value = formatter.format(pagibig).replace('PHP', '₱');
            document.getElementById('tax_deduction').value = formatter.format(tax).replace('PHP', '₱');
            document.getElementById('absent_deduction_total').value = formatter.format(attendanceDeductions).replace('PHP', '₱');
            
            // Update net pay breakdown
            document.getElementById('net_gross_text').textContent = formatter.format(grossPay).replace('PHP', '₱');
            document.getElementById('net_deductions_text').textContent = formatter.format(totalDeductions).replace('PHP', '₱');
            document.getElementById('net_calculation_text').textContent = formatter.format(netPay).replace('PHP', '₱');
            
            // Log calculations for debugging
            console.log('Payroll Calculation:', {
                basicPay,
                workHours,
                hourlyRate,
                regularPay,
                overtimePay,
                grossPay,
                deductions: {
                    tax,
                    sss,
                    philhealth,
                    pagibig,
                    attendance: attendanceDeductions,
                    total: totalDeductions
                },
                netPay
            });
            
            return {
                grossPay,
                totalDeductions,
                netPay
            };  // Get all input values
  const basicPay = parseFloat(document.getElementById('basic_pay').value) || 0;
  const workHours = parseFloat(document.getElementById('work_hours').value) || 0;
  const overtimeHours = parseFloat(document.getElementById('overtime_hours').value) || 0;
  const lateMinutes = parseFloat(document.getElementById('late_minutes').value) || 0;
  const absentHours = parseFloat(document.getElementById('absent_hours').value) || 0;

  // Constants for calculations
  // Get tax settings
  const isTaxable = document.getElementById('tax_status').checked;
  const taxRatePercent = parseFloat(document.getElementById('tax_rate').value) || 0;
  const TAX_RATE = isTaxable ? (taxRatePercent / 100) : 0;  // Convert percentage to decimal or 0 if exempt
  
  const SSS_RATE = 0.045;        // 4.5% SSS
  const PHILHEALTH_RATE = 0.03;  // 3% PhilHealth
  const PAGIBIG_FIXED = 100;     // Fixed Pag-IBIG
  const HOURS_PER_DAY = 8;       // Standard hours per day
  const OVERTIME_RATE = 1.25;    // 125% of regular rate
  
  // Get input values
  const monthlySalary = parseFloat(document.getElementById('basic_pay').value) || 0;
  const workHours = parseFloat(document.getElementById('work_hours').value) || 0;
  const overtimeHours = parseFloat(document.getElementById('overtime_hours').value) || 0;
  const lateMinutes = parseFloat(document.getElementById('late_minutes').value) || 0;
  const absentHours = parseFloat(document.getElementById('absent_hours').value) || 0;

  // Calculate standard rates
  const standardWorkingDaysPerMonth = 22; // Assuming 22 working days per month
  const totalWorkHoursPerMonth = standardWorkingDaysPerMonth * HOURS_PER_DAY;
  const hourlyRate = monthlySalary / totalWorkHoursPerMonth;
  const dailyRate = hourlyRate * HOURS_PER_DAY;
  const minuteRate = hourlyRate / 60;

  // Calculate work days from hours
  const workDays = workHours / HOURS_PER_DAY;
  document.getElementById('work_days').value = workDays.toFixed(2);
  
  // Calculate attendance deductions
  const lateDeduction = lateMinutes * minuteRate;
  const absentDeduction = absentHours * hourlyRate;
  const attendanceDeductions = lateDeduction + absentDeduction;

  // Calculate actual work hours (excluding absences)
  const actualWorkHours = workHours - absentHours;

  // Calculate pay components
  let regularPay = actualWorkHours * hourlyRate;
  let overtimePay = 0;

  // Only calculate overtime if there are no attendance issues
  if (overtimeHours > 0 && lateMinutes === 0 && absentHours === 0) {
    overtimePay = overtimeHours * hourlyRate * OVERTIME_RATE;
  }

  // Calculate regular pay and overtime pay
  const hourlyRate = basicPay / (26 * 8); // Daily rate divided by 8 hours
  const regularPay = workHours * hourlyRate;
  const overtimePay = overtimeHours * hourlyRate * 1.25; // 25% overtime premium

  // Calculate attendance deductions
  const lateDeduction = (lateMinutes / 60) * hourlyRate;
  const absentDeduction = absentHours * hourlyRate;
  const attendanceDeductions = lateDeduction + absentDeduction;

  // Calculate gross pay
  const grossPay = regularPay + overtimePay;

  // Calculate government deductions
  const isTaxable = document.getElementById('tax_status').checked;
  const taxRate = isTaxable ? (parseFloat(document.getElementById('tax_rate').value) || 0) / 100 : 0;
  const tax = grossPay * taxRate;
  const sss = grossPay * 0.045; // 4.5% SSS
  const philhealth = grossPay * 0.03; // 3% PhilHealth
  const pagibig = 100; // Fixed amount
  
  // Calculate total deductions (government + attendance)
  const govDeductions = tax + sss + philhealth + pagibig;
  const totalDeductions = govDeductions + attendanceDeductions;

              // Calculate net pay correctly (gross - total deductions)
              const netPay = grossPay - totalDeductions;

              // Format currency for display
              const formatter = new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP',
                minimumFractionDigits: 2
              });

              // Update net pay breakdown display
              document.getElementById('net_gross_text').textContent = formatter.format(grossPay);
              document.getElementById('net_deductions_text').textContent = formatter.format(totalDeductions);
              document.getElementById('net_calculation_text').textContent = formatter.format(netPay);  // Update computation preview
  document.getElementById('p_workdays').textContent = workDays.toFixed(2) + ' days';
  document.getElementById('p_std_hours').textContent = workHours.toFixed(2) + ' hours';
  document.getElementById('p_absent_hours').textContent = absentHours.toFixed(2) + ' hours';
  document.getElementById('p_late_mins').textContent = lateMinutes + ' mins';
  document.getElementById('p_actual_hours').textContent = actualWorkHours.toFixed(2) + ' hours';
  document.getElementById('p_hourly_rate').textContent = formatter.format(hourlyRate);
  
  document.getElementById('p_gross').textContent = formatter.format(grossPay);
  document.getElementById('p_tax').textContent = formatter.format(tax);
  document.getElementById('p_sss').textContent = formatter.format(sss);
  document.getElementById('p_ph').textContent = formatter.format(philhealth);
  document.getElementById('p_pagibig').textContent = formatter.format(pagibig);
  document.getElementById('p_deduct').textContent = formatter.format(totalDeductions);
  document.getElementById('p_net').textContent = formatter.format(netPay);

  // Update attendance deduction breakdown
  const absentBreakdown = `Late (${lateMinutes} mins): ${formatter.format(lateDeduction)}\nAbsent (${absentHours} hrs): ${formatter.format(absentDeduction)}`;
  const absentDisplay = document.getElementById('absent_deduction_total');
  if (absentDisplay) {
    absentDisplay.value = formatter.format(attendanceDeductions);
    absentDisplay.title = absentBreakdown;
  }

  // Show modal and enable save button
  document.getElementById('modalOverlay').style.display = 'flex';
  document.getElementById('savePayroll').style.display = 'inline-flex';

  return {
    gross: grossPay,
    deductions: {
      tax,
      sss,
      philhealth,
      pagibig,
      attendance: attendanceDeductions
    },
    totalDeductions: totalDeductions,
    netPay,
    rates: {
      daily: dailyRate,
      hourly: hourlyRate,
      minute: minuteRate
    }
  };

  // Update attendance summary
  document.getElementById('p_workdays').innerText = `${workDays} days`;
  document.getElementById('p_std_hours').innerText = `${standardHours} hours`;
  document.getElementById('p_absent_hours').innerText = `${absentHours} hours`;
  document.getElementById('p_late_mins').innerText = `${lateMinutes} mins`;
  document.getElementById('p_actual_hours').innerText = `${actualWorkHours} hours`;
  document.getElementById('p_hourly_rate').innerText = formatter.format(hourlyRate);
  
  // Update salary computation details
  document.getElementById('p_gross').innerText = formatter.format(grossPay);
  document.getElementById('p_tax').innerText = formatter.format(tax);
  document.getElementById('p_sss').innerText = formatter.format(sss);
  document.getElementById('p_ph').innerText = formatter.format(philhealth);
  document.getElementById('p_pagibig').innerText = formatter.format(pagibig);
  document.getElementById('p_deduct').innerText = formatter.format(totalDeductions);
  document.getElementById('p_net').innerText = formatter.format(netPay);

  // Show preview panels
  document.getElementById('modalOverlay').style.display = 'flex';
  document.getElementById('summaryBox').style.display = 'block';
  document.getElementById('submitBtn').style.display = 'inline-flex';
}

// Handle calculation and preview
document.getElementById('computeBtn').addEventListener('click', function() {
  const result = computePayroll();
  if (!result) return;

  // Update summary display
  document.getElementById('summary_gross').textContent = formatCurrency(result.gross);
  document.getElementById('summary_deductions').textContent = formatCurrency(result.totalDeductions);
  document.getElementById('summary_net').textContent = formatCurrency(result.netPay);

  // Show summary and submit button
  document.getElementById('summaryBox').style.display = 'block';
  document.getElementById('submitBtn').style.display = 'inline-flex';
  
        // Change calculate button to "Recalculate"
        this.innerHTML = '<i class="fas fa-sync"></i> Recalculate';
      });

      // Automatically calculate working days from hours
      document.getElementById('work_hours').addEventListener('input', function() {
          const hours = parseFloat(this.value) || 0;
          const hoursPerDay = 8;
          const days = hours / hoursPerDay; // 8 hours = 1 day
          document.getElementById('work_days').value = days.toFixed(2);
          // Trigger salary calculation
          calculateSalary();
      });

      // Records Modal Functionality
      const recordsModal = document.getElementById('recordsModalOverlay');
      const viewRecordsBtn = document.getElementById('viewRecordsBtn');

      // Show records modal when clicking Payroll Records button
      viewRecordsBtn.addEventListener('click', function() {
        const tableRows = Array.from(document.querySelectorAll('#payrollTable tbody tr')).map(row => {
            const cols = row.querySelectorAll('td');
            if (cols.length === 0) return null;
            
            const editLink = cols[cols.length - 1].querySelector('a[href*="edit"]');
            const deleteLink = cols[cols.length - 1].querySelector('a[href*="delete"]');
            
            return `
                <tr>
                    <td>${cols[0].textContent}</td>
                    <td>${cols[1].textContent}</td>
                    <td>${cols[2].textContent}</td>
                    <td>${cols[12].textContent}</td>
                    <td class="actions">
                        <button onclick="window.location.href='${editLink?.href || '#'}'" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button onclick="if(confirm('Delete this payroll record?')) window.location.href='${deleteLink?.href || '#'}'" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            `;
        }).filter(row => row !== null).join('');

        // Populate the records modal and show it
        document.getElementById('modalPayrollRecords').innerHTML = tableRows;
        recordsModal.style.display = 'flex';
      });

      // Close records modal when clicking close button or outside
      document.addEventListener('click', function(e) {
        if (e.target.classList.contains('close-records-modal') || 
            (e.target === recordsModal && !e.target.closest('.records-modal'))) {
            recordsModal.style.display = 'none';
        }
      });

      function formatCurrency(amount) {
        return new Intl.NumberFormat('en-PH', {
          style: 'currency',
          currency: 'PHP'
        }).format(amount);
      }

      // Function to handle all calculations
      function updateAllCalculations() {
          const workHours = parseFloat(document.getElementById('work_hours').value) || 0;
          const workDays = workHours / 8;
          document.getElementById('work_days').value = workDays.toFixed(2);

          // Trigger salary calculation
          if (typeof computePayroll === 'function') {
              computePayroll();
          }
      }

      // Handle overtime input
      function handleOvertimeInput(value) {
          const overtimeHours = parseFloat(value) || 0;
          const lateMinutesInput = document.getElementById('late_minutes');
          const absentHoursInput = document.getElementById('absent_hours');

          if (overtimeHours > 0) {
              // If overtime exists, disable and clear late/absent inputs
              lateMinutesInput.value = '0';
              absentHoursInput.value = '0';
              lateMinutesInput.disabled = true;
              absentHoursInput.disabled = true;
          } else {
              // If no overtime, enable late/absent inputs
              lateMinutesInput.disabled = false;
              absentHoursInput.disabled = false;
          }
          updateAllCalculations();
      }

      // Handle late minutes input
      function handleLateMinutesInput(value) {
          const lateMinutes = parseFloat(value) || 0;
          const overtimeInput = document.getElementById('overtime_hours');

          if (lateMinutes > 0) {
              // If late minutes exist, disable and clear overtime
              overtimeInput.value = '0';
              overtimeInput.disabled = true;
          } else {
              // Check if absent hours also allow enabling overtime
              const absentHours = parseFloat(document.getElementById('absent_hours').value) || 0;
              if (absentHours === 0) {
                  overtimeInput.disabled = false;
              }
          }
          updateAllCalculations();
      }

      // Handle absent hours input
      function handleAbsentHoursInput(value) {
          const absentHours = parseFloat(value) || 0;
          const overtimeInput = document.getElementById('overtime_hours');

          if (absentHours > 0) {
              // If absent hours exist, disable and clear overtime
              overtimeInput.value = '0';
              overtimeInput.disabled = true;
          } else {
              // Check if late minutes also allow enabling overtime
              const lateMinutes = parseFloat(document.getElementById('late_minutes').value) || 0;
              if (lateMinutes === 0) {
                  overtimeInput.disabled = false;
              }
          }
          updateAllCalculations();
      }

      // Initialize all calculations on page load
      document.addEventListener('DOMContentLoaded', function() {
          // Set initial states
          updateAllCalculations();
          handleOvertimeInput(document.getElementById('overtime_hours').value);
          handleLateMinutesInput(document.getElementById('late_minutes').value);
          handleAbsentHoursInput(document.getElementById('absent_hours').value);
      });

      // Modal functionality
const modalOverlay = document.getElementById('modalOverlay');
const closeModal = document.getElementById('closeModal');
const viewRecordsBtn = document.getElementById('viewRecordsBtn');

    // Show modal when clicking Payroll Records button
    viewRecordsBtn.addEventListener('click', function() {
        const tableRows = Array.from(document.querySelectorAll('#payrollTable tbody tr')).map(row => {
            const cols = row.querySelectorAll('td');
            if (cols.length === 0) return null;
            
            const editLink = cols[cols.length - 1].querySelector('a[href*="edit"]');
            const deleteLink = cols[cols.length - 1].querySelector('a[href*="delete"]');
            
            return `
                <tr>
                    <td>${cols[0].textContent}</td>
                    <td>${cols[1].textContent}</td>
                    <td>${cols[2].textContent}</td>
                    <td>${cols[12].textContent}</td>
                    <td class="actions">
                        <button onclick="window.location.href='${editLink?.href || '#'}'" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button onclick="if(confirm('Delete this payroll record?')) window.location.href='${deleteLink?.href || '#'}'" class="btn btn-sm btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            `;
        }).filter(row => row !== null).join('');

        modalOverlay.style.display = 'flex';
        document.querySelector('.modal').innerHTML = `
            <h3><i class="fas fa-history"></i> Payroll Records</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Date</th>
                            <th>Net Pay</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRows}
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close-modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        `;
    });// Close modal when clicking close button or outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('close-modal') || 
        (e.target === modalOverlay && !e.target.closest('.modal'))) {
        modalOverlay.style.display = 'none';
    }
});

// Prevent modal from closing when clicking inside
document.querySelector('.modal').addEventListener('click', function(e) {
    e.stopPropagation();
});

      // Work hours input triggers automatic calculation of working days
      document.getElementById('work_hours').addEventListener('input', function() {
          const hours = parseFloat(this.value) || 0;
          const hoursPerDay = 8;
          const days = hours / hoursPerDay; // 8 hours = 1 day
          document.getElementById('work_days').value = days.toFixed(2);
          // Trigger salary calculation
          calculateSalary();
      });

      // Handle Calculate Preview button
      document.getElementById('computeBtn').addEventListener('click', function() {
          const monthlySalary = parseFloat(document.getElementById('basic_pay').value);
          if (!monthlySalary) {
              alert('Please select an employee first');
              return;
          }

          // Calculate daily and hourly rates
          const standardDays = 22; // Standard working days per month
          const dailyRate = monthlySalary / standardDays;
          const hourlyRate = dailyRate / 8; // 8 hours per day

          // Get attendance values
          const workDays = parseInt(document.getElementById('work_days').value) || 0;
          const workHours = workDays * 8; // Automatically calculate total work hours
          const absentHours = parseFloat(document.getElementById('absent_hours').value) || 0;
          const lateMinutes = parseFloat(document.getElementById('late_minutes').value) || 0;
          const overtimeHours = parseFloat(document.getElementById('overtime_hours').value) || 0;

          // Calculate deductions
          const lateDeductions = (lateMinutes / 60) * hourlyRate;
          const absentDeductions = absentHours * hourlyRate;
          const attendanceDeductions = lateDeductions + absentDeductions;

          // Calculate overtime pay
          const overtimePay = overtimeHours * (hourlyRate * 1.25);

          // Calculate gross pay
          const regularPay = workHours * hourlyRate;
          const grossPay = regularPay + overtimePay - attendanceDeductions;

          // Calculate government deductions
          const tax = grossPay * 0.10; // 10% tax
          const sss = grossPay * 0.045; // 4.5% SSS
          const philhealth = grossPay * 0.03; // 3% PhilHealth
          const pagibig = 100; // Fixed amount
          const totalDeductions = tax + sss + philhealth + pagibig;

          // Calculate net pay
          const netPay = grossPay - totalDeductions;

          // Update preview modal
          document.getElementById('p_workdays').textContent = workDays + ' days';
          document.getElementById('p_std_hours').textContent = workHours + ' hours';
          document.getElementById('p_absent_hours').textContent = absentHours + ' hours';
          document.getElementById('p_late_mins').textContent = lateMinutes + ' mins';
          document.getElementById('p_actual_hours').textContent = (workHours - absentHours) + ' hours';
          document.getElementById('p_hourly_rate').textContent = '₱' + hourlyRate.toFixed(2);

          document.getElementById('p_gross').textContent = '₱' + grossPay.toFixed(2);
          document.getElementById('p_tax').textContent = '₱' + tax.toFixed(2);
          document.getElementById('p_sss').textContent = '₱' + sss.toFixed(2);
          document.getElementById('p_ph').textContent = '₱' + philhealth.toFixed(2);
          document.getElementById('p_pagibig').textContent = '₱' + pagibig.toFixed(2);
          document.getElementById('p_deduct').textContent = '₱' + totalDeductions.toFixed(2);
          document.getElementById('p_net').textContent = '₱' + netPay.toFixed(2);

          // Show the modal
          document.getElementById('modalOverlay').style.display = 'flex';
          
          // Update summary box
          document.getElementById('summaryBox').style.display = 'block';
          document.getElementById('summary_gross').textContent = '₱' + grossPay.toFixed(2);
          document.getElementById('summary_deductions').textContent = '₱' + totalDeductions.toFixed(2);
          document.getElementById('summary_net').textContent = '₱' + netPay.toFixed(2);
          
          // Show submit button
          document.getElementById('submitBtn').style.display = 'inline-flex';
          
          // Change calculate button to "Recalculate"
          this.innerHTML = '<i class="fas fa-sync"></i> Recalculate';
      });

      // Search functionality
      document.getElementById('searchInput').addEventListener('input', function() {
        const searchText = this.value.toLowerCase();
        const rows = document.querySelectorAll('#payrollTable tbody tr');
        
        rows.forEach(row => {
          const text = row.textContent.toLowerCase();
          row.style.display = text.includes(searchText) ? '' : 'none';
        });
      });// Essential payroll calculation script
document.addEventListener('DOMContentLoaded', function() {
    const payrollForm = document.getElementById('payrollForm');
    const inputs = {
        basicPay: document.getElementById('basic_pay'),
        workHours: document.getElementById('work_hours'),
        grossPay: document.getElementById('gross_pay_total'),
        netPay: document.getElementById('net_pay_total'),
        totalDeductions: document.getElementById('total_deductions_display'),
        sssDeduction: document.getElementById('sss_deduction_display'),
        philhealthDeduction: document.getElementById('philhealth_deduction_display'),
        pagibigDeduction: document.getElementById('pagibig_deduction_display')
    };

    // Initialize display values
    function initializeDisplays() {
        ['grossPay', 'netPay', 'totalDeductions', 'sssDeduction', 'philhealthDeduction', 'pagibigDeduction'].forEach(field => {
            if (inputs[field]) {
                inputs[field].value = '0.00';
            }
        });
    }

    // Format number to 2 decimal places
    function formatNumber(num) {
        return (parseFloat(num) || 0).toFixed(2);
    }

    // Main calculation function
    function calculatePayroll() {
        // Get base values
        const basicPay = parseFloat(inputs.basicPay.value.replace(/[^0-9.-]+/g, '')) || 0;
        const workHours = parseFloat(inputs.workHours.value) || 0;

        console.log('Calculating with:', { basicPay, workHours });

        if (basicPay <= 0 || workHours <= 0) {
            initializeDisplays();
            return;
        }

        // Calculate
        const hourlyRate = basicPay / (22 * 8); // Monthly salary divided by standard hours
        const grossPay = workHours * hourlyRate;
        const sss = grossPay * 0.045;
        const philhealth = grossPay * 0.03;
        const pagibig = 100;
        const totalDeductions = sss + philhealth + pagibig;
        const netPay = grossPay - totalDeductions;

        // Update displays
        if (inputs.grossPay) inputs.grossPay.value = formatNumber(grossPay);
        if (inputs.sssDeduction) inputs.sssDeduction.value = formatNumber(sss);
        if (inputs.philhealthDeduction) inputs.philhealthDeduction.value = formatNumber(philhealth);
        if (inputs.pagibigDeduction) inputs.pagibigDeduction.value = formatNumber(pagibig);
        if (inputs.totalDeductions) inputs.totalDeductions.value = formatNumber(totalDeductions);
        if (inputs.netPay) inputs.netPay.value = formatNumber(netPay);

        console.log('Calculation results:', {
            grossPay: formatNumber(grossPay),
            deductions: formatNumber(totalDeductions),
            netPay: formatNumber(netPay)
        });
    }

    // Add event listeners
    if (inputs.basicPay) {
        inputs.basicPay.addEventListener('input', calculatePayroll);
        inputs.basicPay.addEventListener('change', calculatePayroll);
    }
    if (inputs.workHours) {
        inputs.workHours.addEventListener('input', calculatePayroll);
        inputs.workHours.addEventListener('change', calculatePayroll);
    }

    // Initial setup
    initializeDisplays();
    calculatePayroll();
});

// Sort table
function sortTable(columnIndex) {
  const table = document.getElementById('payrollTable');
  const rows = Array.from(table.querySelectorAll('tbody tr'));
  const headers = table.querySelectorAll('th');
  const currentHeader = headers[columnIndex];
  const isAscending = !currentHeader.classList.contains('sort-asc');

  // Reset all headers
  headers.forEach(header => {
    header.classList.remove('sort-asc', 'sort-desc');
  });

  // Set new sort direction
  currentHeader.classList.add(isAscending ? 'sort-asc' : 'sort-desc');

  rows.sort((a, b) => {
    const aValue = a.cells[columnIndex].textContent.trim();
    const bValue = b.cells[columnIndex].textContent.trim();
    
    // Handle currency values
    if (aValue.startsWith('₱')) {
      const aNum = parseFloat(aValue.replace(/[₱,]/g, ''));
      const bNum = parseFloat(bValue.replace(/[₱,]/g, ''));
      return isAscending ? aNum - bNum : bNum - aNum;
    }
    
    // Handle regular text
    return isAscending ? 
      aValue.localeCompare(bValue) : 
      bValue.localeCompare(aValue);
  });

  // Reorder table rows
  const tbody = table.querySelector('tbody');
  rows.forEach(row => tbody.appendChild(row));
}

// Clean payroll calculation script
document.addEventListener('DOMContentLoaded', function() {
    // Format numbers consistently
    function formatNumber(num) {
        return (parseFloat(num) || 0).toFixed(2);
    }

    // Single function to handle all payroll calculations
    function handlePayrollCalculation() {
        // Constants
        const STANDARD_HOURS = 22 * 8;  // 22 days * 8 hours
        const SSS_RATE = 0.045;         // 4.5%
        const PHILHEALTH_RATE = 0.03;   // 3%
        const PAGIBIG_AMOUNT = 100;     // Fixed amount

        // Get input values
        const basicPay = parseFloat(document.getElementById('basic_pay').value.replace(/[^\d.-]/g, '')) || 0;
        const workHours = parseFloat(document.getElementById('work_hours').value) || 0;

        // Initialize values
        let grossPay = 0;
        let totalDeductions = 0;
        let netPay = 0;

        if (basicPay > 0 && workHours > 0) {
            // Calculate gross pay
            const hourlyRate = basicPay / STANDARD_HOURS;
            grossPay = workHours * hourlyRate;

            // Calculate deductions
            const sssDeduction = grossPay * SSS_RATE;
            const philhealthDeduction = grossPay * PHILHEALTH_RATE;
            totalDeductions = sssDeduction + philhealthDeduction + PAGIBIG_AMOUNT;

            // Calculate net pay
            netPay = grossPay - totalDeductions;

            // Update deduction fields
            const deductions = {
                'sss_deduction_display': sssDeduction,
                'philhealth_deduction_display': philhealthDeduction,
                'pagibig_deduction_display': PAGIBIG_AMOUNT,
            };

            Object.entries(deductions).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element) element.value = formatNumber(value);
            });
        }

        // Update main displays
        const displays = {
            'gross_pay_total': grossPay,
            'total_deductions_display': totalDeductions,
            'net_pay_total': netPay
        };

        Object.entries(displays).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) element.value = formatNumber(value);
        });

        console.log('Calculation complete:', { basicPay, workHours, grossPay, totalDeductions, netPay });
    }

    // Add event listeners to input fields
    ['basic_pay', 'work_hours'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            ['input', 'change'].forEach(event => {
                element.addEventListener(event, handlePayrollCalculation);
            });
        }
    });

    // Initialize with zeros
    handlePayrollCalculation();
});

// Export to Excel
function exportToExcel() {
  const table = document.getElementById('payrollTable');
  const wb = XLSX.utils.book_new();
  const ws = XLSX.utils.table_to_sheet(table);
  
  // Style the worksheet
  ws['!cols'] = [
    {wch: 20}, // Employee
    {wch: 15}, // Department
    {wch: 12}, // Date
    {wch: 10}, // Rate Type
    {wch: 8},  // Days
    {wch: 8},  // Hours
    {wch: 12}, // Gross
    {wch: 12}, // Tax
    {wch: 12}, // SSS
    {wch: 12}, // PhilHealth
    {wch: 12}, // Pag-IBIG
    {wch: 12}, // Deductions
    {wch: 12}, // Net Pay
  ];

  XLSX.book_append_sheet(wb, ws, 'Payroll Report');
  XLSX.writeFile(wb, `Payroll_Report_${new Date().toISOString().split('T')[0]}.xlsx`);
}

// Initialize payroll calculations
document.addEventListener('DOMContentLoaded', function() {
    // Set initial values to 0.00
    document.getElementById('gross_pay').value = '0.00';
    document.getElementById('sss_deduction').value = '0.00';
    document.getElementById('philhealth_deduction').value = '0.00';
    document.getElementById('pagibig_deduction').value = '0.00';
    document.getElementById('total_deductions').value = '0.00';
    document.getElementById('net_pay').value = '0.00';
    
    // Add event listeners to input fields
    const basicPayInput = document.getElementById('basic_pay');
    const workHoursInput = document.getElementById('work_hours');
    
    if (basicPayInput) {
        basicPayInput.addEventListener('input', computePayroll);
        basicPayInput.addEventListener('change', computePayroll);
    }
    
    if (workHoursInput) {
        workHoursInput.addEventListener('input', computePayroll);
        workHoursInput.addEventListener('change', computePayroll);
    }
    
    // Add event listeners for basic calculation
    if (workHoursInput) {
        workHoursInput.addEventListener('input', function() {
            console.log('Work hours changed:', this.value);
            calculateSalary();
        });
    }
    
    if (basicPayInput) {
        basicPayInput.addEventListener('input', function() {
            console.log('Basic pay changed:', this.value);
            calculateSalary();
        });
    }
    
    // Initialize display values
    document.getElementById('gross_pay').value = "0.00";
    document.getElementById('total_deductions').value = "0.00";
    document.getElementById('net_pay').value = "0.00";
    
    // Run initial calculation
    calculateSalary();
    const employeeSelect = document.getElementById('emp_select');
    
    console.log('Setting up payroll calculation listeners...');
    
    // Add event listeners for automatic calculations
    if (workHoursInput) {
        console.log('Adding work hours listeners');
        workHoursInput.addEventListener('input', calculateAll);
        workHoursInput.addEventListener('change', calculateAll);
    }
    
    if (overtimeInput) {
        console.log('Adding overtime hours listeners');
        overtimeInput.addEventListener('input', calculateAll);
        overtimeInput.addEventListener('change', calculateAll);
    }
    
    if (basicPayInput) {
        console.log('Adding basic pay listeners');
        basicPayInput.addEventListener('input', calculateAll);
        basicPayInput.addEventListener('change', calculateAll);
    }
    
    if (employeeSelect) {
        console.log('Adding employee select listeners');
        employeeSelect.addEventListener('change', function() {
            setTimeout(calculateAll, 100); // Give time for other handlers to update values
        });
    }
    
    // Initial calculation if needed
    if (employeeSelect && employeeSelect.value) {
        console.log('Running initial calculation');
        setTimeout(calculateAll, 200); // Run initial calculation after page load
    }
});
</script>

<script>
// Modal handling
document.getElementById('closeModal').addEventListener('click', function() {
  document.getElementById('modalOverlay').style.display = 'none';
});

document.getElementById('savePayroll').addEventListener('click', function() {
  document.getElementById('modalOverlay').style.display = 'none';
  document.getElementById('payrollForm').submit();
});

// Close modal when clicking outside
document.getElementById('modalOverlay').addEventListener('click', function(e) {
  if (e.target === this) {
    this.style.display = 'none';
  }
});

// Handle form submission and modals
document.addEventListener('DOMContentLoaded', function() {
    // Close modal when clicking outside
    window.onclick = function(event) {
        const recordsModal = document.getElementById('recordsModal');
        const payslipModal = document.getElementById('payslipModal');
        
        if (event.target === recordsModal) {
            recordsModal.style.display = 'none';
        }
        if (event.target === payslipModal) {
            payslipModal.style.display = 'none';
        }
    }

    // Handle employee selection and salary update
    const empSelect = document.getElementById('emp_select');
    const basicPayInput = document.getElementById('basic_pay');

    if (empSelect) {
        empSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.value) {
                // Update hidden fields
                document.getElementById('emp_id').value = selectedOption.value;
                const positionField = document.getElementById('position');
                const departmentField = document.getElementById('department');
                
                if (positionField) positionField.value = selectedOption.dataset.position || '';
                if (departmentField) departmentField.value = document.getElementById('dept_select').value;
                
                // Show employee details
                const empDetails = document.getElementById('employee_details');
                if (empDetails) {
                    empDetails.style.display = 'grid';
                    const empPosition = document.getElementById('emp_position');
                    const empIdDisplay = document.getElementById('emp_id_display');
                    
                    if (empPosition) empPosition.value = selectedOption.dataset.position || '';
                    if (empIdDisplay) empIdDisplay.value = selectedOption.value;
                }
                
                // Update salary if available
                if (selectedOption.dataset.salary && basicPayInput) {
                    basicPayInput.value = parseFloat(selectedOption.dataset.salary).toFixed(2);
                    basicPayInput.dispatchEvent(new Event('input'));
                }
            } else {
                const empDetails = document.getElementById('employee_details');
                if (empDetails) empDetails.style.display = 'none';
                if (basicPayInput) basicPayInput.value = '';
            }
        });
    }
});
                        title: 'Success!',
                        text: data.message,
                        showConfirmButton: true,
                        confirmButtonText: 'View Reports'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'reports.php';
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'An error occurred'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while submitting the payroll'
                });
            });
        });
    }

    // Handle employee selection and salary update
    const empSelect = document.getElementById('emp_select');
    const basicPayInput = document.getElementById('basic_pay');

    if (empSelect) {
        empSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.value) {
                // Update hidden fields
                document.getElementById('emp_id').value = selectedOption.value;
                document.getElementById('position').value = selectedOption.dataset.position;
                document.getElementById('department').value = document.getElementById('dept_select').value;
                
                // Show employee details
                const empDetails = document.getElementById('employee_details');
                if (empDetails) {
                    empDetails.style.display = 'grid';
                    document.getElementById('emp_position').value = selectedOption.dataset.position;
                    document.getElementById('emp_id_display').value = selectedOption.value;
                }
                
                // Update salary if available
                if (selectedOption.dataset.salary) {
                    basicPayInput.value = parseFloat(selectedOption.dataset.salary).toFixed(2);
                    basicPayInput.dispatchEvent(new Event('input'));
                }
            } else {
                document.getElementById('employee_details').style.display = 'none';
            }
        });
    }
});
                basicPayInput.dispatchEvent(new Event('input'));
            }
        } else {
            basicPayInput.value = '';
        }
    }

    // Add event listeners for automatic calculation
    const inputsToWatch = [
        basicPayInput,
        workDaysInput,
        workingDaysSelect,
        workHoursInput,
        overtimeInput,
        employeeSelect,
        lateMinutesInput,
        absentHoursInput
    ];

    inputsToWatch.forEach(input => {
        if (input) {
            input.addEventListener('change', calculateAll);
            input.addEventListener('input', calculateAll);
        }
    });

    empSelect.addEventListener('change', updateSalary);
    
    // If there's a selected employee on page load (edit mode)
    if (empSelect.value) {
        updateSalary();
        calculateAll(); // Also calculate all values initially
    }
});
</script>
</body>
</html>