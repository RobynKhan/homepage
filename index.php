<?php

/**
 * ============================================================================
 * index.php — Main Dashboard / Homepage
 * ============================================================================
 *
 * The primary page of the Pomodoro Timer application. Renders the full
 * dashboard layout including:
 *   - Pomodoro timer with work/short-break/long-break modes
 *   - YouTube music panel for playing videos alongside work sessions
 *   - Todo/Quests widget (admin CRUD or guest locked state)
 *   - Settings panel for background theme customization
 *
 * Dependencies: config.php, auth_config.php, includes/header.php,
 *               includes/footer.php, todo_widget.php, youtube.js, timer.js
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

<!-- ====== POMODORO TIMER SECTION ====== -->
<section>
    <div class="timer-container">
        <!-- Pixel decorations -->
        <div class="px-star" style="top:10px;left:30px;animation-delay:0.2s;"></div>
        <div class="px-star" style="bottom:15px;right:25px;animation-delay:1.1s;"></div>

        <!-- Timer mode buttons -->
        <div class="timers" role="group" aria-label="Timer Modes">
            <button id="pomodorobtn" class="active" type="button">25 MIN</button>
            <button id="longpomodorobtn" type="button">50 MIN</button>
            <button id="shortbrkbtn" type="button">5 MIN</button>
            <button id="longbrkbtn" type="button">15 MIN</button>
            <button id="custombrkbtn" type="button">CUSTOM</button>
        </div>

        <!-- Custom time input — only shows when Custom is selected -->
        <div id="custom-time-wrap" style="display:none;">
            <input
                type="number"
                id="custom-time-input"
                min="1"
                max="999"
                placeholder="MINUTES..."
                onkeydown="if(event.key==='Enter') applyCustomTime()" />
            <button onclick="applyCustomTime()">SET</button>
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

        <!-- Timer Control Buttons -->
        <div class="config">
            <div class="pomodoro-count" role="group" aria-label="Timer Controls">
                <button
                    class="start-button"
                    id="start-button"
                    type="button"
                    aria-label="Start Timer">
                    START
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

        <!-- Pomodoro counter (pixel style) -->
        <div class="pomodoro-counter">
            POMODOROS: <span id="pomodoro-counter-display">0</span>/<span id="pomodoro-max-display"><?php echo $settings['pomodoros_until_long_break']; ?></span>
        </div>
    </div>
</section>

<!-- ====== YOUTUBE MUSIC PANEL ====== -->
<div id="container-3" class="container-3">
    <div class="px-player px-yt-player" id="px-yt-app">
        <!-- Retro Scanline Visual Overlay -->
        <div class="px-scanlines"></div>

        <!-- Pixel Stars Decorative Animation -->
        <div class="px-star" style="top:10px;right:30px;animation-delay:0.2s;background:#ff0000;box-shadow:0 0 4px #ff0000;"></div>
        <div class="px-star" style="top:25px;right:55px;animation-delay:1.1s;"></div>

        <!-- ══════ SCREEN 1: HOME — Lofi video list ══════ -->
        <div class="px-screen active" id="px-screen-home">
            <div class="px-topbar">
                <div class="px-topbar-title" style="color:var(--px-accent)">&#9654; YOUTUBE</div>
                <div class="px-topbar-actions">
                    <button class="px-topbar-btn" onclick="pxGoTo('search')" title="Search"><i class="bi bi-search"></i></button>
                </div>
            </div>
            <div class="px-scroll-area">
                <div class="px-section-label">PASTE URL</div>
                <div class="px-search-bar">
                    <input type="text" id="lofi-url-input" placeholder="YOUTUBE URL..."
                        onkeydown="if(event.key==='Enter'){loadLofiURL();pxGoTo('player')}" />
                    <button onclick="loadLofiURL();pxGoTo('player')">GO</button>
                    <button onclick="resetLofiDefault();pxGoTo('player')" title="Reset" style="border-left:2px solid #000">&#8634;</button>
                </div>
                <div class="lofi-error-msg" id="lofi-error-msg">&#9888; INVALID URL</div>

                <div class="px-section-label">LOFI PICKS</div>
                <ul class="px-lofi-list" id="px-lofi-list">
                    <!-- Populated by youtube.js → loadLofiHome() -->
                </ul>

                <div class="px-section-label">RECENTLY PLAYED</div>
                <ul class="px-lofi-list" id="px-recent-list">
                    <!-- Populated by youtube.js → loadRecentVideos() -->
                </ul>
            </div>

            <!-- Now-playing mini bar (click to go to player) -->
            <div class="px-np-bar" id="px-np-bar" style="display:none" onclick="pxGoTo('player')">
                <div class="px-np-art"><i class="bi bi-youtube"></i></div>
                <div class="px-np-info">
                    <span class="px-np-title" id="px-np-title">—</span>
                </div>
                <div class="px-np-wave">
                    <span class="px-np-wave-bar" style="animation-delay:0s;height:6px"></span>
                    <span class="px-np-wave-bar" style="animation-delay:0.15s;height:10px"></span>
                    <span class="px-np-wave-bar" style="animation-delay:0.3s;height:4px"></span>
                    <span class="px-np-wave-bar" style="animation-delay:0.45s;height:8px"></span>
                </div>
            </div>
        </div>

        <!-- ══════ SCREEN 2: SEARCH — Search + results ══════ -->
        <div class="px-screen" id="px-screen-search">
            <div class="px-topbar">
                <button class="px-back-btn" onclick="pxGoTo('home')">&#9664; BACK</button>
                <div class="px-topbar-title">SEARCH</div>
            </div>
            <div class="px-scroll-area">
                <div class="px-search-bar">
                    <input type="text" id="yt-search-input" placeholder="SEARCH YOUTUBE..."
                        onkeydown="if(event.key==='Enter') searchYouTube()" />
                    <button onclick="searchYouTube()"><i class="bi bi-search"></i></button>
                </div>
                <div class="px-yt-results" id="yt-search-results"></div>
            </div>
        </div>

        <!-- ══════ SCREEN 3: PLAYER — Video + controls + queue ══════ -->
        <div class="px-screen" id="px-screen-player">
            <div class="px-topbar">
                <button class="px-back-btn" onclick="pxGoTo('home')">&#9664; BACK</button>
                <div class="px-topbar-title" id="px-player-title">NOW PLAYING</div>
            </div>

            <!-- Embedded Video -->
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

            <div class="px-scroll-area">
                <!-- Playback Controls -->
                <div class="px-yt-controls">
                    <button class="px-yt-play-btn" id="lofi-play-btn" onclick="toggleLofiPlay()">&#9654; PLAY</button>
                    <div class="px-yt-volume">
                        <span class="px-yt-vol-icon" id="lofi-vol-icon">&#128264;</span>
                        <input type="range" id="lofi-vol" min="0" max="100" value="70" oninput="lofiSetVolume(this.value)" />
                    </div>
                </div>

                <!-- Now Playing Indicator -->
                <div class="px-yt-now-playing" id="yt-now-playing"></div>

                <!-- Queue -->
                <div class="px-section-label" id="yt-queue-label" style="display:none;">QUEUE</div>
                <div class="px-yt-queue" id="yt-queue"></div>
            </div>
        </div>
    </div>
</div><!-- /container-3 (YouTube Music Panel) -->

<!-- ====== TODO/QUESTS WIDGET SECTION ====== -->
<div id="container-4" class="container-4" data-show-as="flex" style="display:flex;">
    <?php require_once __DIR__ . '/todo_widget.php'; ?>
</div>

<!-- ====== SETTINGS PANEL — Pixel Styled ====== -->
<div id="settings-container" class="settings-container pixel-settings">
    <!-- Pixel decorations -->
    <div class="px-star" style="top:15px;left:20px;animation-delay:0.2s;"></div>
    <div class="px-star" style="top:40px;right:25px;animation-delay:0.7s;"></div>
    <div class="px-star" style="bottom:25px;left:35px;animation-delay:1.2s;"></div>
    <div class="px-star" style="bottom:50px;right:45px;animation-delay:0.4s;"></div>

    <!-- Scanline overlay (added via CSS) -->

    <!-- Title bar like the todo widget -->
    <div class="settings-titlebar">
        <div class="settings-titlebar-dots">
            <span class="px-dot px-dot--red"></span>
            <span class="px-dot px-dot--yellow"></span>
            <span class="px-dot px-dot--green"></span>
        </div>
        <h2 class="settings-title">THEME SETTINGS</h2>
        <i class="bi bi-x-circle" id="close-settings"></i>
    </div>

    <!-- Settings Content — Theme selection only (auto-populated from themes/ folder) -->
    <div class="settings-content">
        <div class="setting-group">
            <div class="setting-group-header">
                <i class="bi bi-palette"></i>
                <span>DISPLAY</span>
            </div>

            <div class="setting-option">
                <label for="background-select">BACKGROUND THEME:</label>
                <div class="pixel-select-wrapper">
                    <select id="background-select">
                        <?php
                        /**
                         * Auto-detect theme files from the themes/ directory.
                         * Supported extensions: .jpg, .jpeg, .png, .gif, .webp
                         * The first file found (alphabetically) becomes "default".
                         * Adding/removing files in themes/ auto-updates this list.
                         */
                        $themesDir = __DIR__ . '/themes';
                        $allowed   = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        $files     = [];
                        if (is_dir($themesDir)) {
                            foreach (scandir($themesDir) as $f) {
                                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                                if (in_array($ext, $allowed)) {
                                    $files[] = $f;
                                }
                            }
                        }
                        foreach ($files as $i => $file) {
                            $name  = pathinfo($file, PATHINFO_FILENAME);
                            $label = strtoupper(preg_replace('/[_\-]+/', ' ', $name));
                            $value = ($i === 0) ? 'default' : preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
                            $path  = 'themes/' . $file;
                            echo '<option value="' . htmlspecialchars($value) . '" data-file="' . htmlspecialchars($path) . '">' . htmlspecialchars($label) . '</option>' . "\n                        ";
                        }
                        ?>
                    </select>
                    <i class="bi bi-chevron-down select-arrow"></i>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- ====== PHP-TO-JS CONFIGURATION BRIDGE ====== -->
