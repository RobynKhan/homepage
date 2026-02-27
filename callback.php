<?php
session_start();
require __DIR__ . '/config.php';

// ─── CSRF state validation ─────────────────────────────────────────────────
$state = $_GET['state'] ?? '';
if (empty($state) || $state !== ($_SESSION['spotify_state'] ?? '')) {
    die('State mismatch — possible CSRF attack.');
}
unset($_SESSION['spotify_state']);

// ─── Check for auth code ───────────────────────────────────────────────────
$code = $_GET['code'] ?? null;
if (!$code) {
    die('Authorization failed: no code returned from Spotify.');
}

// ─── Exchange code for tokens ──────────────────────────────────────────────
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

// ─── Persist tokens in session ────────────────────────────────────────────
$_SESSION['access_token']  = $data['access_token'];
$_SESSION['refresh_token'] = $data['refresh_token'] ?? null;
$_SESSION['expires_at']    = time() + (int)($data['expires_in'] ?? 3600);

header('Location: index.php');
exit;
