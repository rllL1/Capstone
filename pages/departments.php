<?php
include '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

// --- Variables ---
$edit_dept = null;
$edit_pos = null;

// --- DEPARTMENT CRUD ---
if (isset($_POST['add_dept'])) {
  $name = trim($_POST['dept_name']);
  if ($name) {
    if($conn->query("INSERT INTO departments (name) VALUES ('$name')")) {
      $_SESSION['success_msg'] = "Department Added";
      header("Location: departments.php");
      exit;
    }
  }
}
if (isset($_POST['update_dept'])) {
  $id = intval($_POST['dept_id']);
  $name = trim($_POST['dept_name']);
  if ($id && $name) {
    if($conn->query("UPDATE departments SET name='$name' WHERE id=$id")) {
      $_SESSION['success_msg'] = "Department Edited";
      header("Location: departments.php");
      exit;
    }
  }
}
if (isset($_GET['edit_dept'])) {
  $edit_id = intval($_GET['edit_dept']);
  $edit_dept = $conn->query("SELECT * FROM departments WHERE id=$edit_id")->fetch_assoc();
}

// --- POSITION CRUD ---
if (isset($_POST['add_pos'])) {
  $dept_id = intval($_POST['department_id']);
  $name = trim($_POST['pos_name']);
  $salary = floatval($_POST['salary']);
  if ($dept_id && $name) {
    if($conn->query("INSERT INTO positions (department_id, name, salary) VALUES ($dept_id, '$name', $salary)")) {
      $_SESSION['success_msg'] = "Position Added";
      header("Location: departments.php");
      exit;
    }
  }
}
if (isset($_POST['update_pos'])) {
  $id = intval($_POST['pos_id']);
  $name = trim($_POST['pos_name']);
  $dept_id = intval($_POST['department_id']);
  $salary = floatval($_POST['salary']);
  if ($id && $dept_id && $name) {
    if($conn->query("UPDATE positions SET name='$name', department_id=$dept_id, salary=$salary WHERE id=$id")) {
      $_SESSION['success_msg'] = "Position Edited";
      header("Location: departments.php");
      exit;
    }
  }
}
if (isset($_GET['edit_pos'])) {
  $edit_id = intval($_GET['edit_pos']);
  $edit_pos = $conn->query("
    SELECT positions.*, departments.name AS dept_name
    FROM positions
    JOIN departments ON positions.department_id = departments.id
    WHERE positions.id=$edit_id
  ")->fetch_assoc();
}

// --- FETCH ---
$departments = $conn->query("SELECT *, COALESCE(status, 'active') as status FROM departments ORDER BY name ASC");
$positions = $conn->query("
  SELECT positions.*, departments.name AS dept_name, COALESCE(positions.status, 'active') as status
  FROM positions
  JOIN departments ON positions.department_id = departments.id
  ORDER BY departments.name, positions.name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Departments & Positions - SDSC Payroll</title>
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

.content-card {
  background: white;
  padding: 1.5rem;
  border-radius: 0.5rem;
  box-shadow: var(--card-shadow);
  margin-bottom: 1rem;
  transition: box-shadow 0.3s ease;
}

.content-card:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  gap: 1rem;
  background: var(--light);
  padding: 1rem;
  border-radius: 0.5rem;
}

.search-box {
  display: flex;
  gap: 10px;
  align-items: center;
}

.search-input {
  padding: 0.625rem 1rem;
  border: 2px solid var(--border);
  border-radius: 0.5rem;
  width: 260px;
  transition: all 0.2s ease-in-out;
  font-size: 0.875rem;
  background: white;
}

.search-input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(30, 143, 74, 0.15);
  outline: none;
}

.search-input:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 0.2rem rgba(30, 143, 74, 0.25);
}

.bulk-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.checkbox-cell {
  width: 30px;
  text-align: center;
}

.tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  background: var(--light);
  padding: 0.5rem;
  border-radius: 0.5rem;
}
.tab {
  background: white;
  color: var(--primary);
  padding: 0.75rem 1.5rem;
  border-radius: 0.375rem;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s ease-in-out;
  border: 1px solid var(--border);
}
.tab:hover {
  background: var(--light);
  transform: translateY(-1px);
}
.tab.active { 
  background: var(--primary);
  color: white;
  border-color: var(--primary);
}

