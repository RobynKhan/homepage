<?php
require 'config.php';
session_start();

if (!isset($_GET['code'])) {
    die('No code returned from Spotify.');
}

$code = $_GET['code'];

$response = file_get_contents('https://accounts.spotify.com/api/token', false, stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode(SPOTIFY_CLIENT_ID . ':' . SPOTIFY_CLIENT_SECRET),
        ],
        'content' => http_build_query([
            'grant_type'   => 'authorization_code',
            'code'         => $code,
            'redirect_uri' => SPOTIFY_REDIRECT_URI,
        ]),
    ],
]));

$data = json_decode($response, true);

if (!isset($data['access_token'])) {
    die('Failed to get access token.');
}

// Store tokens in session
$_SESSION['access_token']  = $data['access_token'];
$_SESSION['refresh_token'] = $data['refresh_token'];
$_SESSION['expires_at']    = time() + $data['expires_in'];

// Redirect back to main app
header('Location: index.php');
exit;
