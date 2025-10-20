// WebFTP File Manager JavaScript
// Handles file operations, UI interactions, and file browser logic
// NOTE: api-interceptor.js must be loaded BEFORE this file for 401 handling

/**
 * ============================================================================
 * CUSTOM DIALOG SYSTEM - Replaces showDialog(), confirm(), prompt()
 * ============================================================================
 */

/**
 * Show custom alert dialog
 * @param {string} message - The message to display
 * @param {string} title - Optional dialog title (default: "Alert")
 * @returns {Promise} Resolves when user clicks OK
 */
window.showDialog = function (message, title = "Alert") {
  return new Promise((resolve) => {
    const dialog = document.getElementById("customDialog");
    const dialogBox = document.getElementById("customDialogBox");
    const titleEl = document.getElementById("customDialogTitle");
    const messageEl = document.getElementById("customDialogMessage");
    const inputEl = document.getElementById("customDialogInput");
    const confirmBtn = document.getElementById("customDialogConfirm");
    const cancelBtn = document.getElementById("customDialogCancel");

    // Set content
    titleEl.textContent = title;
    messageEl.textContent = message;

    // Hide input and cancel button (alert only has OK)
    inputEl.classList.add("hidden");
    cancelBtn.classList.add("hidden");
    confirmBtn.textContent = "OK";

    // Show dialog with animation
    dialog.classList.remove("hidden");
    dialog.classList.add("flex");
    // Trigger reflow to enable CSS transition
    dialog.offsetHeight;
    dialog.classList.remove("opacity-0");
    dialogBox.classList.remove("scale-95");
    dialogBox.classList.add("scale-100");

    // Handle OK click
    const handleConfirm = () => {
      closeDialog();
      resolve();
    };

    // Handle Escape key
    const handleEscape = (e) => {
      if (e.key === "Escape") {
        closeDialog();
        resolve();
      }
    };

    const closeDialog = () => {
      dialog.classList.add("opacity-0");
      dialogBox.classList.remove("scale-100");
      dialogBox.classList.add("scale-95");
      setTimeout(() => {
        dialog.classList.remove("flex");
        dialog.classList.add("hidden");
      }, 200);
      confirmBtn.removeEventListener("click", handleConfirm);
      document.removeEventListener("keydown", handleEscape);
    };

    confirmBtn.addEventListener("click", handleConfirm);
    document.addEventListener("keydown", handleEscape);

    // Focus the OK button
    setTimeout(() => confirmBtn.focus(), 250);
  });
};

/**
 * Show custom confirm dialog
 * @param {string} message - The message to display
 * @param {string} title - Optional dialog title (default: "Confirm")
 * @returns {Promise<boolean>} Resolves with true if OK, false if Cancel
 */
window.showConfirm = function (message, title = "Confirm") {
  return new Promise((resolve) => {
    const dialog = document.getElementById("customDialog");
    const dialogBox = document.getElementById("customDialogBox");
    const titleEl = document.getElementById("customDialogTitle");
    const messageEl = document.getElementById("customDialogMessage");
    const inputEl = document.getElementById("customDialogInput");
    const confirmBtn = document.getElementById("customDialogConfirm");
    const cancelBtn = document.getElementById("customDialogCancel");

    // Set content
    titleEl.textContent = title;
    messageEl.textContent = message;

    // Show cancel button, hide input
    inputEl.classList.add("hidden");
    cancelBtn.classList.remove("hidden");
    confirmBtn.textContent = "OK";
    cancelBtn.textContent = "Cancel";

    // Show dialog with animation
    dialog.classList.remove("hidden");
    dialog.classList.add("flex");
    // Trigger reflow to enable CSS transition
    dialog.offsetHeight;
    dialog.classList.remove("opacity-0");
    dialogBox.classList.remove("scale-95");
    dialogBox.classList.add("scale-100");

    // Handle OK click
    const handleConfirm = () => {
      closeDialog();
      resolve(true);
    };

    // Handle Cancel click
    const handleCancel = () => {
      closeDialog();
      resolve(false);
    };

    // Handle Escape key
    const handleEscape = (e) => {
      if (e.key === "Escape") {
        closeDialog();
        resolve(false);
      }
    };

    const closeDialog = () => {
      dialog.classList.add("opacity-0");
      dialogBox.classList.remove("scale-100");
      dialogBox.classList.add("scale-95");
      setTimeout(() => {
        dialog.classList.remove("flex");
        dialog.classList.add("hidden");
      }, 200);
      confirmBtn.removeEventListener("click", handleConfirm);
      cancelBtn.removeEventListener("click", handleCancel);
      document.removeEventListener("keydown", handleEscape);
    };

    confirmBtn.addEventListener("click", handleConfirm);
    cancelBtn.addEventListener("click", handleCancel);
    document.addEventListener("keydown", handleEscape);

    // Focus the confirm button
    setTimeout(() => confirmBtn.focus(), 250);
  });
};

/**
 * Show custom prompt dialog
 * @param {string} message - The message to display
 * @param {string} defaultValue - Default input value
 * @param {string} title - Optional dialog title (default: "Input")
 * @returns {Promise<string|null>} Resolves with input value if OK, null if Cancel
 */
window.showPrompt = function (message, defaultValue = "", title = "Input") {
  return new Promise((resolve) => {
    const dialog = document.getElementById("customDialog");
    const dialogBox = document.getElementById("customDialogBox");
    const titleEl = document.getElementById("customDialogTitle");
    const messageEl = document.getElementById("customDialogMessage");
    const inputEl = document.getElementById("customDialogInput");
    const confirmBtn = document.getElementById("customDialogConfirm");
    const cancelBtn = document.getElementById("customDialogCancel");

    // Set content
    titleEl.textContent = title;
    messageEl.textContent = message;
    inputEl.value = defaultValue;

    // Show input and cancel button
    inputEl.classList.remove("hidden");
    cancelBtn.classList.remove("hidden");
    confirmBtn.textContent = "OK";
    cancelBtn.textContent = "Cancel";

    // Show dialog with animation
    dialog.classList.remove("hidden");
    dialog.classList.add("flex");
    // Trigger reflow to enable CSS transition
    dialog.offsetHeight;
    dialog.classList.remove("opacity-0");
    dialogBox.classList.remove("scale-95");
    dialogBox.classList.add("scale-100");

    // Handle OK click
    const handleConfirm = () => {
      const value = inputEl.value.trim();
      closeDialog();
      resolve(value || null);
    };

    // Handle Cancel click
    const handleCancel = () => {
      closeDialog();
      resolve(null);
    };

    // Handle Enter key in input
    const handleEnter = (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        handleConfirm();
      }
    };

    // Handle Escape key
    const handleEscape = (e) => {
      if (e.key === "Escape") {
        closeDialog();
        resolve(null);
      }
    };

    const closeDialog = () => {
      dialog.classList.add("opacity-0");
      dialogBox.classList.remove("scale-100");
      dialogBox.classList.add("scale-95");
      setTimeout(() => {
        dialog.classList.remove("flex");
        dialog.classList.add("hidden");
      }, 200);
      confirmBtn.removeEventListener("click", handleConfirm);
      cancelBtn.removeEventListener("click", handleCancel);
      inputEl.removeEventListener("keydown", handleEnter);
      document.removeEventListener("keydown", handleEscape);
    };

    confirmBtn.addEventListener("click", handleConfirm);
    cancelBtn.addEventListener("click", handleCancel);
    inputEl.addEventListener("keydown", handleEnter);
    document.addEventListener("keydown", handleEscape);

    // Focus and select the input
    setTimeout(() => {
      inputEl.focus();
      inputEl.select();
    }, 250);
  });
};

/**
 * Show elite delete confirmation dialog with smart handling for large selections
 * Uses generic dialog content areas (no HTML building in JS!)
 * @param {Array} items - Array of selected items to delete
 * @returns {Promise<boolean>} Resolves with true if confirmed, false if cancelled
 */
window.showDeleteConfirm = function (items) {
  return new Promise((resolve) => {
    const dialog = document.getElementById("customDialog");
    const dialogBox = document.getElementById("customDialogBox");
    const titleEl = document.getElementById("customDialogTitle");
    const messageEl = document.getElementById("customDialogMessage");
    const summarySection = document.getElementById("customDialogSummary");
    const summaryText = document.getElementById("customDialogSummaryText");
    const itemListSection = document.getElementById("customDialogItemList");
    const itemsUl = document.getElementById("customDialogItems");
    const warningSection = document.getElementById("customDialogWarning");
    const warningMessage = document.getElementById("customDialogWarningMessage");
    const inputEl = document.getElementById("customDialogInput");
    const confirmBtn = document.getElementById("customDialogConfirm");
    const cancelBtn = document.getElementById("customDialogCancel");

    // Count file types
    const fileCount = items.filter(item => item.type === 'file').length;
    const folderCount = items.filter(item => item.type === 'folder').length;
    const totalCount = items.length;

    // Build summary
    let summary = "";
    if (fileCount > 0 && folderCount > 0) {
      summary = `${fileCount} file${fileCount !== 1 ? 's' : ''} and ${folderCount} folder${folderCount !== 1 ? 's' : ''}`;
    } else if (fileCount > 0) {
      summary = `${fileCount} file${fileCount !== 1 ? 's' : ''}`;
    } else {
      summary = `${folderCount} folder${folderCount !== 1 ? 's' : ''}`;
    }

    // Set title
    titleEl.textContent = "Confirm Deletion";

    // Hide basic message, show summary section
    messageEl.textContent = "";
    summarySection.classList.remove("hidden");
    summaryText.textContent = `Are you sure you want to delete ${summary}?`;

    // Populate item list
    itemsUl.innerHTML = "";
    const MAX_DISPLAY = 10;

    if (totalCount <= MAX_DISPLAY) {
      // Show all items
      items.forEach(item => {
        const li = document.createElement("li");
        li.className = "flex items-center gap-2 text-gray-700 dark:text-gray-300";

        const icon = document.createElement("i");
        icon.className = item.type === 'folder'
          ? "fas fa-folder text-blue-500 dark:text-blue-400 w-4"
          : "fas fa-file text-gray-500 dark:text-gray-400 w-4";

        const nameSpan = document.createElement("span");
        nameSpan.className = "truncate";
        nameSpan.textContent = item.name;

        li.appendChild(icon);
        li.appendChild(nameSpan);
        itemsUl.appendChild(li);
      });
    } else {
      // Show first 10 items
      items.slice(0, MAX_DISPLAY).forEach(item => {
        const li = document.createElement("li");
        li.className = "flex items-center gap-2 text-gray-700 dark:text-gray-300";

        const icon = document.createElement("i");
        icon.className = item.type === 'folder'
          ? "fas fa-folder text-blue-500 dark:text-blue-400 w-4"
          : "fas fa-file text-gray-500 dark:text-gray-400 w-4";

        const nameSpan = document.createElement("span");
        nameSpan.className = "truncate";
        nameSpan.textContent = item.name;

        li.appendChild(icon);
        li.appendChild(nameSpan);
        itemsUl.appendChild(li);
      });

      // Add "and X more..." item
      const remaining = totalCount - MAX_DISPLAY;
      const moreLi = document.createElement("li");
      moreLi.className = "flex items-center gap-2 text-gray-500 dark:text-gray-400 italic mt-2 pt-2 border-t border-gray-300 dark:border-gray-600";

      const moreIcon = document.createElement("i");
      moreIcon.className = "fas fa-ellipsis-h w-4";

      const moreSpan = document.createElement("span");
      moreSpan.textContent = `and ${remaining} more item${remaining !== 1 ? 's' : ''}...`;

      moreLi.appendChild(moreIcon);
      moreLi.appendChild(moreSpan);
      itemsUl.appendChild(moreLi);
    }

    // Show item list section
    itemListSection.classList.remove("hidden");

    // Show warning section
    warningSection.classList.remove("hidden");
    warningMessage.textContent = "This action cannot be undone!";

    // Hide input, show cancel button
    inputEl.classList.add("hidden");
    cancelBtn.classList.remove("hidden");
    confirmBtn.textContent = "Delete";
    confirmBtn.className = "px-4 py-2 text-white bg-red-600 hover:bg-red-700 rounded-lg transition font-medium";
    cancelBtn.textContent = "Cancel";

    // Show dialog with animation
    dialog.classList.remove("hidden");
    dialog.classList.add("flex");
    dialog.offsetHeight;
    dialog.classList.remove("opacity-0");
    dialogBox.classList.remove("scale-95");
    dialogBox.classList.add("scale-100");

    // Handle Delete click
    const handleConfirm = () => {
      closeDialog();
      resolve(true);
    };

    // Handle Cancel click
    const handleCancel = () => {
      closeDialog();
      resolve(false);
    };

    // Handle Escape key
    const handleEscape = (e) => {
      if (e.key === "Escape") {
        closeDialog();
        resolve(false);
      }
    };

    const closeDialog = () => {
      dialog.classList.add("opacity-0");
      dialogBox.classList.remove("scale-100");
      dialogBox.classList.add("scale-95");
      setTimeout(() => {
        dialog.classList.remove("flex");
        dialog.classList.add("hidden");
        // Clean up content areas
        messageEl.textContent = "";
        summarySection.classList.add("hidden");
        summaryText.textContent = "";
        itemListSection.classList.add("hidden");
        itemsUl.innerHTML = "";
        warningSection.classList.add("hidden");
        warningMessage.textContent = "";
        // Reset confirm button styling
        confirmBtn.className = "px-4 py-2 text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition";
      }, 200);
      confirmBtn.removeEventListener("click", handleConfirm);
      cancelBtn.removeEventListener("click", handleCancel);
      document.removeEventListener("keydown", handleEscape);
    };

    confirmBtn.addEventListener("click", handleConfirm);
    cancelBtn.addEventListener("click", handleCancel);
    document.addEventListener("keydown", handleEscape);

    // Focus the cancel button (safer default for destructive action)
    setTimeout(() => cancelBtn.focus(), 250);
  });
};