.table-container { display: none; }
.table-container.active { display: block; }

input, select {
  padding: 8px;
  border: 1px solid var(--border);
  border-radius: 6px;
}

button, .btn {
  background: var(--primary);
  color: white;
  border: none;
  border-radius: 0.375rem;
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s ease-in-out;
  text-decoration: none;
  font-weight: 500;
  box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}

button:hover, .btn:hover { 
  background: var(--primary-dark); 
  transform: translateY(-1px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.12);
}

button:active, .btn:active { 
  transform: translateY(0);
  box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}

.btn-danger { 
  background: var(--danger);
}
.btn-danger:hover { 
  background: var(--danger-dark);
}

.btn-info { 
  background: var(--info);
}
.btn-info:hover { 
  background: var(--info-dark);
}

.btn-warning { 
  background: var(--warning); 
  color: #212529;
}
.btn-warning:hover { 
  background: var(--warning-dark);
}

.btn-secondary {
  background: var(--secondary);
}
.btn-secondary:hover {
  background: var(--secondary-dark);
}

a.action {
  text-decoration: none;
  color: white;
  padding: 0.375rem 0.75rem;
  border-radius: 0.375rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s ease-in-out;
  font-size: 0.875rem;
  font-weight: 500;
  box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}

a.edit { 
  background: var(--info);
}
a.edit:hover { 
  background: var(--info-dark);
  transform: translateY(-1px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.12);
}

a.delete { 
  background: var(--danger);
}
a.delete:hover { 
  background: var(--danger-dark);
  transform: translateY(-1px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.12);
}

.table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  margin-top: 10px;
}

.table th, .table td {
  border: 1px solid #ddd;
  padding: 12px;
  text-align: left;
}

.table th {
  background: linear-gradient(135deg, var(--primary), var(--primary-dark));
  color: #ffffff;
  font-weight: 600;
  white-space: nowrap;
  cursor: pointer;
  transition: all 0.2s ease-in-out;
  padding: 0.75rem 1rem;
  text-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
  position: relative;
  overflow: hidden;
}

.table th i {
  color: #ffffff;
  opacity: 0.9;
}

.table th:hover {
  background: linear-gradient(135deg, var(--primary-dark), var(--primary-dark));
  color: #ffffff;
}

.table th:first-child {
  border-top-left-radius: 0.375rem;
}

.table th:last-child {
  border-top-right-radius: 0.375rem;
}

.table th::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(rgba(255, 255, 255, 0.1), transparent);
  pointer-events: none;
}

