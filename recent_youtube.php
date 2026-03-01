<?php

/**
 * ============================================================================
 * recent_youtube.php — Recently Watched YouTube Videos
 * ============================================================================
 *
 * Returns the most recent YouTube videos watched by the current admin user.
 * Used to populate the "Recently Played" list on the YouTube home screen.
 *
 * Response: { items: [ { url, title, thumbnail, watched_at }, ... ] }
 *
 * Authorization: None — available to all visitors (uses session ID for guests).
 * Called by: youtube.js → loadRecentVideos()
 * ============================================================================
 */

session_start();
require_once __DIR__ . '/auth_config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// ─── User Identification (admin username or guest session ID) ─────────────
if (is_admin_logged_in()) {
    $username = current_admin()['username'];
} else {
    $username = 'guest_' . session_id();
}

// ─── Query Recent Videos ──────────────────────────────────────────────────
$db = getDB();

$stmt = $db->prepare('
    SELECT url, title, thumbnail, watched_at
    FROM yt_urls
    WHERE user_id = :user_id
    ORDER BY watched_at DESC
    LIMIT 15
');
$stmt->execute([':user_id' => $username]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['items' => $rows]);
