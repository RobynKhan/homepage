<?php
session_start();

// Only clear Spotify-related session keys, preserve admin login
unset(
    $_SESSION['access_token'],
    $_SESSION['refresh_token'],
    $_SESSION['expires_at'],
    $_SESSION['spotify_state']
);

header('Location: index.php');
exit;
