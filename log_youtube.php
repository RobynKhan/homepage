<?php
// log_youtube.php — Log a YouTube video watch to Supabase PostgreSQL
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

// Must be logged in as admin
if (!is_admin_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$admin    = current_admin();
$username = $admin['username'];
$body     = json_decode(file_get_contents('php://input'), true) ?? [];

$url       = trim($body['url'] ?? '');
$title     = trim($body['title'] ?? '');
$thumbnail = trim($body['thumbnail'] ?? '');

if (empty($url)) {
    http_response_code(400);
    echo json_encode(['error' => 'URL is required']);
    exit;
}

$db = getDB();

$stmt = $db->prepare('
    INSERT INTO yt_urls (user_id, url, title, thumbnail, watched_at)
    VALUES (:user_id, :url, :title, :thumbnail, NOW())
');
$stmt->execute([
    ':user_id'   => $username,
    ':url'       => $url,
    ':title'     => $title,
    ':thumbnail' => $thumbnail,
]);

echo json_encode(['success' => true]);
