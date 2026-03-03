<?php

/**
 * ============================================================================
 * breakout_api.php — Breakout Scores API (Admin Only)
 * ============================================================================
 *
 * RESTful API endpoint for persisting Breakout game scores.
 * Supports two actions via the 'action' query parameter:
 *   - state      — GET  current admin's scores + all-time top score
 *   - finish_run — POST a completed run score; increments totals
 *
 * Authorization: Admin users only (via auth_config.php helpers).
 * Database:      Supabase PostgreSQL 'breakout_scores' table (via db.php).
 * Called by:     breakthrough.js (AJAX fetch)
 * ============================================================================
 */

// ─── Session Initialization & Dependencies ──────────────────────────────
session_start();
require_once __DIR__ . '/auth_config.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

// ─── Admin Authentication Check ────────────────────────────────────────────
if (!is_admin_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$admin    = current_admin();
$username = $admin['username'];
$action   = $_GET['action'] ?? '';
$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$db       = getDB();

// ─── Ensure Row Exists for Current Admin ──────────────────────────────────
$db->prepare('
    INSERT INTO breakout_scores (username)
    VALUES (:username)
    ON CONFLICT (username) DO NOTHING
')->execute([':username' => $username]);

// ─── Helper: Fetch Current Admin Row ──────────────────────────────────────
function fetchMe(PDO $db, string $username): array
{
    $stmt = $db->prepare('
        SELECT username, total_score, best_run_score
        FROM breakout_scores
        WHERE username = :username
    ');
    $stmt->execute([':username' => $username]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'username'       => $username,
        'total_score'    => 0,
        'best_run_score' => 0,
    ];
}

// ─── Helper: Fetch All-Time Top Score Row ─────────────────────────────────
function fetchTop(PDO $db): array
{
    $stmt = $db->query('
        SELECT username, best_run_score
        FROM breakout_scores
        ORDER BY best_run_score DESC
        LIMIT 1
    ');
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['username' => '', 'best_run_score' => 0];
}

// ─── Route to Action Handler ───────────────────────────────────────────────
switch ($action) {

    // ── STATE: Return current admin scores + all-time top ─────────────────
    case 'state':
        echo json_encode([
            'me'  => fetchMe($db, $username),
            'top' => fetchTop($db),
        ]);
        break;

    // ── FINISH_RUN: Record a completed run score ───────────────────────────
    case 'finish_run':
        $runScore = max(0, min(999999, (int)($body['run_score'] ?? 0)));

        $stmt = $db->prepare('
            UPDATE breakout_scores
            SET
                total_score    = total_score + :run_score,
                best_run_score = GREATEST(best_run_score, :run_score2),
                best_run_at    = CASE
                                     WHEN :run_score3 > best_run_score THEN NOW()
                                     ELSE best_run_at
                                 END,
                updated_at     = NOW()
            WHERE username = :username
        ');
        $stmt->execute([
            ':run_score'  => $runScore,
            ':run_score2' => $runScore,
            ':run_score3' => $runScore,
            ':username'   => $username,
        ]);

        echo json_encode([
            'me'  => fetchMe($db, $username),
            'top' => fetchTop($db),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}
