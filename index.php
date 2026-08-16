<?php
/**
 * ComplaintBox — Landing Page
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$use_navbar = true;
$page_title = 'Home';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<main>
  <!-- ============ HERO ============ -->
  <section class="hero">
    <div class="container">
      <h1>A Better Way to <span>Raise Your Concerns</span></h1>
      <p>
        ComplaintBox lets every student and employee of our college safely
        submit concerns, anonymously or with their identity, and track them
        until they are resolved.
      </p>
      <div class="hero-actions">
        <a href="register.php" class="btn btn-primary">
          <i class="fa-solid fa-user-plus"></i> Create Account
        </a>
        <a href="login.php" class="btn btn-outline">
          <i class="fa-solid fa-right-to-bracket"></i> Submit a Complaint
        </a>
      </div>
    </div>
  </section>

  <!-- ============ HOW IT WORKS ============ -->
  <section class="section section-alt" id="how-it-works">
    <div class="container">
      <div class="section-head">
        <h2>How It Works</h2>
        <p>Four simple steps from raising a concern to seeing it resolved.</p>
      </div>

      <div class="process-grid">
        <div class="process-card">
          <div class="process-num">01</div>
          <div class="process-icon"><i class="fa-solid fa-user-plus"></i></div>
          <h3>Create Account</h3>
          <p>Register as a student or employee with your institutional details.</p>
        </div>
        <div class="process-card">
          <div class="process-num">02</div>
          <div class="process-icon"><i class="fa-solid fa-file-circle-plus"></i></div>
          <h3>Submit Complaint</h3>
          <p>Describe your concern and choose to submit it anonymously or openly.</p>
        </div>
        <div class="process-card">
          <div class="process-num">03</div>
          <div class="process-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
          <h3>Track Progress</h3>
          <p>Follow your complaint's status in real time from your dashboard.</p>
        </div>
        <div class="process-card">
          <div class="process-num">04</div>
          <div class="process-icon"><i class="fa-solid fa-circle-check"></i></div>
          <h3>Get Resolution</h3>
          <p>Administrators review your concern and work towards a resolution.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ FEATURES ============ -->
  <section class="section" id="about">
    <div class="container">
      <div class="section-head">
        <h2>Why ComplaintBox?</h2>
        <p>A transparent, organized and secure way to manage institutional concerns.</p>
      </div>

      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon"><i class="fa-solid fa-eye-slash"></i></div>
          <h3>Anonymous Reporting</h3>
          <p>Submit concerns anonymously when you prefer to keep your identity private.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon"><i class="fa-solid fa-magnifying-glass-chart"></i></div>
          <h3>Complaint Tracking</h3>
          <p>Follow every complaint through pending, review, approval and resolution.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon"><i class="fa-solid fa-lock"></i></div>
          <h3>Secure Login</h3>
          <p>Passwords are encrypted and sessions are protected for every user.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon"><i class="fa-solid fa-user-shield"></i></div>
          <h3>Admin Review</h3>
          <p>Administrators review each complaint and add remarks and updates.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon"><i class="fa-solid fa-folder-open"></i></div>
          <h3>Organized Records</h3>
          <p>All complaints and users are stored and managed in a structured database.</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon"><i class="fa-solid fa-bullseye"></i></div>
          <h3>Transparent Status</h3>
          <p>Clear, professional status updates keep everyone informed at all times.</p>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
