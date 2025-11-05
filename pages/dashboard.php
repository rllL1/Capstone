<?php
include '../config/db.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// Get current hour and ensure timezone is correct
date_default_timezone_set('Asia/Manila');
$hour = (int)date('H');
$minute = (int)date('i');

// Precisely define time periods for accurate greetings
if ($hour >= 5 && $hour < 12) {
    // 5:00 AM to 11:59 AM
    $timeOfDay = 'morning';
} elseif ($hour >= 12 && $hour < 17) {
    // 12:00 PM to 4:59 PM
    $timeOfDay = 'afternoon';
} elseif ($hour >= 17 && $hour < 19) {
    // 5:00 PM to 6:59 PM
    $timeOfDay = 'evening';
} else {
    // 7:00 PM to 4:59 AM
    $timeOfDay = 'night';
}

// Debug information to verify time
error_log(sprintf('Current time: %d:%02d - Greeting: %s', $hour, $minute, $timeOfDay));

// Debug line to see what was selected
error_log('Time of day selected: ' . $timeOfDay);

$greet = 'Good ' . $timeOfDay;

// Stats
$totalEmployees   = $conn->query("SELECT COUNT(*) AS t FROM employees")->fetch_assoc()['t'] ?? 0;
$totalPayrolls     = $conn->query("SELECT COUNT(*) AS t FROM payrolls")->fetch_assoc()['t'] ?? 0;
$totalDepartments  = $conn->query("SELECT COUNT(*) AS t FROM departments")->fetch_assoc()['t'] ?? 0;

