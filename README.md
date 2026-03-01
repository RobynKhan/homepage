# Pomodoro Timer Dashboard

A full-featured Pomodoro productivity dashboard with integrated Spotify and YouTube music players, a todo/quest system, and customizable background themes. Built with PHP, JavaScript, and PostgreSQL (Supabase), containerized with Docker, and deployed on Render.

---

## Table of Contents

- [Features](#features)
- [Architecture Overview](#architecture-overview)
- [File Structure & Connections](#file-structure--connections)
- [Authentication System](#authentication-system)
- [Spotify Integration](#spotify-integration)
- [YouTube Integration](#youtube-integration)
- [Todo/Quests System](#todoquests-system)
- [Database Schema](#database-schema)
- [Environment Variables](#environment-variables)
- [Deployment](#deployment)

---

## Features

- **Pomodoro Timer** — Configurable work/short-break/long-break cycles with automatic progression
- **Spotify Player (PixelTune)** — Browse playlists, search tracks, view recently played, and listen via Spotify IFrame Embed API
- **YouTube Player** — Paste any YouTube URL to embed and play videos independently alongside Spotify
- **Dual-Player Architecture** — Spotify and YouTube run in separate iframes; switching tabs never interrupts either player
- **Todo/Quests Widget** — Admin-only persistent task management with priority levels and due dates
- **Background Themes** — Switchable animated/static background themes
- **Responsive Design** — Desktop navigation drawer + mobile bottom dock with slide-up sheets
- **Admin Authentication** — Secure bcrypt-based admin login system

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        BROWSER (Client)                         │
│                                                                 │
│  index.php ──────── Main Dashboard Page                         │
│    ├── includes/header.php  (top bar, nav, dock)                │
│    ├── timer.js             (timer + nav + clock + themes)       │
│    ├── player.js            (Spotify embed + playlists + search) │
│    ├── youtube.js           (YouTube embed + URL input + swap)   │
│    ├── todo_widget.php      (quest list UI + inline JS)         │
│    ├── styling.css          (all visual styles)                  │
│    └── includes/footer.php  (closing tags + timer.js load)      │
│                                                                 │
│  player.html ────── Standalone Music Player (Spotify + YouTube)  │
│    ├── player.js            (Spotify embed controller)           │
│    └── youtube.js           (YouTube embed controller)           │
│  frerein.html ───── Static Prototype / Development Sandbox       │
└───────────┬──────────────────────────────┬──────────────────────┘
            │  AJAX fetch requests         │  OAuth redirect
            ▼                              ▼
┌───────────────────────────┐   ┌─────────────────────────┐
│   PHP API Endpoints       │   │  Spotify OAuth Flow     │
│                           │   │                         │
│  playlists.php            │   │  login.php              │
│  recent.php               │   │    ↓ redirect           │
│  search.php               │   │  Spotify Auth Server    │
│  tracks.php               │   │    ↓ callback           │
│  todo_api.php             │   │  callback.php           │
│  log_spotify.php          │   │    ↓ tokens → session   │
│  log_youtube.php          │   │  (back to index.php)    │
└───────────┬───────────────┘   └─────────────────────────┘
            │
            ▼
┌───────────────────────────┐
│  Shared PHP Libraries     │
│                           │
│  config.php               │  ← App constants + Spotify creds
│  auth_config.php          │  ← Admin auth helpers
│  db.php                   │  ← PostgreSQL connection (PDO)
│  includes/spotify_helpers │  ← Token refresh + API requests
└───────────┬───────────────┘
            │
            ▼
┌───────────────────────────┐
│  Supabase PostgreSQL DB   │
│                           │
│  todos                    │  ← Task/quest items
│  spotify_tracks           │  ← Played track log
│  yt_urls                  │  ← Watched YouTube URLs
└───────────────────────────┘
```

---

## File Structure & Connections

### Configuration & Core

| File              | Purpose                                                                                                              | Connected To                                                                                                  |
| ----------------- | -------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| `config.php`      | App name, timer defaults, Spotify OAuth credentials                                                                  | index.php, login.php, callback.php, spotify_helpers.php                                                       |
| `auth_config.php` | Admin accounts (from env vars), session helpers (`is_admin_logged_in()`, `require_admin_login()`, `current_admin()`) | index.php, login_admin.php, logout_admin.php, todo_api.php, todo_widget.php, log_spotify.php, log_youtube.php |
| `db.php`          | Singleton PDO connection to Supabase PostgreSQL                                                                      | todo_api.php, log_spotify.php, log_youtube.php                                                                |
| `Dockerfile`      | PHP 8.2 container with PostgreSQL PDO for Render deployment                                                          | —                                                                                                             |

### Main Pages

| File              | Purpose                                                                                 | Connected To                                                                                                       |
| ----------------- | --------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| `index.php`       | Main dashboard — timer, music panel (Spotify + YouTube tabs), todo widget, settings     | config.php, auth_config.php, header.php, footer.php, todo_widget.php, player.js, youtube.js, timer.js, styling.css |
| `player.html`     | Standalone 3-column music player with Spotify + YouTube tabs, playlists sidebar, search | player.js, youtube.js, styling.css, Spotify IFrame Embed API                                                       |
| `frerein.html`    | Static prototype/sandbox for layout testing                                             | timer.js, styling.css                                                                                              |
| `login_admin.php` | Admin login form with password verification                                             | auth_config.php → redirects to index.php                                                                           |

### Spotify OAuth Flow

| File           | Purpose                                                                  | Connected To                             |
| -------------- | ------------------------------------------------------------------------ | ---------------------------------------- |
| `login.php`    | Generates CSRF state, redirects to Spotify authorization                 | config.php → Spotify Auth → callback.php |
| `callback.php` | Receives auth code from Spotify, exchanges for tokens, stores in session | config.php → redirects to index.php      |
| `logout.php`   | Destroys Spotify session (all session data)                              | Redirects to index.php                   |

### Spotify API Endpoints (called by player.js via AJAX)

| File            | Purpose                                        | Connected To                      |
| --------------- | ---------------------------------------------- | --------------------------------- |
| `playlists.php` | Returns user's Spotify playlists (JSON)        | spotify_helpers.php → Spotify API |
| `recent.php`    | Returns recently played tracks (JSON)          | spotify_helpers.php → Spotify API |
| `search.php`    | Searches Spotify catalog for tracks (JSON)     | spotify_helpers.php → Spotify API |
| `tracks.php`    | Returns tracks from a specific playlist (JSON) | spotify_helpers.php → Spotify API |

### Data Logging Endpoints

| File              | Purpose                                       | Connected To                                     |
| ----------------- | --------------------------------------------- | ------------------------------------------------ |
| `log_spotify.php` | Logs Spotify track plays to database (upsert) | auth_config.php, db.php → `spotify_tracks` table |
| `log_youtube.php` | Logs YouTube video watches to database        | auth_config.php, db.php → `yt_urls` table        |

### Todo System

| File              | Purpose                                             | Connected To                                 |
| ----------------- | --------------------------------------------------- | -------------------------------------------- |
| `todo_api.php`    | CRUD API for todos (list/add/update/delete)         | auth_config.php, db.php → `todos` table      |
| `todo_widget.php` | Renders todo widget UI (admin CRUD or guest locked) | auth_config.php, todo_api.php (via JS fetch) |

### Admin Authentication

| File               | Purpose                                             | Connected To                             |
| ------------------ | --------------------------------------------------- | ---------------------------------------- |
| `login_admin.php`  | Admin login page with styled form                   | auth_config.php → redirects to index.php |
| `logout_admin.php` | Clears admin session key (preserves Spotify tokens) | auth_config.php → redirects to index.php |

### Shared Includes

| File                           | Purpose                                                | Connected To                                                          |
| ------------------------------ | ------------------------------------------------------ | --------------------------------------------------------------------- |
| `includes/header.php`          | HTML head, top bar, nav drawer, bottom dock, backdrop  | Included by index.php                                                 |
| `includes/footer.php`          | Loads timer.js, closes HTML                            | Included by index.php                                                 |
| `includes/spotify_helpers.php` | Token refresh, token validation, authenticated API GET | config.php; used by playlists.php, recent.php, search.php, tracks.php |

### Client-Side Scripts

| File          | Purpose                                                                                                                                                                      | Connected To                                                                   |
| ------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| `player.js`   | Unified Spotify controller — page-aware (dashboard PixelTune + standalone 3-column), tab switching, screen nav, playlists, search, track rendering, Spotify IFrame Embed API | playlists.php, recent.php, search.php, tracks.php, log_spotify.php (via fetch) |
| `youtube.js`  | YouTube embed controller — URL parsing, video swapping, play/pause toggle, volume icon, logging                                                                              | log_youtube.php (via fetch)                                                    |
| `timer.js`    | Pomodoro timer engine, navigation (drawer + dock), theme switcher, live clock                                                                                                | DOM elements in index.php / frerein.html                                       |
| `styling.css` | All visual styles — layout, glass effects, PixelTune retro theme, responsive breakpoints                                                                                     | Loaded by index.php, player.html, frerein.html                                 |

### Assets

| File                 | Purpose                                                    |
| -------------------- | ---------------------------------------------------------- |
| `themes/theme 2.gif` | Default animated background (purple/lofi aesthetic)        |
| `themes/theme1.jpg`  | Alternative background theme 1                             |
| `themes/default.jpg` | Alternative background theme 2 (mapped to "theme2" option) |
| `themes/theme3.jpg`  | Alternative background theme 3                             |

---

## Authentication System

### Admin Login

- Credentials stored as bcrypt hashes in environment variables (`ADMIN1_USERNAME`, `ADMIN1_PASSWORD_HASH`, etc.)
- Session-based auth via `$_SESSION['admin_user']`
- Protects: todo_api.php, log_youtube.php, admin UI features

### Spotify Login

- OAuth 2.0 Authorization Code flow
- Tokens stored in `$_SESSION['access_token']`, `$_SESSION['refresh_token']`
- Auto-refresh on expiry (60-second buffer) via `spotify_helpers.php`
- App state (timer, lofi, todos, panels) saved to `localStorage` before redirect and restored after

---

## Spotify Integration

1. User clicks "Login with Spotify" → `login.php` generates CSRF state and redirects to Spotify
2. User authorizes → Spotify redirects to `callback.php` with auth code
3. `callback.php` exchanges code for access + refresh tokens, stores in session
4. `player.js` loads playlists, recently played, and handles search via AJAX to PHP endpoints
5. Playback via Spotify IFrame Embed API (`onSpotifyIframeApiReady`)
6. Each track play is logged to the `spotify_tracks` database table

---

## YouTube Integration

- YouTube player available on **both** pages: dashboard (index.php) and standalone player (player.html)
- Self-contained controller in `youtube.js` — paste any YouTube URL → video ID extracted via regex → iframe swapped
- Default video: `76GStMlLF_Y` (lofi stream)
- Runs in an independent iframe from Spotify — switching between tabs never interrupts either player
- Each video watch logged to `yt_urls` table (admin only)
- No YouTube API key required — uses plain iframe embeds

---

## Todo/Quests System

- Admin-only feature (guests see a locked state with login prompt)
- Full CRUD via `todo_api.php` with Supabase PostgreSQL backend
- Features: text input, done/undone toggle, priority levels (high/medium/low), due dates
- Sorted by: incomplete first → priority (high→low) → due date → newest
- Styled as a retro "Quests" widget with window controls

---

## Database Schema

### `todos` table

| Column     | Type      | Description                         |
| ---------- | --------- | ----------------------------------- |
| id         | TEXT (PK) | Unique ID (generated with `uniqid`) |
| username   | TEXT      | Admin username (owner)              |
| text       | TEXT      | Task description                    |
| done       | BOOLEAN   | Completion status                   |
| priority   | TEXT      | `high`, `medium`, or `low`          |
| due_date   | DATE      | Optional due date                   |
| created_at | TIMESTAMP | Creation timestamp                  |

### `spotify_tracks` table

| Column         | Type      | Description                            |
| -------------- | --------- | -------------------------------------- |
| user_id        | TEXT      | Admin username or hashed Spotify token |
| track_id       | TEXT      | Spotify track ID                       |
| title          | TEXT      | Track title                            |
| artist         | TEXT      | Artist name(s)                         |
| album_art      | TEXT      | Album art URL                          |
| last_played_at | TIMESTAMP | Most recent play time                  |
| **PK**         |           | `(user_id, track_id)`                  |

### `yt_urls` table

| Column     | Type      | Description       |
| ---------- | --------- | ----------------- |
| user_id    | TEXT      | Admin username    |
| url        | TEXT      | YouTube video URL |
| title      | TEXT      | Video title       |
| thumbnail  | TEXT      | Thumbnail URL     |
| watched_at | TIMESTAMP | Watch timestamp   |

---

## Environment Variables

Set these in your hosting platform (Render dashboard) or `.env` file:

```env
# Database (Supabase PostgreSQL)
DB_HOST=your-db-host.supabase.co
DB_NAME=postgres
DB_USER=postgres
DB_PASSWORD=your-password
DB_PORT=5432

# Supabase Client (used in index.php for JS client)
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_ANON_KEY=your-anon-key

# Spotify OAuth
SPOTIFY_CLIENT_ID=your-spotify-client-id
SPOTIFY_CLIENT_SECRET=your-spotify-client-secret
SPOTIFY_REDIRECT_URI=https://your-app.onrender.com/callback.php

# Admin Accounts (bcrypt hashes)
ADMIN1_USERNAME=admin
ADMIN1_PASSWORD_HASH=$2y$10$...
ADMIN2_USERNAME=admin2
ADMIN2_PASSWORD_HASH=$2y$10$...
```

Generate password hashes with:

```bash
php -r "echo password_hash('yourPassword', PASSWORD_BCRYPT);"
```

---

## Deployment

### Docker (Render)

```bash
docker build -t pomodoro-timer .
docker run -p 8000:8000 --env-file .env pomodoro-timer
```

The app is served by PHP's built-in web server on port **8000**.

### Local Development

```bash
php -S localhost:8000
```

Ensure all environment variables are set (e.g., via `export` or an `.env` loader).