/**
 * ============================================================================
 * SELECTION TRACKING SYSTEM
 * ============================================================================
 */

// Global selection state
window.selectedItems = [];

/**
 * Clear all selections
 */
function clearSelection() {
  window.selectedItems = [];
  updateSelectionUI();
  updateActionButtonStates();
}

/**
 * Select a single item (replaces current selection)
 */
function selectItem(item) {
  window.selectedItems = [item];
  updateSelectionUI();
  updateActionButtonStates();
}

/**
 * Toggle item selection (for checkboxes)
 */
function toggleItemSelection(item) {
  const index = window.selectedItems.findIndex((i) => i.path === item.path);

  if (index >= 0) {
    // Item is selected - unselect it
    window.selectedItems.splice(index, 1);
  } else {
    // Item is not selected - add it
    window.selectedItems.push(item);
  }

  updateSelectionUI();
  updateActionButtonStates();
}

/**
 * Update visual selection feedback
 */
function updateSelectionUI() {
  const count = window.selectedItems.length;

  // Update selection count in sidebar footer
  const selectedCount = document.getElementById("selectedCount");
  const statusBar = document.getElementById("statusBar");
  const statusBarText = document.getElementById("statusBarText");

  if (selectedCount) {
    selectedCount.textContent = count;
  }

  // Make status bar more visible when items are selected
  if (statusBar && statusBarText) {
    if (count > 0) {
      // Highlight status bar when items selected
      statusBar.classList.remove("bg-gray-50", "dark:bg-gray-900");
      statusBar.classList.add("bg-blue-100", "dark:bg-blue-900/30");
      statusBarText.classList.remove("text-gray-500", "dark:text-gray-400");
      statusBarText.classList.add(
        "text-blue-700",
        "dark:text-blue-300",
        "font-semibold"
      );
    } else {
      // Reset to default when no selection
      statusBar.classList.remove("bg-blue-100", "dark:bg-blue-900/30");
      statusBar.classList.add("bg-gray-50", "dark:bg-gray-900");
      statusBarText.classList.remove(
        "text-blue-700",
        "dark:text-blue-300",
        "font-semibold"
      );
      statusBarText.classList.add("text-gray-500", "dark:text-gray-400");
    }
  }

  // Update row highlighting and checkboxes in list view
  const rows = document.querySelectorAll("tr[data-path]");
  rows.forEach((row) => {
    const rowPath = row.dataset.path;
    const checkbox = row.querySelector('input[type="checkbox"]');
    const isSelected = window.selectedItems.some(
      (item) => item.path === rowPath
    );

    if (isSelected) {
      // Add selection highlight (blue background)
      row.classList.add("bg-blue-50", "dark:bg-blue-900/20");
      if (checkbox) checkbox.checked = true;
    } else {
      // Remove selection highlight
      row.classList.remove("bg-blue-50", "dark:bg-blue-900/20");
      if (checkbox) checkbox.checked = false;
    }
  });

  // Update select-all checkbox state
  const selectAllCheckbox = document.getElementById("selectAllCheckbox");
  if (selectAllCheckbox) {
    const totalRows = rows.length;
    const selectedRows = count;

    if (selectedRows === 0) {
      // Nothing selected
      selectAllCheckbox.checked = false;
      selectAllCheckbox.indeterminate = false;
    } else if (selectedRows === totalRows && totalRows > 0) {
      // All selected
      selectAllCheckbox.checked = true;
      selectAllCheckbox.indeterminate = false;
    } else {
      // Some selected (indeterminate state)
      selectAllCheckbox.checked = false;
      selectAllCheckbox.indeterminate = true;
    }
  }
}

/**
 * Update action button states based on selection
 */
function updateActionButtonStates() {
  const editBtn = document.getElementById("editBtn");
  const renameBtn = document.getElementById("renameBtn");
  const deleteBtn = document.querySelector(
    'button[onclick="deleteSelected()"]'
  );
  const downloadBtn = document.querySelector(
    'button[onclick="downloadSelected()"]'
  );

  const count = window.selectedItems.length;
  const fileCount = window.selectedItems.filter(
    (item) => item.type === "file"
  ).length;

  // Edit: only enabled when exactly 1 file selected (not folder, not archive)
  if (editBtn) {
    if (count === 1 && window.selectedItems[0].type === "file") {
      const file = window.selectedItems[0];
      const extension = file.extension || "";
      const isArchive = ["zip", "rar", "tar", "gz", "bz2", "7z", "tgz", "xz", "iso"].includes(extension.toLowerCase());

      if (!isArchive) {
        editBtn.disabled = false;
        editBtn.classList.remove("opacity-50", "cursor-not-allowed");
        editBtn.title = "Edit File";
      } else {
        editBtn.disabled = true;
        editBtn.classList.add("opacity-50", "cursor-not-allowed");
        editBtn.title = "Archive files cannot be edited";
      }
    } else if (count === 0) {
      editBtn.disabled = true;
      editBtn.classList.add("opacity-50", "cursor-not-allowed");
      editBtn.title = "Select a file to edit";
    } else if (count === 1 && window.selectedItems[0].type === "directory") {
      editBtn.disabled = true;
      editBtn.classList.add("opacity-50", "cursor-not-allowed");
      editBtn.title = "Folders cannot be edited";
    } else {
      editBtn.disabled = true;
      editBtn.classList.add("opacity-50", "cursor-not-allowed");
      editBtn.title = "Select only one file to edit";
    }
  }

  // Rename: only enabled when exactly 1 item selected
  if (renameBtn) {
    if (count === 1) {
      renameBtn.disabled = false;
      renameBtn.classList.remove("opacity-50", "cursor-not-allowed");
      renameBtn.title = "Rename";
    } else if (count === 0) {
      renameBtn.disabled = true;
      renameBtn.classList.add("opacity-50", "cursor-not-allowed");
      renameBtn.title = "Select an item to rename";
    } else {
      renameBtn.disabled = true;
      renameBtn.classList.add("opacity-50", "cursor-not-allowed");
      renameBtn.title = "Select only one item to rename";
    }
  }

  // Delete: enabled when 1+ items selected
  if (deleteBtn) {
    if (count > 0) {
      deleteBtn.disabled = false;
      deleteBtn.classList.remove("opacity-50", "cursor-not-allowed");
      deleteBtn.title = "Delete";
    } else {
      deleteBtn.disabled = true;
      deleteBtn.classList.add("opacity-50", "cursor-not-allowed");
      deleteBtn.title = "Select item(s) to delete";
    }
  }

  // Download: enabled when 1+ files selected (folders cannot be downloaded)
  if (downloadBtn) {
    if (fileCount > 0) {
      downloadBtn.disabled = false;
      downloadBtn.classList.remove("opacity-50", "cursor-not-allowed");
      downloadBtn.title =
        fileCount === 1 ? "Download file" : `Download ${fileCount} file(s)`;
    } else if (count > 0) {
      // Has selection but no files (only folders)
      downloadBtn.disabled = true;
      downloadBtn.classList.add("opacity-50", "cursor-not-allowed");
      downloadBtn.title = "Select file(s) to download (folders not supported)";
    } else {
      downloadBtn.disabled = true;
      downloadBtn.classList.add("opacity-50", "cursor-not-allowed");
      downloadBtn.title = "Select file(s) to download";
    }
  }

  // Update unzip button state (if SSH enabled)
  updateUnzipButtonState();
}

/**
 * ============================================================================
 * TOOLBAR MANAGEMENT SYSTEM (Event-Driven Architecture)
 * Professional, generic, and extensible toolbar state management
 * ============================================================================
 */

/**
 * ToolbarManager - Centralized toolbar state management
 * Handles visibility, enabled/disabled states, and context-aware configurations
 */
class ToolbarManager {
  constructor() {
    this.currentContext = null;
    this.currentFileData = null;

    // Define toolbar action configurations
    this.actions = {
      // Button ID -> Configuration
      parentFolderBtn: { group: 'navigation', type: 'button' },
      refreshBtn: { group: 'navigation', type: 'button' },
      uploadBtn: { group: 'transfer', type: 'button' },
      downloadBtn: { group: 'transfer', type: 'button' },
      editBtn: { group: 'operations', type: 'button' },
      renameBtn: { group: 'operations', type: 'button' },
      deleteBtn: { group: 'operations', type: 'button' },
      zipBtn: { group: 'ssh', type: 'button' },
      unzipBtn: { group: 'ssh', type: 'button' },
      moveBtn: { group: 'ssh', type: 'button' },
    };

    // Define toolbar groups
    this.groups = {
      navigation: { selector: '.toolbar-navigation' },
      transfer: { selector: '.toolbar-transfer' },
      create: { selector: '.toolbar-create' },
      operations: { selector: '.toolbar-operations' },
      ssh: { selector: '.toolbar-ssh' },
    };

    // Context rules - define what's visible/enabled for each context
    this.contextRules = {
      // FOLDER VIEW - Viewing folder contents (list view)
      folder: {
        groups: {
          navigation: { visible: true },
          transfer: { visible: true },
          create: { visible: true },
          operations: { visible: true },
          ssh: { visible: true },
        },
        actions: {
          uploadBtn: { visible: false }, // Not implemented yet
          downloadBtn: { enabled: false, tooltip: 'Select item(s) to download' },
          editBtn: { enabled: false, tooltip: 'Select a file to edit' },
          renameBtn: { enabled: false, tooltip: 'Select an item to rename' },
          deleteBtn: { enabled: false, tooltip: 'Select item(s) to delete' },
        },
      },

      // FILE VIEW - Viewing single file info
      file: {
        groups: {
          navigation: { visible: true },
          transfer: { visible: true },
          create: { visible: false }, // Hide "New File" and "New Folder"
          operations: { visible: true },
          ssh: { visible: false }, // Hide SSH operations
        },
        actions: {
          uploadBtn: { visible: false },
          downloadBtn: { enabled: true, tooltip: 'Download' },
          editBtn: {
            enabled: (fileData) => !this.isArchiveFile(fileData?.extension),
            tooltip: (fileData) => this.isArchiveFile(fileData?.extension)
              ? 'Archive files cannot be edited'
              : 'Edit File'
          },
          renameBtn: { enabled: true, tooltip: 'Rename' },
          deleteBtn: { enabled: true, tooltip: 'Delete' },
        },
        // Auto-select the file
        autoSelect: true,
      },

      // IMAGE VIEW - Viewing image file (preview mode)
      image: {
        groups: {
          navigation: { visible: true },
          transfer: { visible: true },
          create: { visible: false },
          operations: { visible: true },
          ssh: { visible: false },
        },
        actions: {
          uploadBtn: { visible: false },
          downloadBtn: { enabled: true, tooltip: 'Download Image' },
          editBtn: { enabled: false, tooltip: 'Images cannot be edited in text editor' },
          renameBtn: { enabled: true, tooltip: 'Rename' },
          deleteBtn: { enabled: true, tooltip: 'Delete' },
        },
        autoSelect: true,
      },

      // ARCHIVE VIEW - Viewing archive file (zip, tar, etc.)
      archive: {
        groups: {
          navigation: { visible: true },
          transfer: { visible: true },
          create: { visible: false },
          operations: { visible: true },
          ssh: { visible: true }, // Show SSH for unzip
        },
        actions: {
          uploadBtn: { visible: false },
          downloadBtn: { enabled: true, tooltip: 'Download Archive' },
          editBtn: { enabled: false, tooltip: 'Archive files cannot be edited' },
          renameBtn: { enabled: true, tooltip: 'Rename' },
          deleteBtn: { enabled: true, tooltip: 'Delete' },
          unzipBtn: { enabled: true, tooltip: 'Extract Archive' },
          zipBtn: { visible: false }, // Hide zip button for archives
          moveBtn: { visible: false },
        },
        autoSelect: true,
      },
    };
  }

  /**
   * Set toolbar context and apply rules
   * @param {string} context - 'folder', 'file', 'image', 'archive'
   * @param {object|null} fileData - File data object (required for file contexts)
   */
  setContext(context, fileData = null) {
    this.currentContext = context;
    this.currentFileData = fileData;

    // Get context rules
    const rules = this.contextRules[context];
    if (!rules) {
      console.warn(`[ToolbarManager] Unknown context: ${context}`);
      return;
    }

    // Apply group visibility rules
    this.applyGroupRules(rules.groups);

    // Apply action rules
    this.applyActionRules(rules.actions, fileData);

    // Update separators
    this.updateSeparators();

    // Auto-select file if specified
    if (rules.autoSelect && fileData) {
      this.selectFile(fileData);
    }

    // Dispatch event for other components
    this.dispatchContextChangeEvent(context, fileData);
  }

