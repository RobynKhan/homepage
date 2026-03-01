<?php

/**
 * ============================================================================
 * log_youtube.php — YouTube Video Watch Logger
 * ============================================================================
 *
 * Logs each YouTube video watch to the database (yt_urls table).
 * Accepts POST requests with JSON body containing:
 *   { url, title, thumbnail }
 *
 * Authorization: None — available to all visitors (uses session ID for guests).
 * Called by: youtube.js → swapLofiVideo() (fire-and-forget fetch)
 * ============================================================================
 */

// ─── Session Initialization & Dependencies ──────────────────────────────
session_start();
require_once __DIR__ . '/auth_config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// ─── HTTP Method Validation (POST only) ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ─── User Identification (admin username or guest session ID) ─────────────
if (is_admin_logged_in()) {
    $username = current_admin()['username'];
} else {
    $username = 'guest_' . session_id();
}
// ─── Parse Request Body & Validate URL ────────────────────────────────────
$body     = json_decode(file_get_contents('php://input'), true) ?? [];

$url       = trim($body['url'] ?? '');
$title     = trim($body['title'] ?? '');
$thumbnail = trim($body['thumbnail'] ?? '');

if (empty($url)) {
    http_response_code(400);
    echo json_encode(['error' => 'URL is required']);
    exit;
}

// ─── Insert Watch Record into Database ────────────────────────────────────
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
