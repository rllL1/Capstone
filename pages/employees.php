<?php
include '../config/db.php';
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$success_msg = "";

// Fetch all departments
$departments = $conn->query("SELECT * FROM departments ORDER BY name ASC");

// Function to get next employee ID
function getNextEmployeeId($conn) {
    $query = "SELECT employee_id FROM employees WHERE employee_id IS NOT NULL ORDER BY CAST(employee_id AS UNSIGNED) DESC LIMIT 1";
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $lastId = intval($row['employee_id']);
        return sprintf('%03d', $lastId + 1);
    }
    return '001'; // Start with 001 if no existing IDs
}

// Add employee
if (isset($_POST['add_employee'])) {
  $name = trim($_POST['emp_name']);
  $position_id = intval($_POST['position']);
  $dept_id = intval($_POST['department']);
  $salary = floatval($_POST['salary']);
  $date_hired = $_POST['date_hired'] ?? date('Y-m-d');
  $employee_id = getNextEmployeeId($conn);

  if ($name && $position_id && $dept_id) {
    // Get position and department info
    $pos_info = $conn->query("SELECT name, salary FROM positions WHERE id = $position_id")->fetch_assoc();
    $dept_info = $conn->query("SELECT name FROM departments WHERE id = $dept_id")->fetch_assoc();
    
    if ($pos_info && $dept_info) {
      $stmt = $conn->prepare("INSERT INTO employees (emp_name, position, department, salary, date_hired, employee_id) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("sssdss", $name, $pos_info['name'], $dept_info['name'], $pos_info['salary'], $date_hired, $employee_id);
      if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Employee added successfully";
        header("Location: employees.php");
        exit;
      }
      $stmt->close();
    }
  }
}

// Update employee
if (isset($_POST['update_employee'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['emp_name']);
    $position_id = intval($_POST['position']);
    $dept_id = intval($_POST['department']);
    $date_hired = $_POST['date_hired'];

    if ($id && $name && $position_id && $dept_id) {
        // Get position and department info with prepared statements
        $pos_stmt = $conn->prepare("SELECT id, name, salary, base_salary FROM positions WHERE id = ?");
        $pos_stmt->bind_param("i", $position_id);
        $pos_stmt->execute();
        $pos_info = $pos_stmt->get_result()->fetch_assoc();
        $pos_stmt->close();

        $dept_stmt = $conn->prepare("SELECT id, name FROM departments WHERE id = ?");
        $dept_stmt->bind_param("i", $dept_id);
        $dept_stmt->execute();
        $dept_info = $dept_stmt->get_result()->fetch_assoc();
        $dept_stmt->close();
    
    if ($pos_info && $dept_info) {
      $employee_id = $_POST['employee_id'] ? str_pad($_POST['employee_id'], 3, '0', STR_PAD_LEFT) : null;
      
      // Check if employee_id is unique (if provided)
      if ($employee_id) {
        $check_id = $conn->prepare("SELECT id FROM employees WHERE employee_id = ? AND id != ?");
        $check_id->bind_param("si", $employee_id, $id);
        $check_id->execute();
        if ($check_id->get_result()->num_rows > 0) {
          $_SESSION['error_msg'] = "Employee ID already exists. Please choose a different one.";
          header("Location: employees.php?edit=" . $id);
          exit;
        }
      }
      
      $stmt = $conn->prepare("UPDATE employees SET emp_name=?, position=?, department=?, salary=?, date_hired=?, employee_id=? WHERE id=?");
      $stmt->bind_param("sssdssi", $name, $pos_info['name'], $dept_info['name'], $pos_info['salary'], $date_hired, $employee_id, $id);
      if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Employee updated successfully";
        header("Location: employees.php");
        exit;
      }
      $stmt->close();
    }
  }
}

