// Simple Integrated Editor
// Replaces the popup editor with an integrated one in the content area

let currentEditingFile = null;
let originalContent = null;

/**
 * Open file in integrated editor
 * NOTE: URL should already be updated before calling this
 */
function openIntegratedEditor(path) {
  // Store current file
  currentEditingFile = path;

  // Hide all content views
  document.getElementById("contentEmpty").classList.add("hidden");
  document.getElementById("contentLoading").classList.add("hidden");
  document.getElementById("listView").classList.add("hidden");

  // Show editor view
  document.getElementById("editorView").classList.remove("hidden");

  // Switch toolbars
  document.getElementById("fileManagerToolbar").classList.add("hidden");
  document.getElementById("editorToolbar").classList.remove("hidden");

  // Get file info for immediate UI update
  const fileName = path.split("/").pop();
  const icon = getFileIcon(path);
  const extension = fileName.split(".").pop().toLowerCase();

  // Update toolbar file info immediately (before API call)
  document.getElementById("editorToolbarFileName").textContent = fileName;
  document.getElementById("editorToolbarFileIcon").className = icon;
  document.getElementById("editorToolbarFileType").textContent =
    getFileTypeName(extension);
  document.getElementById("editorToolbarFileSize").textContent = "Loading...";

  // Clear editor container and show professional loading state
  const editorContainer = document.getElementById("editorContainer");

  // Clear any previous editor content
  editorContainer.innerHTML = "";

  // Create and add loading overlay
  const loadingOverlay = document.createElement("div");
  loadingOverlay.id = "editorLoadingOverlay";
  loadingOverlay.className =
    "absolute inset-0 flex flex-col items-center justify-center h-full space-y-6 bg-white dark:bg-gray-900 z-50";
  loadingOverlay.innerHTML = `
    <div class="flex flex-col items-center space-y-6 animate-pulse">
      <!-- Elegant loading spinner -->
      <div class="relative">
        <div class="w-16 h-16 border-4 border-gray-200 dark:border-gray-700 rounded-full"></div>
        <div class="absolute top-0 left-0 w-16 h-16 border-4 border-primary-500 dark:border-primary-400 rounded-full border-t-transparent animate-spin"></div>
      </div>

      <!-- Loading text -->
      <div class="text-center space-y-2">
        <p class="text-lg font-medium text-gray-700 dark:text-gray-300">Loading ${fileName}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">Please wait...</p>
      </div>

      <!-- Loading skeleton (simulates code lines) -->
      <div class="w-full max-w-2xl space-y-3 px-8">
        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4"></div>
        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-5/6"></div>
        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-2/3"></div>
        <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-4/5"></div>
      </div>
    </div>
  `;

  // Make container relative for absolute positioning of overlay
  editorContainer.style.position = "relative";
  editorContainer.appendChild(loadingOverlay);

  // Disable all editor toolbar buttons during loading
  document.getElementById("editorToolbarSaveBtn").disabled = true;
  const editorToolbar = document.getElementById("editorToolbar");
  const allButtons = editorToolbar.querySelectorAll("button");
  allButtons.forEach((btn) => {
    btn.disabled = true;
    btn.classList.add("opacity-50", "cursor-not-allowed");
  });

  // Track if we should close editor on completion (for error cases)
  let shouldCloseEditor = false;
  let errorMessage = null;

  // Fetch file content
  fetch("/api/file/read?path=" + encodeURIComponent(path))
    .then((response) => response.json())
    .then((data) => {
      if (!data.success) {
        // API returned error - mark for closure
        shouldCloseEditor = true;
        errorMessage = data.message || "Unknown error";
        return;
      }

      // Update file size in toolbar
      document.getElementById("editorToolbarFileSize").textContent =
        formatFileSize(data.size || 0);

      // Update legacy editor view file info (backwards compatibility)
      const editorFileNameEl = document.getElementById("editorFileName");
      if (editorFileNameEl) {
        editorFileNameEl.textContent = fileName;
      }

      const editorFileTypeEl = document.getElementById("editorFileType");
      if (editorFileTypeEl) {
        editorFileTypeEl.textContent = getFileTypeName(extension);
      }

      const editorFileSizeEl = document.getElementById("editorFileSize");
      if (editorFileSizeEl) {
        editorFileSizeEl.textContent = formatFileSize(data.size || 0);
      }

      if (data.isEditable) {
        // Initialize CodeMirror editor
        if (window.codeMirrorEditor) {
          window.codeMirrorEditor.initialize(data.content, extension, false);
          originalContent = data.content;
          window.codeMirrorEditor.setOriginalContent(data.content);
          window.codeMirrorEditor.setCurrentFilePath(path);
          window.codeMirrorEditor.setModified(false);

          // Update all editor button states based on actual state
          updateEditorButtons();
        }
      } else {
        // Show message for non-editable files
        editorContainer.innerHTML =
          '<div class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">This file type cannot be edited</div>';
        document.getElementById("editorToolbarSaveBtn").disabled = true;
      }
    })
    .catch((error) => {
      console.error("Error opening file:", error);

      // Network/JavaScript error - mark for closure
      shouldCloseEditor = true;
      errorMessage = error.message || "Network error";
    })
    .finally(() => {
      // Remove loading overlay from editorContainer
      const overlay = document.getElementById("editorLoadingOverlay");
      if (overlay && overlay.parentNode) {
        overlay.parentNode.removeChild(overlay);
      }

      // Re-enable all editor toolbar buttons
      allButtons.forEach((btn) => {
        btn.disabled = false;
        btn.classList.remove("opacity-50", "cursor-not-allowed");
      });

      // If error occurred, show message and close editor
      if (shouldCloseEditor) {
        // Use double requestAnimationFrame to ensure browser has painted the removal
        // This guarantees the spinner disappears before the alert shows
        requestAnimationFrame(() => {
          requestAnimationFrame(() => {
            alert("Failed to open file: " + errorMessage);
            closeEditor();
          });
        });
      }
    });
}

