/**
 * ============================================================================
 * youtube.js — YouTube Player Controller (3-Screen PixelTune)
 * ============================================================================
 *
 * Self-contained YouTube player module for the PixelTune music panel.
 * Three sliding screens: Home → Search → Player
 *
 * Handles:
 *   - Screen navigation (home / search / player with slide animations)
 *   - Curated lofi video list on home screen
 *   - Recently played videos from DB (via recent_youtube.php)
 *   - YouTube search via search_youtube.php proxy
 *   - URL parsing and video ID extraction (all YouTube URL formats)
 *   - Video swapping via iframe replacement (no YT JS API needed)
 *   - Play/pause toggle via iframe src swap
 *   - Volume icon display updates
 *   - Queue system (add, play-from-queue, render)
 *   - Now-playing bar + player title updates
 *   - Play logging to log_youtube.php (fire-and-forget)
 *   - Page-aware rendering (PixelTune retro vs glass styles)
 *
 * Loaded by: index.php, player.html
 * ============================================================================
 */

// ─── YouTube Default Video Configuration ─────────────────────────────────
const YT_DEFAULT = {
  id: "76GStMlLF_Y",
  title: "why the rush?",
};

// ─── Curated Lofi Picks ──────────────────────────────────────────────────
const LOFI_PICKS = [
  { id: "jfKfPfyJRdk", title: "lofi hip hop radio" },
  { id: "76GStMlLF_Y", title: "why the rush?" },
  { id: "rUxyKA_-grg", title: "2am study session" },
  { id: "5qap5aO4i9A", title: "chill beats to relax" },
  { id: "DWcJFNfaw9c", title: "midnight coding" },
  { id: "lTRiuFIWV54", title: "coffee shop vibes" },
  { id: "kgx4WGK0oNU", title: "jazz & rain" },
  { id: "7NOSDKb0HlU", title: "synthwave radio" },
];

// ═══════════════════════════════════════════════════════════════════════════
// SCREEN NAVIGATION — slide between Home / Search / Player
// ═══════════════════════════════════════════════════════════════════════════

let pxCurrentScreen = "home";

function pxGoTo(screen) {
  if (!isPixelTune()) return; // Only for dashboard
  const screens = {
    home: document.getElementById("px-screen-home"),
    search: document.getElementById("px-screen-search"),
    player: document.getElementById("px-screen-player"),
  };

  // Remove all state classes
  Object.values(screens).forEach((el) => {
    if (el) el.classList.remove("active", "behind");
  });

  // Activate target
  if (screens[screen]) screens[screen].classList.add("active");

  // Push previous behind (for depth effect)
  if (screens[pxCurrentScreen] && pxCurrentScreen !== screen) {
    screens[pxCurrentScreen].classList.add("behind");
  }

  pxCurrentScreen = screen;
}

// ═══════════════════════════════════════════════════════════════════════════
// HOME SCREEN — Lofi picks + recently played
// ═══════════════════════════════════════════════════════════════════════════

function loadLofiHome() {
  const list = document.getElementById("px-lofi-list");
  if (!list) return;
  list.innerHTML = "";

  LOFI_PICKS.forEach((v) => {
    const li = document.createElement("li");
    li.onclick = () => { playVideo(v.id, v.title); };
    li.innerHTML =
      `<img src="https://img.youtube.com/vi/${v.id}/default.jpg" alt="" />` +
      `<span class="px-lofi-meta">` +
        `<span class="px-lofi-title">${v.title}</span>` +
        `<span class="px-lofi-sub">YouTube</span>` +
      `</span>`;
    list.appendChild(li);
  });
}

function loadRecentVideos() {
  const list = document.getElementById("px-recent-list");
  if (!list) return;

  fetch("recent_youtube.php")
    .then((r) => r.json())
    .then((data) => {
      if (!data.items || !data.items.length) {
        list.innerHTML =
          '<li class="px-empty-hint">No recent videos yet</li>';
        return;
      }
      list.innerHTML = "";
      data.items.forEach((v) => {
        const videoId = extractIdFromUrl(v.url);
        if (!videoId) return;
        const li = document.createElement("li");
        li.onclick = () => { playVideo(videoId, v.title); };
        li.innerHTML =
          `<img src="${v.thumbnail || 'https://img.youtube.com/vi/' + videoId + '/default.jpg'}" alt="" />` +
          `<span class="px-lofi-meta">` +
            `<span class="px-lofi-title">${v.title || 'Untitled'}</span>` +
            `<span class="px-lofi-sub">${formatTimeAgo(v.watched_at)}</span>` +
          `</span>`;
        list.appendChild(li);
      });
    })
    .catch(() => {
      list.innerHTML = '<li class="px-empty-hint">Could not load history</li>';
    });
}

