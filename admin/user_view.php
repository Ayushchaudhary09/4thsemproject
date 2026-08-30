<?php
/**
 * ComplaintBox — Admin: User Details & Actions
 * View a user, their complaints, and (for the top admin) change
 * role or activate/deactivate the account.
 */
declare(strict_types=1);

$active = 'users';
require_once __DIR__ . '/../includes/admin_auth.php';

$db = db();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare(
    "SELECT u.*, (SELECT COUNT(*) FROM complaints c WHERE c.user_id = u.id) AS complaint_count
     FROM users u WHERE u.id = :id LIMIT 1"
);
$stmt->execute([':id' => $id]);
$user = $stmt->fetch();

if (!$user) {
    set_flash('error', 'User not found.');
    redirect('users.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = clean($_POST['action'] ?? '');

    if (in_array($action, ['activate', 'deactivate'], true)) {
        $newStatus = $action === 'activate' ? 'active' : 'inactive';
        $stmt = db()->prepare("UPDATE users SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $newStatus, ':id' => $id]);
        set_flash('success', 'User account ' . $newStatus . 'd successfully.');
        redirect('user_view.php?id=' . $id);
    } elseif ($action === 'change_role') {
        $newRole = clean($_POST['role'] ?? '');
        if (!in_array($newRole, ['student', 'employee', 'admin'], true)) {
            $errors['role'] = 'Please select a valid role.';
        } else {
            $stmt = db()->prepare("UPDATE users SET role = :role WHERE id = :id");
            $stmt->execute([':role' => $newRole, ':id' => $id]);
            set_flash('success', 'User role updated successfully.');
            redirect('user_view.php?id=' . $id);
        }
    }
}

// Reload after changes
$stmt->execute([':id' => $id]);
$user = $stmt->fetch();

/* ---------- User's complaints ---------- */
$cStmt = $db->prepare(
    "SELECT id, complaint_id, title, category, status, anonymous, created_at
     FROM complaints WHERE user_id = :uid ORDER BY created_at DESC, id DESC"
);
$cStmt->execute([':uid' => $id]);
$userComplaints = $cStmt->fetchAll();

$page_title = 'User Details';
include __DIR__ . '/../includes/header.php';
?>
<div class="container dash-layout">
  <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

  <main class="dash-main">
    <div class="detail-grid">
      <!-- ===== User details ===== -->
      <div class="card">
        <div class="card-head">
          <div>
            <h2><?php echo e($user['full_name']); ?></h2>
            <p>User account details</p>
          </div>
          <span class="account-badge <?php echo $user['status'] === 'active' ? 'account-active' : 'account-inactive'; ?>">
            <?php echo e(ucfirst($user['status'])); ?>
          </span>
        </div>

        <ul class="detail-list">
          <li>
            <span class="detail-label">Full Name</span>
            <span class="detail-value"><?php echo e($user['full_name']); ?></span>
          </li>
          <li>
            <span class="detail-label">Email</span>
            <span class="detail-value"><?php echo e($user['email']); ?></span>
          </li>
          <li>
            <span class="detail-label">Phone</span>
            <span class="detail-value"><?php echo e($user['phone']); ?></span>
          </li>
          <li>
            <span class="detail-label">Role</span>
            <span class="detail-value"><?php echo e(role_label($user['role'])); ?></span>
          </li>
          <li>
            <span class="detail-label">Total Complaints</span>
            <span class="detail-value"><?php echo $user['complaint_count']; ?></span>
          </li>
          <li>
            <span class="detail-label">Member Since</span>
            <span class="detail-value"><?php echo e(nice_date($user['created_at'])); ?></span>
          </li>
        </ul>

        <!-- ===== Admin actions (top admin only) ===== -->
        <hr class="form-divider" />
        <h3 style="font-size:1.05rem; color:var(--dark); margin-bottom:16px;">Administrative Actions</h3>

        <form action="user_view.php?id=<?php echo (int) $id; ?>" method="POST" data-validate-form novalidate style="margin-bottom:14px;">
          <input type="hidden" name="action" value="change_role" />
          <div class="form-group <?php echo isset($errors['role']) ? 'invalid' : ''; ?>" data-validate="required">
            <label for="role">Change Role</label>
            <select id="role" name="role">
              <option value="student" <?php echo $user['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
              <option value="employee" <?php echo $user['role'] === 'employee' ? 'selected' : ''; ?>>Employee</option>
              <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select>
          </div>
          <button type="submit" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-user-tag"></i> Update Role
          </button>
        </form>

        <form action="user_view.php?id=<?php echo (int) $id; ?>" method="POST">
          <input type="hidden" name="action" value="<?php echo $user['status'] === 'active' ? 'deactivate' : 'activate'; ?>" />
          <button type="submit" class="btn <?php echo $user['status'] === 'active' ? 'btn-danger' : 'btn-secondary'; ?> btn-sm">
            <i class="fa-solid <?php echo $user['status'] === 'active' ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
            <?php echo $user['status'] === 'active' ? 'Deactivate Account' : 'Activate Account'; ?>
          </button>
        </form>

        <div style="margin-top:16px;">
          <a href="users.php" class="btn btn-outline btn-block">
            <i class="fa-solid fa-arrow-left"></i> Back to Users
          </a>
        </div>
      </div>

      <!-- ===== User's complaints ===== -->
      <div class="card">
        <div class="card-head">
          <div>
            <h2>User's Complaints</h2>
            <p>All complaints submitted by this user</p>
          </div>
        </div>

        <div class="table-wrap">
          <?php if (empty($userComplaints)): ?>
            <div class="empty-state" style="padding:30px 10px;">
              <i class="fa-regular fa-folder-open"></i>
              <p>This user has not submitted any complaints.</p>
            </div>
          <?php else: ?>
            <table class="data-table">
              <thead>
                <tr>
                  <th>Complaint ID</th>
                  <th>Title</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($userComplaints as $c): ?>
                <tr>
                  <td class="id-cell"><?php echo e($c['complaint_id']); ?></td>
                  <td><?php echo e($c['title']); ?></td>
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
    </div>
  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
