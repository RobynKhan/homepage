<?php
session_start();
header('Content-Type: application/javascript');

$token     = isset($_SESSION['access_token']) ? $_SESSION['access_token']        : '';
$expiresAt = isset($_SESSION['expires_at'])   ? (int)$_SESSION['expires_at'] * 1000 : 0;
?>
// ─── Spotify token (injected by PHP) ──────────────────────────────────────
const SPOTIFY_TOKEN = <?php echo json_encode($token); ?>;
const TOKEN_EXPIRES_AT = <?php echo $expiresAt; ?>; // ms since epoch

// ─── Fetch a fresh token from the server when close to expiry ─────────────
async function getValidToken() {
const fiveMinutes = 5 * 60 * 1000;

if (TOKEN_EXPIRES_AT - Date.now() < fiveMinutes) {
  try {
  const res=await fetch('refresh_token.php');
  const data=await res.json();
  if (data.access_token) return data.access_token;
  } catch (e) {
  console.warn('Token refresh failed:', e);
  }
  }

  return SPOTIFY_TOKEN;
  }

  // ─── Spotify Web Playback SDK ──────────────────────────────────────────────
  window.onSpotifyWebPlaybackSDKReady=()=> {
  const player = new Spotify.Player({
  name: 'My Study App',
  getOAuthToken: async (cb) => {
  const token = await getValidToken();
  cb(token);
  },
  volume: 1.0,
  });

  // Ready
  player.addListener('ready', ({ device_id }) => {
  console.log('Ready with Device ID', device_id);
  window._spotify_device_id = device_id;
  });

  // Not Ready
  player.addListener('not_ready', ({ device_id }) => {
  console.log('Device ID has gone offline', device_id);
  });

  // Player state changed — update UI
  player.addListener('player_state_changed', (state) => {
  if (!state) return;

  const track = state.track_window.current_track;

  document.getElementById('track-name').textContent = track.name;
  document.getElementById('artist-name').textContent = track.artists.map((a) => a.name).join(', ');
  document.getElementById('album-art').src = track.album.images[0].url;

  // Play/pause icon swap
  const playBtn = document.getElementById('play-pause-btn');
  playBtn.innerHTML = state.paused
  ? '<i class="bi bi-play-fill"></i>'
  : '<i class="bi bi-pause-fill"></i>';

  // Progress bar
  const progress = document.getElementById('progress-bar');
  progress.max = state.duration;
  progress.value = state.position;

  // Time display
  document.getElementById('current-time').textContent = formatTime(state.position);
  document.getElementById('total-time').textContent = formatTime(state.duration);
  });

  // Controls
  document.getElementById('play-pause-btn').onclick = () => player.togglePlay();
  document.getElementById('prev-btn').onclick = () => player.previousTrack();
  document.getElementById('next-btn').onclick = () => player.nextTrack();

  // Volume
  document.getElementById('volume-bar').addEventListener('input', (e) => {
  player.setVolume(e.target.value / 100);
  });

  // Progress seek
  document.getElementById('progress-bar').addEventListener('change', (e) => {
  player.seek(Number(e.target.value));
  });

  player.connect();
  };

  // ─── Helpers ──────────────────────────────────────────────────────────────
  function formatTime(ms) {
  const totalSeconds = Math.floor(ms / 1000);
  const minutes = Math.floor(totalSeconds / 60);
  const seconds = totalSeconds % 60;
  return `${minutes}:${seconds.toString().padStart(2, '0')}`;
  }