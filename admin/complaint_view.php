<?php
/**
 * ComplaintBox — Admin: Complaint Details & Update
 * View a single complaint and update its status + admin remark.
 */
declare(strict_types=1);

$active = 'complaints';
require_once __DIR__ . '/../includes/admin_auth.php';

$id = (int) ($_GET['id'] ?? 0);
$db = db();

$stmt = $db->prepare(
    "SELECT c.*, u.full_name, u.email, u.phone
     FROM complaints c
     LEFT JOIN users u ON u.id = c.user_id
     WHERE c.id = :id LIMIT 1"
);
$stmt->execute([':id' => $id]);
$complaint = $stmt->fetch();

if (!$complaint) {
    set_flash('error', 'Complaint not found.');
    redirect('complaints.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = clean($_POST['status'] ?? '');
    $remark = clean($_POST['admin_remark'] ?? '');

    if (!array_key_exists($status, complaint_statuses())) {
        $errors['status'] = 'Please select a valid status.';
    }

    if (empty($errors)) {
        $stmt = db()->prepare(
            "UPDATE complaints
             SET status = :status, admin_remark = :remark, updated_at = NOW()
             WHERE id = :id"
        );
        $stmt->execute([
            ':status' => $status,
            ':remark' => $remark,
            ':id'     => $id,
        ]);

        set_flash('success', 'Complaint updated successfully.');
        redirect('complaint_view.php?id=' . $id);
    }
}

$page_title = 'Complaint Details';
include __DIR__ . '/../includes/header.php';
?>
<div class="container dash-layout">
  <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

  <main class="dash-main">
    <div class="detail-grid">
      <!-- ===== Complaint details ===== -->
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
            <span class="detail-label">Submitted By</span>
            <span class="detail-value">
              <?php if ((int) $complaint['anonymous'] === 1): ?>
                <span class="role-badge role-admin"><i class="fa-solid fa-eye-slash"></i> Anonymous Complaint</span>
              <?php else: ?>
                <?php echo e($complaint['full_name']); ?>
              <?php endif; ?>
            </span>
          </li>
          <?php if ((int) $complaint['anonymous'] !== 1): ?>
          <li>
            <span class="detail-label">Contact</span>
            <span class="detail-value"><?php echo e($complaint['email']); ?> · <?php echo e($complaint['phone']); ?></span>
          </li>
          <?php endif; ?>
          <li>
            <span class="detail-label">Submitted</span>
            <span class="detail-value"><?php echo e(nice_date($complaint['created_at'])); ?></span>
          </li>
          <li>
            <span class="detail-label">Last Updated</span>
            <span class="detail-value"><?php echo e(nice_date($complaint['updated_at'])); ?></span>
          </li>
        </ul>

        <div class="remark-box" style="margin-bottom:0;">
          <strong>Description</strong>
          <?php echo nl2br(e($complaint['description'])); ?>
        </div>
      </div>

      <!-- ===== Update form ===== -->
      <div class="card">
        <div class="card-head">
          <div>
            <h2>Update Status</h2>
            <p>Change the status and add an administrative remark</p>
          </div>
        </div>

        <form action="complaint_view.php?id=<?php echo (int) $id; ?>" method="POST" data-validate-form novalidate style="margin-top:20px;">
          <div class="form-group <?php echo isset($errors['status']) ? 'invalid' : ''; ?>" data-validate="required">
            <label for="status">Status</label>
            <select id="status" name="status">
              <?php foreach (complaint_statuses() as $k => $v): ?>
                <option value="<?php echo e($k); ?>" <?php echo $complaint['status'] === $k ? 'selected' : ''; ?>><?php echo e($v); ?></option>
              <?php endforeach; ?>
            </select>
            <span class="field-error">Please select a valid status.</span>
          </div>

          <div class="form-group">
            <label for="admin_remark">Admin Remark</label>
            <textarea id="admin_remark" name="admin_remark" rows="5" placeholder="Add a remark or resolution note..."><?php echo e($complaint['admin_remark']); ?></textarea>
          </div>

          <button type="submit" class="btn btn-primary btn-block">
            <i class="fa-solid fa-save"></i> Save Changes
          </button>
        </form>

        <div style="margin-top:16px;">
          <a href="complaints.php" class="btn btn-outline btn-block">
            <i class="fa-solid fa-arrow-left"></i> Back to All Complaints
          </a>
        </div>
      </div>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
