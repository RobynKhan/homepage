<?php
session_start();
require_once __DIR__ . '/auth_config.php';

// Already logged in? Send to dashboard
if (is_admin_logged_in()) {
  header('Location: ' . DASHBOARD_PAGE);
  exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if (
    isset(ADMIN_ACCOUNTS[$username]) &&
    password_verify($password, ADMIN_ACCOUNTS[$username]['password_hash'])
  ) {
    // ✅ Valid — store minimal info in session
    $_SESSION[AUTH_SESSION_KEY] = [
      'username'     => ADMIN_ACCOUNTS[$username]['username'],
      'display_name' => ADMIN_ACCOUNTS[$username]['display_name'],
      'logged_in_at' => time(),
    ];

    // Regenerate session ID to prevent fixation attacks
    session_regenerate_id(true);

    header('Location: ' . DASHBOARD_PAGE);
    exit;
  } else {
    $error = 'Invalid username or password.';
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700&family=Space+Mono&display=swap" rel="stylesheet" />
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --bg: #0f0e17;
      --card: #1a1929;
      --accent: #e8c87a;
      --accent2: #a78bfa;
      --text: #fffffe;
      --muted: #7c7b8a;
      --error: #f87171;
      --glow: rgba(232, 200, 122, 0.12);
    }

    body {
      background: var(--bg);
      min-height: 100vh;
      min-height: 100dvh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Syne', sans-serif;
      color: var(--text);
      padding: clamp(16px, 4vw, 40px);
    }

    /* Noise texture */
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
      pointer-events: none;
      opacity: 0.5;
    }

    .card {
      position: relative;
      background: var(--card);
      border: 1px solid rgba(232, 200, 122, 0.12);
      border-radius: clamp(16px, 3vw, 24px);
      padding: clamp(28px, 5vw, 44px) clamp(24px, 4vw, 40px);
      width: 100%;
      max-width: 400px;
      box-shadow: 0 0 60px var(--glow), 0 24px 48px rgba(0, 0, 0, 0.5);
      animation: fadeUp 0.4s ease;
    }

    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(16px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .brand {
      text-align: center;
      margin-bottom: clamp(20px, 4vw, 32px);
    }

    .brand-icon {
      font-size: clamp(26px, 5vw, 36px);
      margin-bottom: 10px;
    }

    .brand h1 {
      font-size: clamp(18px, 4vw, 24px);
      font-weight: 700;
      letter-spacing: -0.02em;
    }

    .brand p {
      font-family: 'Space Mono', monospace;
      font-size: clamp(10px, 2vw, 12px);
      color: var(--muted);
      margin-top: 4px;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }

    .form-group {
      margin-bottom: clamp(12px, 2.5vw, 18px);
    }

    label {
      display: block;
      font-family: 'Space Mono', monospace;
      font-size: clamp(9px, 1.8vw, 11px);
      color: var(--muted);
      letter-spacing: 0.08em;
      text-transform: uppercase;
      margin-bottom: 6px;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: clamp(10px, 2vw, 14px) 14px;
      color: var(--text);
      font-family: 'Syne', sans-serif;
      font-size: clamp(13px, 2.5vw, 15px);
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    input[type="text"]:focus,
    input[type="password"]:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(232, 200, 122, 0.08);
    }

    .error-banner {
      background: rgba(248, 113, 113, 0.1);
      border: 1px solid rgba(248, 113, 113, 0.3);
      border-radius: 10px;
      padding: 10px 14px;
      font-family: 'Space Mono', monospace;
      font-size: 11px;
      color: var(--error);
      margin-bottom: 16px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-login {
      width: 100%;
      background: var(--accent);
      color: #0f0e17;
      border: none;
      border-radius: 50px;
      padding: clamp(11px, 2vw, 15px);
      font-family: 'Syne', sans-serif;
      font-size: clamp(14px, 2.5vw, 16px);
      font-weight: 700;
      cursor: pointer;
      letter-spacing: 0.04em;
      margin-top: 8px;
      transition: opacity 0.2s, transform 0.15s, box-shadow 0.2s;
      box-shadow: 0 4px 18px rgba(232, 200, 122, 0.2);
    }

    .btn-login:hover {
      opacity: 0.9;
      transform: scale(1.02);
      box-shadow: 0 6px 24px rgba(232, 200, 122, 0.3);
    }

    .btn-login:active {
      transform: scale(0.98);
    }

    .back-link {
      display: block;
      text-align: center;
      margin-top: clamp(14px, 3vw, 22px);
      font-family: 'Space Mono', monospace;
      font-size: clamp(10px, 2vw, 12px);
      color: var(--muted);
      text-decoration: none;
      transition: color 0.2s;
    }

    .back-link:hover {
      color: var(--text);
    }

    /* Larger screens — constrain and center nicely */
    @media (min-width: 768px) {
      .card {
        max-width: 420px;
      }
    }
  </style>
</head>

<body>

  <div class="card">
    <div class="brand">
      <div class="brand-icon"></div>
      <h1>Admin Login</h1>
      <p>Pomodoro Dashboard</p>
    </div>

    <?php if ($error): ?>
      <div class="error-banner">⚠ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label for="username">Username</label>
        <input
          type="text"
          id="username"
          name="username"
          autocomplete="username"
          value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
          required
          autofocus />
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input
          type="password"
          id="password"
          name="password"
          autocomplete="current-password"
          required />
      </div>

      <button type="submit" class="btn-login">Sign In →</button>
    </form>

    <a href="index.php" class="back-link">← back to homepage</a>
  </div>

</body>

</html>