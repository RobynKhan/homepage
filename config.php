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
 *   - Spotify OAuth credentials and scopes (loaded from environment variables)
 *
 * Used by: index.php, login.php, callback.php, includes/spotify_helpers.php
 * ============================================================================
 */

// ─── Application Identity ─────────────────────────────────────────────────
define('APP_NAME', 'Pomodoro Timer');

// ─── Default Timer Durations (in minutes) ────────────────────────────────
define('DEFAULT_POMODORO',             25);
define('DEFAULT_SHORT_BREAK',           5);
define('DEFAULT_LONG_BREAK',           15);
define('DEFAULT_POMODOROS_UNTIL_LONG',  4);

// ─── Spotify OAuth Credentials (loaded from environment variables) ────────
define('SPOTIFY_CLIENT_ID',     getenv('SPOTIFY_CLIENT_ID'));
define('SPOTIFY_CLIENT_SECRET', getenv('SPOTIFY_CLIENT_SECRET'));
define('SPOTIFY_REDIRECT_URI',  getenv('SPOTIFY_REDIRECT_URI'));
define('SPOTIFY_SCOPES',        'streaming user-read-email user-read-private user-modify-playback-state user-read-recently-played');
