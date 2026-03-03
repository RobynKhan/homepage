/**
 * ============================================================================
 * breakthrough.js — Atari Breakout Clone
 * ============================================================================
 *
 * Canvas-based Breakout game rendered inside container-6 (admin only).
 * Controls: Arrow Left/Right or A/D to move paddle, Space or START button
 * to launch the ball.
 *
 * Scoring:  +10 per brick destroyed, +100 per level cleared.
 * Levels:   Each level adds a brick row and increases ball speed (capped).
 * Lives:    3 per run.  On game over the run score is posted to
 *           breakout_api.php for persistence.
 * ============================================================================
 */

(function () {
  "use strict";

  // ── API ──────────────────────────────────────────────────────────────────
  const API = "breakout_api.php";

  // ── Game Constants ───────────────────────────────────────────────────────
  const PADDLE_H = 8;
  const BALL_R = 6;
  const BRICK_COLS = 8;
  const BRICK_H = 14;
  const BRICK_GAP = 4;
  const BRICK_TOP = 36;
  const ROWS_BASE = 3;
  const ROWS_CAP = 7;
  const SPEED_BASE = 3.0;
  const SPEED_CAP = 5.5;
  const LIVES_START = 3;
  const CANVAS_HEIGHT = 260;

  // Retro brick colour palette (row 0 → row 5, cycling)
  const BRICK_COLORS = [
    "#ff2020",
    "#ff8800",
    "#ffe600",
    "#00e500",
    "#00d4ff",
    "#c800ff",
  ];

  // ── Module State ────────────────────────────────────────────────────────
  let canvas, ctx;
  let animFrame = null;
  let gameStarted = false;
  let state = null;
  let apiData = {
    me: { username: "", total_score: 0, best_run_score: 0 },
    top: { username: "", best_run_score: 0 },
  };
  const keys = {};
  let startBtn = null;

  function setBreakoutActive(flag) {
    window.breakoutActive = !!flag;
    if (flag) {
      document.body.dataset.breakoutActive = "1";
    } else {
      document.body.removeAttribute("data-breakout-active");
    }
  }

  setBreakoutActive(false);

  // Module-level hook: set by initBreakout(), called by onGameOver()
  let _showRetryChooser = null;

  // ── HUD Element References ───────────────────────────────────────────────
  let hudScore, hudLevel, hudTotal, hudTop;

  // ── Build Brick Grid ─────────────────────────────────────────────────────
  function buildBricks(rows, W) {
    const bricks = [];
    const totalGap = BRICK_GAP * (BRICK_COLS + 1);
    const brickW = Math.floor((W - totalGap) / BRICK_COLS);
    for (let r = 0; r < rows; r++) {
      for (let c = 0; c < BRICK_COLS; c++) {
        bricks.push({
          x: BRICK_GAP + c * (brickW + BRICK_GAP),
          y: BRICK_TOP + r * (BRICK_H + BRICK_GAP),
          w: brickW,
          h: BRICK_H,
          color: BRICK_COLORS[r % BRICK_COLORS.length],
          alive: true,
        });
      }
    }
    return bricks;
  }

  // ── Initialise/Reset Game State for a Level ──────────────────────────────
  function initGameState(level, score, lives) {
    const W = canvas.width;
    const H = canvas.height;
    const rows = Math.min(ROWS_BASE + (level - 1), ROWS_CAP);
    const speed = Math.min(SPEED_BASE + (level - 1) * 0.5, SPEED_CAP);
    state = {
      level,
      lives: lives !== undefined ? lives : LIVES_START,
      score: score !== undefined ? score : 0,
      paddle: { x: W / 2 - 30, w: 60 },
      ball: {
        x: W / 2,
        y: H - PADDLE_H - 30,
        dx: speed * Math.cos(-Math.PI / 4),
        dy: speed * Math.sin(-Math.PI / 4),
        speed,
      },
      bricks: buildBricks(rows, W),
      launched: false,
      gameOver: false,
      won: false,
    };
  }

  // ── Update HUD Displays ──────────────────────────────────────────────────
  function updateHUD() {
    if (!state) return;
    if (hudScore) hudScore.textContent = String(state.score).padStart(5, "0");
    if (hudLevel) hudLevel.textContent = String(state.level).padStart(2, "0");
    if (hudTotal)
      hudTotal.textContent = String(apiData.me.total_score || 0).padStart(
        5,
        "0",
      );
    if (hudTop) {
      const ts = String(apiData.top.best_run_score || 0).padStart(5, "0");
      hudTop.textContent =
        "TOP: " + ts + " (" + (apiData.top.username || "---") + ")";
    }
  }

  // ── Game Logic Update ────────────────────────────────────────────────────
  function update() {
    if (!state || state.gameOver || state.won) return;
    const W = canvas.width;
    const H = canvas.height;
    const paddleY = H - PADDLE_H - 6;

    // Paddle movement
    const pSpeed = 5;
    if (keys["ArrowLeft"] || keys["a"] || keys["A"]) {
      state.paddle.x = Math.max(0, state.paddle.x - pSpeed);
    }
    if (keys["ArrowRight"] || keys["d"] || keys["D"]) {
      state.paddle.x = Math.min(W - state.paddle.w, state.paddle.x + pSpeed);
    }

    // Ball follows paddle until launched
    if (!state.launched) {
      state.ball.x = state.paddle.x + state.paddle.w / 2;
      state.ball.y = H - PADDLE_H - 30;
      return;
    }

    // Move ball
    state.ball.x += state.ball.dx;
    state.ball.y += state.ball.dy;

    // Wall collisions (left / right)
    if (state.ball.x - BALL_R < 0) {
      state.ball.x = BALL_R;
      state.ball.dx = Math.abs(state.ball.dx);
    } else if (state.ball.x + BALL_R > W) {
      state.ball.x = W - BALL_R;
      state.ball.dx = -Math.abs(state.ball.dx);
    }
    // Ceiling
    if (state.ball.y - BALL_R < 0) {
      state.ball.y = BALL_R;
      state.ball.dy = Math.abs(state.ball.dy);
    }

    // Paddle collision
    if (
      state.ball.dy > 0 &&
      state.ball.y + BALL_R >= paddleY &&
      state.ball.y + BALL_R <=
        paddleY + PADDLE_H + Math.abs(state.ball.dy) + 1 &&
      state.ball.x >= state.paddle.x &&
      state.ball.x <= state.paddle.x + state.paddle.w
    ) {
      const hitPos = (state.ball.x - state.paddle.x) / state.paddle.w - 0.5; // −0.5 to 0.5
      const angle = hitPos * Math.PI * 0.65; // max ±58.5°
      const s = state.ball.speed;
      state.ball.dx = s * Math.sin(angle);
      state.ball.dy = -Math.abs(s * Math.cos(angle));
      state.ball.y = paddleY - BALL_R - 1;
    }

    // Ball exits bottom → lose a life
    if (state.ball.y - BALL_R > H) {
      state.lives--;
      updateHUD();
      if (state.lives <= 0) {
        state.gameOver = true;
        onGameOver();
      } else {
        // Reset ball to paddle
        state.launched = false;
        state.ball.x = state.paddle.x + state.paddle.w / 2;
        state.ball.y = H - PADDLE_H - 30;
        state.ball.dx = state.ball.speed * Math.cos(-Math.PI / 4);
        state.ball.dy = state.ball.speed * Math.sin(-Math.PI / 4);
      }
      return;
    }

    // Brick collisions
    for (let i = 0; i < state.bricks.length; i++) {
      const b = state.bricks[i];
      if (!b.alive) continue;
      if (
        state.ball.x + BALL_R > b.x &&
        state.ball.x - BALL_R < b.x + b.w &&
        state.ball.y + BALL_R > b.y &&
        state.ball.y - BALL_R < b.y + b.h
      ) {
        b.alive = false;
        state.score += 10;
        updateHUD();

        // Bounce direction: reflect along the axis with smallest overlap
        const oL = state.ball.x + BALL_R - b.x;
        const oR = b.x + b.w - (state.ball.x - BALL_R);
        const oT = state.ball.y + BALL_R - b.y;
        const oB = b.y + b.h - (state.ball.y - BALL_R);
        const minO = Math.min(oL, oR, oT, oB);
        if (minO === oT || minO === oB) {
          state.ball.dy = -state.ball.dy;
        } else {
          state.ball.dx = -state.ball.dx;
        }
        break; // one brick per frame
      }
    }

    // All bricks cleared → level complete
    if (
      state.bricks.every(function (b) {
        return !b.alive;
      })
    ) {
      state.score += 100;
      state.won = true;
      updateHUD();
      setTimeout(nextLevel, 800);
    }
  }

  // ── Advance to Next Level ────────────────────────────────────────────────
  function nextLevel() {
    initGameState(state.level + 1, state.score, state.lives);
    updateHUD();
  }

  // ── Render Frame ─────────────────────────────────────────────────────────
  function draw() {
    if (!canvas || !ctx || !state) return;
    const W = canvas.width;
    const H = canvas.height;
    const paddleY = H - PADDLE_H - 6;

    // Background
    ctx.fillStyle = "#0d0020";
    ctx.fillRect(0, 0, W, H);

    // Scanlines
    ctx.fillStyle = "rgba(0,0,0,0.10)";
    for (let y = 0; y < H; y += 4) {
      ctx.fillRect(0, y, W, 2);
    }

    // Bricks
    state.bricks.forEach(function (b) {
      if (!b.alive) return;
      ctx.fillStyle = b.color;
      ctx.fillRect(b.x, b.y, b.w, b.h);
      // Top highlight
      ctx.fillStyle = "rgba(255,255,255,0.20)";
      ctx.fillRect(b.x, b.y, b.w, 3);
    });

    // Paddle
    ctx.fillStyle = "#00e5ff";
    ctx.fillRect(state.paddle.x, paddleY, state.paddle.w, PADDLE_H);

    // Ball
    ctx.beginPath();
    ctx.arc(state.ball.x, state.ball.y, BALL_R, 0, Math.PI * 2);
    ctx.fillStyle = "#ffffff";
    ctx.fill();

    // Lives (hearts)
    ctx.fillStyle = "#ff4444";
    ctx.font = '9px "Press Start 2P", monospace';
    ctx.textAlign = "left";
    for (let i = 0; i < state.lives; i++) {
      ctx.fillText("\u2665", 4 + i * 16, H - 4);
    }

    // Overlays
    if (state.gameOver) {
      ctx.fillStyle = "rgba(0,0,0,0.65)";
      ctx.fillRect(0, 0, W, H);
      ctx.fillStyle = "#ff2020";
      ctx.font = '12px "Press Start 2P", monospace';
      ctx.textAlign = "center";
      ctx.fillText("GAME OVER", W / 2, H / 2 - 12);
      ctx.fillStyle = "#ffff00";
      ctx.font = '8px "Press Start 2P", monospace';
      ctx.fillText("PRESS START", W / 2, H / 2 + 10);
    } else if (state.won) {
      ctx.fillStyle = "rgba(0,0,0,0.50)";
      ctx.fillRect(0, 0, W, H);
      ctx.fillStyle = "#00e500";
      ctx.font = '11px "Press Start 2P", monospace';
      ctx.textAlign = "center";
      ctx.fillText("LEVEL CLEAR!", W / 2, H / 2);
    } else if (!state.launched) {
      ctx.fillStyle = "rgba(255,255,255,0.55)";
      ctx.font = '7px "Press Start 2P", monospace';
      ctx.textAlign = "center";
      ctx.fillText("SPACE / START", W / 2, H - 22);
    }
    ctx.textAlign = "left";
  }

  // ── Main Game Loop ───────────────────────────────────────────────────────
  function gameLoop() {
    update();
    draw();
    animFrame = requestAnimationFrame(gameLoop);
  }

  // ── Resize Canvas to Parent Width ────────────────────────────────────────
  function resizeCanvas() {
    const parent = canvas.parentElement;
    const w = parent && parent.clientWidth > 10 ? parent.clientWidth : 280;
    canvas.width = w;
    canvas.height = CANVAS_HEIGHT;
  }

  // ── Start a New Run ──────────────────────────────────────────────────────
  function startGame() {
    cancelAnimationFrame(animFrame);
    animFrame = null;
    resizeCanvas();
    initGameState(1);
    updateHUD();
    gameStarted = true;
    gameLoop();
    setBreakoutActive(true);
    if (startBtn) startBtn.style.display = "none";
  }

  // ── Post Run Score to API ────────────────────────────────────────────────
  function onGameOver() {
    setBreakoutActive(false);
    gameStarted = false;
    fetch(API + "?action=finish_run", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "same-origin",
      body: JSON.stringify({ run_score: state.score }),
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        apiData = data;
        updateHUD();
      })
      .catch(function (err) {
        console.error("Breakout API error:", err);
        if (hudTop) hudTop.textContent = "SAVE ERR";
      });

    // On mobile: show the control chooser after the game-over screen appears
    setTimeout(function () {
      if (_showRetryChooser) _showRetryChooser();
    }, 900);

    if (startBtn) {
      startBtn.textContent = "RETRY";
      startBtn.style.display = "inline-flex";
    }
  }

  // ── Load Initial Scores from API ─────────────────────────────────────────
  async function loadAPIState() {
    try {
      const res = await fetch(API + "?action=state", {
        credentials: "same-origin",
      });
      if (res.ok) {
        apiData = await res.json();
        updateHUD();
      }
    } catch (e) {
      console.error("Breakout state load error:", e);
    }
  }

  // ── Entry Point ──────────────────────────────────────────────────────────
  function initBreakout() {
    canvas = document.getElementById("breakout-canvas");
    if (!canvas) return;
    ctx = canvas.getContext("2d");
    const breakoutPanel = document.getElementById("container-6");
    const todoPanel = document.getElementById("container-4");
    const DESKTOP_GAP = 16;

    function isDesktopLayout() {
      return (
        window.matchMedia && window.matchMedia("(min-width: 901px)").matches
      );
    }

    function isPanelVisible(el) {
      return !!(
        el &&
        (el.classList.contains("sheet-open") ||
          (window.getComputedStyle(el).display !== "none" &&
            window.getComputedStyle(el).visibility !== "hidden"))
      );
    }

    function alignBreakoutUnderTodo() {
      if (!breakoutPanel) return;
      if (!isDesktopLayout()) {
        breakoutPanel.style.top = "";
        breakoutPanel.style.bottom = "";
        breakoutPanel.style.right = "";
        return;
      }
      if (
        !todoPanel ||
        !isPanelVisible(todoPanel) ||
        !isPanelVisible(breakoutPanel)
      ) {
        breakoutPanel.style.top = "";
        breakoutPanel.style.bottom = "";
        breakoutPanel.style.right = "";
        return;
      }

      const todoRect = todoPanel.getBoundingClientRect();
      const breakoutHeight = breakoutPanel.offsetHeight || 0;
      const minTop = 56;
      const maxTop = Math.max(
        minTop,
        window.innerHeight - breakoutHeight - DESKTOP_GAP,
      );
      const desiredTop = todoRect.bottom + DESKTOP_GAP;
      const clampedTop = Math.min(Math.max(desiredTop, minTop), maxTop);

      breakoutPanel.style.top = Math.round(clampedTop) + "px";
      breakoutPanel.style.bottom = "auto";
      breakoutPanel.style.right = window.getComputedStyle(todoPanel).right;
    }

    // Important for mobile: prevent scrolling while dragging on the canvas
    canvas.style.touchAction = "none";

    // Track finger/mouse being held down for drag controls
    let bkPointerDown = false;

    hudScore = document.getElementById("bk-hud-score");
    hudLevel = document.getElementById("bk-hud-level");
    hudTotal = document.getElementById("bk-hud-total");
    hudTop = document.getElementById("bk-hud-top");
    // Keyboard: track held keys
    document.addEventListener("keydown", function (e) {
      keys[e.key] = true;
      // Space launches ball — only when the breakout panel is visible
      if (e.key === " ") {
        const panel = document.getElementById("container-6");
        const vis =
          panel &&
          (panel.classList.contains("sheet-open") ||
            (panel.style.display && panel.style.display !== "none"));
        if (vis) {
          e.preventDefault(); // always prevent scroll when panel is open
          if (state && !state.launched && !state.gameOver && !state.won) {
            state.launched = true;
          }
        }
      }
    });
    document.addEventListener("keyup", function (e) {
      keys[e.key] = false;
    });
    // ── Mobile Control Mode ─────────────────────────────────────────────────────
    const CONTROL_KEY = "breakoutControlMode"; // 'drag' | 'buttons' | 'both'

    function isMobileLike() {
      return (
        window.matchMedia && window.matchMedia("(max-width: 800px)").matches
      );
    }

    function clamp(v, min, max) {
      return Math.max(min, Math.min(max, v));
    }

    function setPaddleFromClientX(clientX) {
      if (!canvas || !state) return;
      const rect = canvas.getBoundingClientRect();
      const x = (clientX - rect.left) * (canvas.width / rect.width);
      state.paddle.x = clamp(
        x - state.paddle.w / 2,
        0,
        canvas.width - state.paddle.w,
      );
    }

    function chooseControlsIfNeeded() {
      if (!isMobileLike()) return;

      const existing = localStorage.getItem(CONTROL_KEY);
      if (existing) {
        applyControlMode(existing);
        return;
      }

      // Show chooser UI (expects HTML elements to exist)
      const chooser = document.getElementById("bk-control-chooser");
      if (!chooser) {
        applyControlMode("both"); // safe default
        return;
      }

      chooser.style.display = "block";

      const setMode = (mode) => {
        localStorage.setItem(CONTROL_KEY, mode);
        chooser.style.display = "none";
        applyControlMode(mode);
      };

      const dragBtn = document.getElementById("bk-choose-drag");
      const buttonsBtn = document.getElementById("bk-choose-buttons");
      const bothBtn = document.getElementById("bk-choose-both");

      if (dragBtn) dragBtn.onclick = () => setMode("drag");
      if (buttonsBtn) buttonsBtn.onclick = () => setMode("buttons");
      if (bothBtn) bothBtn.onclick = () => setMode("both");
    }
    function applyControlMode(mode) {
      // Toggle button UI visibility
      const controls = document.getElementById("bk-touch-controls");
      if (controls) {
        controls.style.display =
          mode === "buttons" || mode === "both" ? "flex" : "none";
      }

      // Pointer drag enabled?
      canvas._bkDragEnabled = mode === "drag" || mode === "both";
    }
    // Touch / Pointer drag on canvas (mobile-friendly)
    canvas.addEventListener("pointerdown", function (e) {
      if (!canvas._bkDragEnabled) return;
      bkPointerDown = true;
      canvas.setPointerCapture(e.pointerId);
      setPaddleFromClientX(e.clientX);
      if (state && !state.launched && !state.gameOver && !state.won)
        state.launched = true;
    });

    canvas.addEventListener("pointermove", function (e) {
      if (!canvas._bkDragEnabled) return;
      if (!bkPointerDown) return;
      setPaddleFromClientX(e.clientX);
    });

    canvas.addEventListener("pointerup", function () {
      bkPointerDown = false;
    });
    canvas.addEventListener("pointercancel", function () {
      bkPointerDown = false;
    });
    canvas.addEventListener("pointerleave", function () {
      bkPointerDown = false;
    });

    canvas.addEventListener(
      "touchstart",
      function (e) {
        if (!canvas._bkDragEnabled) return;
        if (!e.touches || !e.touches.length) return;
        bkPointerDown = true;
        setPaddleFromClientX(e.touches[0].clientX);
        if (state && !state.launched && !state.gameOver && !state.won)
          state.launched = true;
      },
      { passive: true },
    );

    canvas.addEventListener(
      "touchmove",
      function (e) {
        if (!canvas._bkDragEnabled || !bkPointerDown) return;
        if (!e.touches || !e.touches.length) return;
        setPaddleFromClientX(e.touches[0].clientX);
      },
      { passive: true },
    );

    canvas.addEventListener("touchend", function () {
      bkPointerDown = false;
    });
    canvas.addEventListener("touchcancel", function () {
      bkPointerDown = false;
    });

    // On-screen buttons (hold to move)
    const leftBtn = document.getElementById("bk-btn-left");
    const rightBtn = document.getElementById("bk-btn-right");

    function bindHold(btn, keyName) {
      if (!btn) return;
      const down = (e) => {
        e.preventDefault();
        keys[keyName] = true;
      };
      const up = (e) => {
        e.preventDefault();
        keys[keyName] = false;
      };

      btn.addEventListener("pointerdown", down);
      btn.addEventListener("pointerup", up);
      btn.addEventListener("pointercancel", up);
      btn.addEventListener("pointerleave", up);
      btn.addEventListener("touchstart", down, { passive: false });
      btn.addEventListener("touchend", up, { passive: false });
      btn.addEventListener("touchcancel", up, { passive: false });
    }

    bindHold(leftBtn, "ArrowLeft");
    bindHold(rightBtn, "ArrowRight");

    // Module-level hook: lets onGameOver() re-open the chooser after each run
    _showRetryChooser = function () {
      if (!isMobileLike()) return;
      // Clear saved preference so the full chooser re-appears
      localStorage.removeItem(CONTROL_KEY);
      var chooser = document.getElementById("bk-control-chooser");
      if (chooser) chooser.style.display = "block";
    };

    // Finally, prompt user on mobile if not chosen yet
    chooseControlsIfNeeded();
    // START button
    startBtn = document.getElementById("bk-start-btn");
    if (startBtn) {
      startBtn.addEventListener("click", function () {
        if (!gameStarted || (state && state.gameOver)) {
          startGame();
        } else if (state && !state.launched && !state.won) {
          state.launched = true;
        }
      });
    }

    // Watch for the panel becoming visible (lazy first-start)
    const panel = breakoutPanel;
    if (panel) {
      const mo = new MutationObserver(function () {
        alignBreakoutUnderTodo();
        if (gameStarted) return;
        const isVisible =
          panel.classList.contains("sheet-open") ||
          (panel.style.display && panel.style.display !== "none");
        if (isVisible) startGame();
      });
      mo.observe(panel, {
        attributes: true,
        attributeFilter: ["style", "class"],
      });
    }

    if (todoPanel) {
      const todoObserver = new MutationObserver(function () {
        alignBreakoutUnderTodo();
      });
      todoObserver.observe(todoPanel, {
        attributes: true,
        attributeFilter: ["style", "class"],
      });
    }

    // Re-size canvas on window resize
    window.addEventListener("resize", function () {
      if (gameStarted) resizeCanvas();
      if (isMobileLike()) {
        const mode = localStorage.getItem(CONTROL_KEY) || "both";
        applyControlMode(mode);
      } else {
        applyControlMode("drag");
      }
      alignBreakoutUnderTodo();
    });

    setTimeout(alignBreakoutUnderTodo, 0);
    setTimeout(alignBreakoutUnderTodo, 120);

    // Pre-load scores
    loadAPIState();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initBreakout);
  } else {
    initBreakout();
  }
})();
