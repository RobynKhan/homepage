<?php
session_start();
require_once __DIR__ . '/auth_config.php';

// Wipe just the admin session key, keeping anything else (like Spotify tokens)
unset($_SESSION[AUTH_SESSION_KEY]);

// If you want to destroy the entire session instead, uncomment these:
// session_unset();
// session_destroy();

header('Location: index.php');
exit;
