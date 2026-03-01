<?php

/**
 * ============================================================================
 * recent.php — Spotify Recently Played Tracks API Endpoint
 * ============================================================================
 *
 * Returns the authenticated user's 10 most recently played Spotify tracks
 * as JSON. Requires an active Spotify session.
 *
 * Called by: player.js → loadRecentlyPlayed() (AJAX fetch)
 * Returns:   JSON with recently played track objects from Spotify API
 * ============================================================================
 */
session_start();
require_once __DIR__ . '/includes/spotify_helpers.php';
header('Content-Type: application/json');

$token = requireSpotifyToken();
$response = spotifyGet('https://api.spotify.com/v1/me/player/recently-played?limit=10', $token);
echo $response;
