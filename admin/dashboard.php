<?php
/**
 * ComplaintBox — Admin Dashboard
 * Shows global statistics calculated from MySQL.
 */
declare(strict_types=1);

$active = 'dashboard';
require_once __DIR__ . '/../includes/admin_auth.php';

$db = db();

/* ---------- Complaint stats by status ---------- */
$stmt = $db->query("SELECT status, COUNT(*) AS cnt FROM complaints GROUP BY status");
$statusCounts = ['pending' => 0, 'review' => 0, 'approved' => 0, 'resolved' => 0, 'rejected' => 0];
$total = 0;
foreach ($stmt->fetchAll() as $row) {
    $statusCounts[$row['status']] = (int) $row['cnt'];
    $total += (int) $row['cnt'];
}
$statusCounts['total'] = $total;

/* ---------- User stats ---------- */
$userStmt = $db->query("SELECT COUNT(*) AS cnt FROM users");
$totalUsers = (int) $userStmt->fetchColumn();

/* ---------- Recent complaints ---------- */
$recentStmt = $db->prepare(
    "SELECT c.id, c.complaint_id, c.title, c.category, c.status, c.anonymous, c.created_at,
            u.full_name
     FROM complaints c
     JOIN users u ON u.id = c.user_id
     ORDER BY c.created_at DESC, c.id DESC LIMIT 6"
);
$recentStmt->execute();
$recent = $recentStmt->fetchAll();

$page_title = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="container dash-layout">
  <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

  <main class="dash-main">
    <div class="welcome-bar">
      <div>
        <h1>Admin Dashboard</h1>
        <p>Institutional control center</p>
      </div>
      <span class="role-badge role-admin"><i class="fa-solid fa-user-tie"></i> Administrator</span>
    </div>

    <!-- ===== Stats grid ===== -->
    <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
      <div class="stat-card">
        <span class="stat-icon icon-total"><i class="fa-solid fa-file-lines"></i></span>
        <div class="stat-meta">
          <strong class="stat-value"><?php echo $statusCounts['total']; ?></strong>
          <span class="stat-label">Total Complaints</span>
        </div>
      </div>
      <div class="stat-card">
        <span class="stat-icon icon-pending"><i class="fa-solid fa-clock"></i></span>
        <div class="stat-meta">
          <strong class="stat-value"><?php echo $statusCounts['pending']; ?></strong>
          <span class="stat-label">Pending</span>
        </div>
      </div>
      <div class="stat-card">
        <span class="stat-icon icon-review"><i class="fa-solid fa-magnifying-glass"></i></span>
        <div class="stat-meta">
          <strong class="stat-value"><?php echo $statusCounts['review']; ?></strong>
          <span class="stat-label">Under Review</span>
        </div>
      </div>
      <div class="stat-card">
        <span class="stat-icon icon-approved"><i class="fa-solid fa-check"></i></span>
        <div class="stat-meta">
          <strong class="stat-value"><?php echo $statusCounts['approved']; ?></strong>
          <span class="stat-label">Approved</span>
        </div>
      </div>
      <div class="stat-card">
        <span class="stat-icon icon-resolved"><i class="fa-solid fa-circle-check"></i></span>
        <div class="stat-meta">
          <strong class="stat-value"><?php echo $statusCounts['resolved']; ?></strong>
          <span class="stat-label">Resolved</span>
        </div>
      </div>
      <div class="stat-card">
        <span class="stat-icon icon-rejected"><i class="fa-solid fa-xmark"></i></span>
        <div class="stat-meta">
          <strong class="stat-value"><?php echo $statusCounts['rejected']; ?></strong>
          <span class="stat-label">Rejected</span>
        </div>
      </div>
      <div class="stat-card">
        <span class="stat-icon icon-users"><i class="fa-solid fa-users"></i></span>
        <div class="stat-meta">
          <strong class="stat-value"><?php echo $totalUsers; ?></strong>
          <span class="stat-label">Total Users</span>
        </div>
      </div>
    </div>

    <!-- ===== Quick actions ===== -->
    <div class="quick-actions">
      <a href="complaints.php" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-inbox"></i> Manage Complaints
      </a>
      <a href="users.php" class="btn btn-outline btn-sm">
        <i class="fa-solid fa-users"></i> Manage Users
      </a>
    </div>

    <!-- ===== Recent complaints ===== -->
    <div class="card">
      <div class="card-head">
        <div>
          <h2>Recent Complaints</h2>
          <p>Latest submissions from students and employees</p>
        </div>
        <a href="complaints.php" class="btn btn-outline btn-sm">View All</a>
      </div>

      <div class="table-wrap">
        <?php if (empty($recent)): ?>
          <div class="empty-state">
            <i class="fa-regular fa-folder-open"></i>
            <p>No complaints found.</p>
          </div>
        <?php else: ?>
          <table class="data-table">
            <thead>
              <tr>
                <th>Complaint ID</th>
                <th>Title</th>
                <th>Submitted By</th>
                <th>Category</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent as $c): ?>
              <tr>
                <td class="id-cell"><?php echo e($c['complaint_id']); ?></td>
                <td><?php echo e($c['title']); ?></td>
                <td>
                  <?php if ((int) $c['anonymous'] === 1): ?>
                    <span class="role-badge role-admin"><i class="fa-solid fa-eye-slash"></i> Anonymous</span>
                  <?php else: ?>
                    <?php echo e($c['full_name']); ?>
                  <?php endif; ?>
                </td>
                <td><?php echo e(category_label($c['category'])); ?></td>
                <td>
                  <span class="status-badge <?php echo status_class($c['status']); ?>">
                    <?php echo status_label($c['status']); ?>
                  </span>
                </td>
                <td>
                  <a href="complaint_view.php?id=<?php echo (int) $c['id']; ?>" class="btn btn-outline btn-sm">View</a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
