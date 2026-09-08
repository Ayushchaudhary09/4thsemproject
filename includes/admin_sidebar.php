<?php
/**
 * ComplaintBox — Admin Sidebar
 * Expects $current_user and $active to be set.
 * $active is one of: 'dashboard', 'complaints', 'users', 'profile'
 */
declare(strict_types=1);

$active = $active ?? '';
$role   = $current_user['role'] ?? 'admin';
?>
<aside class="sidebar">
  <div class="sidebar-user">
    <span class="sidebar-avatar"><?php echo e(strtoupper(substr($current_user['full_name'], 0, 1))); ?></span>
    <span class="sidebar-user-info">
      <strong><?php echo e($current_user['full_name']); ?></strong>
      <small><?php echo e(role_label($role)); ?></small>
    </span>
  </div>

  <p class="sidebar-label">Admin Section</p>
  <nav class="sidebar-menu" aria-label="Admin navigation">
    <a href="dashboard.php" class="menu-item <?php echo $active === 'dashboard' ? 'active' : ''; ?>">
      <i class="fa-solid fa-gauge-high"></i> Dashboard
    </a>
    <a href="complaints.php" class="menu-item <?php echo $active === 'complaints' ? 'active' : ''; ?>">
      <i class="fa-solid fa-inbox"></i> Complaints
    </a>
    <a href="users.php" class="menu-item <?php echo $active === 'users' ? 'active' : ''; ?>">
      <i class="fa-solid fa-users"></i> Users
    </a>
    <a href="profile.php" class="menu-item <?php echo $active === 'profile' ? 'active' : ''; ?>">
      <i class="fa-solid fa-user-tie"></i> Profile
    </a>
    <a href="../logout.php" class="menu-item menu-item--logout">
      <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
    </a>
  </nav>
</aside>