// Payroll Summary (Current Month)
$summary = $conn->query("
  SELECT 
    SUM(net_pay) AS total_net, 
    SUM(deductions) AS total_ded, 
    AVG(basic_pay) AS avg_salary 
  FROM payrolls 
  WHERE MONTH(pay_date)=MONTH(CURDATE()) AND YEAR(pay_date)=YEAR(CURDATE())
")->fetch_assoc();
$total_net = (float)($summary['total_net'] ?? 0);
$total_ded = (float)($summary['total_ded'] ?? 0);
$avg_salary = (float)($summary['avg_salary'] ?? 0);

// Highest Paid Employee
$top = $conn->query("
    SELECT e.emp_name, p.salary 
    FROM employees e 
    JOIN positions p ON e.position = p.id 
    ORDER BY p.salary DESC 
    LIMIT 1
")->fetch_assoc();

// Monthly payroll chart
$months=[]; $totals=[];
$res = $conn->query("SELECT DATE_FORMAT(pay_date,'%b') AS m, SUM(net_pay) AS s FROM payrolls GROUP BY MONTH(pay_date) ORDER BY MONTH(pay_date)");
while($r=$res->fetch_assoc()){ $months[]=$r['m']; $totals[]=(float)$r['s']; }

// Department distribution chart
$deptLabels=[]; $deptTotals=[];
$res2 = $conn->query("
    SELECT d.name as department, COUNT(e.id) AS total 
    FROM departments d 
    LEFT JOIN employees e ON d.id = e.department 
    GROUP BY d.id, d.name
");
while($r2=$res2->fetch_assoc()){ $deptLabels[]=$r2['department']; $deptTotals[]=(int)$r2['total']; }

// Salary vs deductions
$d = $conn->query("SELECT SUM(basic_pay) AS s, SUM(deductions) AS d FROM payrolls")->fetch_assoc();
$total_salary = (float)($d['s'] ?? 0); 
$total_deductions=(float)($d['d'] ?? 0);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard - SDSC</title>
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.greeting {
    background: linear-gradient(135deg, #2e7d32 0%, #3cbb44ff 100%);
    color: #ffffff;
    padding: 30px;
    border-radius: 20px;
    margin-bottom: 25px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(46,125,50,0.2);
    border: 1px solid rgba(255,255,255,0.1);
}

.greeting::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg,
        rgba(255,255,255,0.1) 0%, 
        rgba(255,255,255,0.05) 100%);
    pointer-events: none;
}

.greeting::after {
    content: '';
    position: absolute;
    top: 50%;
    right: 40px;
    transform: translateY(-50%);
    width: 80px;
    height: 80px;
    opacity: 1;
    background-size: contain !important;
    filter: drop-shadow(0 0 12px rgba(255,255,255,0.4));
    transition: all 0.3s ease;
}

.greeting:hover::after {
    transform: translateY(-50%) scale(1.1);
    filter: drop-shadow(0 0 20px rgba(255,255,255,0.6));
}

/* Morning icon - Philippine Sun Rising */
.greeting.morning::after {
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><defs><radialGradient id="a" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="white"/><stop offset="60%" stop-color="rgba(255,255,255,0.95)"/><stop offset="100%" stop-color="rgba(255,255,255,0.9)"/></radialGradient></defs><circle cx="12" cy="12" r="4" fill="url(%23a)" stroke="white" stroke-width="0.5"/><g><path fill="white" d="M12 4l.8 3h-1.6L12 4zM12 20l.8-3h-1.6l.8 3zM4 12l3 .8v-1.6L4 12zM20 12l-3 .8v-1.6l3 .8zM6.3 6.3l2.8 1.5L7.8 9.1 6.3 6.3zM17.7 17.7l-2.8-1.5 1.3-1.3 1.5 2.8zM17.7 6.3l-1.5 2.8-1.3-1.3 2.8-1.5zM6.3 17.7l1.5-2.8 1.3 1.3-2.8 1.5z"/></g></svg>') no-repeat center center;
}

/* Afternoon icon - Philippine Sun at Peak */
.greeting.afternoon::after {
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><defs><radialGradient id="a" cx="50%" cy="50%" r="50%"><stop offset="0%" stop-color="white"/><stop offset="60%" stop-color="rgba(255,255,255,0.95)"/><stop offset="100%" stop-color="rgba(255,255,255,0.9)"/></radialGradient></defs><circle cx="12" cy="12" r="4.5" fill="url(%23a)" stroke="white" stroke-width="0.5"/><g fill="white"><path d="M12 2l1 4h-2l1-4zM12 22l1-4h-2l1 4zM2 12l4 1v-2l-4 1zM22 12l-4 1v-2l4 1zM4.9 4.9l3.5 1.8-1.4 1.4L4.9 4.9zM19.1 19.1l-3.5-1.8 1.4-1.4 2.1 3.2zM19.1 4.9l-2.1 3.2-1.4-1.4 3.5-1.8zM4.9 19.1l2.1-3.2 1.4 1.4-3.5 1.8z"/><path d="M12 5l.5 2h-1l.5-2zM12 19l.5-2h-1l.5 2zM5 12l2 .5v-1l-2 .5zM19 12l-2 .5v-1l2 .5zM6.7 6.7l1.8 1-0.7.7-1.1-1.7zM17.3 17.3l-1.8-1 0.7-0.7 1.1 1.7zM17.3 6.7l-1.1 1.7-0.7-0.7 1.8-1zM6.7 17.3l1.1-1.7 0.7 0.7-1.8 1z"/></g></svg>') no-repeat center center;
}

/* Evening icon - Philippine Sun Setting */
.greeting.evening::after {
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><defs><radialGradient id="a" cx="50%" cy="40%" r="50%"><stop offset="0%" stop-color="white"/><stop offset="60%" stop-color="rgba(255,255,255,0.95)"/><stop offset="100%" stop-color="rgba(255,255,255,0.9)"/></radialGradient><linearGradient id="b" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" stop-color="rgba(255,255,255,0.8)"/><stop offset="100%" stop-color="rgba(255,255,255,0)"/></linearGradient></defs><circle cx="12" cy="10" r="4" fill="url(%23a)" stroke="white" stroke-width="0.5"/><path fill="url(%23b)" d="M2 15h20v2H2z"/><g fill="white"><path d="M12 3l.8 3h-1.6l.8-3zM4 10l3 .8v-1.6L4 10zM20 10l-3 .8v-1.6l3 .8zM6.3 6.3l2.8 1.5-1.3 1.3-1.5-2.8zM17.7 6.3l-1.5 2.8-1.3-1.3 2.8-1.5z"/></g></svg>') no-repeat center center;
}

/* Night icon - Elegant Moon with Stars */
.greeting.night::after {
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><defs><radialGradient id="a" cx="30%" cy="30%" r="70%"><stop offset="0%" stop-color="white"/><stop offset="60%" stop-color="rgba(255,255,255,0.95)"/><stop offset="100%" stop-color="rgba(255,255,255,0.9)"/></radialGradient></defs><path fill="url(%23a)" stroke="white" stroke-width="0.5" d="M12 3c5.5 0 10 4.5 10 10s-4.5 10-10 10c-2.8 0-5.5-1.2-7.4-3.3C7.3 20.5 11 17 11 12c0-5-3.7-8.5-6.4-7.7C6.5 4.2 9.2 3 12 3z"/><g fill="white"><circle cx="8" cy="6" r="1"/><circle cx="18" cy="8" r="1"/><circle cx="16" cy="15" r="1"/><circle cx="19" cy="12" r="0.8"/><circle cx="9" cy="17" r="0.8"/></g></svg>') no-repeat center center;
}

.greeting h2 {
    margin: 0;
    font-size: 1.8rem;
}

.greeting p {
    margin: 5px 0 0;
    opacity: 0.9;
}

.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.stat-card {
    background: linear-gradient(145deg, #ffffff, #f8f9fa);
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    border-left: 4px solid #2e7d32;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.stat-card h4 {
    margin: 0;
    color: #1b5e20;
    font-weight: 600;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.stat-card h4 i {
    font-size: 1.2rem;
    background: #e8f5e9;
    color: #2e7d32;
    padding: 10px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(46,125,50,0.15);
}

.stat-card p {
    margin: 15px 0 5px;
    font-size: 2rem;
    font-weight: 700;
    color: #1b5e20;
    text-shadow: 1px 1px 0 rgba(255,255,255,0.8);
}

.stat-card small {
    font-size: 0.9rem;
    color: #66bb6a;
    font-weight: 500;
    display: block;
    margin-top: 5px;
}
.nav-link.active{
  color: #000000;
    background: #dcfce7;
    font-weight: 700;
}
.charts {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-top: 20px;
}

.chart-box {
    background: #ffffff;
    padding: 15px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.2s ease;
    border: 1px solid #e8f5e9;
}

.chart-box:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.chart-box h4 {
    margin: 0 0 12px;
    color: #1b5e20;
    font-size: 0.95rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e8f5e9;
}

.chart-box h4 i {
    font-size: 0.9rem;
    background: #e8f5e9;
    color: #2e7d32;
    padding: 8px;
    border-radius: 8px;
}

.table-wrap {
    display: flex;
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.table-container {
    background: var(--bg-white);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    overflow: hidden;
    margin-bottom: var(--space-6);
    flex: 1;
    min-width: 0; /* Prevents flex items from overflowing */
}

.card-header {
    padding: 1rem;
    background: var(--primary);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-header h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-size: 1rem;
}

.badge.bg-white {
    background: white;
    color: var(--primary);
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.table-responsive {
    height: 300px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--primary) transparent;
}

.table-responsive::-webkit-scrollbar {
    width: 6px;
}

.table-responsive::-webkit-scrollbar-track {
    background: transparent;
}

.table-responsive::-webkit-scrollbar-thumb {
    background-color: var(--primary);
    border-radius: 3px;
}

.table-container .card-header {
    padding: 1rem;
    background: var(--primary);
    border-radius: 0.5rem 0.5rem 0 0;
}

.table-container .card-header h3 {
    color: #fff;
    margin: 0;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.table-container .card-header h3 i {
    color: #fff;
}

.table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.875rem;
}

.table th {
    padding: 0.875rem 1rem;
    text-align: left;
    font-weight: 600;
    color: var(--text-dark);
    background: var(--bg-light);
    border-bottom: 2px solid var(--border);
    white-space: nowrap;
}

.table td {
    padding: 0.875rem 1rem;
    color: var(--text-dark);
    border-bottom: 1px solid var(--border-light);
    vertical-align: middle;
}

.table tbody tr:last-child td {
    border-bottom: none;
}

.table tbody tr:hover {
    background: var(--bg-light);
}

.table th i, .table td i {
    width: 1.125rem;
    text-align: center;
    margin-right: 0.5rem;
    color: var(--primary);
}

.refresh-btn {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: var(--primary);
    color: white;
    width: 50px;
    height: 50px;
    border-radius: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.refresh-btn:hover {
    transform: scale(1.1);
}

.refresh-btn i {
    font-size: 1.2rem;
}

/* Table styles improvements */
.table-responsive {
    overflow-x: auto;
    margin: 0;
    padding: 0.5rem;
}

.text-muted {
    color: #6c757d !important;
}

.text-center {
    text-align: center !important;
}

small.d-block {
    display: block;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

.badge {
    display: inline-block;
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem;
    margin-left: 0.5rem;
}

.bg-success {
    background-color: #28a745 !important;
    color: #fff;
}

.bg-warning {
    background-color: #ffc107 !important;
    color: #000;
}

.bg-danger {
    background-color: #dc3545 !important;
    color: #fff;
}

.bg-secondary {
    background-color: #6c757d !important;
    color: #fff;
}

.emp-id {
    background: #2e7d32;
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: bold;
    margin-right: 8px;
    font-family: monospace;
    letter-spacing: 0.5px;
}

@keyframes spin {
    100% { transform: rotate(360deg); }
}

.refresh-btn.loading i {
    animation: spin 1s linear infinite;
}
</style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<div class="main-area">
  <?php include '../includes/header.php'; ?>

  <section class="content">
    <div class="content-card">
      <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
        <div style="background: #e8f5e9; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
          <i class="fas fa-tachometer-alt" style="font-size: 1.2rem; color: #2e7d32;"></i>
        </div>
        <h2 style="margin: 0; color: #1b5e20;">Dashboard</h2>
      </div>
      
      <!-- Time-based greeting -->
      <div class="greeting <?= $timeOfDay ?>">
        <h2 style="color: #ffffff;"><?= $greet ?>, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>!</h2>
        <p>Here's an overview of your payroll system today.</p>
      </div>

      <!-- Quick Stats -->
      <div class="cards">
        <div class="stat-card">
          <h4><i class="fas fa-users"></i> Total Employees</h4>
          <p><?= $totalEmployees ?></p>
          <small>Active members</small>
        </div>
        <div class="stat-card">
          <h4><i class="fas fa-receipt"></i> Total Payrolls</h4>
          <p><?= $totalPayrolls ?></p>
          <small>Processed payments</small>
        </div>
        <div class="stat-card">
          <h4><i class="fas fa-building"></i> Total Departments</h4>
          <p><?= $totalDepartments ?></p>
          <small>Active departments</small>
        </div>
        <div class="stat-card">
          <h4><i class="fas fa-trophy"></i> Highest Paid Employee</h4>
          <p><?= htmlspecialchars($top['emp_name'] ?? 'N/A') ?></p>
          <small>₱<?= number_format($top['salary'] ?? 0, 2) ?> monthly</small>
        </div>
      </div>

      <!-- Monthly Summary -->
      <div class="cards">
        <div class="stat-card">
          <h4><i class="fas fa-money-bill-wave"></i> This Month's Net Pay</h4>
          <p>₱<?= number_format($total_net, 2) ?></p>
          <small>Total disbursement</small>
        </div>
        <div class="stat-card">
          <h4><i class="fas fa-file-invoice-dollar"></i> Total Deductions</h4>
          <p>₱<?= number_format($total_ded, 2) ?></p>
          <small>All deductions this month</small>
        </div>
        <div class="stat-card">
          <h4><i class="fas fa-chart-line"></i> Average Salary</h4>
          <p>₱<?= number_format($avg_salary, 2) ?></p>
          <small>Per employee average</small>
        </div>
      </div>

      <!-- Charts -->
      <div class="charts" style="margin-top:18px;">
        <div class="chart-box">
          <h4><i class="fas fa-chart-bar"></i> Monthly Payroll</h4>
          <div style="height:180px; position:relative;">
            <canvas id="barChart"></canvas>
          </div>
        </div>
        <div class="chart-box">
          <h4><i class="fas fa-chart-pie"></i> Salary vs Deductions</h4>
          <div style="height:180px; position:relative;">
            <canvas id="pieChart"></canvas>
          </div>
        </div>
        <div class="chart-box">
          <h4><i class="fas fa-users-cog"></i> Department Distribution</h4>
          <div style="height:180px; position:relative;">
            <canvas id="deptChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Tables -->
      <div class="table-wrap" style="margin-top:18px;">
        <div class="table-container" style="flex: 1;">
          <div class="card-header bg-primary text-white">
            <h3>
              <i class="fas fa-user-group"></i> Recent Employees
            
            </h3>
          </div>
          <div class="table-responsive" style="height: 300px; overflow-y: auto;">
            <table class="table">
              <thead>
                <tr>
                  <th><i class="fas fa-user"></i> Name</th>
                  <th><i class="fas fa-briefcase"></i> Position</th>
                  <th><i class="fas fa-money-bill"></i> Salary</th>
                </tr>
              </thead>
              <tbody>
                <?php
                // Get recent employees from employees table with proper joins
                $r = $conn->query("
                  SELECT 
                    e.id as emp_id,
                    e.emp_name, 
                    d.name as department,
                    p.name as position, 
                    p.salary, 
                    e.date_hired 
                  FROM employees e
                  LEFT JOIN departments d ON e.department = d.id
                  LEFT JOIN positions p ON e.position = p.id 
                  ORDER BY e.id DESC 
                  LIMIT 5
                ");
                if($r->num_rows > 0){
                  while($rw = $r->fetch_assoc()){
                    echo "<tr>
                            <td>
                              <span class='emp-id'>#" . sprintf('%03d', $rw['emp_id']) . "</span>
                              ".htmlspecialchars($rw['emp_name'])."
                              <small class='text-muted d-block'>Added: ".date('M d, Y', strtotime($rw['date_hired']))."</small>
                            </td>
                            <td>
                              ".htmlspecialchars($rw['position'])."
                              <small class='text-muted d-block'>".htmlspecialchars($rw['department'])."</small>
                            </td>
                            <td>₱".number_format($rw['salary'],2)."</td>
                          </tr>";
                  }
                } else {
                  echo "<tr><td colspan='3' class='text-center'>No recently added employees</td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="table-container" style="flex: 1;">
          <div class="card-header bg-primary text-white">
            <h3>
              <i class="fas fa-money-check-alt"></i> Recent Payrolls
             
            </h3>
          </div>
          <div class="table-responsive" style="height: 300px; overflow-y: auto;">
            <table class="table">
              <thead>
                <tr>
                  <th><i class="fas fa-calendar"></i> Date</th>
                  <th><i class="fas fa-user"></i> Employee</th>
                  <th><i class="fas fa-money-bill-wave"></i> Net Pay</th>
                </tr>
              </thead>
              <tbody>
                <?php
                // Get records from payrolls table with comprehensive details
                $r = $conn->query("
                  SELECT 
                    p.id,
                    p.pay_date,
                    e.emp_name,
                    e.id as emp_id,
                    p.net_pay,
                    d.name as department
                  FROM payrolls p 
                  JOIN employees e ON p.emp_id = e.id 
                  LEFT JOIN departments d ON e.department = d.id
                  ORDER BY p.id DESC, p.pay_date DESC
                  LIMIT 5
                ");
                if($r && $r->num_rows > 0){
                  while($rw = $r->fetch_assoc()){
                    echo "<tr>
                            <td>".date('M d, Y', strtotime($rw['pay_date']))."
                                <small class='text-muted d-block'>ID: #" . sprintf('%05d', $rw['id']) . "</small>
                            </td>
                            <td>
                                <span class='emp-id'>#" . sprintf('%03d', $rw['emp_id']) . "</span>
                                ".htmlspecialchars($rw['emp_name'])."
                                <small class='text-muted d-block'>".htmlspecialchars($rw['department'])."</small>
                            </td>
                            <td>₱".number_format($rw['net_pay'],2)."</td>
                          </tr>";
                  }
                } else {
                  echo "<tr><td colspan='3' class='text-center'>No recent payroll records available</td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="app-footer" style="margin-top:24px">
        © <?= date('Y') ?> SDSC Payroll Management System. All rights reserved.
      </div>
    </div>
  </section>
</div>

<script>
const months = <?= json_encode($months) ?>;
const totals = <?= json_encode($totals) ?>;
// Chart configuration
const chartConfig = {
    responsive: true,
    maintainAspectRatio: true,
    animation: {
        duration: 800,
        easing: 'easeInOutQuart'
    },
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                padding: 10,
                usePointStyle: true,
                boxWidth: 6,
                font: {
                    size: 11
                }
            }
        },
        tooltip: {
            backgroundColor: 'rgba(0,0,0,0.8)',
            padding: 8,
            titleFont: {
                size: 12
            },
            bodyFont: {
                size: 11
            },
            displayColors: true,
            boxWidth: 3
        }
    }
};

// Bar Chart
const barCtx = document.getElementById('barChart').getContext('2d');
new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: months,
        datasets: [{
            label: 'Net Pay (₱)',
            data: totals,
            backgroundColor: 'rgba(46,125,50,0.9)',
            borderColor: 'rgba(46,125,50,1)',
            borderWidth: 1,
            borderRadius: 5,
            hoverBackgroundColor: 'rgba(46,125,50,1)'
        }]
    },
    options: {
        ...chartConfig,
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    display: true,
                    drawBorder: false,
                    color: 'rgba(0,0,0,0.05)'
                },
                ticks: {
                    callback: value => '₱' + value.toLocaleString(),
                    font: {
                        size: 10
                    },
                    maxTicksLimit: 5
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    font: {
                        size: 10
                    }
                }
            }
        }
    }
});

// Pie Chart
const pieCtx = document.getElementById('pieChart').getContext('2d');
new Chart(pieCtx, {
    type: 'pie',
    data: {
        labels: ['Total Salary', 'Deductions'],
        datasets: [{
            data: [<?= $total_salary ?>, <?= $total_deductions ?>],
            backgroundColor: ['rgba(76,175,80,0.9)', 'rgba(244,67,54,0.9)'],
            borderColor: ['#4caf50', '#f44336'],
            borderWidth: 2,
            hoverOffset: 4
        }]
    },
    options: {
        ...chartConfig,
        cutout: '0%'
    }
});

// Department Chart
const deptCtx = document.getElementById('deptChart').getContext('2d');
new Chart(deptCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($deptLabels) ?>,
        datasets: [{
            data: <?= json_encode($deptTotals) ?>,
            backgroundColor: [
                'rgba(66,165,245,0.9)',
                'rgba(102,187,106,0.9)',
                'rgba(255,167,38,0.9)',
                'rgba(171,71,188,0.9)',
                'rgba(41,182,246,0.9)',
                'rgba(236,64,122,0.9)'
            ],
            borderColor: [
                '#42a5f5',
                '#66bb6a',
                '#ffa726',
                '#ab47bc',
                '#29b6f6',
                '#ec407a'
            ],
            borderWidth: 2,
            hoverOffset: 4
        }]
    },
    options: {
        ...chartConfig,
        cutout: '60%'
    }
});

// Add refresh button functionality
document.body.insertAdjacentHTML('beforeend', `
    <div class="refresh-btn" onclick="refreshDashboard()">
        <i class="fas fa-sync-alt"></i>
    </div>
`);

function refreshDashboard() {
    const btn = document.querySelector('.refresh-btn');
    btn.classList.add('loading');
    
    // Simulate refresh - in production, this would be an AJAX call
    setTimeout(() => {
        location.reload();
    }, 1000);
}

// Add hover animations to stat cards
document.querySelectorAll('.stat-card').forEach(card => {
    card.addEventListener('mouseenter', () => {
        card.style.transform = 'translateY(-5px)';
    });
    card.addEventListener('mouseleave', () => {
        card.style.transform = 'translateY(0)';
    });
});
</script>
</body>
</html>
