<?php
$current = basename($_SERVER['PHP_SELF']);

// Function to get active menu item count for badges
function getMenuCounts($conn) {
    $counts = ['employees' => 0, 'departments' => 0, 'payroll' => 0];
    
    try {
        // Get employees count
        $result = $conn->query("SELECT COUNT(*) as count FROM employees");
        if ($result) {
            $counts['employees'] = $result->fetch_assoc()['count'];
        }
        
        // Get departments count
        $result = $conn->query("SELECT COUNT(*) as count FROM departments");
        if ($result) {
            $counts['departments'] = $result->fetch_assoc()['count'];
        }
        
        // Get current month's payroll count
        $result = $conn->query("SELECT COUNT(*) as count FROM payrolls WHERE MONTH(pay_date) = MONTH(CURRENT_DATE())");
        if ($result) {
            $counts['payroll'] = $result->fetch_assoc()['count'];
        }
    } catch (Exception $e) {
        error_log("Error in getMenuCounts: " . $e->getMessage());
    }
    
    return $counts;
}

$menuCounts = getMenuCounts($conn);
?>

<style>
:root {
  --sidebar-width: 280px;
  --sidebar-collapsed-width: 80px;
  --transition-speed: 0.3s;
  --sidebar-bg: #ffffff;
  --sidebar-hover: #f8f9fa;
  --sidebar-border: #e9ecef;
}

.sidebar {
  width: var(--sidebar-width);
  background: var(--sidebar-bg);
  height: 100vh;
  position: fixed;
  left: 0;
  top: 0;
  box-shadow: 2px 0 8px rgba(0,0,0,0.1);
  transition: all var(--transition-speed) ease;
  z-index: 1000;
  font-family: 'Poppins', sans-serif;
  display: flex;
  flex-direction: column;
  border-right: 1px solid var(--sidebar-border);
  overflow: hidden;
}

.brand {
  padding: 2rem 1.5rem;
  text-align: center;
  border-bottom: 1px solid var(--sidebar-border);
  background: linear-gradient(to bottom, var(--primary-lighter), var(--sidebar-bg));
  margin-bottom: 1rem;
}

.logo-container {
  width: 90px;
  height: 90px;
  margin: 0 auto 1.25rem;
  padding: 0.5rem;
  background: var(--sidebar-bg);
  border-radius: 50%;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  border: 2px solid var(--primary-light);
  transition: transform var(--transition-speed) ease;
}

.logo-container:hover {
  transform: scale(1.05);
}

.logo {
  width: 100%;
  height: 100%;
  object-fit: contain;
  border-radius: 50%;
}

.brand .title {
  text-align: center;
  color: var(--primary);
  padding: 0 1rem;
}

.school-name {
  display: block;
  font-size: 1.25rem;
  font-weight: 700;
  line-height: 1.4;
  text-shadow: 1px 1px 0 rgba(255,255,255,0.8);
  transition: transform var(--transition-speed) ease;
}

.school-name:first-child {
  color: var(--primary-dark);
}

.school-name:last-child {
  font-size: 1.1rem;
  color: var(--primary);
}
</style>