.table tr:nth-child(even) { background-color: #f8f9fa; }
.table tr:hover { background-color: #f2f2f2; }

.table th:first-child,
.table td:first-child {
  border-left: 1px solid #ddd;
}

.table th:last-child,
.table td:last-child {
  border-right: 1px solid #ddd;
}

.table tr:first-child th {
  border-top: 1px solid #ddd;
}

.table tr:last-child td {
  border-bottom: 1px solid #ddd;
}

.sort-icon {
  margin-left: 5px;
  opacity: 0.9;
  font-size: 0.8em;
  transition: transform 0.2s ease, opacity 0.2s ease;
  vertical-align: middle;
  color: #ffffff;
}

th:hover .sort-icon {
  opacity: 1;
  transform: translateY(-1px);
  color: #ffffff;
}

th.active-sort .sort-icon {
  opacity: 1;
  color: #ffffff;
  text-shadow: 0 0 3px rgba(255, 255, 255, 0.5);
}

.no-data {
  text-align: center;
  color: #777;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 6px;
  margin: 10px 0;
}

.highlight {
  background-color: #fff3cd !important;
}

.toast {
  position: fixed;
  top: 1.25rem;
  right: 1.25rem;
  padding: 1rem 1.5rem;
  border-radius: 0.375rem;
  background: white;
  color: #212529;
  display: none;
  z-index: 1000;
  animation: slideIn 0.3s ease;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  border-left: 4px solid var(--primary);
  font-size: 0.875rem;
  max-width: 350px;
}

.toast.success {
  border-left-color: var(--success);
}

.toast.error {
  border-left-color: var(--danger);
}

.toast.warning {
  border-left-color: var(--warning);
}

/* Edit Popup Styles */
.swal2-popup.edit-popup-width {
  width: 450px !important;
  padding: 2rem;
}

.edit-popup .swal2-title {
  font-size: 1.5rem;
  color: var(--primary);
  margin-bottom: 1.5rem;
}

.edit-popup .swal2-title i {
  margin-right: 0.75rem;
}

.edit-popup-container {
  background: #f8f9fa;
  border-radius: 8px;
  margin: 1rem 0;
}

.edit-popup .swal2-form {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.edit-popup .swal2-input-group {
  margin-bottom: 1.5rem;
  text-align: left;
}

.edit-popup .swal2-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #2c3e50;
  font-size: 0.9rem;
}

.edit-popup .swal2-label i {
  color: var(--primary);
  width: 20px;
}

.edit-popup .swal2-input,
.edit-popup .swal2-select {
  width: 100%;
  padding: 0.625rem 1rem;
  border: 2px solid var(--border);
  border-radius: 0.5rem;
  font-size: 0.95rem;
  transition: all 0.2s ease-in-out;
  margin: 0;
  background-color: #fff;
}

.edit-popup .swal2-input:focus,
.edit-popup .swal2-select:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(30, 143, 74, 0.15);
  outline: none;
}

.edit-popup .salary-input-group {
  position: relative;
}

.edit-popup .salary-input-group .currency-symbol {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #666;
  font-weight: 500;
}

.edit-popup .salary-input-group .swal2-input {
  padding-left: 2rem;
}

.edit-popup .swal2-actions {
  gap: 0.75rem;
  margin-top: 2rem;
}

.edit-popup .swal2-confirm,
.edit-popup .swal2-cancel {
  padding: 0.625rem 1.25rem;
  font-weight: 500;
  letter-spacing: 0.3px;
  border-radius: 0.5rem;
  transition: all 0.2s ease-in-out;
}

.edit-popup .swal2-confirm:hover,
.edit-popup .swal2-cancel:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.12);
}

/* Animation Classes */
@keyframes fadeInDown {
  from {
    opacity: 0;
    transform: translate3d(0, -20px, 0);
  }
  to {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }
}

@keyframes fadeOutUp {
  from {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }
  to {
    opacity: 0;
    transform: translate3d(0, -20px, 0);
  }
}

.animate__fadeInDown {
  animation: fadeInDown 0.3s ease-out;
}

.animate__fadeOutUp {
  animation: fadeOutUp 0.3s ease-in;
}

/* Status Switch Styles */
.form-check.form-switch {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0;
}

.form-check-input.status-switch {
  width: 52px;
  height: 26px;
  margin: 0;
  position: relative;
  cursor: pointer;
  appearance: none;
  border-radius: 13px;
  background-color: var(--secondary);
  border: none;
  transition: all 0.3s ease;
  outline: none;
}

.form-check-input.status-switch:checked {
  background-color: var(--success);
}