  /**
   * Apply group visibility rules
   */
  applyGroupRules(groupRules) {
    Object.entries(this.groups).forEach(([groupName, groupConfig]) => {
      const element = document.querySelector(groupConfig.selector);
      if (!element) return;

      const rule = groupRules[groupName];
      if (rule) {
        this.setVisibility(element, rule.visible !== false);
      }
    });
  }

  /**
   * Apply action button rules
   */
  applyActionRules(actionRules, fileData) {
    Object.entries(this.actions).forEach(([actionId, actionConfig]) => {
      const element = document.getElementById(actionId);
      if (!element) return;

      const rule = actionRules[actionId];
      if (!rule) return;

      // Handle visibility
      if (rule.visible !== undefined) {
        this.setVisibility(element, rule.visible);
      }

      // Handle enabled/disabled state
      if (rule.enabled !== undefined) {
        const enabled = typeof rule.enabled === 'function'
          ? rule.enabled(fileData)
          : rule.enabled;
        this.setEnabled(element, enabled);
      }

      // Handle tooltip
      if (rule.tooltip !== undefined) {
        const tooltip = typeof rule.tooltip === 'function'
          ? rule.tooltip(fileData)
          : rule.tooltip;
        element.title = tooltip;
      }
    });
  }

  /**
   * Set element visibility
   */
  setVisibility(element, visible) {
    if (visible) {
      element.classList.remove('hidden');
    } else {
      element.classList.add('hidden');
    }
  }

  /**
   * Set button enabled/disabled state
   */
  setEnabled(button, enabled) {
    if (enabled) {
      button.disabled = false;
      button.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
      button.disabled = true;
      button.classList.add('opacity-50', 'cursor-not-allowed');
    }
  }

  /**
   * Update separator visibility (hide if neighboring groups are hidden)
   */
  updateSeparators() {
    const separators = document.querySelectorAll('.toolbar-separator');

    separators.forEach((separator) => {
      const prev = separator.previousElementSibling;
      const next = separator.nextElementSibling;

      const prevHidden = prev && (prev.classList.contains('hidden') || prev.offsetParent === null);
      const nextHidden = next && (next.classList.contains('hidden') || next.offsetParent === null);

      if (prevHidden || nextHidden) {
        separator.classList.add('hidden');
      } else {
        separator.classList.remove('hidden');
      }
    });
  }

  /**
   * Auto-select file for context
   */
  selectFile(fileData) {
    // Clear previous selection
    if (typeof clearSelection === 'function') {
      clearSelection();
    }

    // Add file to selection
    if (!window.selectedItems) {
      window.selectedItems = [];
    }
    window.selectedItems = [fileData];

    // Update selection count
    if (typeof updateSelectionCount === 'function') {
      updateSelectionCount();
    }
  }

  /**
   * Dispatch custom event for context change
   */
  dispatchContextChangeEvent(context, fileData) {
    const event = new CustomEvent('toolbar:contextchange', {
      detail: { context, fileData }
    });
    window.dispatchEvent(event);
  }

  /**
   * Helper: Check if file is archive
   */
  isArchiveFile(extension) {
    if (!extension) return false;
    const archiveExtensions = ['zip', 'rar', 'tar', 'gz', 'bz2', '7z', 'tgz', 'xz', 'iso'];
    return archiveExtensions.includes(extension.toLowerCase());
  }

  /**
   * Helper: Check if file is image
   */
  isImageFile(extension) {
    if (!extension) return false;
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'ico'];
    return imageExtensions.includes(extension.toLowerCase());
  }

  /**
   * Detect context from file data
   * @param {object} fileData - File data object
   * @returns {string} - Context name: 'file', 'image', 'archive'
   */
  detectFileContext(fileData) {
    if (!fileData) return 'folder';

    const extension = fileData.extension;

    if (this.isArchiveFile(extension)) {
      return 'archive';
    } else if (this.isImageFile(extension)) {
      return 'image';
    } else {
      return 'file';
    }
  }

  /**
   * Add custom context rule (for future extensibility)
   * @param {string} contextName - Name of the context
   * @param {object} rules - Context rules object
   */
  addContextRule(contextName, rules) {
    this.contextRules[contextName] = rules;
  }
}

// Initialize global ToolbarManager instance
window.toolbarManager = new ToolbarManager();

/**
 * ============================================================================
 * EXTENSIBILITY GUIDE - Adding New File Type Contexts
 * ============================================================================
 *
 * To add support for a new file type with custom toolbar behavior:
 *
 * EXAMPLE: Adding support for PDF files
 *
 * 1. Add detection logic (optional - if not using extension-based detection):
 *
 *    window.toolbarManager.isPdfFile = function(extension) {
 *      return extension && extension.toLowerCase() === 'pdf';
 *    };
 *
 * 2. Add custom context rule:
 *
 *    window.toolbarManager.addContextRule('pdf', {
 *      groups: {
 *        navigation: { visible: true },
 *        transfer: { visible: true },      // Show download
 *        create: { visible: false },       // Hide create buttons
 *        operations: { visible: true },    // Show operations
 *        ssh: { visible: false },          // Hide SSH operations
 *      },
 *      actions: {
 *        uploadBtn: { visible: false },
 *        downloadBtn: { enabled: true, tooltip: 'Download PDF' },
 *        editBtn: { enabled: false, tooltip: 'PDFs cannot be edited' },
 *        renameBtn: { enabled: true, tooltip: 'Rename' },
 *        deleteBtn: { enabled: true, tooltip: 'Delete' },
 *      },
 *      autoSelect: true,  // Auto-select the file when viewing
 *    });
 *
 * 3. Update detectFileContext() to recognize the new type:
 *
 *    const originalDetect = window.toolbarManager.detectFileContext.bind(window.toolbarManager);
 *    window.toolbarManager.detectFileContext = function(fileData) {
 *      if (!fileData) return 'folder';
 *
 *      const extension = fileData.extension;
 *
 *      // Check for PDF first
 *      if (this.isPdfFile(extension)) {
 *        return 'pdf';
 *      }
 *
 *      // Fall back to default detection
 *      return originalDetect(fileData);
 *    };
 *
 * 4. Toolbar will automatically update when viewing PDF files!
 *
 * ============================================================================
 * ADVANCED: Dynamic Button States
 * ============================================================================
 *
 * You can use functions for dynamic enabled/disabled states and tooltips:
 *
 * EXAMPLE: Enable edit only for small files
 *
 *    window.toolbarManager.addContextRule('largefile', {
 *      groups: { ... },
 *      actions: {
 *        editBtn: {
 *          enabled: (fileData) => {
 *            const maxSize = 5 * 1024 * 1024; // 5MB
 *            return fileData.size < maxSize;
 *          },
 *          tooltip: (fileData) => {
 *            const maxSize = 5 * 1024 * 1024;
 *            return fileData.size < maxSize
 *              ? 'Edit File'
 *              : 'File too large to edit (>5MB)';
 *          }
 *        },
 *      },
 *    });
 *
 * ============================================================================
 * EVENT SYSTEM - Listen to Context Changes
 * ============================================================================
 *
 * Listen to toolbar context changes in your own code:
 *
 *    window.addEventListener('toolbar:contextchange', (event) => {
 *      const { context, fileData } = event.detail;
 *      console.log(`Toolbar context changed to: ${context}`, fileData);
 *
 *      // Perform custom actions based on context
 *      if (context === 'image') {
 *        // Show image-specific UI elements
 *      }
 *    });
 *
 * ============================================================================
 */

/**
 * ============================================================================
 * URL-BASED NAVIGATION SYSTEM
 * The URL is the SINGLE SOURCE OF TRUTH
 * ============================================================================
 */

/**
 * Navigate to a path by updating the URL
 * This is the ONLY way to navigate - all navigation goes through this function
 */
function navigateTo(path, action = null, skipExpand = false) {
  const url = new URL(window.location);
  url.searchParams.set("path", path);

  if (action) {
    url.searchParams.set("action", action);
  } else {
    url.searchParams.delete("action");
  }

  window.history.pushState({ path, action }, "", url);

  // Dispatch custom event for URL change
  window.dispatchEvent(
    new CustomEvent("urlchange", { detail: { skipExpand } })
  );
}

/**
 * Handle URL changes - this is called when:
 * - Page loads (DOMContentLoaded)
 * - User clicks back/forward (popstate)
 * - navigateTo() is called
 */
function handleUrlChange(event) {
  const urlParams = new URLSearchParams(window.location.search);
  const path = urlParams.get("path") || "/";
  const action = urlParams.get("action");

  // Clear selection when navigating to different folder
  clearSelection();

  // Update parent button visibility
  if (typeof updateParentButtonVisibility === "function") {
    updateParentButtonVisibility();
  }

  // Determine what to show based on path and action
  const isFile = path.includes(".") && !path.endsWith("/");

  // Check if we should skip expanding (folder click in tree)
  const skipExpand = event?.detail?.skipExpand || false;

  // Sync tree
  if (typeof highlightCurrentPath === "function") {
    if (skipExpand) {
      // Just highlight, don't expand (folder was clicked in tree)
      highlightCurrentPath(path, false);
    } else {
      // Expand parents to show the path (URL navigation, file, or initial load)
      highlightCurrentPath(path, true);
    }
  }

  if (isFile) {
    // It's a FILE
    const folderPath = path.substring(0, path.lastIndexOf("/")) || "/";

    // Check if we need to load folder contents
    // If we're opening editor, we don't need to load the folder list
    if (action === "edit") {
      // Just open the editor directly - no need to load folder contents
      // Editor has its own toolbar (editorToolbar), so we don't set context here
      openIntegratedEditor(path);
    } else if (action === "preview") {
      // Show image preview - need file data
      loadFolderContents(folderPath, (data) => {
        if (!data || !data.success) {
          const errorMessage = data?.message || "Folder not found";
          if (typeof window.displayFolderNotFound === "function") {
            window.displayFolderNotFound(folderPath, errorMessage);
          }
          return;
        }

        const fileData = (data.files || []).find((f) => f.path === path);

        if (!fileData) {
          const fileName = path.split("/").pop();
          if (typeof window.displayFileNotFound === "function") {
            window.displayFileNotFound(
              path,
              `File "${fileName}" not found in this folder`
            );
          }
          return;
        }

        // Set toolbar context for image preview
        if (window.toolbarManager) {
          window.toolbarManager.setContext('image', fileData);
        }

        // Always show preview for action=preview
        if (typeof window.displayImagePreview === "function") {
          window.displayImagePreview(fileData);
        }
      });
    } else {
      // No action = check file type and show appropriate view (FILE INFO)
      loadFolderContents(folderPath, (data) => {
        if (!data || !data.success) {
          const errorMessage = data?.message || "Folder not found";
          if (typeof window.displayFolderNotFound === "function") {
            window.displayFolderNotFound(folderPath, errorMessage);
          }
          return;
        }

        const fileData = (data.files || []).find((f) => f.path === path);

        if (!fileData) {
          const fileName = path.split("/").pop();
          if (typeof window.displayFileNotFound === "function") {
            window.displayFileNotFound(
              path,
              `File "${fileName}" not found in this folder`
            );
          }
          return;
        }

        // Detect file context and set toolbar accordingly
        if (window.toolbarManager) {
          const context = window.toolbarManager.detectFileContext(fileData);
          window.toolbarManager.setContext(context, fileData);
        }

        // No action = always show file info (for both images and regular files)
        if (typeof window.displaySelectedFile === "function") {
          window.displaySelectedFile(fileData);
        }
      });
    }
  } else {
    // It's a FOLDER - Set toolbar to folder context
    if (window.toolbarManager) {
      window.toolbarManager.setContext('folder');
    }

    loadFolderContents(path, (data) => {
      if (!data || !data.success) {
        const errorMessage = data?.message || "Folder not found";
        if (typeof window.displayFolderNotFound === "function") {
          window.displayFolderNotFound(path, errorMessage);
        }
      }
    });
  }
}

/**
 * Preview/Edit file - now just updates URL
 * @param {string} path - File path
 * @param {string} [extension] - Optional: file extension from backend API
 */
function previewFile(path, extension = null) {
  closeAllPreviews();

  // Use extension from backend API if provided, otherwise extract from path
  if (!extension) {
    const filename = path.split("/").pop();
    extension = filename.split(".").pop();
  }

  if (isImageFile(extension)) {
    // Navigate with preview action (will show image preview)
    navigateTo(path, "preview");
  } else {
    // Navigate with edit action (will open editor)
    navigateTo(path, "edit");
  }
}

/**
 * Sort list view by column
 */
