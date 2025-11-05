<?php
include '../config/db.php';
header('Content-Type: application/json');

if (isset($_GET['department_id'])) {
    $dept_id = intval($_GET['department_id']);
    
    // Get positions for the specific department
    $result = $conn->query("SELECT id, name, salary FROM positions WHERE department_id = $dept_id ORDER BY name ASC");
    
    $positions = [];
    while ($row = $result->fetch_assoc()) {
        $positions[] = [
            'id' => intval($row['id']),
            'name' => $row['name'],
            'salary' => floatval($row['salary'])
        ];
    }
    echo json_encode($positions);
} else {
    echo json_encode([]);
}
?>
