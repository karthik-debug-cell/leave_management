<?php
// ============================================================
// config/db.php — Database Connection (PDO)
// All database connections go through this single file.
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Change to your MySQL username
define('DB_PASS', '');              // Change to your MySQL password
define('DB_NAME', 'leave_management');

/**
 * Returns a PDO connection instance.
 * Uses singleton pattern so only one connection is made per request.
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Throw exceptions on error
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // Return arrays by default
            PDO::ATTR_EMULATE_PREPARES   => false,                     // Real prepared statements
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log this instead of echoing
            die("Database connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}