<!-- Timer duration and config values injected from PHP into JavaScript -->
<script>
    const PHP_TIMERS = <?php echo $jsTimers; ?>;
    const PHP_CONFIG = <?php echo $jsConfig; ?>;
</script>

<!-- ====== APP STATE SAVE/RESTORE ====== -->
<!-- Preserves app state (timer, YouTube video, todos, panels) across page reloads -->
<script>
    (function() {
        const STATE_KEY = 'appState';

        // ── Save App State Before Navigation ──
        window.addEventListener('beforeunload', function() {
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

            // YouTube widget state
            const lofiVol = document.getElementById('lofi-vol');
            const lofiIframe = document.getElementById('lofi-yt-player');
            snap.youtube = {
                volume: lofiVol ? parseInt(lofiVol.value) : 70,
                videoId: null,
            };
            if (lofiIframe && lofiIframe.src) {
                const m = lofiIframe.src.match(/embed\/([a-zA-Z0-9_-]{11})/);
                if (m) snap.youtube.videoId = m[1];
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

        // ── Restore App State After Page Load ──
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

            // YouTube widget
            if (snap.youtube) {
                const lofiVol = document.getElementById('lofi-vol');
                if (lofiVol) lofiVol.value = snap.youtube.volume;
                if (snap.youtube.videoId) {
                    const currentIframe = document.getElementById('lofi-yt-player');
                    if (currentIframe && currentIframe.src) {
                        const cm = currentIframe.src.match(/embed\/([a-zA-Z0-9_-]{11})/);
                        if (!cm || cm[1] !== snap.youtube.videoId) {
                            currentIframe.src = 'https://www.youtube.com/embed/' + snap.youtube.videoId + '?autoplay=0&rel=0&modestbranding=1';
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

<!-- ====== SUPABASE CLIENT SETUP ====== -->
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script>
    const supabaseClient = window.supabase.createClient(
        '<?php echo getenv("SUPABASE_URL"); ?>',
        '<?php echo getenv("SUPABASE_ANON_KEY"); ?>'
    );
</script>

<!-- ====== YOUTUBE WIDGET CONTROLLER ====== -->
<!-- Handles YouTube URL input, video swapping, play/pause, and volume -->
<script src="youtube.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>