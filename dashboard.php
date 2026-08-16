<?php
/**
 * ComplaintBox — User Dashboard
 * Shows summary stats + recent complaints for the logged-in student/employee.
 */
declare(strict_types=1);

$active = 'dashboard';
require_once __DIR__ . '/includes/auth.php';

$db = db();
$userId = (int) $current_user['id'];

/* ---------- Stats ---------- */
$stats = [
    'total'    => 0, 'pending' => 0, 'review' => 0, 'resolved' => 0,
];

$stmt = $db->prepare(
    "SELECT status, COUNT(*) AS cnt FROM complaints WHERE user_id = :uid GROUP BY status"
);
$stmt->execute([':uid' => $userId]);
foreach ($stmt->fetchAll() as $row) {
    $stats['total'] += (int) $row['cnt'];
    if (isset($stats[$row['status']])) {
        $stats[$row['status']] = (int) $row['cnt'];
    }
}

/* ---------- Recent complaints ---------- */
$stmt = $db->prepare(
    "SELECT id, complaint_id, title, category, status, anonymous, created_at
     FROM complaints WHERE user_id = :uid ORDER BY created_at DESC, id DESC LIMIT 5"
);
$stmt->execute([':uid' => $userId]);
$recent = $stmt->fetchAll();

$page_title = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>
<header class="dash-header container">
  <div class="welcome-bar">
    <div>
      <h1>Welcome, <?php echo e(explode(' ', $current_user['full_name'])[0]); ?></h1>
      <p>Here is an overview of your complaints.</p>
    </div>
    <span class="role-badge <?php echo $current_user['role'] === 'student' ? 'role-student' : 'role-employee'; ?>">
      <i class="fa-solid fa-user"></i> <?php echo e(role_label($current_user['role'])); ?>
    </span>
  </div>
</header>

<div class="container dash-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <main class="dash-main">
    <!-- ===== Summary cards (from MySQL) ===== -->
    <div class="stats-grid">
      <div class="stat-card">
        <span class="stat-icon icon-total"><i class="fa-solid fa-file-lines"></i></span>
        <div class="stat-meta">
          <strong class="stat-value"><?php echo $stats['total']; ?></strong>
          <span class="stat-label">Total Complaints</span>
        </div>
      </div>
      <div class="stat-card">
        <span class="stat-icon icon-pending"><i class="fa-solid fa-clock"></i></span>
        <div class="stat-meta">
          <strong class="stat-value"><?php echo $stats['pending']; ?></strong>
          <span class="stat-label">Pending</span>
        </div>
      </div>
      <div class="stat-card">
        <span class="stat-icon icon-review"><i class="fa-solid fa-magnifying-glass"></i></span>
        <div class="stat-meta">
          <strong class="stat-value"><?php echo $stats['review']; ?></strong>
          <span class="stat-label">Under Review</span>
        </div>
      </div>
      <div class="stat-card">
        <span class="stat-icon icon-resolved"><i class="fa-solid fa-circle-check"></i></span>
        <div class="stat-meta">
          <strong class="stat-value"><?php echo $stats['resolved']; ?></strong>
          <span class="stat-label">Resolved</span>
        </div>
      </div>
    </div>

    <!-- ===== Quick actions ===== -->
    <div class="quick-actions">
      <a href="submit_complaint.php" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-file-circle-plus"></i> Submit Complaint
      </a>
      <a href="my_complaints.php" class="btn btn-outline btn-sm">
        <i class="fa-solid fa-clock-rotate-left"></i> My Complaints
      </a>
      <a href="profile.php" class="btn btn-outline btn-sm">
        <i class="fa-solid fa-user"></i> Profile
      </a>
    </div>

    <!-- ===== Recent complaints ===== -->
    <div class="card">
      <div class="card-head">
        <div>
          <h2>Recent Complaints</h2>
          <p>Your latest submissions and their current status</p>
        </div>
        <a href="my_complaints.php" class="btn btn-outline btn-sm">View All</a>
      </div>

      <div class="table-wrap">
        <?php if (empty($recent)): ?>
          <div class="empty-state">
            <i class="fa-regular fa-folder-open"></i>
            <p>No complaints submitted yet.</p>
            <a href="submit_complaint.php" class="btn btn-primary btn-sm">Submit Your First Complaint</a>
          </div>
        <?php else: ?>
          <table class="data-table">
            <thead>
              <tr>
                <th>Complaint ID</th>
                <th>Title</th>
                <th>Category</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent as $c): ?>
              <tr>
                <td class="id-cell"><?php echo e($c['complaint_id']); ?></td>
                <td>
                  <?php echo e($c['title']); ?>
                  <?php if ((int) $c['anonymous'] === 1): ?>
                    <span class="role-badge role-admin"><i class="fa-solid fa-eye-slash"></i> Anonymous</span>
                  <?php endif; ?>
                </td>
                <td><?php echo e(ucfirst($c['category'])); ?></td>
                <td><?php echo e(nice_date($c['created_at'])); ?></td>
                <td>
                  <span class="status-badge <?php echo status_class($c['status']); ?>">
                    <i class="fa-solid <?php echo $c['status'] === 'pending' ? 'fa-clock' : ($c['status'] === 'review' ? 'fa-magnifying-glass' : ($c['status'] === 'approved' ? 'fa-check' : ($c['status'] === 'resolved' ? 'fa-circle-check' : 'fa-xmark'))); ?>"></i>
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

<?php include __DIR__ . '/includes/footer.php'; ?>
