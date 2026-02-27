<?php
// ─── App ──────────────────────────────────────────────────────────────────
define('APP_NAME', 'Pomodoro Timer');

// ─── Default timer durations (minutes) ────────────────────────────────────
define('DEFAULT_POMODORO',             25);
define('DEFAULT_SHORT_BREAK',           5);
define('DEFAULT_LONG_BREAK',           15);
define('DEFAULT_POMODOROS_UNTIL_LONG',  4);

// ─── Spotify credentials ──────────────────────────────────────────────────
define('SPOTIFY_CLIENT_ID',     getenv('SPOTIFY_CLIENT_ID'));
define('SPOTIFY_CLIENT_SECRET', getenv('SPOTIFY_CLIENT_SECRET'));
define('SPOTIFY_REDIRECT_URI',  getenv('SPOTIFY_REDIRECT_URI'));
define('SPOTIFY_SCOPES',        'streaming user-read-email user-read-private user-modify-playback-state');