function extractIdFromUrl(url) {
  if (!url) return null;
  const m = url.match(/[?&]v=([a-zA-Z0-9_-]{11})/);
  return m ? m[1] : null;
}

function formatTimeAgo(dateStr) {
  if (!dateStr) return "";
  const diff = Date.now() - new Date(dateStr).getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 1) return "just now";
  if (mins < 60) return mins + "m ago";
  const hrs = Math.floor(mins / 60);
  if (hrs < 24) return hrs + "h ago";
  const days = Math.floor(hrs / 24);
  return days + "d ago";
}

// ═══════════════════════════════════════════════════════════════════════════
// PLAY VIDEO — Central play function, navigates to player screen
// ═══════════════════════════════════════════════════════════════════════════

function playVideo(videoId, title) {
  swapLofiVideo(videoId, title);
  if (isPixelTune()) pxGoTo("player");
}

// ═══════════════════════════════════════════════════════════════════════════
// VIDEO SWAP — replaces iframe to avoid API dependency
// ═══════════════════════════════════════════════════════════════════════════

function swapLofiVideo(videoId, title) {
  const loadingEl = document.getElementById("lofi-loading");
  if (loadingEl) loadingEl.classList.add("visible");

  // Update now-playing displays
  updateNowPlaying(title);

  // Log the watch (fire-and-forget)
  fetch("log_youtube.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      url: "https://www.youtube.com/watch?v=" + videoId,
      title: title || "",
      thumbnail: "https://img.youtube.com/vi/" + videoId + "/hqdefault.jpg",
    }),
  }).catch(() => {});

  const wrap = document.getElementById("lofi-player-wrap");
  if (!wrap) return;

  // Remove old iframe
  const old = document.getElementById("lofi-yt-player");
  if (old) old.remove();

  // Create fresh iframe with autoplay
  const iframe = document.createElement("iframe");
  iframe.id = "lofi-yt-player";
  iframe.style.cssText = "display:block;width:100%;height:200px;";
  iframe.setAttribute("frameborder", "0");
  iframe.setAttribute("allow", "autoplay; encrypted-media; fullscreen");
  iframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0&modestbranding=1`;
  iframe.onload = () => {
    if (loadingEl) loadingEl.classList.remove("visible");
  };

  const loadingOverlay = document.getElementById("lofi-loading");
  wrap.insertBefore(iframe, loadingOverlay);
}

// ═══════════════════════════════════════════════════════════════════════════
// URL PARSING
// ═══════════════════════════════════════════════════════════════════════════

function getLofiIframe() {
  return document.getElementById("lofi-yt-player");
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
  const input = document.getElementById("lofi-url-input");
  const errorMsg = document.getElementById("lofi-error-msg");
  if (!input) return;

  input.classList.remove("error");
  if (errorMsg) errorMsg.classList.remove("visible");

  const videoId = lofiExtractId(input.value);
  if (!videoId) {
    input.classList.add("error");
    if (errorMsg) errorMsg.classList.add("visible");
    return;
  }
  playVideo(videoId, "now playing ♪");
  input.value = "";
}

function resetLofiDefault() {
  playVideo(YT_DEFAULT.id, YT_DEFAULT.title);
}

// ═══════════════════════════════════════════════════════════════════════════
// PLAYBACK CONTROLS
// ═══════════════════════════════════════════════════════════════════════════

function toggleLofiPlay() {
  const iframe = getLofiIframe();
  if (!iframe) return;
  const src = iframe.src;
  if (src.includes("autoplay=1")) {
    iframe.src = src.replace("autoplay=1", "autoplay=0");
  } else {
    iframe.src = src.replace("autoplay=0", "autoplay=1");
  }
}

function lofiSetVolume(val) {
  const icon = document.getElementById("lofi-vol-icon");
  if (!icon) return;
  icon.textContent =
    val == 0 ? "\u{1F507}" : val < 50 ? "\u{1F508}" : "\u{1F50A}";
}

// ═══════════════════════════════════════════════════════════════════════════
// NOW PLAYING — updates mini bar on home + title on player screen
// ═══════════════════════════════════════════════════════════════════════════

function updateNowPlaying(title) {
  if (!title) return;

  // Player screen title
  const playerTitle = document.getElementById("px-player-title");
  if (playerTitle) playerTitle.textContent = title;

  // Now-playing text in player scroll area
  const el = document.getElementById("yt-now-playing");
  if (el) el.textContent = "\u266A " + title;

  // Mini now-playing bar on home screen
  const npBar = document.getElementById("px-np-bar");
  const npTitle = document.getElementById("px-np-title");
  if (npBar) npBar.style.display = "";
  if (npTitle) npTitle.textContent = title;
}

// ═══════════════════════════════════════════════════════════════════════════
// CONTEXT DETECTION
// ═══════════════════════════════════════════════════════════════════════════

function isPixelTune() {
  return !!document.querySelector(".px-yt-player");
}

// ═══════════════════════════════════════════════════════════════════════════
// YOUTUBE SEARCH — proxied via search_youtube.php
// ═══════════════════════════════════════════════════════════════════════════

async function searchYouTube() {
  const input = document.getElementById("yt-search-input");
  const query = input ? input.value.trim() : "";
  if (!query) return;

  let data;
  try {
    const res = await fetch(
      `search_youtube.php?q=${encodeURIComponent(query)}`,
    );
    data = await res.json();
  } catch {
    return;
  }
  if (!data.items) return;

  const container = document.getElementById("yt-search-results");
  if (!container) return;
  container.innerHTML = "";

  const px = isPixelTune();

  data.items.forEach((item) => {
    const videoId = item.id.videoId;
    const title = item.snippet.title;
    const thumb = item.snippet.thumbnails.default.url;
    const safeTitle = title.replace(/'/g, "\\'").replace(/"/g, "&quot;");

    const div = document.createElement("div");

    if (px) {
      div.className = "px-yt-result-item";
      div.innerHTML =
        `<img src="${thumb}" alt="" />` +
        `<span class="result-title">${title}</span>` +
        `<span class="result-actions">` +
        `<button onclick="playVideo('${videoId}','${safeTitle}')">&#9654;</button>` +
        `<button onclick="addToQueue('${videoId}','${safeTitle}')">+Q</button>` +
        `</span>`;
    } else {
      div.className = "yt-result-item";
      div.innerHTML =
        `<img src="${thumb}" />` +
        `<span class="yt-result-title">${title}</span>` +
        `<span class="yt-result-actions">` +
        `<button onclick="swapLofiVideo('${videoId}','${safeTitle}')">&#9654; Play</button>` +
        `<button onclick="addToQueue('${videoId}','${safeTitle}')">+ Queue</button>` +
        `</span>`;
    }
    container.appendChild(div);
  });
}

