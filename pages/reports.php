<?php
include '../config/db.php';
session_start();

// Security check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get filter values
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$department = isset($_GET['department']) ? $_GET['department'] : '';

// Get departments and their data
$departments_query = "SELECT d.id, d.name as department,
                      COUNT(DISTINCT e.id) as emp_count,
                      SUM(p.net_pay) as dept_total
               FROM departments d
               LEFT JOIN employees e ON d.id = e.department
               LEFT JOIN payrolls p ON e.id = p.emp_id 
                    AND MONTH(p.pay_date) = ?
                    AND YEAR(p.pay_date) = ?
               GROUP BY d.id ORDER BY d.name";

$stmt = $conn->prepare($departments_query);
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$departments = $stmt->get_result();

// Store department data for charts
$deptLabels = [];
$deptCounts = [];
$deptTotals = [];

while ($d = $departments->fetch_assoc()) {
    $deptLabels[] = $d['department'];
    $deptCounts[] = $d['emp_count'];
    $deptTotals[] = $d['dept_total'] ?? 0;
}

// Calculate totals
$totals_sql = "SELECT 
    COUNT(DISTINCT e.id) as total_employees,
    SUM(p.net_pay) as total_payroll,
    AVG(p.net_pay) as avg_salary,
    SUM(p.gross_pay) as total_gross,
    SUM(p.deductions) as total_deductions,
    SUM(p.overtime_hours) as total_overtime
FROM employees e
JOIN payrolls p ON e.id = p.emp_id
JOIN departments d ON e.department = d.id
WHERE MONTH(p.pay_date) = ? AND YEAR(p.pay_date) = ?
" . ($department ? "AND d.id = ?" : "");

$totals_stmt = $conn->prepare($totals_sql);
if ($department) {
    $totals_stmt->bind_param("iis", $month, $year, $department);
} else {
    $totals_stmt->bind_param("ii", $month, $year);
}
$totals_stmt->execute();
$totals = $totals_stmt->get_result()->fetch_assoc();

// Get weekly payroll data
$weekly_sql = "SELECT 
    WEEK(p.pay_date) as week_number,
    MIN(DATE_FORMAT(p.pay_date, '%b %d')) as week_start,
    SUM(p.net_pay) as total_pay,
    COUNT(DISTINCT p.emp_id) as employee_count
FROM payrolls p
JOIN employees e ON p.emp_id = e.id
JOIN departments d ON e.department = d.id
WHERE MONTH(p.pay_date) = ? AND YEAR(p.pay_date) = ?
" . ($department ? "AND d.id = ?" : "") . "
GROUP BY WEEK(p.pay_date)
ORDER BY week_number";

$weekly_stmt = $conn->prepare($weekly_sql);
if ($department) {
    $weekly_stmt->bind_param("iis", $month, $year, $department);
} else {
    $weekly_stmt->bind_param("ii", $month, $year);
}
$weekly_stmt->execute();
$weekly_data = $weekly_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Process weekly data for the chart
$weeks_labels = array();
$weeks_data = array();
$weeks_employee_count = array();

foreach ($weekly_data as $week) {
    $weeks_labels[] = "Week " . date('W', strtotime($week['week_start'])) . "\n" . $week['week_start'];
    $weeks_data[] = $week['total_pay'];
    $weeks_employee_count[] = $week['employee_count'];
}

