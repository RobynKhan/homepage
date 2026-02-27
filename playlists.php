<?php
session_start();
header('Content-Type: application/json');

$token = $_SESSION['access_token'] ?? null;

if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$response = file_get_contents(
    'https://api.spotify.com/v1/me/playlists?limit=50',
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
    echo json_encode(['error' => 'Failed to fetch playlists from Spotify']);
    exit;
}

echo $response;
