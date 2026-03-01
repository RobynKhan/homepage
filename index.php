<?php

/**
 * ============================================================================
 * index.php — Main Dashboard / Homepage
 * ============================================================================
 *
 * The primary page of the Pomodoro Timer application. Renders the full
 * dashboard layout including:
 *   - Pomodoro timer with work/short-break/long-break modes
 *   - PixelTune music panel with Spotify and YouTube tabs
 *   - Todo/Quests widget (admin CRUD or guest locked state)
 *   - Settings panel for background theme customization
 *   - Spotify login state save/restore across OAuth redirects
 *
 * Dependencies: config.php, auth_config.php, includes/header.php,
 *               includes/footer.php, todo_widget.php, player.js, timer.js
 * ============================================================================
 */
session_start();
require_once __DIR__ . '/auth_config.php';
require 'config.php';

// ─── Default Timer Settings (from config constants) ──────────────────────
$settings = [
    'pomodoro_duration'          => DEFAULT_POMODORO,
    'short_break_duration'       => DEFAULT_SHORT_BREAK,
    'long_break_duration'        => DEFAULT_LONG_BREAK,
    'pomodoros_until_long_break' => DEFAULT_POMODOROS_UNTIL_LONG,
    'background_theme'           => 'default',
];

$pomodoroCount = 0;

// ─── Convert Timer Durations to Seconds for JavaScript ────────────────────
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

<!-- (lofi FAB removed — YouTube is inside the left panel) -->

<!-- ====== POMODORO TIMER SECTION ====== -->
<section>
    <div id="timer-container" class="timer-container">
        <!-- Timer Mode Selection Buttons (Pomodoro / Short Break / Long Break) -->
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
        <!-- Timer Countdown Display -->
        <div class="runner">
            <span class="timer-display" role="timer" aria-live="polite"><?php
                                                                        $initialSeconds = $settings['pomodoro_duration'] * 60;
                                                                        $m = str_pad(intdiv($initialSeconds, 60), 2, '0', STR_PAD_LEFT);
                                                                        $s = str_pad($initialSeconds % 60, 2, '0', STR_PAD_LEFT);
                                                                        echo "{$m}:{$s}";
                                                                        ?></span>
        </div>
        <!-- Timer Control Buttons (Start / Pause / Restart / Settings) -->
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

