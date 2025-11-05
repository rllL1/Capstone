<?php
include '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Unauthorized']));
}

$query = "
    SELECT 
        id,
        employee_id,
        emp_name,
        position,
        department,
        DATE_FORMAT(deleted_at, '%Y-%m-%d %H:%i') as archived_date
    FROM employees 
    WHERE deleted_at IS NOT NULL 
    ORDER BY deleted_at DESC
";

$result = $conn->query($query);
$archived = [];

while ($row = $result->fetch_assoc()) {
    $archived[] = $row;
}

header('Content-Type: application/json');
echo json_encode($archived);
?>