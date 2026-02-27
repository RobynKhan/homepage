// ─── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener("DOMContentLoaded", async () => {
  loadPlaylists();

  // Search on Enter
  const searchInput = document.getElementById("search-input");
  if (searchInput) {
    searchInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter") searchSongs();
    });
  }
});

// ─── Iframe Player ────────────────────────────────────────────────────────────

function playInEmbed(type, id) {
  const embed = document.getElementById("spotify-embed");
  if (!embed) return;
  embed.src = `https://open.spotify.com/embed/${type}/${id}?utm_source=generator&autoplay=1`;
}

// ─── Playlists ────────────────────────────────────────────────────────────────

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
    li.addEventListener("click", () => {
      // Play whole playlist in embed
      playInEmbed("playlist", pl.id);
      // Also load tracklist so user can pick individual songs
      loadTracks(pl.id, pl.name, li);
    });
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

// ─── Search ───────────────────────────────────────────────────────────────────

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

// ─── Render Tracks ────────────────────────────────────────────────────────────

function renderTracks(tracks) {
  const list = document.getElementById("track-list");
  if (!list) return;
  list.innerHTML = "";

  if (tracks.length === 0) {
    list.innerHTML = `<li style="color:var(--text-muted);padding:12px">No tracks found.</li>`;
    return;
  }

  tracks.forEach((track) => {
    const art = track.album.images[2]?.url || track.album.images[0]?.url || "";
    const trackId = track.uri.split(":")[2]; // extract ID from spotify:track:ID
    const li = document.createElement("li");

    li.innerHTML = `
      ${art ? `<img src="${art}" alt="" style="width:40px;height:40px;border-radius:6px;object-fit:cover;flex-shrink:0">` : ""}
      <div class="track-meta">
        <span class="track-title">${track.name}</span>
        <span class="track-artist">${track.artists.map((a) => a.name).join(", ")}</span>
      </div>
      <button class="add-queue-btn" title="Play this track" aria-label="Play">
        <i class="bi bi-play-fill"></i>
      </button>
    `;

    li.querySelector(".add-queue-btn").addEventListener("click", () => {
      playInEmbed("track", trackId);
    });

    // Clicking the track row also plays it
    li.querySelector(".track-meta").addEventListener("click", () => {
      playInEmbed("track", trackId);
    });

    list.appendChild(li);
  });
}