function sortListView(column) {
  if (!window.currentListData) return;

  const data = window.currentListData;

  // Toggle sort direction if clicking the same column
  if (data.sortColumn === column) {
    data.sortDirection = data.sortDirection === "asc" ? "desc" : "asc";
  } else {
    data.sortColumn = column;
    data.sortDirection = "asc";
  }

  // Combine folders and files for sorting
  let allItems = [
    ...data.folders.map((f) => ({ ...f, type: "folder" })),
    ...data.files.map((f) => ({ ...f, type: "file" })),
  ];

  // Sort based on column
  allItems.sort((a, b) => {
    let aVal, bVal;

    switch (column) {
      case "name":
        aVal = a.name.toLowerCase();
        bVal = b.name.toLowerCase();
        break;
      case "size":
        aVal = a.size || 0;
        bVal = b.size || 0;
        break;
      case "modified":
        aVal = a.modified || "";
        bVal = b.modified || "";
        break;
      case "permissions":
        aVal = convertPermissionsToOctal(a.permissions);
        bVal = convertPermissionsToOctal(b.permissions);
        break;
      default:
        return 0;
    }

    if (aVal < bVal) return data.sortDirection === "asc" ? -1 : 1;
    if (aVal > bVal) return data.sortDirection === "asc" ? 1 : -1;
    return 0;
  });

  // Re-render the list
  const listViewBody = document.getElementById("listViewBody");
  listViewBody.innerHTML = "";

  allItems.forEach((item) => {
    const row = createListRow(item, item.type, data.currentPath);
    listViewBody.appendChild(row);
  });

  // Update sort icons
  updateSortIcons(column, data.sortDirection);
}

/**
 * Update sort icons in table headers
 */
function updateSortIcons(activeColumn, direction) {
  const headers = document.querySelectorAll("#listView th[onclick]");
  headers.forEach((header) => {
    const icon = header.querySelector("i");
    const columnName = header
      .getAttribute("onclick")
      .match(/sortListView\('(\w+)'\)/)[1];

    if (columnName === activeColumn) {
      icon.className =
        direction === "asc"
          ? "fas fa-sort-up text-xs text-primary-500 dark:text-primary-400"
          : "fas fa-sort-down text-xs text-primary-500 dark:text-primary-400";
    } else {
      icon.className = "fas fa-sort text-xs opacity-50";
    }
  });
}

/**
 * Refresh current folder contents (ELITE - instant UX with callbacks)
 */
function refreshCurrentFolder() {
  const urlParams = new URLSearchParams(window.location.search);
  const currentPath = urlParams.get("path") || "/";

  const refreshBtn = document.getElementById("refreshBtn");
  const icon = refreshBtn.querySelector("i");

  // Start spinning
  icon.classList.add("fa-spin");

  // Load folder contents with callback to stop spinning exactly when done
  if (typeof window.loadFolderContents === "function") {
    window.loadFolderContents(currentPath, (data) => {
      // Stop spinning immediately when loading completes (instant UX!)
      icon.classList.remove("fa-spin");
    });
  } else {
    // Fallback if loadFolderContents not available
    handleUrlChange();
    icon.classList.remove("fa-spin");
  }
}

// =================================================================
// VIEW MANAGEMENT - PROFESSIONAL & REUSABLE
// =================================================================

/**
 * Close all previews and editors (professional utility function)
 *
 * This ensures clean navigation - when user navigates to a folder,
 * any open previews/editors are automatically closed.
 *
 * Closes:
 * - Image preview
 * - Code editor
 * - Any future preview types
 *
 * Robust: Always ensures elements are hidden, regardless of current state
 */
function closeAllPreviews() {
  // Close image preview (always ensure it's hidden)
  const imagePreviewView = document.getElementById("imagePreviewView");
  if (imagePreviewView) {
    imagePreviewView.classList.add("hidden");
  }

  // Close code editor (always ensure it's hidden)
  const editorView = document.getElementById("editorView");
  if (editorView) {
    editorView.classList.add("hidden");
  }

  // Restore file manager toolbar (always ensure correct state)
  const editorToolbar = document.getElementById("editorToolbar");
  const fileManagerToolbar = document.getElementById("fileManagerToolbar");
  if (editorToolbar) {
    editorToolbar.classList.add("hidden");
  }
  if (fileManagerToolbar) {
    fileManagerToolbar.classList.remove("hidden");
  }
}

// =================================================================
// FILE SELECTION AND ACTIONS (LEGACY - NOW HANDLED BY NEW SYSTEM)
// =================================================================

/**
 * Update unzip button state (enabled only if exactly 1 zip file is selected)
 * Now uses the extension field from backend API
 */
function updateUnzipButtonState() {
  const unzipBtn = document.getElementById("unzipBtn");
  if (!unzipBtn) return; // SSH not enabled

  const zipExtensions = ["zip", "tar", "gz", "bz2", "7z", "rar", "tgz", "xz"];

  // Enable if exactly 1 item selected and it's a zip format
  if (window.selectedItems && window.selectedItems.length === 1) {
    const selectedItem = window.selectedItems[0];
    // Use extension from backend API if available, otherwise fallback to parsing name
    const extension = (selectedItem.extension || selectedItem.name.split(".").pop()).toLowerCase();
    const isZipFile = zipExtensions.includes(extension);

    if (isZipFile) {
      unzipBtn.disabled = false;
      unzipBtn.classList.remove("opacity-50", "cursor-not-allowed");
      unzipBtn.classList.add("hover:bg-gray-100", "dark:hover:bg-gray-700");
    } else {
      unzipBtn.disabled = true;
      unzipBtn.classList.add("opacity-50", "cursor-not-allowed");
      unzipBtn.classList.remove("hover:bg-gray-100", "dark:hover:bg-gray-700");
    }
  } else {
    // Disable if 0 or more than 1 item selected
    unzipBtn.disabled = true;
    unzipBtn.classList.add("opacity-50", "cursor-not-allowed");
    unzipBtn.classList.remove("hover:bg-gray-100", "dark:hover:bg-gray-700");
  }
}

/**
 * Get appropriate icon class for file type
 */
function getFileIcon(filename) {
  const ext = filename.split(".").pop().toLowerCase();

  // Search through all configured file icon categories
  for (const category in FILE_ICON_CONFIG) {
    const config = FILE_ICON_CONFIG[category];

    // Check if this extension matches this category
    if (config.extensions && config.extensions.includes(ext)) {
      return config.icon;
    }
  }

  // Return default icon if no match found
  return (
    FILE_ICON_CONFIG.default?.icon ||
    "fas fa-file text-gray-400 dark:text-gray-500"
  );
}

/**
 * Format file size to human readable format
 */
