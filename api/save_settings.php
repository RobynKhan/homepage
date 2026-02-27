<?php
// api/save_settings.php — Persist timer settings to the database
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

$allowedThemes = ['default', 'theme1', 'theme2', 'theme3', 'theme4'];
$theme = in_array($input['background_theme'] ?? '', $allowedThemes, true)
    ? $input['background_theme']
    : 'default';

$data = [
    ':pomodoro'    => max(1, min((int)($input['pomodoro_duration']          ?? 25), 120)),
    ':short_break' => max(1, min((int)($input['short_break_duration']       ?? 5),  60)),
    ':long_break'  => max(1, min((int)($input['long_break_duration']        ?? 15), 60)),
    ':until_long'  => max(1, min((int)($input['pomodoros_until_long_break'] ?? 4),  10)),
    ':theme'       => $theme,
    ':id'          => 1, // single-row settings table
];

try {
    // Upsert – update existing row (id = 1) or insert if none exists
    $stmt = $pdo->prepare(
        'INSERT INTO settings
             (id, pomodoro_duration, short_break_duration, long_break_duration, pomodoros_until_long_break, background_theme)
         VALUES (:id, :pomodoro, :short_break, :long_break, :until_long, :theme)
         ON DUPLICATE KEY UPDATE
             pomodoro_duration          = VALUES(pomodoro_duration),
             short_break_duration       = VALUES(short_break_duration),
             long_break_duration        = VALUES(long_break_duration),
             pomodoros_until_long_break = VALUES(pomodoros_until_long_break),
             background_theme           = VALUES(background_theme)'
    );
    $stmt->execute($data);

    echo json_encode(['success' => true, 'message' => 'Settings saved']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save settings: ' . $e->getMessage()]);
}
