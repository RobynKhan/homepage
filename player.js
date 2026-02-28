// ─── Tab Switching (Spotify / YouTube) ────────────────────────────────────────

function pxSwitchTab(tabName) {
  // Update tab buttons
  document
    .querySelectorAll(".px-tab")
    .forEach((t) => t.classList.remove("active"));
  const tabBtn = document.getElementById("px-tab-" + tabName);
  if (tabBtn) tabBtn.classList.add("active");

  // Update panels
  document
    .querySelectorAll(".px-tab-panel")
    .forEach((p) => p.classList.remove("active"));
  const panel = document.getElementById("px-panel-" + tabName);
  if (panel) panel.classList.add("active");

  // Lazy-init YouTube player when its tab becomes visible
  if (tabName === "youtube" && typeof initYouTubePlayer === "function") {
    // Short delay so the panel is rendered before YT.Player binds to the iframe
    setTimeout(initYouTubePlayer, 100);
  }
}

// ─── Screen Navigation ────────────────────────────────────────────────────────

let pxScreenHistory = ["home"];

function pxShowScreen(name) {
  const screens = document.querySelectorAll(".px-screen");
  const target = document.getElementById("px-screen-" + name);
  if (!target) return;

  // Mark current screen as behind, new one as active
  screens.forEach((s) => {
    s.classList.remove("active", "behind");
    if (s === target) {
      s.classList.add("active");
    }
  });

  // Push to history (avoid duplicates at top)
  if (pxScreenHistory[pxScreenHistory.length - 1] !== name) {
    pxScreenHistory.push(name);
  }
}

function pxGoBack() {
  if (pxScreenHistory.length > 1) {
    pxScreenHistory.pop();
    const prev = pxScreenHistory[pxScreenHistory.length - 1];
    pxShowScreen(prev);
    // Remove the duplicate push that pxShowScreen adds
    if (
      pxScreenHistory.length > 1 &&
      pxScreenHistory[pxScreenHistory.length - 1] ===
        pxScreenHistory[pxScreenHistory.length - 2]
    ) {
      pxScreenHistory.pop();
    }
  }
}

// ─── Now-Playing Bar Updates ──────────────────────────────────────────────────
function updateNowPlayingBar(trackName, artistName) {
  // Update both home and tracks screen NP bars
  ["", "2"].forEach((suffix) => {
    const titleEl = document.getElementById("px-np-title" + suffix);
    const artistEl = document.getElementById("px-np-artist" + suffix);
    if (titleEl) titleEl.textContent = trackName || "Unknown";
    if (artistEl) artistEl.textContent = artistName || "—";
  });
}

// ─── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", async () => {
  buildPxWaveform();
  buildNpWaveforms();

  if (typeof PX_LOGGED_IN !== "undefined" && PX_LOGGED_IN) {
    // Logged in: load user playlists + recently played
    loadPlaylists();
    loadRecentlyPlayed();
  } else {
    // Guest: load default Top Songs into home screen
    loadDefaultTopSongs();
  }

  // Search on Enter
  const searchInput = document.getElementById("search-input");
  if (searchInput) {
    searchInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") {
        searchSongs();
      }
    });
  }
});

// ─── Spotify IFrame Embed API ─────────────────────────────────────────────────

let spotifyEmbedController = null;
let spotifyEmbedReady = false;
let pendingSpotifyUri = null;

// Load the Spotify IFrame API script
(function loadSpotifyIFrameAPI() {
  const script = document.createElement("script");
  script.src = "https://open.spotify.com/embed/iframe-api/v1";
  script.async = true;
  document.head.appendChild(script);
})();

// Spotify calls this global callback when the API is ready
window.onSpotifyIframeApiReady = (IFrameAPI) => {
  const container = document.getElementById("spotify-embed-container");
  if (!container) return;

  const options = {
    uri: "spotify:playlist:37i9dQZF1DXcBWIGoYBM5M",
    width: "100%",
    height: "100%",
  };

  const callback = (controller) => {
    spotifyEmbedController = controller;
    spotifyEmbedReady = true;

    // If a song was queued before the API was ready, play it now
    if (pendingSpotifyUri) {
      controller.loadUri(pendingSpotifyUri);
      controller.play();
      pendingSpotifyUri = null;
    }
  };

  IFrameAPI.createController(container, options, callback);
};

function playInEmbed(type, id, trackName, artistName) {
  const uri = `spotify:${type}:${id}`;

  if (spotifyEmbedReady && spotifyEmbedController) {
    spotifyEmbedController.loadUri(uri);
    spotifyEmbedController.play();
  } else {
    // API not ready yet — queue it
    pendingSpotifyUri = uri;
  }

  if (trackName) updateNowPlayingBar(trackName, artistName);
  pxShowScreen("player");

  // Log track play to Supabase (fire-and-forget)
  supabase.auth.getUser().then(({ data }) => {
    if (!data.user) return;
    supabase.from("spotify_tracks").upsert(
      {
        user_id: data.user.id,
        track_id: id,
        title: trackName,
        artist: artistName,
        last_played_at: new Date().toISOString(),
      },
      { onConflict: "user_id,track_id" },
    );
  });
}

