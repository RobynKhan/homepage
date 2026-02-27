let player;
let deviceId;
let isPlaying = false;
let spotifyToken;
let queue = [];
let currentDuration = 0;
let currentPosition = 0;
let progressInterval;

// ─── Init ────────────────────────────────────────────────────────────────────

window.onSpotifyWebPlaybackSDKReady = async () => {
  const res = await fetch("token.php");
  if (!res.ok) {
    document.getElementById("track-name").textContent = "Not logged in.";
    return;
  }
  const data = await res.json();
  spotifyToken = data.token;

  player = new Spotify.Player({
    name: "My Web Player",
    getOAuthToken: (cb) => cb(spotifyToken),
    volume: 0.8,
  });

  player.addListener("ready", ({ device_id }) => {
    deviceId = device_id;
    console.log("Ready with Device ID", device_id);
    transferPlayback(device_id);
    loadPlaylists();
  });

  player.addListener("not_ready", ({ device_id }) => {
    console.warn("Device has gone offline", device_id);
  });

  player.addListener("player_state_changed", (state) => {
    if (!state) return;
    const track = state.track_window.current_track;

    document.getElementById("track-name").textContent = track.name;
    document.getElementById("artist-name").textContent = track.artists
      .map((a) => a.name)
      .join(", ");

    const art = document.getElementById("album-art");
    if (track.album.images.length > 0) art.src = track.album.images[0].url;

    isPlaying = !state.paused;
    updatePlayIcon();

    currentDuration = state.duration;
    currentPosition = state.position;
    updateProgressBar();
    updateTimestamps();

    clearInterval(progressInterval);
    if (isPlaying) {
      progressInterval = setInterval(() => {
        currentPosition = Math.min(currentPosition + 1000, currentDuration);
        updateProgressBar();
        updateTimestamps();
      }, 1000);
    }

    // Auto-advance local queue when track ends
    if (state.paused && state.position === 0 && queue.length > 0) {
      playNext();
    }
  });

  player.addListener("initialization_error", ({ message }) =>
    console.error(message),
  );
  player.addListener("authentication_error", ({ message }) =>
    console.error(message),
  );
  player.addListener("account_error", ({ message }) => console.error(message));

  player.connect();

  // Volume slider
  const volumeBar = document.getElementById("volume-bar");
  if (volumeBar) {
    volumeBar.addEventListener("input", () => {
      player.setVolume(volumeBar.value / 100);
    });
  }

  // Progress bar scrubbing
  const progressBar = document.getElementById("progress-bar");
  if (progressBar) {
    progressBar.addEventListener("input", () => {
      if (!currentDuration) return;
      const seekMs = Math.floor((progressBar.value / 100) * currentDuration);
      player.seek(seekMs);
      currentPosition = seekMs;
      updateTimestamps();
    });
  }

  // Search on Enter
  const searchInput = document.getElementById("search-input");
  if (searchInput) {
    searchInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") searchSongs();
    });
  }
};

// ─── Playback ────────────────────────────────────────────────────────────────

