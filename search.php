<?php
session_start();
header('Content-Type: application/json');

$token = $_SESSION['access_token'] ?? null;
$query = $_GET['q'] ?? null;

if (!$token || !$query) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing token or query']);
    exit;
}

$q = urlencode($query);
$response = file_get_contents(
    "https://api.spotify.com/v1/search?q=$q&type=track&limit=20",
    false,
    stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Authorization: Bearer $token"
        ]
    ])
);

echo $response;
