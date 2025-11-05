<?php
include '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Not authorized']));
}

$departments = $conn->query("SELECT id, name FROM departments WHERE status = 'active' ORDER BY name ASC");
$result = [];

while ($dept = $departments->fetch_assoc()) {
    $result[] = [
        'id' => $dept['id'],
        'name' => htmlspecialchars($dept['name'])
    ];
}

header('Content-Type: application/json');
echo json_encode($result);
?>