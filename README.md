# Pomodoro Timer Dashboard

A full-featured Pomodoro productivity dashboard with an integrated YouTube music player, a todo/quest system, and customizable background themes. Built with PHP, JavaScript, and PostgreSQL (Supabase), containerized with Docker, and deployed on Render.

---

## Table of Contents

- [Features](#features)
- [Architecture Overview](#architecture-overview)
- [File Structure & Connections](#file-structure--connections)
- [Authentication System](#authentication-system)
- [YouTube Integration](#youtube-integration)
- [Todo/Quests System](#todoquests-system)
- [Database Schema](#database-schema)
- [Environment Variables](#environment-variables)
- [Deployment](#deployment)

---

## Features

- **Pomodoro Timer** — Configurable work/short-break/long-break cycles with automatic progression
- **YouTube Player (PixelTune)** — Search YouTube, paste URLs, queue videos, and play in a retro pixel-art player
- **YouTube Search** — Server-side YouTube Data API search with queue management
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
│    ├── includes/header.php        (top bar, nav, dock)          │
│    ├── timer.js                   (timer + nav + clock + themes)│
│    ├── youtube.js                 (YouTube embed + search)      │
│    ├── widgets/todo_widget.php    (quest list UI + inline JS)   │
│    ├── widgets/messages_widget.php(messaging UI + inline JS)    │
│    ├── widgets/breakthrough.js    (breakout game engine)        │
│    ├── styling.css                (all visual styles)           │
│    └── includes/footer.php        (closing tags + timer.js)     │
│                                                                 │
└───────────┬─────────────────────────────────────────────────────┘
            │  AJAX fetch requests
            ▼
┌───────────────────────────────┐
│   PHP API Endpoints           │
│                               │
│  widgets/todo_api.php         │   ← Todo CRUD
│  widgets/messages_api.php     │   ← Messaging CRUD
│  widgets/breakout_api.php     │   ← Breakout scores
│  log_youtube.php              │   ← Watch logging
│  search_youtube.php           │   ← YouTube Data API proxy
└───────────┬───────────────────┘
            │
            ▼
┌───────────────────────────────┐
│  Shared PHP Libraries         │
│                               │
│  config.php                   │   ← App constants + timer defaults
│  auth_config.php              │   ← Admin auth helpers
│  db.php                       │   ← PostgreSQL connection (PDO)
└───────────┬───────────────────┘
            │
            ▼
┌───────────────────────────────┐
│  Supabase PostgreSQL DB       │
│                               │
│  todos                        │   ← Task/quest items
│  yt_urls                      │   ← Watched YouTube URLs
│  admin_messages               │   ← Admin messages
│  breakout_scores              │   ← Breakout game scores
└───────────────────────────────┘
```

---

## File Structure & Connections

### Configuration & Core

| File              | Purpose                                                                                                              | Connected To                                                                                                  |
| ----------------- | -------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| `config.php`      | App name and timer duration defaults                                                                                 | index.php                                                                                                     |
| `auth_config.php` | Admin accounts (from env vars), session helpers (`is_admin_logged_in()`, `require_admin_login()`, `current_admin()`) | index.php, login_admin.php, logout_admin.php, widgets/todo_api.php, widgets/messages_api.php, log_youtube.php |
| `db.php`          | Singleton PDO connection to Supabase PostgreSQL                                                                      | widgets/todo_api.php, widgets/messages_api.php, widgets/breakout_api.php, log_youtube.php                     |
| `Dockerfile`      | PHP 8.2 container with PostgreSQL PDO for Render deployment                                                          | —                                                                                                             |

### Main Pages

| File              | Purpose                                                            | Connected To                                                                                                           |
| ----------------- | ------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------- |
| `index.php`       | Main dashboard — timer, YouTube music panel, todo widget, settings | config.php, auth_config.php, header.php, footer.php, widgets/todo_widget.php, widgets/messages_widget.php, styling.css |
| `login_admin.php` | Admin login form with password verification                        | auth_config.php → redirects to index.php                                                                               |

### Data Logging Endpoints

| File                 | Purpose                                                     | Connected To                              |
| -------------------- | ----------------------------------------------------------- | ----------------------------------------- |
| `log_youtube.php`    | Logs YouTube video watches to database                      | auth_config.php, db.php → `yt_urls` table |
| `search_youtube.php` | Proxies YouTube Data API search (keeps API key server-side) | auth_config.php → YouTube Data API v3     |

### Widgets (widgets/ folder)

| File                          | Purpose                                             | Connected To                                          |
| ----------------------------- | --------------------------------------------------- | ----------------------------------------------------- |
| `widgets/todo_widget.php`     | Renders todo widget UI (admin CRUD or guest locked) | auth_config.php, widgets/todo_api.php (via JS fetch)  |
| `widgets/todo_api.php`        | CRUD API for todos (list/add/update/delete)         | auth_config.php, db.php → `todos` table               |
| `widgets/messages_widget.php` | Admin messaging widget with compose/inbox/sent tabs | auth_config.php, widgets/messages_api.php (via fetch) |
| `widgets/messages_api.php`    | Messaging API (send/inbox/sent/read/delete)         | auth_config.php, db.php → `admin_messages` table      |
| `widgets/breakout_api.php`    | Breakout scores API (state/finish_run)              | auth_config.php, db.php → `breakout_scores` table     |
| `widgets/breakthrough.js`     | Canvas-based Breakout game engine                   | widgets/breakout_api.php (via JS fetch)               |

### Admin Authentication

| File               | Purpose                           | Connected To                             |
| ------------------ | --------------------------------- | ---------------------------------------- |
| `login_admin.php`  | Admin login page with styled form | auth_config.php → redirects to index.php |
| `logout_admin.php` | Clears admin session key          | auth_config.php → redirects to index.php |

### Shared Includes

| File                  | Purpose                                               | Connected To          |
| --------------------- | ----------------------------------------------------- | --------------------- |
| `includes/header.php` | HTML head, top bar, nav drawer, bottom dock, backdrop | Included by index.php |
| `includes/footer.php` | Loads timer.js, closes HTML                           | Included by index.php |

### Client-Side Scripts

| File                      | Purpose                                                                                            | Connected To                                    |
| ------------------------- | -------------------------------------------------------------------------------------------------- | ----------------------------------------------- |
| `youtube.js`              | YouTube embed controller — URL parsing, video swapping, search, queue, play/pause, volume, logging | log_youtube.php, search_youtube.php (via fetch) |
| `timer.js`                | Pomodoro timer engine, navigation (drawer + dock), theme switcher, live clock                      | DOM elements in index.php                       |
| `widgets/breakthrough.js` | Canvas Breakout game engine with touch/keyboard controls                                           | widgets/breakout_api.php (via fetch)            |
| `styling.css`             | All visual styles — layout, PixelTune retro theme, responsive breakpoints                          | Loaded by index.php                             |

### Assets

| File                 | Purpose                                             |
| -------------------- | --------------------------------------------------- |
| `themes/default.gif` | Default animated background (purple/lofi aesthetic) |
| `themes/snow.gif`    | Animated snow background                            |
| `themes/theme1.jpg`  | Alternative background theme 1                      |
| `themes/theme3.jpg`  | Alternative background theme 3                      |
| `themes/theme4.jpg`  | Alternative background theme 4                      |
| `themes/theme6.gif`  | Animated background theme 6                         |
| `themes/theme7.gif`  | Animated background theme 7                         |

---

## Authentication System

### Admin Login

- Credentials stored as bcrypt hashes in environment variables (`ADMIN1_USERNAME`, `ADMIN1_PASSWORD_HASH`, etc.)
- Session-based auth via `$_SESSION['admin_user']`
- Protects: widgets/todo_api.php, widgets/messages_api.php, widgets/breakout_api.php, log_youtube.php, admin UI features

---

## YouTube Integration

- YouTube player embedded in the dashboard (index.php)
- **Search** — Type a query to search YouTube via `search_youtube.php` proxy (keeps API key server-side)
- **Queue** — Add search results to a queue, play songs in sequence
- **URL input** — Paste any YouTube URL directly → video ID extracted via regex → iframe swapped
- Default video: `76GStMlLF_Y` (lofi stream)
- Each video watch logged to `yt_urls` table (admin only)
- Search requires `YOUTUBE_API_KEY` environment variable (YouTube Data API v3)

---

## Todo/Quests System

- Admin-only feature (guests see a locked state with login prompt)
- Full CRUD via `widgets/todo_api.php` with Supabase PostgreSQL backend
- Features: text input, done/undone toggle, priority levels (high/medium/low), due dates
- Sorted by: incomplete first → priority (high→low) → due date → newest
- Styled as a retro "Quests" widget with window controls

---

## Messages System

- Admin-only inter-admin messaging with inbox, sent, and compose tabs
- Full CRUD via `widgets/messages_api.php` with Supabase PostgreSQL backend
- Supabase Realtime subscription for live "new message" notifications
- Typewriter-style message viewer with animated cursor
- Unread badges on nav drawer, bottom dock, and top bar
- Full-screen bottom sheet on mobile with swipe-to-dismiss

---

## Breakout Game

- Admin-only canvas-based Atari Breakout clone
- Keyboard (Arrow/A/D) and mobile touch controls (drag, buttons, or both)
- Progressive difficulty: each level adds a row and increases ball speed
- Score persistence via `widgets/breakout_api.php` — tracks total, best run, and all-time top
- Retro pixel art style with neon brick palette

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

### `yt_urls` table

| Column     | Type      | Description       |
| ---------- | --------- | ----------------- |
| user_id    | TEXT      | Admin username    |
| url        | TEXT      | YouTube video URL |
| title      | TEXT      | Video title       |
| thumbnail  | TEXT      | Thumbnail URL     |
| watched_at | TIMESTAMP | Watch timestamp   |

### `admin_messages` table

| Column        | Type      | Description                   |
| ------------- | --------- | ----------------------------- |
| id            | UUID (PK) | Auto-generated message ID     |
| from_username | TEXT      | Sender admin username         |
| to_username   | TEXT      | Recipient admin username      |
| subject       | TEXT      | Message subject line          |
| body          | TEXT      | Message body                  |
| is_read       | BOOLEAN   | Read status (default `false`) |
| created_at    | TIMESTAMP | Send timestamp                |

### `breakout_scores` table

| Column         | Type      | Description                       |
| -------------- | --------- | --------------------------------- |
| username       | TEXT (PK) | Admin username (unique per admin) |
| total_score    | INTEGER   | Cumulative score across all runs  |
| best_run_score | INTEGER   | Highest single-run score          |
| best_run_at    | TIMESTAMP | When the best run was achieved    |
| updated_at     | TIMESTAMP | Last update timestamp             |

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

# Admin Accounts (bcrypt hashes)
ADMIN1_USERNAME=admin
ADMIN1_PASSWORD_HASH=$2y$10$...
ADMIN2_USERNAME=admin2
ADMIN2_PASSWORD_HASH=$2y$10$...

# YouTube Data API (for search feature)
YOUTUBE_API_KEY=your-youtube-api-key
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
