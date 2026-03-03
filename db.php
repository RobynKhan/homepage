<?php

/**
 * ============================================================================
 * db.php — Database Connection (Supabase PostgreSQL)
 * ============================================================================
 *
 * Provides a singleton PDO connection to the Supabase PostgreSQL database.
 * Credentials are loaded from Render environment variables:
 *   DB_HOST, DB_NAME, DB_USER, DB_PASSWORD, DB_PORT
 *
 * Features:
 *   - Singleton pattern (one connection per request)
 *   - IPv4 DNS resolution to avoid IPv6 issues on Render
 *   - SSL-required connection (sslmode=require)
 *   - Exception-based error handling
 *
 * Used by: widgets/todo_api.php, widgets/messages_api.php,
 *          widgets/breakout_api.php, log_youtube.php
 * ============================================================================
 */

/**
 * Get or create a singleton PDO connection to the PostgreSQL database.
 *
 * @return PDO Active database connection
 */
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

    // Resolve hostname to IPv4 to avoid IPv6 connectivity issues on Render
    $ipv4 = gethostbyname($host);
    $connectHost = ($ipv4 !== $host) ? $ipv4 : $host;

    try {
        $pdo = new PDO(
            "pgsql:host=$connectHost;port=$port;dbname=$name;sslmode=require",
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
