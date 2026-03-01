<?php

/**
 * ============================================================================
 * logout_admin.php — Admin Session Logout
 * ============================================================================
 *
 * Removes only the admin authentication key from the session, preserving
 * other session data (e.g., Spotify tokens). Redirects to the homepage.
 *
 * Called from: Admin greeting area in the top bar (header.php)
 * ============================================================================
 */
session_start();
require_once __DIR__ . '/auth_config.php';

// Wipe just the admin session key, keeping anything else (like Spotify tokens)
unset($_SESSION[AUTH_SESSION_KEY]);

// If you want to destroy the entire session instead, uncomment these:
// session_unset();
// session_destroy();

header('Location: index.php');
exit;
