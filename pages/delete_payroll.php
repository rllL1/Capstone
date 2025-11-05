<?php
include '../config/db.php';
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Not authorized']));
}

// Check if payroll ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Invalid payroll ID']));
}

$payroll_id = intval($_GET['id']);

// Get JSON input for CSRF token
$input = json_decode(file_get_contents('php://input'), true);

// Verify CSRF token
if (!isset($input['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token']) {
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Invalid security token']));
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Check if payroll record exists
    $check_query = "SELECT id FROM payrolls WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    
    if (!$check_stmt) {
        throw new Exception("Error preparing check query: " . $conn->error);
    }
    
    $check_stmt->bind_param("i", $payroll_id);
    if (!$check_stmt->execute()) {
        throw new Exception("Error executing check query: " . $check_stmt->error);
    }
    
    $result = $check_stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Payroll record not found");
    }

    // Delete the payroll record
    $delete_query = "DELETE FROM payrolls WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    
    if (!$delete_stmt) {
        throw new Exception("Error preparing delete query: " . $conn->error);
    }
    
    $delete_stmt->bind_param("i", $payroll_id);
    if (!$delete_stmt->execute()) {
        throw new Exception("Error executing delete query: " . $delete_stmt->error);
    }

    // Commit transaction
    $conn->commit();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Payroll record deleted successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    error_log("Payroll deletion error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Clean up
if (isset($check_stmt)) {
    $check_stmt->close();
}
if (isset($delete_stmt)) {
    $delete_stmt->close();
}
$conn->close();
?>