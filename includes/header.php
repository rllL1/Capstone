<?php
// header.php — used in pages; ensures session is started already
$user_name = $_SESSION['user_name'] ?? 'Admin';
?>
<?php require_once 'notifications.php'; ?>
<header class="header">
  <div class="header-content">
    <div class="header-title">
      <i class="fas fa-wallet"></i>
      <span>SDSC Payroll System</span>
    </div>
    <div class="header-user">
      <i class="fas fa-user-circle"></i>
      <span>Welcome, <strong><?= htmlspecialchars($user_name) ?></strong></span>
    </div>
  </div>
</header>

<style>
.header {
  background: white;
  box-shadow: var(--card-shadow);
  padding: 1rem;
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-content {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  max-width: 1400px;
  margin: 0 auto;
  font-family: 'Poppins', sans-serif;
  gap: 0.75rem;
}

.header-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.5rem;
  color: var(--primary);
  font-weight: 700;
  text-align: center;
}

.header-title i {
  font-size: 1.75rem;
}

.header-user {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--secondary);
  font-size: 1rem;
}

.header-user i {
  font-size: 1.25rem;
  color: var(--primary);
}

.header-user strong {
  color: var(--primary);
  font-weight: 600;
}

@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    gap: 0.5rem;
    text-align: center;
  }
}
</style>
