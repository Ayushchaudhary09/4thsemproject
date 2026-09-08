<?php
/**
 * ComplaintBox — Sidebar
 * Expects $current_user and $active (current page key) to be set.
 * $active is one of: 'dashboard', 'submit', 'complaints', 'profile'
 */
declare(strict_types=1);

$active = $active ?? '';
$role   = $current_user['role'] ?? 'student';
?>
<aside class="sidebar">
  <div class="sidebar-user">
    <span class="sidebar-avatar"><?php echo e(strtoupper(substr($current_user['full_name'], 0, 1))); ?></span>
    <span class="sidebar-user-info">
      <strong><?php echo e($current_user['full_name']); ?></strong>
      <small><?php echo e(role_label($role)); ?></small>
    </span>
  </div>

  <p class="sidebar-label"><?php echo e(role_label($role)); ?> Section</p>
  <nav class="sidebar-menu" aria-label="Dashboard navigation">
    <a href="dashboard.php" class="menu-item <?php echo $active === 'dashboard' ? 'active' : ''; ?>">
      <i class="fa-solid fa-gauge-high"></i> Dashboard
    </a>
    <a href="submit_complaint.php" class="menu-item <?php echo $active === 'submit' ? 'active' : ''; ?>">
      <i class="fa-solid fa-file-circle-plus"></i> Submit Complaint
    </a>
    <a href="my_complaints.php" class="menu-item <?php echo $active === 'complaints' ? 'active' : ''; ?>">
      <i class="fa-solid fa-clock-rotate-left"></i> My Complaints
    </a>
    <a href="profile.php" class="menu-item <?php echo $active === 'profile' ? 'active' : ''; ?>">
      <i class="fa-solid fa-user"></i> Profile
    </a>
    <a href="logout.php" class="menu-item menu-item--logout">
      <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
    </a>
  </nav>
</aside>
