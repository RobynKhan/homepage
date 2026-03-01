<?php

/**
 * ============================================================================
 * search.php — Spotify Track Search API Endpoint
 * ============================================================================
 *
 * Searches the Spotify catalog for tracks matching a user query.
 * Accepts a 'q' query parameter and returns up to 20 matching tracks as JSON.
 * Requires an active Spotify session.
 *
 * Called by: player.js → searchSongs() (AJAX fetch)
 * Query:    GET search.php?q=<search_term>
 * Returns:  JSON with matching track objects from Spotify API
 * ============================================================================
 */
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
