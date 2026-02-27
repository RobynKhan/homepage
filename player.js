let player;
let deviceId;
let isPlaying = false;
let progressInterval = null;
let currentDuration = 0;
let currentPosition = 0;

window.onSpotifyWebPlaybackSDKReady = async () => {
  // Get token from PHP session via token.php
  const res = await fetch("token.php");
  if (!res.ok) {
    document.getElementById("track-name").textContent = "Not logged in.";
    return;
  }
  const data = await res.json();
  const token = data.token;

  player = new Spotify.Player({
    name: "My Web Player",
    getOAuthToken: (cb) => cb(token),
    volume: 0.8,
  });

  // ── Ready ──────────────────────────────────────────────────────────────
  player.addListener("ready", ({ device_id }) => {
    deviceId = device_id;
    console.log("Ready with Device ID", device_id);
    transferPlayback(device_id, token);
  });

  player.addListener("not_ready", ({ device_id }) => {
    console.warn("Device has gone offline", device_id);
  });

  // ── State changes ──────────────────────────────────────────────────────
  player.addListener("player_state_changed", (state) => {
    if (!state) return;

    const track = state.track_window.current_track;

    // Track info
    document.getElementById("track-name").textContent = track.name;
    document.getElementById("artist-name").textContent = track.artists
      .map((a) => a.name)
      .join(", ");

    const albumArt = document.getElementById("album-art");
    if (track.album.images.length > 0) {
      albumArt.src = track.album.images[0].url;
    }

    // Play / pause state
    isPlaying = !state.paused;
    updatePlayIcon();

    // Duration & position
    currentDuration = state.duration;
    currentPosition = state.position;
    updateProgressBar();
    updateTimestamps();

    // Tick the progress bar forward locally each second
    clearInterval(progressInterval);
    if (isPlaying) {
      progressInterval = setInterval(() => {
        currentPosition = Math.min(currentPosition + 1000, currentDuration);
        updateProgressBar();
        updateTimestamps();
      }, 1000);
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

  // ── Volume slider ──────────────────────────────────────────────────────
  const volumeBar = document.getElementById("volume-bar");
  if (volumeBar) {
    volumeBar.addEventListener("input", () => {
      player.setVolume(volumeBar.value / 100);
    });
  }

  // ── Progress bar scrubbing ─────────────────────────────────────────────
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
};

// ── Transfer playback to this browser tab ─────────────────────────────────
async function transferPlayback(deviceId, token) {
  await fetch("https://api.spotify.com/v1/me/player", {
    method: "PUT",
    headers: {
      Authorization: `Bearer ${token}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ device_ids: [deviceId], play: true }),
  });
}

// ── Playback controls ──────────────────────────────────────────────────────
function togglePlay() {
  player.togglePlay();
}

function nextTrack() {
  player.nextTrack();
}

function previousTrack() {
  player.previousTrack();
}

// ── UI helpers ─────────────────────────────────────────────────────────────
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
