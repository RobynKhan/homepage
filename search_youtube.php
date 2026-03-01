<?php

/**
 * ============================================================================
 * search_youtube.php — YouTube Search Proxy
 * ============================================================================
 * Proxies search requests to the YouTube Data API v3.
 * Keeps the API key server-side so it's never exposed to the browser.
 *
 * Called by: youtube.js → searchYouTube()
 * ============================================================================
 */

session_start();
require_once __DIR__ . '/auth_config.php';

header('Content-Type: application/json');

// ─── Auth Check ───────────────────────────────────────────────────────────
if (!is_admin_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// ─── Validate Query ───────────────────────────────────────────────────────
$query = trim($_GET['q'] ?? '');
if (empty($query)) {
    http_response_code(400);
    echo json_encode(['error' => 'Query required']);
    exit;
}

// ─── YouTube API Request ──────────────────────────────────────────────────
$apiKey = getenv('YOUTUBE_API_KEY'); // Replace with your actual API key
$url = "https://www.googleapis.com/youtube/v3/search?"
    . http_build_query([
        'part'        => 'snippet',
        'q'           => $query,
        'type'        => 'video',
        'maxResults'  => 10,
        'key'         => $apiKey,
    ]);

$response = @file_get_contents($url);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Failed to reach YouTube API']);
    exit;
}

echo $response;
