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

<!-- (lofi FAB removed — YouTube is inside the left panel) -->

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

<!-- PixelTune Left Panel — Spotify + YouTube switcher -->
<div id="container-3" class="container-3">

    <!-- Tab switcher bar -->
    <div class="px-tab-bar">
        <button class="px-tab active" id="px-tab-spotify" onclick="pxSwitchTab('spotify')">
            <i class="bi bi-spotify"></i> SPOTIFY
        </button>
        <button class="px-tab" id="px-tab-youtube" onclick="pxSwitchTab('youtube')">
            <i class="bi bi-youtube"></i> YOUTUBE
        </button>
    </div>

    <!-- ════ SPOTIFY PANEL ════ -->
    <div class="px-tab-panel active" id="px-panel-spotify">
        <div class="px-player" id="px-app">
            <!-- Pixel stars decoration -->
            <div class="px-star" style="top:12px;right:40px;animation-delay:0.3s"></div>
            <div class="px-star" style="top:30px;right:18px;animation-delay:0.9s;background:#00e5ff;box-shadow:0 0 4px #00e5ff;"></div>
            <div class="px-star" style="top:6px;right:70px;animation-delay:1.4s;width:2px;height:2px;background:#b06bff;box-shadow:0 0 4px #b06bff;"></div>

            <!-- Scanline overlay -->
            <div class="px-scanlines"></div>

            <!-- ===== SCREEN 1: HOME (playlists + search + recent) ===== -->
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
                    <!-- Search -->
                    <div class="px-section-label">SEARCH</div>
                    <div class="px-search-bar">
                        <input type="text" id="search-input" placeholder="SEARCH SONGS..."
                            onkeydown="if(event.key==='Enter') searchSongs()" />
                        <button onclick="searchSongs()">&#128269;</button>
                    </div>

                    <?php if (!isset($_SESSION['access_token'])): ?>
                        <!-- Login prompt -->
                        <div class="px-login-prompt">
                            <i class="bi bi-spotify" style="font-size:18px;color:var(--px-green)"></i>
                            <span><a href="login.php" target="_blank" rel="noopener" style="color:var(--px-green);text-decoration:underline">Login with Spotify</a> to see your playlists, liked songs &amp; more</span>
                        </div>

                        <!-- Default: Top Songs -->
                        <div class="px-section-label" style="margin-top:10px">&#127942; TOP SONGS</div>
                        <ul id="track-list-home"></ul>
                    <?php else: ?>
                        <!-- Recently Played -->
                        <div class="px-section-label" style="margin-top:10px">&#9655; RECENTLY PLAYED</div>
                        <ul class="px-recent-list" id="recent-list"></ul>

                        <!-- Your Playlists — grid with covers -->
                        <div class="px-section-label" style="margin-top:12px">&#127925; YOUR PLAYLISTS</div>
                        <div class="px-playlist-grid" id="playlist-grid"></div>
                    <?php endif; ?>
                </div>

                <!-- Now playing bar at bottom -->
                <div class="px-np-bar" onclick="pxShowScreen('player')">
                    <div class="px-np-art" id="px-np-emoji">&#127925;</div>
                    <div class="px-np-info">
                        <div class="px-np-title" id="px-np-title">Select a track</div>
                        <div class="px-np-artist" id="px-np-artist">—</div>
                    </div>
                    <div class="px-np-wave" id="px-np-wave"></div>
                </div>
            </div>

            <!-- ===== SCREEN 2: TRACKS (search results / playlist tracks) ===== -->
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

                <!-- Now playing bar -->
                <div class="px-np-bar" onclick="pxShowScreen('player')">
                    <div class="px-np-art" id="px-np-emoji2">&#127925;</div>
                    <div class="px-np-info">
                        <div class="px-np-title" id="px-np-title2">Select a track</div>
                        <div class="px-np-artist" id="px-np-artist2">—</div>
                    </div>
                    <div class="px-np-wave" id="px-np-wave2"></div>
                </div>
            </div>

            <!-- ===== SCREEN 3: NOW PLAYING (Spotify embed) ===== -->
            <div class="px-screen" id="px-screen-player">
                <div class="px-topbar">
                    <button class="px-back-btn" onclick="pxGoBack()">&#9664; BACK</button>
                    <div class="px-topbar-title">&#9654; NOW PLAYING</div>
                    <div class="px-waveform" id="px-waveform"></div>
                </div>

                <div class="px-embed-body" id="spotify-embed-container">
                    <!-- Spotify IFrame Embed API will create the iframe here -->
                </div>
            </div>
        </div>
    </div><!-- /px-panel-spotify -->

    <!-- ════ YOUTUBE PANEL ════ -->
    <div class="px-tab-panel" id="px-panel-youtube">
        <div class="px-player px-yt-player" id="px-yt-app">
            <!-- Scanline overlay -->
            <div class="px-scanlines"></div>

            <!-- Pixel stars -->
            <div class="px-star" style="top:10px;right:30px;animation-delay:0.2s;background:#ff0000;box-shadow:0 0 4px #ff0000;"></div>
            <div class="px-star" style="top:25px;right:55px;animation-delay:1.1s;"></div>

            <!-- Single screen for YouTube -->
            <div class="px-screen active">
                <div class="px-topbar">
                    <div class="px-topbar-title" style="color:var(--px-accent)">&#9654; YOUTUBE</div>
                </div>

                <div class="px-scroll-area">
                    <!-- URL input -->
                    <div class="px-section-label">PASTE URL</div>
                    <div class="px-search-bar">
                        <input type="text" id="lofi-url-input" placeholder="YOUTUBE URL..."
                            onkeydown="if(event.key==='Enter') loadLofiURL()" />
                        <button onclick="loadLofiURL()">GO</button>
                        <button onclick="resetLofiDefault()" title="Reset" style="border-left:2px solid #000">&#8634;</button>
                    </div>
                    <div class="lofi-error-msg" id="lofi-error-msg">&#9888; INVALID URL</div>

                    <!-- YouTube embed -->
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

                    <!-- Controls -->
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

