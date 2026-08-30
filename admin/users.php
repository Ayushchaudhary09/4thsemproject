<?php
/**
 * ComplaintBox — Admin: Manage Users
 * Lists all users with role & status. Only the top admin can
 * activate/deactivate and change roles.
 */
declare(strict_types=1);

$active = 'users';
require_once __DIR__ . '/../includes/admin_auth.php';

$db = db();

$search = trim($_GET['search'] ?? '');

$sql = "SELECT u.id, u.full_name, u.email, u.phone, u.role, u.status, u.created_at,
               (SELECT COUNT(*) FROM complaints c WHERE c.user_id = u.id) AS complaint_count
        FROM users u
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (u.full_name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY u.created_at DESC, u.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$page_title = 'Manage Users';
include __DIR__ . '/../includes/header.php';
?>
<div class="container dash-layout">
  <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

  <main class="dash-main">
    <div class="card">
      <div class="card-head">
        <div>
          <h2>Manage Users</h2>
          <p>View registered students and employees</p>
        </div>

        <form action="users.php" method="GET" class="toolbar" style="display:flex; gap:10px; flex-wrap:wrap;">
          <input
            type="text"
            name="search"
            value="<?php echo e($search); ?>"
            placeholder="Search name, email, phone..."
            style="width:auto; min-width:220px; height:44px; padding:0 14px; border:1.5px solid var(--border); border-radius:10px;"
          />
          <button type="submit" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-magnifying-glass"></i> Search
          </button>
        </form>
      </div>

      <div class="table-wrap">
        <?php if (empty($users)): ?>
          <div class="empty-state">
            <i class="fa-regular fa-user"></i>
            <p>No users found.</p>
          </div>
        <?php else: ?>
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Complaints</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
              <tr>
                <td><strong><?php echo e($u['full_name']); ?></strong></td>
                <td><?php echo e($u['email']); ?></td>
                <td><?php echo e($u['phone']); ?></td>
                <td>
                  <span class="role-badge <?php echo $u['role'] === 'admin' ? 'role-admin' : ($u['role'] === 'employee' ? 'role-employee' : 'role-student'); ?>">
                    <?php echo e(role_label($u['role'])); ?>
                  </span>
                </td>
                <td><strong><?php echo $u['complaint_count']; ?></strong></td>
                <td>
                  <span class="account-badge <?php echo $u['status'] === 'active' ? 'account-active' : 'account-inactive'; ?>">
                    <?php echo e(ucfirst($u['status'])); ?>
                  </span>
                </td>
                <td>
                  <a href="user_view.php?id=<?php echo (int) $u['id']; ?>" class="btn btn-outline btn-sm">View</a>
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
