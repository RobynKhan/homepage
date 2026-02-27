<?php
session_start();
header('Content-Type: application/json');

$token      = $_SESSION['access_token'] ?? null;
$playlistId = $_GET['id'] ?? null;

if (!$token || !$playlistId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing token or playlist ID']);
    exit;
}

$id = urlencode($playlistId);

$response = file_get_contents(
    "https://api.spotify.com/v1/playlists/{$id}/tracks?limit=50",
    false,
    stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: Bearer $token",
        ],
    ])
);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Failed to fetch tracks from Spotify']);
    exit;
}

echo $response;