// Archive employee
if (isset($_GET['archive'])) {
  $id = intval($_GET['archive']);
  if ($id) {
    // Check if employee is inactive
    $stmt = $conn->prepare("SELECT status FROM employees WHERE id = ? AND deleted_at IS NULL");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $status_check = $result->fetch_assoc();
    $stmt->close();
    
    if (!$status_check) {
      $_SESSION['error_msg'] = "Employee not found";
      header("Location: employees.php");
      exit;
    }
    
    if ($status_check['status'] === 'inactive') {
      $archive_stmt = $conn->prepare("UPDATE employees SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
      $archive_stmt->bind_param("i", $id);
      if ($archive_stmt->execute() && $archive_stmt->affected_rows > 0) {
        $_SESSION['success_msg'] = "Employee archived successfully";
      } else {
        $_SESSION['error_msg'] = "Failed to archive employee";
      }
      $archive_stmt->close();
    } else {
      $_SESSION['error_msg'] = "Only inactive employees can be archived";
    }
    header("Location: employees.php");
    exit;
  }
}

// Restore archived employees
if (isset($_POST['restore_employees'])) {
  if (isset($_POST['restore']) && is_array($_POST['restore'])) {
    $restored = 0;
    foreach ($_POST['restore'] as $id) {
      $id = intval($id);
      $stmt = $conn->prepare("UPDATE employees SET deleted_at = NULL WHERE id = ?");
      $stmt->bind_param("i", $id);
      if ($stmt->execute()) {
        $restored++;
      }
    }
    if ($restored > 0) {
      $_SESSION['success_msg'] = "$restored employee(s) restored successfully";
    }
    header("Location: employees.php");
    exit;
  }
}

// Edit mode
$edit = null;
if (isset($_GET['edit'])) {
  $id = intval($_GET['edit']);
  if ($id) {
    $stmt = $conn->prepare("
    SELECT 
        e.*,
        d.id as department_id,
        d.name as department_name,
        p.id as position_id,
        p.name as position_name,
        COALESCE(p.salary, p.base_salary) as position_salary
    FROM employees e
    LEFT JOIN departments d ON e.department = d.name
    LEFT JOIN positions p ON e.position = p.name AND p.department_id = d.id
    WHERE e.id = ? AND e.deleted_at IS NULL
");
$stmt->bind_param('i', $id);
$stmt->execute();
$edit = $stmt->get_result()->fetch_assoc();
  }
}

// Search/filter
$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? '');
$where = "WHERE e.deleted_at IS NULL";
if ($search) {
  $s = $conn->real_escape_string($search);
  $where .= " AND e.emp_name LIKE '%$s%'";
}
if ($filter) {
  $f = $conn->real_escape_string($filter);
  $where .= " AND e.department = '$f'";
}

$employees = $conn->query("
    SELECT 
        id,
        emp_name,
        employee_id,
        position as position_name,
        department as department_name,
        salary as position_salary,
        date_hired,
        IFNULL(status, 'active') as status
    FROM employees e
    $where 
    ORDER BY id DESC
");
$total_employees = $conn->query("SELECT COUNT(*) AS total FROM employees WHERE deleted_at IS NULL")->fetch_assoc()['total'];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Employees - SDSC Payroll</title>
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
:root {
  --primary: #1e8f4a;
  --primary-dark: #166c36;
  --secondary: #6c757d;
  --secondary-dark: #545b62;
  --light: #f8f9fa;
  --border: #dee2e6;
  --danger: #dc3545;
  --danger-dark: #c82333;
  --info: #17a2b8;
  --info-dark: #138496;
  --warning: #ffc107;
  --warning-dark: #e0a800;
  --success: #28a745;
  --success-dark: #218838;
  --card-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

body { 
  font-family: "Poppins", sans-serif; 
  background: var(--light); 
  margin: 0; 
  min-height: 100vh;
}

h2 i {
  margin-right: 0.5rem;
  color: var(--primary);
}

.alert { 
  background: #fff; 
  color: var(--success);
  padding: 1rem 1.5rem;
  border-left: 4px solid var(--success);
  border-radius: 0.375rem;
  margin-bottom: 1rem;
  box-shadow: var(--card-shadow);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.content-card { 
  background: #fff;
  border-radius: 0.5rem;
  padding: 1.5rem;
  box-shadow: var(--card-shadow);
  margin-bottom: 1.5rem;
  transition: box-shadow 0.3s ease;
}

.content-card:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.form-grid { 
  display: grid; 
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
  gap: 1rem; 
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-grid input, 
.form-grid select { 
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  border: 1px solid var(--border);
  transition: all 0.2s ease-in-out;
  font-size: 0.875rem;
}

.form-grid input:focus,
.form-grid select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 0.2rem rgba(30, 143, 74, 0.25);
}

.form-grid input:disabled,
.form-grid select:disabled,
.form-grid input[readonly] {
  background-color: var(--light);
  cursor: not-allowed;
}

.form-grid input[readonly] {
  border-color: #ccc;
  color: #666;
  font-weight: bold;
}

.form-group small {
  font-size: 0.8rem;
  color: #666;
  margin-top: 4px;
  display: block;
}

.btn { 
  background: var(--primary);
  color: #fff;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s ease-in-out;
  font-size: 0.875rem;
  font-weight: 500;
  text-decoration: none;
  box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}

.btn:hover { 
  background: var(--primary-dark);
  transform: translateY(-1px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.12);
}

.btn:active {
  transform: translateY(0);
  box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}

.btn.secondary { 
  background: var(--secondary);
}

.btn.secondary:hover { 
  background: var(--secondary-dark);
}

.btn.ghost { 
  background: transparent; 
  border: 1px solid var(--primary); 
  color: var(--primary);
  box-shadow: none;
}

.btn.ghost:hover { 
  background: var(--primary); 
  color: white;
  box-shadow: 0 4px 6px rgba(0,0,0,0.12);
}

.btn.small {
  padding: 0.375rem 0.75rem;
  font-size: 0.813rem;
}

.table { 
  width: 100%; 
  border-collapse: separate;
  border-spacing: 0;
  margin-top: 1rem;
}

.table th { 
  background: linear-gradient(145deg, #2e7d32, #1b5e20);
  color: #ffffff;
  padding: 1rem;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.875rem;
  letter-spacing: 0.5px;
  transition: all 0.2s ease-in-out;
  text-align: left;
  border-bottom: 2px solid rgba(255, 255, 255, 0.1);
}

.table th i {
  margin-right: 8px;
  font-size: 1rem;
  width: 20px;
  height: 20px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.15);
  border-radius: 4px;
  padding: 4px;
  color: #ffffff;
}

.table th:hover i {
  background: rgba(255, 255, 255, 0.2);
  transform: scale(1.1);
}

.table th:first-child {
  border-top-left-radius: 0.375rem;
}

.table th:last-child {
  border-top-right-radius: 0.375rem;
}

.table td { 
  border: 1px solid var(--border);
  padding: 0.75rem 1rem;
  transition: all 0.2s ease;
  font-size: 0.875rem;
}

.table tr:nth-child(even) { 
  background: var(--light);
}

.table tr:hover {
  background-color: rgba(30, 143, 74, 0.05);
}

.table-info { 
  font-size: 0.875rem;
  color: var(--secondary);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem;
  background: var(--light);
  border-radius: 0.375rem;
}

.search-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  background: var(--light);
  padding: 1rem;
  border-radius: 0.375rem;
  margin-bottom: 1rem;
}

.search-form {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  align-items: center;
  width: 100%;
}

.search-input-wrapper {
  position: relative;
  width: 300px;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #6c757d;
  font-size: 0.875rem;
}

.clear-search {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #6c757d;
  font-size: 0.875rem;
  cursor: pointer;
  transition: color 0.2s;
}

.clear-search:hover {
  color: #dc3545;
}

.search-input-wrapper input {
  width: 100%;
  padding: 0.5rem 2.5rem;
  border-radius: 0.375rem;
  border: 1px solid var(--border);
  transition: all 0.2s ease-in-out;
  font-size: 0.875rem;
}

.search-input-wrapper input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 0.2rem rgba(30, 143, 74, 0.25);
}

.form-control {
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  border: 1px solid var(--border);
  font-size: 0.875rem;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 0.2rem rgba(30, 143, 74, 0.25);
}

.action-link {
  padding: 0.375rem 0.75rem;
  border-radius: 0.375rem;
  color: white;
  text-decoration: none;
  transition: all 0.2s ease-in-out;
  font-size: 0.875rem;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.action-link.edit {
  background: var(--info);
}

.action-link.edit:hover {
  background: var(--info-dark);
}

.action-link.delete {
  background: var(--danger);
}

.action-link.delete:hover {
  background: var(--danger-dark);
}

.action-link.disabled {
  background: var(--secondary);
  cursor: not-allowed;
  opacity: 0.6;
  pointer-events: none;
  user-select: none;
  transition: all 0.3s ease;
}

.action-link.disabled:hover {
  background: var(--secondary);
  transform: none;
  box-shadow: none;
}

.actions-cell {
  display: flex;
  gap: 0.5rem;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  color: var(--secondary);
  background: var(--light);
  border-radius: 0.375rem;
  font-size: 0.875rem;
}

.emp-id {
  font-family: monospace;
  background: var(--primary);
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.85rem;
  font-weight: bold;
  letter-spacing: 0.5px;
}

.sort-icon {
  margin-left: 5px;
  opacity: 0.9;
  color: #ffffff;
  transition: all 0.2s ease;
}

th:hover .sort-icon {
  opacity: 1;
  transform: translateY(-1px);
}

th.active-sort .sort-icon {
  opacity: 1;
  text-shadow: 0 0 3px rgba(255, 255, 255, 0.5);
}

th[data-sort] {
  cursor: pointer;
}

th[data-sort]:hover {
  background: var(--primary-dark);
}

/* Switch button styles */
.switch-container {
  display: flex;
  align-items: center;
  gap: 10px;
}

.switch {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 24px;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  transition: .4s;
}

.slider:before {
  position: absolute;
  content: "";
  height: 16px;
  width: 16px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  transition: .4s;
}

input:checked + .slider {
  background-color: var(--success);
}

input:focus + .slider {
  box-shadow: 0 0 1px var(--success);
}

input:checked + .slider:before {
  transform: translateX(26px);
}

.slider.round {
  border-radius: 24px;
}

.slider.round:before {
  border-radius: 50%;
}

.status-text {
  font-size: 0.875rem;
  font-weight: 500;
}

.status-text.active {
  color: var(--success);
}

.status-text.inactive {
  color: var(--secondary);
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
          <i class="fas fa-users" style="font-size: 1.2rem; color: #2e7d32;"></i>
        </div>
        <h2 style="margin: 0; color: #1b5e20;">Employees Management</h2>
    </div>
    
    <h3 style="font-size: 1.2rem; margin-bottom: 1rem;"><i class="fas <?= $edit ? 'fa-user-edit' : 'fa-user-plus' ?>" style="color: var(--primary);"></i> <?= $edit ? 'Edit Employee' : 'Add New Employee' ?></h3>

    <?php if ($success_msg): ?>
      <div class="alert">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($success_msg) ?>
      </div>
    <?php endif; ?>

    <?php 
    // Get next employee ID for new employees
    $nextEmployeeId = !$edit ? getNextEmployeeId($conn) : null;
    ?>
    <form method="POST" class="form-grid" style="margin-top:1rem">
      <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
      
      <div class="form-group">
        <label for="employee_id">Employee ID</label>
        <?php if ($edit): ?>
          <input type="text" id="employee_id" name="employee_id" placeholder="Enter Employee ID (e.g., 001)" 
                 value="<?= htmlspecialchars($edit['employee_id'] ?? '') ?>" 
                 pattern="[0-9]{3}" title="Please enter a 3-digit number (e.g., 001)">
        <?php else: ?>
          <input type="text" id="employee_id" name="employee_id" 
                 value="<?= htmlspecialchars($nextEmployeeId) ?>" 
                 readonly style="background-color: #f5f5f5;">
          <small style="color: #666;">Next available ID will be automatically assigned</small>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="emp_name">Full Name</label>
        <input type="text" id="emp_name" name="emp_name" placeholder="Enter Employee Name" 
               value="<?= htmlspecialchars($edit['emp_name'] ?? '') ?>" required>
      </div>

      <div class="form-group">
        <label for="department">Department</label>
        <select name="department" id="department" required>
          <option value="">Select Department</option>
          <?php
          mysqli_data_seek($departments, 0);
          while ($d = $departments->fetch_assoc()):
            $sel = (isset($edit['department']) && $edit['department'] == $d['name']) ? 'selected' : '';
            echo "<option value='".htmlspecialchars($d['id'])."' $sel>".htmlspecialchars($d['name'])."</option>";
          endwhile;
          ?>
        </select>
      </div>

      <div class="form-group">
        <label for="position">Position</label>
        <select name="position" id="position" required>
          <option value="">Select Position</option>
        </select>
        <small id="position-error" class="error-text" style="display: none; color: #dc3545;"></small>
      </div>

      <div class="form-group">
        <label for="salary">Salary (₱)</label>
        <input type="number" name="salary" id="salary" step="0.01" placeholder="Auto-filled from position" 
               value="<?= htmlspecialchars($edit['salary'] ?? '') ?>" readonly required>
        <small class="input-help">Salary is automatically set based on position</small>
      </div>

      <script>
      // Position loading functionality
      document.addEventListener('DOMContentLoaded', function() {
          const deptSelect = document.getElementById('department');
          const posSelect = document.getElementById('position');
          const salaryInput = document.getElementById('salary');
          const positionError = document.getElementById('position-error');

          async function loadPositions(departmentId, selectedPosId = null) {
              posSelect.disabled = true;
              posSelect.innerHTML = '<option value="">Loading positions...</option>';
              positionError.style.display = 'none';
              salaryInput.value = '';

              if (!departmentId) {
                  posSelect.innerHTML = '<option value="">Select Department First</option>';
                  posSelect.disabled = true;
                  return;
              }

              try {
                  console.log('Fetching positions for department:', departmentId);
                  const response = await fetch(`get_dept_pos_new.php?department_id=${departmentId}`);
                  const data = await response.json();
                  console.log('Response:', data);

                  posSelect.innerHTML = '<option value="">Select Position</option>';

                  if (!data.success) {
                      throw new Error(data.error || 'Failed to load positions');
                  }

                  if (!data.data || data.data.length === 0) {
                      positionError.textContent = `No positions found for ${data.department}`;
                      positionError.style.display = 'block';
                      posSelect.disabled = true;
                      return;
                  }

                  data.data.forEach(position => {
                      const option = document.createElement('option');
                      option.value = position.id;
                      option.textContent = position.name;
                      option.dataset.salary = position.salary;
                      posSelect.appendChild(option);

                      if (selectedPosId && position.id === parseInt(selectedPosId)) {
                          option.selected = true;
                          salaryInput.value = position.salary;
                      }
                  });

                  posSelect.disabled = false;
              } catch (error) {
                  console.error('Error loading positions:', error);
                  positionError.textContent = error.message;
                  positionError.style.display = 'block';
                  posSelect.innerHTML = '<option value="">Error loading positions</option>';
                  posSelect.disabled = true;
              }
          }

          // Handle department change
          deptSelect.addEventListener('change', function() {
              const departmentId = this.value;
              loadPositions(departmentId);
          });

          // Handle position change
          posSelect.addEventListener('change', function() {
              const selectedOption = this.options[this.selectedIndex];
              salaryInput.value = selectedOption?.dataset?.salary || '';
          });

          // Initialize positions if in edit mode
          <?php if ($edit && isset($edit['department_id'])): ?>
          loadPositions(<?= json_encode($edit['department_id']) ?>, <?= json_encode($edit['position_id'] ?? null) ?>);
          <?php endif; ?>
      });
      </script>
            <div class="form-group">
        <label for="date_hired">Date Hired</label>
        <input type="date" id="date_hired" name="date_hired" 
               value="<?= htmlspecialchars($edit['date_hired'] ?? date('Y-m-d')) ?>" required>
      </div>

      <div style="grid-column:1 / -1; display:flex; gap:0.5rem; margin-top:0.5rem;">
        <button class="btn" type="submit" name="<?= $edit ? 'update_employee' : 'add_employee' ?>">
          <i class="fas fa-<?= $edit ? 'save' : 'plus' ?>"></i>
          <?= $edit ? 'Update Employee' : 'Add Employee' ?>
        </button>
        <?php if ($edit): ?>
          <a href="employees.php" class="btn ghost">
            <i class="fas fa-times"></i> Cancel
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="content-card">
    <div class="search-toolbar">
      <div style="display: flex; gap: 1rem; align-items: center; width: 100%;">
        <div class="search-form" style="display: flex; gap: 1rem; align-items: center;">
          <div class="search-input-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchInput" placeholder="Search employee name..." 
                   value="<?= htmlspecialchars($search) ?>" class="form-control" style="width: 250px;">
            <?php if ($search): ?>
              <i class="fas fa-times clear-search" id="clearSearch"></i>
            <?php endif; ?>
          </div>
          <select id="departmentFilter" class="form-control" style="width: 145px;">
            <option value="">All Departments</option>
            <?php
            $departments->data_seek(0);
            while ($d = $departments->fetch_assoc()):
              $selected = ($filter == $d['name']) ? 'selected' : '';
              echo "<option value='".htmlspecialchars($d['name'])."' $selected>".htmlspecialchars($d['name'])."</option>";
            endwhile;
            ?>
          </select>
          <select id="positionFilter" class="form-control" style="width: 145px;">
            <option value="">All Positions</option>
            <?php
            $positions = $conn->query("SELECT DISTINCT position FROM employees WHERE deleted_at IS NULL ORDER BY position");
            while ($p = $positions->fetch_assoc()):
              echo "<option value='".htmlspecialchars($p['position'])."'>".htmlspecialchars($p['position'])."</option>";
            endwhile;
            ?>
          </select>
        </div>
        <div style="display: flex; gap: 1rem; align-items: center; margin-left: auto;">
          <button type="button" class="btn" onclick="showArchives()">
            <i class="fas fa-archive"></i> View Archives
          </button>
          <div class="stats-card" style="margin: 0;">
            <div class="icon-box">
              <i class="fas fa-users"></i>
            </div>
            <div class="stats-info">
              <h4>Total Employees</h4>
              <div class="stats-number"><?= $total_employees ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <style>
    .stats-card {
      background: #ffffff;
      color: #212529;
      padding: 1rem 1.5rem;
      border-radius: 10px;
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      border: 1px solid var(--border);
    }
    
    .stats-card .icon-box {
      background: var(--light);
      width: 48px;
      height: 48px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .stats-card .icon-box i {
      font-size: 1.5rem;
      color: #212529;
    }
    
    .stats-card .stats-info {
      flex: 1;
    }
    
    .stats-card h4 {
      margin: 0;
      font-size: 0.875rem;
      color: #6c757d;
      font-weight: normal;
    }
    
    .stats-card .stats-number {
      font-size: 2rem;
      font-weight: 700;
      line-height: 1.2;
      color: #212529;
    }
    </style>

    <table class="table">
      <thead>
        <tr>
          <th><i class="fas fa-id-card"></i> Employee ID <i class="fas fa-sort sort-icon"></i></th>
          <th><i class="fas fa-user"></i> Name</th>
          <th><i class="fas fa-briefcase"></i> Position</th>
          <th><i class="fas fa-building"></i> Department</th>
          <th><i class="fas fa-money-bill-wave"></i> Salary</th>
          <th><i class="fas fa-calendar-alt"></i> Date Hired</th>
          <th><i class="fas fa-toggle-on"></i> Status</th>
          <th><i class="fas fa-cog"></i> Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($employees->num_rows): while ($row = $employees->fetch_assoc()): ?>
          <tr data-employee-id="<?= $row['id'] ?>">
            <td><span class="emp-id">#<?= htmlspecialchars($row['employee_id']) ?></span></td>
            <td><?= htmlspecialchars($row['emp_name']) ?></td>
            <td><?= htmlspecialchars($row['position_name']) ?></td>
            <td><?= htmlspecialchars($row['department_name']) ?></td>
            <td>₱<?= number_format($row['position_salary'], 2) ?></td>
            <td><?= htmlspecialchars($row['date_hired']) ?></td>
            <td>
              <div class="switch-container">
                <label class="switch">
                  <input type="checkbox" class="status-toggle" 
                         data-id="<?= $row['id'] ?>"
                         <?= $row['status'] === 'active' ? 'checked' : '' ?>>
                  <span class="slider round"></span>
                </label>
                <span class="status-text <?= $row['status'] ?>">
                  <?= ucfirst($row['status']) ?>
                </span>
              </div>
            </td>
            <td class="actions-cell">
              <a href="employees.php?edit=<?= $row['id'] ?>" class="action-link edit" title="Edit">
                <i class="fas fa-edit"></i> Edit
              </a>
              <a href="#" 
                 onclick="return confirmArchive(<?= $row['id'] ?>, '<?= htmlspecialchars($row['emp_name']) ?>', '<?= $row['status'] ?>')"
                 class="action-link delete <?= $row['status'] === 'active' ? 'disabled' : '' ?>" 
                 style="pointer-events: <?= $row['status'] === 'active' ? 'none' : 'auto' ?>;"
                 title="<?= $row['status'] === 'active' ? 'Cannot archive active employee' : 'Archive' ?>">
                <i class="fas fa-archive"></i> Archive
              </a>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr>
            <td colspan="6" class="empty-state">
              <i class="fas fa-folder-open"></i>
              No employees found
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
</div>

<script>
const deptSelect = document.getElementById("department");
const posSelect = document.getElementById("position");
const salaryInput = document.getElementById("salary");

function loadPositions(deptId, selectedPos = '') {
    posSelect.disabled = true;
    posSelect.innerHTML = '<option>Loading positions...</option>';
    
    if (!deptId) {
        posSelect.innerHTML = '<option value="">Select Department First</option>';
        posSelect.disabled = true;
        salaryInput.value = '';
        return;
    }
    
    // Clear salary when changing department
    if (!selectedPos) {
        salaryInput.value = '';
    }
    
    fetch(`get_dept_pos.php?dept_id=${deptId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            posSelect.innerHTML = '<option value="">Select Position</option>';
            
            if (data.length === 0) {
                posSelect.innerHTML = '<option value="">No positions available</option>';
                posSelect.disabled = true;
                return;
            }

      data.forEach(pos => {
        const opt = document.createElement("option");
        opt.value = pos.id;
        opt.textContent = pos.name;
        opt.dataset.salary = pos.salary;
        // Check both ID and name for matching position
        if (pos.id.toString() === selectedPos || pos.name === selectedPos) {
          opt.selected = true;
          salaryInput.value = pos.salary;
        }
        posSelect.appendChild(opt);
      });
      posSelect.disabled = false;
    })
    .catch(error => {
      console.error('Error loading positions:', error);
      posSelect.innerHTML = '<option value="">Error loading positions</option>';
      posSelect.disabled = true;
    });
}

deptSelect.addEventListener("change", function() {
    const deptId = this.value;
    console.log('Department changed to:', deptId);
    
    // Enable position select
    posSelect.disabled = false;
    
    if (deptId) {
        // Show loading state
        posSelect.innerHTML = '<option value="">Loading positions...</option>';
        
        // Fetch positions for selected department
        fetch(`get_dept_pos.php?dept_id=${deptId}`)
            .then(response => response.json())
            .then(response => {
                if (!response.success) {
                    throw new Error(response.error || 'Failed to load positions');
                }
                
                const positions = response.data;
                posSelect.innerHTML = '<option value="">Select Position</option>';
                
                positions.forEach(pos => {
                    const option = document.createElement('option');
                    option.value = pos.id;
                    option.textContent = pos.name;
                    option.dataset.salary = pos.salary;
                    posSelect.appendChild(option);
                });
                
                // Enable position select
                posSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading positions:', error);
                posSelect.innerHTML = '<option value="">Error loading positions</option>';
                posSelect.disabled = true;
                salaryInput.value = '';
            });
    } else {
        posSelect.innerHTML = '<option value="">Select Department First</option>';
        posSelect.disabled = true;
        salaryInput.value = '';
    }
});

posSelect.addEventListener("change", function() {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption && selectedOption.dataset.salary) {
        salaryInput.value = selectedOption.dataset.salary;
    } else {
        salaryInput.value = '';
    }
});

<?php if ($edit): ?>
document.addEventListener('DOMContentLoaded', () => {
    <?php if ($edit): ?>
    // Initialize edit mode
    const editData = {
        departmentId: <?= json_encode($edit['department_id']) ?>,
        positionId: <?= json_encode($edit['position_id']) ?>,
        positionName: <?= json_encode($edit['position_name']) ?>,
        salary: <?= json_encode($edit['position_salary']) ?>
    };
    
    console.log('Edit mode data:', editData);
    
    // Set department
    if (editData.departmentId) {
        deptSelect.value = editData.departmentId;
        
        // Load positions for this department
        fetch(`get_dept_pos.php?dept_id=${editData.departmentId}`)
            .then(response => response.json())
            .then(response => {
                if (!response.success) {
                    throw new Error(response.error || 'Failed to load positions');
                }
                
                const positions = response.data;
                posSelect.innerHTML = '<option value="">Select Position</option>';
                
                positions.forEach(pos => {
                    const option = document.createElement('option');
                    option.value = pos.id;
                    option.textContent = pos.name;
                    option.dataset.salary = pos.salary;
                    
                    // Select the correct position
                    if (editData.positionId && pos.id == editData.positionId) {
                        option.selected = true;
                        salaryInput.value = pos.salary;
                    }
                    posSelect.appendChild(option);
                });
                
                posSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error loading positions:', error);
                posSelect.innerHTML = '<option value="">Error loading positions</option>';
            });
    }
    <?php endif; ?>
});
<?php endif; ?>

// Delete confirmation using SweetAlert2
function confirmArchive(employeeId, employeeName, status) {
  // No need to check status here since the button is already disabled for active employees
  Swal.fire({
    title: 'Archive Employee?',
    html: `Are you sure you want to archive <strong>${employeeName}</strong>?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, archive',
    cancelButtonText: 'Cancel',
    reverseButtons: true
  }).then((result) => {
    if (result.isConfirmed) {
      const row = document.querySelector(`tr[data-employee-id="${employeeId}"]`);
      if (row) {
        // Add animation
        row.style.transition = 'all 0.3s ease-out';
        row.style.opacity = '0';
        row.style.transform = 'translateX(20px)';
        
        // Perform the archive operation
        fetch(`employees.php?archive=${employeeId}`)
          .then(response => {
            setTimeout(() => {
              row.remove(); // Remove the row from the table
              showArchives(); // Show the archives modal automatically
              Swal.fire({
                icon: 'success',
                title: 'Employee Archived',
                text: `${employeeName} has been archived successfully`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
              });
            }, 300);
          })
          .catch(error => {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Failed to archive employee'
            });
          });
      }
    }
  });
  return false;
}

function showArchives() {
  // Fetch archived employees
  fetch('get_archived_employees.php')
    .then(response => response.json())
    .then(data => {
      const rows = data.map(emp => `
        <tr>
          <td><input type="checkbox" name="restore[]" value="${emp.id}"></td>
          <td><span class="emp-id">#${emp.employee_id}</span></td>
          <td>${emp.emp_name}</td>
          <td>${emp.position}</td>
          <td>${emp.department}</td>
          <td>${emp.archived_date}</td>
        </tr>
      `).join('');

      Swal.fire({
        title: 'Archived Employees',
        html: `
          <form id="restoreForm" method="POST">
            <div style="max-height: 400px; overflow-y: auto;">
              <table class="table">
                <thead>
                  <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Department</th>
                    <th>Archived Date</th>
                  </tr>
                </thead>
                <tbody>
                  ${rows}
                </tbody>
              </table>
            </div>
            <div style="margin-top: 1rem;">
              <button type="submit" class="btn" name="restore_employees" style="margin-right: 1rem;">
                <i class="fas fa-undo"></i> Restore Selected
              </button>
            </div>
          </form>
        `,
        width: '80%',
        showConfirmButton: false,
        showCloseButton: true
      });

      // Handle select all checkbox
      document.getElementById('selectAll')?.addEventListener('change', function() {
        document.querySelectorAll('input[name="restore[]"]')
          .forEach(cb => cb.checked = this.checked);
      });
    })
    .catch(error => {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Failed to load archived employees'
      });
    });
}