function formatFileSize(bytes) {
  if (bytes === 0) return "0 B";
  const k = 1024;
  const sizes = ["B", "KB", "MB", "GB", "TB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
}

// =================================================================
// END GLOBAL FUNCTIONS
// =================================================================

document.addEventListener("DOMContentLoaded", function () {
  // Profile Dropdown Toggle
  const profileButton = document.getElementById("profileButton");
  const profileDropdown = document.getElementById("profileDropdown");

  profileButton.addEventListener("click", function (e) {
    e.stopPropagation();
    profileDropdown.classList.toggle("hidden");
  });

  // Close dropdown when clicking outside
  document.addEventListener("click", function (e) {
    if (
      !profileDropdown.contains(e.target) &&
      !profileButton.contains(e.target)
    ) {
      profileDropdown.classList.add("hidden");
    }
  });

  // Select All Checkbox Handler
  const selectAllCheckbox = document.getElementById("selectAllCheckbox");
  if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener("change", function () {
      if (this.checked) {
        // Select all items in list view
        const listCheckboxes = document.querySelectorAll(
          '#listViewBody input[type="checkbox"]'
        );
        listCheckboxes.forEach((cb) => {
          if (!cb.checked) {
            cb.checked = true;
            cb.dispatchEvent(new Event("change"));
          }
        });
      } else {
        // Deselect all files
        clearSelection();
      }
    });
  }

  // Dynamic Folder Tree Management
  const folderTree = document.getElementById("folderTree");
  const treeLoading = document.getElementById("treeLoading");
  const treeError = document.getElementById("treeError");
  const retryLoadTree = document.getElementById("retryLoadTree");

  // Track expanded folders
  const expandedFolders = new Set();

  /**
   * Load folder tree from API
   */
  function loadFolderTree(path = "/", callback = null) {
    // Show loading
    treeLoading.classList.remove("hidden");
    folderTree.classList.add("hidden");
    treeError.classList.add("hidden");

    return fetch("/api/folder-tree?path=" + encodeURIComponent(path), {
      credentials: "same-origin",
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Hide loading
          treeLoading.classList.add("hidden");
          folderTree.classList.remove("hidden");

          // Render tree
          if (path === "/") {
            // Initial load - render root
            renderTree(data.tree);
          }

          // Call callback if provided (used for highlighting after tree loads)
          if (callback) {
            setTimeout(callback, 100); // Small delay to ensure DOM is ready
          }
        } else {
          throw new Error(data.message || "Failed to load tree");
        }
      })
      .catch((error) => {
        treeLoading.classList.add("hidden");
        treeError.classList.remove("hidden");
      });
  }

  /**
   * Render folder tree
   */
  function renderTree(tree, parentElement = folderTree, level = 0) {
    tree.forEach((item) => {
      const folderElement = createFolderElement(item, level);
      parentElement.appendChild(folderElement);
    });
  }

  /**
   * Create folder or file element
   */
  function createFolderElement(item, level) {
    const div = document.createElement("div");
    div.dataset.path = item.path;
    div.dataset.level = level;
    div.dataset.type = item.type;

    // Create button
    const button = document.createElement("button");
    button.className =
      "flex items-center w-full px-2 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition";
    button.style.paddingLeft = level * 12 + 8 + "px";

    if (item.type === "directory") {
      // Arrow icon for folders
      const arrow = document.createElement("i");
      arrow.className =
        "folder-arrow fas fa-chevron-right text-xs text-gray-400 dark:text-gray-500 mr-2 transition-transform";
      button.appendChild(arrow);

      // Folder icon
      const folderIcon = document.createElement("i");
      folderIcon.className =
        level === 0
          ? "fas fa-folder text-base text-primary-500 mr-2"
          : "fas fa-folder text-base text-yellow-500 mr-2";
      button.appendChild(folderIcon);

      // Folder name
      const span = document.createElement("span");
      span.className = "text-sm font-medium";
      span.textContent = item.name;
      button.appendChild(span);

      // Symlink indicator for folders
      if (item.is_symlink) {
        const symlinkIcon = document.createElement("i");
        symlinkIcon.className = "fas fa-link text-xs text-yellow-500 dark:text-yellow-400 ml-2";
        symlinkIcon.title = item.symlink_target ? `Symlink → ${item.symlink_target}` : "Symbolic Link";
        button.appendChild(symlinkIcon);
      }

      // Container for children
      const childrenContainer = document.createElement("div");
      childrenContainer.dataset.childrenFor = item.path;

      // Check if this folder is already expanded (restore state after re-render)
      const isExpanded = expandedFolders.has(item.path);
      if (isExpanded) {
        childrenContainer.className = ""; // Show children
        arrow.classList.add("rotate-90"); // Rotate arrow
      } else {
        childrenContainer.className = "hidden"; // Hide children
      }

      // Click handler for folders
      button.addEventListener("click", function (e) {
        // Toggle folder expand/collapse in sidebar
        toggleFolder(item.path, arrow, childrenContainer, level);

        // Navigate to folder (skipExpand=true because we already toggled manually)
        navigateTo(item.path, null, true);

        e.stopPropagation();
      });

      div.appendChild(button);
      div.appendChild(childrenContainer);
    } else {
      // File - no arrow, just icon and name

      // Spacer to align with folders (same width as arrow)
      const spacer = document.createElement("span");
      spacer.className = "inline-block w-4 mr-2";
      button.appendChild(spacer);

      // File icon based on extension
      const fileIcon = document.createElement("i");
      fileIcon.className =
        getFileIcon(item.name).replace(/text-\w+/g, "text-base") + " mr-2";
      button.appendChild(fileIcon);

      // File name
      const span = document.createElement("span");
      span.textContent = item.name;
      span.className = "text-sm";
      button.appendChild(span);

      // Symlink indicator for files
      if (item.is_symlink) {
        const symlinkIcon = document.createElement("i");
        symlinkIcon.className = "fas fa-link text-xs text-yellow-500 dark:text-yellow-400 ml-2";
        symlinkIcon.title = item.symlink_target ? `Symlink → ${item.symlink_target}` : "Symbolic Link";
        button.appendChild(symlinkIcon);
      }

      // Click handler for files - single click shows file info with preview/edit buttons
      button.addEventListener("click", function (e) {
        // Navigate to file WITHOUT action (will show file info only)
        // Use skipExpand=true because file is already visible in tree!
        // This prevents unnecessary /api/folder-tree call to expand parent folders
        navigateTo(item.path, null, true);
        e.stopPropagation();
      });

      div.appendChild(button);
    }

    return div;
  }

  /**
   * Get appropriate icon class for file type
   * Uses configuration from config.php file_icons section
   */
  function getFileIcon(filename) {
    const ext = filename.split(".").pop().toLowerCase();

    // Search through all configured file icon categories
    for (const category in FILE_ICON_CONFIG) {
      const config = FILE_ICON_CONFIG[category];

      // Check if this extension matches this category
      if (config.extensions && config.extensions.includes(ext)) {
        return config.icon;
      }
    }

    // Return default icon if no match found
    return (
      FILE_ICON_CONFIG.default?.icon ||
      "fas fa-file text-gray-400 dark:text-gray-500"
    );
  }

  /**
   * Toggle folder expand/collapse
   */
  function toggleFolder(path, arrow, childrenContainer, level) {
    const isExpanded = expandedFolders.has(path);

    if (isExpanded) {
      // Collapse
      arrow.classList.remove("rotate-90");
      childrenContainer.classList.add("hidden");
      expandedFolders.delete(path);
      // Clear children when collapsing to save memory
      childrenContainer.innerHTML = "";
    } else {
      // Expand
      arrow.classList.add("rotate-90");
      childrenContainer.classList.remove("hidden");
      expandedFolders.add(path);

      // Mark as recently expanded to prevent duplicate tree refresh
      recentlyExpandedFolders.add(path);

      // Load children only when expanding
      // Clear old data first, then load fresh data from FTP server
      childrenContainer.innerHTML = "";
      loadFolderChildren(path, childrenContainer, level + 1);
    }
  }

  /**
   * Load children for a folder
   */
  function loadFolderChildren(path, container, level, onComplete = null) {
    // Show loading indicator
    const loading = document.createElement("div");
    loading.className =
      "flex items-center px-3 py-2 text-sm text-gray-500 dark:text-gray-400";
    loading.style.paddingLeft = level * 16 + 12 + "px";
    loading.innerHTML =
      '<i class="fas fa-spinner fa-spin mr-2 text-base"></i>Loading...';
    container.appendChild(loading);

    fetch("/api/folder-tree?path=" + encodeURIComponent(path), {
      credentials: "same-origin",
    })
      .then((response) => response.json())
      .then((data) => {
        // Remove loading indicator
        container.innerHTML = "";

        if (data.success && data.tree.length > 0) {
          renderTree(data.tree, container, level);
        } else if (data.tree.length === 0) {
          // Empty folder
          const empty = document.createElement("div");
          empty.className =
            "px-3 py-2 text-sm text-gray-400 dark:text-gray-500 italic";
          empty.style.paddingLeft = level * 16 + 12 + "px";
          empty.textContent = "Empty";
          container.appendChild(empty);
        }

        // Call callback when children are loaded and rendered
        if (onComplete) {
          onComplete();
        }
      })
      .catch((error) => {
        container.innerHTML = "";
        const errorMsg = document.createElement("div");
        errorMsg.className = "px-3 py-2 text-sm text-red-500";
        errorMsg.style.paddingLeft = level * 16 + 12 + "px";
        errorMsg.textContent = "Error loading";
        container.appendChild(errorMsg);
      });
  }

  // Retry button
  retryLoadTree.addEventListener("click", function () {
    loadFolderTree();
  });

  // Initial load - load tree first, then handle URL
  loadFolderTree("/", function () {
    handleUrlChange();
  });

  // ============================================================================
  // SMART TREE REFRESH - Event-based, debounced, efficient
  // ============================================================================
  // Automatically refreshes tree folders when their contents change
  // - Only refreshes expanded folders (no wasted API calls)
  // - Debounces rapid changes (300ms)
  // - Works for operations that MODIFY folder contents (create, delete, rename, move, etc.)

  /**
   * Notify that folder contents have changed (dispatch event for tree refresh)
   * Should ONLY be called after operations that MODIFY folder contents
   * @param {string} path - Folder path that was modified
   */
  function notifyFolderContentsChanged(path) {
    window.dispatchEvent(new CustomEvent("foldercontentsloaded", {
      detail: { path: path }
    }));
  }

  let treeRefreshTimeout = null;
  const TREE_REFRESH_DEBOUNCE = 300; // 300ms debounce
  const recentlyExpandedFolders = new Set(); // Track folders that just expanded

  window.addEventListener("foldercontentsloaded", function(event) {
    const path = event.detail.path;

    // Debounce: Prevent rapid duplicate refreshes
    clearTimeout(treeRefreshTimeout);

    treeRefreshTimeout = setTimeout(() => {
      // Check if this folder was recently expanded (skip refresh to avoid duplicate)
      if (recentlyExpandedFolders.has(path)) {
        recentlyExpandedFolders.delete(path); // Clear the flag
        return;
      }

      // Find the folder element in tree
      const folderElement = document.querySelector(`[data-path="${path}"]`);

      if (folderElement && folderElement.dataset.type === "directory") {
        const childrenContainer = folderElement.querySelector(`[data-children-for="${path}"]`);

        // Only refresh if folder is expanded and has content
        if (childrenContainer &&
            !childrenContainer.classList.contains("hidden") &&
            childrenContainer.children.length > 0) {

          const level = parseInt(folderElement.dataset.level) || 0;

          // Reload only this folder's children (smart, targeted refresh!)
          childrenContainer.innerHTML = "";

          fetch("/api/folder-tree?path=" + encodeURIComponent(path), {
            credentials: "same-origin",
          })
            .then(res => res.json())
            .then(data => {
              if (data.success && data.tree.length > 0) {
                renderTree(data.tree, childrenContainer, level + 1);
              } else if (data.tree.length === 0) {
                // Empty folder
                const empty = document.createElement("div");
                empty.className = "px-3 py-2 text-sm text-gray-400 dark:text-gray-500 italic";
                empty.style.paddingLeft = (level + 1) * 16 + 12 + "px";
                empty.textContent = "Empty";
                childrenContainer.appendChild(empty);
              }
            });
        }
      }
    }, TREE_REFRESH_DEBOUNCE);
  });

  // Sidebar Resize Functionality
  const sidebar = document.getElementById("sidebar");
  const resizeHandle = document.getElementById("resizeHandle");
  let isResizing = false;
  let startX = 0;
  let startWidth = 0;

  // Apply saved width from localStorage (default is 280px now)
  const savedWidth = localStorage.getItem("sidebarWidth");
  if (savedWidth) {
    sidebar.style.width = savedWidth + "px";
  }

  // Mouse down on resize handle
  resizeHandle.addEventListener("mousedown", function (e) {
    isResizing = true;
    startX = e.clientX;
    startWidth = sidebar.offsetWidth;

    // Prevent text selection during resize
    document.body.style.userSelect = "none";
    document.body.style.cursor = "col-resize";

    e.preventDefault();
  });

  // Mouse move - resize sidebar
  document.addEventListener("mousemove", function (e) {
    if (!isResizing) return;

    const deltaX = e.clientX - startX;
    const newWidth = startWidth + deltaX;

    // Get min and max width from inline styles
    const minWidth = parseInt(getComputedStyle(sidebar).minWidth);
    const maxWidth = parseInt(getComputedStyle(sidebar).maxWidth);

    // Apply new width within constraints
    if (newWidth >= minWidth && newWidth <= maxWidth) {
      sidebar.style.width = newWidth + "px";
    }
  });

  // Mouse up - stop resizing
  document.addEventListener("mouseup", function () {
    if (isResizing) {
      isResizing = false;
      document.body.style.userSelect = "";
      document.body.style.cursor = "";

      // Save width to localStorage
      localStorage.setItem("sidebarWidth", sidebar.offsetWidth);
    }
  });

  /**
   * Load folder contents - INTERNAL USE ONLY
   * Use navigateTo() for navigation
   */
  function loadFolderContents(path, onComplete = null) {
    closeAllPreviews();
    clearSelection();

    const contentEmpty = document.getElementById("contentEmpty");
    const contentLoading = document.getElementById("contentLoading");
    const listView = document.getElementById("listView");

    // Show loading state
    if (contentEmpty) contentEmpty.classList.add("hidden");
    if (contentLoading) contentLoading.classList.remove("hidden");
    listView.classList.add("hidden");

    // Update path input
    const pathInput = document.getElementById("pathInput");
    if (pathInput) {
      pathInput.value = path;
    }

    // Fetch folder contents
    return fetch("/api/folder-contents?path=" + encodeURIComponent(path), {
      credentials: "same-origin",
    })
      .then((response) => response.json())
      .then((data) => {
        // Hide loading
        if (contentLoading) contentLoading.classList.add("hidden");

        if (data.success) {
          // Render contents in list view
          renderListView(data.folders, data.files, path);

          // Show list view
          listView.classList.remove("hidden");

          // DO NOT auto-dispatch "foldercontentsloaded" event here!
          // That event should only be dispatched by operations that MODIFY folder contents
          // (create, delete, rename, move, upload, etc.)
          // Viewing/navigation operations should NOT trigger tree refresh to avoid unnecessary API calls

          // Call the callback when content is loaded
          if (onComplete) {
            onComplete(data);
          }
        } else{
          // Folder load failed - call callback to handle error
          if (onComplete) {
            onComplete(data);
          }
        }
      })
      .catch((error) => {
        if (contentLoading) contentLoading.classList.add("hidden");

        // Call callback with error to handle it
        if (onComplete) {
          onComplete({
            success: false,
            message: "Unable to connect to server: " + error.message,
          });
        }
      });
  }

  // Make loadFolderContents globally accessible for refresh button
  window.loadFolderContents = loadFolderContents;

  /**
   * Convert Unix permissions to octal format
   * Example: "drwxr-xr-x" -> "755"
   * Example: "-rw-r--r--" -> "644"
   * NOTE: Made global because sortListView() needs access to it
   */
  window.convertPermissionsToOctal = function (permissions) {
    if (!permissions || permissions.length < 10) {
      return "-";
    }

    // Remove the first character (file type: d, -, l, etc.)
    const perms = permissions.substring(1);

    // Calculate octal for each triplet (owner, group, others)
    let octal = "";
    for (let i = 0; i < 9; i += 3) {
      let value = 0;
      if (perms[i] === "r") value += 4;
      if (perms[i + 1] === "w") value += 2;
      if (perms[i + 2] === "x" || perms[i + 2] === "s" || perms[i + 2] === "t")
        value += 1;
      octal += value;
    }

    return octal;
  };

  /**
   * Display selected file in main content area
   */
  window.displaySelectedFile = function (file) {
    // Close any open previews (image preview or code editor)
    closeAllPreviews();

    // Get container elements
    const contentEmpty = document.getElementById("contentEmpty");
    const contentLoading = document.getElementById("contentLoading");
    const listView = document.getElementById("listView");

    // Hide all other views
    if (contentLoading) contentLoading.classList.add("hidden");
    listView.classList.add("hidden");

    // Update path input with file path
    const pathInput = document.getElementById("pathInput");
    if (pathInput) {
      pathInput.value = file.path;
    }

    // Get file icon without size classes and make it huge
    const iconClasses = getFileIcon(file.name)
      .replace(/text-\w+/g, "")
      .trim();

    // Create beautiful file display
    const fileDisplay = `
            <div class="flex items-center justify-center h-full">
                <div class="text-center px-8 max-w-2xl">
                    <!-- File Icon with glow effect -->
                    <div class="relative inline-block mb-6">
                        <div class="absolute inset-0 bg-gradient-to-br from-primary-400 to-primary-600 rounded-2xl blur-xl opacity-20 animate-pulse"></div>
                        <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-12 shadow-xl border-2 border-gray-200 dark:border-gray-700">
                            <i class="${iconClasses} text-7xl"></i>
                        </div>
                    </div>

                    <!-- File Name -->
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3 break-all">${escapeHtml(
                      file.name
                    )}</h2>

                    ${
                      file.is_symlink && file.symlink_target
                        ? `
                    <!-- Symlink Warning -->
                    <div class="mb-4 px-4 py-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                        <div class="flex items-center gap-2 text-yellow-800 dark:text-yellow-300">
                            <i class="fas fa-link text-sm"></i>
                            <span class="text-sm font-semibold">Symbolic Link</span>
                        </div>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-400 break-all">
                            <i class="fas fa-arrow-right text-xs mr-2"></i>
                            Points to: <code class="bg-yellow-100 dark:bg-yellow-900/40 px-2 py-1 rounded">${escapeHtml(
                              file.symlink_target
                            )}</code>
                        </div>
                    </div>
                    `
                        : ""
                    }

                    <!-- File Details -->
                    <div class="flex items-center justify-center gap-6 text-gray-600 dark:text-gray-400 mb-8">
                        ${
                          file.size
                            ? `
                            <div class="flex items-center gap-2">
                                <i class="fas fa-file-lines text-base"></i>
                                <span class="text-base font-medium">${formatFileSize(
                                  file.size
                                )}</span>
                            </div>
                        `
                            : ""
                        }
                        <div class="flex items-center gap-2">
                            <i class="fas fa-shield-halved text-base"></i>
                            <span class="text-base font-mono">${convertPermissionsToOctal(
                              file.permissions
                            )}</span>
                        </div>
                        ${
                          file.modified
                            ? `
                            <div class="flex items-center gap-2">
                                <i class="fas fa-clock text-base"></i>
                                <span class="text-base">${file.modified}</span>
                            </div>
                        `
                            : ""
                        }
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-center gap-3">
                        ${
                          isArchiveFile(file.extension)
                            ? `
                            <!-- Download Button for Archives -->
                            <a href="/api/download?path=${encodeURIComponent(
                              file.real_path || file.path
                            )}" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 rounded-lg border border-blue-200 dark:border-blue-800 transition-all duration-200">
                                <i class="fas fa-download text-sm"></i>
                                <span>Download</span>
                            </a>
                            ${
                              window.APP_CONFIG && window.APP_CONFIG.sshEnabled
                                ? `
                            <!-- Unzip Button (only if SSH enabled) -->
                            <button onclick="unzipFile('${escapeHtml(
                              file.real_path || file.path
                            )}')" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg border border-green-200 dark:border-green-800 transition-all duration-200">
                                <i class="fas fa-file-zipper text-sm"></i>
                                <span>Unzip</span>
                            </button>
                            `
                                : ""
                            }
                        `
                            : `
                            <!-- Edit/Preview Button for non-archives -->
                            <button onclick="previewFile('${escapeHtml(
                              file.real_path || file.path
                            )}', '${escapeHtml(
                              file.extension || ""
                            )}')" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 bg-primary-50 dark:bg-primary-900/20 hover:bg-primary-100 dark:hover:bg-primary-900/30 rounded-lg border border-primary-200 dark:border-primary-800 transition-all duration-200">
                                <i class="fas ${
                                  isImageFile(file.extension)
                                    ? "fa-eye"
                                    : "fa-pen-to-square"
                                } text-sm"></i>
                                <span>${
                                  isImageFile(file.extension)
                                    ? "Preview Image"
                                    : "Edit File"
                                }</span>
                            </button>
                        `
                        }
                    </div>

                    <!-- File Path -->
                    <div class="mt-8 p-4 bg-gray-100 dark:bg-gray-900 rounded-lg">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-mono break-all">
                            <i class="fas fa-folder-tree mr-2"></i>${escapeHtml(
                              file.path
                            )}
                        </p>
                    </div>
                </div>
            </div>
        `;

    // Show the file display
    if (contentEmpty) {
      contentEmpty.innerHTML = fileDisplay;
      contentEmpty.classList.remove("hidden");
    }
  };

  /**
   * Display file not found error (red styled, same position as file preview)
   */
  window.displayFileNotFound = function (
    path,
    errorMessage = "File not found"
  ) {
    // Clear previous content and show empty state
    const contentEmpty = document.getElementById("contentEmpty");
    const contentLoading = document.getElementById("contentLoading");
    const listView = document.getElementById("listView");

    if (contentLoading) contentLoading.classList.add("hidden");
    listView.classList.add("hidden");

    // Get filename and extension
    const fileName = path.substring(path.lastIndexOf("/") + 1);
    const extension = fileName.split(".").pop().toLowerCase();

    // Get file icon (but make it red)
    const iconClasses = getFileIcon(fileName)
      .replace(/text-\w+-\d+/g, "text-red-500")
      .replace(/dark:text-\w+-\d+/g, "dark:text-red-400")
      .trim();

    // Create error display (same layout as normal file, but red)
    const errorDisplay = `
      <div class="flex items-center justify-center h-full">
        <div class="text-center px-8 max-w-2xl">
          <!-- Red File Icon -->
          <div class="relative inline-block mb-6">
            <div class="absolute inset-0 bg-red-500 rounded-2xl blur-xl opacity-20 animate-pulse"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-12 shadow-xl border-2 border-red-300 dark:border-red-700">
              <i class="${iconClasses} text-7xl"></i>
            </div>
          </div>

          <!-- File Name in Red -->
          <h2 class="text-2xl font-bold text-red-600 dark:text-red-400 mb-3 break-all">${escapeHtml(
            fileName
          )}</h2>

          <!-- Error Message -->
          <div class="flex items-center justify-center gap-2 text-red-600 dark:text-red-400 mb-6">
            <i class="fas fa-exclamation-triangle text-lg"></i>
            <span class="text-lg font-semibold">${escapeHtml(
              errorMessage
            )}</span>
          </div>

          <!-- File Path -->
          <div class="mt-8 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
            <p class="text-xs text-red-600 dark:text-red-400 font-mono break-all">
              <i class="fas fa-folder-tree mr-2"></i>${escapeHtml(path)}
            </p>
          </div>
        </div>
      </div>
    `;

    // Show the error display
    if (contentEmpty) {
      contentEmpty.innerHTML = errorDisplay;
      contentEmpty.classList.remove("hidden");
    }
  };

  /**
   * Display folder not found error
   */
  window.displayFolderNotFound = function (
    path,
    errorMessage = "Folder not found"
  ) {
    // Clear previous content and show empty state
    const contentEmpty = document.getElementById("contentEmpty");
    const contentLoading = document.getElementById("contentLoading");
    const listView = document.getElementById("listView");

    if (contentLoading) contentLoading.classList.add("hidden");
    listView.classList.add("hidden");

    // Get folder name
    const folderName = path.substring(path.lastIndexOf("/") + 1) || "/";

    // Create error display
    const errorDisplay = `
      <div class="flex items-center justify-center h-full">
        <div class="text-center px-8 max-w-2xl">
          <!-- Red Folder Icon -->
          <div class="relative inline-block mb-6">
            <div class="absolute inset-0 bg-red-500 rounded-2xl blur-xl opacity-20 animate-pulse"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-12 shadow-xl border-2 border-red-300 dark:border-red-700">
              <i class="fas fa-folder text-red-500 dark:text-red-400 text-7xl"></i>
            </div>
          </div>

          <!-- Folder Name in Red -->
          <h2 class="text-2xl font-bold text-red-600 dark:text-red-400 mb-3 break-all">${escapeHtml(
            folderName
          )}</h2>

          <!-- Error Message -->
          <div class="flex items-center justify-center gap-2 text-red-600 dark:text-red-400 mb-6">
            <i class="fas fa-exclamation-triangle text-xl"></i>
            <span class="text-xl font-semibold">${escapeHtml(
              errorMessage
            )}</span>
          </div>

          <!-- Path -->
          <div class="mt-8 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
            <p class="text-sm text-red-600 dark:text-red-400 font-mono break-all">
              <i class="fas fa-folder-tree mr-2"></i>${escapeHtml(path)}
            </p>
          </div>
        </div>
      </div>
    `;

    // Show the error display
    if (contentEmpty) {
      contentEmpty.innerHTML = errorDisplay;
      contentEmpty.classList.remove("hidden");
    }
  };

  /**
   * Render folder/file contents in list view (table format)
   */
  function renderListView(folders, files, currentPath) {
    const listViewBody = document.getElementById("listViewBody");

    // Clear existing content
    listViewBody.innerHTML = "";

    // Render folders first
    folders.forEach((folder) => {
      const row = createListRow(folder, "folder", currentPath);
      listViewBody.appendChild(row);
    });

    // Render files
    files.forEach((file) => {
      const row = createListRow(file, "file", currentPath);
      listViewBody.appendChild(row);
    });

    // If empty
    if (folders.length === 0 && files.length === 0) {
      const emptyRow = document.createElement("tr");
      emptyRow.innerHTML = `
                <td colspan="5" class="px-4 py-12 text-center">
                    <i class="fas fa-folder-open text-gray-300 dark:text-gray-600 text-5xl mb-3 block"></i>
                    <p class="text-gray-500 dark:text-gray-400">This folder is empty</p>
                </td>
            `;
      listViewBody.appendChild(emptyRow);
    }

    // Store current data for sorting
    window.currentListData = {
      folders: folders,
      files: files,
      currentPath: currentPath,
      sortColumn: "name",
      sortDirection: "asc",
    };
  }

  /**
   * Create a list row for folder or file
   * NOTE: This function is also used by sortListView() which is in global scope
   */
  window.createListRow = function (item, type, currentPath) {
    const row = document.createElement("tr");
    row.className =
      "hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition";
    row.dataset.path = item.path;
    row.dataset.type = type;

    // Checkbox cell
    const checkboxCell = document.createElement("td");
    checkboxCell.className = "px-4 py-3";
    const checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.className = "rounded border-gray-300 dark:border-gray-600";

    // Check if this item is already in selection (using NEW selection system)
    const isSelected =
      window.selectedItems &&
      window.selectedItems.some((i) => i.path === item.path);
    if (isSelected) {
      checkbox.checked = true;
      row.classList.add("bg-blue-50", "dark:bg-blue-900/20");
    }

    checkboxCell.appendChild(checkbox);
    row.appendChild(checkboxCell);

    const extension = item.name.split(".").pop().toLowerCase();

    // Checkbox change handler (adds/removes from selection)
    checkbox.addEventListener("change", function (e) {
      e.stopPropagation();

      const itemData = {
        path: item.path,
        name: item.name,
        type: type,
        extension: extension,
        size: item.size,
        permissions: item.permissions,
      };

      toggleItemSelection(itemData);
    });

    // Row click handler (single-click selection)
    row.addEventListener("click", function (e) {
      // If clicking on the checkbox cell, let the checkbox handler deal with it
      if (e.target === checkbox || e.target === checkboxCell) {
        return;
      }

      const itemData = {
        path: item.path,
        name: item.name,
        type: type,
        extension: extension,
        size: item.size,
        permissions: item.permissions,
      };

      // Single click = select this item (replaces current selection)
      selectItem(itemData);
    });

    // Row double-click handler (professional behavior)
    row.addEventListener("dblclick", function (e) {
      // If clicking on the checkbox cell, ignore
      if (e.target === checkbox || e.target === checkboxCell) {
        return;
      }

      if (type === "folder") {
        navigateTo(item.path); // For folders, use symlink path for navigation
      } else {
        previewFile(item.real_path || item.path); // For files, use real path for operations
      }
    });

    // Name cell
    const nameCell = document.createElement("td");
    nameCell.className = "px-4 py-3";
    const nameDiv = document.createElement("div");
    nameDiv.className = "flex items-center";

    const icon = document.createElement("i");
    if (type === "folder") {
      icon.className =
        "fas fa-folder text-base text-yellow-500 dark:text-yellow-400 mr-3";
    } else {
      icon.className = getFileIcon(item.name) + " mr-3";
    }
    nameDiv.appendChild(icon);

    const nameSpan = document.createElement("span");
    nameSpan.className =
      "text-gray-900 dark:text-white text-sm" +
      (type === "folder" ? " font-medium" : "");
    nameSpan.textContent = item.name;
    nameDiv.appendChild(nameSpan);

    // Add symlink indicator
    if (item.is_symlink) {
      const symlinkIcon = document.createElement("i");
      symlinkIcon.className = "fas fa-link text-xs text-yellow-500 dark:text-yellow-400 ml-2";
      symlinkIcon.title = item.symlink_target ? `Symlink → ${item.symlink_target}` : "Symbolic Link";
      nameDiv.appendChild(symlinkIcon);
    }

    nameCell.appendChild(nameDiv);
    row.appendChild(nameCell);

    // Size cell
    const sizeCell = document.createElement("td");
    sizeCell.className =
      "px-4 py-3 text-gray-600 dark:text-gray-400 text-sm text-right";
    sizeCell.textContent =
      type === "folder" ? "-" : item.size ? formatFileSize(item.size) : "-";
    sizeCell.dataset.size = item.size || 0;
    row.appendChild(sizeCell);

    // Modified date cell
    const modifiedCell = document.createElement("td");
    modifiedCell.className =
      "px-4 py-3 text-gray-600 dark:text-gray-400 text-sm";
    modifiedCell.textContent = item.modified || "-";
    modifiedCell.dataset.modified = item.modified || "";
    row.appendChild(modifiedCell);

    // Permissions cell (octal format)
    const permCell = document.createElement("td");
    permCell.className =
      "px-4 py-3 text-gray-600 dark:text-gray-400 text-sm font-mono text-center";
    const octalPerm = convertPermissionsToOctal(item.permissions);
    permCell.textContent = octalPerm;
    permCell.dataset.permissions = octalPerm;
    row.appendChild(permCell);

    return row;
  };

  /**
   * Escape HTML to prevent XSS
   */
  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  /**
   * Highlight current path in sidebar tree
   */
  window.highlightCurrentPath = function (path, expandParents = true) {
    // If we need to expand parents, do it FIRST, then highlight
    if (expandParents) {
      expandParentFolders(path, () => {
        // After expanding, highlight with expandParents=false to avoid infinite loop
        highlightCurrentPath(path, false);
      });
      return; // Exit early, let the callback handle highlighting
    }

    // Remove previous highlights
    const previousHighlights = document.querySelectorAll(
      ".sidebar-tree-item-active"
    );
    previousHighlights.forEach((el) => {
      el.classList.remove(
        "sidebar-tree-item-active",
        "bg-blue-100",
        "dark:bg-blue-900",
        "text-blue-600",
        "dark:text-blue-400"
      );
    });

    // Find and highlight the current path
    const sidebarItems = document.querySelectorAll("[data-path]");

    sidebarItems.forEach((item) => {
      const itemPath = item.getAttribute("data-path");

      if (itemPath === path) {
        // Add highlight to the button inside the item, not the container
        const button = item.querySelector("button");
        if (button) {
          button.classList.add(
            "sidebar-tree-item-active",
            "bg-blue-100",
            "dark:bg-blue-900",
            "text-blue-600",
            "dark:text-blue-400"
          );
        } else {
          // For files - highlight the whole div
          item.classList.add(
            "sidebar-tree-item-active",
            "bg-blue-100",
            "dark:bg-blue-900",
            "text-blue-600",
            "dark:text-blue-400"
          );
        }

        // Scroll into view
        item.scrollIntoView({ behavior: "smooth", block: "nearest" });
      }
    });
  };

  /**
   * Expand parent folders to make a path visible (one by one, sequentially)
   */
  async function expandParentFolders(path, onComplete = null) {
    const parts = path.split("/").filter((p) => p);

    // Check if the target is a file (has extension)
    const isTargetFile = path.includes(".") && !path.endsWith("/");

    // If target is a file, only process parent folders (exclude the last part which is the filename)
    const partsToProcess = isTargetFile ? parts.slice(0, -1) : parts;

    // Expand folders one by one, SEQUENTIALLY
    let currentPath = "";
    for (let i = 0; i < partsToProcess.length; i++) {
      currentPath += "/" + partsToProcess[i];

      const folderElement = document.querySelector(
        `[data-path="${currentPath}"]`
      );

      if (folderElement && folderElement.dataset.type === "directory") {
        const button = folderElement.querySelector("button");
        if (!button) {
          continue;
        }

        const arrow = button.querySelector(".folder-arrow");
        const childrenContainer = folderElement.querySelector(
          "[data-children-for]"
        );

        if (arrow && childrenContainer) {
          const isHidden = childrenContainer.classList.contains("hidden");

          if (isHidden) {
            arrow.classList.add("rotate-90");
            childrenContainer.classList.remove("hidden");
            expandedFolders.add(currentPath);

            // Mark as recently expanded to prevent duplicate tree refresh
            recentlyExpandedFolders.add(currentPath);

            // Load children if empty - WAIT for this to complete before continuing
            if (childrenContainer.children.length === 0) {
              await new Promise((resolve) => {
                loadFolderChildren(
                  currentPath,
                  childrenContainer,
                  i + 1,
                  resolve
                );
              });
            }
          }
        }
      }
    }

    if (onComplete) {
      onComplete();
    }
  }

  // Note: handleUrlChange is called after tree loads
  // Note: popstate listener is at the end of file (global scope)

  /**
   * Path input navigation - when user types a path and presses Enter
   * Simply update the URL - the URL is the single source of truth!
   */
  const pathInput = document.getElementById("pathInput");
  if (pathInput) {
    pathInput.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        const inputPath = pathInput.value.trim();

        if (!inputPath) {
          showDialog("Please enter a path");
          return;
        }

        // Normalize path (ensure it starts with /)
        const normalizedPath = inputPath.startsWith("/")
          ? inputPath
          : "/" + inputPath;

        // Navigate to path - URL is the single source of truth!
        navigateTo(normalizedPath);
      }
    });
  }

  /**
   * Keyboard shortcuts
   */
  document.addEventListener("keydown", function (e) {
    // F5 or Ctrl+R - Refresh current folder (global shortcut)
    if (e.key === "F5" || ((e.ctrlKey || e.metaKey) && e.key === "r")) {
      e.preventDefault(); // Prevent browser refresh
      refreshCurrentFolder();
      return;
    }

    // Editor shortcuts are handled by integrated-editor.js
  });
});

