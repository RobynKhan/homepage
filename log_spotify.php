<?php
// log_spotify.php — Log a Spotify track play to Supabase PostgreSQL
// Uses PHP session auth + PDO (same pattern as todo_api.php)

session_start();
require_once __DIR__ . '/auth_config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Allow admin OR Spotify-authenticated users
if (is_admin_logged_in()) {
    $username = current_admin()['username'];
} elseif (!empty($_SESSION['access_token'])) {
    // Use a hash of the access token as anonymous user id
    $username = 'spotify_' . substr(md5($_SESSION['access_token']), 0, 12);
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$body     = json_decode(file_get_contents('php://input'), true) ?? [];

$track_id  = trim($body['track_id'] ?? '');
$title     = trim($body['title'] ?? '');
$artist    = trim($body['artist'] ?? '');
$album_art = trim($body['album_art'] ?? '');

if (empty($track_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'track_id is required']);
    exit;
}

$db = getDB();

// Upsert: update last_played_at if this user+track already exists
$stmt = $db->prepare('
    INSERT INTO spotify_tracks (user_id, track_id, title, artist, album_art, last_played_at)
    VALUES (:user_id, :track_id, :title, :artist, :album_art, NOW())
    ON CONFLICT (user_id, track_id)
    DO UPDATE SET title = EXCLUDED.title,
                  artist = EXCLUDED.artist,
                  album_art = EXCLUDED.album_art,
                  last_played_at = NOW()
');
$stmt->execute([
    ':user_id'   => $username,
    ':track_id'  => $track_id,
    ':title'     => $title,
    ':artist'    => $artist,
    ':album_art' => $album_art,
]);

echo json_encode(['success' => true]);
