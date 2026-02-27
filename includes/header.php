<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <title><?php echo defined('APP_NAME') ? htmlspecialchars(APP_NAME) : 'Pomodoro Timer'; ?></title>
    <link rel="stylesheet" href="styling.css" />
</head>

<body>
    <nav class="navbar">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="#settings" class="nav-link" aria-label="Settings" data-target="settings-container">
                    <i class="bi bi-sliders2"></i>
                </a>
            </li>
            <li class="nav-item">
                <a href="#timer" class="nav-link" aria-label="Timer" data-target="timer-container">
                    <i class="bi bi-hourglass-split"></i>
                </a>
            </li>
            <li class="nav-item">
                <a href="#item2" class="nav-link" aria-label="Clock" data-target="container-2">
                    <i class="bi bi-clock"></i>
                </a>
            </li>
            <li class="nav-item">
                <a href="#lofi" class="nav-link" aria-label="Lofi Radio" data-target="lofi-widget">
                    <i class="bi bi-youtube"></i>
                </a>
            </li>
            <li class="nav-item">
                <a href="#spotify" class="nav-link" aria-label="Spotify" data-target="container-3">
                    <i class="bi bi-spotify"></i>
                </a>
            </li>
            <li class="nav-item">
                <a href="#todo" class="nav-link" aria-label="To-do List" data-target="container-4">
                    <i class="bi bi-journal-check"></i>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Admin login/logout button — top right -->
    <div class="admin-corner">
        <?php if (function_exists('is_admin_logged_in') && is_admin_logged_in()): ?>
            <span class="admin-greeting">👋 <?php echo htmlspecialchars(current_admin()['display_name']); ?></span>
            <a href="logout_admin.php" class="admin-btn" title="Logout">
                <i class="bi bi-person-check-fill"></i> Logout
            </a>
        <?php else: ?>
            <a href="login_admin.php" class="admin-btn" title="Admin Login">
                <i class="bi bi-person-lock"></i> Admin Login
            </a>
        <?php endif; ?>
    </div>