.form-check-input.status-switch:before {
  content: '';
  position: absolute;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background-color: white;
  top: 3px;
  left: 3px;
  transition: transform 0.3s ease;
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.form-check-input.status-switch:checked:before {
  transform: translateX(26px);
}

.status-label {
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

.status-label.active {
  color: var(--success);
}

.status-label.inactive {
  color: var(--secondary);
}

@keyframes slideIn {
  from { 
    transform: translateX(100%); 
    opacity: 0; 
  }
  to { 
    transform: translateX(0); 
    opacity: 1; 
  }
}

@keyframes fadeOut {
  from { 
    transform: translateX(0); 
    opacity: 1; 
  }
  to { 
    transform: translateX(100%); 
    opacity: 0; 
  }
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
          <i class="fas fa-sitemap" style="font-size: 1.2rem; color: #2e7d32;"></i>
        </div>
        <h2 style="margin: 0; color: #1b5e20;">Departments & Positions</h2>
      </div>

      <div class="tabs">
        <div class="tab active" data-target="departments">Departments</div>
        <div class="tab" data-target="positions">Positions</div>
      </div>

      <!-- Departments -->
      <div id="departments" class="table-container active">
        <div class="toolbar">
          <div class="search-box">
            <input type="text" id="deptSearch" class="search-input" placeholder="Search departments...">
          </div>
        </div>

        <?php if ($edit_dept): ?>
        <form method="POST" style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; align-items: center;">
          <input type="hidden" name="dept_id" value="<?= $edit_dept['id'] ?>">
          <input type="text" name="dept_name" value="<?= htmlspecialchars($edit_dept['name']) ?>" required
                 class="search-input" style="width:auto;" placeholder="Department name">
          <button type="submit" name="update_dept" class="btn">
            <i class="fas fa-save"></i> Update
          </button>
          <a href="departments.php" class="btn" style="background:#6c757d;">
            <i class="fas fa-times"></i> Cancel
          </a>
        </form>
        <?php else: ?>
        <form method="POST" style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; align-items: center;">
          <input type="text" name="dept_name" placeholder="Enter department name" required
                 class="search-input" style="width:auto;">
          <button type="submit" name="add_dept" class="btn">
            <i class="fas fa-plus"></i> Add Department
          </button>
        </form>
        <?php endif; ?>

        <table class="table" id="departmentsTable">
          <thead>
            <tr>
              <th data-sort="id">ID <i class="fas fa-sort sort-icon"></i></th>
              <th>Department Name</th>
              <th><i class="fas fa-toggle-on"></i> Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($departments->num_rows > 0): ?>
            <?php while ($d = $departments->fetch_assoc()): ?>
              <tr>
                <td><?= $d['id'] ?></td>
                <td><?= htmlspecialchars($d['name']) ?></td>
                <td>
                  <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input status-switch" 
                           id="deptStatus_<?= $d['id'] ?>"
                           data-id="<?= $d['id'] ?>"
                           data-type="department"
                           data-name="<?= htmlspecialchars($d['name']) ?>"
                           <?= $d['status'] === 'active' ? 'checked' : '' ?>>
                    <span class="status-label <?= $d['status'] ?>">
                      <?= ucfirst($d['status']) ?>
                    </span>
                  </div>
                </td>
                <td>
                  <button onclick="editDepartment(<?= $d['id'] ?>, '<?= htmlspecialchars(addslashes($d['name'])) ?>')" 
                          class="action edit" title="Edit Department">
                    <i class="fas fa-edit"></i> Edit
                  </button>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="4" class="no-data">
              <i class="fas fa-folder-open"></i> No departments found.
            </td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Positions -->
      <div id="positions" class="table-container">
        <div class="toolbar">
          <div class="search-box">
            <input type="text" id="posSearch" class="search-input" placeholder="Search positions...">
          </div>
        </div>

        <?php if ($edit_pos): ?>
        <form method="POST" style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; align-items: center;">
          <input type="hidden" name="pos_id" value="<?= $edit_pos['id'] ?>">
          <select name="department_id" required class="search-input" style="width:auto;">
            <option value="">Select Department</option>
            <?php
            $deptList = $conn->query("SELECT * FROM departments ORDER BY name ASC");
            while ($d = $deptList->fetch_assoc()):
              $sel = ($d['id'] == $edit_pos['department_id']) ? 'selected' : '';
              echo "<option value='{$d['id']}' $sel>".htmlspecialchars($d['name'])."</option>";
            endwhile;
            ?>
          </select>
          <input type="text" name="pos_name" value="<?= htmlspecialchars($edit_pos['name']) ?>" 
                 class="search-input" style="width:auto;" placeholder="Position name" required>
          <input type="number" name="salary" value="<?= $edit_pos['salary'] ?>" step="0.01" required
                 class="search-input" style="width:auto;" placeholder="Salary">
          <button type="submit" name="update_pos" class="btn">
            <i class="fas fa-save"></i> Update
          </button>
          <a href="departments.php" class="btn" style="background:#6c757d;">
            <i class="fas fa-times"></i> Cancel
          </a>
        </form>
        <?php else: ?>
        <form method="POST" style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; align-items: center;">
          <select name="department_id" required class="search-input" style="width:auto;">
            <option value="">Select Department</option>
            <?php
            $deptList = $conn->query("SELECT * FROM departments ORDER BY name ASC");
            while ($d = $deptList->fetch_assoc()):
              echo "<option value='{$d['id']}'>".htmlspecialchars($d['name'])."</option>";
            endwhile;
            ?>
          </select>
          <input type="text" name="pos_name" placeholder="Enter position name" required
                 class="search-input" style="width:auto;">
          <input type="number" name="salary" placeholder="Salary (₱)" step="0.01" required
                 class="search-input" style="width:auto;">
          <button type="submit" name="add_pos" class="btn">
            <i class="fas fa-plus"></i> Add Position
          </button>
        </form>
        <?php endif; ?>

        <table class="table" id="positionsTable">
          <thead>
            <tr>
              <th data-sort="id">ID <i class="fas fa-sort sort-icon"></i></th>
              <th>Department</th>
              <th>Position</th>
              <th>Salary</th>
              <th><i class="fas fa-toggle-on"></i> Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($positions->num_rows > 0): ?>
            <?php while ($p = $positions->fetch_assoc()): ?>
              <tr>
                <td><?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['dept_name']) ?></td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td>₱<?= number_format($p['salary'], 2) ?></td>
                <td>
                  <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input status-switch"
                           id="posStatus_<?= $p['id'] ?>"
                           data-id="<?= $p['id'] ?>"
                           data-type="position"
                           data-name="<?= htmlspecialchars($p['name']) ?>"
                           <?= $p['status'] === 'active' ? 'checked' : '' ?>>
                    <span class="status-label <?= $p['status'] ?>">
                      <?= ucfirst($p['status']) ?>
                    </span>
                  </div>
                </td>
                <td>
                  <button onclick="editPosition(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>', 
                          <?= $p['department_id'] ?>, <?= $p['salary'] ?>)" 
                          class="action edit" title="Edit Position">
                    <i class="fas fa-edit"></i> Edit
                  </button>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="6" class="no-data">
              <i class="fas fa-folder-open"></i> No positions found.
            </td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>

<div id="toast" class="toast"></div>

<script>
// Edit Department Function
function editDepartment(id, name) {
  Swal.fire({
    title: '<i class="fas fa-building"></i> Edit Department',
    html: `
      <div class="edit-popup-container">
        <form id="editDeptForm" class="swal2-form">
          <input type="hidden" name="dept_id" value="${id}">
          <input type="hidden" name="update_dept" value="1">
          <div class="swal2-input-group">
            <label for="dept_name" class="swal2-label">
              <i class="fas fa-building"></i> Department Name
            </label>
            <input type="text" id="dept_name" name="dept_name" class="swal2-input" 
                   value="${name}" placeholder="Enter department name" required
                   pattern="[A-Za-z0-9\s\-\&]+" title="Only letters, numbers, spaces, hyphens and ampersands allowed">
          </div>
        </form>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: '<i class="fas fa-save"></i> Save Changes',
    cancelButtonText: '<i class="fas fa-times"></i> Cancel',
    confirmButtonColor: '#1e8f4a',
    cancelButtonColor: '#6c757d',
    focusConfirm: false,
    customClass: {
      container: 'edit-popup',
      title: 'edit-popup-title',
      htmlContainer: 'edit-popup-content',
      confirmButton: 'edit-popup-confirm',
      cancelButton: 'edit-popup-cancel',
      popup: 'edit-popup-width'
    },
    showClass: {
      popup: 'animate__animated animate__fadeInDown'
    },
    hideClass: {
      popup: 'animate__animated animate__fadeOutUp'
    },
    preConfirm: () => {
      const form = document.getElementById('editDeptForm');
      const formData = new FormData(form);
      
      return fetch('departments.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (!response.ok) throw new Error(response.statusText);
        return response.text();
      })
      .catch(error => {
        Swal.showValidationMessage(`Request failed: ${error}`);
      });
    }
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire({
        icon: 'success',
        title: 'Department Updated!',
        text: 'The department has been updated successfully.',
        timer: 2000,
        showConfirmButton: false
      }).then(() => {
        window.location.reload();
      });
    }
  });
}

// Edit Position Function
function editPosition(id, name, deptId, salary) {
  // First fetch departments for the select dropdown
  fetch('get_departments.php')
    .then(response => response.json())
    .then(departments => {
      let deptOptions = departments.map(dept => 
        `<option value="${dept.id}" ${dept.id == deptId ? 'selected' : ''}>${dept.name}</option>`
      ).join('');

      Swal.fire({
        title: '<i class="fas fa-briefcase"></i> Edit Position',
        html: `
          <div class="edit-popup-container">
            <form id="editPosForm" class="swal2-form">
              <input type="hidden" name="pos_id" value="${id}">
              <input type="hidden" name="update_pos" value="1">
              <div class="swal2-input-group">
                <label for="department_id" class="swal2-label">
                  <i class="fas fa-building"></i> Department
                </label>
                <select name="department_id" id="department_id" class="swal2-select" required>
                  <option value="">Select Department</option>
                  ${deptOptions}
                </select>
              </div>
              <div class="swal2-input-group">
                <label for="pos_name" class="swal2-label">
                  <i class="fas fa-briefcase"></i> Position Name
                </label>
                <input type="text" id="pos_name" name="pos_name" class="swal2-input" 
                       value="${name}" placeholder="Enter position name" required
                       pattern="[A-Za-z0-9\s\-\&]+" title="Only letters, numbers, spaces, hyphens and ampersands allowed">
              </div>
              <div class="swal2-input-group">
                <label for="salary" class="swal2-label">
                  <i class="fas fa-money-bill-wave"></i> Salary (₱)
                </label>
                <div class="salary-input-group">
                  <span class="currency-symbol">₱</span>
                  <input type="number" id="salary" name="salary" class="swal2-input" 
                         value="${salary}" step="0.01" min="0" required>
                </div>
              </div>
            </form>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-save"></i> Save Changes',
        cancelButtonText: '<i class="fas fa-times"></i> Cancel',
        confirmButtonColor: '#1e8f4a',
        cancelButtonColor: '#6c757d',
        focusConfirm: false,
        width: '500px',
        customClass: {
          container: 'edit-popup',
          title: 'edit-popup-title',
          htmlContainer: 'edit-popup-content',
          confirmButton: 'edit-popup-confirm',
          cancelButton: 'edit-popup-cancel'
        },
        showClass: {
          popup: 'animate__animated animate__fadeInDown'
        },
        hideClass: {
          popup: 'animate__animated animate__fadeOutUp'
        },
        preConfirm: () => {
          const form = document.getElementById('editPosForm');
          const formData = new FormData(form);
          
          return fetch('departments.php', {
            method: 'POST',
            body: formData
          })
          .then(response => {
            if (!response.ok) throw new Error(response.statusText);
            return response.text();
          })
          .catch(error => {
            Swal.showValidationMessage(`Request failed: ${error}`);
          });
        }
      }).then((result) => {
        if (result.isConfirmed) {
          Swal.fire({
            icon: 'success',
            title: 'Position Updated!',
            text: 'The position has been updated successfully.',
            timer: 2000,
            showConfirmButton: false
          }).then(() => {
            window.location.reload();
          });
        }
      });
    });
}