// If no data, initialize with zeros
if (empty($weeks_data)) {
    $weeks_labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5'];
    $weeks_data = array_fill(0, 5, 0);
    $weeks_employee_count = array_fill(0, 5, 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports | Payroll System</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="css/reports.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-area">
        <?php include '../includes/header.php'; ?>

        <section class="content">
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>
                        <i class="fas fa-users"></i> 
                        Total Employees
                    </h4>
                    <p><?= number_format($totals['total_employees']) ?></p>
                    <small>For this period</small>
                </div>
                <div class="stat-card">
                    <h4>
                        <i class="fas fa-money-bill-wave"></i> 
                        Total Payroll
                    </h4>
                    <p>₱<?= number_format($totals['total_payroll'], 2) ?></p>
                    <small>Total disbursement</small>
                </div>
                <div class="stat-card">
                    <h4>
                        <i class="fas fa-chart-line"></i> 
                        Average Salary
                    </h4>
                    <p>₱<?= number_format($totals['avg_salary'], 2) ?></p>
                    <small>Per employee</small>
                </div>
            </div>

            <!-- Main Content Card -->
            <div class="content-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="background: #e8f5e9; width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-file-invoice-dollar" style="font-size: 1.2rem; color: #2e7d32;"></i>
                        </div>
                        <h2 style="margin: 0; color: #1b5e20;">Payroll Reports</h2>
                    </div>

                    <div class="export-buttons" style="display: flex; gap: 0.5rem;">
                        <button onclick="exportReport('excel')" class="export-button excel">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                        <button onclick="exportReport('pdf')" class="export-button pdf">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters-container">
                    <div class="filter-group">
                        <label><i class="fas fa-calendar"></i> Month</label>
                        <select name="month" onchange="applyFilters()">
                            <?php for($i=1; $i<=12; $i++): ?>
                                <option value="<?=$i?>" <?=$month==$i?'selected':''?>><?=date('F',mktime(0,0,0,$i,1))?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-alt"></i> Year</label>
                        <select name="year" onchange="applyFilters()">
                            <?php for($y=2020; $y<=date('Y'); $y++): ?>
                                <option value="<?=$y?>" <?=$year==$y?'selected':''?>><?=$y?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-building"></i> Department</label>
                        <select name="department" onchange="applyFilters()">
                            <option value="">All Departments</option>
                            <?php 
                            $departments->data_seek(0);
                            while($d = $departments->fetch_assoc()): ?>
                                <option value="<?=$d['id']?>" <?=$department==$d['id']?'selected':''?>>
                                    <?=htmlspecialchars($d['department'])?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- Payroll Table -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-id-card"></i> Employee ID</th>
                                <th><i class="fas fa-user"></i> Name</th>
                                <th><i class="fas fa-building"></i> Department</th>
                                <th><i class="fas fa-briefcase"></i> Position</th>
                                <th><i class="fas fa-money-bill"></i> Monthly Base</th>
                                <th><i class="fas fa-clock"></i> Work Hours</th>
                                <th><i class="fas fa-business-time"></i> OT Hours</th>
                                <th><i class="fas fa-minus-circle"></i> Deductions</th>
                                <th><i class="fas fa-money-bill-wave"></i> Net Pay</th>
                                <th><i class="fas fa-calendar"></i> Pay Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        // Get current date for comparison
                        $currentDate = date('Y-m-d');
                        
                        $sql = "SELECT 
                                p.id as payroll_id,
                                p.emp_id,
                                e.emp_name,
                                d.name as department,
                                pos.name as position,
                                p.basic_pay as monthly_salary,
                                p.hours_worked,
                                p.overtime_hours,
                                p.absent_hours,
                                p.late_minutes,
                                p.gross_pay,
                                p.deductions,
                                p.net_pay,
                                p.pay_date,
                                p.pay_date = ? as is_today
                            FROM payrolls p
                            JOIN employees e ON p.emp_id = e.id
                            JOIN departments d ON e.department = d.id
                            JOIN positions pos ON e.position = pos.id
                            WHERE MONTH(p.pay_date) = ? AND YEAR(p.pay_date) = ?
                            " . ($department ? "AND d.id = ?" : "") . "
                            ORDER BY p.pay_date DESC, p.id DESC";

                        $stmt = $conn->prepare($sql);
                        
                        if ($department) {
                            $stmt->bind_param("siis", $currentDate, $month, $year, $department);
                        } else {
                            $stmt->bind_param("sii", $currentDate, $month, $year);
                        }
                        
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $totalHours = $row['hours_worked'] + $row['overtime_hours'];
                                
                                $attendanceInfo = sprintf(
                                    "Regular: %s hrs\nOvertime: %s hrs\nAbsent: %s hrs\nLate: %s mins",
                                    number_format($row['hours_worked'], 2),
                                    number_format($row['overtime_hours'], 2),
                                    number_format($row['absent_hours'], 2),
                                    number_format($row['late_minutes'], 2)
                                );
                                
                                echo "<tr" . ($row['is_today'] ? ' class="highlight-today"' : '') . ">
                                    <td><span class='emp-id'>#" . sprintf('%03d', $row['emp_id']) . "</span></td>
                                    <td>" . htmlspecialchars($row['emp_name']) . "</td>
                                    <td>" . htmlspecialchars($row['department']) . "</td>
                                    <td>" . htmlspecialchars($row['position']) . "</td>
                                    <td class='money'>₱" . number_format($row['monthly_salary'], 2) . "</td>
                                    <td title='" . htmlspecialchars($attendanceInfo) . "'>" . number_format($totalHours, 2) . "</td>
                                    <td>" . number_format($row['overtime_hours'], 2) . "</td>
                                    <td class='money'>₱" . number_format($row['deductions'], 2) . "</td>
                                    <td class='money'>₱" . number_format($row['net_pay'], 2) . "</td>
                                    <td>" . date('M d, Y', strtotime($row['pay_date'])) . "</td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='10' class='no-records'><i class='fas fa-info-circle'></i> No payroll records found</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Charts -->
            <div class="charts">
                <div class="chart-box">
                    <h4><i class="fas fa-chart-bar"></i> Monthly Payroll</h4>
                    <div style="height:180px;">
                        <canvas id="payrollChart"></canvas>
                    </div>
                </div>
                <div class="chart-box">
                    <h4><i class="fas fa-chart-pie"></i> Department Distribution</h4>
                    <div style="height:180px;">
                        <canvas id="deptDistChart"></canvas>
                    </div>
                </div>
                <div class="chart-box">
                    <h4><i class="fas fa-chart-line"></i> Salary Trends</h4>
                    <div style="height:180px;">
                        <canvas id="salaryTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        // Chart configuration
        const chartConfig = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 10,
                        usePointStyle: true,
                        font: { size: 11 }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    padding: 8,
                    titleFont: { size: 12 },
                    bodyFont: { size: 11 }
                }
            }
        };

        // Monthly Payroll Chart
        new Chart(document.getElementById('payrollChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($weeks_labels) ?>,
                datasets: [{
                    label: 'Net Pay',
                    data: <?= json_encode($weeks_data) ?>,
                    backgroundColor: 'rgba(46,125,50,0.8)',
                    borderColor: '#2e7d32',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                ...chartConfig,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => '₱' + value.toLocaleString()
                        }
                    }
                }
            }
        });

        // Department Distribution Chart
        new Chart(document.getElementById('deptDistChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($deptLabels) ?>,
                datasets: [{
                    data: <?= json_encode($deptCounts) ?>,
                    backgroundColor: [
                        'rgba(46,125,50,0.8)',
                        'rgba(66,165,245,0.8)',
                        'rgba(255,167,38,0.8)',
                        'rgba(171,71,188,0.8)'
                    ]
                }]
            },
            options: {
                ...chartConfig,
                cutout: '60%'
            }
        });

        // Salary Trends Chart
        new Chart(document.getElementById('salaryTrendChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: <?= json_encode($weeks_labels) ?>,
                datasets: [{
                    label: 'Weekly Net Pay',
                    data: <?= json_encode($weeks_data) ?>,
                    borderColor: 'rgba(46,125,50,1)',
                    backgroundColor: 'rgba(46,125,50,0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                ...chartConfig,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => '₱' + value.toLocaleString()
                        }
                    }
                }
            }
        });

        // Handle filters
        function applyFilters() {
            const month = document.querySelector('select[name="month"]').value;
            const year = document.querySelector('select[name="year"]').value;
            const department = document.querySelector('select[name="department"]').value;
            window.location.href = `reports.php?month=${month}&year=${year}&department=${department}`;
        }

        // Handle exports
        function exportReport(type) {
            const month = document.querySelector('select[name="month"]').value;
            const year = document.querySelector('select[name="year"]').value;
            const department = document.querySelector('select[name="department"]').value;
            
            const url = type === 'excel' ? 'export_excel.php' : 'export_reports_pdf.php';
            window.location.href = `${url}?type=reports&month=${month}&year=${year}&department=${department}`;
        }
    </script>
</body>
</html>