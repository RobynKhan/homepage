<?php
// db.php — Supabase PostgreSQL connection
// Credentials are loaded from Render environment variables:
//   DB_HOST, DB_NAME, DB_USER, DB_PASSWORD, DB_PORT

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;

    $host = getenv('DB_HOST');
    $name = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASSWORD');
    $port = getenv('DB_PORT') ?: '5432';

    if (!$host || !$name || !$user || !$pass) {
        http_response_code(500);
        echo json_encode(['error' => 'Missing DB environment variables']);
        exit;
    }

    try {
        $pdo = new PDO(
            "pgsql:host=$host;port=$port;dbname=$name;sslmode=require",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }

    return $pdo;
}
