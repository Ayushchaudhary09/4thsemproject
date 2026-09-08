<?php
/**
 * ComplaintBox — Shared HTML head / header
 * Expects $page_title to be set before including.
 * Expects $current_user (optional) for the navbar.
 */
declare(strict_types=1);

if (!isset($page_title)) {
    $page_title = 'ComplaintBox';
}

// Base path for asset URLs ('' for root pages, '../' for admin pages).
$base_path = $base_path ?? '';

// Public pages (landing/login/register) use the navbar instead of the site header.
$use_navbar = $use_navbar ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="ComplaintBox — College Complaint Management System" />
  <title><?php echo e($page_title); ?> — ComplaintBox</title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

<!-- Global styles -->
  <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css" />
</head>
<body>
<?php
// Dismissible flash message
$flash = get_flash();
if ($flash) {
    echo '<div class="alert alert-' . e($flash['type']) . ' alert-dismissible" role="alert">'
        . '<span class="alert-text">' . e($flash['message']) . '</span>'
        . '<button type="button" class="alert-close" aria-label="Dismiss">&times;</button>'
        . '</div>';
}
?>
<?php if (!$use_navbar): ?>
<header class="site-header">
  <div class="container header-inner">
<a href="<?php echo $brand_href ?? 'index.php'; ?>" class="brand">
      <span class="brand-logo"><i class="fa-solid fa-bullhorn"></i></span>
      <span class="brand-text">Complaint<em>Box</em></span>
    </a>
    <span class="tagline">Your Voice Deserves Attention</span>
  </div>
</header>
<?php endif; ?>

