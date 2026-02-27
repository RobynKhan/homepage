<?php
define('DB_HOST',     getenv('DB_HOST')     ?: 'localhost');
define('DB_NAME',     getenv('DB_NAME')     ?: 'pomodoro_db');
define('DB_USER',     getenv('DB_USER')     ?: 'root');
define('DB_PASS',     getenv('DB_PASS')     ?: '');
define('DB_CHARSET',  'utf8mb4');

define('APP_NAME',    'Pomodoro Timer');
define('APP_VERSION', '1.0.0');

define('DEFAULT_POMODORO',            25);
define('DEFAULT_SHORT_BREAK',          5);
define('DEFAULT_LONG_BREAK',          15);
define('DEFAULT_POMODOROS_UNTIL_LONG', 4);

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('SPOTIFY_CLIENT_ID',     getenv('SPOTIFY_CLIENT_ID')     ?: '');
define('SPOTIFY_CLIENT_SECRET', getenv('SPOTIFY_CLIENT_SECRET') ?: '');
define('SPOTIFY_REDIRECT_URI',  getenv('SPOTIFY_REDIRECT_URI')  ?: '');