/**
 * Save file
 */
async function saveFile() {
  if (!window.codeMirrorEditor || !currentEditingFile) return;

  // Check if file is modified
  if (!window.codeMirrorEditor.isModified()) {
    return; // Nothing to save
  }

  const content = window.codeMirrorEditor.getContent();

  // Show saving state
  const saveBtn = document.getElementById("editorToolbarSaveBtn");
  const originalIcon = '<i class="fas fa-save"></i>';

  saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
  saveBtn.disabled = true;

  try {
    // First, get a fresh CSRF token
    const tokenResponse = await fetch("/api/csrf-token");
    const tokenData = await tokenResponse.json();

    if (!tokenData.success) {
      throw new Error(tokenData.message || "Failed to get security token");
    }

    const csrfToken = tokenData.csrf_token;

    // Create URL-encoded form data
    const params = new URLSearchParams();
    params.append("path", currentEditingFile);
    params.append("content", content);
    params.append("_csrf_token", csrfToken);

    const saveResponse = await fetch("/api/file/write", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: params.toString(),
    });

    const data = await saveResponse.json();

    if (data.success) {
      // Update original content
      originalContent = content;
      window.codeMirrorEditor.setOriginalContent(content);
      window.codeMirrorEditor.setModified(false);

      // Hide modified status
      document.getElementById("editorModifiedStatus")?.classList.add("hidden");

      // Show success icon briefly
      saveBtn.innerHTML = '<i class="fas fa-check text-green-500"></i>';
      setTimeout(() => {
        saveBtn.innerHTML = originalIcon;
        updateSaveButton(); // Update button state
      }, 2000);
    } else {
      alert("Failed to save: " + (data.message || "Unknown error"));
      saveBtn.innerHTML = originalIcon;
      saveBtn.disabled = false;
    }
  } catch (error) {
    alert("Failed to save file: " + error.message);
    saveBtn.innerHTML = originalIcon;
    saveBtn.disabled = false;
  }
}

/**
 * Refresh file from server
 */
function refreshFile() {
  if (!currentEditingFile) return;

  if (window.codeMirrorEditor && window.codeMirrorEditor.isModified()) {
    if (
      !confirm(
        "You have unsaved changes. Refreshing will discard them. Continue?"
      )
    ) {
      return;
    }
  }

  openIntegratedEditor(currentEditingFile);
}

/**
 * Search in file - open CodeMirror search panel
 */
function searchInFile() {
  // Call openSearchPanel just like in the example
  if (window.codeMirrorEditor && window.codeMirrorEditor.openSearchPanel) {
    window.codeMirrorEditor.openSearchPanel();
  }
}

/**
 * Close editor and return to file list
 */
function closeEditor() {
  // Check for unsaved changes
  if (window.codeMirrorEditor && window.codeMirrorEditor.isModified()) {
    if (!confirm("You have unsaved changes. Are you sure you want to close?")) {
      return;
    }
  }

  // Clear the editor container completely
  const editorContainer = document.getElementById("editorContainer");
  if (editorContainer) {
    editorContainer.innerHTML = "";
    editorContainer.style.position = "";
  }

  // Hide editor view
  document.getElementById("editorView").classList.add("hidden");

  // Switch toolbars back - show normal toolbar, hide editor toolbar
  document.getElementById("fileManagerToolbar").classList.remove("hidden");
  document.getElementById("editorToolbar").classList.add("hidden");

  // Navigate to parent folder
  const currentPath = currentEditingFile
    ? currentEditingFile.substring(0, currentEditingFile.lastIndexOf("/")) ||
      "/"
    : "/";

  currentEditingFile = null;

  window.navigateTo(currentPath);
}

