// WebCodeFTP Toast Notification System
// Elite, professional toast notifications with configurable duration
// NO progress bar - clean, simple design

/**
 * ============================================================================
 * TOAST NOTIFICATION API
 * ============================================================================
 *
 * Modern, non-blocking notification system for user feedback.
 * Used by all major applications (GitHub, Gmail, Slack, etc.)
 *
 * Usage:
 *   showToast.error('Failed to delete', 'Invalid paths array', { duration: 5000 });
 *   showToast.success('Files deleted', '5 items removed', { duration: 3000 });
 *   showToast.warning('Session expiring', 'Please save your work');
 *   showToast.info('Upload started', 'Processing 10 files...');
 */

(function () {
  "use strict";

  // Configuration - Load from PHP config or use defaults
  const userConfig = window.webftpToastConfig || {};
  const CONFIG = {
    MAX_TOASTS: userConfig.max_toasts || 5, // Maximum simultaneous toasts
    ANIMATION_DURATION: userConfig.animation_duration || 300, // Slide-in/out animation time
    POSITION: userConfig.position || "top-right", // Toast position

    // Duration per toast type (professional UX - errors stay longer, success shorter)
    DURATIONS: userConfig.durations || {
      error: 8000, // 8 seconds - Critical messages
      warning: 6000, // 6 seconds - Important information
      success: 3000, // 3 seconds - Quick confirmation
      info: 5000, // 5 seconds - Moderate importance
    },

    // Fallback for custom types
    DEFAULT_DURATION: userConfig.default_duration || 5000,
  };

  // Position class mapping for Tailwind CSS
  const POSITION_CLASSES = {
    "top-right": "top-4 right-4",
    "top-left": "top-4 left-4",
    "bottom-right": "bottom-4 right-4",
    "bottom-left": "bottom-4 left-4",
  };

  // Toast type configurations
  const TOAST_TYPES = {
    error: {
      icon: "fas fa-circle-xmark",
      iconColor: "text-red-600 dark:text-red-400",
      borderColor: "border-red-500",
      titleColor: "text-gray-900 dark:text-white",
      messageColor: "text-gray-700 dark:text-gray-300",
    },
    success: {
      icon: "fas fa-circle-check",
      iconColor: "text-green-600 dark:text-green-400",
      borderColor: "border-green-500",
      titleColor: "text-gray-900 dark:text-white",
      messageColor: "text-gray-700 dark:text-gray-300",
    },
    warning: {
      icon: "fas fa-triangle-exclamation",
      iconColor: "text-yellow-600 dark:text-yellow-400",
      borderColor: "border-yellow-500",
      titleColor: "text-gray-900 dark:text-white",
      messageColor: "text-gray-700 dark:text-gray-300",
    },
    info: {
      icon: "fas fa-circle-info",
      iconColor: "text-blue-600 dark:text-blue-400",
      borderColor: "border-blue-500",
      titleColor: "text-gray-900 dark:text-white",
      messageColor: "text-gray-700 dark:text-gray-300",
    },
  };

  // Active toasts tracking
  const activeToasts = [];

  /**
   * Create and show a toast notification
   * @param {string} type - Toast type (error, success, warning, info)
   * @param {string} title - Toast title (user-friendly message)
   * @param {string} message - Toast message (technical details)
   * @param {Object} options - Configuration options
   * @param {number} options.duration - Auto-dismiss duration in ms (uses type-specific duration if not provided)
   * @param {boolean} options.closable - Show close button (default: true)
   * @param {Function} options.onClose - Callback when toast closes
   */
  function createToast(type, title, message = "", options = {}) {
    // Use type-specific duration if not explicitly provided (professional UX)
    const typeDuration = CONFIG.DURATIONS[type] || CONFIG.DEFAULT_DURATION;

    // Merge with defaults
    const config = {
      duration:
        options.duration !== undefined ? options.duration : typeDuration,
      closable: options.closable !== undefined ? options.closable : true,
      onClose: options.onClose || null,
    };

    // Check if we have too many toasts - remove oldest
    if (activeToasts.length >= CONFIG.MAX_TOASTS) {
      const oldestToast = activeToasts[0];
      removeToast(oldestToast.element, oldestToast);
    }

    // Get template and container
    const template = document.getElementById("toastTemplate");
    const container = document.getElementById("toastContainer");

    if (!template || !container) {
      console.error("Toast template or container not found");
      return null;
    }

    // Clone template
    const toastElement = template.content
      .cloneNode(true)
      .querySelector(".toast");
    const typeConfig = TOAST_TYPES[type] || TOAST_TYPES.info;

    // Apply styling
    toastElement.classList.add(typeConfig.borderColor);

    // Set icon
    const iconEl = toastElement.querySelector(".toast-icon");
    iconEl.className = `toast-icon ${typeConfig.icon} ${typeConfig.iconColor} text-xl`;

    // Set title
    const titleEl = toastElement.querySelector(".toast-title");
    titleEl.className = `toast-title font-semibold text-sm ${typeConfig.titleColor}`;
    titleEl.textContent = title;

    // Set message
    const messageEl = toastElement.querySelector(".toast-message");
    messageEl.className = `toast-message text-xs mt-1 ${typeConfig.messageColor}`;
    messageEl.textContent = message;

    // Handle close button
    const closeBtn = toastElement.querySelector(".toast-close");
    if (config.closable) {
      closeBtn.addEventListener("click", () => {
        removeToast(toastElement, toastData);
      });
    } else {
      closeBtn.remove();
    }

    // Add to container
    container.appendChild(toastElement);

    // Create toast data object
    const toastData = {
      element: toastElement,
      timeout: null,
      config: config,
    };

    // Track active toast
    activeToasts.push(toastData);

    // Trigger slide-in animation
    requestAnimationFrame(() => {
      toastElement.classList.remove("translate-x-full", "opacity-0");
      toastElement.classList.add("translate-x-0", "opacity-100");
    });

    // Set auto-dismiss timeout
    if (config.duration > 0) {
      toastData.timeout = setTimeout(() => {
        removeToast(toastElement, toastData);
      }, config.duration);
    }

    return toastData;
  }

  /**
   * Remove a toast with animation
   * @param {HTMLElement} toastElement - Toast DOM element
   * @param {Object} toastData - Toast data object
   */
  function removeToast(toastElement, toastData) {
    // Clear timeout if exists
    if (toastData.timeout) {
      clearTimeout(toastData.timeout);
    }

    // Remove from active toasts
    const index = activeToasts.indexOf(toastData);
    if (index > -1) {
      activeToasts.splice(index, 1);
    }

    // Trigger slide-out animation
    toastElement.classList.remove("translate-x-0", "opacity-100");
    toastElement.classList.add("translate-x-full", "opacity-0");

    // Remove from DOM after animation
    setTimeout(() => {
      if (toastElement.parentNode) {
        toastElement.parentNode.removeChild(toastElement);
      }

      // Call onClose callback
      if (toastData.config.onClose) {
        toastData.config.onClose();
      }
    }, CONFIG.ANIMATION_DURATION);
  }

  /**
   * Remove all active toasts
   */
  function clearAllToasts() {
    // Clone array to avoid modification during iteration
    const toastsToRemove = [...activeToasts];
    toastsToRemove.forEach((toastData) => {
      removeToast(toastData.element, toastData);
    });
  }

  /**
   * Initialize toast container with configured position
   */
  function initializeToastContainer() {
    const container = document.getElementById("toastContainer");
    if (!container) {
      console.error("Toast container not found");
      return;
    }

    // Remove all position classes
    Object.values(POSITION_CLASSES).forEach((classes) => {
      classes.split(" ").forEach((cls) => container.classList.remove(cls));
    });

    // Apply configured position classes
    const positionClasses =
      POSITION_CLASSES[CONFIG.POSITION] || POSITION_CLASSES["top-right"];
    positionClasses.split(" ").forEach((cls) => container.classList.add(cls));
  }

  // Initialize on DOM ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeToastContainer);
  } else {
    initializeToastContainer();
  }

  // Public API - Expose to global window object
  window.showToast = {
    /**
     * Show error toast (red)
     * @param {string} title - Error title
     * @param {string} message - Error details
     * @param {Object} options - Options (duration, closable, onClose)
     */
    error: function (title, message = "", options = {}) {
      return createToast("error", title, message, options);
    },

    /**
     * Show success toast (green)
     * @param {string} title - Success title
     * @param {string} message - Success details
     * @param {Object} options - Options (duration, closable, onClose)
     */
    success: function (title, message = "", options = {}) {
      return createToast("success", title, message, options);
    },

    /**
     * Show warning toast (yellow/orange)
     * @param {string} title - Warning title
     * @param {string} message - Warning details
     * @param {Object} options - Options (duration, closable, onClose)
     */
    warning: function (title, message = "", options = {}) {
      return createToast("warning", title, message, options);
    },

    /**
     * Show info toast (blue)
     * @param {string} title - Info title
     * @param {string} message - Info details
     * @param {Object} options - Options (duration, closable, onClose)
     */
    info: function (title, message = "", options = {}) {
      return createToast("info", title, message, options);
    },

    /**
     * Clear all active toasts
     */
    clearAll: function () {
      clearAllToasts();
    },
  };

  console.log(
    "Toast Notification System loaded (position: " +
      CONFIG.POSITION +
      ") - Use showToast.error(), .success(), .warning(), .info()"
  );
})();
