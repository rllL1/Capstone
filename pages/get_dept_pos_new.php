<?php
include '../config/db.php';
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get department_id from request
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;

if (!$department_id) {
    echo json_encode([
        'success' => false,
        'error' => 'No department ID provided'
    ]);
    exit;
}

try {
    // First get the department name
    $dept_query = "SELECT name FROM departments WHERE id = ?";
    $dept_stmt = $conn->prepare($dept_query);
    if (!$dept_stmt) {
        throw new Exception("Failed to prepare department query: " . $conn->error);
    }
    
    $dept_stmt->bind_param('i', $department_id);
    $dept_stmt->execute();
    $dept_result = $dept_stmt->get_result();
    $department = $dept_result->fetch_assoc();
    
    if (!$department) {
        throw new Exception("Department not found with ID: " . $department_id);
    }
    
    $dept_name = $department['name'];
    
    // Get positions for this department with salary (base_salary as fallback)
    $query = "SELECT 
                id,
                name,
                COALESCE(salary, base_salary) as salary
              FROM positions 
              WHERE department_id = ? 
              ORDER BY name ASC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare positions query: " . $conn->error);
    }
    
    $stmt->bind_param('i', $department_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => true,
            'department' => $dept_name,
            'data' => []
        ]);
        exit;
    }
    
    $positions = array();
    while ($row = $result->fetch_assoc()) {
        $positions[] = array(
            'id' => $row['id'],
            'name' => $row['name'],
            'salary' => $row['salary']
        );
    }
    
    echo json_encode([
        'success' => true,
        'department' => $dept_name,
        'data' => $positions
    ]);

} catch (Exception $e) {
    error_log("Error in get_dept_pos_new.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>