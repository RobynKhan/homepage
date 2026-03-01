<?php

/**
 * ============================================================================
 * login.php — Spotify OAuth Login Initiator
 * ============================================================================
 *
 * Generates a CSRF-safe state token, stores it in the session, and redirects
 * the user to Spotify's authorization page. After the user grants permission,
 * Spotify redirects back to callback.php with the authorization code.
 *
 * Flow: User clicks "Login with Spotify" → login.php → Spotify → callback.php
 * ============================================================================
 */
session_start();
require __DIR__ . '/config.php';

$state = bin2hex(random_bytes(16));
$_SESSION['spotify_state'] = $state;

$params = http_build_query([
    'response_type' => 'code',
    'client_id'     => SPOTIFY_CLIENT_ID,
    'scope'         => SPOTIFY_SCOPES,
    'redirect_uri'  => SPOTIFY_REDIRECT_URI,
    'state'         => $state,
]);

header('Location: https://accounts.spotify.com/authorize?' . $params);
exit;
