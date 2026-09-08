<?php
/**
 * ComplaintBox — Public Navigation Bar
 * Used on the landing, login and register pages.
 */
declare(strict_types=1);
?>
<nav class="navbar" aria-label="Primary navigation">
  <div class="container nav-inner">
    <a href="index.php" class="nav-brand">
      <span class="brand-logo"><i class="fa-solid fa-bullhorn"></i></span>
      <span class="brand-text">Complaint<em>Box</em></span>
    </a>

    <button type="button" class="nav-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false">
      <i class="fa-solid fa-bars"></i>
    </button>

    <ul class="nav-links" id="navLinks">
      <li><a href="index.php">Home</a></li>
      <li><a href="index.php#about">About</a></li>
      <li><a href="index.php#how-it-works">How It Works</a></li>
      <li class="nav-actions">
        <a href="login.php" class="btn btn-outline btn-sm">Login</a>
        <a href="register.php" class="btn btn-primary btn-sm">Create Account</a>
      </li>
    </ul>
  </div>
</nav>