<!-- ====== PIXELTUNE MUSIC PANEL (Left Panel — Spotify + YouTube Switcher) ====== -->
<div id="container-3" class="container-3">

    <!-- Music Source Tab Switcher Bar (Spotify / YouTube) -->
    <div class="px-tab-bar">
        <button class="px-tab active" id="px-tab-spotify" onclick="pxSwitchTab('spotify')">
            <i class="bi bi-spotify"></i> SPOTIFY
        </button>
        <button class="px-tab" id="px-tab-youtube" onclick="pxSwitchTab('youtube')">
            <i class="bi bi-youtube"></i> YOUTUBE
        </button>
    </div>

    <!-- ====== SPOTIFY TAB PANEL ====== -->
    <div class="px-tab-panel active" id="px-panel-spotify">
        <div class="px-player" id="px-app">
            <!-- Pixel Stars Decorative Animation -->
            <div class="px-star" style="top:12px;right:40px;animation-delay:0.3s"></div>
            <div class="px-star" style="top:30px;right:18px;animation-delay:0.9s;background:#00e5ff;box-shadow:0 0 4px #00e5ff;"></div>
            <div class="px-star" style="top:6px;right:70px;animation-delay:1.4s;width:2px;height:2px;background:#b06bff;box-shadow:0 0 4px #b06bff;"></div>

            <!-- Retro Scanline Visual Overlay -->
            <div class="px-scanlines"></div>

            <!-- ===== SPOTIFY SCREEN 1: Home (Playlists + Search + Recently Played) ===== -->
            <div class="px-screen active" id="px-screen-home">
                <div class="px-topbar">
                    <div class="px-topbar-title">&#9654; PIXELTUNE</div>
                    <?php if (!isset($_SESSION['access_token'])): ?>
                        <a href="login.php" class="px-topbar-btn" target="_blank" rel="noopener" title="Login with Spotify">
                            <i class="bi bi-spotify"></i>
                        </a>
                    <?php else: ?>
                        <div class="px-topbar-actions">
                            <span class="px-connected-dot"></span>
                            <a href="logout.php" class="px-topbar-btn" title="Disconnect Spotify">
                                <i class="bi bi-box-arrow-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="px-scroll-area">
                    <!-- Spotify Song Search Input -->
                    <div class="px-section-label">SEARCH</div>
                    <div class="px-search-bar">
                        <input type="text" id="search-input" placeholder="SEARCH SONGS..."
                            onkeydown="if(event.key==='Enter') searchSongs()" />
                        <button onclick="searchSongs()">&#128269;</button>
                    </div>

                    <?php if (!isset($_SESSION['access_token'])): ?>
                        <!-- Spotify Login Prompt (shown when not connected) -->
                        <div class="px-login-prompt">
                            <i class="bi bi-spotify" style="font-size:18px;color:var(--px-green)"></i>
                            <span><a href="login.php" target="_blank" rel="noopener" style="color:var(--px-green);text-decoration:underline">Login with Spotify</a> to see your playlists, liked songs &amp; more</span>
                        </div>

                        <!-- Default Popular Playlists (guest mode) -->
                        <div class="px-section-label" style="margin-top:10px">&#127942; TOP SONGS</div>
                        <ul id="track-list-home"></ul>
                    <?php else: ?>
                        <!-- Recently Played Tracks (authenticated users) -->
                        <div class="px-section-label" style="margin-top:10px">&#9655; RECENTLY PLAYED</div>
                        <ul class="px-recent-list" id="recent-list"></ul>

                        <!-- User's Spotify Playlists Grid with Cover Art -->
                        <div class="px-section-label" style="margin-top:12px">&#127925; YOUR PLAYLISTS</div>
                        <div class="px-playlist-grid" id="playlist-grid"></div>
                    <?php endif; ?>
                </div>

                <!-- Now Playing Mini-Bar (navigates to player screen) -->
                <div class="px-np-bar" onclick="pxShowScreen('player')">
                    <div class="px-np-art" id="px-np-emoji">&#127925;</div>
                    <div class="px-np-info">
                        <div class="px-np-title" id="px-np-title">Select a track</div>
                        <div class="px-np-artist" id="px-np-artist">—</div>
                    </div>
                    <div class="px-np-wave" id="px-np-wave"></div>
                </div>
            </div>

            <!-- ===== SPOTIFY SCREEN 2: Track List (Search Results / Playlist Tracks) ===== -->
            <div class="px-screen" id="px-screen-tracks">
                <div class="px-topbar">
                    <button class="px-back-btn" onclick="pxShowScreen('home')">&#9664; BACK</button>
                    <div class="px-topbar-title" id="px-tracks-screen-title">TRACKS</div>
                    <div style="width:18px"></div>
                </div>

                <div class="px-scroll-area">
                    <div class="px-section-label"><span id="playlist-title">TRACKS</span></div>
                    <ul id="track-list"></ul>
                </div>

                <!-- Now Playing Mini-Bar (tracks screen) -->
                <div class="px-np-bar" onclick="pxShowScreen('player')">
                    <div class="px-np-art" id="px-np-emoji2">&#127925;</div>
                    <div class="px-np-info">
                        <div class="px-np-title" id="px-np-title2">Select a track</div>
                        <div class="px-np-artist" id="px-np-artist2">—</div>
                    </div>
                    <div class="px-np-wave" id="px-np-wave2"></div>
                </div>
            </div>

            <!-- ===== SPOTIFY SCREEN 3: Now Playing (Spotify Embed Player) ===== -->
            <div class="px-screen" id="px-screen-player">
                <div class="px-topbar">
                    <button class="px-back-btn" onclick="pxGoBack()">&#9664; BACK</button>
                    <div class="px-topbar-title">&#9654; NOW PLAYING</div>
                    <div class="px-waveform" id="px-waveform"></div>
                </div>

                <div class="px-embed-body" id="spotify-embed-container">
                    <!-- Spotify IFrame Embed API Container -->
                </div>
            </div>
        </div>
    </div><!-- /px-panel-spotify -->

    <!-- ====== YOUTUBE TAB PANEL ====== -->
    <div class="px-tab-panel" id="px-panel-youtube">
        <div class="px-player px-yt-player" id="px-yt-app">
            <!-- Retro Scanline Visual Overlay -->
            <div class="px-scanlines"></div>

            <!-- Pixel Stars Decorative Animation -->
            <div class="px-star" style="top:10px;right:30px;animation-delay:0.2s;background:#ff0000;box-shadow:0 0 4px #ff0000;"></div>
            <div class="px-star" style="top:25px;right:55px;animation-delay:1.1s;"></div>

            <!-- YouTube Single Screen View -->
            <div class="px-screen active">
                <div class="px-topbar">
                    <div class="px-topbar-title" style="color:var(--px-accent)">&#9654; YOUTUBE</div>
                </div>

                <div class="px-scroll-area">
                    <!-- YouTube URL Input Field -->
                    <div class="px-section-label">PASTE URL</div>
                    <div class="px-search-bar">
                        <input type="text" id="lofi-url-input" placeholder="YOUTUBE URL..."
                            onkeydown="if(event.key==='Enter') loadLofiURL()" />
                        <button onclick="loadLofiURL()">GO</button>
                        <button onclick="resetLofiDefault()" title="Reset" style="border-left:2px solid #000">&#8634;</button>
                    </div>
                    <div class="lofi-error-msg" id="lofi-error-msg">&#9888; INVALID URL</div>

                    <!-- YouTube Embedded Video Player -->
                    <div class="px-yt-embed-wrap" id="lofi-player-wrap">
                        <iframe
                            id="lofi-yt-player"
                            src="https://www.youtube.com/embed/76GStMlLF_Y?enablejsapi=1&autoplay=0&rel=0&modestbranding=1"
                            frameborder="0"
                            allow="autoplay; encrypted-media; fullscreen"></iframe>
                        <div class="lofi-loading-overlay" id="lofi-loading">
                            <div class="px-yt-spinner"></div>
                            LOADING...
                        </div>
                    </div>

                    <!-- YouTube Playback Controls (Play/Pause + Volume) -->
                    <div class="px-yt-controls">
                        <button class="px-yt-play-btn" id="lofi-play-btn" onclick="toggleLofiPlay()">&#9654; PLAY</button>
                        <div class="px-yt-volume">
                            <span class="px-yt-vol-icon" id="lofi-vol-icon">&#128264;</span>
                            <input type="range" id="lofi-vol" min="0" max="100" value="70" oninput="lofiSetVolume(this.value)" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /px-panel-youtube -->

