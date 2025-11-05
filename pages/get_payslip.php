<?php
include '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Not authorized']));
}

if (!isset($_GET['id'])) {
    die(json_encode(['error' => 'Payroll ID is required']));
}

$payroll_id = intval($_GET['id']);

// Get payroll details
$query = "SELECT 
    p.*,
    e.emp_name,
    d.name as department,
    pos.name as position
    FROM payrolls p
    JOIN employees e ON p.emp_id = e.id
    JOIN departments d ON e.department = d.id
    JOIN positions pos ON e.position = pos.id
    WHERE p.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $payroll_id);
$stmt->execute();
$result = $stmt->get_result();
$payroll = $result->fetch_assoc();

if (!$payroll) {
    die(json_encode(['error' => 'Payroll record not found']));
}

// Format the data
$data = [
    'ref_no' => $payroll['ref_no'],
    'pay_date' => date('M d, Y', strtotime($payroll['pay_date'])),
    'emp_name' => $payroll['emp_name'],
    'department' => $payroll['department'],
    'position' => $payroll['position'],
    'basic_pay' => $payroll['basic_pay'],
    'hours_worked' => $payroll['hours_worked'],
    'overtime_hours' => $payroll['overtime_hours'],
    'gross_pay' => $payroll['gross_pay'],
    'net_pay' => $payroll['net_pay'],
    'sss' => $payroll['sss'],
    'philhealth' => $payroll['philhealth'],
    'pagibig' => $payroll['pagibig'],
    'tax' => $payroll['tax'],
    'deductions' => $payroll['deductions']
];

header('Content-Type: application/json');
echo json_encode($data);