<?php
include '../config/db.php';
header('Content-Type: application/json');

$name = trim($_POST['name'] ?? '');
if ($name === '') {
  echo json_encode(['status' => 'error', 'message' => 'Department name is required']);
  exit;
}

// prevent duplicates
$check = $conn->prepare("SELECT id FROM departments WHERE name = ?");
$check->bind_param("s", $name);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
  echo json_encode(['status' => 'error', 'message' => 'Department already exists']);
  exit;
}

$stmt = $conn->prepare("INSERT INTO departments (name) VALUES (?)");
$stmt->bind_param("s", $name);
$stmt->execute();

echo json_encode(['status' => 'success', 'message' => 'Department added successfully']);
?>
