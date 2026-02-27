// POMODORO TIMER — PHP-backed functionality
// PHP_TIMERS and PHP_CONFIG are injected by index.php;
// fall back to defaults when running without PHP (e.g. local file preview).
const _PHP_TIMERS =
  typeof PHP_TIMERS !== "undefined"
    ? PHP_TIMERS
    : { POMODORO: 1500, SHORTBREAK: 300, LONGBREAK: 900 };
const _PHP_CONFIG =
  typeof PHP_CONFIG !== "undefined"
    ? PHP_CONFIG
    : {
        pomodorosUntilLongBreak: 4,
        initialCount: 0,
        backgroundTheme: "default",
      };

const elements = {
  startBtn: document.querySelector("#start-button"),
  pauseBtn: document.querySelector("#pause-button"),
  resetBtn: document.querySelector("#restart-button"),
  pomodoroBtn: document.getElementById("pomodorobtn"),
  shortbrkBtn: document.getElementById("shortbrkbtn"),
  longbrkBtn: document.getElementById("longbrkbtn"),
  pomCount: document.querySelector(".pomodoro-count"),
  timerDisplay: document.querySelector(".timer-display"),
  saveSettingsBtn: document.getElementById("save-settings-btn"),
  bgSelect: document.getElementById("background-select"),
  closeSettings: document.getElementById("close-settings"),
};

// Durations (seconds) — sourced from PHP server settings
const timers = {
  POMODORO: _PHP_TIMERS.POMODORO,
  SHORTBREAK: _PHP_TIMERS.SHORTBREAK,
  LONGBREAK: _PHP_TIMERS.LONGBREAK,
};

const state = {
  pomodoroCount: _PHP_CONFIG.initialCount, // seeded from DB
  pomodorosUntilLongBreak: _PHP_CONFIG.pomodorosUntilLongBreak,
  timerValue: timers.POMODORO,
  initialTime: timers.POMODORO,
  timerInterval: null,
  isPaused: false,
  pomodoroType: "POMODORO",
};

const addEventListeners = () => {
  elements.startBtn.addEventListener("click", handleStart);
  elements.pauseBtn.addEventListener("click", handlePause);
  elements.pomodoroBtn.addEventListener("click", () => setTimeType("POMODORO"));
  elements.shortbrkBtn.addEventListener("click", () =>
    setTimeType("SHORTBREAK"),
  );
  elements.longbrkBtn.addEventListener("click", () => setTimeType("LONGBREAK"));
  elements.resetBtn.addEventListener("click", resetTimer);

  if (elements.saveSettingsBtn) {
    elements.saveSettingsBtn.addEventListener("click", handleSaveSettings);
  }
  if (elements.closeSettings) {
    elements.closeSettings.addEventListener("click", () => {
      const panel = document.getElementById("settings-container");
      if (panel) panel.style.display = "none";
    });
  }

  document.querySelectorAll(".nav-link").forEach((link) => {
    link.addEventListener("click", handleNavClick);
  });
};

const handleNavClick = (event) => {
  event.preventDefault();

  // Get the ID of the target container
  const targetId = event.target.closest("a").dataset.target;

  // Get the target container
  const targetElement = document.getElementById(targetId);

  // Toggle visibility of the target container
  if (targetElement) {
    // If the container is already visible, hide it
    if (targetElement.style.display === "block") {
      targetElement.style.display = "none";
    } else {
      // Otherwise, show the container
      targetElement.style.display = "block";
    }
  }
};

// Add event listeners to all navigation links
const handleStart = () => {
  state.isPaused ? resumeTimer() : startTimer();
};

const handlePause = () => {
  pauseTimer();
};

const startTimer = () => {
  clearInterval(state.timerInterval);
  state.timerInterval = setInterval(() => {
    if (state.timerValue > 0) {
      state.timerValue--;
      updateTimerDisplay();
    } else {
      clearInterval(state.timerInterval);
      // Save completed session to the PHP backend
      saveSession(state.pomodoroType, state.initialTime);
      if (state.pomodoroType === "POMODORO") {
        state.pomodoroCount++;
        updatePomodoroCount();
      }
      if (state.pomodoroCount % state.pomodorosUntilLongBreak === 0) {
        setTimeType("LONGBREAK");
      } else {
        setTimeType("SHORTBREAK");
      }
    }
  }, 1000);
};

const updateTimerDisplay = () => {
  elements.timerDisplay.textContent = formatTime(state.timerValue);
};

const formatTime = (seconds) => {
  const minutes = Math.floor(seconds / 60)
    .toString()
    .padStart(2, "0");
  const secs = (seconds % 60).toString().padStart(2, "0");
  return `${minutes}:${secs}`;
};

const pauseTimer = () => {
  clearInterval(state.timerInterval);
  state.isPaused = true;
};

const resumeTimer = () => {
  state.isPaused = false;
  startTimer();
};

const setTimeType = (type) => {
  state.pomodoroType = type;
  state.timerValue = timers[type];
  state.initialTime = timers[type]; // Update initial time when time type is set
  updateActiveButton(type);
  updateTimerDisplay();
};