// ─── Default Top Songs (guest mode) ──────────────────────────────────────────

function loadDefaultTopSongs() {
  // The default playlist is already loaded via the IFrame API init
  // (spotify:playlist:37i9dQZF1DXcBWIGoYBM5M)

  // Populate the home track list with curated top songs
  const list = document.getElementById("track-list-home");
  if (!list) return;

  const topSongs = [
    { name: "Top 50 - Global", type: "playlist", id: "37i9dQZF1DXcBWIGoYBM5M" },
    {
      name: "Today's Top Hits",
      type: "playlist",
      id: "37i9dQZF1DXcBWIGoYBM5M",
    },
    {
      name: "Viral 50 - Global",
      type: "playlist",
      id: "37i9dQZEVXbLiRSasKsNU9",
    },
    { name: "All Out 2020s", type: "playlist", id: "37i9dQZF1DX2M1RktxUUHG" },
    { name: "Hot Hits USA", type: "playlist", id: "37i9dQZF1DX0kbJZpiYdZl" },
    { name: "RapCaviar", type: "playlist", id: "37i9dQZF1DX0XUsuxWHRQd" },
    { name: "mint", type: "playlist", id: "37i9dQZF1DX4dyzvuaRJ0n" },
    { name: "Chill Hits", type: "playlist", id: "37i9dQZF1DX4WYpdgoIcn6" },
    { name: "Lofi Beats", type: "playlist", id: "37i9dQZF1DWWQRwui0ExPn" },
    { name: "Pop Rising", type: "playlist", id: "37i9dQZF1DWUa8ZRTfalHk" },
  ];

  list.innerHTML = "";
  topSongs.forEach((song) => {
    const li = document.createElement("li");
    li.innerHTML = `
      <div style="width:34px;height:34px;background:var(--px-panel2);border:2px solid #000;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">&#127911;</div>
      <div class="track-meta">
        <span class="track-title">${song.name}</span>
        <span class="track-artist">Spotify Playlist</span>
      </div>
      <button class="add-queue-btn" title="Play" aria-label="Play">
        <i class="bi bi-play-fill"></i>
      </button>
    `;

    const playAction = () =>
      playInEmbed(song.type, song.id, song.name, "Spotify");
    li.querySelector(".add-queue-btn").addEventListener("click", playAction);
    li.querySelector(".track-meta").addEventListener("click", playAction);
    list.appendChild(li);
  });
}

// ─── Playlists (grid with cover art) ──────────────────────────────────────────

