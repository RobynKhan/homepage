<?php
session_start();
require 'config.php';

// ─── Proactively refresh Spotify token if it expires within 5 minutes ─────
if (
    isset($_SESSION['access_token']) &&
    isset($_SESSION['expires_at']) &&
    $_SESSION['expires_at'] - time() < 300
) {
    $response = file_get_contents('https://accounts.spotify.com/api/token', false, stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => [
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic ' . base64_encode(SPOTIFY_CLIENT_ID . ':' . SPOTIFY_CLIENT_SECRET),
            ],
            'content' => http_build_query([
                'grant_type'    => 'refresh_token',
                'refresh_token' => $_SESSION['refresh_token'],
            ]),
        ],
    ]));

    $data = json_decode($response, true);

    if (isset($data['access_token'])) {
        $_SESSION['access_token'] = $data['access_token'];
        $_SESSION['expires_at']   = time() + $data['expires_in'];
        if (isset($data['refresh_token'])) {
            $_SESSION['refresh_token'] = $data['refresh_token'];
        }
    }
}

// ─── Default timer settings (no database required) ────────────────────────
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
                <button id="pomodorobtn" class="active" type="button" aria-label="Pomodoro Mode">
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
                <span class="timer-display" role="timer" aria-live="polite">
                    <?php
                    // Server-side initial display using PHP settings
                    $initialSeconds = $settings['pomodoro_duration'] * 60;
                    $m = str_pad(intdiv($initialSeconds, 60), 2, '0', STR_PAD_LEFT);
                    $s = str_pad($initialSeconds % 60,         2, '0', STR_PAD_LEFT);
                    echo "{$m}:{$s}";
                    ?>
                </span>
            </div>

            <!-- Timer Controls -->
            <div class="config">
                <div class="pomodoro-count" role="group" aria-label="Timer Controls">
                    <button class="start-button" id="start-button" type="button" aria-label="Start Timer">
                        Start
                    </button>
                    <button class="pause-button" id="pause-button" type="button" aria-label="Pause Timer">
                        <i class="bi bi-pause-circle" aria-hidden="true"></i>
                    </button>
                    <button class="restart-button" id="restart-button" type="button" aria-label="Restart Timer">
                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                    </button>
                    <button class="timer-settings" id="timer-settings" type="button" aria-label="Open Timer Settings">
                        <i class="bi bi-gear" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <!-- Session pomodoro count (updated by JS) -->
            <div id="lifetime-count" class="lifetime-count"></div>
        </div>
    </section>

    <!-- Clock Widget -->
    <div id="container-2" class="container-2">
        <div id="digital-clock">
            <div id="time"></div>
            <div id="date"></div>
        </div>
    </div>

    <!-- Music Player -->
    <div id="container-3" class="container-3">
        <?php if (!isset($_SESSION['access_token'])): ?>
            <!-- Not logged in yet -->
            <a href="login.php" class="spotify-login-btn">
                <i class="bi bi-spotify"></i> Connect Spotify
            </a>
        <?php else: ?>
            <!-- Player UI -->
            <div id="spotify-player">
                <div id="track-info">
                    <img id="album-art" src="" alt="Album Art" />
                    <div id="track-details">
                        <span id="track-name">No track playing</span>
                        <span id="artist-name"></span>
                    </div>
                </div>
                <div id="player-controls">
                    <button id="prev-btn"><i class="bi bi-skip-start-fill"></i></button>
                    <button id="play-pause-btn"><i class="bi bi-play-fill"></i></button>
                    <button id="next-btn"><i class="bi bi-skip-end-fill"></i></button>
                </div>
                <div id="progress-bar-container">
                    <span id="current-time">0:00</span>
                    <input type="range" id="progress-bar" value="0" min="0" max="100" />
                    <span id="total-time">0:00</span>
                </div>
                <div id="volume-control">
                    <i class="bi bi-volume-down"></i>
                    <input type="range" id="volume-bar" value="100" min="0" max="100" />
                    <i class="bi bi-volume-up"></i>
                </div>
                <a href="logout.php" id="logout-btn">
                    <i class="bi bi-box-arrow-right"></i> Disconnect
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Games Placeholder -->
    <div id="container-4" class="container-4">
        <div class="section-1">
            Section 1
            <button class="button">Button</button>
        </div>
    </div>

    <!-- Settings Panel -->
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

        <div class="setting-option">
            <label for="pomodoro-duration">Pomodoro (min):</label>
            <input type="number" id="pomodoro-duration" min="1" max="120"
                value="<?php echo (int)$settings['pomodoro_duration']; ?>" />
        </div>

        <div class="setting-option">
            <label for="short-break-duration">Short Break (min):</label>
            <input type="number" id="short-break-duration" min="1" max="60"
                value="<?php echo (int)$settings['short_break_duration']; ?>" />
        </div>

        <div class="setting-option">
            <label for="long-break-duration">Long Break (min):</label>
            <input type="number" id="long-break-duration" min="1" max="60"
                value="<?php echo (int)$settings['long_break_duration']; ?>" />
        </div>

        <div class="setting-option">
            <label for="until-long-break">Pomodoros until long break:</label>
            <input type="number" id="until-long-break" min="1" max="10"
                value="<?php echo (int)$settings['pomodoros_until_long_break']; ?>" />
        </div>

        <button id="save-settings-btn" type="button">Save Settings</button>
    </div>

    <!-- PHP-injected timer config passed to JS -->
    <script>
        const PHP_TIMERS = <?php echo $jsTimers; ?>;
        const PHP_CONFIG  = <?php echo $jsConfig; ?>;
    </script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>