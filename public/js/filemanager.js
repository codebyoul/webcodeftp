// WebFTP File Manager JavaScript
// Handles file operations, UI interactions, and file browser logic

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
function navigateTo(path, action = null) {
  const url = new URL(window.location);
  url.searchParams.set('path', path);

  if (action) {
    url.searchParams.set('action', action);
  } else {
    url.searchParams.delete('action');
  }

  window.history.pushState({ path, action }, '', url);

  // Dispatch custom event for URL change
  window.dispatchEvent(new CustomEvent('urlchange'));
}

/**
 * Handle URL changes - this is called when:
 * - Page loads (DOMContentLoaded)
 * - User clicks back/forward (popstate)
 * - navigateTo() is called
 */
function handleUrlChange() {
  const urlParams = new URLSearchParams(window.location.search);
  const path = urlParams.get('path') || '/';
  const action = urlParams.get('action');

  // ALWAYS sync tree first
  if (typeof highlightCurrentPath === 'function') {
    highlightCurrentPath(path);
  }

  // Determine what to show based on path and action
  const isFile = path.includes('.') && !path.endsWith('/');

  if (isFile) {
    // It's a FILE
    const folderPath = path.substring(0, path.lastIndexOf('/')) || '/';

    // Check if we need to load folder contents
    // If we're opening editor, we don't need to load the folder list
    if (action === 'edit') {
      // Just open the editor directly - no need to load folder contents
      openIntegratedEditor(path);
    } else {
      // For image preview or file info, we need the file data from folder contents
      loadFolderContents(folderPath, (data) => {
        if (!data || !data.success) {
          displayPathNotFound(path);
          return;
        }

        const fileData = (data.files || []).find(f => f.path === path);

        if (!fileData) {
          displayFileNotFound(path);
          return;
        }

        if (isImageFile(fileData.name)) {
          displayImagePreview(fileData);
        } else {
          displaySelectedFile(fileData);
        }
      });
    }
  } else {
    // It's a FOLDER
    loadFolderContents(path, (data) => {
      if (!data || !data.success) {
        displayPathNotFound(path);
      }
    });
  }
}

/**
 * Preview/Edit file - now just updates URL
 */