</div><!-- /container-3 -->

<div id="container-4" class="container-4" data-show-as="flex" style="display:flex;">
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

<!-- State save/restore for Spotify login flow -->
<script>
    (function() {
        const STATE_KEY = 'spotifyLoginState';

        // ── Save state when clicking Spotify login ──
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

        // ── Restore state on load ──
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
                            currentIframe.src = 'https://www.youtube.com/embed/' + snap.lofi.videoId + '?enablejsapi=1&autoplay=0&rel=0&modestbranding=1';
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

<!-- PixelTune player JS (always loaded) -->
<script>
    const PX_LOGGED_IN = <?php echo isset($_SESSION['access_token']) ? 'true' : 'false'; ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script>
    const supabase = window.supabase.createClient(
        '<?php echo getenv("SUPABASE_URL"); ?>',
        '<?php echo getenv("SUPABASE_ANON_KEY"); ?>'
    );
</script>
<script src="player.js"></script>

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
    let ytApiReady = false;
    let ytPlayerInitialized = false;

    /* ── YouTube IFrame API ── */
    const tag = document.createElement('script');
    tag.src = 'https://www.youtube.com/iframe_api';
    document.head.appendChild(tag);

    window.onYouTubeIframeAPIReady = function() {
        ytApiReady = true;
        // Only init if the YouTube tab is already visible
        if (document.getElementById('px-panel-youtube')?.classList.contains('active')) {
            initYouTubePlayer();
        }
    };

    function initYouTubePlayer() {
        if (!ytApiReady) return;
        const el = document.getElementById('lofi-yt-player');
        if (!el) return;

        // If already initialized, destroy and re-bind so it works after tab switch
        if (player) {
            try {
                player.destroy();
            } catch (e) {}
            player = null;
        }

        // The iframe may have been replaced by destroy(), recreate it if needed
        let iframe = document.getElementById('lofi-yt-player');
        if (!iframe) {
            const wrap = document.getElementById('lofi-player-wrap');
            iframe = document.createElement('iframe');
            iframe.id = 'lofi-yt-player';
            iframe.style.cssText = 'display:block;width:100%;height:200px;';
            iframe.setAttribute('frameborder', '0');
            iframe.setAttribute('allow', 'autoplay; encrypted-media; fullscreen');
            iframe.src = 'https://www.youtube.com/embed/' + DEFAULT.id + '?enablejsapi=1&autoplay=0&rel=0&modestbranding=1';
            wrap.insertBefore(iframe, document.getElementById('lofi-loading'));
        }

        ytPlayerInitialized = true;
        player = new YT.Player('lofi-yt-player', {
            events: {
                onReady: lofiPlayerReady,
                onStateChange: lofiStateChange
            }
        });
    }

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

        // Log YouTube video to Supabase (fire-and-forget)
        if (typeof PX_LOGGED_IN !== 'undefined' && PX_LOGGED_IN) {
            fetch('log_youtube.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    url: 'https://www.youtube.com/watch?v=' + videoId,
                    title: title || '',
                }),
            }).catch(() => {});
        }

        if (player) {
            try {
                player.destroy();
            } catch (err) {}
            player = null;
            ytPlayerInitialized = false;
        }

        // Safely remove old iframe
        const wrap = document.getElementById('lofi-player-wrap');
        const oldIframe = document.getElementById('lofi-yt-player');
        if (oldIframe) oldIframe.remove();

        // Create fresh iframe
        const iframe = document.createElement('iframe');
        iframe.id = 'lofi-yt-player';
        iframe.style.cssText = 'display:block;width:100%;height:200px;';
        iframe.setAttribute('frameborder', '0');
        iframe.setAttribute('allow', 'autoplay; encrypted-media; fullscreen');
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
        document.getElementById('lofi-play-btn').textContent = playing ? '\u23F8 PAUSE' : '\u25B6 PLAY';
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>