<?php
session_start();
require 'config.php';

if (!isset($_SESSION['refresh_token'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No refresh token available']);
    exit;
}

$response = file_get_contents('https://accounts.spotify.com/api/token', false, stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode(SPOTIFY_CLIENT_ID . ':' . SPOTIFY_CLIENT_SECRET),
        ],
        'content' => http_build_query([
            'grant_type'    => 'refresh_token',
            'refresh_token' => $_SESSION['refresh_token'],
        ]),
    ],
]));

$data = json_decode($response, true);

if (!isset($data['access_token'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to refresh token']);
    exit;
}

// Update session with new token and expiry
$_SESSION['access_token'] = $data['access_token'];
$_SESSION['expires_at']   = time() + $data['expires_in'];

// If Spotify issues a new refresh token, save that too
if (isset($data['refresh_token'])) {
    $_SESSION['refresh_token'] = $data['refresh_token'];
}

echo json_encode([
    'access_token' => $data['access_token'],
    'expires_in'   => $data['expires_in'],
]);
exit;
