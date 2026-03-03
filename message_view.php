<?php

/**
 * message_view.php — Animated Letter Viewer
 * Opens a message like a real envelope with typewriter reveal
 */
session_start();
require_once __DIR__ . '/auth_config.php';
require_admin_login();

$id = preg_replace('/[^a-f0-9\-]/', '', $_GET['id'] ?? '');
if (!$id) {
  header('Location: messages.php');
  exit;
}

require_once __DIR__ . '/db.php';
$pdo  = getDB();
$me   = current_admin()['username'];

$stmt = $pdo->prepare(
  'SELECT * FROM admin_messages WHERE id = ? AND (to_username = ? OR from_username = ?)'
);
$stmt->execute([$id, $me, $me]);
$msg = $stmt->fetch();

if (!$msg) {
  header('Location: messages.php');
  exit;
}

// Mark as read
if ($msg['to_username'] === $me && !$msg['is_read']) {
  $pdo->prepare('UPDATE admin_messages SET is_read = true WHERE id = ?')->execute([$id]);
}

$from    = htmlspecialchars($msg['from_username']);
$subject = htmlspecialchars($msg['subject']);
$body    = htmlspecialchars($msg['body']);
$date    = date('F j, Y · g:i a', strtotime($msg['created_at']));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $subject ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;600;700&family=DM+Sans:ital,wght@0,300;0,400;1,300&display=swap" rel="stylesheet" />
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    :root {
      --bg: #0d1f2d;
      --bg2: #112233;
      --teal: #4ecdc4;
      --teal2: #2bb5ac;
      --blue: #6ab4f5;
      --blue2: #4a9de0;
      --cream: #f5f0e8;
      --ink: #1a2e3d;
      --muted: #8ab4cc;
      --envelope: #1e4060;
      --flap: #1a3a58;
      --shadow: rgba(0, 0, 0, 0.5);
    }

    body {
      background: var(--bg);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      font-family: 'DM Sans', sans-serif;
      padding: 40px 20px;
      overflow-x: hidden;
    }

    /* Starfield background */
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image:
        radial-gradient(1px 1px at 10% 20%, rgba(78, 205, 196, 0.4) 0%, transparent 100%),
        radial-gradient(1px 1px at 30% 60%, rgba(106, 180, 245, 0.3) 0%, transparent 100%),
        radial-gradient(1px 1px at 50% 10%, rgba(78, 205, 196, 0.2) 0%, transparent 100%),
        radial-gradient(1px 1px at 70% 80%, rgba(106, 180, 245, 0.4) 0%, transparent 100%),
        radial-gradient(1px 1px at 85% 35%, rgba(78, 205, 196, 0.3) 0%, transparent 100%),
        radial-gradient(1.5px 1.5px at 20% 85%, rgba(255, 255, 255, 0.15) 0%, transparent 100%),
        radial-gradient(1.5px 1.5px at 60% 45%, rgba(255, 255, 255, 0.1) 0%, transparent 100%),
        radial-gradient(1.5px 1.5px at 90% 70%, rgba(255, 255, 255, 0.12) 0%, transparent 100%);
      pointer-events: none;
    }

    /* Glow orbs */
    .orb {
      position: fixed;
      border-radius: 50%;
      filter: blur(80px);
      opacity: 0.15;
      pointer-events: none;
    }

    .orb1 {
      width: 400px;
      height: 400px;
      background: var(--teal);
      top: -100px;
      left: -100px;
    }

    .orb2 {
      width: 350px;
      height: 350px;
      background: var(--blue);
      bottom: -80px;
      right: -80px;
    }

    /* Back link */
    .back {
      position: fixed;
      top: 20px;
      left: 24px;
      font-family: 'DM Sans', sans-serif;
      font-size: 13px;
      color: var(--muted);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: color 0.2s;
      z-index: 100;
    }

    .back:hover {
      color: var(--teal);
    }

    /* ── SCENE ── */
    .scene {
      width: 100%;
      max-width: 520px;
      perspective: 1200px;
      position: relative;
    }

    /* ── ENVELOPE ── */
    .envelope-wrap {
      position: relative;
      width: 100%;
      animation: envFloat 0.6s ease forwards;
    }

    @keyframes envFloat {
      from {
        opacity: 0;
        transform: translateY(30px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .envelope {
      position: relative;
      width: 100%;
      background: var(--envelope);
      border-radius: 4px 4px 8px 8px;
      box-shadow: 0 20px 60px var(--shadow), 0 0 0 1px rgba(78, 205, 196, 0.15);
      overflow: visible;
    }

    /* Envelope body bottom triangles */
    .envelope::before {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 60%;
      background: linear-gradient(135deg, #1a3a58 50%, transparent 50%),
        linear-gradient(225deg, #1a3a58 50%, transparent 50%);
      background-size: 50% 100%;
      background-repeat: no-repeat;
      background-position: left bottom, right bottom;
      border-radius: 0 0 8px 8px;
    }

    /* Envelope body */
    .envelope-body {
      width: 100%;
      padding-top: 65%;
      position: relative;
      background: var(--envelope);
      border-radius: 4px 4px 8px 8px;
      overflow: hidden;
    }

    /* Side triangles */
    .envelope-body::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        linear-gradient(to bottom right, var(--flap) 0%, var(--flap) 49%, transparent 50%),
        linear-gradient(to bottom left, var(--flap) 0%, var(--flap) 49%, transparent 50%);
      background-size: 50% 100%;
      background-repeat: no-repeat;
      background-position: left, right;
    }

    /* Wax seal */
    .seal {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 48px;
      height: 48px;
      background: radial-gradient(circle at 40% 35%, var(--teal), var(--teal2));
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      box-shadow: 0 2px 12px rgba(78, 205, 196, 0.5), inset 0 -2px 4px rgba(0, 0, 0, 0.2);
      z-index: 5;
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .seal:hover {
      transform: translate(-50%, -50%) scale(1.08);
      box-shadow: 0 4px 20px rgba(78, 205, 196, 0.7), inset 0 -2px 4px rgba(0, 0, 0, 0.2);
    }

    .seal.broken {
      animation: sealPop 0.3s ease forwards;
    }

    @keyframes sealPop {
      0% {
        transform: translate(-50%, -50%) scale(1);
      }

      50% {
        transform: translate(-50%, -50%) scale(1.3) rotate(15deg);
      }

      100% {
        transform: translate(-50%, -50%) scale(0) rotate(30deg);
        opacity: 0;
      }
    }

    /* ── FLAP ── */
    .flap-wrap {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 55%;
      transform-origin: top center;
      transform-style: preserve-3d;
      z-index: 10;
    }

    .flap {
      width: 100%;
      height: 100%;
      position: relative;
      clip-path: polygon(0 0, 100% 0, 50% 100%);
      background: linear-gradient(160deg, #1e4d72 0%, var(--flap) 100%);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .flap-wrap.open {
      animation: flapOpen 0.8s cubic-bezier(0.4, 0, 0.2, 1) 0.3s forwards;
    }

    @keyframes flapOpen {
      0% {
        transform: rotateX(0deg);
      }

      100% {
        transform: rotateX(-180deg);
      }
    }

    /* ── LETTER ── */
    .letter-wrap {
      position: absolute;
      bottom: 8px;
      left: 8px;
      right: 8px;
      z-index: 3;
      transform: translateY(0);
    }

    .letter-wrap.rising {
      animation: letterRise 0.9s cubic-bezier(0.34, 1.56, 0.64, 1) 1.2s forwards;
    }

    @keyframes letterRise {
      0% {
        transform: translateY(0);
      }

      100% {
        transform: translateY(-105%);
      }
    }

    .letter {
      background: var(--cream);
      border-radius: 6px 6px 4px 4px;
      padding: 28px 28px 24px;
      box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.4);
      position: relative;
      min-height: 320px;
    }

    /* Ruled lines on letter */
    .letter::before {
      content: '';
      position: absolute;
      inset: 60px 28px 24px;
      background-image: repeating-linear-gradient(to bottom,
          transparent,
          transparent 27px,
          rgba(106, 180, 245, 0.2) 27px,
          rgba(106, 180, 245, 0.2) 28px);
      pointer-events: none;
    }

    .letter-from {
      font-family: 'Caveat', cursive;
      font-size: 13px;
      color: var(--teal2);
      letter-spacing: 0.06em;
      text-transform: uppercase;
      margin-bottom: 4px;
    }

    .letter-subject {
      font-family: 'Caveat', cursive;
      font-size: 26px;
      font-weight: 700;
      color: var(--ink);
      margin-bottom: 6px;
      line-height: 1.2;
    }

    .letter-date {
      font-size: 11px;
      color: #8a9bb0;
      margin-bottom: 20px;
      font-style: italic;
    }

    .letter-body {
      font-family: 'Caveat', cursive;
      font-size: 19px;
      color: var(--ink);
      line-height: 1.75;
      min-height: 160px;
      position: relative;
      z-index: 1;
      white-space: pre-wrap;
    }

    .cursor {
      display: inline-block;
      width: 2px;
      height: 1.1em;
      background: var(--teal2);
      margin-left: 1px;
      vertical-align: text-bottom;
      animation: blink 0.8s steps(1) infinite;
    }

    .cursor.done {
      display: none;
    }

    @keyframes blink {

      0%,
      100% {
        opacity: 1;
      }

      50% {
        opacity: 0;
      }
    }

    /* ── OPEN STATE — standalone letter card ── */
    .standalone {
      width: 100%;
      max-width: 520px;
      background: var(--cream);
      border-radius: 12px;
      padding: 36px 36px 32px;
      box-shadow: 0 24px 80px var(--shadow), 0 0 0 1px rgba(78, 205, 196, 0.2);
      position: relative;
      opacity: 0;
      transform: translateY(20px);
      transition: opacity 0.5s ease, transform 0.5s ease;
      display: none;
    }

    .standalone.visible {
      display: block;
    }

    .standalone.shown {
      opacity: 1;
      transform: translateY(0);
    }

    .standalone::before {
      content: '';
      position: absolute;
      inset: 60px 36px 32px;
      background-image: repeating-linear-gradient(to bottom,
          transparent,
          transparent 29px,
          rgba(106, 180, 245, 0.18) 29px,
          rgba(106, 180, 245, 0.18) 30px);
      pointer-events: none;
    }

    .standalone .letter-from {
      font-family: 'Caveat', cursive;
      font-size: 13px;
      color: var(--teal2);
      letter-spacing: 0.06em;
      text-transform: uppercase;
      margin-bottom: 4px;
    }

    .standalone .letter-subject {
      font-family: 'Caveat', cursive;
      font-size: 30px;
      font-weight: 700;
      color: var(--ink);
      margin-bottom: 6px;
    }

    .standalone .letter-date {
      font-size: 11px;
      color: #8a9bb0;
      margin-bottom: 24px;
      font-style: italic;
    }

    .standalone .letter-body {
      font-family: 'Caveat', cursive;
      font-size: 20px;
      color: var(--ink);
      line-height: 1.8;
      white-space: pre-wrap;
      position: relative;
      z-index: 1;
    }

    /* Open button hint */
    .hint {
      margin-top: 16px;
      text-align: center;
      font-family: 'DM Sans', sans-serif;
      font-size: 12px;
      color: var(--muted);
      animation: pulse 2s ease infinite;
      letter-spacing: 0.04em;
    }

    /* Hide letter until the envelope is opened */
    #letterWrap {
      opacity: 0;
      transform: translateY(20px);
      transition: opacity 0.3s ease, transform 0.6s ease;
    }

    /* When rising starts, reveal it */
    #letterWrap.rising {
      opacity: 1;
      transform: translateY(0);
    }

    @keyframes pulse {

      0%,
      100% {
        opacity: 0.5;
      }

      50% {
        opacity: 1;
      }
    }
  </style>
</head>

<body>

  <div class="orb orb1"></div>
  <div class="orb orb2"></div>

  <a href="index.php" class="back">← back to messages</a>

  <!-- ENVELOPE SCENE -->
  <div class="scene" id="scene">
    <div class="envelope-wrap">
      <div class="envelope">
        <div class="flap-wrap" id="flap">
          <div class="flap"></div>
        </div>
        <div class="envelope-body">
          <div class="seal" id="seal" onclick="openEnvelope()">💌</div>
          <div class="letter-wrap" id="letterWrap">
            <div class="letter">
              <div class="letter-from">from <?= $from ?></div>
              <div class="letter-subject"><?= $subject ?></div>
              <div class="letter-date"><?= $date ?></div>
              <div class="letter-body" id="previewBody"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <p class="hint" id="hint">tap the seal to open ✨</p>
  </div>

  <!-- STANDALONE LETTER (after animation) -->
  <div class="standalone" id="standalone">
    <div class="letter-from">from <?= $from ?></div>
    <div class="letter-subject"><?= $subject ?></div>
    <div class="letter-date"><?= $date ?></div>
    <div class="letter-body" id="typeBody"><span class="cursor" id="cursor"></span></div>
  </div>

  <script>
    const fullText = <?= json_encode($msg['body']) ?>;
    let opened = false;

    function openEnvelope() {
      if (opened) return;
      opened = true;

      const seal = document.getElementById('seal');
      const flap = document.getElementById('flap');
      const letterWrap = document.getElementById('letterWrap');
      const hint = document.getElementById('hint');

      hint.style.opacity = '0';

      // 1. Pop seal
      seal.classList.add('broken');

      // 2. Open flap
      setTimeout(() => {
        flap.classList.add('open');
      }, 350);

      // 3. Rise letter
      setTimeout(() => {
        letterWrap.classList.add('rising');
      }, 1200);

      // 4. Show standalone letter + typewriter
      setTimeout(() => {
        document.getElementById('scene').style.display = 'none';
        const standalone = document.getElementById('standalone');
        standalone.classList.add('visible');
        requestAnimationFrame(() => {
          requestAnimationFrame(() => standalone.classList.add('shown'));
        });
        typeWriter();
      }, 2200);
    }

    function typeWriter() {
      const el = document.getElementById('typeBody');
      const cursor = document.getElementById('cursor');
      let i = 0;
      const speed = 28; // ms per character

      function type() {
        if (i < fullText.length) {
          // Insert character before cursor
          const char = document.createTextNode(fullText[i]);
          el.insertBefore(char, cursor);
          i++;
          setTimeout(type, speed + (fullText[i - 1] === '\n' ? 120 : Math.random() * 20));
        } else {
          cursor.classList.add('done');
        }
      }
      type();
    }
  </script>
</body>

</html>