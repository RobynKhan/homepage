<?php
// api/save_session.php — Records a completed pomodoro/break session
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
    exit;
}

$type     = $input['type']         ?? null;
$duration = (int)($input['duration'] ?? 0);

$allowedTypes = ['POMODORO', 'SHORTBREAK', 'LONGBREAK'];
if (!in_array($type, $allowedTypes, true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid session type']);
    exit;
}

if ($duration <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Duration must be a positive integer (seconds)']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO sessions (session_type, duration, completed_at) VALUES (:type, :duration, NOW())'
    );
    $stmt->execute([':type' => $type, ':duration' => $duration]);

    echo json_encode([
        'success' => true,
        'id'      => $pdo->lastInsertId(),
        'message' => 'Session saved'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save session: ' . $e->getMessage()]);
}
