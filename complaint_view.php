<?php
/**
 * ComplaintBox — Complaint Details (User)
 * Shows a single complaint. Enforces ownership: a user can only
 * view their OWN complaint. Changing the ID in the URL is blocked.
 */
declare(strict_types=1);

$active = 'complaints';
require_once __DIR__ . '/includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);
$userId = (int) $current_user['id'];

/* Enforce ownership — include user_id in the WHERE clause. */
$stmt = db()->prepare(
    "SELECT c.*, c.admin_remark
     FROM complaints c
     WHERE c.id = :id AND c.user_id = :uid LIMIT 1"
);
$stmt->execute([':id' => $id, ':uid' => $userId]);
$complaint = $stmt->fetch();

if (!$complaint) {
    set_flash('error', 'Complaint not found.');
    redirect('my_complaints.php');
}

$page_title = 'Complaint Details';
include __DIR__ . '/includes/header.php';
?>
<div class="container dash-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <main class="dash-main">
    <div class="detail-grid">
      <!-- ===== Main details ===== -->
      <div class="card">
        <div class="card-head">
          <div>
            <h2><?php echo e($complaint['title']); ?></h2>
            <p>Complaint ID: <?php echo e($complaint['complaint_id']); ?></p>
          </div>
          <span class="status-badge <?php echo status_class($complaint['status']); ?>">
            <?php echo status_label($complaint['status']); ?>
          </span>
        </div>

        <ul class="detail-list">
          <li>
            <span class="detail-label">Category</span>
            <span class="detail-value"><?php echo e(category_label($complaint['category'])); ?></span>
          </li>
          <li>
            <span class="detail-label">Submitted</span>
            <span class="detail-value"><?php echo e(nice_date($complaint['created_at'])); ?></span>
          </li>
          <li>
            <span class="detail-label">Last Updated</span>
            <span class="detail-value"><?php echo e(nice_date($complaint['updated_at'])); ?></span>
          </li>
          <li>
            <span class="detail-label">Privacy</span>
            <span class="detail-value">
              <?php if ((int) $complaint['anonymous'] === 1): ?>
                <span class="role-badge role-admin"><i class="fa-solid fa-eye-slash"></i> Anonymous Complaint</span>
              <?php else: ?>
                <span class="role-badge role-student"><i class="fa-solid fa-eye"></i> Identified</span>
              <?php endif; ?>
            </span>
          </li>
        </ul>

        <div class="remark-box" style="margin-bottom:0;">
          <strong>Description</strong>
          <?php echo nl2br(e($complaint['description'])); ?>
        </div>
      </div>

      <!-- ===== Admin remark ===== -->
      <div class="card">
        <div class="card-head">
          <div>
            <h2>Admin Response</h2>
            <p>Review and remarks from the institution team</p>
          </div>
        </div>

        <?php if (empty($complaint['admin_remark'])): ?>
          <div class="empty-state" style="padding:30px 10px;">
            <i class="fa-regular fa-comment-dots"></i>
            <p>No admin remark yet. Your complaint is being processed.</p>
          </div>
        <?php else: ?>
          <div class="remark-box">
            <strong>Administrative Remark</strong>
            <?php echo nl2br(e($complaint['admin_remark'])); ?>
          </div>
        <?php endif; ?>

        <div style="margin-top:24px;">
          <a href="my_complaints.php" class="btn btn-outline btn-block">
            <i class="fa-solid fa-arrow-left"></i> Back to My Complaints
          </a>
        </div>
      </div>
    </div>
  </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
