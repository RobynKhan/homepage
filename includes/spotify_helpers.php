<?php

/**
 * ============================================================================
 * includes/spotify_helpers.php — Spotify API Token & Request Helpers
 * ============================================================================
 *
 * Provides shared utility functions for communicating with the Spotify Web API:
 *   - refreshSpotifyToken() — Refresh expired access tokens using refresh token
 *   - requireSpotifyToken() — Get a valid token or exit with 401
 *   - spotifyGet()          — Make authenticated GET requests with auto-retry
 *
 * Used by: playlists.php, recent.php, search.php, tracks.php
 * ============================================================================
 */
require_once __DIR__ . '/../config.php';

/**
 * Refresh the Spotify access token using the stored refresh token.
 * Updates $_SESSION on success.
 * @return bool true if refresh succeeded
 */
function refreshSpotifyToken(): bool
{
    $refresh = $_SESSION['refresh_token'] ?? null;
    if (!$refresh) return false;

    $res = @file_get_contents(
        'https://accounts.spotify.com/api/token',
        false,
        stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Authorization: Basic ' . base64_encode(SPOTIFY_CLIENT_ID . ':' . SPOTIFY_CLIENT_SECRET),
                ],
                'content' => http_build_query([
                    'grant_type'    => 'refresh_token',
                    'refresh_token' => $refresh,
                ]),
            ],
        ])
    );
    if ($res === false) return false;

    $data = json_decode($res, true);
    if (empty($data['access_token'])) return false;

    $_SESSION['access_token'] = $data['access_token'];
    $_SESSION['expires_at']   = time() + (int)($data['expires_in'] ?? 3600);
    if (!empty($data['refresh_token'])) {
        $_SESSION['refresh_token'] = $data['refresh_token'];
    }
    return true;
}

/**
 * Get a valid Spotify access token, auto-refreshing if expired.
 * Sends a JSON 401 and exits if no valid token is available.
 * @return string valid access token
 */
function requireSpotifyToken(): string
{
    $token = $_SESSION['access_token'] ?? null;

    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }

    // Auto-refresh if expired or about to expire within 60s
    if (isset($_SESSION['expires_at']) && time() >= ($_SESSION['expires_at'] - 60)) {
        if (!refreshSpotifyToken()) {
            http_response_code(401);
            echo json_encode(['error' => 'Token expired and refresh failed']);
            exit;
        }
        $token = $_SESSION['access_token'];
    }

    return $token;
}

/**
 * Make a GET request to the Spotify API with auto-retry on 401.
 * @param string $url  Full Spotify API URL
 * @param string $token  Bearer token
 * @return string  Raw JSON response body
 */
function spotifyGet(string $url, string $token): string
{
    $response = @file_get_contents($url, false, stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: Bearer $token",
            'ignore_errors' => true,
        ],
    ]));

    if ($response === false) {
        http_response_code(502);
        echo json_encode(['error' => 'Spotify API request failed']);
        exit;
    }

    // If Spotify returned 401, try refreshing and retrying once
    $statusLine = $http_response_header[0] ?? '';
    if (strpos($statusLine, '401') !== false) {
        if (refreshSpotifyToken()) {
            $token = $_SESSION['access_token'];
            $response = @file_get_contents($url, false, stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "Authorization: Bearer $token",
                    'ignore_errors' => true,
                ],
            ]));
        }
        if ($response === false) {
            http_response_code(502);
            echo json_encode(['error' => 'Spotify API request failed after refresh']);
            exit;
        }
    }

    return $response;
}