// Success notifications
<?php if (isset($_SESSION['success_msg'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        title: 'Success!',
        text: '<?= $_SESSION['success_msg'] ?>',
        icon: 'success',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
});
<?php unset($_SESSION['success_msg']); endif; ?>

// Tab switching
document.querySelectorAll('.tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.table-container').forEach(c => c.classList.remove('active'));
    tab.classList.add('active');
    document.getElementById(tab.dataset.target).classList.add('active');
  });
});

// Toast notification
function showToast(message, type = 'success', duration = 3000) {
  const toast = document.getElementById('toast');
  toast.textContent = message;
  toast.className = 'toast ' + type;
  toast.style.display = 'block';
  toast.style.animation = 'slideIn 0.3s ease forwards';
  
  setTimeout(() => {
    toast.style.animation = 'fadeOut 0.3s ease forwards';
    setTimeout(() => {
      toast.style.display = 'none';
    }, 300);
  }, duration);
}

// Department functions
const deptSearch = document.getElementById('deptSearch');
const deptTable = document.getElementById('departmentsTable');
function resetDeptSearch() {
  deptSearch.value = '';
  Array.from(deptTable.getElementsByTagName('tr')).forEach(row => {
    if (row.parentNode.tagName === 'TBODY') {
      row.style.display = '';
    }
  });
}

