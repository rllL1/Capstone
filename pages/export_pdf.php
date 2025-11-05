<?php
include '../config/db.php';
require '../vendor/autoload.php'; // Make sure you have TCPDF installed
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get filter values
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$department = isset($_GET['department']) ? $_GET['department'] : '';
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Prepare filter conditions
$conditions = ["MONTH(p.pay_date) = ?", "YEAR(p.pay_date) = ?"];
$params = [$month, $year];
$types = "ii";

if ($department) {
    $conditions[] = "d.id = ?";
    $params[] = $department;
    $types .= "s";
}

if ($searchQuery) {
    $conditions[] = "(e.emp_name LIKE ? OR d.name LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
    $types .= "ss";
}

// Build the query
$sql = "
    SELECT 
        p.*,
        e.emp_name,
        d.name as department,
        pos.name as position
    FROM payrolls p
    JOIN employees e ON p.emp_id = e.id
    JOIN departments d ON e.department = d.id
    JOIN positions pos ON e.position = pos.id
    WHERE " . implode(" AND ", $conditions) . "
    ORDER BY p.pay_date DESC, p.id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('SDSC Payroll System');
$pdf->SetTitle('Payroll Records - ' . date('F Y', mktime(0, 0, 0, $month, 1, $year)));

// Set margins
$pdf->SetMargins(10, 10, 10);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(0);

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 10);

// Add a page
$pdf->AddPage('L', 'A4');

// Set font
$pdf->SetFont('helvetica', '', 10);

// Title
$title = 'Payroll Records - ' . date('F Y', mktime(0, 0, 0, $month, 1, $year));
if ($department) {
    $dept_query = $conn->prepare("SELECT name FROM departments WHERE id = ?");
    $dept_query->bind_param("s", $department);
    $dept_query->execute();
    $dept_result = $dept_query->get_result()->fetch_assoc();
    $title .= ' - ' . $dept_result['name'];
}
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, $title, 0, 1, 'C');
$pdf->Ln(5);

// Table headers
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(46, 125, 50);
$pdf->SetTextColor(255);
$headers = ['ID', 'Employee', 'Department', 'Position', 'Basic Pay', 'Work Hrs', 'OT Hrs', 'Deductions', 'Net Pay', 'Pay Date', 'Status'];
$widths = [20, 40, 35, 35, 25, 20, 20, 25, 25, 25, 20];

foreach ($headers as $i => $header) {
    $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
}
$pdf->Ln();

// Reset text color
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', '', 9);

// Table data
$fill = false;
while ($row = $result->fetch_assoc()) {
    $pdf->Cell($widths[0], 6, sprintf('#%05d', $row['id']), 1, 0, 'C', $fill);
    $pdf->Cell($widths[1], 6, $row['emp_name'], 1, 0, 'L', $fill);
    $pdf->Cell($widths[2], 6, $row['department'], 1, 0, 'L', $fill);
    $pdf->Cell($widths[3], 6, $row['position'], 1, 0, 'L', $fill);
    $pdf->Cell($widths[4], 6, '₱' . number_format($row['basic_pay'], 2), 1, 0, 'R', $fill);
    $pdf->Cell($widths[5], 6, number_format($row['hours_worked'], 2), 1, 0, 'C', $fill);
    $pdf->Cell($widths[6], 6, number_format($row['overtime_hours'], 2), 1, 0, 'C', $fill);
    $pdf->Cell($widths[7], 6, '₱' . number_format($row['deductions'], 2), 1, 0, 'R', $fill);
    $pdf->Cell($widths[8], 6, '₱' . number_format($row['net_pay'], 2), 1, 0, 'R', $fill);
    $pdf->Cell($widths[9], 6, date('M d, Y', strtotime($row['pay_date'])), 1, 0, 'C', $fill);
    $pdf->Cell($widths[10], 6, $row['net_pay'] > 0 ? 'Paid' : 'Pending', 1, 0, 'C', $fill);
    $pdf->Ln();
    $fill = !$fill;
}

// Summary
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 7, 'Summary', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

// Calculate totals
$result->data_seek(0);
$totals = [
    'count' => 0,
    'total_pay' => 0,
    'total_hours' => 0,
    'total_ot' => 0
];

while ($row = $result->fetch_assoc()) {
    $totals['count']++;
    $totals['total_pay'] += $row['net_pay'];
    $totals['total_hours'] += $row['hours_worked'];
    $totals['total_ot'] += $row['overtime_hours'];
}

// Print summary
$pdf->Cell(50, 6, 'Total Records:', 0, 0, 'L');
$pdf->Cell(50, 6, number_format($totals['count']), 0, 1, 'L');
$pdf->Cell(50, 6, 'Total Net Pay:', 0, 0, 'L');
$pdf->Cell(50, 6, '₱' . number_format($totals['total_pay'], 2), 0, 1, 'L');
$pdf->Cell(50, 6, 'Total Work Hours:', 0, 0, 'L');
$pdf->Cell(50, 6, number_format($totals['total_hours'], 2) . ' hrs', 0, 1, 'L');
$pdf->Cell(50, 6, 'Total Overtime:', 0, 0, 'L');
$pdf->Cell(50, 6, number_format($totals['total_ot'], 2) . ' hrs', 0, 1, 'L');

// Footer with generation date
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 5, 'Generated on: ' . date('F d, Y h:i A'), 0, 0, 'R');

// Output PDF
$filename = "payroll_records_" . date('F_Y', mktime(0, 0, 0, $month, 1, $year)) . ".pdf";
$pdf->Output($filename, 'D');
exit;