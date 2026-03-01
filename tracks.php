<?php

/**
 * ============================================================================
 * tracks.php — Spotify Playlist Tracks API Endpoint
 * ============================================================================
 *
 * Returns up to 50 tracks from a given Spotify playlist.
 * Accepts a playlist 'id' query parameter.
 * Requires an active Spotify session.
 *
 * Called by: player.js → loadTracks() (AJAX fetch)
 * Query:    GET tracks.php?id=<spotify_playlist_id>
 * Returns:  JSON with playlist track objects from Spotify API
 * ============================================================================
 */
session_start();
require_once __DIR__ . '/includes/spotify_helpers.php';
header('Content-Type: application/json');

$playlistId = $_GET['id'] ?? null;
if (!$playlistId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing playlist ID']);
    exit;
}

$token = requireSpotifyToken();
$id = urlencode($playlistId);
$response = spotifyGet("https://api.spotify.com/v1/playlists/{$id}/tracks?limit=50", $token);
echo $response;
