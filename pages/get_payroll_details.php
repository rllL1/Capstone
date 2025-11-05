<?php
require_once '../config/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid record ID');
}

$record_id = (int)$_GET['id'];

$query = "SELECT pr.*, 
          e.firstname, e.lastname, e.employee_id as emp_code,
          d.name as department_name,
          p.name as position_name
          FROM payroll_records pr
          JOIN employees e ON pr.employee_id = e.id
          JOIN departments d ON e.department_id = d.id
          JOIN positions p ON e.position_id = p.id
          WHERE pr.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$result = $stmt->get_result();
$record = $result->fetch_assoc();

if (!$record) {
    die('Record not found');
}
?>

<div class="row">
    <div class="col-md-12">
        <table class="table table-bordered">
            <tr>
                <th colspan="4" class="bg-gray"><h4>Employee Information</h4></th>
            </tr>
            <tr>
                <th width="25%">Employee Name</th>
                <td width="25%"><?php echo htmlspecialchars($record['firstname'] . ' ' . $record['lastname']); ?></td>
                <th width="25%">Employee ID</th>
                <td width="25%"><?php echo htmlspecialchars($record['emp_code']); ?></td>
            </tr>
            <tr>
                <th>Department</th>
                <td><?php echo htmlspecialchars($record['department_name']); ?></td>
                <th>Position</th>
                <td><?php echo htmlspecialchars($record['position_name']); ?></td>
            </tr>
            <tr>
                <th>Pay Period</th>
                <td colspan="3">
                    <?php 
                    echo date('M d, Y', strtotime($record['pay_period_start'])) . ' - ' . 
                         date('M d, Y', strtotime($record['pay_period_end']));
                    ?>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <table class="table table-bordered">
            <tr>
                <th colspan="2" class="bg-gray"><h4>Work Details</h4></th>
            </tr>
            <tr>
                <th width="50%">Days Worked</th>
                <td><?php echo $record['days_worked']; ?></td>
            </tr>
            <tr>
                <th>Hours Per Day</th>
                <td><?php echo $record['hours_per_day']; ?></td>
            </tr>
            <tr>
                <th>Total Hours</th>
                <td><?php echo $record['total_hours']; ?></td>
            </tr>
            <tr>
                <th>Absent Hours</th>
                <td><?php echo $record['absent_hours']; ?></td>
            </tr>
            <tr>
                <th>Actual Hours</th>
                <td><?php echo $record['actual_hours']; ?></td>
            </tr>
            <tr>
                <th>Late Minutes</th>
                <td><?php echo $record['late_minutes']; ?></td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <table class="table table-bordered">
            <tr>
                <th colspan="2" class="bg-gray"><h4>Earnings & Deductions</h4></th>
            </tr>
            <tr>
                <th width="50%">Hourly Rate</th>
                <td><?php echo formatCurrency($record['hourly_rate']); ?></td>
            </tr>
            <tr>
                <th>Gross Pay</th>
                <td><?php echo formatCurrency($record['gross_pay']); ?></td>
            </tr>
            <tr>
                <th>Tardiness Deduction</th>
                <td><?php echo formatCurrency($record['tardiness_deduction']); ?></td>
            </tr>
            <tr>
                <th>Tax</th>
                <td><?php echo formatCurrency($record['tax_deduction']); ?></td>
            </tr>
            <tr>
                <th>SSS</th>
                <td><?php echo formatCurrency($record['sss_deduction']); ?></td>
            </tr>
            <tr>
                <th>PhilHealth</th>
                <td><?php echo formatCurrency($record['philhealth_deduction']); ?></td>
            </tr>
            <tr>
                <th>Pag-IBIG</th>
                <td><?php echo formatCurrency($record['pagibig_deduction']); ?></td>
            </tr>
            <tr class="bg-gray">
                <th>Total Deductions</th>
                <td><strong><?php echo formatCurrency($record['total_deductions']); ?></strong></td>
            </tr>
            <tr class="bg-success">
                <th>Net Pay</th>
                <td><strong><?php echo formatCurrency($record['net_pay']); ?></strong></td>
            </tr>
        </table>
    </div>
</div>