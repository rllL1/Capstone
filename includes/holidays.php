<?php
function get_holidays($month, $year) {
    global $conn;
    
    $holidays = [];
    $start_date = sprintf('%04d-%02d-01', $year, $month);
    $end_date = sprintf('%04d-%02d-%02d', $year, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year));
    
    // Check if holidays table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'holidays'");
    if ($table_check && $table_check->num_rows > 0) {
        // Query holidays from database
        $stmt = $conn->prepare("
            SELECT date, name, type 
            FROM holidays 
            WHERE date BETWEEN ? AND ?
            ORDER BY date ASC
        ");
        $stmt->bind_param('ss', $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $holidays[] = $row;
        }
    } else {
        // If holidays table doesn't exist, use default holidays
        $holidays = get_default_holidays($month, $year);
    }
    
    return $holidays;
}

// Example holidays for the current month (you should replace this with database data)
function get_default_holidays($month, $year) {
    return [
        ['date' => '2025-10-16', 'name' => 'Regular Holiday 1', 'type' => 'regular'],
        ['date' => '2025-10-31', 'name' => 'Special Non-working Day', 'type' => 'special']
    ];
}