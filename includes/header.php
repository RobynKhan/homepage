<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <title><?php echo defined('APP_NAME') ? htmlspecialchars(APP_NAME) : 'Pomodoro Timer'; ?></title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="styling.css" />
    <link rel="icon" href="icon.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <!-- ── Top Bar: hamburger + clock + admin ── -->
    <header class="top-bar">
        <button class="top-bar__hamburger" id="hamburger-btn" aria-label="Open menu">
            <i class="bi bi-list"></i>
        </button>
        <div class="top-bar__clock">
            <span id="time"></span>
            <span class="top-bar__dot">·</span>
            <span id="date"></span>
        </div>
        <div class="top-bar__actions">
            <?php if (function_exists('is_admin_logged_in') && is_admin_logged_in()): ?>
                <span class="admin-greeting">👋 <?php echo htmlspecialchars(current_admin()['display_name']); ?></span>
                <a href="logout_admin.php" class="admin-btn" title="Logout">
                    <i class="bi bi-person-check-fill"></i> Logout
                </a>
            <?php else: ?>
                <a href="login_admin.php" class="admin-btn" title="Admin Login">
                    <i class="bi bi-person-lock"></i>
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- ── Nav Drawer (desktop, slides from left) ── -->
    <div class="nav-overlay" id="nav-overlay"></div>
    <nav class="nav-drawer" id="nav-drawer">
        <div class="nav-drawer__head">
            <span class="nav-drawer__title">Menu</span>
            <button class="nav-drawer__close" id="nav-drawer-close" aria-label="Close menu">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <ul class="nav-list">
            <li class="nav-item">
                <a href="#timer" class="nav-link" data-target="timer-container">
                    <i class="bi bi-hourglass-split"></i> <span>Timer</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#lofi" class="nav-link" data-target="lofi-widget">
                    <i class="bi bi-youtube"></i> <span>YouTube</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#spotify" class="nav-link" data-target="container-3">
                    <i class="bi bi-spotify"></i> <span>Spotify</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#todo" class="nav-link" data-target="container-4">
                    <i class="bi bi-journal-check"></i> <span>Tasks</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#settings" class="nav-link" data-target="settings-container">
                    <i class="bi bi-sliders2"></i> <span>Settings</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- ── Mobile Bottom Dock ── -->
    <nav class="bottom-dock" id="bottom-dock">
        <button class="dock-btn" data-target="timer-container" aria-label="Timer">
            <i class="bi bi-hourglass-split"></i>
            <span>Timer</span>
        </button>
        <button class="dock-btn" data-target="lofi-widget" aria-label="Music">
            <i class="bi bi-music-note-beamed"></i>
            <span>Music</span>
        </button>
        <button class="dock-btn" data-target="container-3" aria-label="Spotify">
            <i class="bi bi-spotify"></i>
            <span>Spotify</span>
        </button>
        <button class="dock-btn" data-target="container-4" aria-label="Tasks">
            <i class="bi bi-journal-check"></i>
            <span>Tasks</span>
        </button>
        <button class="dock-btn" data-target="settings-container" aria-label="Settings">
            <i class="bi bi-sliders2"></i>
            <span>More</span>
        </button>
    </nav>

    <!-- ── Bottom Sheet Backdrop (mobile) ── -->
    <div class="sheet-backdrop" id="sheet-backdrop"></div>