<?php

/**
 * ============================================================================
 * auth_config.php — Admin Authentication Configuration & Helpers
 * ============================================================================
 *
 * Manages admin user authentication for the Pomodoro Dashboard.
 * Loads admin credentials from environment variables (ADMIN1_*, ADMIN2_*)
 * and provides helper functions for session-based auth:
 *   - is_admin_logged_in()  — Check if an admin session is active
 *   - require_admin_login() — Redirect unauthenticated users to login page
 *   - current_admin()       — Retrieve the logged-in admin's data
 *
 * Used by: index.php, login_admin.php, logout_admin.php,
 *          widgets/todo_api.php, widgets/messages_api.php,
 *          widgets/breakout_api.php, widgets/todo_widget.php, log_youtube.php
 * ============================================================================
 */

// ─── Admin Accounts — credentials loaded from environment variables ──────
// Set these on your server / Render dashboard:
//   ADMIN1_USERNAME, ADMIN1_PASSWORD_HASH
//   ADMIN2_USERNAME, ADMIN2_PASSWORD_HASH
//
// Generate a hash locally:
//   php -r "echo password_hash('yourPassword', PASSWORD_BCRYPT);"

define('ADMIN_ACCOUNTS', array_filter([
    getenv('ADMIN1_USERNAME') => (getenv('ADMIN1_USERNAME') && getenv('ADMIN1_PASSWORD_HASH')) ? [
        'username'      => getenv('ADMIN1_USERNAME'),
        'display_name'  => getenv('ADMIN1_USERNAME'),
        'password_hash' => getenv('ADMIN1_PASSWORD_HASH'),
    ] : null,
    getenv('ADMIN2_USERNAME') => (getenv('ADMIN2_USERNAME') && getenv('ADMIN2_PASSWORD_HASH')) ? [
        'username'      => getenv('ADMIN2_USERNAME'),
        'display_name'  => getenv('ADMIN2_USERNAME'),
        'password_hash' => getenv('ADMIN2_PASSWORD_HASH'),
    ] : null,
]));

// ─── Session key used across the app ──────────────────────────────────────
define('AUTH_SESSION_KEY', 'admin_user');

// ─── Where to redirect after login / on unauthorized access ───────────────
define('LOGIN_PAGE',     'login_admin.php');
define('DASHBOARD_PAGE', 'index.php');

// ─── Helper: check if someone is logged in ────────────────────────────────
function is_admin_logged_in(): bool
{
    return isset($_SESSION[AUTH_SESSION_KEY]);
}

// ─── Helper: require login — redirect if not authenticated ────────────────
function require_admin_login(): void
{
    if (!is_admin_logged_in()) {
        header('Location: ' . LOGIN_PAGE);
        exit;
    }
}

// ─── Helper: get current logged-in user data ──────────────────────────────
function current_admin(): ?array
{
    return $_SESSION[AUTH_SESSION_KEY] ?? null;
}