</div><!-- /container-3 (PixelTune Music Panel) -->

<!-- ====== TODO/QUESTS WIDGET SECTION ====== -->
<div id="container-4" class="container-4" data-show-as="flex" style="display:flex;">
    <?php require_once __DIR__ . '/todo_widget.php'; ?>
</div>

<!-- ====== SETTINGS PANEL (Background Theme Selection) ====== -->
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

<!-- ====== PHP-TO-JS CONFIGURATION BRIDGE ====== -->
<!-- Timer duration and config values injected from PHP into JavaScript -->
<script>
    const PHP_TIMERS = <?php echo $jsTimers; ?>;
    const PHP_CONFIG = <?php echo $jsConfig; ?>;
</script>

<!-- ====== SPOTIFY LOGIN STATE SAVE/RESTORE ====== -->
<!-- Preserves app state (timer, lofi, todos, panels) across Spotify OAuth redirects -->
<script>
    (function() {
        const STATE_KEY = 'spotifyLoginState';

        // ── Save App State Before Spotify Login Redirect ──
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a.btn-spotify[target="_blank"]');
            if (!link) return;

            const snap = {};

            // Timer state
            if (typeof state !== 'undefined') {
                snap.timer = {
                    pomodoroType: state.pomodoroType,
                    timerValue: state.timerValue,
                    isPaused: state.isPaused,
                    pomodoroCount: state.pomodoroCount,
                    wasRunning: !!state.timerInterval
                };
            }

            // Lofi widget state
            const lofiVol = document.getElementById('lofi-vol');
            const lofiIframe = document.getElementById('lofi-yt-player');
            snap.lofi = {
                activeTab: document.querySelector('.px-tab.active')?.id === 'px-tab-youtube' ? 'youtube' : 'spotify',
                volume: lofiVol ? parseInt(lofiVol.value) : 70,
                videoId: null,
                isPlaying: typeof isPlaying !== 'undefined' ? isPlaying : false
            };
            if (lofiIframe && lofiIframe.src) {
                const m = lofiIframe.src.match(/embed\/([a-zA-Z0-9_-]{11})/);
                if (m) snap.lofi.videoId = m[1];
            }

            // Background theme
            const bgSelect = document.getElementById('background-select');
            if (bgSelect) snap.theme = bgSelect.value;

            // Guest todos (non-admin in-memory list)
            if (typeof todos !== 'undefined' && Array.isArray(todos)) {
                snap.guestTodos = todos;
            }

            // Panel visibility
            snap.panels = {};
            ['container-3', 'container-4', 'settings-container'].forEach(function(id) {
                const el = document.getElementById(id);
                if (el) snap.panels[id] = window.getComputedStyle(el).display;
            });

            try {
                localStorage.setItem(STATE_KEY, JSON.stringify(snap));
            } catch (e) {}
        });

        // ── Restore App State After Spotify Login Redirect ──
        function restoreState() {
            let raw;
            try {
                raw = localStorage.getItem(STATE_KEY);
            } catch (e) {}
            if (!raw) return;
            localStorage.removeItem(STATE_KEY);

            let snap;
            try {
                snap = JSON.parse(raw);
            } catch (e) {
                return;
            }

            // Theme
            if (snap.theme && typeof applyTheme === 'function') {
                applyTheme(snap.theme);
            }

            // Timer
            if (snap.timer && typeof state !== 'undefined') {
                if (snap.timer.pomodoroType && typeof setTimeType === 'function') {
                    setTimeType(snap.timer.pomodoroType);
                }
                state.timerValue = snap.timer.timerValue;
                state.pomodoroCount = snap.timer.pomodoroCount || 0;
                state.isPaused = snap.timer.isPaused || false;
                if (typeof updateTimerDisplay === 'function') updateTimerDisplay();
            }

            // Lofi widget
            if (snap.lofi) {
                // Restore active tab
                if (snap.lofi.activeTab && typeof pxSwitchTab === 'function') {
                    pxSwitchTab(snap.lofi.activeTab);
                }
                const lofiVol = document.getElementById('lofi-vol');
                if (lofiVol) lofiVol.value = snap.lofi.volume;
                if (snap.lofi.videoId) {
                    const currentIframe = document.getElementById('lofi-yt-player');
                    if (currentIframe && currentIframe.src) {
                        const cm = currentIframe.src.match(/embed\/([a-zA-Z0-9_-]{11})/);
                        if (!cm || cm[1] !== snap.lofi.videoId) {
                            // Different video — swap but don't autoplay
                            currentIframe.src = 'https://www.youtube.com/embed/' + snap.lofi.videoId + '?autoplay=0&rel=0&modestbranding=1';
                        }
                    }
                }
            }

            // Guest todos
            if (snap.guestTodos && typeof todos !== 'undefined' && typeof render === 'function') {
                todos = snap.guestTodos;
                render();
            }

            // Panel visibility
            if (snap.panels) {
                Object.keys(snap.panels).forEach(function(id) {
                    const el = document.getElementById(id);
                    if (el) el.style.display = snap.panels[id];
                });
            }
        }

        // Run restore after everything else has initialized
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(restoreState, 100);
            });
        } else {
            setTimeout(restoreState, 100);
        }
    }());
</script>

<!-- ====== PIXELTUNE PLAYER INITIALIZATION ====== -->
<!-- Spotify embed player JS + Supabase client setup -->
<script>
    const PX_LOGGED_IN = <?php echo isset($_SESSION['access_token']) ? 'true' : 'false'; ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script>
    const supabaseClient = window.supabase.createClient(
        '<?php echo getenv("SUPABASE_URL"); ?>',
        '<?php echo getenv("SUPABASE_ANON_KEY"); ?>'
    );
</script>

<script src="player.js"></script>

<!-- ====== YOUTUBE WIDGET CONTROLLER ====== -->
<!-- Handles YouTube URL input, video swapping, play/pause, and volume -->
<script src="youtube.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>