/**
 * ============================================================================
 * timer.js — Pomodoro Timer, Navigation & Clock Controller
 * ============================================================================
 */

// ─── DOM Element References ───────────────────────────────────────────────
const elements = {
  startBtn: document.querySelector("#start-button"),
  pauseBtn: document.querySelector("#pause-button"),
  resetBtn: document.querySelector("#restart-button"),
  pomodoroBtn: document.getElementById("pomodorobtn"),
  longpomodoroBtn: document.getElementById("longpomodorobtn"),
  shortbrkBtn: document.getElementById("shortbrkbtn"),
  longbrkBtn: document.getElementById("longbrkbtn"),
  pomCount: document.querySelector(".pomodoro-count"),
  timerDisplay: document.querySelector(".timer-display"),
};

// ─── Timer Duration Defaults (in seconds) ─────────────────────────────────
const timers = {
  POMODORO: 25 * 60, // 25 minutes
  LONGPOMODORO: 50 * 60, // 50 minutes
  SHORTBREAK: 5 * 60, // 5 minutes
  LONGBREAK: 15 * 60, // 15 minutes
};

// ─── Timer State Object ───────────────────────────────────────────────────
const state = {
  pomodoroCount: 0,
  pomodorosUntilLongBreak: 4,
  timerValue: 25 * 60,
  initialTime: 25 * 60,
  timerInterval: null,
  isPaused: false,
  pomodoroType: "POMODORO",
};

// When breakout is active, block message panel opens on mobile
const breakoutLocked = () => !!window.breakoutActive;

// ─── Event Listener Registration ─────────────────────────────────────────
const addEventListeners = () => {
  elements.startBtn.addEventListener("click", handleStart);
  elements.pauseBtn.addEventListener("click", handlePause);
  elements.resetBtn.addEventListener("click", resetTimer);

  elements.pomodoroBtn.addEventListener("click", () => {
    setTimeType("POMODORO");
    hideCustomWrap();
  });
  elements.shortbrkBtn.addEventListener("click", () => {
    setTimeType("SHORTBREAK");
    hideCustomWrap();
  });
  elements.longbrkBtn.addEventListener("click", () => {
    setTimeType("LONGBREAK");
    hideCustomWrap();
  });
  elements.longpomodoroBtn.addEventListener("click", () => {
    setTimeType("LONGPOMODORO");
    hideCustomWrap();
  });

  // ─── Custom Timer Button ──────────────────────────────────────────────
  const customBtn = document.getElementById("custombrkbtn");
  if (customBtn) {
    customBtn.addEventListener("click", () => {
      const wrap = document.getElementById("custom-time-wrap");
      if (wrap)
        wrap.style.display = wrap.style.display === "none" ? "block" : "none";
    });
  }

  // ─── Desktop Navigation Drawer (Hamburger Menu) ───────────────────────
  const hamburgerBtn = document.getElementById("hamburger-btn");
  const navDrawer = document.getElementById("nav-drawer");
  const navOverlay = document.getElementById("nav-overlay");
  const navDrawerClose = document.getElementById("nav-drawer-close");

  if (hamburgerBtn && navDrawer && navOverlay) {
    const openDrawer = () => {
      navDrawer.classList.add("open");
      navOverlay.classList.add("visible");
    };
    const closeDrawer = () => {
      navDrawer.classList.remove("open");
      navOverlay.classList.remove("visible");
    };

    hamburgerBtn.addEventListener("click", openDrawer);
    navOverlay.addEventListener("click", closeDrawer);
    if (navDrawerClose) navDrawerClose.addEventListener("click", closeDrawer);

    navDrawer.querySelectorAll(".nav-link").forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault();
        const targetEl = document.getElementById(link.dataset.target);
        if (targetEl) {
          if (
            breakoutLocked() &&
            targetEl.id === "container-5" &&
            window.matchMedia("(max-width: 768px)").matches
          )
            return;
          togglePanel(targetEl);
        }
        closeDrawer();
      });
    });
  }

  // ─── Mobile Bottom Dock & Bottom Sheet Interactions ───────────────────
  const sheetBackdrop = document.getElementById("sheet-backdrop");
  const isMobile = () => window.matchMedia("(max-width: 768px)").matches;

  function closeAllSheets() {
    document
      .querySelectorAll(".sheet-open")
      .forEach((el) => el.classList.remove("sheet-open"));
    document
      .querySelectorAll(".dock-btn.active")
      .forEach((btn) => btn.classList.remove("active"));
    if (sheetBackdrop) sheetBackdrop.classList.remove("visible");
  }

  document.querySelectorAll(".dock-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      if (!isMobile()) return;
      const target = document.getElementById(btn.dataset.target);
      if (!target) return;
      if (breakoutLocked() && target.id === "container-5") return;
      const wasOpen = target.classList.contains("sheet-open");
      closeAllSheets();
      if (!wasOpen) {
        target.classList.add("sheet-open");
        btn.classList.add("active");
        if (sheetBackdrop) sheetBackdrop.classList.add("visible");
      }
    });
  });

  if (sheetBackdrop) sheetBackdrop.addEventListener("click", closeAllSheets);

  // ─── Swipe Left/Right to open/close Messages panel (Mobile) ───────────
  (function () {
    let touchStartX = 0;
    let touchStartY = 0;
    const SWIPE_THRESHOLD = 60;

    document.addEventListener(
      "touchstart",
      function (e) {
        touchStartX = e.changedTouches[0].clientX;
        touchStartY = e.changedTouches[0].clientY;
      },
      { passive: true },
    );

    document.addEventListener(
      "touchend",
      function (e) {
        if (!isMobile()) return;
        const msgPanel = document.getElementById("container-5");
        if (!msgPanel) return;
        const isBreakout = breakoutLocked();

        const dx = e.changedTouches[0].clientX - touchStartX;
        const dy = e.changedTouches[0].clientY - touchStartY;

        // Only horizontal swipes (ignore vertical scrolling)
        if (Math.abs(dx) < SWIPE_THRESHOLD || Math.abs(dy) > Math.abs(dx))
          return;

        if (dx < 0) {
          // Swipe LEFT → open messages
          if (isBreakout) return;
          if (!msgPanel.classList.contains("sheet-open")) {
            closeAllSheets();
            msgPanel.classList.add("sheet-open");
            if (sheetBackdrop) sheetBackdrop.classList.add("visible");
            // Highlight the dock button if it exists
            document.querySelectorAll(".dock-btn").forEach(function (btn) {
              if (btn.dataset.target === "container-5")
                btn.classList.add("active");
            });
          }
        } else {
          // Swipe RIGHT → close messages
          if (msgPanel.classList.contains("sheet-open")) {
            closeAllSheets();
          }
        }
      },
      { passive: true },
    );
  })();

  // ─── Settings Panel: Open/Close ───────────────────────────────────────
  const timerSettingsBtn = document.getElementById("timer-settings");
  if (timerSettingsBtn) {
    timerSettingsBtn.addEventListener("click", () => {
      const panel = document.getElementById("settings-container");
      if (!panel) return;
      if (isMobile()) {
        const wasOpen = panel.classList.contains("sheet-open");
        closeAllSheets();
        if (!wasOpen) {
          panel.classList.add("sheet-open");
          if (sheetBackdrop) sheetBackdrop.classList.add("visible");
        }
      } else {
        panel.style.display =
          panel.style.display === "block" ? "none" : "block";
      }
    });
  }

  const closeSettingsBtn = document.getElementById("close-settings");
  if (closeSettingsBtn) {
    closeSettingsBtn.addEventListener("click", () => {
      const panel = document.getElementById("settings-container");
      if (!panel) return;
      if (isMobile()) {
        closeAllSheets();
      } else {
        panel.style.display = "none";
      }
    });
  }

  // ─── Background Theme ─────────────────────────────────────────────────
  const bgSelect = document.getElementById("background-select");
  if (bgSelect)
    bgSelect.addEventListener("change", () => applyTheme(bgSelect.value));
};

