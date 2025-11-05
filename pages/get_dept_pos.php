<?php
include '../config/db.php';
header('Content-Type: application/json');

// Get department name from request
$department = isset($_GET['department']) ? trim($_GET['department']) : '';

if (empty($department)) {
    echo json_encode(['error' => 'No department provided']);
    exit;
}

try {
    // Get employees with position and salary for the selected department
    $query = "
        SELECT 
            e.id,
            e.emp_name as name,
            e.position as position_name,
            e.salary,
            e.department as department_name
        FROM employees e
        WHERE TRIM(REPLACE(e.department, '\r\n', '')) = TRIM(?) 
        AND e.deleted_at IS NULL 
        ORDER BY e.emp_name ASC
    ";
    
    error_log("Searching for employees in department: " . $department);

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare query: " . $conn->error);
    }
    
    $stmt->bind_param('s', $department);
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $employees = array();
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    
    echo json_encode(['data' => $employees]);

} catch (Exception $e) {
    error_log("Error in get_dept_pos.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
