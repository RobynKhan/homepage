/**
 * ============================================================================
 * youtube.js — YouTube Player Controller
 * ============================================================================
 *
 * Self-contained YouTube player module for the PixelTune music panel.
 * Handles:
 *   - URL parsing and video ID extraction (all YouTube URL formats)
 *   - Video swapping via iframe replacement (no YT JS API needed)
 *   - Play/pause toggle via iframe src swap
 *   - Volume icon display updates
 *   - Loading overlay management
 *   - Play logging to log_youtube.php (fire-and-forget)
 *
 * Loaded by: index.php, player.html
 * ============================================================================
 */

// ─── YouTube Default Video Configuration ─────────────────────────────────
const YT_DEFAULT = {
  id: "76GStMlLF_Y",
  title: "why the rush?",
};

// ─── Get YouTube iframe reference ────────────────────────────────────────
function getLofiIframe() {
  return document.getElementById("lofi-yt-player");
}

// ─── Swap YouTube Video (replaces iframe to avoid API dependency) ─────────
function swapLofiVideo(videoId, title) {
  const loadingEl = document.getElementById("lofi-loading");
  if (loadingEl) loadingEl.classList.add("visible");

  // Log the watch (fire-and-forget, won't block or error visibly)
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

  // Remove old iframe completely to prevent stacking
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

// ─── Extract YouTube Video ID from Any URL Format ────────────────────────
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
  // Bare 11-char ID
  if (/^[a-zA-Z0-9_-]{11}$/.test(url)) return url;
  return null;
}

// ─── Load Video from URL Input Field ─────────────────────────────────────
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
  swapLofiVideo(videoId, "now playing ♪");
  input.value = "";
}

// ─── Reset to Default Video ──────────────────────────────────────────────
function resetLofiDefault() {
  swapLofiVideo(YT_DEFAULT.id, YT_DEFAULT.title);
}

// ─── Toggle Play/Pause (iframe src swap — no JS API needed) ──────────────
function toggleLofiPlay() {
  const iframe = getLofiIframe();
  if (!iframe) return;
  const src = iframe.src;
  // Toggle by swapping autoplay parameter and reloading iframe
  if (src.includes("autoplay=1")) {
    iframe.src = src.replace("autoplay=1", "autoplay=0");
  } else {
    iframe.src = src.replace("autoplay=0", "autoplay=1");
  }
}

// ─── Volume Icon Update (visual only — iframe handles actual volume) ─────
function lofiSetVolume(val) {
  const icon = document.getElementById("lofi-vol-icon");
  if (!icon) return;
  icon.textContent =
    val == 0 ? "\u{1F507}" : val < 50 ? "\u{1F508}" : "\u{1F50A}";
}
async function searchYouTube() {
  const query = document.getElementById("yt-search-input").value.trim();
  if (!query) return;

  const res = await fetch(`search_youtube.php?q=${encodeURIComponent(query)}`);
  const data = await res.json();

  const container = document.getElementById("yt-search-results");
  container.innerHTML = "";

  data.items.forEach((item) => {
    const videoId = item.id.videoId;
    const title = item.snippet.title;
    const thumb = item.snippet.thumbnails.default.url;

    const div = document.createElement("div");
    div.className = "yt-result-item";
    div.innerHTML = `
      <img src="${thumb}" />
      <span>${title}</span>
      <button onclick="swapLofiVideo('${videoId}', '${title.replace(/'/g, "\\'")}')">▶ Play</button>
      <button onclick="addToQueue('${videoId}', '${title.replace(/'/g, "\\'")}')">+ Queue</button>
    `;
    container.appendChild(div);
  });
}

// ─── Queue ────────────────────────────────────────────────────────────
let queue = [];
let currentQueueIndex = -1;

function addToQueue(videoId, title) {
  queue.push({ videoId, title });
  renderQueue();
}

function playFromQueue(index) {
  currentQueueIndex = index;
  swapLofiVideo(queue[index].videoId, queue[index].title);
}

function renderQueue() {
  let container = document.getElementById("yt-queue");
  if (!container) return;
  container.innerHTML = "";
  queue.forEach((item, i) => {
    const div = document.createElement("div");
    div.className =
      "yt-queue-item" + (i === currentQueueIndex ? " active" : "");
    div.innerHTML = `
      <span>${item.title}</span>
      <button onclick="playFromQueue(${i})">▶</button>
    `;
    container.appendChild(div);
  });
}
