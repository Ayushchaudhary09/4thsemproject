<?php
/**
 * ComplaintBox — Shared Helper Functions
 * Validation, sessions, escaping, complaint ID generation, status helpers.
 */

declare(strict_types=1);

require_once __DIR__ . '/../database/database.php';

/* ---------- Session start (if not already) ---------- */
function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/* ---------- Escape output ---------- */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/* ---------- Clean / trim ---------- */
function clean(?string $value): string
{
    return trim((string) $value);
}

/* ---------- Redirect ---------- */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/* ---------- Flash messages (session-based, dismissible) ---------- */
function set_flash(string $type, string $message): void
{
    start_session();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    start_session();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/* ============================================================
   VALIDATION
   ============================================================ */

/**
 * Name validation — names cannot contain numbers.
 * Returns true if valid, false otherwise.
 */
function validate_name(string $name): bool
{
    if ($name === '') return false;                    // required
    if (strlen($name) > 120) return false;             // max length
    if (preg_match('/\d/', $name)) return false;       // no numbers
    // Allow letters (including Unicode), spaces, and common apostrophes/hyphens.
    if (!preg_match("/^[A-Za-z][A-Za-z .'-]+$/", $name)) return false;
    return true;
}

/**
 * Email validation — syntax + domain structure + MX (where supported).
 * Returns true if the email is syntactically valid and its domain can
 * accept mail (best-effort, API-free).
 */
function validate_email(string $email): bool
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $host = strtolower(substr($email, strrpos($email, '@') + 1));

    if ($host === '') return false;

    // Local / literal hosts we intentionally accept (XAMPP development).
    if ($host === 'localhost' || preg_match('/^127\.\d+\.\d+\.\d+$/', $host) || $host === '::1') {
        return true;
    }

    // Must contain a dot to be a real-world deliverable address.
    if (strpos($host, '.') === false) {
        return false;
    }

    // Best-effort MX / A record check (no external API).
    if (function_exists('getmxrr')) {
        $mxHosts = [];
        if (@getmxrr($host, $mxHosts) && count($mxHosts) > 0) {
            return true;
        }
    }

    if (function_exists('checkdnsrr')) {
        foreach (['MX', 'A', 'AAAA'] as $type) {
            if (@checkdnsrr($host, $type)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Nepali mobile number validation.
 * Exactly 10 digits, starts with 97 or 98.
 * Pattern: ^[9][78][0-9]{8}$
 */
function validate_phone(string $phone): bool
{
    return (bool) preg_match('/^[9][78][0-9]{8}$/', $phone);
}

/**
 * Password validation — minimum 8 characters.
 * Prefers uppercase, lowercase and a number.
 */
function validate_password(string $password): bool
{
    return strlen($password) >= 8;
}

/**
 * Check if a password has reasonable strength (uppercase + lowercase + number).
 */
function password_complexity(string $password): bool
{
    return (bool) preg_match('/[A-Z]/', $password)
        && (bool) preg_match('/[a-z]/', $password)
        && (bool) preg_match('/[0-9]/', $password);
}

/**
 * Check if an email already exists in the database (user OR admin tables).
 * Returns the id if it exists, otherwise null.
 */
function email_exists(string $email): ?int
{
    $email = strtolower(clean($email));

    $stmt = db()->prepare("SELECT id FROM user WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $id = $stmt->fetchColumn();

    if (!$id) {
        $stmt = db()->prepare("SELECT id FROM admin WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $id = $stmt->fetchColumn();
    }

    return $id ? (int) $id : null;
}

/* ============================================================
   COMPLAINT HELPERS
   ============================================================ */

/**
 * Generate a unique complaint id like CB-2025-0001.
 */
function generate_complaint_id(): string
{
    $db = db();
    $prefix = 'CB-' . date('Y') . '-';

    $stmt = $db->prepare("SELECT complaint_id FROM complaints WHERE complaint_id LIKE :prefix ORDER BY complaint_id DESC LIMIT 1");
    $stmt->execute([':prefix' => $prefix . '%']);
    $last = $stmt->fetchColumn();

    if ($last) {
        $num = (int) substr($last, strlen($prefix)) + 1;
    } else {
        $num = 1;
    }

    return $prefix . str_pad((string) $num, 4, '0', STR_PAD_LEFT);
}

/* ---------- Complaint status configuration ---------- */
function status_config(): array
{
    return [
        'pending'  => ['label' => 'Pending',      'class' => 'status-pending'],
        'review'   => ['label' => 'Under Review', 'class' => 'status-review'],
        'approved' => ['label' => 'Approved',     'class' => 'status-approved'],
        'resolved' => ['label' => 'Resolved',     'class' => 'status-resolved'],
        'rejected' => ['label' => 'Rejected',     'class' => 'status-rejected'],
    ];
}

function status_label(string $status): string
{
    $cfg = status_config();
    return $cfg[$status]['label'] ?? ucfirst($status);
}

/**
 * Key => label pairs for status filter dropdowns (admin pages).
 * Status values are stored in the normalized `status` table.
 */
function complaint_statuses(): array
{
    $pairs = [];
    $rows  = db()->query("SELECT status_name FROM status ORDER BY id")->fetchAll();
    foreach ($rows as $row) {
        $pairs[$row['status_name']] = status_label($row['status_name']);
    }
    return $pairs;
}

function status_class(string $status): string
{
    $cfg = status_config();
    return $cfg[$status]['class'] ?? 'status-pending';
}

/**
 * Resolve a status name (e.g. 'pending') to its status table id.
 */
function status_id_by_name(string $status): ?int
{
    $stmt = db()->prepare("SELECT id FROM status WHERE status_name = :name LIMIT 1");
    $stmt->execute([':name' => $status]);
    $id = $stmt->fetchColumn();
    return $id ? (int) $id : null;
}

/* ---------- Complaint categories ---------- */
/**
 * Name => label pairs. Categories live in the `categories` table.
 */
function complaint_categories(): array
{
    $pairs = [];
    $rows  = db()->query("SELECT category_name FROM categories ORDER BY id")->fetchAll();
    foreach ($rows as $row) {
        $pairs[$row['category_name']] = category_label($row['category_name']);
    }
    return $pairs;
}

function category_label(string $category): string
{
    return ucfirst(str_replace('_', ' ', $category));
}

/**
 * Resolve a category name (e.g. 'academic') to its categories table id.
 */
function category_id_by_name(string $category): ?int
{
    $stmt = db()->prepare("SELECT id FROM categories WHERE category_name = :name LIMIT 1");
    $stmt->execute([':name' => $category]);
    $id = $stmt->fetchColumn();
    return $id ? (int) $id : null;
}

/* ---------- Role labels ---------- */
function role_label(string $role): string
{
    return match ($role) {
        'super_admin' => 'Super Admin',
        'admin'       => 'Administrator',
        'employee'    => 'Employee',
        default       => 'Student',
    };
}

/* ---------- Role checks ---------- */
function is_admin_role(string $role): bool
{
    return in_array($role, ['admin', 'super_admin'], true);
}

function is_super_admin(string $role): bool
{
    return $role === 'super_admin';
}

/* ---------- Institutional email check ---------- */
function is_kathford_email(string $email): bool
{
    return str_ends_with(strtolower($email), '@kathford.edu.np');
}

/* ---------- Human-friendly date ---------- */
function nice_date(string $date): string
{
    $ts = strtotime($date);
    return $ts ? date('M j, Y g:i A', $ts) : $date;
}
