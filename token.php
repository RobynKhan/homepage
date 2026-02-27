<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['access_token'])) {
    echo json_encode(['token' => $_SESSION['access_token']]);
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
}