const updateActiveButton = (type) => {
  elements.pomodoroBtn.classList.toggle("active", type === "POMODORO");
  elements.shortbrkBtn.classList.toggle("active", type === "SHORTBREAK");
  elements.longbrkBtn.classList.toggle("active", type === "LONGBREAK");
};

const resetTimer = () => {
  clearInterval(state.timerInterval);
  state.timerValue = state.initialTime; // Reset to the initially set time
  updateTimerDisplay();
  state.isPaused = false;
};

const updatePomodoroCount = () => {
  elements.pomCount.style.display = "block";
  elements.pomCount.style.color = "white";
  elements.pomCount.style.fontSize = "30px";
  elements.pomCount.textContent = `Pomodoro Count: ${state.pomodoroCount}`;

  // Also update the lifetime counter rendered by PHP
  const lifetimeEl = document.getElementById("lifetime-count");
  if (lifetimeEl) {
    lifetimeEl.innerHTML = `Lifetime Pomodoros: <strong>${state.pomodoroCount}</strong>`;
  }
};

// ─── PHP API helpers ───────────────────────────────────────────────────────

/**
 * POST a completed session to api/save_session.php
 * @param {string} type     - 'POMODORO' | 'SHORTBREAK' | 'LONGBREAK'
 * @param {number} duration - duration in seconds
 */
// saveSession is a no-op without a database — counts are tracked in JS state
const saveSession = (_type, _duration) => {};

/**
 * Reads current settings inputs and POSTs them to api/save_settings.php.
 * Also refreshes the active timer durations so changes take effect immediately.
 */
const handleSaveSettings = () => {
  const pomodoro =
    parseInt(document.getElementById("pomodoro-duration")?.value) || 25;
  const shortBreak =
    parseInt(document.getElementById("short-break-duration")?.value) || 5;
  const longBreak =
    parseInt(document.getElementById("long-break-duration")?.value) || 15;
  const untilLong =
    parseInt(document.getElementById("until-long-break")?.value) || 4;
  const theme = elements.bgSelect?.value || "default";

  // Update in-memory timers
  timers.POMODORO = pomodoro * 60;
  timers.SHORTBREAK = shortBreak * 60;
  timers.LONGBREAK = longBreak * 60;
  state.pomodorosUntilLongBreak = untilLong;

  // Re-apply the current timer type so the display reflects new duration
  setTimeType(state.pomodoroType);
  applyTheme(theme);

  // Close settings panel
  const panel = document.getElementById("settings-container");
  if (panel) panel.style.display = "none";
};

// ─── Apply background theme on load ───────────────────────────────────────
const applyTheme = (theme) => {
  const themes = {
    default:
      "https://i.pinimg.com/originals/41/fd/4b/41fd4b8362d93cb0bdc7117bf92ee8a2.jpg",
    theme1:
      "https://i.pinimg.com/originals/3b/73/99/3b7399c74894fb9b1fb27b3e4c8e3b1e.jpg",
    theme2:
      "https://i.pinimg.com/originals/51/02/0f/51020f0a2b56b35e10d22d6bc4deab5d.jpg",
    theme3:
      "https://i.pinimg.com/originals/08/d2/e9/08d2e9d8b4c7f7c9edbe5d5cd4b37e4a.jpg",
    theme4:
      "https://i.pinimg.com/originals/58/2a/d3/582ad3a92c1f4c5d73ade7dd0f5d70e3.jpg",
  };
  document.body.style.backgroundImage = `url('${themes[theme] || themes.default}')`;
  if (elements.bgSelect) elements.bgSelect.value = theme;
};

applyTheme(_PHP_CONFIG.backgroundTheme);

// Sync theme preview when dropdown changes (before Save is clicked)
if (elements.bgSelect) {
  elements.bgSelect.addEventListener("change", () =>
    applyTheme(elements.bgSelect.value),
  );
}

const setCustomTime = (minutes) => {
  clearInterval(state.timerInterval);
  state.timerValue = minutes * 60;
  state.initialTime = state.timerValue; // Update initial time when custom time is set
  updateTimerDisplay();
};

addEventListeners();

// THE START OF THE CLOCK  FUNCTIONALITY

function updateDateTime() {
  const now = new Date();

  const hours = now.getHours();
  const minutes = now.getMinutes();
  const seconds = now.getSeconds();

  const ampm = hours >= 12 ? "PM" : "AM";
  const hourTime = hours % 12 || 12; // Convert 24hr to 12hr format
  const minute = minutes < 10 ? `0${minutes}` : minutes;
  const second = seconds < 10 ? `0${seconds}` : seconds;

  const time = `${hourTime}:${minute}:${second} ${ampm}`;

  const month = now.getMonth();
  const year = now.getFullYear();
  const day = now.getDate();

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

  const date = `${monthList[month]} ${day}, ${year}`;

  // Update the time and date in the respective elements
  document.getElementById("time").innerHTML = time;
  document.getElementById("date").innerHTML = date;
}

setInterval(updateDateTime, 1000);
updateDateTime();