// ─── Panel Visibility Toggle Helper ───────────────────────────────────────
const togglePanel = (targetElement) => {
  if (!targetElement) return;
  if (
    breakoutLocked() &&
    targetElement.id === "container-5" &&
    window.matchMedia("(max-width: 768px)").matches
  )
    return;

  // On mobile, use sheet behavior for panels that support it
  const isMobileView = window.matchMedia("(max-width: 768px)").matches;
  const sheetTargets = [
    "container-3",
    "container-4",
    "container-5",
    "container-6",
    "settings-container",
  ];
  if (isMobileView && sheetTargets.includes(targetElement.id)) {
    const wasOpen = targetElement.classList.contains("sheet-open");
    // Close all open sheets
    document
      .querySelectorAll(".sheet-open")
      .forEach((el) => el.classList.remove("sheet-open"));
    document
      .querySelectorAll(".dock-btn.active")
      .forEach((btn) => btn.classList.remove("active"));
    const backdrop = document.getElementById("sheet-backdrop");
    if (!wasOpen) {
      targetElement.classList.add("sheet-open");
      if (backdrop) backdrop.classList.add("visible");
      // Activate matching dock button
      document.querySelectorAll(".dock-btn").forEach((btn) => {
        if (btn.dataset.target === targetElement.id)
          btn.classList.add("active");
      });
    } else {
      if (backdrop) backdrop.classList.remove("visible");
    }
    return;
  }

  const isHidden =
    targetElement.style.display === "none" ||
    window.getComputedStyle(targetElement).display === "none";
  if (isHidden) {
    targetElement.style.display = "";
    if (window.getComputedStyle(targetElement).display === "none") {
      targetElement.style.display = targetElement.dataset.showAs || "block";
    }
  } else {
    targetElement.style.display = "none";
  }
};

// ─── Timer Start/Resume Handler ───────────────────────────────────────────
const handleStart = () => {
  state.isPaused ? resumeTimer() : startTimer();
};
const handlePause = () => {
  pauseTimer();
};