// =================================================================
// IMAGE PREVIEW FUNCTIONS
// =================================================================

let currentImagePath = null;
let currentImageZoom = 1.0;

/**
 * Check if file is an image based on extension
 * Now uses the extension from backend API response
 * @param {string} extension - File extension from backend (e.g., 'png', 'jpg')
 * @returns {boolean}
 */
function isImageFile(extension) {
  const imageExtensions = [
    "png",
    "jpg",
    "jpeg",
    "gif",
    "svg",
    "webp",
    "bmp",
    "ico",
    "tiff",
    "tif",
  ];
  if (!extension) return false;
  return imageExtensions.includes(extension.toLowerCase());
}

/**
 * Check if file is an archive/compressed file
 * @param {string} extension - File extension from backend (e.g., 'zip', 'gz')
 * @returns {boolean}
 */
function isArchiveFile(extension) {
  const archiveExtensions = [
    "zip",
    "rar",
    "tar",
    "gz",
    "bz2",
    "7z",
    "tgz",
    "xz",
    "iso",
  ];
  if (!extension) return false;
  return archiveExtensions.includes(extension.toLowerCase());
}

/**
 * Display image preview
 */
window.displayImagePreview = function (fileData) {
  currentImagePath = fileData.path;
  currentImageZoom = 1.0;

  // Hide other views
  document.getElementById("contentEmpty").classList.add("hidden");
  document.getElementById("listView").classList.add("hidden");

  document.getElementById("editorView").classList.add("hidden");

  // Switch toolbars - hide normal toolbar (like editor does)
  document.getElementById("fileManagerToolbar").classList.add("hidden");

  // Show image preview
  const imagePreviewView = document.getElementById("imagePreviewView");
  imagePreviewView.classList.remove("hidden");

  // Update image info immediately
  document.getElementById("imagePreviewFileName").textContent = fileData.name;
  document.getElementById("imagePreviewInfo").textContent = `${formatFileSize(
    fileData.size
  )} • Loading...`;

  // Get image container and show loading state
  const img = document.getElementById("imagePreviewImg");
  const imageContainer = img.parentElement;

  // Create loading overlay
  const loadingOverlay = document.createElement("div");
  loadingOverlay.id = "imageLoadingOverlay";
  loadingOverlay.className =
    "absolute inset-0 flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-900 z-10";
  loadingOverlay.innerHTML = `
    <div class="flex flex-col items-center space-y-4">
      <!-- Elegant spinner -->
      <div class="relative">
        <div class="w-12 h-12 border-4 border-gray-200 dark:border-gray-700 rounded-full"></div>
        <div class="absolute top-0 left-0 w-12 h-12 border-4 border-primary-500 dark:border-primary-400 rounded-full border-t-transparent animate-spin"></div>
      </div>
      <!-- Loading text -->
      <p class="text-sm text-gray-600 dark:text-gray-400 animate-pulse">Loading image...</p>
    </div>
  `;

  // Remove any existing loading overlay
  const existingOverlay = document.getElementById("imageLoadingOverlay");
  if (existingOverlay) {
    existingOverlay.remove();
  }

  // Add loading overlay
  imageContainer.style.position = "relative";
  imageContainer.appendChild(loadingOverlay);

  // Clear previous image immediately to prevent showing old content
  img.src = "";
  img.style.transform = "scale(1)";

  // Load new image
  img.onload = function () {
    // Remove loading overlay
    const overlay = document.getElementById("imageLoadingOverlay");
    if (overlay) {
      overlay.remove();
    }

    // Update info with dimensions
    const info = document.getElementById("imagePreviewInfo");
    info.textContent = `${formatFileSize(fileData.size)} • ${
      img.naturalWidth
    } × ${img.naturalHeight} px`;
  };

  img.onerror = function () {
    // Remove loading overlay
    const overlay = document.getElementById("imageLoadingOverlay");
    if (overlay) {
      overlay.remove();
    }

    // Show error message
    imageContainer.innerHTML = `
      <div class="flex items-center justify-center h-full">
        <div class="text-center space-y-3">
          <i class="fas fa-exclamation-triangle text-5xl text-red-500"></i>
          <p class="text-gray-600 dark:text-gray-400">Failed to load image</p>
        </div>
      </div>
    `;
  };

  // Set image source (triggers loading)
  img.src = `/api/file/image?path=${encodeURIComponent(fileData.path)}`;
  img.alt = fileData.name;
};

