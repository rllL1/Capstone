<?php
require('../config/db.php');
require('../vendor/tecnickcom/tcpdf/tcpdf.php');

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get filter values
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$department = isset($_GET['department']) ? $_GET['department'] : '';

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

// Create new PDF document
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 15, 'SDSC Payroll System - Reports', 0, false, 'C', 0, '', 0, false, 'M', 'M');
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('SDSC Payroll System');
$pdf->SetAuthor('SDSC');
$pdf->SetTitle('Payroll Report Summary');

// Set margins
$pdf->SetMargins(15, 30, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 25);

// Add a page
$pdf->AddPage('P', 'A4');

// Set font
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Payroll Summary Report', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 10, 'Period: ' . date('F Y', mktime(0, 0, 0, $month, 1, $year)), 0, 1, 'C');
$pdf->Cell(0, 10, 'Department: ' . $dept_name, 0, 1, 'C');
$pdf->Ln(5);

// Calculate totals with proper filters
$totals_sql = "
    SELECT 
        COUNT(DISTINCT e.id) as total_employees,
        SUM(p.net_pay) as total_payroll,
        AVG(p.net_pay) as avg_salary,
        SUM(p.gross_pay) as total_gross,
        SUM(p.deductions) as total_deductions,
        SUM(p.overtime_hours) as total_overtime
    FROM employees e
    JOIN payrolls p ON e.id = p.emp_id
    JOIN departments d ON e.department = d.id
    WHERE MONTH(p.pay_date) = ? AND YEAR(p.pay_date) = ?
    " . ($department ? "AND d.id = ?" : "");

$totals_stmt = $conn->prepare($totals_sql);

if ($department) {
    $totals_stmt->bind_param("iis", $month, $year, $department);
} else {
    $totals_stmt->bind_param("ii", $month, $year);
}

$totals_stmt->execute();
$totals = $totals_stmt->get_result()->fetch_assoc();

// Summary Statistics Section
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Summary Statistics', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 11);

// Create summary table
$pdf->SetFillColor(46, 125, 50);
$pdf->SetTextColor(255);
$pdf->Cell(100, 8, 'Metric', 1, 0, 'L', true);
$pdf->Cell(80, 8, 'Value', 1, 1, 'R', true);

$pdf->SetTextColor(0);
$pdf->Cell(100, 8, 'Total Employees', 1, 0, 'L');
$pdf->Cell(80, 8, number_format($totals['total_employees']), 1, 1, 'R');

$pdf->Cell(100, 8, 'Total Payroll', 1, 0, 'L');
$pdf->Cell(80, 8, '₱ ' . number_format($totals['total_payroll'], 2), 1, 1, 'R');

$pdf->Cell(100, 8, 'Average Salary', 1, 0, 'L');
$pdf->Cell(80, 8, '₱ ' . number_format($totals['avg_salary'], 2), 1, 1, 'R');

$pdf->Cell(100, 8, 'Total Gross Pay', 1, 0, 'L');
$pdf->Cell(80, 8, '₱ ' . number_format($totals['total_gross'], 2), 1, 1, 'R');

$pdf->Cell(100, 8, 'Total Deductions', 1, 0, 'L');
$pdf->Cell(80, 8, '₱ ' . number_format($totals['total_deductions'], 2), 1, 1, 'R');

$pdf->Cell(100, 8, 'Total Overtime Hours', 1, 0, 'L');
$pdf->Cell(80, 8, number_format($totals['total_overtime'], 2) . ' hrs', 1, 1, 'R');

$pdf->Ln(10);

// Department Breakdown Section
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Department Breakdown', 0, 1, 'L');

// Get department data
$dept_sql = "
    SELECT 
        d.name as department,
        COUNT(DISTINCT e.id) as emp_count,
        SUM(p.net_pay) as dept_total,
        AVG(p.net_pay) as dept_avg,
        SUM(p.overtime_hours) as total_overtime
    FROM departments d
    LEFT JOIN employees e ON d.id = e.department
    LEFT JOIN payrolls p ON e.id = p.emp_id 
        AND MONTH(p.pay_date) = ? AND YEAR(p.pay_date) = ?
    " . ($department ? "WHERE d.id = ?" : "") . "
    GROUP BY d.id, d.name
    ORDER BY d.name";

$dept_stmt = $conn->prepare($dept_sql);

if ($department) {
    $dept_stmt->bind_param("iis", $month, $year, $department);
} else {
    $dept_stmt->bind_param("ii", $month, $year);
}

$dept_stmt->execute();
$dept_result = $dept_stmt->get_result();

// Department breakdown table headers
$pdf->SetFillColor(46, 125, 50);
$pdf->SetTextColor(255);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(50, 8, 'Department', 1, 0, 'L', true);
$pdf->Cell(25, 8, 'Staff', 1, 0, 'C', true);
$pdf->Cell(40, 8, 'Total Pay', 1, 0, 'R', true);
$pdf->Cell(40, 8, 'Avg. Salary', 1, 0, 'R', true);
$pdf->Cell(25, 8, 'OT Hours', 1, 1, 'R', true);

