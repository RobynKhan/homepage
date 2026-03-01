<!--
============================================================================
player.html — Standalone YouTube Player
============================================================================
A standalone YouTube player page with:
  - URL input for pasting any YouTube video link
  - Embedded YouTube iframe player
  - Play/pause toggle and volume controls
  - Clean glass-morphism styling matching the main dashboard

Uses plain iframe embeds (no YouTube JS API needed).
Operates independently from the main dashboard (index.php).

Dependencies: styling.css, youtube.js
============================================================================
-->
<?php session_start(); ?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

  <title>YouTube Player — PixelTune</title>
  <link rel="stylesheet" href="styling.css" />

  <style>
    /* ── Standalone YouTube Player: Body Layout ───────────── */
    body {
      align-items: center;
      justify-content: center;
    }

    /* ── Single-Column Player Layout ── */
    .player-layout {
      display: flex;
      flex-direction: column;
      gap: 16px;
      width: 100%;
      max-width: 720px;
      padding: 20px;
      box-sizing: border-box;
    }

    /* ── YouTube Player Card ── */
    .youtube-player-card {
      background: var(--glass-bg);
      -webkit-backdrop-filter: blur(20px);
      backdrop-filter: blur(20px);
      border: 1px solid var(--glass-border);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow);
      padding: 24px 20px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .youtube-player-card h3 {
      margin: 0;
      font-size: 12px;
      font-weight: 600;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: var(--text-muted);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    /* ── URL Input Bars ── */
    .yt-url-bar {
      display: flex;
      gap: 6px;
    }

    .yt-url-bar input {
      flex: 1;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--glass-border);
      border-radius: var(--radius-pill);
      color: var(--cream);
      font-family: "Inter", sans-serif;
      font-size: 14px;
      padding: 10px 16px;
      outline: none;
      transition: border-color var(--transition);
    }

    .yt-url-bar input::placeholder {
      color: var(--text-muted);
    }

    .yt-url-bar input:focus {
      border-color: #ff0000;
    }

    .yt-url-bar input.error {
      border-color: #e74c3c;
    }

    .yt-url-bar button {
      padding: 10px 18px;
      border-radius: var(--radius-pill);
      border: 1px solid rgba(255, 0, 0, 0.4);
      background: rgba(255, 0, 0, 0.15);
      color: var(--cream);
      font-family: "Inter", sans-serif;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition:
        background var(--transition),
        transform var(--transition);
      white-space: nowrap;
    }

    .yt-url-bar button:hover {
      background: rgba(255, 0, 0, 0.3);
      transform: translateY(-1px);
    }

    .yt-error-msg {
      font-size: 11px;
      color: #e74c3c;
      display: none;
      letter-spacing: 0.5px;
    }

    .yt-error-msg.visible {
      display: block;
    }

    /* ── YouTube Embed Wrapper ── */
    .yt-embed-wrap {
      position: relative;
      border-radius: 12px;
      overflow: hidden;
      aspect-ratio: 16 / 9;
    }

    .yt-embed-wrap iframe {
      width: 100%;
      height: 100%;
      border: 0;
      display: block;
    }

    .yt-loading-overlay {
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0.7);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      color: var(--text-muted);
      letter-spacing: 1px;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s;
    }

    .yt-loading-overlay.visible {
      opacity: 1;
      pointer-events: auto;
    }

    /* ── Playback Controls ── */
    .yt-controls {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .yt-play-btn {
      padding: 10px 22px;
      border-radius: var(--radius-pill);
      border: 1px solid rgba(255, 0, 0, 0.4);
      background: rgba(255, 0, 0, 0.15);
      color: var(--cream);
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition:
        background var(--transition),
        transform var(--transition);
    }

    .yt-play-btn:hover {
      background: rgba(255, 0, 0, 0.3);
      transform: translateY(-1px);
    }

    .yt-volume {
      display: flex;
      align-items: center;
      gap: 8px;
      flex: 1;
    }

    .yt-volume input[type="range"] {
      flex: 1;
      height: 4px;
      border-radius: 999px;
      background: var(--glass-border);
      cursor: pointer;
      accent-color: #ff4444;
    }

    /* ── Navigation Link ── */
    .player-links {
      display: flex;
      justify-content: center;
      margin-top: 10px;
    }

    .back-link {
      font-size: 12px;
      color: var(--text-muted);
      text-decoration: none;
      letter-spacing: 1px;
      text-transform: uppercase;
      transition: color var(--transition);
    }

    .back-link:hover {
      color: var(--cream);
    }

    @media (max-width: 768px) {
      .player-layout {
        padding: 12px;
      }
    }

    /* ── Search Results ── */
    #yt-search-results {
      display: flex;
      flex-direction: column;
      gap: 8px;
      max-height: 300px;
      overflow-y: auto;
    }

    .yt-result-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--glass-border);
    }

    .yt-result-item img {
      width: 60px;
      height: 45px;
      object-fit: cover;
      border-radius: 6px;
      flex-shrink: 0;
    }

    .yt-result-title {
      flex: 1;
      font-size: 12px;
      color: var(--cream);
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .yt-result-actions {
      display: flex;
      gap: 6px;
      flex-shrink: 0;
    }

    .yt-result-actions button {
      padding: 6px 12px;
      border-radius: var(--radius-pill);
      border: 1px solid rgba(255, 0, 0, 0.4);
      background: rgba(255, 0, 0, 0.15);
      color: var(--cream);
      font-size: 11px;
      font-weight: 600;
      cursor: pointer;
      transition: background var(--transition);
    }

    .yt-result-actions button:hover {
      background: rgba(255, 0, 0, 0.3);
    }

    /* ── Now Playing ── */
    #yt-now-playing {
      font-size: 11px;
      color: var(--text-muted);
      letter-spacing: 1px;
      min-height: 16px;
    }

    /* ── Queue ── */
    #yt-queue {
      background: var(--glass-bg);
      -webkit-backdrop-filter: blur(20px);
      backdrop-filter: blur(20px);
      border: 1px solid var(--glass-border);
      border-radius: var(--radius-lg);
      padding: 16px 20px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    #yt-queue:empty {
      display: none;
    }

    .yt-queue-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px;
      border-radius: 8px;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--glass-border);
    }

    .yt-queue-item.active {
      border-color: rgba(255, 0, 0, 0.5);
      background: rgba(255, 0, 0, 0.08);
    }

    .yt-queue-title {
      flex: 1;
      font-size: 12px;
      color: var(--cream);
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  </style>
</head>

<body>
  <div class="player-layout">
    <!-- ====== YouTube Player Card ====== -->
    <div class="youtube-player-card">
      <h3><i class="bi bi-youtube"></i> YouTube Player</h3>

      <!-- Search -->
      <div class="yt-url-bar">
        <input
          type="text"
          id="yt-search-input"
          placeholder="Search for a song..."
          onkeydown="if (event.key === 'Enter') searchYouTube();" />
        <button onclick="searchYouTube()">Search</button>
      </div>

      <div id="yt-search-results"></div>

      <!-- URL Input -->
      <div class="yt-url-bar">
        <input
          type="text"
          id="lofi-url-input"
          placeholder="Paste YouTube URL…"
          onkeydown="if (event.key === 'Enter') loadLofiURL();" />
        <button onclick="loadLofiURL()">Go</button>
        <button onclick="resetLofiDefault()" title="Reset to default">
          &#8634;
        </button>
      </div>

      <div class="yt-error-msg" id="lofi-error-msg">
        &#9888; Invalid YouTube URL
      </div>

      <!-- Now Playing -->
      <div id="yt-now-playing"></div>

      <!-- Embedded Video -->
      <div class="yt-embed-wrap" id="lofi-player-wrap">
        <iframe
          id="lofi-yt-player"
          src="https://www.youtube.com/embed/76GStMlLF_Y?autoplay=0&rel=0&modestbranding=1"
          allow="autoplay; encrypted-media; fullscreen"></iframe>
        <div class="yt-loading-overlay" id="lofi-loading">LOADING...</div>
      </div>

      <!-- Controls -->
      <div class="yt-controls">
        <button class="yt-play-btn" onclick="toggleLofiPlay()">
          &#9654; Play
        </button>

        <div class="yt-volume">
          <span id="lofi-vol-icon">&#128264;</span>
          <input
            type="range"
            id="lofi-vol"
            min="0"
            max="100"
            value="70"
            oninput="lofiSetVolume(this.value)" />
        </div>
      </div>
    </div>

    <div id="yt-queue"></div>

    <!-- Navigation -->
    <div class="player-links">
      <a href="index.php" class="back-link">
        <i class="bi bi-arrow-left"></i> Home
      </a>
    </div>
  </div>

  <script src="youtube.js"></script>
</body>

</html>