/**
 * Zoom image in
 */
function zoomImageIn() {
  currentImageZoom = Math.min(currentImageZoom * 1.25, 5);
  const img = document.getElementById("imagePreviewImg");
  img.style.transform = `scale(${currentImageZoom})`;
}

/**
 * Zoom image out
 */
function zoomImageOut() {
  currentImageZoom = Math.max(currentImageZoom / 1.25, 0.25);
  const img = document.getElementById("imagePreviewImg");
  img.style.transform = `scale(${currentImageZoom})`;
}

/**
 * Reset image zoom
 */
function resetImageZoom() {
  currentImageZoom = 1.0;
  const img = document.getElementById("imagePreviewImg");
  img.style.transform = "scale(1)";
}

/**
 * Download current image
 */
function downloadImage() {
  if (!currentImagePath) return;

  const link = document.createElement("a");
  link.href = `/api/file/image?path=${encodeURIComponent(currentImagePath)}`;
  link.download = currentImagePath.split("/").pop();
  link.click();
}

/**
 * Close image preview
 */
function closeImagePreview() {
  // Hide image preview
  document.getElementById("imagePreviewView").classList.add("hidden");

  // Switch toolbars back - show normal toolbar
  document.getElementById("fileManagerToolbar").classList.remove("hidden");

  // Navigate to parent folder
  const currentPath = currentImagePath
    ? currentImagePath.substring(0, currentImagePath.lastIndexOf("/")) || "/"
    : "/";
  currentImagePath = null;

  navigateTo(currentPath);
}

// =================================================================
// SSH OPERATIONS (ZIP, UNZIP, MOVE)
// =================================================================

/**
 * Zip selected files/folders
 * Requires SSH connection to be enabled in config
 */
function zipSelectedFiles() {
  if (!window.selectedItems || window.selectedItems.length === 0) {
    showDialog("Please select at least one file or folder to zip.");
    return;
  }

  // TODO: Implement SSH-based zip operation
  // This will require:
  // 1. SSH connection to server
  // 2. Create zip archive using command-line tools
  // 3. Return success/failure status

  showDialog(
    `Zip functionality coming soon!\n\nSelected ${
      window.selectedItems.length
    } item(s):\n${window.selectedItems.map((f) => f.name).join("\n")}`
  );
}

/**
 * Unzip selected archive file
 * Requires SSH connection and exactly 1 zip file selected
 */
function unzipSelectedFile() {
  if (!window.selectedItems || window.selectedItems.length !== 1) {
    showDialog("Please select exactly one archive file to unzip.");
    return;
  }

  const selectedFile = window.selectedItems[0];
  const zipExtensions = ["zip", "tar", "gz", "bz2", "7z", "rar", "tgz", "xz"];
  const extension = selectedFile.name.split(".").pop().toLowerCase();

  if (!zipExtensions.includes(extension)) {
    showDialog("Selected file is not a supported archive format.");
    return;
  }

  // TODO: Implement SSH-based unzip operation
  // This will require:
  // 1. SSH connection to server
  // 2. Extract archive using appropriate command (unzip, tar, etc.)
  // 3. Handle different archive formats
  // 4. Return success/failure status

  showDialog(
    `Unzip functionality coming soon!\n\nArchive: ${
      selectedFile.name
    }\nFormat: ${extension.toUpperCase()}`
  );
}

/**
 * Move selected files/folders to another directory
 * Requires SSH connection to be enabled in config
 */
async function moveSelectedFiles() {
  if (!window.selectedItems || window.selectedItems.length === 0) {
    showDialog("Please select at least one file or folder to move.");
    return;
  }

  // TODO: Implement SSH-based move operation
  // This will require:
  // 1. Prompt user for destination directory
  // 2. SSH connection to server
  // 3. Move files using mv command
  // 4. Refresh file listing
  // 5. Return success/failure status

  // Placeholder: Show move dialog
  const destination = await showPrompt(
    `Move ${window.selectedItems.length} item(s) to:\n\n${window.selectedItems
      .map((f) => f.name)
      .join("\n")}\n\nEnter destination path:`,
    "",
    "Move Items"
  );

  if (destination) {
    showDialog(
      `Move functionality coming soon!\n\nWould move to: ${destination}`
    );
  }
}

/**
 * ============================================================================
 * URL CHANGE DETECTION - CENTRAL MECHANISM
 * Listen to URL change events and trigger handleUrlChange()
 * ============================================================================
 */

// Listen to custom urlchange event (dispatched by navigateTo)
window.addEventListener("urlchange", handleUrlChange);

// Listen to popstate (back/forward buttons)
window.addEventListener("popstate", handleUrlChange);

/**
 * ============================================================================
 * CREATE NEW FILE/FOLDER
 * ============================================================================
 */

/**
 * Create new file in current folder
 */