<aside class="sidebar" id="appSidebar">
  <div class="brand">
    <div class="logo-container">
      <img src="../images/Logo.png" alt="SDSC Logo" class="logo">
    </div>
    <div class="title">
      <span class="school-name">St. Dominic</span>
      <span class="school-name">Savio College</span>
    </div>
  </div>

  <style>
  .nav {
    padding: 0.5rem 1rem;
    overflow-y: auto;
    height: calc(100vh - 280px);
    scrollbar-width: thin;
    scrollbar-color: var(--primary-light) #f0f0f0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .nav::-webkit-scrollbar {
    width: 4px;
  }

  .nav::-webkit-scrollbar-track {
    background: #f0f0f0;
  }

  .nav::-webkit-scrollbar-thumb {
    background-color: var(--primary-light);
    border-radius: 20px;
  }

  .nav-link {
    display: flex;
    align-items: center;
    padding: 0.875rem 1.25rem;
    color: #000000 !important;
    text-decoration: none;
    transition: all var(--transition-speed) ease;
    position: relative;
    gap: 1rem;
    border-radius: 10px;
    margin-bottom: 0.25rem;
    font-weight: 500;
    letter-spacing: 0.3px;
    background: #ffffff;
    width: 100%;
  }

  .nav-link:hover {
    color: #000000 !important;
    background: #dcfce7;
    transform: translateX(5px);
    font-weight: 600;
  }

  .nav-link.active {
    color: #000000 !important;
    background: #dcfce7;
    font-weight: 700;
  }

  .nav-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    height: 60%;
    width: 4px;
    background: var(--primary);
    border-radius: 0 4px 4px 0;
  }

  .nav-link .icon {
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    background: #e2f5e9;
    border-radius: 10px;
    transition: all var(--transition-speed) ease;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    color: #166534;
    border: 1px solid #4ade80;
  }

  .nav-link:hover .icon,
  .nav-link.active .icon {
    background: #166534;
    color: white;
    transform: scale(1.1);
    border-color: #14532d;
  }

  .nav-link .title {
    flex: 1;
    font-size: 1rem;
    font-weight: 500;
  }

  .badge {
    background: #166534;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(22,101,52,0.2);
    transition: all var(--transition-speed) ease;
    min-width: 24px;
  }

  .nav-link:hover .badge {
    transform: scale(1.1) translateX(5px);
    background: #14532d;
    box-shadow: 0 4px 6px rgba(22,101,52,0.3);
  }
  </style>

  <nav class="nav" role="navigation">
    <a href="../pages/dashboard.php" class="nav-link <?= $current=='dashboard.php' ? 'active' : '' ?>">
      <span class="icon"><i class="fas fa-home"></i></span>
      <span class="title">Dashboard</span>
    </a>
    
    <a href="../pages/employees.php" class="nav-link <?= $current=='employees.php' ? 'active' : '' ?>">
      <span class="icon"><i class="fas fa-users"></i></span>
      <span class="title">Employees</span>
      <span class="badge"><?= $menuCounts['employees'] ?></span>
    </a>
    
    <a href="../pages/payroll.php" class="nav-link <?= $current=='payroll.php' ? 'active' : '' ?>">
      <span class="icon"><i class="fas fa-money-bill-wave"></i></span>
      <span class="title">Payroll</span>
      <span class="badge"><?= $menuCounts['payroll'] ?></span>
    </a>
    
    <a href="../pages/departments.php" class="nav-link <?= $current=='departments.php' ? 'active' : '' ?>">
      <span class="icon"><i class="fas fa-building"></i></span>
      <span class="title">Departments</span>
      <span class="badge"><?= $menuCounts['departments'] ?></span>
    </a>

    <a href="../pages/reports.php" class="nav-link <?= $current=='reports.php' ? 'active' : '' ?>">
      <span class="icon"><i class="fas fa-chart-bar"></i></span>
      <span class="title">Reports</span>
    </a>
  </nav>

  <style>
  .sidebar-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, var(--primary-lighter), var(--sidebar-bg));
    border-top: 1px solid var(--sidebar-border);
    padding: 0.75rem;
  }

  .user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    margin-bottom: 1rem;
  }

  .user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: var(--sidebar-bg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 1.25rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    border: 2px solid var(--primary-light);
    transition: all var(--transition-speed) ease;
  }

  .user-info:hover .user-avatar {
    transform: scale(1.1) rotate(5deg);
  }

  .user-details {
    flex: 1;
    min-width: 0;
  }

  .user-name {
    font-weight: 700;
    color: #166534;
    margin-bottom: 0.25rem;
    font-size: 1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    letter-spacing: 0.3px;
  }

  .user-role {
    color: #1a202c;
    font-size: 0.85rem;
    font-weight: 500;
    opacity: 0.9;
    letter-spacing: 0.2px;
  }

  .logout {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    color: var(--danger);
    text-decoration: none;
    padding: 0.75rem;
    border-radius: 10px;
    transition: all var(--transition-speed) ease;
    font-size: 0.9rem;
    font-weight: 500;
    background: var(--sidebar-bg);
    border: 1px solid var(--danger);
  }

  .logout:hover {
    background: var(--danger);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(220,53,69,0.2);
  }

  .sidebar-toggle {
    position: fixed;
    left: 1.5rem;
    top: 1.5rem;
    width: 45px;
    height: 45px;
    border-radius: 12px;
    background: var(--sidebar-bg);
    border: 1px solid var(--primary-light);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    color: var(--primary);
    cursor: pointer;
    display: none;
    z-index: 1001;
    transition: all var(--transition-speed) ease;
  }

  .sidebar-toggle:hover {
    transform: scale(1.1);
    background: var(--primary-lighter);
  }

  .sidebar-toggle i {
    font-size: 1.25rem;
    transition: transform var(--transition-speed) ease;
  }

  .sidebar.collapsed .sidebar-toggle i {
    transform: rotate(180deg);
  }

  @media (max-width: 992px) {
    :root {
      --sidebar-width: 250px;
    }
    
    .nav-link {
      padding: 0.75rem 1rem;
    }
  }

  @media (max-width: 768px) {
    .sidebar {
      transform: translateX(-100%);
      box-shadow: none;
    }

    .sidebar.collapsed {
      transform: translateX(0);
      box-shadow: 4px 0 16px rgba(0,0,0,0.1);
    }

    .sidebar-toggle {
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .nav {
      height: calc(100vh - 320px);
    }
  }
  
  @media (max-width: 576px) {
    :root {
      --sidebar-width: 100%;
    }
    
    .sidebar {
      transform: translateX(-100%);
    }
    
    .brand {
      padding: 1.5rem 1rem;
    }
    
    .nav-link {
      padding: 0.75rem;
    }
  }
  </style>

  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar">
        <i class="fas fa-user"></i>
      </div>
      <div class="user-details">
        <div class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></div>
        <div class="user-role">Administrator</div>
      </div>
    </div>
    
    <a class="logout" href="../pages/logout.php">
      <i class="fas fa-sign-out-alt"></i>
      <span>Logout</span>
    </a>
  </div>
</aside>

<!-- Toggle Button for Mobile -->
<button id="sidebarToggle" class="sidebar-toggle">
  <i class="fas fa-bars"></i>
</button>

<script>
const sidebar = document.getElementById('appSidebar');
const sidebarToggle = document.getElementById('sidebarToggle');
const mainArea = document.querySelector('.main-area');

// Toggle sidebar on mobile
sidebarToggle.addEventListener('click', function() {
    sidebar.classList.toggle('collapsed');
    mainArea.classList.toggle('expanded');
});

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const isMobile = window.innerWidth <= 768;
    const clickedOutsideSidebar = !sidebar.contains(event.target) && !sidebarToggle.contains(event.target);
    
    if (isMobile && clickedOutsideSidebar && sidebar.classList.contains('collapsed')) {
        sidebar.classList.remove('collapsed');
        mainArea.classList.remove('expanded');
    }
});

// Add smooth hover effect to nav links
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('mouseenter', function() {
        if (!sidebar.classList.contains('collapsed')) {
            this.style.transform = 'translateX(5px)';
        }
    });
    
    link.addEventListener('mouseleave', function() {
        this.style.transform = 'translateX(0)';
    });
});

// Handle window resize
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
        sidebar.classList.remove('collapsed');
        mainArea.classList.remove('expanded');
    }
});
</script>
