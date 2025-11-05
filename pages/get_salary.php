<?php
include '../config/db.php';

if (isset($_GET['position_id'])) {
    $position_id = intval($_GET['position_id']);
    $result = $conn->query("SELECT salary FROM positions WHERE id = $position_id");
    $row = $result->fetch_assoc();
    echo $row ? $row['salary'] : '';
}
?>
