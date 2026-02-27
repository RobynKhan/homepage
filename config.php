<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

define('DB_HOST',     $_ENV['DB_HOST']     ?? 'localhost');
define('DB_NAME',     $_ENV['DB_NAME']     ?? 'pomodoro_db');
define('DB_USER',     $_ENV['DB_USER']     ?? 'root');
define('DB_PASS',     $_ENV['DB_PASS']     ?? '');
define('DB_CHARSET',  'utf8mb4');

define('APP_NAME',    'Pomodoro Timer');
define('APP_VERSION', '1.0.0');

define('DEFAULT_POMODORO',             25);
define('DEFAULT_SHORT_BREAK',           5);
define('DEFAULT_LONG_BREAK',           15);
define('DEFAULT_POMODOROS_UNTIL_LONG',  4);

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('SPOTIFY_CLIENT_ID',     $_ENV['SPOTIFY_CLIENT_ID']     ?? '');
define('SPOTIFY_CLIENT_SECRET', $_ENV['SPOTIFY_CLIENT_SECRET'] ?? '');
define('SPOTIFY_REDIRECT_URI',  $_ENV['SPOTIFY_REDIRECT_URI']  ?? '');
```

---

**Keep a `.env` locally for development:**
```
SPOTIFY_CLIENT_ID=your_id_here
SPOTIFY_CLIENT_SECRET=your_secret_here
SPOTIFY_REDIRECT_URI=https://your-app.onrender.com/callback.php
DB_HOST=localhost
DB_NAME=pomodoro_db
DB_USER=root
DB_PASS=
```

---

**Your `.gitignore` should have:**
```
.env
vendor/
