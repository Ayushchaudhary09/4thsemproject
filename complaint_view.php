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
    "SELECT c.*, c.admin_remark, s.status_name AS status, ct.category_name AS category
     FROM complaints c
     JOIN status s ON s.id = c.status_id
     JOIN categories ct ON ct.id = c.category_id
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

        <?php if (!empty($complaint['evidence'])): ?>
          <div style="margin-top:20px;">
            <strong style="font-size:0.85rem;color:var(--muted);display:block;margin-bottom:8px;">Attached Evidence</strong>
            <?php if (preg_match('/\.(jpg|jpeg|png|gif|webp|avif|bmp)$/i', $complaint['evidence'])): ?>
              <a href="<?php echo e($complaint['evidence']); ?>" target="_blank" rel="noopener">
                <img src="<?php echo e($complaint['evidence']); ?>" alt="Evidence" style="max-width:100%;max-height:400px;border-radius:var(--radius-sm);border:1px solid var(--border);cursor:pointer;" />
              </a>
            <?php else: ?>
              <a href="<?php echo e($complaint['evidence']); ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-file-pdf"></i> View Evidence
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
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
