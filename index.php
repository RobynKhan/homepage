<?php
session_start();
require_once __DIR__ . '/auth_config.php';
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

<div class="admin-bar">
    <?php if (is_admin_logged_in()): ?>
        <!-- Logged in: show admin name + logout -->
        <span>👋 <?php echo htmlspecialchars(current_admin()['display_name']); ?></span>
        <a href="logout_admin.php">Logout</a>
    <?php else: ?>
        <!-- Not logged in: show login button -->
        <a href="login_admin.php">Admin Login</a>
    <?php endif; ?>
</div>

<!-- Lofi Study Widget -->
<div class="lofi-widget" id="lofi-widget">

    <div class="lofi-search-row">
        <input
            class="lofi-url-input"
            id="lofi-url-input"
            type="text"
            placeholder="paste youtube url..."
            onkeydown="if(event.key==='Enter') loadLofiURL()" />
        <button class="lofi-url-btn" onclick="loadLofiURL()">Go</button>
        <button class="lofi-reset-btn" onclick="resetLofiDefault()" title="reset to default">↺</button>
    </div>
    <div class="lofi-error-msg" id="lofi-error-msg">⚠ invalid youtube url</div>

    <div class="lofi-player-wrap" id="lofi-player-wrap">
        <iframe
            id="lofi-yt-player"
            src="https://www.youtube.com/embed/76GStMlLF_Y?enablejsapi=1&autoplay=1&rel=0&modestbranding=1"
            frameborder="0"
            allow="autoplay; encrypted-media"
            allowfullscreen></iframe>
        <div class="lofi-loading-overlay" id="lofi-loading">
            <div class="lofi-spinner"></div>
            loading...
        </div>
    </div>

    <div class="lofi-controls">
        <button class="lofi-btn-main" id="lofi-play-btn" onclick="toggleLofiPlay()">▶ Play</button>
        <div class="lofi-volume-wrap">
            <span class="lofi-vol-icon" id="lofi-vol-icon">🔈</span>
            <input type="range" id="lofi-vol" min="0" max="100" value="70" oninput="lofiSetVolume(this.value)" />
        </div>
    </div>

</div>

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
    <?php require_once __DIR__ . '/todo_widget.php'; ?>
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

<!-- Lofi Widget Script -->
<script>
    const DEFAULT = {
        id: '76GStMlLF_Y',
        title: 'why the rush?',
        sub: 'lo-fi beats · cat jazz',
        label: 'cat jazz · live radio'
    };

    let isPlaying = false;
    let player = null;

    /* ── YouTube IFrame API ── */
    const tag = document.createElement('script');
    tag.src = 'https://www.youtube.com/iframe_api';
    document.head.appendChild(tag);

    window.onYouTubeIframeAPIReady = function() {
        player = new YT.Player('lofi-yt-player', {
            events: {
                onReady: lofiPlayerReady,
                onStateChange: lofiStateChange
            }
        });
    };

    function lofiPlayerReady(e) {
        e.target.setVolume(parseInt(document.getElementById('lofi-vol').value));
    }

    function lofiStateChange(e) {
        if (e.data === YT.PlayerState.PLAYING) {
            lofiSetPlaying(true);
            document.getElementById('lofi-loading').classList.remove('visible');
        } else if (e.data === YT.PlayerState.PAUSED || e.data === YT.PlayerState.ENDED) {
            lofiSetPlaying(false);
        }
    }

    function swapLofiVideo(videoId, title, sub, label) {
        document.getElementById('lofi-loading').classList.add('visible');
        lofiSetPlaying(false);

        if (player) {
            try {
                player.destroy();
            } catch (err) {}
            player = null;
        }

        // Safely remove old iframe
        const wrap = document.getElementById('lofi-player-wrap');
        const oldIframe = document.getElementById('lofi-yt-player');
        if (oldIframe) oldIframe.remove();

        // Create fresh iframe
        const iframe = document.createElement('iframe');
        iframe.id = 'lofi-yt-player';
        iframe.style.cssText = 'display:block;width:100%;height:160px;';
        iframe.setAttribute('frameborder', '0');
        iframe.setAttribute('allow', 'autoplay; encrypted-media');
        iframe.setAttribute('allowfullscreen', '');
        iframe.src = `https://www.youtube.com/embed/${videoId}?enablejsapi=1&autoplay=1&rel=0&modestbranding=1`;
        wrap.insertBefore(iframe, document.getElementById('lofi-loading'));

        // Bind new player
        player = new YT.Player('lofi-yt-player', {
            events: {
                onReady: function(e) {
                    e.target.setVolume(parseInt(document.getElementById('lofi-vol').value));
                    e.target.playVideo();
                },
                onStateChange: lofiStateChange
            }
        });

        document.getElementById('lofi-track-title').textContent = title;
    }

    function lofiExtractId(url) {
        url = url.trim();
        const patterns = [
            /[?&]v=([a-zA-Z0-9_-]{11})/,
            /youtu\.be\/([a-zA-Z0-9_-]{11})/,
            /youtube\.com\/live\/([a-zA-Z0-9_-]{11})/,
            /youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/,
            /youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/,
        ];
        for (const p of patterns) {
            const m = url.match(p);
            if (m) return m[1];
        }
        if (/^[a-zA-Z0-9_-]{11}$/.test(url)) return url;
        return null;
    }

    function loadLofiURL() {
        const input = document.getElementById('lofi-url-input');
        const errorMsg = document.getElementById('lofi-error-msg');

        input.classList.remove('error');
        errorMsg.classList.remove('visible');

        const videoId = lofiExtractId(input.value);
        if (!videoId) {
            input.classList.add('error');
            errorMsg.classList.add('visible');
            return;
        }

        swapLofiVideo(videoId, 'now playing ♪');
        input.value = '';
    }

    function resetLofiDefault() {
        swapLofiVideo(DEFAULT.id, DEFAULT.title);
    }

    function toggleLofiPlay() {
        if (!player) return;
        isPlaying ? player.pauseVideo() : player.playVideo();
    }

    function lofiSetVolume(val) {
        if (player && player.setVolume) player.setVolume(parseInt(val));
        document.getElementById('lofi-vol-icon').textContent =
            val == 0 ? '🔇' : val < 50 ? '🔈' : '🔊';
    }

    function lofiSetPlaying(playing) {
        isPlaying = playing;
        document.getElementById('lofi-play-btn').textContent = playing ? '⏸ Pause' : '▶ Play';
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>