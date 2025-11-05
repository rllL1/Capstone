<?php
include '../config/db.php';

echo "=== Department Names in departments table ===\n";
$query = "SELECT id, name FROM departments ORDER BY id";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}, Name: {$row['name']}\n";
}

echo "\n=== Unique department values in employees table ===\n";
$query = "SELECT DISTINCT department FROM employees WHERE deleted_at IS NULL";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    echo "Department: " . ($row['department'] ?? 'NULL') . "\n";
}

echo "\n=== Sample employee records ===\n";
$query = "SELECT id, emp_name, department, position, salary FROM employees WHERE deleted_at IS NULL LIMIT 5";
$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}, Name: {$row['emp_name']}, Dept: {$row['department']}, Position: {$row['position']}, Salary: {$row['salary']}\n";
}
?>