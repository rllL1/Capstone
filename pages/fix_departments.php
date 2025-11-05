<?php
include '../config/db.php';

// First, let's clean up department names in employees table
$clean_query = "
    UPDATE employees 
    SET department = TRIM(REPLACE(department, '\r', ''))
    WHERE department LIKE '%\r%' OR department LIKE ' %' OR department LIKE '% '
";
$conn->query($clean_query);

// Get all departments
$dept_query = "SELECT id, name FROM departments ORDER BY name";
$dept_result = $conn->query($dept_query);
$departments = [];
while ($row = $dept_result->fetch_assoc()) {
    $departments[$row['name']] = $row['id'];
}

// Update employees to match department names exactly
foreach ($departments as $dept_name => $dept_id) {
    $update_query = "
        UPDATE employees 
        SET department = ? 
        WHERE LOWER(TRIM(department)) = LOWER(?)
    ";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('ss', $dept_name, $dept_name);
    $stmt->execute();
}

echo "Department names have been standardized.\n";

// Show the results
echo "\nCurrent department mappings:\n";
$check_query = "
    SELECT DISTINCT e.department, d.name as dept_name
    FROM employees e
    LEFT JOIN departments d ON d.name = e.department
    WHERE e.deleted_at IS NULL
    ORDER BY e.department
";

$result = $conn->query($check_query);
while ($row = $result->fetch_assoc()) {
    echo "Employee dept: {$row['department']}, Mapped to: {$row['dept_name']}\n";
}
?>