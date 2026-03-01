// POMODORO TIMER JAVA FUNCTIONALITY
const elements = {
  startBtn: document.querySelector("#start-button"),
  pauseBtn: document.querySelector("#pause-button"),
  resetBtn: document.querySelector("#restart-button"),
  pomodoroBtn: document.getElementById("pomodorobtn"),
  shortbrkBtn: document.getElementById("shortbrkbtn"),
  longbrkBtn: document.getElementById("longbrkbtn"),
  pomCount: document.querySelector(".pomodoro-count"),
  timerDisplay: document.querySelector(".timer-display"),
};

const timers = {
  POMODORO: (typeof PHP_TIMERS !== "undefined" && PHP_TIMERS.POMODORO) || 1500,
  SHORTBREAK:
    (typeof PHP_TIMERS !== "undefined" && PHP_TIMERS.SHORTBREAK) || 300,
  LONGBREAK: (typeof PHP_TIMERS !== "undefined" && PHP_TIMERS.LONGBREAK) || 900,
};

const state = {
  pomodoroCount:
    (typeof PHP_CONFIG !== "undefined" && PHP_CONFIG.initialCount) || 0,
  pomodorosUntilLongBreak:
    (typeof PHP_CONFIG !== "undefined" && PHP_CONFIG.pomodorosUntilLongBreak) ||
    4,
  timerValue:
    (typeof PHP_TIMERS !== "undefined" && PHP_TIMERS.POMODORO) || 1500,
  initialTime:
    (typeof PHP_TIMERS !== "undefined" && PHP_TIMERS.POMODORO) || 1500,
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

  // ─── Nav drawer (hamburger menu) ───────────────────
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

    // Nav drawer link clicks toggle panels (desktop)
    navDrawer.querySelectorAll(".nav-link").forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault();
        const targetId = link.dataset.target;
        const targetEl = document.getElementById(targetId);
        if (targetEl) {
          togglePanel(targetEl);
          // If link specifies a tab, switch to it
          const tab = link.dataset.tab;
          if (tab && typeof pxSwitchTab === "function") {
            pxSwitchTab(tab);
          }
        }
        closeDrawer();
      });
    });
  }

  // (Lofi FAB removed — YouTube is now a tab inside container-3)

  // ─── Mobile bottom dock + bottom sheets ────────────
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
      const targetId = btn.dataset.target;
      const target = document.getElementById(targetId);
      if (!target) return;

      const wasOpen = target.classList.contains("sheet-open");
      closeAllSheets();

      if (!wasOpen) {
        target.classList.add("sheet-open");
        btn.classList.add("active");
        if (sheetBackdrop) sheetBackdrop.classList.add("visible");
      }

      // Switch tab if specified
      const tab = btn.dataset.tab;
      if (tab && typeof pxSwitchTab === "function") {
        pxSwitchTab(tab);
      }
    });
  });

  if (sheetBackdrop) {
    sheetBackdrop.addEventListener("click", closeAllSheets);
  }

  // Gear button opens settings panel
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

  // X button closes settings panel
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

  // Background theme live preview
  const bgSelect = document.getElementById("background-select");
  if (bgSelect) {
    bgSelect.addEventListener("change", () => applyTheme(bgSelect.value));
  }
};

const togglePanel = (targetElement) => {
  if (targetElement) {
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
  }
};

const handleNavClick = (event) => {
  event.preventDefault();

  // Get the ID of the target container
  const link = event.target.closest("a");
  if (!link) return;
  const targetId = link.dataset.target;

  // Get the target container
  const targetElement = document.getElementById(targetId);

  togglePanel(targetElement);
};

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
      state.pomodoroCount++;
      updatePomodoroCount();
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
};

const applyTheme = (theme) => {
  const themes = {
    default: "themes/theme 2.gif",
    theme1: "themes/theme1.jpg",
    theme2: "themes/default.jpg",
    theme3: "themes/theme3.jpg",
  };
  document.body.style.backgroundImage = `url('${themes[theme] || themes.default}')`;

  // Swap theme colour class
  document.body.classList.remove(
    "theme-default",
    "theme-theme1",
    "theme-theme2",
    "theme-theme3",
  );
  document.body.classList.add(`theme-${theme}`);

  const bgSelect = document.getElementById("background-select");
  if (bgSelect) bgSelect.value = theme;
};

const setCustomTime = (minutes) => {
  clearInterval(state.timerInterval);
  state.timerValue = minutes * 60;
  state.initialTime = state.timerValue; // Update initial time when custom time is set
  updateTimerDisplay();
};

addEventListeners();

// Apply theme from PHP config or default
const initialTheme =
  (typeof PHP_CONFIG !== "undefined" && PHP_CONFIG.backgroundTheme) ||
  "default";
applyTheme(initialTheme);

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
