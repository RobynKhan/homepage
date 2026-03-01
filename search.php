<?php
session_start();
require_once __DIR__ . '/includes/spotify_helpers.php';
header('Content-Type: application/json');

$query = $_GET['q'] ?? null;
if (!$query) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing query']);
    exit;
}

$token = requireSpotifyToken();
$q = urlencode($query);
$response = spotifyGet("https://api.spotify.com/v1/search?q=$q&type=track&limit=20", $token);
echo $response;
