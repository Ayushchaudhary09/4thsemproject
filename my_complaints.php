<?php
/**
 * ComplaintBox — My Complaints
 * Lists ONLY the logged-in user's complaints. Authorization enforced in PHP.
 */
declare(strict_types=1);

$active = 'complaints';
require_once __DIR__ . '/includes/auth.php';

$userId = (int) $current_user['id'];

$stmt = db()->prepare(
    "SELECT id, complaint_id, title, category, status, anonymous, created_at
     FROM complaints WHERE user_id = :uid ORDER BY created_at DESC, id DESC"
);
$stmt->execute([':uid' => $userId]);
$complaints = $stmt->fetchAll();

$page_title = 'My Complaints';
include __DIR__ . '/includes/header.php';
?>
<div class="container dash-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <main class="dash-main">
    <div class="card">
      <div class="card-head">
        <div>
          <h2>My Complaints</h2>
          <p>All of your submitted complaints and their status</p>
        </div>
      </div>

      <div class="table-wrap">
        <?php if (empty($complaints)): ?>
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
              <?php foreach ($complaints as $c): ?>
              <tr>
                <td class="id-cell"><?php echo e($c['complaint_id']); ?></td>
                <td>
                  <?php echo e($c['title']); ?>
                  <?php if ((int) $c['anonymous'] === 1): ?>
                    <span class="role-badge role-admin"><i class="fa-solid fa-eye-slash"></i> Anonymous</span>
                  <?php endif; ?>
                </td>
                <td><?php echo e(category_label($c['category'])); ?></td>
                <td><?php echo e(nice_date($c['created_at'])); ?></td>
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

<?php include __DIR__ . '/includes/footer.php'; ?>
