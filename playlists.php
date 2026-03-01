<?php
session_start();
require_once __DIR__ . '/includes/spotify_helpers.php';
header('Content-Type: application/json');

$token = requireSpotifyToken();
$response = spotifyGet('https://api.spotify.com/v1/me/playlists?limit=50', $token);
echo $response;
