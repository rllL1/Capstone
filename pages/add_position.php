<?php
include '../config/db.php';
header('Content-Type: application/json');

$type = $_POST['type'] ?? '';
$id = intval($_POST['id'] ?? 0);

if (!$id || !in_array($type, ['department', 'position'])) {
  echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
  exit;
}

$table = ($type === 'department') ? 'departments' : 'positions';
$stmt = $conn->prepare("DELETE FROM $table WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

echo json_encode(['status' => 'success', 'message' => ucfirst($type) . ' deleted successfully']);
?>