// Department breakdown data
$pdf->SetTextColor(0);
$total_employees = 0;
$total_payroll = 0;
$total_overtime = 0;

while ($dept = $dept_result->fetch_assoc()) {
    $total_employees += $dept['emp_count'];
    $total_payroll += $dept['dept_total'];
    $total_overtime += $dept['total_overtime'];
    
    $pdf->Cell(50, 8, $dept['department'], 1, 0, 'L');
    $pdf->Cell(25, 8, number_format($dept['emp_count']), 1, 0, 'C');
    $pdf->Cell(40, 8, '₱ ' . number_format($dept['dept_total'], 2), 1, 0, 'R');
    $pdf->Cell(40, 8, '₱ ' . number_format($dept['dept_avg'], 2), 1, 0, 'R');
    $pdf->Cell(25, 8, number_format($dept['total_overtime'], 2), 1, 1, 'R');
}

// Add totals row
$pdf->SetFillColor(238, 245, 233);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(50, 8, 'TOTAL', 1, 0, 'L', true);
$pdf->Cell(25, 8, number_format($total_employees), 1, 0, 'C', true);
$pdf->Cell(40, 8, '₱ ' . number_format($total_payroll, 2), 1, 0, 'R', true);
$pdf->Cell(40, 8, '₱ ' . number_format($total_payroll / ($total_employees ?: 1), 2), 1, 0, 'R', true);
$pdf->Cell(25, 8, number_format($total_overtime, 2), 1, 1, 'R', true);

// Add Attendance and Overtime Summary
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'Attendance and Overtime Summary', 0, 1, 'L');

// Get attendance data
$attendance_sql = "
    SELECT 
        d.name as department,
        SUM(p.hours_worked) as total_hours,
        SUM(p.overtime_hours) as total_overtime,
        SUM(p.absent_hours) as total_absent,
        SUM(p.late_minutes) as total_late
    FROM departments d
    LEFT JOIN employees e ON d.id = e.department
    LEFT JOIN payrolls p ON e.id = p.emp_id 
        AND MONTH(p.pay_date) = ? AND YEAR(p.pay_date) = ?
    " . ($department ? "WHERE d.id = ?" : "") . "
    GROUP BY d.id, d.name
    ORDER BY d.name";

$attendance_stmt = $conn->prepare($attendance_sql);
if ($department) {
    $attendance_stmt->bind_param("iis", $month, $year, $department);
} else {
    $attendance_stmt->bind_param("ii", $month, $year);
}
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();

// Attendance table headers
$pdf->SetFillColor(46, 125, 50);
$pdf->SetTextColor(255);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(50, 8, 'Department', 1, 0, 'L', true);
$pdf->Cell(35, 8, 'Work Hours', 1, 0, 'R', true);
$pdf->Cell(35, 8, 'OT Hours', 1, 0, 'R', true);
$pdf->Cell(35, 8, 'Absent Hours', 1, 0, 'R', true);
$pdf->Cell(25, 8, 'Late (min)', 1, 1, 'R', true);

// Attendance data
$pdf->SetTextColor(0);
$total_work = 0;
$total_ot = 0;
$total_absent = 0;
$total_late = 0;

while ($att = $attendance_result->fetch_assoc()) {
    $total_work += $att['total_hours'];
    $total_ot += $att['total_overtime'];
    $total_absent += $att['total_absent'];
    $total_late += $att['total_late'];
    
    $pdf->Cell(50, 8, $att['department'], 1, 0, 'L');
    $pdf->Cell(35, 8, number_format($att['total_hours'], 2), 1, 0, 'R');
    $pdf->Cell(35, 8, number_format($att['total_overtime'], 2), 1, 0, 'R');
    $pdf->Cell(35, 8, number_format($att['total_absent'], 2), 1, 0, 'R');
    $pdf->Cell(25, 8, number_format($att['total_late'], 0), 1, 1, 'R');
}

// Add totals row for attendance
$pdf->SetFillColor(238, 245, 233);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(50, 8, 'TOTAL', 1, 0, 'L', true);
$pdf->Cell(35, 8, number_format($total_work, 2), 1, 0, 'R', true);
$pdf->Cell(35, 8, number_format($total_ot, 2), 1, 0, 'R', true);
$pdf->Cell(35, 8, number_format($total_absent, 2), 1, 0, 'R', true);
$pdf->Cell(25, 8, number_format($total_late, 0), 1, 1, 'R', true);

// Add generated date at the bottom
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 10, 'Report generated on: ' . date('F d, Y h:i A'), 0, 1, 'L');

// Close and output PDF document
$pdf->Output('SDSC_Payroll_Report_Summary_' . date('Y-m-d') . '.pdf', 'D');