function previewFile(path) {
  closeAllPreviews();

  // Check if it's an image or code file
  const filename = path.split('/').pop();

  if (isImageFile(filename)) {
    // Navigate without action (will show image preview)
    navigateTo(path);
  } else {
    // Navigate with edit action (will open editor)
    navigateTo(path, 'edit');
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
 * Refresh current folder contents
 */
function refreshCurrentFolder() {
  const urlParams = new URLSearchParams(window.location.search);
  const currentPath = urlParams.get('path') || '/';

  const refreshBtn = document.getElementById("refreshBtn");
  const icon = refreshBtn.querySelector("i");
  icon.classList.add("fa-spin");

  // Re-trigger URL handler to refresh content
  handleUrlChange();

  setTimeout(() => {
    icon.classList.remove("fa-spin");
  }, 1000);
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
// FILE SELECTION AND ACTIONS
// =================================================================

// Global selection state management
// Two distinct types of selection:
// 1. Checkbox Selection (multi-select for operations) - stored in Set
// 2. Navigation Focus (single-select for preview) - stored as single item
window.checkedItems = new Set(); // Paths of checkbox-selected items (persists across views)
window.focusedItem = null;       // Path of navigation-focused item (clears on view switch)
window.selectedFiles = [];       // Legacy compatibility - synced with checkedItems

/**
 * Handle file/folder selection (multi-select support)
 */
function selectItem(path, name, type, extension) {
  // For single-select compatibility
  window.selectedFile = {
    path: path,
    name: name,
    type: type,
    extension: extension,
  };

  // Check if already selected
  const existingIndex = window.selectedFiles.findIndex(f => f.path === path);

  if (existingIndex === -1) {
    // Add to selection
    window.selectedFiles.push({
      path: path,
      name: name,
      type: type,
      extension: extension,
    });
  }

  // Update unzip button state based on selection
  updateUnzipButtonState();
}

/**
 * Deselect a specific item
 */
function deselectItem(path) {
  window.selectedFiles = window.selectedFiles.filter(f => f.path !== path);

  // Clear single selection if it matches
  if (window.selectedFile && window.selectedFile.path === path) {
    window.selectedFile = null;
  }

  // Update unzip button state
  updateUnzipButtonState();
}

/**
 * Add item to checkbox selection (blue highlight)
 */
function checkboxSelectItem(path, name, type, extension) {
  window.checkedItems.add(path);

  // Legacy compatibility
  const existingIndex = window.selectedFiles.findIndex(f => f.path === path);
  if (existingIndex === -1) {
    window.selectedFiles.push({ path, name, type, extension });
  }
  window.selectedFile = { path, name, type, extension };

  updateUnzipButtonState();
}

/**
 * Remove item from checkbox selection
 */
function checkboxDeselectItem(path) {
  window.checkedItems.delete(path);

  // Legacy compatibility
  window.selectedFiles = window.selectedFiles.filter(f => f.path !== path);
  if (window.selectedFile && window.selectedFile.path === path) {
    window.selectedFile = null;
  }

  updateUnzipButtonState();
}

/**
 * Set navigation focus (gray highlight)
 */
function setNavigationFocus(path) {
  // Clear previous focus visual
  clearNavigationFocus();

  // Set new focus
  window.focusedItem = path;
}

/**
 * Clear navigation focus
 */
function clearNavigationFocus() {
  // Remove navigation focus from all items
  document.querySelectorAll('.navigation-focused').forEach(el => {
    el.classList.remove('navigation-focused', 'bg-gray-100', 'dark:bg-gray-700/50');
  });

  window.focusedItem = null;
}

/**
 * Update Select All checkbox state (checked/unchecked/indeterminate)
 */
function updateSelectAllCheckboxState() {
  const selectAllCheckbox = document.getElementById('selectAllCheckbox');
  const listCheckboxes = Array.from(document.querySelectorAll('#listViewBody input[type="checkbox"]'));

  if (!selectAllCheckbox) return;

  if (listCheckboxes.length === 0) {
    selectAllCheckbox.checked = false;
    selectAllCheckbox.indeterminate = false;
    return;
  }

  const checkedCount = listCheckboxes.filter(cb => cb.checked).length;

  if (checkedCount === 0) {
    // None checked
    selectAllCheckbox.checked = false;
    selectAllCheckbox.indeterminate = false;
  } else if (checkedCount === listCheckboxes.length) {
    // All checked
    selectAllCheckbox.checked = true;
    selectAllCheckbox.indeterminate = false;
  } else {
    // Some checked (indeterminate)
    selectAllCheckbox.checked = false;
    selectAllCheckbox.indeterminate = true;
  }
}

/**
 * Clear all checkbox selections
 */
function clearSelection() {
  window.selectedFile = null;
  window.selectedFiles = [];
  window.checkedItems.clear();

  // Uncheck all file checkboxes (except select-all)
  document.querySelectorAll('input[type="checkbox"]:not(#selectAllCheckbox)').forEach((cb) => {
    cb.checked = false;
  });

  // Uncheck select-all checkbox
  const selectAllCheckbox = document.getElementById('selectAllCheckbox');
  if (selectAllCheckbox) {
    selectAllCheckbox.checked = false;
    selectAllCheckbox.indeterminate = false;
  }

  // Remove checkbox selection visual from grid cards
  document.querySelectorAll(".checkbox-selected").forEach((el) => {
    el.classList.remove(
      "checkbox-selected",
      "border-primary-500",
      "dark:border-primary-400",
      "bg-primary-100",
      "dark:bg-primary-900/30"
    );
  });

  // Clear navigation focus
  clearNavigationFocus();

  // Update unzip button state
  updateUnzipButtonState();
}

/**
 * Update unzip button state (enabled only if exactly 1 zip file is selected)
 */
function updateUnzipButtonState() {
  const unzipBtn = document.getElementById('unzipBtn');
  if (!unzipBtn) return; // SSH not enabled

  const zipExtensions = ['zip', 'tar', 'gz', 'bz2', '7z', 'rar', 'tgz', 'xz'];

  // Enable if exactly 1 file selected and it's a zip format
  if (window.selectedFiles.length === 1) {
    const selectedFile = window.selectedFiles[0];
    const isZipFile = zipExtensions.includes(selectedFile.extension.toLowerCase());

    if (isZipFile) {
      unzipBtn.disabled = false;
      unzipBtn.classList.remove('opacity-50', 'cursor-not-allowed');
      unzipBtn.classList.add('hover:bg-gray-100', 'dark:hover:bg-gray-700');
    } else {
      unzipBtn.disabled = true;
      unzipBtn.classList.add('opacity-50', 'cursor-not-allowed');
      unzipBtn.classList.remove('hover:bg-gray-100', 'dark:hover:bg-gray-700');
    }
  } else {
    // Disable if 0 or more than 1 file selected
    unzipBtn.disabled = true;
    unzipBtn.classList.add('opacity-50', 'cursor-not-allowed');
    unzipBtn.classList.remove('hover:bg-gray-100', 'dark:hover:bg-gray-700');
  }
}

/**
 * Select all files in current view
 */
function selectAllFiles() {
  const checkboxes = document.querySelectorAll('#listViewBody input[type="checkbox"]');
  checkboxes.forEach(cb => {
    cb.checked = true;
    cb.dispatchEvent(new Event('change'));
  });
}

/**
 * Deselect all files in current view
 */
function deselectAllFiles() {
  clearSelection();
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
    selectAllCheckbox.addEventListener("change", function() {
      if (this.checked) {
        // Select all items in list view
        const listCheckboxes = document.querySelectorAll('#listViewBody input[type="checkbox"]');
        listCheckboxes.forEach(cb => {
          if (!cb.checked) {
            cb.checked = true;
            cb.dispatchEvent(new Event('change'));
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

    return fetch("/api/folder-tree?path=" + encodeURIComponent(path))
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

      // Container for children
      const childrenContainer = document.createElement("div");
      childrenContainer.className = "hidden";
      childrenContainer.dataset.childrenFor = item.path;

      // Click handler for folders
      button.addEventListener("click", function (e) {

        // Navigate to folder
        navigateTo(item.path);

        // Toggle folder expand/collapse in sidebar
        toggleFolder(item.path, arrow, childrenContainer, level);

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

      // Click handler for files - show file info in main content
      button.addEventListener("click", function (e) {
        displaySelectedFile(item);
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
    } else {
      // Expand
      arrow.classList.add("rotate-90");
      childrenContainer.classList.remove("hidden");
      expandedFolders.add(path);
    }

    // Always reload children to match main list view (both expand and collapse)
    // This ensures tree always shows fresh data from FTP server
    childrenContainer.innerHTML = ''; // Clear old data
    loadFolderChildren(path, childrenContainer, level + 1);

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

    fetch("/api/folder-tree?path=" + encodeURIComponent(path))
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
  loadFolderTree("/", function() {
    handleUrlChange();
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
    return fetch("/api/folder-contents?path=" + encodeURIComponent(path))
      .then((response) => response.json())
      .then((data) => {
        // Hide loading
        if (contentLoading) contentLoading.classList.add("hidden");

        if (data.success) {
          // Render contents in list view
          renderListView(data.folders, data.files, path);

          // Show list view
          listView.classList.remove("hidden");

          // Call the callback when content is loaded
          if (onComplete) {
            onComplete(data);
          }
        } else {
          // Folder load failed - call callback so caller can handle error
          if (onComplete) {
            onComplete(data);
          } else {
            // Only show generic error if no callback provided (backwards compatibility)
            if (contentEmpty) {
              contentEmpty.classList.remove("hidden");
              contentEmpty.innerHTML =
                '<div class="text-center px-6"><i class="fas fa-exclamation-triangle text-red-500 text-6xl mb-4"></i><h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">Error Loading Folder</h3><p class="text-gray-500 dark:text-gray-400">' +
                (data.message || "Unable to load folder contents") +
                "</p></div>";
            }
          }
        }
      })
      .catch((error) => {
        if (contentLoading) contentLoading.classList.add("hidden");
        if (contentEmpty) {
          contentEmpty.classList.remove("hidden");
          contentEmpty.innerHTML =
            '<div class="text-center px-6"><i class="fas fa-exclamation-triangle text-red-500 text-6xl mb-4"></i><h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">Connection Error</h3><p class="text-gray-500 dark:text-gray-400">Unable to connect to server</p></div>';
        }
      });
  }

  // Make loadFolderContents globally accessible for refresh button
  window.loadFolderContents = loadFolderContents;

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

  /**
   * Convert Unix permissions to octal format
   * Example: "drwxr-xr-x" -> "755"
   * Example: "-rw-r--r--" -> "644"
   */
  function convertPermissionsToOctal(permissions) {
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
  }

  /**
   * Display selected file in main content area
   */
  function displaySelectedFile(file) {
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

                    <!-- Edit/Preview Button (Small & Elegant) -->
                    <div class="flex items-center justify-center">
                        <button onclick="previewFile('${escapeHtml(
                          file.path
                        )}')" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 bg-primary-50 dark:bg-primary-900/20 hover:bg-primary-100 dark:hover:bg-primary-900/30 rounded-lg border border-primary-200 dark:border-primary-800 transition-all duration-200">
                            <i class="fas ${isImageFile(file.name) ? 'fa-eye' : 'fa-pen-to-square'} text-sm"></i>
                            <span>${isImageFile(file.name) ? 'Preview Image' : 'Edit File'}</span>
                        </button>
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
  }

  /**
   * Display file not found error (red styled, same position as file preview)
   */
  function displayFileNotFound(path) {

    // Clear previous content and show empty state
    const contentEmpty = document.getElementById("contentEmpty");
    const contentLoading = document.getElementById("contentLoading");
    const listView = document.getElementById("listView");
    

    if (contentLoading) contentLoading.classList.add("hidden");
    listView.classList.add("hidden");
    

    // Get filename and extension
    const fileName = path.substring(path.lastIndexOf('/') + 1);
    const extension = fileName.split('.').pop().toLowerCase();

    // Get file icon (but make it red)
    const iconClasses = getFileIcon(fileName)
      .replace(/text-\w+-\d+/g, 'text-red-500')
      .replace(/dark:text-\w+-\d+/g, 'dark:text-red-400')
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
          <h2 class="text-2xl font-bold text-red-600 dark:text-red-400 mb-3 break-all">${escapeHtml(fileName)}</h2>

          <!-- Error Message -->
          <div class="flex items-center justify-center gap-2 text-red-600 dark:text-red-400 mb-6">
            <i class="fas fa-exclamation-triangle text-lg"></i>
            <span class="text-lg font-semibold">File not found</span>
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
  }

  /**
   * Display path not found error (for completely invalid paths)
   */
  function displayPathNotFound(path) {

    // Clear previous content and show empty state
    const contentEmpty = document.getElementById("contentEmpty");
    const contentLoading = document.getElementById("contentLoading");
    const listView = document.getElementById("listView");
    

    if (contentLoading) contentLoading.classList.add("hidden");
    listView.classList.add("hidden");
    

    // Determine if it's a file or folder based on extension
    const isFile = path.includes('.') && !path.endsWith('/');
    const iconClass = isFile
      ? 'fas fa-file text-red-500 dark:text-red-400'
      : 'fas fa-folder text-red-500 dark:text-red-400';

    // Create error display
    const errorDisplay = `
      <div class="flex items-center justify-center h-full">
        <div class="text-center px-8 max-w-2xl">
          <!-- Red Icon (file or folder) -->
          <div class="relative inline-block mb-6">
            <div class="absolute inset-0 bg-red-500 rounded-2xl blur-xl opacity-20 animate-pulse"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-12 shadow-xl border-2 border-red-300 dark:border-red-700">
              <i class="${iconClass} text-7xl"></i>
            </div>
          </div>

          <!-- Error Message -->
          <div class="flex items-center justify-center gap-2 text-red-600 dark:text-red-400 mb-6">
            <i class="fas fa-exclamation-triangle text-xl"></i>
            <span class="text-xl font-semibold">Path not found</span>
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
  }

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
   */
  function createListRow(item, type, currentPath) {
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

    // Check if this item is already in checkbox selection
    if (window.checkedItems.has(item.path)) {
      checkbox.checked = true;
      row.classList.add(
        "checkbox-selected",
        "bg-primary-100",
        "dark:bg-primary-900/30"
      );
    }

    checkboxCell.appendChild(checkbox);
    row.appendChild(checkboxCell);

    const extension = item.name.split(".").pop().toLowerCase();

    // Checkbox change handler (checkbox selection only)
    checkbox.addEventListener("change", function (e) {
      e.stopPropagation();

      if (checkbox.checked) {
        // Add to checkbox selection (blue highlight)
        checkboxSelectItem(item.path, item.name, type, extension);
        row.classList.add(
          "checkbox-selected",
          "bg-primary-100",
          "dark:bg-primary-900/30"
        );
      } else {
        // Remove from checkbox selection
        checkboxDeselectItem(item.path);
        row.classList.remove(
          "checkbox-selected",
          "bg-primary-100",
          "dark:bg-primary-900/30"
        );
      }

      // Update Select All checkbox state
      updateSelectAllCheckboxState();
    });

    // Row click handler (navigation focus only - NO checkbox toggle)
    row.addEventListener("click", function (e) {
      // If clicking on the checkbox cell, let the checkbox handler deal with it
      if (e.target === checkbox || e.target === checkboxCell) {
        return;
      }

      // Set navigation focus (gray highlight)
      setNavigationFocus(item.path);
      row.classList.add("navigation-focused", "bg-gray-100", "dark:bg-gray-700/50");
    });

    // Row double-click handler (professional behavior)
    row.addEventListener("dblclick", function (e) {
      // If clicking on the checkbox cell, ignore
      if (e.target === checkbox || e.target === checkboxCell) {
        return;
      }

      if (type === "folder") {
        navigateTo(item.path);
      } else {
        previewFile(item.path);
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
  }


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
  window.highlightCurrentPath = function(path, expandParents = true) {
    // If we need to expand parents, do it FIRST, then highlight
    if (expandParents) {
      expandParentFolders(path, () => {
        // After expanding, highlight with expandParents=false to avoid infinite loop
        highlightCurrentPath(path, false);
      });
      return; // Exit early, let the callback handle highlighting
    }

    // Remove previous highlights
    const previousHighlights = document.querySelectorAll('.sidebar-tree-item-active');
    previousHighlights.forEach(el => {
      el.classList.remove('sidebar-tree-item-active', 'bg-blue-100', 'dark:bg-blue-900', 'text-blue-600', 'dark:text-blue-400');
    });

    // Find and highlight the current path
    const sidebarItems = document.querySelectorAll('[data-path]');

    sidebarItems.forEach(item => {
      const itemPath = item.getAttribute('data-path');

      if (itemPath === path) {
        // Add highlight to the button inside the item, not the container
        const button = item.querySelector('button');
        if (button) {
          button.classList.add('sidebar-tree-item-active', 'bg-blue-100', 'dark:bg-blue-900', 'text-blue-600', 'dark:text-blue-400');
        } else {
          // For files - highlight the whole div
          item.classList.add('sidebar-tree-item-active', 'bg-blue-100', 'dark:bg-blue-900', 'text-blue-600', 'dark:text-blue-400');
        }

        // Scroll into view
        item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    });
  }

  /**
   * Expand parent folders to make a path visible (one by one, sequentially)
   */
  async function expandParentFolders(path, onComplete = null) {
    const parts = path.split('/').filter(p => p);

    // Check if the target is a file (has extension)
    const isTargetFile = path.includes('.') && !path.endsWith('/');

    // If target is a file, only process parent folders (exclude the last part which is the filename)
    const partsToProcess = isTargetFile ? parts.slice(0, -1) : parts;

    // Expand folders one by one, SEQUENTIALLY
    let currentPath = '';
    for (let i = 0; i < partsToProcess.length; i++) {
      currentPath += '/' + partsToProcess[i];

      const folderElement = document.querySelector(`[data-path="${currentPath}"]`);

      if (folderElement && folderElement.dataset.type === 'directory') {

        const button = folderElement.querySelector('button');
        if (!button) {
          continue;
        }

        const arrow = button.querySelector('.folder-arrow');
        const childrenContainer = folderElement.querySelector('[data-children-for]');

        if (arrow && childrenContainer) {
          const isHidden = childrenContainer.classList.contains('hidden');

          if (isHidden) {
            arrow.classList.add('rotate-90');
            childrenContainer.classList.remove('hidden');
            expandedFolders.add(currentPath);

            // Load children if empty - WAIT for this to complete before continuing
            if (childrenContainer.children.length === 0) {
              await new Promise((resolve) => {
                loadFolderChildren(currentPath, childrenContainer, i + 1, resolve);
              });
            }
          } else {
          }
        }
      } else {
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
          alert("Please enter a path");
          return;
        }

        // Normalize path (ensure it starts with /)
        const normalizedPath = inputPath.startsWith('/') ? inputPath : '/' + inputPath;

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
 */
function isImageFile(filename) {
  const imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'bmp', 'ico', 'tiff', 'tif'];
  const extension = filename.split('.').pop().toLowerCase();
  return imageExtensions.includes(extension);
}

/**
 * Display image preview
 */
function displayImagePreview(fileData) {
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
  document.getElementById("imagePreviewInfo").textContent = `${formatFileSize(fileData.size)} • Loading...`;

  // Get image container and show loading state
  const img = document.getElementById("imagePreviewImg");
  const imageContainer = img.parentElement;

  // Create loading overlay
  const loadingOverlay = document.createElement('div');
  loadingOverlay.id = 'imageLoadingOverlay';
  loadingOverlay.className = 'absolute inset-0 flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-900 z-10';
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
  const existingOverlay = document.getElementById('imageLoadingOverlay');
  if (existingOverlay) {
    existingOverlay.remove();
  }

  // Add loading overlay
  imageContainer.style.position = 'relative';
  imageContainer.appendChild(loadingOverlay);

  // Clear previous image immediately to prevent showing old content
  img.src = '';
  img.style.transform = "scale(1)";

  // Load new image
  img.onload = function() {
    // Remove loading overlay
    const overlay = document.getElementById('imageLoadingOverlay');
    if (overlay) {
      overlay.remove();
    }

    // Update info with dimensions
    const info = document.getElementById("imagePreviewInfo");
    info.textContent = `${formatFileSize(fileData.size)} • ${img.naturalWidth} × ${img.naturalHeight} px`;
  };

  img.onerror = function() {
    // Remove loading overlay
    const overlay = document.getElementById('imageLoadingOverlay');
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
}

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

  const link = document.createElement('a');
  link.href = `/api/file/image?path=${encodeURIComponent(currentImagePath)}`;
  link.download = currentImagePath.split('/').pop();
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
  const currentPath = currentImagePath ? currentImagePath.substring(0, currentImagePath.lastIndexOf('/')) || '/' : '/';
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
  if (!window.selectedFiles || window.selectedFiles.length === 0) {
    alert('Please select at least one file or folder to zip.');
    return;
  }

  // TODO: Implement SSH-based zip operation
  // This will require:
  // 1. SSH connection to server
  // 2. Create zip archive using command-line tools
  // 3. Return success/failure status

  alert(`Zip functionality coming soon!\n\nSelected ${window.selectedFiles.length} item(s):\n${window.selectedFiles.map(f => f.name).join('\n')}`);
}

/**
 * Unzip selected archive file
 * Requires SSH connection and exactly 1 zip file selected
 */
function unzipSelectedFile() {
  if (!window.selectedFiles || window.selectedFiles.length !== 1) {
    alert('Please select exactly one archive file to unzip.');
    return;
  }

  const selectedFile = window.selectedFiles[0];
  const zipExtensions = ['zip', 'tar', 'gz', 'bz2', '7z', 'rar', 'tgz', 'xz'];

  if (!zipExtensions.includes(selectedFile.extension.toLowerCase())) {
    alert('Selected file is not a supported archive format.');
    return;
  }


  // TODO: Implement SSH-based unzip operation
  // This will require:
  // 1. SSH connection to server
  // 2. Extract archive using appropriate command (unzip, tar, etc.)
  // 3. Handle different archive formats
  // 4. Return success/failure status

  alert(`Unzip functionality coming soon!\n\nArchive: ${selectedFile.name}\nFormat: ${selectedFile.extension.toUpperCase()}`);
}

/**
 * Move selected files/folders to another directory
 * Requires SSH connection to be enabled in config
 */
function moveSelectedFiles() {
  if (!window.selectedFiles || window.selectedFiles.length === 0) {
    alert('Please select at least one file or folder to move.');
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
  const destination = prompt(`Move ${window.selectedFiles.length} item(s) to:\n\n${window.selectedFiles.map(f => f.name).join('\n')}\n\nEnter destination path:`);

  if (destination) {
    alert(`Move functionality coming soon!\n\nWould move to: ${destination}`);
  }
}

/**
 * ============================================================================
 * URL CHANGE DETECTION - CENTRAL MECHANISM
 * Listen to URL change events and trigger handleUrlChange()
 * ============================================================================
 */

// Listen to custom urlchange event (dispatched by navigateTo)
window.addEventListener('urlchange', handleUrlChange);

// Listen to popstate (back/forward buttons)
window.addEventListener('popstate', handleUrlChange);
