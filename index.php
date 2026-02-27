<?php
session_start();
require 'config.php';

// ─── Default timer settings ────────────────────────────────────────────────
$settings = [
    'pomodoro_duration'          => DEFAULT_POMODORO,
    'short_break_duration'       => DEFAULT_SHORT_BREAK,
    'long_break_duration'        => DEFAULT_LONG_BREAK,
    'pomodoros_until_long_break' => DEFAULT_POMODOROS_UNTIL_LONG,
    'background_theme'           => 'default',
];

$pomodoroCount = 0;

// ─── Convert minutes → seconds for the JS state object ───────────────────
$jsTimers = json_encode([
    'POMODORO'   => $settings['pomodoro_duration']          * 60,
    'SHORTBREAK' => $settings['short_break_duration']       * 60,
    'LONGBREAK'  => $settings['long_break_duration']        * 60,
]);
$jsConfig = json_encode([
    'pomodorosUntilLongBreak' => (int)$settings['pomodoros_until_long_break'],
    'initialCount'            => $pomodoroCount,
    'backgroundTheme'         => $settings['background_theme'],
]);
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<section>
    <div id="timer-container" class="timer-container">
        <!-- Timer Mode Buttons -->
        <div class="timers" role="group" aria-label="Timer Modes">
            <button
                id="pomodorobtn"
                class="active"
                type="button"
                aria-label="Pomodoro Mode">
                Pomodoro
            </button>
            <button id="shortbrkbtn" type="button" aria-label="Short Break Mode">
                Short Break
            </button>
            <button id="longbrkbtn" type="button" aria-label="Long Break Mode">
                Long Break
            </button>
        </div>
        <!-- Timer Display -->
        <div class="runner">
            <span class="timer-display" role="timer" aria-live="polite"><?php
                                                                        $initialSeconds = $settings['pomodoro_duration'] * 60;
                                                                        $m = str_pad(intdiv($initialSeconds, 60), 2, '0', STR_PAD_LEFT);
                                                                        $s = str_pad($initialSeconds % 60, 2, '0', STR_PAD_LEFT);
                                                                        echo "{$m}:{$s}";
                                                                        ?></span>
        </div>
        <!-- Timer Controls -->
        <div class="config">
            <div class="pomodoro-count" role="group" aria-label="Timer Controls">
                <button
                    class="start-button"
                    id="start-button"
                    type="button"
                    aria-label="Start Timer">
                    Start
                </button>
                <button
                    class="pause-button"
                    id="pause-button"
                    type="button"
                    aria-label="Pause Timer">
                    <i class="bi bi-pause-circle" aria-hidden="true"></i>
                </button>
                <button
                    class="restart-button"
                    id="restart-button"
                    type="button"
                    aria-label="Restart Timer">
                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                </button>
                <button
                    class="timer-settings"
                    id="timer-settings"
                    type="button"
                    aria-label="Open Timer Settings">
                    <i class="bi bi-gear" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </div>
</section>

<div id="container-2" class="container-2">
    <div id="digital-clock">
        <div id="time"></div>
        <div id="date"></div>
    </div>
</div>
<div id="container-3" class="container-3">

    <!-- Spotify Embed Player (always visible) -->
    <div class="spotify-player-section">

        <!-- Login/Logout bar -->
        <div class="spotify-auth-bar">
            <?php if (!isset($_SESSION['access_token'])): ?>
                <a href="login.php" class="btn-spotify">
                    <i class="bi bi-spotify"></i> Login with Spotify to see your playlists
                </a>
            <?php else: ?>
                <p class="spotify-connected"><i class="bi bi-spotify"></i> Spotify connected</p>
                <a href="logout.php" class="btn-spotify btn-spotify--disconnect">Disconnect</a>
            <?php endif; ?>
        </div>

        <div class="app-layout">

            <!-- Sidebar: playlists (only when logged in) -->
            <div class="sidebar">
                <h3>Your Playlists</h3>
                <?php if (!isset($_SESSION['access_token'])): ?>
                    <p class="sidebar-hint">Login to see your playlists</p>
                <?php else: ?>
                    <ul id="playlist-list"></ul>
                <?php endif; ?>
            </div>

            <!-- Main: search + tracklist -->
            <div class="main-content">
                <div class="search-bar">
                    <input type="text" id="search-input" placeholder="Search for songs…"
                        onkeydown="if(event.key==='Enter') searchSongs()" />
                    <button onclick="searchSongs()">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
                <div class="tracklist-panel">
                    <h3 id="playlist-title">Search or select a playlist</h3>
                    <ul id="track-list"></ul>
                </div>
            </div>

            <!-- Right: Spotify iframe embed -->
            <div class="right-panel">
                <div class="embed-container">
                    <h2>Now Playing</h2>
                    <iframe
                        id="spotify-embed"
                        src="https://open.spotify.com/embed/playlist/37i9dQZF1DXcBWIGoYBM5M"
                        width="100%"
                        height="380"
                        frameborder="0"
                        allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
                        allowfullscreen
                        loading="lazy"
                        style="border-radius: 12px;">
                    </iframe>
                </div>
            </div>

        </div>
    </div>
</div>

<div id="container-4" class="container-4">
    <div class="section-1">
        Section 1
        <button class="button">Button</button>
    </div>
</div>

<div id="settings-container" class="settings-container">
    <i class="bi bi-x-circle" id="close-settings"></i>
    <h2>Settings</h2>
    <div class="setting-option">
        <label for="background-select">Background Theme:</label>
        <select id="background-select">
            <option value="default" <?php echo $settings['background_theme'] === 'default' ? 'selected' : ''; ?>>Default</option>
            <option value="theme1" <?php echo $settings['background_theme'] === 'theme1'  ? 'selected' : ''; ?>>Theme 1</option>
            <option value="theme2" <?php echo $settings['background_theme'] === 'theme2'  ? 'selected' : ''; ?>>Theme 2</option>
            <option value="theme3" <?php echo $settings['background_theme'] === 'theme3'  ? 'selected' : ''; ?>>Theme 3</option>
            <option value="theme4" <?php echo $settings['background_theme'] === 'theme4'  ? 'selected' : ''; ?>>Hogwarts</option>
        </select>
    </div>
</div>

<!-- PHP-injected timer config passed to JS -->
<script>
    const PHP_TIMERS = <?php echo $jsTimers; ?>;
    const PHP_CONFIG = <?php echo $jsConfig; ?>;
</script>

<!-- Load player JS only when logged in for playlist fetching -->
<?php if (isset($_SESSION['access_token'])): ?>
    <script src="player.js"></script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>