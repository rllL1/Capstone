<?php
require_once '../config/db.php';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Payroll Records</h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Payroll Records</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">All Payroll Records</h3>
                    </div>
                    <div class="box-body">
                        <table id="payrollRecords" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Employee Name</th>
                                    <th>Pay Period</th>
                                    <th>Days Worked</th>
                                    <th>Gross Pay</th>
                                    <th>Total Deductions</th>
                                    <th>Net Pay</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT pr.*, 
                                         CONCAT(e.firstname, ' ', e.lastname) as employee_name,
                                         e.employee_id as emp_code
                                         FROM payroll_records pr
                                         JOIN employees e ON pr.employee_id = e.id
                                         ORDER BY pr.created_at DESC";
                                
                                $result = $conn->query($query);
                                
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['employee_name']) . " (" . htmlspecialchars($row['emp_code']) . ")</td>";
                                    echo "<td>" . date('M d, Y', strtotime($row['pay_period_start'])) . " - " . 
                                         date('M d, Y', strtotime($row['pay_period_end'])) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['days_worked']) . "</td>";
                                    echo "<td>" . formatCurrency($row['gross_pay']) . "</td>";
                                    echo "<td>" . formatCurrency($row['total_deductions']) . "</td>";
                                    echo "<td>" . formatCurrency($row['net_pay']) . "</td>";
                                    echo "<td><span class='label label-" . 
                                         ($row['status'] == 'approved' ? 'success' : 
                                          ($row['status'] == 'rejected' ? 'danger' : 'warning')) . 
                                         "'>" . ucfirst($row['status']) . "</span></td>";
                                    echo "<td>
                                            <button type='button' class='btn btn-info btn-sm view-record' 
                                                    data-id='" . $row['id'] . "'><i class='fa fa-eye'></i> View</button>
                                         </td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- View Record Modal -->
<div class="modal fade" id="viewRecordModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Payroll Record Details</h4>
            </div>
            <div class="modal-body" id="recordDetails">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#payrollRecords').DataTable({
        "order": [[1, "desc"]],
        "responsive": true
    });

    // View Record Modal Handler
    $('.view-record').on('click', function() {
        var recordId = $(this).data('id');
        
        $.ajax({
            url: 'get_payroll_details.php',
            type: 'GET',
            data: { id: recordId },
            success: function(response) {
                $('#recordDetails').html(response);
                $('#viewRecordModal').modal('show');
            },
            error: function() {
                alert('Error loading record details');
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
