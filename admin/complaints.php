<?php
/**
 * ComplaintBox — Admin: Manage Complaints
 * Lists all complaints with search + status filter.
 */
declare(strict_types=1);

$active = 'complaints';
require_once __DIR__ . '/../includes/admin_auth.php';

$db = db();

$search = trim($_GET['search'] ?? '');
$status = clean($_GET['status'] ?? '');

$sql = "SELECT c.id, c.complaint_id, c.title, c.category, c.status, c.anonymous, c.created_at,
               u.full_name
        FROM complaints c
        JOIN users u ON u.id = c.user_id
        WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (c.complaint_id LIKE :search OR c.title LIKE :search OR u.full_name LIKE :search OR u.email LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (in_array($status, ['pending', 'review', 'approved', 'resolved', 'rejected'], true)) {
    $sql .= " AND c.status = :status";
    $params[':status'] = $status;
}

$sql .= " ORDER BY c.created_at DESC, c.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

$page_title = 'Manage Complaints';
include __DIR__ . '/../includes/header.php';
?>
<div class="container dash-layout">
  <?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

  <main class="dash-main">
    <div class="card">
      <div class="card-head">
        <div>
          <h2>All Complaints</h2>
          <p>Review, update status, or view complaint details</p>
        </div>

        <form action="complaints.php" method="GET" class="toolbar" style="display:flex; gap:10px; flex-wrap:wrap;">
          <input
            type="text"
            name="search"
            value="<?php echo e($search); ?>"
            placeholder="Search ID, title, user, email..."
            style="width:auto; min-width:220px; height:44px; padding:0 14px; border:1.5px solid var(--border); border-radius:10px;"
          />
          <select name="status" style="width:auto; height:44px; padding:0 12px; border:1.5px solid var(--border); border-radius:10px;">
            <option value="">All Status</option>
            <?php foreach (complaint_statuses() as $k => $v): ?>
              <option value="<?php echo e($k); ?>" <?php echo $status === $k ? 'selected' : ''; ?>><?php echo e($v); ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-outline btn-sm">
            <i class="fa-solid fa-magnifying-glass"></i> Filter
          </button>
        </form>
      </div>

      <div class="table-wrap">
        <?php if (empty($complaints)): ?>
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
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($complaints as $c): ?>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
