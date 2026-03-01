<?php

/**
 * ============================================================================
 * playlists.php — Spotify User Playlists API Endpoint
 * ============================================================================
 *
 * Returns the authenticated user's Spotify playlists as JSON.
 * Fetches up to 50 playlists from the Spotify Web API.
 * Requires an active Spotify session (access_token in session).
 *
 * Called by: player.js → loadPlaylists() (AJAX fetch)
 * Returns:   JSON array of playlist objects from Spotify API
 * ============================================================================
 */
session_start();
require_once __DIR__ . '/includes/spotify_helpers.php';
header('Content-Type: application/json');

$token = requireSpotifyToken();
$response = spotifyGet('https://api.spotify.com/v1/me/playlists?limit=50', $token);
echo $response;
