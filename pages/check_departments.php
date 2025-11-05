<?php
include '../config/db.php';

// Get all departments from departments table
$dept_query = "SELECT id, name FROM departments ORDER BY name";
$dept_result = $conn->query($dept_query);
$departments = [];
while ($row = $dept_result->fetch_assoc()) {
    $departments[$row['id']] = $row['name'];
}

echo "Departments from departments table:\n";
print_r($departments);

// Get all departments from employees table
$emp_query = "SELECT DISTINCT department FROM employees WHERE deleted_at IS NULL ORDER BY department";
$emp_result = $conn->query($emp_query);
$employee_depts = [];
while ($row = $emp_result->fetch_assoc()) {
    $employee_depts[] = $row['department'];
}

echo "\n\nDepartments from employees table:\n";
print_r($employee_depts);

// Find mismatches
echo "\n\nDepartments in employees table that don't match departments table:\n";
foreach ($employee_depts as $dept) {
    if (!in_array($dept, $departments)) {
        echo "- " . $dept . "\n";
    }
}
?>