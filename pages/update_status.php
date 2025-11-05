<?php
include '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $type = $_POST['type'] ?? '';
    $status = $_POST['status'] === 'true' ? 'active' : 'inactive';
    
    if (!$id || !in_array($type, ['department', 'position'])) {
        die(json_encode(['status' => 'error', 'message' => 'Invalid parameters']));
    }
    
    $table = $type === 'department' ? 'departments' : 'positions';
    $stmt = $conn->prepare("UPDATE $table SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => ucfirst($type) . ' status updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update status']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>