// Table sorting functionality
function sortTable(table, column, asc = true) {
  const dirModifier = asc ? 1 : -1;
  const tBody = table.tBodies[0];
  const rows = Array.from(tBody.querySelectorAll('tr'));

  // Sort rows
  const sortedRows = rows.sort((a, b) => {
    let aColText = a.querySelector(`td:nth-child(${column + 1})`).textContent.trim();
    let bColText = b.querySelector(`td:nth-child(${column + 1})`).textContent.trim();

    // Handle employee ID numbers (remove # and leading zeros)
    if (column === 0) {
      aColText = parseInt(aColText.replace('#', ''));
      bColText = parseInt(bColText.replace('#', ''));
    }

    return aColText > bColText ? (1 * dirModifier) : (-1 * dirModifier);
  });

  // Remove existing rows
  while (tBody.firstChild) {
    tBody.removeChild(tBody.firstChild);
  }

  // Add sorted rows
  tBody.append(...sortedRows);

  // Update sort icons
  table.querySelectorAll('th').forEach(th => th.classList.remove('active-sort'));
  table.querySelector(`th:nth-child(${column + 1})`).classList.add('active-sort');
}

// Add click event listeners to sortable headers
document.querySelectorAll('th[data-sort]').forEach(headerCell => {
  headerCell.addEventListener('click', () => {
    const table = headerCell.closest('table');
    const columnIndex = Array.from(headerCell.parentElement.children).indexOf(headerCell);
    const currentIsAsc = headerCell.classList.contains('active-sort');
    sortTable(table, columnIndex, !currentIsAsc);
  });
});