async function createNewFile() {
  const urlParams = new URLSearchParams(window.location.search);
  const currentPath = urlParams.get("path") || "/";

  // Prompt for filename
  const filename = await showPrompt(
    "Enter file name (with extension):\n\nExample: index.php, style.css, readme.txt",
    "",
    "Create New File"
  );

  if (!filename || filename.trim() === "") {
    return; // User cancelled or empty input
  }

  // Validate filename
  const trimmedFilename = filename.trim();
  if (trimmedFilename.includes("/") || trimmedFilename.includes("\\")) {
    showDialog("Invalid file name: Cannot contain slashes (/ or \\)");
    return;
  }

  try {
    // Get CSRF token
    const tokenResponse = await fetch("/api/csrf-token", {
      credentials: "same-origin",
    });
    const tokenData = await tokenResponse.json();

    if (!tokenData.success) {
      throw new Error(tokenData.message || "Failed to get security token");
    }

    const csrfToken = tokenData.csrf_token;

    // Create form data
    const params = new URLSearchParams();
    params.append("path", currentPath);
    params.append("filename", trimmedFilename);
    params.append("_csrf_token", csrfToken);

    // Send create request
    const response = await fetch("/api/file/create", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: params.toString(),
    });

    const data = await response.json();

    if (data.success) {
      // Refresh current folder view
      handleUrlChange();

      // Notify tree that folder contents changed (so tree refreshes to show new file)
      notifyFolderContentsChanged(currentPath);

      // Optionally open the file for editing
      const shouldEdit = await showConfirm(
        "File created successfully. Do you want to edit it now?",
        "File Created"
      );
      if (shouldEdit) {
        navigateTo(data.path, "edit");
      }
    } else {
      showDialog("Failed to create file: " + (data.message || "Unknown error"));
    }
  } catch (error) {
    showDialog("Failed to create file: " + error.message);
  }
}

/**
 * Create new folder in current folder
 */
async function createNewFolder() {
  const urlParams = new URLSearchParams(window.location.search);
  const currentPath = urlParams.get("path") || "/";

  // Prompt for folder name
  const foldername = await showPrompt(
    "Enter folder name:\n\nExample: images, css, backups",
    "",
    "Create New Folder"
  );

  if (!foldername || foldername.trim() === "") {
    return; // User cancelled or empty input
  }

  // Validate foldername
  const trimmedFoldername = foldername.trim();
  if (trimmedFoldername.includes("/") || trimmedFoldername.includes("\\")) {
    showDialog("Invalid folder name: Cannot contain slashes (/ or \\)");
    return;
  }

  try {
    // Get CSRF token
    const tokenResponse = await fetch("/api/csrf-token", {
      credentials: "same-origin",
    });
    const tokenData = await tokenResponse.json();

    if (!tokenData.success) {
      throw new Error(tokenData.message || "Failed to get security token");
    }

    const csrfToken = tokenData.csrf_token;

    // Create form data
    const params = new URLSearchParams();
    params.append("path", currentPath);
    params.append("foldername", trimmedFoldername);
    params.append("_csrf_token", csrfToken);

    // Send create request
    const response = await fetch("/api/folder/create", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: params.toString(),
    });

    const data = await response.json();

    if (data.success) {
      // Refresh current folder view
      handleUrlChange();
      // Notify tree to refresh (manual dispatch for modification operations only)
      notifyFolderContentsChanged(currentPath);

      // Optionally navigate to the new folder
      const shouldOpen = await showConfirm(
        "Folder created successfully. Do you want to open it?",
        "Folder Created"
      );
      if (shouldOpen) {
        navigateTo(data.path);
      }
    } else {
      showDialog(
        "Failed to create folder: " + (data.message || "Unknown error")
      );
    }
  } catch (error) {
    showDialog("Failed to create folder: " + error.message);
  }
}

/**
 * Navigate to parent folder
 */
function navigateToParent() {
  const urlParams = new URLSearchParams(window.location.search);
  const currentPath = urlParams.get("path") || "/";

  // Don't navigate if already at root
  if (currentPath === "/" || currentPath === "") {
    return;
  }

  // Get parent path
  const parentPath =
    currentPath.substring(0, currentPath.lastIndexOf("/")) || "/";

  // Navigate to parent
  navigateTo(parentPath);
}

/**
 * Update parent folder button state (always visible, disabled at root)
 */
function updateParentButtonVisibility() {
  const urlParams = new URLSearchParams(window.location.search);
  const currentPath = urlParams.get("path") || "/";
  const parentBtn = document.getElementById("parentFolderBtn");

  if (!parentBtn) return;

  // Disable button at root, enable otherwise
  if (currentPath === "/" || currentPath === "") {
    parentBtn.disabled = true;
    parentBtn.classList.add("opacity-50", "cursor-not-allowed");
    parentBtn.title = "At Root Directory";
  } else {
    parentBtn.disabled = false;
    parentBtn.classList.remove("opacity-50", "cursor-not-allowed");
    parentBtn.title = "Go to Parent Folder";
  }
}

/**
 * ============================================================================
 * EDIT FILE
 * ============================================================================
 */

/**
 * Edit selected file (open in editor or preview)
 */
async function editSelected() {
  // Check selection - should be exactly 1 file (button should be disabled otherwise)
  if (window.selectedItems.length !== 1) {
    showDialog("Please select exactly one file to edit.");
    return;
  }

  const file = window.selectedItems[0];

  // Double-check it's a file
  if (file.type !== "file") {
    showDialog("Folders cannot be edited.");
    return;
  }

  // Use real_path for symlinks, fallback to path
  const realPath = file.real_path || file.path;
  const extension = file.extension || "";

  // Open the file in editor or preview (uses existing previewFile function)
  previewFile(realPath, extension);
}

/**
 * ============================================================================
 * RENAME FILE/FOLDER
 * ============================================================================
 */

/**
 * Rename selected file or folder
 */
async function renameSelected() {
  // Check selection - should be exactly 1 item (button should be disabled otherwise)
  if (window.selectedItems.length !== 1) {
    showDialog("Please select exactly one item to rename.");
    return;
  }

  const item = window.selectedItems[0];
  const itemToRename = item.path;
  const itemType = item.type;
  const currentName = item.name;

  // Prompt for new name
  const promptMessage =
    itemType === "file"
      ? `Current name: ${currentName}\n\nEnter new name (with extension):`
      : `Current name: ${currentName}\n\nEnter new name:`;

  const promptTitle = itemType === "file" ? "Rename File" : "Rename Folder";
  const newName = await showPrompt(promptMessage, currentName, promptTitle);

  if (!newName || newName.trim() === "") {
    return; // User cancelled or empty input
  }

  const trimmedNewName = newName.trim();

  // Check if name actually changed
  if (trimmedNewName === currentName) {
    showDialog("The name is the same. No changes made.");
    return;
  }

  // Validate new name
  if (trimmedNewName.includes("/") || trimmedNewName.includes("\\")) {
    showDialog("Invalid name: Cannot contain slashes (/ or \\)");
    return;
  }

  try {
    // Get CSRF token
    const tokenResponse = await fetch("/api/csrf-token", {
      credentials: "same-origin",
    });
    const tokenData = await tokenResponse.json();

    if (!tokenData.success) {
      throw new Error(tokenData.message || "Failed to get security token");
    }

    const csrfToken = tokenData.csrf_token;

    // Create form data
    const params = new URLSearchParams();
    params.append("old_path", itemToRename);
    params.append("new_name", trimmedNewName);
    params.append("_csrf_token", csrfToken);

    // Send rename request
    const response = await fetch("/api/rename", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: params.toString(),
    });

    const data = await response.json();

    if (data.success) {
      // Navigate to parent folder
      navigateTo(data.parent_path);
      // Notify tree to refresh (manual dispatch for modification operations only)
      notifyFolderContentsChanged(data.parent_path);

      showDialog(
        `${itemType === "file" ? "File" : "Folder"} renamed successfully!`
      );
    } else {
      showDialog("Failed to rename: " + (data.message || "Unknown error"));
    }
  } catch (error) {
    showDialog("Failed to rename: " + error.message);
  }
}

/**
 * ============================================================================
 * DELETE SELECTED FILES/FOLDERS
 * ============================================================================
 */

/**
 * Delete selected files and/or folders
 */
async function deleteSelected() {
  // Check selection
  if (!window.selectedItems || window.selectedItems.length === 0) {
    showDialog("Please select at least one item to delete.");
    return;
  }

  const itemCount = window.selectedItems.length;

  // Confirm deletion with smart dialog (handles large selections elegantly)
  const confirmed = await showDeleteConfirm(window.selectedItems);

  if (!confirmed) {
    return; // User cancelled
  }

  try {
    // Get CSRF token
    const tokenResponse = await fetch("/api/csrf-token", {
      credentials: "same-origin",
    });
    const tokenData = await tokenResponse.json();

    if (!tokenData.success) {
      throw new Error(tokenData.message || "Failed to get security token");
    }

    const csrfToken = tokenData.csrf_token;

    // Collect all paths
    const paths = window.selectedItems.map((item) => item.path);

    // Send batch delete request (single API call for all items!)
    const params = new URLSearchParams();
    params.append("paths", JSON.stringify(paths));
    params.append("_csrf_token", csrfToken);

    const response = await fetch("/api/delete", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: params.toString(),
    });

    const data = await response.json();

    // Show results (API interceptor handles error responses automatically)
    if (data.success) {
      // All deleted successfully - show success toast
      if (window.showToast) {
        showToast.success('Files deleted', `${data.successCount} item(s) removed`, { duration: 3000 });
      }

      // Get current path for tree refresh
      const urlParams = new URLSearchParams(window.location.search);
      const currentPath = urlParams.get("path") || "/";

      // Refresh current folder
      handleUrlChange();
      // Notify tree to refresh (manual dispatch for modification operations only)
      notifyFolderContentsChanged(currentPath);

      // Clear selection
      clearSelection();
    } else if (data.successCount > 0) {
      // Some succeeded, some failed - show warning toast
      if (window.showToast) {
        showToast.warning(
          `Partial deletion`,
          `${data.successCount} deleted, ${data.failedCount} failed`,
          { duration: 5000 }
        );
      }

      // Get current path for tree refresh
      const urlParams = new URLSearchParams(window.location.search);
      const currentPath = urlParams.get("path") || "/";

      // Refresh current folder
      handleUrlChange();
      // Notify tree to refresh (manual dispatch for modification operations only)
      notifyFolderContentsChanged(currentPath);
      clearSelection();
    }
    // Note: If all failed (data.success === false), the API interceptor
    // will automatically show an error toast with the error message
  } catch (error) {
    // Network errors are handled by API interceptor
    console.error('Delete operation error:', error);
  }
}

/**
 * ============================================================================
 * DOWNLOAD SELECTED FILES
 * ============================================================================
 */

/**
 * Download selected file(s)
 */
async function downloadSelected() {
  // Check selection
  if (!window.selectedItems || window.selectedItems.length === 0) {
    showDialog("Please select at least one file to download.");
    return;
  }

  // Filter out folders - only allow files
  const files = window.selectedItems.filter((item) => item.type === "file");

  if (files.length === 0) {
    showDialog(
      "Please select at least one file to download.\n\nNote: Folders cannot be downloaded directly."
    );
    return;
  }

  if (files.length !== window.selectedItems.length) {
    const folderCount = window.selectedItems.length - files.length;
    const proceed = await showConfirm(
      `You have selected ${folderCount} folder(s) which cannot be downloaded.\n\nDo you want to download only the ${files.length} selected file(s)?`,
      "Download Files"
    );

    if (!proceed) {
      return;
    }
  }

  // Download each file
  for (const file of files) {
    try {
      // Create a temporary link and trigger download
      const link = document.createElement("a");
      link.href = `/api/download?path=${encodeURIComponent(file.path)}`;
      link.download = file.name;
      link.style.display = "none";
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      // Small delay between downloads to avoid browser blocking
      if (files.length > 1) {
        await new Promise((resolve) => setTimeout(resolve, 300));
      }
    } catch (error) {
      console.error(`Failed to download ${file.name}:`, error);
    }
  }

  // Show success message
  if (files.length === 1) {
    showDialog(`Downloading: ${files[0].name}`);
  } else {
    showDialog(`Downloading ${files.length} file(s)...`);
  }
}

/**
 * Unzip/Extract archive file via SSH
 * @param {string} path - Full path to the archive file
 */
async function unzipFile(path) {
  if (!path) {
    showDialog("Please select an archive file to extract");
    return;
  }

  // Get filename for confirmation
  const filename = path.split("/").pop();

  // Confirm extraction
  const confirmed = await showConfirm(
    `Extract archive "${filename}"?\n\nFiles will be extracted to the same directory.`,
    "Extract Archive"
  );

  if (!confirmed) {
    return;
  }

  try {
    // Get CSRF token
    const tokenResponse = await fetch("/api/csrf-token", {
      credentials: "same-origin",
    });
    const tokenData = await tokenResponse.json();

    if (!tokenData.success) {
      throw new Error(tokenData.message || "Failed to get security token");
    }

    const csrfToken = tokenData.csrf_token;

    // Send unzip request
    const params = new URLSearchParams();
    params.append("path", path);
    params.append("_csrf_token", csrfToken);

    const response = await fetch("/api/unzip", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      credentials: "same-origin",
      body: params.toString(),
    });

    const data = await response.json();

    if (data.success) {
      showDialog("Archive extracted successfully!");

      // Reload tree and refresh current folder
      if (typeof loadFolderTree === "function") {
        loadFolderTree("/", () => {
          handleUrlChange();
        });
      } else {
        handleUrlChange();
      }
    } else {
      throw new Error(data.message || "Failed to extract archive");
    }
  } catch (error) {
    console.error("Unzip error:", error);
    showDialog(`Failed to extract archive: ${error.message}`);
  }
}
