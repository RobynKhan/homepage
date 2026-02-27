<?php
// ─── TEMPORARY DEBUG FILE ─────────────────────────────────────────────────
// Upload this as debug_auth.php to your project root
// Visit yoursite.com/debug_auth.php ONCE to diagnose the login issue
// ⚠️ DELETE THIS FILE immediately after you're done debugging!
// ─────────────────────────────────────────────────────────────────────────

$admin1_user = getenv('ADMIN1_USERNAME');
$admin1_hash = getenv('ADMIN1_PASSWORD_HASH');
$admin2_user = getenv('ADMIN2_USERNAME');
$admin2_hash = getenv('ADMIN2_PASSWORD_HASH');

echo "<pre style='font-family:monospace; font-size:14px; padding:20px;'>";
echo "=== ENV VARIABLE CHECK ===\n\n";

echo "ADMIN1_USERNAME:      " . ($admin1_user  ? "✅ '{$admin1_user}'" : "❌ NOT FOUND") . "\n";
echo "ADMIN1_PASSWORD_HASH: " . ($admin1_hash  ? "✅ found (starts with: " . substr($admin1_hash, 0, 7) . "...)" : "❌ NOT FOUND") . "\n\n";

echo "ADMIN2_USERNAME:      " . ($admin2_user  ? "✅ '{$admin2_user}'" : "❌ NOT FOUND") . "\n";
echo "ADMIN2_PASSWORD_HASH: " . ($admin2_hash  ? "✅ found (starts with: " . substr($admin2_hash, 0, 7) . "...)" : "❌ NOT FOUND") . "\n\n";

echo "=== HASH FORMAT CHECK ===\n\n";
if ($admin1_hash) {
    $valid_hash = str_starts_with($admin1_hash, '$2y$') || str_starts_with($admin1_hash, '$2a$');
    echo "ADMIN1 hash looks like bcrypt: " . ($valid_hash ? "✅ YES" : "❌ NO — paste a proper bcrypt hash from bcrypt-generator.com") . "\n";
}
if ($admin2_hash) {
    $valid_hash = str_starts_with($admin2_hash, '$2y$') || str_starts_with($admin2_hash, '$2a$');
    echo "ADMIN2 hash looks like bcrypt: " . ($valid_hash ? "✅ YES" : "❌ NO — paste a proper bcrypt hash from bcrypt-generator.com") . "\n";
}

echo "\n=== QUICK LOGIN TEST ===\n\n";
echo "Enter a username and password below to test verify:\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testUser = trim($_POST['username'] ?? '');
    $testPass = $_POST['password'] ?? '';

    $accounts = [
        $admin1_user => $admin1_hash,
        $admin2_user => $admin2_hash,
    ];

    if (isset($accounts[$testUser])) {
        $result = password_verify($testPass, $accounts[$testUser]);
        echo "Username match: ✅\n";
        echo "Password verify: " . ($result ? "✅ CORRECT — login would succeed" : "❌ WRONG PASSWORD") . "\n";
    } else {
        echo "Username match: ❌ '{$testUser}' not found in accounts\n";
        echo "Available usernames: " . implode(', ', array_filter([$admin1_user, $admin2_user])) . "\n";
    }
}
echo "</pre>";
?>

<form method="POST" style="padding:20px; font-family:monospace;">
    <input name="username" placeholder="username" style="display:block;margin-bottom:8px;padding:6px;width:200px;" /><br />
    <input name="password" type="password" placeholder="password" style="display:block;margin-bottom:8px;padding:6px;width:200px;" /><br />
    <button type="submit" style="padding:8px 16px;">Test Login</button>
</form>

<p style="padding:20px; color:red; font-family:monospace;">
    ⚠️ REMEMBER: Delete this file after debugging!
</p>