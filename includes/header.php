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
                <a href="#item3" class="nav-link" aria-label="Music" data-target="container-3">
                    <i class="bi bi-music-note-beamed"></i>
                </a>
            </li>
            <li class="nav-item">
                <a href="#item4" class="nav-link" aria-label="Games" data-target="container-4">
                    <i class="bi bi-controller"></i>
                </a>
            </li>
        </ul>
    </nav>