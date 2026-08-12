<?php
/**
 * ComplaintBox — Login Page
 * Authenticates a user and routes based on role.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

start_session();

// Already logged in? Redirect accordingly.
if (isset($_SESSION['user_id'])) {
    redirect($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(clean($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errors['general'] = 'Invalid email or password.';
    } else {
        $stmt = db()->prepare(
            "SELECT id, full_name, email, password, phone, role, status
             FROM users WHERE email = :email LIMIT 1"
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors['general'] = 'Invalid email or password.';
        } elseif ($user['status'] !== 'active') {
            $errors['general'] = 'Your account has been deactivated. Contact the administrator.';
        } else {
            // Regenerate to prevent session fixation
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['role']    = $user['role'];

            set_flash('success', 'Login successful. Welcome back!');
            redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'dashboard.php');
        }
    }
}

$page_title = 'Login';
$use_navbar  = true;
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<main class="page-load">
  <section class="auth-section">
    <div class="container auth-grid">

      <!-- ===== LEFT: INFO PANEL ===== -->
      <aside class="auth-info">
        <span class="auth-info-tag"><i class="fa-solid fa-lock"></i> Secure Portal</span>
        <h1 class="auth-info-title">Welcome Back To ComplaintBox</h1>
        <p class="auth-info-desc">
          Log in to continue tracking your complaints, view responses,
          and stay up to date with resolutions in real time.
        </p>
        <div class="auth-info-features">
          <div class="info-feature-card">
            <span class="info-feature-icon"><i class="fa-solid fa-shield-halved"></i></span>
            <div>
              <h4>Secure</h4>
              <p>Your concerns are protected with encrypted passwords and sessions.</p>
            </div>
          </div>
          <div class="info-feature-card">
            <span class="info-feature-icon"><i class="fa-solid fa-bolt"></i></span>
            <div>
              <h4>Fast</h4>
              <p>Track every complaint and its current status without delay.</p>
            </div>
          </div>
        </div>
      </aside>

      <!-- ===== RIGHT: LOGIN FORM ===== -->
      <div class="auth-form-card">
        <h2>Welcome Back!</h2>
        <p class="auth-subtitle">Enter your credentials to continue.</p>

        <?php if (isset($errors['general'])): ?>
          <div class="alert alert-error" role="alert">
            <span class="alert-text"><?php echo e($errors['general']); ?></span>
            <button type="button" class="alert-close" aria-label="Dismiss">&times;</button>
          </div>
        <?php endif; ?>

        <form action="login.php" method="POST" data-validate-form novalidate>
          <div class="form-group" data-validate="email">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="<?php echo e($email); ?>" autocomplete="email" />
            <span class="field-error">Please enter a valid email address.</span>
          </div>

          <div class="form-group" data-validate="required">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" />
            <span class="field-error">Password is required.</span>
          </div>

          <button type="submit" class="btn btn-primary btn-block">
            <i class="fa-solid fa-right-to-bracket"></i> Log In
          </button>
        </form>

        <p class="auth-bottom">
          Don't have an account yet?
          <a href="register.php">Create an account</a>
        </p>
      </div>

    </div>
  </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