// Real-time search and filtering
const searchInput = document.getElementById('searchInput');
const departmentFilter = document.getElementById('departmentFilter');
const positionFilter = document.getElementById('positionFilter');
const clearSearch = document.getElementById('clearSearch');
const tbody = document.querySelector('.table tbody');
const allRows = Array.from(tbody.querySelectorAll('tr'));

function filterTable() {
  const searchTerm = searchInput.value.toLowerCase();
  const department = departmentFilter.value.toLowerCase();
  const position = positionFilter.value.toLowerCase();

  allRows.forEach(row => {
    const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
    const deptText = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
    const posText = row.querySelector('td:nth-child(3)').textContent.toLowerCase();

    const matchesSearch = name.includes(searchTerm);
    const matchesDepartment = !department || deptText === department;
    const matchesPosition = !position || posText === position;

    row.style.display = (matchesSearch && matchesDepartment && matchesPosition) ? '' : 'none';
  });

  // Show/hide no results message
  let visibleRows = allRows.filter(row => row.style.display !== 'none');
  let emptyState = tbody.querySelector('.empty-state');
  
  if (visibleRows.length === 0) {
    if (!emptyState) {
      emptyState = document.createElement('tr');
      emptyState.className = 'empty-state';
      emptyState.innerHTML = `
        <td colspan="7" style="text-align: center; padding: 2rem;">
          <i class="fas fa-search" style="font-size: 2rem; color: #6c757d; margin-bottom: 1rem;"></i>
          <div>No employees found matching your search criteria</div>
        </td>`;
      tbody.appendChild(emptyState);
    }
  } else if (emptyState) {
    emptyState.remove();
  }
}