// ═══════════════════════════════════════════════════════════════════════════
// QUEUE SYSTEM
// ═══════════════════════════════════════════════════════════════════════════

let queue = [];
let currentQueueIndex = -1;

function addToQueue(videoId, title) {
  queue.push({ videoId, title });
  renderQueue();
}

function playFromQueue(index) {
  currentQueueIndex = index;
  playVideo(queue[index].videoId, queue[index].title);
  renderQueue();
}

function renderQueue() {
  const container = document.getElementById("yt-queue");
  if (!container) return;
  container.innerHTML = "";

  const label = document.getElementById("yt-queue-label");
  if (label) label.style.display = queue.length ? "" : "none";

  const px = isPixelTune();

  queue.forEach((item, i) => {
    const div = document.createElement("div");
    const isActive = i === currentQueueIndex;

    if (px) {
      div.className = "px-yt-queue-item" + (isActive ? " active" : "");
      div.innerHTML =
        `<span class="queue-title">${item.title}</span>` +
        `<button onclick="playFromQueue(${i})">&#9654;</button>`;
    } else {
      div.className = "yt-queue-item" + (isActive ? " active" : "");
      div.innerHTML =
        `<span class="yt-queue-title">${item.title}</span>` +
        `<button onclick="playFromQueue(${i})">&#9654;</button>`;
    }
    container.appendChild(div);
  });
}

// ═══════════════════════════════════════════════════════════════════════════
// INIT — Load home screen content when DOM is ready
// ═══════════════════════════════════════════════════════════════════════════

document.addEventListener("DOMContentLoaded", () => {
  if (isPixelTune()) {
    loadLofiHome();
    loadRecentVideos();
  }
});