async function loadPlaylists() {
  try {
    const res = await fetch("playlists.php");
    const data = await res.json();
    if (!data.items) return;

    const grid = document.getElementById("playlist-grid");
    if (!grid) return;
    grid.innerHTML = "";

    data.items.forEach((pl) => {
      const art = (pl.images && pl.images[0]?.url) || "";
      const card = document.createElement("div");
      card.className = "px-pl-card";
      card.title = pl.name;
      card.innerHTML = `
        <div class="px-pl-cover">
          ${
            art
              ? `<img src="${art}" alt="" loading="lazy">`
              : `<span class="px-pl-placeholder">&#127925;</span>`
          }
          <button class="px-pl-play-btn" aria-label="Play ${pl.name}">
            <i class="bi bi-play-fill"></i>
          </button>
        </div>
        <div class="px-pl-name">${pl.name}</div>
        <div class="px-pl-count">${pl.tracks?.total ?? "—"} tracks</div>
      `;

      // Click cover/name → open tracklist
      card.addEventListener("click", (e) => {
        if (e.target.closest(".px-pl-play-btn")) return;
        loadTracks(pl.id, pl.name);
      });

      // Play button → play entire playlist in embed
      card.querySelector(".px-pl-play-btn").addEventListener("click", (e) => {
        e.stopPropagation();
        playInEmbed("playlist", pl.id, pl.name, "Playlist");
      });

      grid.appendChild(card);
    });
  } catch (err) {
    console.error("Failed to load playlists:", err);
  }
}

// ─── Recently Played ──────────────────────────────────────────────────────────

async function loadRecentlyPlayed() {
  try {
    const res = await fetch("recent.php");
    const data = await res.json();
    const items = data.items;
    if (!items || items.length === 0) return;

    const list = document.getElementById("recent-list");
    if (!list) return;
    list.innerHTML = "";

    // Deduplicate by track id, keep most recent
    const seen = new Set();
    const unique = [];
    for (const item of items) {
      const track = item.track;
      if (!track || seen.has(track.id)) continue;
      seen.add(track.id);
      unique.push(track);
    }

    unique.forEach((track) => {
      const art =
        track.album.images[2]?.url || track.album.images[0]?.url || "";
      const trackId = track.uri.split(":")[2];
      const trackName = track.name;
      const artistName = track.artists.map((a) => a.name).join(", ");
      const li = document.createElement("li");

      li.innerHTML = `
        ${
          art
            ? `<img src="${art}" alt="" class="px-recent-art">`
            : `<div class="px-recent-art px-recent-placeholder">&#127911;</div>`
        }
        <div class="track-meta">
          <span class="track-title">${trackName}</span>
          <span class="track-artist">${artistName}</span>
        </div>
        <button class="add-queue-btn" title="Play" aria-label="Play">
          <i class="bi bi-play-fill"></i>
        </button>
      `;

      const playAction = () =>
        playInEmbed("track", trackId, trackName, artistName, art);
      li.querySelector(".add-queue-btn").addEventListener("click", playAction);
      li.querySelector(".track-meta").addEventListener("click", playAction);
      list.appendChild(li);
    });
  } catch (err) {
    console.error("Failed to load recently played:", err);
  }
}

async function loadTracks(playlistId, playlistName) {
  document.getElementById("playlist-title").textContent = playlistName;
  const titleEl = document.getElementById("px-tracks-screen-title");
  if (titleEl) titleEl.textContent = playlistName;

  const searchInput = document.getElementById("search-input");
  if (searchInput) searchInput.value = "";

  // Navigate to tracks screen
  pxShowScreen("tracks");

  const res = await fetch(`tracks.php?id=${encodeURIComponent(playlistId)}`);
  const data = await res.json();
  renderTracks((data.items ?? []).map((i) => i.track).filter(Boolean));
}

// ─── Search ───────────────────────────────────────────────────────────────────

async function searchSongs() {
  const query = document.getElementById("search-input")?.value.trim();
  if (!query) return;

  const title = `Results: "${query}"`;
  document.getElementById("playlist-title").textContent = title;
  const titleEl = document.getElementById("px-tracks-screen-title");
  if (titleEl) titleEl.textContent = "SEARCH";

  // Navigate to tracks screen
  pxShowScreen("tracks");

  const res = await fetch(`search.php?q=${encodeURIComponent(query)}`);
  const data = await res.json();
  renderTracks(data.tracks?.items ?? []);
}

// ─── Render Tracks ────────────────────────────────────────────────────────────

function renderTracks(tracks) {
  const list = document.getElementById("track-list");
  if (!list) return;
  list.innerHTML = "";

  if (tracks.length === 0) {
    list.innerHTML = `<li style="color:var(--px-text2);padding:12px;font-family:'VT323',monospace;font-size:15px;border:none;box-shadow:none">No tracks found.</li>`;
    return;
  }

  tracks.forEach((track) => {
    const art = track.album.images[2]?.url || track.album.images[0]?.url || "";
    const trackId = track.uri.split(":")[2];
    const trackName = track.name;
    const artistName = track.artists.map((a) => a.name).join(", ");
    const li = document.createElement("li");

    li.innerHTML = `
      ${art ? `<img src="${art}" alt="">` : ""}
      <div class="track-meta">
        <span class="track-title">${trackName}</span>
        <span class="track-artist">${artistName}</span>
      </div>
      <button class="add-queue-btn" title="Play this track" aria-label="Play">
        <i class="bi bi-play-fill"></i>
      </button>
    `;

    li.querySelector(".add-queue-btn").addEventListener("click", () => {
      playInEmbed("track", trackId, trackName, artistName, art);
    });

    li.querySelector(".track-meta").addEventListener("click", () => {
      playInEmbed("track", trackId, trackName, artistName, art);
    });

    list.appendChild(li);
  });
}

// ─── PixelTune Waveform Decoration ────────────────────────────────────────────
function buildPxWaveform() {
  const el = document.getElementById("px-waveform");
  if (!el) return;
  for (let i = 0; i < 12; i++) {
    const bar = document.createElement("div");
    bar.className = "px-waveform-bar";
    const h = 3 + Math.random() * 11;
    bar.style.maxHeight = h + "px";
    bar.style.animationDelay = Math.random() * 1.5 + "s";
    bar.style.animationDuration = 0.5 + Math.random() * 0.8 + "s";
    el.appendChild(bar);
  }
}

// ─── Mini Waveforms in Now-Playing Bars ──────────────────────────────────────
function buildNpWaveforms() {
  ["px-np-wave", "px-np-wave2"].forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    for (let i = 0; i < 6; i++) {
      const bar = document.createElement("div");
      bar.className = "px-np-wave-bar";
      bar.style.maxHeight = 3 + Math.random() * 9 + "px";
      bar.style.animationDelay = Math.random() * 1 + "s";
      bar.style.animationDuration = 0.4 + Math.random() * 0.6 + "s";
      el.appendChild(bar);
    }
  });
}
