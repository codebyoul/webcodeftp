// WebCodeFTP Theme Handler
// Manages dark/light theme switching and language preferences

// Apply theme IMMEDIATELY (before page renders - prevents flash)
(function () {
  const savedTheme = localStorage.getItem("theme") || "dark";
  if (savedTheme === "dark") {
    document.documentElement.classList.add("dark");
  } else {
    document.documentElement.classList.remove("dark");
  }
})();

// Theme Switcher
function switchTheme(theme) {
  // Update DOM
  if (theme === "dark") {
    document.documentElement.classList.add("dark");
  } else {
    document.documentElement.classList.remove("dark");
  }

  // Save to localStorage (persists across sessions)
  localStorage.setItem("theme", theme);

  // Sync to server session (for server-side rendering)
  fetch("/api/theme", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "theme=" + theme,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Update button states
        updateThemeButtons(theme);

        // Update CodeMirror theme if editor is open
        if (window.codeMirrorEditor && window.codeMirrorEditor.updateTheme) {
          window.codeMirrorEditor.updateTheme();
        }
      }
    });
}

// Language Switcher
function switchLanguage(language) {
  // Save to cookie (works on both server and client)
  const cookieLifetime = 31536000; // 1 year in seconds
  document.cookie =
    "webftp_language=" +
    language +
    "; path=/; max-age=" +
    cookieLifetime +
    "; SameSite=Strict";

  // Sync to server session
  fetch("/api/language", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: "language=" + language,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Reload page to apply language changes
        location.reload();
      }
    });
}

// Update theme button states
function updateThemeButtons(theme) {
  const lightBtn = document.querySelector(
    "[onclick*=\"switchTheme('light')\"]"
  );
  const darkBtn = document.querySelector("[onclick*=\"switchTheme('dark')\"]");

  if (!lightBtn || !darkBtn) return;

  const activeClasses =
    "bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 font-medium";
  const inactiveClasses =
    "text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700";

  if (theme === "light") {
    lightBtn.className =
      "flex-1 px-3 py-2 text-sm rounded-md transition " + activeClasses;
    darkBtn.className =
      "flex-1 px-3 py-2 text-sm rounded-md transition " + inactiveClasses;
  } else {
    lightBtn.className =
      "flex-1 px-3 py-2 text-sm rounded-md transition " + inactiveClasses;
    darkBtn.className =
      "flex-1 px-3 py-2 text-sm rounded-md transition " + activeClasses;
  }
}

// Sync theme from localStorage to server on page load
document.addEventListener("DOMContentLoaded", function () {
  const savedTheme = localStorage.getItem("theme") || "dark";
  const serverTheme = window.APP_CONFIG?.theme || "dark";

  // Sync theme to server if different from PHP session
  if (savedTheme !== serverTheme) {
    fetch("/api/theme", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: "theme=" + savedTheme,
    });
  }

  // Update button states to match current theme
  updateThemeButtons(savedTheme);
});
