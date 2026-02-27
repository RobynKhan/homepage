<?php
// ─── Database Configuration ───────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'pomodoro_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ─── Application Configuration ────────────────────────────────────────────
define('APP_NAME', 'Pomodoro Timer');
define('APP_VERSION', '1.0.0');

// ─── Default Timer Durations (in minutes) ─────────────────────────────────
define('DEFAULT_POMODORO', 25);
define('DEFAULT_SHORT_BREAK', 5);
define('DEFAULT_LONG_BREAK', 15);
define('DEFAULT_POMODOROS_UNTIL_LONG', 4);

// ─── Error Reporting (set to 0 in production) ─────────────────────────────
error_reporting(E_ALL);
ini_set('display_errors', 1);
//  spotify app credentials (replace with your actual values)
define('SPOTIFY_CLIENT_ID', 'e24e8fc43eda44b4be3395752826cac0');
define('SPOTIFY_CLIENT_SECRET', '92f1fa0334854033a18f2cdad6646bfe');
define('SPOTIFY_REDIRECT_URI', 'https://timer-1002.web.app/');
