<?php

/**
 * ============================================================================
 * config.php — Application Configuration
 * ============================================================================
 *
 * Central configuration file for the Pomodoro Timer application.
 * Defines application-wide constants including:
 *   - App identity (name)
 *   - Default Pomodoro timer durations (work, short break, long break)
 *
 * Used by: index.php
 * ============================================================================
 */

// ─── Application Identity ─────────────────────────────────────────────────
define('APP_NAME', 'Pomodoro Timer');

// ─── Default Timer Durations (in minutes) ────────────────────────────────
define('DEFAULT_POMODORO',             25);
define('DEFAULT_SHORT_BREAK',          50);
define('DEFAULT_LONG_BREAK',           25); // fallback, custom replaces this
define('DEFAULT_POMODOROS_UNTIL_LONG',  4);
