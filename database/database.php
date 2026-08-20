<?php
/**
 * ComplaintBox — Database Connection
 * PDO connection to the MySQL `complaintbox` database (XAMPP defaults).
 */

declare(strict_types=1);

// ---- Configuration (edit these to match your environment) ----
const DB_HOST = '127.0.0.1';
const DB_NAME = 'complaintbox';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';
// ---------------------------------------------------------------

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Do NOT expose raw SQL errors to users.
            http_response_code(500);
            echo 'Database connection failed. Please check config/database.php and confirm MySQL is running.';
            exit;
        }
    }

    return $pdo;
}
