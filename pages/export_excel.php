<?php
// export_excel.php
// Professional Excel export with summary row (totals)

// Include database connection
$included = false;
$paths = [
    __DIR__ . '/../config/db.php',
    __DIR__ . '/config/db.php'
];
foreach ($paths as $p) {
    if (file_exists($p)) {
        include $p;
        $included = true;
        break;
    }
}

if (!$included || !isset($conn)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Database connection not found. Please check ../config/db.php.";
    exit;
}

// Set headers for Excel
$filename = 'Payroll_Report_' . date('F_Y') . '.xls';
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// UTF-8 BOM for Excel
echo "\xEF\xBB\xBF";

// Top Header
echo "<table border='0' cellpadding='3' cellspacing='0' style='font-family:Segoe UI, Arial, sans-serif;'>";
echo "<tr><td colspan='13' style='font-size:18pt;font-weight:bold;text-align:center;'>St. Dominic Savio College</td></tr>";
echo "<tr><td colspan='13' style='font-size:14pt;text-align:center;'>" . ($type === 'reports' ? 'Payroll Report' : 'Payroll Records') . "</td></tr>";
echo "<tr><td colspan='13' style='font-size:12pt;text-align:center;'>Period: " . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . "</td></tr>";
echo "<tr><td colspan='13' style='font-size:12pt;text-align:center;'>Department: " . htmlspecialchars($dept_name) . "</td></tr>";
echo "<tr><td colspan='13' style='font-size:10pt;text-align:right;'>Generated on: " . date('F d, Y h:i A') . "</td></tr>";
echo "<tr><td colspan='13'>&nbsp;</td></tr>";

// Column Header Style
$thStyle = "style='background:#2e7d32;color:#fff;font-weight:bold;padding:8px;border:1px solid #ccc;text-align:left;'";

// Table Headers based on type
$headers = $type === 'reports' ? [
    'Payroll ID',
    'Employee ID',
    'Employee Name',
    'Department',
    'Position',
    'Work Hours',
    'OT Hours',
    'Basic Pay',
    'Gross Pay',
    'Deductions',
    'Net Pay',
    'Pay Date',
    'Remarks'
] : [
    'Payroll ID',
    'Payroll Date',
    'Employee ID',
    'Employee Name',
    'Department',
    'Position',
    'Work Hours',
    'Basic Pay',
    'Deductions',
    'Net Pay',
    'Late (mins)',
    'Absent (hrs)',
    'Remarks'
];

echo "<tr>";
foreach ($headers as $h) {
    echo "<th $thStyle>" . htmlspecialchars($h) . "</th>";
}
echo "</tr>";

// Get filter values from POST
$month = isset($_POST['month']) ? $_POST['month'] : date('m');
$year = isset($_POST['year']) ? $_POST['year'] : date('Y');
$department = isset($_POST['department']) ? $_POST['department'] : '';
$type = isset($_POST['type']) ? $_POST['type'] : 'payroll';

// Update filename based on export type
$filename = ($type === 'reports' ? 'Payroll_Report_' : 'Payroll_Records_') . date('F_Y', mktime(0, 0, 0, $month, 1, $year)) . '.xls';

// Get department name if selected
$dept_name = "All Departments";
if ($department) {
    $dept_query = $conn->prepare("SELECT name FROM departments WHERE id = ?");
    $dept_query->bind_param("s", $department);
    $dept_query->execute();
    $dept_result = $dept_query->get_result();
    if ($dept_row = $dept_result->fetch_assoc()) {
        $dept_name = $dept_row['name'];
    }
}

// Fetch Payroll Data with filters
$sql = "
SELECT 
    p.id AS payroll_id,
    p.pay_date,
    DATE_FORMAT(p.pay_date, '%M %Y') AS payroll_period,
    e.id AS employee_id,
    e.emp_name,
    COALESCE(d.name, '') AS department,
    pos.name AS position,
    p.basic_pay,
    p.deductions,
    p.net_pay,
    e.date_hired,
    p.hours_worked,
    p.overtime_hours,
    p.absent_hours,
    p.late_minutes,
    p.gross_pay
FROM payrolls p
LEFT JOIN employees e ON p.emp_id = e.id
LEFT JOIN departments d ON e.department = d.id
LEFT JOIN positions pos ON e.position = pos.id
WHERE MONTH(p.pay_date) = ? AND YEAR(p.pay_date) = ?
" . ($department ? "AND d.id = ? " : "") . "
ORDER BY p.pay_date DESC, e.emp_name ASC
";

