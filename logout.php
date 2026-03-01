<?php

/**
 * ============================================================================
 * logout.php — Spotify Session Logout
 * ============================================================================
 *
 * Destroys the entire PHP session (including Spotify tokens) and redirects
 * the user back to the main homepage. Used when disconnecting from Spotify.
 *
 * Called from: Spotify disconnect button in the PixelTune player panel
 * ============================================================================
 */
session_start();
session_destroy();
header('Location: index.php');
exit;