async function transferPlayback(deviceId) {
  await fetch("https://api.spotify.com/v1/me/player", {
    method: "PUT",
    headers: {
      Authorization: `Bearer ${spotifyToken}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ device_ids: [deviceId], play: false }),
  });
}

async function playSong(uri) {
  if (!spotifyToken || !deviceId) return;
  await fetch(
    `https://api.spotify.com/v1/me/player/play?device_id=${deviceId}`,
    {
      method: "PUT",
      headers: {
        Authorization: `Bearer ${spotifyToken}`,
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ uris: [uri] }),
    },
  );
}

async function playNext() {
  if (queue.length === 0) return;
  const next = queue.shift();
  renderQueue();
  await playSong(next.uri);
}

function togglePlay() {
  player.togglePlay();
}
function nextTrack() {
  queue.length > 0 ? playNext() : player.nextTrack();
}
function previousTrack() {
  player.previousTrack();
}

// ─── UI Helpers ──────────────────────────────────────────────────────────────

function updatePlayIcon() {
  const icon = document.getElementById("play-icon");
  if (!icon) return;
  icon.className = isPlaying ? "bi bi-pause-fill" : "bi bi-play-fill";
}

function updateProgressBar() {
  const bar = document.getElementById("progress-bar");
  if (!bar || !currentDuration) return;
  bar.value = (currentPosition / currentDuration) * 100;
}

function updateTimestamps() {
  const current = document.getElementById("current-time");
  const total = document.getElementById("total-time");
  if (current) current.textContent = formatMs(currentPosition);
  if (total) total.textContent = formatMs(currentDuration);
}

function formatMs(ms) {
  const totalSec = Math.floor(ms / 1000);
  const min = Math.floor(totalSec / 60);
  const sec = String(totalSec % 60).padStart(2, "0");
  return `${min}:${sec}`;
}

// ─── Playlists & Tracks ──────────────────────────────────────────────────────

async function loadPlaylists() {
  const res = await fetch("playlists.php");
  const data = await res.json();
  const list = document.getElementById("playlist-list");
  if (!list || !data.items) return;
  list.innerHTML = "";

  data.items.forEach((pl) => {
    const li = document.createElement("li");
    li.textContent = pl.name;
    li.dataset.id = pl.id;
    li.title = pl.name;
    li.addEventListener("click", () => loadTracks(pl.id, pl.name, li));
    list.appendChild(li);
  });
}

async function loadTracks(playlistId, playlistName, liEl) {
  document
    .querySelectorAll("#playlist-list li")
    .forEach((l) => l.classList.remove("active"));
  if (liEl) liEl.classList.add("active");

  document.getElementById("playlist-title").textContent = playlistName;
  const searchInput = document.getElementById("search-input");
  if (searchInput) searchInput.value = "";

  const res = await fetch(`tracks.php?id=${encodeURIComponent(playlistId)}`);
  const data = await res.json();
  renderTracks((data.items ?? []).map((i) => i.track).filter(Boolean));
}

// ─── Search ──────────────────────────────────────────────────────────────────

async function searchSongs() {
  const query = document.getElementById("search-input")?.value.trim();
  if (!query) return;

  document.getElementById("playlist-title").textContent =
    `Results for "${query}"`;
  document
    .querySelectorAll("#playlist-list li")
    .forEach((l) => l.classList.remove("active"));

  const res = await fetch(`search.php?q=${encodeURIComponent(query)}`);
  const data = await res.json();
  renderTracks(data.tracks?.items ?? []);
}

// ─── Render Tracks ───────────────────────────────────────────────────────────

function renderTracks(tracks) {
  const list = document.getElementById("track-list");
  if (!list) return;
  list.innerHTML = "";

  tracks.forEach((track) => {
    const art = track.album.images[2]?.url || track.album.images[0]?.url || "";
    const li = document.createElement("li");

    li.innerHTML = `
      ${art ? `<img src="${art}" alt="" style="width:40px;height:40px;border-radius:6px;object-fit:cover;flex-shrink:0">` : ""}
      <div class="track-meta">
        <span class="track-title">${track.name}</span>
        <span class="track-artist">${track.artists.map((a) => a.name).join(", ")}</span>
      </div>
      <div style="display:flex;gap:6px;flex-shrink:0">
        <button class="add-queue-btn" title="Play now" aria-label="Play now">
          <i class="bi bi-play-fill"></i>
        </button>
        <button class="add-queue-btn" title="Add to queue" aria-label="Add to queue">
          <i class="bi bi-plus"></i>
        </button>
      </div>
    `;

    const [playBtn, queueBtn] = li.querySelectorAll(".add-queue-btn");
    playBtn.addEventListener("click", () => playSong(track.uri));
    queueBtn.addEventListener("click", () => addToQueue(track));
    list.appendChild(li);
  });
}

// ─── Queue ───────────────────────────────────────────────────────────────────

function addToQueue(track) {
  queue.push(track);
  renderQueue();
}

function removeFromQueue(index) {
  queue.splice(index, 1);
  renderQueue();
}

function clearQueue() {
  queue = [];
  renderQueue();
}

function renderQueue() {
  const ul = document.getElementById("queue-list");
  const count = document.getElementById("queue-count");
  if (!ul) return;
  if (count) count.textContent = `(${queue.length})`;
  ul.innerHTML = "";

  queue.forEach((track, i) => {
    const li = document.createElement("li");
    li.innerHTML = `
      <span class="q-name" title="${track.name} — ${track.artists[0].name}">
        ${track.name}
      </span>
      <button class="remove-btn" title="Remove" aria-label="Remove">&times;</button>
    `;
    li.querySelector(".q-name").addEventListener("click", () =>
      playSong(track.uri),
    );
    li.querySelector(".remove-btn").addEventListener("click", () =>
      removeFromQueue(i),
    );
    ul.appendChild(li);
  });
}
