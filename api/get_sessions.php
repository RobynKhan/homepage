<?php
// api/get_sessions.php — Returns session history (most recent first)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../db.php';

$limit  = min((int)($_GET['limit']  ?? 50), 200);  // max 200
$offset = max((int)($_GET['offset'] ?? 0),  0);

try {
    $stmt = $pdo->prepare(
        'SELECT id, session_type, duration, completed_at
           FROM sessions
          ORDER BY completed_at DESC
          LIMIT :limit OFFSET :offset'
    );
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $sessions = $stmt->fetchAll();

    // Total pomodoro count
    $countStmt = $pdo->query("SELECT COUNT(*) FROM sessions WHERE session_type = 'POMODORO'");
    $pomodoroCount = (int)$countStmt->fetchColumn();

    echo json_encode([
        'success'       => true,
        'pomodoro_count' => $pomodoroCount,
        'sessions'      => $sessions,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch sessions: ' . $e->getMessage()]);
}