// Prepare and execute query with filters
$stmt = $conn->prepare($sql);
if ($department) {
    $stmt->bind_param("iis", $month, $year, $department);
} else {
    $stmt->bind_param("ii", $month, $year);
}
$stmt->execute();
$result = $stmt->get_result();

$total_basic = 0;
$total_deductions = 0;
$total_net = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $total_basic += (float)$row['basic_pay'];
        $total_deductions += (float)$row['deductions'];
        $total_net += (float)$row['net_pay'];

        // Calculate total hours and generate remarks
        $total_hours = (float)$row['hours_worked'] + (float)$row['overtime_hours'];
        $remarks = [];
        if ((float)$row['overtime_hours'] > 0) $remarks[] = "OT: " . number_format($row['overtime_hours'], 2) . "hrs";
        if ((float)$row['late_minutes'] > 0) $remarks[] = "Late: " . $row['late_minutes'] . "min";
        if ((float)$row['absent_hours'] > 0) $remarks[] = "Absent: " . number_format($row['absent_hours'], 2) . "hrs";
        
        echo "<tr>";
        
        if ($type === 'reports') {
            echo "<td style='border:1px solid #ddd;padding:6px;'>#" . sprintf('%05d', $row['payroll_id']) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;'>#" . sprintf('%03d', $row['employee_id']) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;'>" . htmlspecialchars($row['emp_name']) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;'>" . htmlspecialchars($row['department']) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;'>" . htmlspecialchars($row['position']) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;text-align:right;'>" . number_format($row['hours_worked'], 2) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;text-align:right;'>" . number_format($row['overtime_hours'], 2) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;text-align:right;mso-number-format:\"#,##0.00\";'>" . number_format((float)$row['basic_pay'], 2) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;text-align:right;mso-number-format:\"#,##0.00\";'>" . number_format((float)$row['gross_pay'], 2) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;text-align:right;mso-number-format:\"#,##0.00\";'>" . number_format((float)$row['deductions'], 2) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;text-align:right;mso-number-format:\"#,##0.00\";'>" . number_format((float)$row['net_pay'], 2) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;'>" . date('M d, Y', strtotime($row['pay_date'])) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;'>" . implode(', ', $remarks) . "</td>";
        } else {
            echo "<td style='border:1px solid #ddd;padding:6px;'>#" . sprintf('%05d', $row['payroll_id']) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;'>" . date('M d, Y', strtotime($row['pay_date'])) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;'>#" . sprintf('%03d', $row['employee_id']) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;'>" . htmlspecialchars($row['emp_name']) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;'>" . htmlspecialchars($row['department']) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;'>" . htmlspecialchars($row['position']) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;text-align:right;'>" . number_format($total_hours, 2) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;text-align:right;mso-number-format:\"#,##0.00\";'>" . number_format((float)$row['basic_pay'], 2) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;text-align:right;mso-number-format:\"#,##0.00\";'>" . number_format((float)$row['deductions'], 2) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;text-align:right;mso-number-format:\"#,##0.00\";'>" . number_format((float)$row['net_pay'], 2) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;text-align:right;'>" . number_format($row['late_minutes'], 0) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;text-align:right;'>" . number_format($row['absent_hours'], 2) . "</td>";
            echo "<td style='border:1px solid #ddd;padding:6px;'>" . implode(', ', $remarks) . "</td>";
        }
        
        echo "</tr>";
    }

    // Summary row (totals)
    echo "<tr style='background:#e3f2fd;font-weight:bold;'>";
    echo "<td colspan='7' style='border:1px solid #ccc;padding:8px;text-align:right;'>TOTALS:</td>";
    echo "<td style='border:1px solid #ccc;padding:8px;text-align:right;mso-number-format:\"#,##0.00\";'>" . number_format($total_basic, 2) . "</td>";
    echo "<td style='border:1px solid #ccc;padding:8px;text-align:right;mso-number-format:\"#,##0.00\";'>" . number_format($total_deductions, 2) . "</td>";
    echo "<td style='border:1px solid #ccc;padding:8px;text-align:right;mso-number-format:\"#,##0.00\";'>" . number_format($total_net, 2) . "</td>";
    echo "<td style='border:1px solid #ccc;padding:8px;'></td>";
    echo "</tr>";

} else {
    echo "<tr><td colspan='11' style='text-align:center;padding:10px;'>No payroll records found.</td></tr>";
}

echo "</table>";
exit;
?>
