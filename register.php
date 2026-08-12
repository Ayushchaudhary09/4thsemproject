<?php
/**
 * ComplaintBox — Registration Page
 * Creates a student or employee account. Admin is never exposed.
 * Server-side validation is authoritative.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

start_session();

if (isset($_SESSION['user_id'])) {
    redirect($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php');
}

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => '', 'role' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name   = clean($_POST['full_name'] ?? '');
    $email       = strtolower(clean($_POST['email'] ?? ''));
    $phone       = preg_replace('/\s+/', '', $_POST['phone'] ?? '');
    $password    = (string) ($_POST['password'] ?? '');
    $confirm     = (string) ($_POST['confirm_password'] ?? '');
    $role        = clean($_POST['role'] ?? '');

    $old = ['full_name' => $full_name, 'email' => $email, 'phone' => $phone, 'role' => $role];

    /* ---------- Full name ---------- */
    if ($full_name === '') {
        $errors['full_name'] = 'This field is required.';
    } elseif (!validate_name($full_name)) {
        $errors['full_name'] = 'Name cannot contain numbers.';
    }

    /* ---------- Email ---------- */
    if ($email === '') {
        $errors['email'] = 'This field is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (!validate_email($email)) {
        $errors['email'] = 'This email does not appear to exist. Please use a real, deliverable email address.';
    } elseif (email_exists($email)) {
        $errors['email'] = 'This email is already registered.';
    }

    /* ---------- Phone ---------- */
    if ($phone === '') {
        $errors['phone'] = 'This field is required.';
    } elseif (!validate_phone($phone)) {
        $errors['phone'] = 'Enter a valid Nepali mobile number starting with 97 or 98.';
    }

    /* ---------- Password ---------- */
    if ($password === '') {
        $errors['password'] = 'This field is required.';
    } elseif (!validate_password($password)) {
        $errors['password'] = 'Password must contain at least 8 characters.';
    } elseif (!password_complexity($password)) {
        $errors['password'] = 'Password must contain uppercase, lowercase and a number.';
    }

    if ($confirm !== $password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    /* ---------- Role (student/employee only — never admin) ---------- */
    if (!in_array($role, ['student', 'employee'], true)) {
        $errors['role'] = 'Please select a valid account type.';
    }

    /* ---------- Insert if no errors ---------- */
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = db()->prepare(
            "INSERT INTO users (full_name, email, password, phone, role)
             VALUES (:full_name, :email, :password, :phone, :role)"
        );
        $stmt->execute([
            ':full_name' => $full_name,
            ':email'     => $email,
            ':password'  => $hash,
            ':phone'     => $phone,
            ':role'      => $role,
        ]);

        set_flash('success', 'Account created successfully. Please log in.');
        redirect('login.php');
    }
}

$page_title = 'Create Account';
$use_navbar  = true;
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<main class="page-load">
  <section class="auth-section">
    <div class="container auth-grid">

      <!-- ===== LEFT: INFO PANEL ===== -->
      <aside class="auth-info">
        <span class="auth-info-tag"><i class="fa-solid fa-user-plus"></i> Join ComplaintBox</span>
        <h1 class="auth-info-title">Start Your Journey</h1>
        <p class="auth-info-desc">
          ComplaintBox gives every student and employee a clear, secure channel
          to raise concerns — and get them resolved.
        </p>
        <div class="auth-info-features">
          <div class="info-feature-card">
            <span class="info-feature-icon"><i class="fa-solid fa-shield-halved"></i></span>
            <div>
              <h4>Secure</h4>
              <p>Your data and complaints are protected with strong encryption.</p>
            </div>
          </div>
          <div class="info-feature-card">
            <span class="info-feature-icon"><i class="fa-solid fa-bolt"></i></span>
            <div>
              <h4>Fast</h4>
              <p>Smart routing sends every complaint to the right team without delay.</p>
            </div>
          </div>
        </div>
      </aside>

      <!-- ===== RIGHT: REGISTRATION FORM ===== -->
      <div class="auth-form-card">
        <h2>Create Account</h2>
        <p class="auth-subtitle">Select your account type to get started.</p>
        <hr class="form-divider" />

        <form action="register.php" method="POST" data-validate-form novalidate>
          <div class="form-group <?php echo isset($errors['full_name']) ? 'invalid' : ''; ?>" data-validate="name">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo e($old['full_name']); ?>" autocomplete="name" />
            <span class="field-error"><?php echo e($errors['full_name'] ?? 'Name cannot contain numbers.'); ?></span>
          </div>

          <div class="form-group <?php echo isset($errors['email']) ? 'invalid' : ''; ?>" data-validate="email">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?php echo e($old['email']); ?>" autocomplete="email" />
            <span class="field-error"><?php echo e($errors['email'] ?? 'Please enter a valid email address.'); ?></span>
          </div>

          <div class="form-group <?php echo isset($errors['phone']) ? 'invalid' : ''; ?>" data-validate="phone">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" value="<?php echo e($old['phone']); ?>" placeholder="e.g. 9812345678" autocomplete="tel" />
            <span class="field-error"><?php echo e($errors['phone'] ?? 'Enter a valid Nepali mobile number starting with 97 or 98.'); ?></span>
          </div>

          <div class="form-group <?php echo isset($errors['role']) ? 'invalid' : ''; ?>" data-validate="required">
            <label for="role">Account Type</label>
            <select id="role" name="role">
              <option value="" disabled <?php echo $old['role'] === '' ? 'selected' : ''; ?>>Select account type</option>
              <option value="student" <?php echo $old['role'] === 'student' ? 'selected' : ''; ?>>Student</option>
              <option value="employee" <?php echo $old['role'] === 'employee' ? 'selected' : ''; ?>>Employee</option>
            </select>
            <span class="field-error">Please select a valid account type.</span>
          </div>

          <div class="form-row">
            <div class="form-group <?php echo isset($errors['password']) ? 'invalid' : ''; ?>" data-validate="password">
              <label for="password">Password</label>
              <input type="password" id="password" name="password" autocomplete="new-password" />
              <span class="field-error"><?php echo e($errors['password'] ?? 'Password must contain at least 8 characters.'); ?></span>
            </div>

            <div class="form-group <?php echo isset($errors['confirm_password']) ? 'invalid' : ''; ?>" data-validate="confirm">
              <label for="confirm_password">Confirm Password</label>
              <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" />
              <span class="field-error">Passwords do not match.</span>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-block">
            <i class="fa-solid fa-user-plus"></i> Create Account
          </button>
        </form>

        <p class="auth-bottom">
          Already have an account?
          <a href="login.php">Log In</a>
        </p>
      </div>

    </div>
  </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