// ─── Timer Countdown Engine ───────────────────────────────────────────────
const startTimer = () => {
  clearInterval(state.timerInterval);
  state.timerInterval = setInterval(() => {
    if (state.timerValue > 0) {
      state.timerValue--;
      updateTimerDisplay();
    } else {
      clearInterval(state.timerInterval);
      state.pomodoroCount++;
      updatePomodoroCount();
      // Auto-advance to 25 min session after completion
      setTimeType("POMODORO");
    }
  }, 1000);
};

// ─── Timer Display Formatting (MM:SS) ────────────────────────────────────
const updateTimerDisplay = () => {
  elements.timerDisplay.textContent = formatTime(state.timerValue);
};

const formatTime = (seconds) => {
  const m = Math.floor(seconds / 60)
    .toString()
    .padStart(2, "0");
  const s = (seconds % 60).toString().padStart(2, "0");
  return `${m}:${s}`;
};

// ─── Timer Pause/Resume ────────────────────────────────────────────────────
const pauseTimer = () => {
  clearInterval(state.timerInterval);
  state.isPaused = true;
};
const resumeTimer = () => {
  state.isPaused = false;
  startTimer();
};

// ─── Timer Mode Switching ─────────────────────────────────────────────────
const setTimeType = (type) => {
  state.pomodoroType = type;
  state.timerValue = timers[type];
  state.initialTime = timers[type];
  updateActiveButton(type);
  updateTimerDisplay();
};

// ─── Timer Mode Button Active State ──────────────────────────────────────
const updateActiveButton = (type) => {
  elements.pomodoroBtn.classList.toggle("active", type === "POMODORO");
  elements.longpomodoroBtn?.classList.toggle("active", type === "LONGPOMODORO");
  elements.shortbrkBtn.classList.toggle("active", type === "SHORTBREAK");
  elements.longbrkBtn?.classList.toggle("active", type === "LONGBREAK");
  document
    .getElementById("custombrkbtn")
    ?.classList.toggle("active", type === "CUSTOM");
};

// ─── Hide Custom Time Input Wrapper ───────────────────────────────────────
const hideCustomWrap = () => {
  const wrap = document.getElementById("custom-time-wrap");
  if (wrap) wrap.style.display = "none";
};

// ─── Custom Timer ─────────────────────────────────────────────────────────
const applyCustomTime = () => {
  const input = document.getElementById("custom-time-input");
  if (!input) return;
  const minutes = parseInt(input.value);
  if (isNaN(minutes) || minutes < 1 || minutes > 999) {
    input.style.borderColor = "#e74c3c";
    return;
  }
  input.style.borderColor = "";
  clearInterval(state.timerInterval);
  state.timerInterval = null;
  state.isPaused = false;
  state.timerValue = minutes * 60;
  state.initialTime = minutes * 60;
  state.pomodoroType = "CUSTOM";
  updateTimerDisplay();

  // Set custom button as active
  elements.pomodoroBtn.classList.remove("active");
  elements.shortbrkBtn.classList.remove("active");
  document.getElementById("custombrkbtn")?.classList.add("active");

  // Hide the input after applying
  hideCustomWrap();
};

// ─── Timer Reset ──────────────────────────────────────────────────────────
const resetTimer = () => {
  clearInterval(state.timerInterval);
  state.timerValue = state.initialTime;
  state.isPaused = false;
  updateTimerDisplay();
};

// ─── Pomodoro Count Display ───────────────────────────────────────────────
const updatePomodoroCount = () => {
  const display = document.getElementById("pomodoro-counter-display");
  if (display) display.textContent = state.pomodoroCount;
};

// ─── Background Theme (dynamic — reads file paths from <select> data-file) ──
const applyTheme = (theme) => {
  const bgSelect = document.getElementById("background-select");
  if (!bgSelect) return;

  // Find the matching <option> to get the file path
  const opt = bgSelect.querySelector(`option[value="${theme}"]`);
  const file = opt ? opt.dataset.file : null;
  if (file) {
    document.body.style.backgroundImage = `url('${file}')`;
  }

  // Remove all existing theme-* classes, then add the active one
  const themeClasses = [...document.body.classList].filter((c) =>
    c.startsWith("theme-"),
  );
  themeClasses.forEach((c) => document.body.classList.remove(c));
  document.body.classList.add(`theme-${theme}`);

  bgSelect.value = theme;
};

// ─── Initialize ───────────────────────────────────────────────────────────
addEventListeners();
applyTheme("default");

// ============================================================================
// LIVE DIGITAL CLOCK
// ============================================================================
function updateDateTime() {
  const now = new Date();
  const hours = now.getHours();
  const minutes = now.getMinutes();
  const seconds = now.getSeconds();
  const ampm = hours >= 12 ? "PM" : "AM";
  const h = hours % 12 || 12;
  const m = minutes < 10 ? `0${minutes}` : minutes;
  const s = seconds < 10 ? `0${seconds}` : seconds;
  const time = `${h}:${m}:${s} ${ampm}`;

  const monthList = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ];
  const date = `${monthList[now.getMonth()]} ${now.getDate()}, ${now.getFullYear()}`;

  document.getElementById("time").innerHTML = time;
  document.getElementById("date").innerHTML = date;
}

setInterval(updateDateTime, 1000);
updateDateTime();