// Event listeners for real-time filtering
searchInput.addEventListener('input', filterTable);
departmentFilter.addEventListener('change', filterTable);
positionFilter.addEventListener('change', filterTable);

// Clear search functionality
if (clearSearch) {
  clearSearch.addEventListener('click', () => {
    searchInput.value = '';
    filterTable();
  });
}

// Handle status toggle
document.querySelectorAll('.status-toggle').forEach(toggle => {
  toggle.addEventListener('change', function() {
    const employeeId = this.dataset.id;
    const status = this.checked ? 'active' : 'inactive';
    const statusText = this.closest('.switch-container').querySelector('.status-text');

    fetch('update_employee_status.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `id=${employeeId}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        statusText.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        statusText.className = `status-text ${status}`;
        
        // Update archive button state
        const row = this.closest('tr');
        const archiveBtn = row.querySelector('.action-link.delete');
        if (status === 'active') {
          archiveBtn.classList.add('disabled');
          archiveBtn.style.pointerEvents = 'none';
          archiveBtn.title = 'Cannot archive active employee';
        } else {
          archiveBtn.classList.remove('disabled');
          archiveBtn.style.pointerEvents = 'auto';
          archiveBtn.title = 'Archive';
          
          // Show notification for inactive status with actions
          Swal.fire({
            title: 'Employee Set to Inactive',
            html: `
              <p>The employee status has been set to inactive.</p>
              <p>You can now:</p>
              <ul style="text-align: left; display: inline-block;">
                <li>Archive the employee record</li>
                <li>Reactivate the employee if needed</li>
              </ul>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#28a745',
            confirmButtonText: 'Archive Now',
            cancelButtonText: 'Keep Inactive',
            reverseButtons: true
          }).then((result) => {
            if (result.isConfirmed) {
              // Trigger archive process
              confirmArchive(employeeId, row.querySelector('td:nth-child(2)').textContent, 'inactive');
            }
          });
          return;
        }
        
        Swal.fire({
          icon: 'success',
          title: 'Status Updated',
          text: data.message,
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000
        });
      } else {
        this.checked = !this.checked;
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.message
        });
      }
    })
    .catch(error => {
      this.checked = !this.checked;
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Failed to update status'
      });
    });
  });
});
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
<?php // End of file ?>