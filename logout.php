<?php
/**
 * ComplaintBox — Logout
 * Destroys the session and clears authentication state.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

start_session();

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

set_flash('success', 'You have been logged out successfully.');
redirect('login.php');
