<?php
include '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Not authorized']));
}

// Get payroll records
$emp_id = isset($_GET['emp_id']) ? intval($_GET['emp_id']) : null;

$query = "SELECT 
    p.*,
    e.emp_name,
    e.department,
    e.position,
    e.salary
    FROM payrolls p
    JOIN employees e ON p.emp_id = e.id
    WHERE 1=1 " . 
    ($emp_id ? "AND p.emp_id = " . $emp_id : "") . "
    ORDER BY p.pay_date DESC, p.id DESC";

$result = $conn->query($query);
if (!$result) {
    die(json_encode(['error' => 'Database error: ' . $conn->error]));
}

$records = [];
while ($row = $result->fetch_assoc()) {
    $records[] = [
        'id' => $row['id'],
        'ref_no' => str_pad($row['id'], 8, '0', STR_PAD_LEFT),
        'pay_date' => date('M d, Y', strtotime($row['pay_date'])),
        'emp_name' => $row['emp_name'],
        'department' => $row['department'],
        'position' => $row['position'],
        'net_pay' => $row['net_pay']
    ];
}

header('Content-Type: application/json');
echo json_encode($records);