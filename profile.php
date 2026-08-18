<?php
/**
 * ComplaintBox — User Profile
 * Shows and updates the logged-in user's profile information.
 */
declare(strict_types=1);

$active = 'profile';
require_once __DIR__ . '/includes/auth.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = clean($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $full_name = clean($_POST['full_name'] ?? '');
        $phone     = preg_replace('/\s+/', '', $_POST['phone'] ?? '');

        if ($full_name === '') {
            $errors['full_name'] = 'This field is required.';
        } elseif (!validate_name($full_name)) {
            $errors['full_name'] = 'Name cannot contain numbers.';
        }

        if ($phone === '') {
            $errors['phone'] = 'This field is required.';
        } elseif (!validate_phone($phone)) {
            $errors['phone'] = 'Enter a valid Nepali mobile number starting with 97 or 98.';
        }

        if (empty($errors)) {
            $stmt = db()->prepare("UPDATE users SET full_name = :name, phone = :phone WHERE id = :id");
            $stmt->execute([
                ':name'  => $full_name,
                ':phone' => $phone,
                ':id'    => (int) $current_user['id'],
            ]);
            set_flash('success', 'Profile updated successfully.');
            redirect('profile.php');
        }
    } elseif ($action === 'change_password') {
        $current_pw = (string) ($_POST['current_password'] ?? '');
        $new_pw     = (string) ($_POST['new_password'] ?? '');
        $confirm_pw = (string) ($_POST['confirm_password'] ?? '');

        $stmt = db()->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => (int) $current_user['id']]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($current_pw, $row['password'])) {
            $errors['current_password'] = 'Your current password is incorrect.';
        }
        if (!validate_password($new_pw)) {
            $errors['new_password'] = 'Password must contain at least 8 characters.';
        } elseif (!password_complexity($new_pw)) {
            $errors['new_password'] = 'Password must contain uppercase, lowercase and a number.';
        }
        if ($confirm_pw !== $new_pw) {
            $errors['confirm_password'] = 'New passwords do not match.';
        }

        if (empty($errors)) {
            $hash = password_hash($new_pw, PASSWORD_DEFAULT);
            $stmt = db()->prepare("UPDATE users SET password = :hash WHERE id = :id");
            $stmt->execute([':hash' => $hash, ':id' => (int) $current_user['id']]);
            set_flash('success', 'Password changed successfully.');
            redirect('profile.php');
        }
    }
}

// Refresh current user after a profile update
$stmt = db()->prepare(
    "SELECT id, full_name, email, password, phone, role, status, created_at
     FROM users WHERE id = :id LIMIT 1"
);
$stmt->execute([':id' => (int) $current_user['id']]);
$current_user = $stmt->fetch();

$initials = strtoupper(substr($current_user['full_name'], 0, 1));

$page_title = 'Profile';
include __DIR__ . '/includes/header.php';
?>
<div class="container dash-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>

  <main class="dash-main">
    <div class="detail-grid">
      <!-- ===== Profile info ===== -->
      <div class="card">
        <div class="card-head">
          <div>
            <h2>User Profile</h2>
            <p>View and update your personal information</p>
          </div>
        </div>

        <div class="profile-display">
          <div class="profile-avatar-lg"><?php echo e($initials); ?></div>
          <ul class="profile-info-list">
            <li>
              <span class="pi-label">Full Name</span>
              <span class="pi-value"><?php echo e($current_user['full_name']); ?></span>
            </li>
            <li>
              <span class="pi-label">Email</span>
              <span class="pi-value"><?php echo e($current_user['email']); ?></span>
            </li>
            <li>
              <span class="pi-label">Phone</span>
              <span class="pi-value"><?php echo e($current_user['phone']); ?></span>
            </li>
            <li>
              <span class="pi-label">Account Type</span>
              <span class="pi-value"><?php echo e(role_label($current_user['role'])); ?></span>
            </li>
            <li>
              <span class="pi-label">Member Since</span>
              <span class="pi-value"><?php echo e(nice_date($current_user['created_at'])); ?></span>
            </li>
          </ul>
        </div>

        <hr class="form-divider" />

        <h3 style="font-size:1.05rem; color:var(--dark); margin-bottom:18px;">Edit Profile</h3>
        <form action="profile.php" method="POST" data-validate-form novalidate>
          <input type="hidden" name="action" value="update_profile" />

          <div class="form-group <?php echo isset($errors['full_name']) ? 'invalid' : ''; ?>" data-validate="name">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo e($current_user['full_name']); ?>" />
            <span class="field-error"><?php echo e($errors['full_name'] ?? 'Name cannot contain numbers.'); ?></span>
          </div>

          <div class="form-group <?php echo isset($errors['phone']) ? 'invalid' : ''; ?>" data-validate="phone">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" value="<?php echo e($current_user['phone']); ?>" />
            <span class="field-error"><?php echo e($errors['phone'] ?? 'Enter a valid Nepali mobile number starting with 97 or 98.'); ?></span>
          </div>

          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk"></i> Save Changes
          </button>
        </form>
      </div>

      <!-- ===== Change password ===== -->
      <div class="card">
        <div class="card-head">
          <div>
            <h2>Change Password</h2>
            <p>Update the password you use to sign in</p>
          </div>
        </div>

        <form action="profile.php" method="POST" data-validate-form novalidate style="margin-top:20px;">
          <input type="hidden" name="action" value="change_password" />

          <div class="form-group <?php echo isset($errors['current_password']) ? 'invalid' : ''; ?>" data-validate="required">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" autocomplete="current-password" />
            <span class="field-error"><?php echo e($errors['current_password'] ?? 'This field is required.'); ?></span>
          </div>

          <div class="form-group <?php echo isset($errors['new_password']) ? 'invalid' : ''; ?>" data-validate="password">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" autocomplete="new-password" />
            <span class="field-error"><?php echo e($errors['new_password'] ?? 'Password must contain at least 8 characters.'); ?></span>
          </div>

          <div class="form-group <?php echo isset($errors['confirm_password']) ? 'invalid' : ''; ?>" data-validate="required">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" />
            <span class="field-error"><?php echo e($errors['confirm_password'] ?? 'New passwords do not match.'); ?></span>
          </div>

          <button type="submit" class="btn btn-secondary btn-block">
            <i class="fa-solid fa-key"></i> Change Password
          </button>
        </form>
      </div>
    </div>
  </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
