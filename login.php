<?php
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
