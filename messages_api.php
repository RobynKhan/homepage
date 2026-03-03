<?php

/**
 * messages_api.php — Admin Messaging API
 * Handles send / inbox / sent / mark-read / unread-count
 * Uses direct PDO connection via db.php
 */
session_start();
require_once __DIR__ . '/auth_config.php';
require_once __DIR__ . '/db.php';
require_admin_login();

header('Content-Type: application/json');

$me  = current_admin()['username'];
$pdo = getDB();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'inbox':
        $stmt = $pdo->prepare(
            'SELECT * FROM admin_messages WHERE to_username = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$me]);
        echo json_encode($stmt->fetchAll());
        break;

    case 'sent':
        $stmt = $pdo->prepare(
            'SELECT * FROM admin_messages WHERE from_username = ? ORDER BY created_at DESC'
        );
        $stmt->execute([$me]);
        echo json_encode($stmt->fetchAll());
        break;

    case 'unread_count':
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS count FROM admin_messages WHERE to_username = ? AND is_read = false'
        );
        $stmt->execute([$me]);
        echo json_encode($stmt->fetch());
        break;

    case 'read':
        $id = $_POST['id'] ?? '';
        if (!$id) {
            echo json_encode(['ok' => false, 'error' => 'Missing id']);
            break;
        }
        $stmt = $pdo->prepare(
            'UPDATE admin_messages SET is_read = true WHERE id = ? AND to_username = ?'
        );
        $stmt->execute([$id, $me]);
        echo json_encode(['ok' => true]);
        break;

    case 'send':
        $to      = trim($_POST['to'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $body    = trim($_POST['body'] ?? '');

        $valid_usernames = array_keys(ADMIN_ACCOUNTS);
        if (!in_array($to, $valid_usernames)) {
            echo json_encode(['ok' => false, 'error' => 'Recipient not found.']);
            break;
        }
        if (!$subject || !$body) {
            echo json_encode(['ok' => false, 'error' => 'Missing fields.']);
            break;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO admin_messages (from_username, to_username, subject, body)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$me, $to, $subject, $body]);
        echo json_encode(['ok' => true]);
        break;

    case 'delete':
        $id = $_POST['id'] ?? '';
        if (!$id) {
            echo json_encode(['ok' => false, 'error' => 'Missing id']);
            break;
        }
        $stmt = $pdo->prepare(
            'DELETE FROM admin_messages WHERE id = ? AND (to_username = ? OR from_username = ?)'
        );
        $stmt->execute([$id, $me, $me]);
        if ($stmt->rowCount() === 0) {
            echo json_encode(['ok' => false, 'error' => 'Not found or not allowed']);
        } else {
            echo json_encode(['ok' => true]);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
