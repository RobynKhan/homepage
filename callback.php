<?php

/**
 * ============================================================================
 * callback.php — Spotify OAuth Callback Handler
 * ============================================================================
 *
 * This is the redirect URI that Spotify calls after user authorization.
 * It handles:
 *   1. CSRF state validation (prevents cross-site request forgery)
 *   2. Exchanging the authorization code for access/refresh tokens
 *   3. Storing tokens in the PHP session
 *   4. Closing the popup window or redirecting to the main app
 *
 * Flow: login.php → Spotify Auth → callback.php → index.php
 * ============================================================================
 */
session_start();
require __DIR__ . '/config.php';

// ─── CSRF State Validation ─────────────────────────────────────────────────
$state = $_GET['state'] ?? '';
if (empty($state) || $state !== ($_SESSION['spotify_state'] ?? '')) {
    die('State mismatch — possible CSRF attack.');
}
unset($_SESSION['spotify_state']);

// ─── Authorization Code Extraction ─────────────────────────────────────────
$code = $_GET['code'] ?? null;
if (!$code) {
    die('Authorization failed: no code returned from Spotify.');
}

// ─── Token Exchange (authorization code → access + refresh tokens) ────────
$response = file_get_contents(
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
                'grant_type'   => 'authorization_code',
                'code'         => $code,
                'redirect_uri' => SPOTIFY_REDIRECT_URI,
            ]),
        ],
    ])
);

if ($response === false) {
    die('Token request to Spotify failed.');
}

$data = json_decode($response, true);

if (empty($data['access_token'])) {
    die('Spotify did not return an access token: ' . htmlspecialchars($response));
}

// ─── Persist Tokens in PHP Session ────────────────────────────────────────
$_SESSION['access_token']  = $data['access_token'];
$_SESSION['refresh_token'] = $data['refresh_token'] ?? null;
$_SESSION['expires_at']    = time() + (int)($data['expires_in'] ?? 3600);

// If opened in a new tab, close it and refresh the opener; otherwise redirect normally
echo '<!DOCTYPE html><html><head><title>Spotify Connected</title></head><body>
<script>
if (window.opener) {
    window.opener.location.reload();
    window.close();
} else {
    window.location.href = "index.php";
}
</script>
<noscript><meta http-equiv="refresh" content="0;url=index.php"></noscript>
<p>Spotify connected! Redirecting...</p>
</body></html>';
exit;