/**
 * Get file type name
 */
function getFileTypeName(extension) {
  const types = {
    php: "PHP",
    js: "JavaScript",
    css: "CSS",
    html: "HTML",
    json: "JSON",
    xml: "XML",
    sql: "SQL",
    py: "Python",
    java: "Java",
    cpp: "C++",
    c: "C",
    go: "Go",
    rs: "Rust",
    md: "Markdown",
    txt: "Plain Text",
  };
  return types[extension] || extension.toUpperCase();
}

/**
 * Monitor for changes
 */
document.addEventListener("DOMContentLoaded", function () {
  // Monitor for modified state
  setInterval(() => {
    if (window.codeMirrorEditor && currentEditingFile) {
      const badge = document.getElementById("editorModifiedBadge");
      const topBadge = document.getElementById("editorToolbarModifiedBadge");

      if (window.codeMirrorEditor.isModified()) {
        if (badge) badge.classList.remove("hidden");
        if (topBadge) topBadge.classList.remove("hidden");
      } else {
        if (badge) badge.classList.add("hidden");
        if (topBadge) topBadge.classList.add("hidden");
      }
    }
  }, 500);
});

/**
 * Undo last change
 */
function editorUndo() {
  if (window.codeMirrorEditor && window.codeMirrorEditor.undo) {
    window.codeMirrorEditor.undo();
    updateEditorButtons();
  }
}

/**
 * Redo last undone change
 */
function editorRedo() {
  if (window.codeMirrorEditor && window.codeMirrorEditor.redo) {
    window.codeMirrorEditor.redo();
    updateEditorButtons();
  }
}

/**
 * Update undo/redo button states based on availability
 */
function updateUndoRedoButtons() {
  const undoBtn = document.querySelector('button[onclick="editorUndo()"]');
  const redoBtn = document.querySelector('button[onclick="editorRedo()"]');

  if (!undoBtn || !redoBtn || !window.codeMirrorEditor) return;

  // Check if undo/redo are available
  const canUndo = window.codeMirrorEditor.canUndo
    ? window.codeMirrorEditor.canUndo()
    : false;
  const canRedo = window.codeMirrorEditor.canRedo
    ? window.codeMirrorEditor.canRedo()
    : false;

  // Update button states
  if (canUndo) {
    undoBtn.disabled = false;
    undoBtn.classList.remove("opacity-50", "cursor-not-allowed");
    undoBtn.classList.add("hover:bg-gray-100", "dark:hover:bg-gray-700");
  } else {
    undoBtn.disabled = true;
    undoBtn.classList.add("opacity-50", "cursor-not-allowed");
    undoBtn.classList.remove("hover:bg-gray-100", "dark:hover:bg-gray-700");
  }

  if (canRedo) {
    redoBtn.disabled = false;
    redoBtn.classList.remove("opacity-50", "cursor-not-allowed");
    redoBtn.classList.add("hover:bg-gray-100", "dark:hover:bg-gray-700");
  } else {
    redoBtn.disabled = true;
    redoBtn.classList.add("opacity-50", "cursor-not-allowed");
    redoBtn.classList.remove("hover:bg-gray-100", "dark:hover:bg-gray-700");
  }
}

/**
 * Update save button state based on file modification status
 */
function updateSaveButton() {
  const saveBtn = document.getElementById("editorToolbarSaveBtn");
  if (!saveBtn || !window.codeMirrorEditor) return;

  const isModified = window.codeMirrorEditor.isModified();

  if (isModified) {
    saveBtn.disabled = false;
    saveBtn.classList.remove("opacity-50", "cursor-not-allowed");
    saveBtn.classList.add("hover:bg-gray-100", "dark:hover:bg-gray-700");
  } else {
    saveBtn.disabled = true;
    saveBtn.classList.add("opacity-50", "cursor-not-allowed");
    saveBtn.classList.remove("hover:bg-gray-100", "dark:hover:bg-gray-700");
  }
}

/**
 * Update all editor button states
 */
function updateEditorButtons() {
  updateUndoRedoButtons();
  updateSaveButton();
}

/**
 * Keyboard shortcuts
 */
document.addEventListener("keydown", function (e) {
  if (!currentEditingFile) return;

  // Ctrl+S / Cmd+S - Save
  if ((e.ctrlKey || e.metaKey) && e.key === "s") {
    e.preventDefault();
    saveFile();
  }

  // Escape - Close editor
  if (e.key === "Escape") {
    e.preventDefault();
    closeEditor();
  }
});
