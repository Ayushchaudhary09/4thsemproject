<?php
/**
 * ComplaintBox — User Authentication Guard
 * Ensures the visitor is logged in as a normal user (student/employee).
 * Redirects to login otherwise.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

// Base path for CSS/JS assets. Root pages use '' (current dir).
$base_path = $base_path ?? '';

// Brand link target for authenticated users.
$brand_href = 'dashboard.php';

start_session();

if (!isset($_SESSION['user_id']) || ($_SESSION['account'] ?? '') !== 'user') {
    redirect('login.php');
}

// Load the current user from the database (fresh data).
$stmt = db()->prepare(
    "SELECT id, full_name, email, password, phone, role, status
     FROM user WHERE id = :id LIMIT 1"
);
$stmt->execute([':id' => $_SESSION['user_id']]);
$current_user = $stmt->fetch();

if (!$current_user) {
    session_destroy();
    redirect('login.php');
}

// Deactivated accounts cannot access protected pages.
if ($current_user['status'] !== 'active') {
    session_destroy();
    set_flash('error', 'Your account has been deactivated. Contact the administrator.');
    redirect('login.php');
}
