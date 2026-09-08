<?php
/**
 * ComplaintBox — Admin Authentication Guard
 * Ensures the visitor is logged in as an admin.
 * Redirects non-admins away.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

// Base path for CSS/JS assets. Admin pages are one level deep.
$base_path = '../';

// Brand link target for admins (admin pages live one level deep).
$brand_href = 'dashboard.php';

start_session();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    redirect('../login.php');
}

if (!is_admin_role($_SESSION['role'])) {
    set_flash('error', 'You are not authorized to access this page.');
    redirect('../dashboard.php');
}

// Load the current admin from the database (fresh data).
$stmt = db()->prepare(
    "SELECT id, full_name, email, password, phone, role, status, created_at
     FROM users WHERE id = :id LIMIT 1"
);
$stmt->execute([':id' => $_SESSION['user_id']]);
$current_user = $stmt->fetch();

if (!$current_user || $current_user['status'] !== 'active') {
    session_destroy();
    set_flash('error', 'Your account has been deactivated. Contact the administrator.');
    redirect('../login.php');
}
