<?php
// ============================================================
// config/session.php — Session & Auth Helpers
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require user to be logged in.
 * If not logged in, redirect to login page immediately.
 */
function requireLogin(): void {
    if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
        header("Location: /leave_management/auth/login.php");
        exit;
    }
}

/**
 * Require the logged-in user to be an admin.
 * If an employee somehow lands on an admin page, kick them out.
 */
function requireAdmin(): void {
    requireLogin();
    // Double check — role MUST be exactly 'admin', nothing else gets through
    if ($_SESSION['role'] !== 'admin') {
        header("Location: /leave_management/employee/dashboard.php");
        exit;
    }
}

/**
 * Require the logged-in user to be an employee.
 * If an admin somehow lands on an employee page, send them back.
 */
function requireEmployee(): void {
    requireLogin();
    // Double check — role MUST be exactly 'employee'
    if ($_SESSION['role'] !== 'employee') {
        header("Location: /leave_management/admin/dashboard.php");
        exit;
    }
}

/**
 * Check if user is logged in.
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['role']);
}

/**
 * Get current logged-in user's session data.
 */
function currentUser(): array {
    return [
        'id'    => $_SESSION['user_id'] ?? null,
        'name'  => $_SESSION['name']    ?? '',
        'role'  => $_SESSION['role']    ?? '',
        'email' => $_SESSION['email']   ?? '',
    ];
}

/**
 * Set a one-time flash message (stored in session, shown once).
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear the flash message.
 */
function getFlash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
