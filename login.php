<?php
require 'config.php';

$scopes = 'streaming user-read-email user-read-private user-modify-playback-state';

$params = http_build_query([
    'client_id'     => SPOTIFY_CLIENT_ID,
    'response_type' => 'code',
    'redirect_uri'  => SPOTIFY_REDIRECT_URI,
    'scope'         => $scopes,
]);

header('Location: https://accounts.spotify.com/authorize?' . $params);
exit;
