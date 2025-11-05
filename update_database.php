<?php
include 'config/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Function to check if column exists
    function columnExists($conn, $table, $column) {
        $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        return $result->num_rows > 0;
    }

    // Function to check if index exists
    function indexExists($conn, $table, $indexName) {
        $result = $conn->query("SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'");
        return $result->num_rows > 0;
    }

    // Start transaction
    $conn->begin_transaction();

    // Modify existing columns
    $conn->query("ALTER TABLE payrolls
        MODIFY COLUMN net_pay DECIMAL(10,2),
        MODIFY COLUMN pay_date DATE");

    // Add columns if they don't exist
    $columns = [
        'hours_worked' => 'DECIMAL(10,2) DEFAULT 0.00',
        'absent_hours' => 'DECIMAL(10,2) DEFAULT 0.00',
        'late_minutes' => 'DECIMAL(10,2) DEFAULT 0.00',
        'overtime_hours' => 'DECIMAL(10,2) DEFAULT 0.00',
        'gross_pay' => 'DECIMAL(10,2) DEFAULT 0.00',
        'deductions' => 'DECIMAL(10,2) DEFAULT 0.00'
    ];

    foreach ($columns as $column => $definition) {
        if (!columnExists($conn, 'payrolls', $column)) {
            $conn->query("ALTER TABLE payrolls ADD COLUMN $column $definition");
        }
    }

    // Add indexes if they don't exist
    if (!indexExists($conn, 'payrolls', 'idx_emp_date')) {
        $conn->query("CREATE INDEX idx_emp_date ON payrolls (emp_id, pay_date)");
    }
    if (!indexExists($conn, 'payrolls', 'idx_pay_date')) {
        $conn->query("CREATE INDEX idx_pay_date ON payrolls (pay_date)");
    }

    // Commit transaction
    $conn->commit();
    echo "Database updated successfully!";

} catch (Exception $e) {
    // Roll back transaction on error
    $conn->rollback();
    echo "Error updating database: " . $e->getMessage();
    error_log("Database update error: " . $e->getMessage());
}

// Close connection
$conn->close();
?>