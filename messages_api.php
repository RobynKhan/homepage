<?php

/**
 * messages_api.php — Admin Messaging API
 * Handles send / inbox / sent / mark-read / unread-count
 * Uses Supabase REST API with service role key
 */
session_start();
require_once __DIR__ . '/auth_config.php';
require_admin_login();

header('Content-Type: application/json');

$supabase_url = getenv('SUPABASE_URL');
$supabase_key = getenv('SUPABASE_SERVICE_KEY'); // service role key
$me = current_admin()['username'];

function sb_get(string $endpoint): array
{
    global $supabase_url, $supabase_key;
    $ch = curl_init($supabase_url . '/rest/v1/' . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "apikey: $supabase_key",
            "Authorization: Bearer $supabase_key",
        ],
    ]);
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return is_array($res) ? $res : [];
}

function sb_post(string $endpoint, array $data): void
{
    global $supabase_url, $supabase_key;
    $ch = curl_init($supabase_url . '/rest/v1/' . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            "apikey: $supabase_key",
            "Authorization: Bearer $supabase_key",
            "Content-Type: application/json",
            "Prefer: return=minimal",
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function sb_patch(string $endpoint): void
{
    global $supabase_url, $supabase_key;
    $ch = curl_init($supabase_url . '/rest/v1/' . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_HTTPHEADER     => [
            "apikey: $supabase_key",
            "Authorization: Bearer $supabase_key",
            "Content-Type: application/json",
        ],
        CURLOPT_POSTFIELDS => json_encode(['is_read' => true]),
    ]);
    curl_exec($ch);
    curl_close($ch);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─── Temporary debug — remove after testing ───────────────────────────────
if ($action === 'debug') {
    $url = $supabase_url . '/rest/v1/admin_messages';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "apikey: $supabase_key",
            "Authorization: Bearer $supabase_key",
        ],
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo json_encode(['url' => $url, 'http_code' => $http, 'response' => $res, 'curl_error' => $err]);
    exit;
}

switch ($action) {

    case 'inbox':
        $rows = sb_get('admin_messages?to_username=eq.' . urlencode($me) . '&order=created_at.desc');
        echo json_encode($rows);
        break;

    case 'sent':
        $rows = sb_get('admin_messages?from_username=eq.' . urlencode($me) . '&order=created_at.desc');
        echo json_encode($rows);
        break;

    case 'unread_count':
        $rows = sb_get('admin_messages?to_username=eq.' . urlencode($me) . '&is_read=eq.false&select=id');
        echo json_encode(['count' => count($rows)]);
        break;

    case 'read':
        $id = preg_replace('/[^a-f0-9\-]/', '', $_POST['id'] ?? '');
        if ($id) sb_patch('admin_messages?id=eq.' . $id);
        echo json_encode(['ok' => true]);
        break;

    case 'send':
        $to      = trim($_POST['to'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $body    = trim($_POST['body'] ?? '');

        // Validate recipient is a real admin
        $valid_usernames = array_keys(ADMIN_ACCOUNTS);
        if (!in_array($to, $valid_usernames)) {
            echo json_encode(['ok' => false, 'error' => 'Recipient not found.']);
            break;
        }
        if (!$subject || !$body) {
            echo json_encode(['ok' => false, 'error' => 'Missing fields.']);
            break;
        }

        sb_post('admin_messages', [
            'from_username' => $me,
            'to_username'   => $to,
            'subject'       => $subject,
            'body'          => $body,
        ]);
        echo json_encode(['ok' => true]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
