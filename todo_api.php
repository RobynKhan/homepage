<?php

/**
 * ============================================================================
 * todo_api.php — Todo/Quests CRUD API
 * ============================================================================
 *
 * RESTful API endpoint for managing admin todo items ("quests").
 * Supports four actions via the 'action' query parameter:
 *   - list   — GET  all todos for the logged-in admin (sorted by priority)
 *   - add    — POST a new todo (text, priority, due_date)
 *   - update — POST to toggle done/edit text/priority/due_date
 *   - delete — POST to remove a todo by ID
 *
 * Authorization: Admin users only (via auth_config.php helpers).
 * Database:      Supabase PostgreSQL 'todos' table (via db.php).
 * Called by:     todo_widget.php inline JavaScript (AJAX fetch)
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

// ─── Route to Action Handler ───────────────────────────────────────────────
switch ($action) {

    // ── LIST: Retrieve all todos for the current admin ────────────────────
    case 'list':
        $stmt = $db->prepare('
            SELECT * FROM todos
            WHERE username = :username
            ORDER BY
                done ASC,
                CASE priority WHEN \'high\' THEN 0 WHEN \'medium\' THEN 1 ELSE 2 END,
                due_date ASC NULLS LAST,
                created_at DESC
        ');
        $stmt->execute([':username' => $username]);
        $todos = $stmt->fetchAll();

        // Convert done from string to bool for JS
        $todos = array_map(function ($t) {
            $t['done'] = (bool)$t['done'];
            return $t;
        }, $todos);

        echo json_encode($todos);
        break;

    // ── ADD: Create a new todo item ─────────────────────────────────────────
    case 'add':
        $text     = trim($body['text'] ?? '');
        $priority = $body['priority'] ?? 'medium';
        $due_date = $body['due_date'] ?? null;
        $id       = uniqid('todo_', true);

        if (empty($text)) {
            http_response_code(400);
            echo json_encode(['error' => 'Task text is required']);
            exit;
        }

        if (!in_array($priority, ['low', 'medium', 'high'])) {
            $priority = 'medium';
        }

        $stmt = $db->prepare('
            INSERT INTO todos (id, username, text, done, priority, due_date, created_at)
            VALUES (:id, :username, :text, FALSE, :priority, :due_date, NOW())
            RETURNING *
        ');
        $stmt->execute([
            ':id'       => $id,
            ':username' => $username,
            ':text'     => $text,
            ':priority' => $priority,
            ':due_date' => $due_date,
        ]);

        $todo = $stmt->fetch();
        $todo['done'] = (bool)$todo['done'];
        echo json_encode($todo);
        break;

    // ── UPDATE: Modify an existing todo (toggle done, edit text/priority/due) ─
    case 'update':
        $id = $body['id'] ?? '';

        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID is required']);
            exit;
        }

        $fields = [];
        $params = [':id' => $id, ':username' => $username];

        if (isset($body['done'])) {
            $fields[] = 'done = :done';
            $params[':done'] = $body['done'] ? 'TRUE' : 'FALSE';
        }
        if (isset($body['text'])) {
            $fields[] = 'text = :text';
            $params[':text'] = trim($body['text']);
        }
        if (isset($body['priority'])) {
            $fields[] = 'priority = :priority';
            $params[':priority'] = $body['priority'];
        }
        if (isset($body['due_date'])) {
            $fields[] = 'due_date = :due_date';
            $params[':due_date'] = $body['due_date'] ?: null;
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nothing to update']);
            exit;
        }

        $stmt = $db->prepare('
            UPDATE todos
            SET ' . implode(', ', $fields) . '
            WHERE id = :id AND username = :username
            RETURNING *
        ');
        $stmt->execute($params);

        $todo = $stmt->fetch();
        if (!$todo) {
            http_response_code(404);
            echo json_encode(['error' => 'Todo not found']);
            exit;
        }

        $todo['done'] = (bool)$todo['done'];
        echo json_encode($todo);
        break;

    // ── DELETE: Remove a todo by ID ────────────────────────────────────────
    case 'delete':
        $id = $body['id'] ?? '';

        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID is required']);
            exit;
        }

        $stmt = $db->prepare('
            DELETE FROM todos
            WHERE id = :id AND username = :username
        ');
        $stmt->execute([':id' => $id, ':username' => $username]);

        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}