deptSearch.addEventListener('input', e => {
  const searchText = e.target.value.toLowerCase();
  Array.from(deptTable.getElementsByTagName('tr')).forEach(row => {
    if (row.parentNode.tagName === 'TBODY') {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(searchText) ? '' : 'none';
    }
  });
});



// Position functions
const posSearch = document.getElementById('posSearch');
const posTable = document.getElementById('positionsTable');
function resetPosSearch() {
  posSearch.value = '';
  Array.from(posTable.getElementsByTagName('tr')).forEach(row => {
    if (row.parentNode.tagName === 'TBODY') {
      row.style.display = '';
    }
  });
}

posSearch.addEventListener('input', e => {
  const searchText = e.target.value.toLowerCase();
  Array.from(posTable.getElementsByTagName('tr')).forEach(row => {
    if (row.parentNode.tagName === 'TBODY') {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(searchText) ? '' : 'none';
    }
  });
});



// Sorting functionality
function sortTable(table, column, asc = true) {
  const dirModifier = asc ? 1 : -1;
  const tBody = table.tBodies[0];
  const rows = Array.from(tBody.querySelectorAll('tr'));

  // Sort rows
  const sortedRows = rows.sort((a, b) => {
    let aColText = a.querySelector(`td:nth-child(${column + 1})`).textContent.trim();
    let bColText = b.querySelector(`td:nth-child(${column + 1})`).textContent.trim();

    // Handle numeric values
    if (!isNaN(aColText) && !isNaN(bColText)) {
      return (parseFloat(aColText) - parseFloat(bColText)) * dirModifier;
    }

    // Handle currency values
    if (aColText.startsWith('₱') && bColText.startsWith('₱')) {
      aColText = parseFloat(aColText.replace('₱', '').replace(',', ''));
      bColText = parseFloat(bColText.replace('₱', '').replace(',', ''));
      return (aColText - bColText) * dirModifier;
    }

    return aColText.localeCompare(bColText) * dirModifier;
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

document.querySelectorAll('th[data-sort]').forEach(headerCell => {
  headerCell.addEventListener('click', () => {
    const table = headerCell.closest('table');
    const columnIndex = Array.from(headerCell.parentElement.children).indexOf(headerCell);
    const currentIsAsc = headerCell.classList.contains('active-sort');
    sortTable(table, columnIndex, !currentIsAsc);
  });
});
</script>


// Handle status toggle switches
// Handle status toggle switches
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.status-switch').forEach(switchInput => {
    switchInput.addEventListener('change', function() {
      const id = this.dataset.id;
      const type = this.dataset.type;
      const name = this.dataset.name;
      const isActive = this.checked;
      const statusLabel = this.nextElementSibling;
      const newStatus = isActive ? 'active' : 'inactive';
      
      // Show confirmation dialog
      Swal.fire({
      title: 'Change Status?',
      html: `Are you sure you want to set ${type} <strong>${name}</strong> to <strong>${newStatus}</strong>?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: isActive ? '#28a745' : '#6c757d',
      cancelButtonColor: '#dc3545',
      confirmButtonText: isActive ? 'Yes, activate' : 'Yes, deactivate',
      cancelButtonText: 'Cancel',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        fetch('update_status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `id=${id}&type=${type}&status=${isActive}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            // Update status label
            statusLabel.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            statusLabel.className = `status-label ${newStatus}`;
            
            // Show success message
            Swal.fire({
              toast: true,
              position: 'top-end',
              icon: 'success',
              title: data.message,
              showConfirmButton: false,
              timer: 3000,
              timerProgressBar: true
            });
            
            // If deactivating, show additional info
            if (!isActive) {
              Swal.fire({
                title: 'Status Updated',
                html: `
                  <p>The ${type} has been set to inactive.</p>
                  <p>Please note:</p>
                  <ul style="text-align: left; display: inline-block;">
                    <li>New employees cannot be assigned to this ${type}</li>
                    <li>You can reactivate it at any time if needed</li>
                    ${type === 'department' ? '<li>Associated positions will remain unchanged</li>' : ''}
                  </ul>
                `,
                icon: 'info',
                confirmButtonColor: '#17a2b8'
              });
            }
          } else {
            // Revert the switch if there was an error
            this.checked = !isActive;
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message,
              confirmButtonColor: '#dc3545'
            });
          }
        })
        .catch(error => {
          console.error('Error:', error);
          // Revert the switch if there was an error
          this.checked = !isActive;
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to update status',
            confirmButtonColor: '#dc3545'
          });
        });
      } else {
        // If cancelled, revert the switch
        this.checked = !isActive;
      }
    });
  });
});
</script>
<?php include '../includes/footer.php'; ?>
</body>
</html>
