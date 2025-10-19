// WebFTP File Manager JavaScript
// Handles file operations, UI interactions, and file browser logic

/**
 * Preview/Edit file using integrated editor or image preview
 */
function previewFile(path) {
  // Check if it's an image file
  const filename = path.split('/').pop();
  if (isImageFile(filename)) {
    // Get file data from current context and show image preview
    // We need to fetch the file data to get size and other details
    fetch(`/api/folder-contents?path=${encodeURIComponent(path.substring(0, path.lastIndexOf('/')) || '/')}`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Find the file in the returned data
          const file = data.files.find(f => f.path === path);
          if (file) {
            displayImagePreview(file);
          }
        }
      })
      .catch(error => {
        console.error('Error loading file data:', error);
      });
  } else {
    // Use integrated editor for code files
    if (typeof openIntegratedEditor === 'function') {
      openIntegratedEditor(path, true);
    }
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
  // Get current path from input
  const pathInput = document.getElementById("pathInput");
  const currentPath = pathInput ? pathInput.value : "/";

  // Show visual feedback - spin the refresh icon
  const refreshBtn = document.getElementById("refreshBtn");
  const icon = refreshBtn.querySelector("i");
  icon.classList.add("fa-spin");

  // Store the loadFolderContents function reference
  if (window.loadFolderContents) {
    window.loadFolderContents(currentPath);

    // Stop spinning after 1 second
    setTimeout(() => {
      icon.classList.remove("fa-spin");
    }, 1000);
  } else {
    // If function not available yet, remove spin immediately
    icon.classList.remove("fa-spin");
  }
}

// =================================================================
// FILE SELECTION AND ACTIONS
// =================================================================

// Global variable to track selected file
window.selectedFile = null;

/**
 * Handle file/folder selection
 */
function selectItem(path, name, type, extension) {
  window.selectedFile = {
    path: path,
    name: name,
    type: type,
    extension: extension,
  };
}

/**
 * Clear file selection
 */
function clearSelection() {
  window.selectedFile = null;

  // Uncheck all checkboxes
  document.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
    cb.checked = false;
  });

  // Remove visual selection from grid cards
  document.querySelectorAll(".file-card-selected").forEach((c) => {
    c.classList.remove(
      "file-card-selected",
      "border-primary-600",
      "dark:border-primary-400",
      "bg-primary-50",
      "dark:bg-primary-900/20"
    );
  });

  // Remove visual selection from list rows
  document.querySelectorAll(".file-row-selected").forEach((r) => {
    r.classList.remove(
      "file-row-selected",
      "bg-primary-50",
      "dark:bg-primary-900/20"
    );
  });
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

        // Load folder contents in main area (updates URL, highlights folder, and loads content)
        loadFolderContents(item.path);

        // Toggle folder expand/collapse in sidebar (independent of highlighting)
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

  // Initial load - load tree first, then initialize from URL
  loadFolderTree("/", function() {
    initializeFromUrl();
  });

  // View Toggle (List/Grid)
  const viewToggleList = document.getElementById("viewToggleList");
  const viewToggleGrid = document.getElementById("viewToggleGrid");
  const listView = document.getElementById("listView");
  const gridView = document.getElementById("gridView");

  viewToggleList.addEventListener("click", function () {
    // Show list view
    listView.classList.remove("hidden");
    gridView.classList.add("hidden");

    // Update button states
    viewToggleList.className =
      "p-2 text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 rounded transition";
    viewToggleGrid.className =
      "p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition";
  });

  viewToggleGrid.addEventListener("click", function () {
    // Show grid view
    listView.classList.add("hidden");
    gridView.classList.remove("hidden");

    // Update button states
    viewToggleGrid.className =
      "p-2 text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 rounded transition";
    viewToggleList.className =
      "p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition";
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
   * Load folder contents in main content area
   */
  function loadFolderContents(path, updateUrl = true, onComplete = null) {

    // Clear any file selection when loading new folder
    clearSelection();

    // Update URL with current path (for shareable links)
    if (updateUrl) {
      const url = new URL(window.location);
      url.searchParams.set('path', path);
      window.history.pushState({ path: path }, '', url);
    }

    // Get container elements
    const contentEmpty = document.getElementById("contentEmpty");
    const contentLoading = document.getElementById("contentLoading");
    const listView = document.getElementById("listView");
    const gridView = document.getElementById("gridView");

    // Show loading state
    if (contentEmpty) contentEmpty.classList.add("hidden");
    if (contentLoading) contentLoading.classList.remove("hidden");
    listView.classList.add("hidden");
    gridView.classList.add("hidden");

    // Update path input
    const pathInput = document.getElementById("pathInput");
    if (pathInput) {
      pathInput.value = path;
    }

    // Only highlight if we're updating the URL (user-initiated navigation)
    // Skip highlighting on initial load to avoid duplicate calls
    // Pass expandParents = false because folder clicks handle their own expansion via toggleFolder
    if (updateUrl) {
      highlightCurrentPath(path, false);
    }

    // Fetch folder contents
    return fetch("/api/folder-contents?path=" + encodeURIComponent(path))
      .then((response) => response.json())
      .then((data) => {
        // Hide loading
        if (contentLoading) contentLoading.classList.add("hidden");

        if (data.success) {
          // Render contents in BOTH views
          renderEliteGrid(data.folders, data.files, path);
          renderListView(data.folders, data.files, path);

          // Show the appropriate view based on user preference
          const currentView = localStorage.getItem("fileManagerView") || "list";
          if (currentView === "list") {
            listView.classList.remove("hidden");
            gridView.classList.add("hidden");
          } else {
            gridView.classList.remove("hidden");
            listView.classList.add("hidden");
          }

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
   * Render folder/file contents in elite grid design
   */
  function renderEliteGrid(folders, files, currentPath) {
    const gridView = document.getElementById("gridView");

    // Create elite grid container
    gridView.innerHTML =
      '<div class="p-6"><div id="eliteGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-5"></div></div>';

    const eliteGrid = document.getElementById("eliteGrid");

    // Render folders first
    folders.forEach((folder) => {
      const card = createEliteCard(folder, "folder", currentPath);
      eliteGrid.appendChild(card);
    });

    // Render files
    files.forEach((file) => {
      const card = createEliteCard(file, "file", currentPath);
      eliteGrid.appendChild(card);
    });

    // If empty
    if (folders.length === 0 && files.length === 0) {
      eliteGrid.innerHTML =
        '<div class="col-span-full text-center py-16"><i class="fas fa-folder-open text-gray-300 dark:text-gray-600 text-6xl mb-4"></i><p class="text-gray-500 dark:text-gray-400">This folder is empty</p></div>';
    }
  }

  /**
   * Create elite card for folder or file
   */
  function createEliteCard(item, type, currentPath) {
    const card = document.createElement("div");
    card.className =
      "group relative bg-white dark:bg-gray-800 rounded-xl p-5 border-2 border-gray-200 dark:border-gray-700 hover:border-primary-500 dark:hover:border-primary-500 hover:shadow-2xl hover:scale-105 transform transition-all duration-300 cursor-pointer";
    card.dataset.path = item.path;
    card.dataset.type = type;

    // Add click handler (single click)
    card.addEventListener("click", function (e) {
      if (type === "folder") {
        loadFolderContents(item.path);
        clearSelection();
      } else {
        // Select file and update extract button
        const extension = item.name.split(".").pop().toLowerCase();
        selectItem(item.path, item.name, type, extension);

        // Remove selection from all other cards
        document.querySelectorAll(".file-card-selected").forEach((c) => {
          c.classList.remove(
            "file-card-selected",
            "border-primary-600",
            "dark:border-primary-400",
            "bg-primary-50",
            "dark:bg-primary-900/20"
          );
        });

        // Add selection to this card
        card.classList.add(
          "file-card-selected",
          "border-primary-600",
          "dark:border-primary-400",
          "bg-primary-50",
          "dark:bg-primary-900/20"
        );

        // Show file details
        displaySelectedFile(item);
      }
    });

    // Add double-click handler (professional behavior)
    card.addEventListener("dblclick", function (e) {
      if (type === "folder") {
        // Double-click folder = open it (same as single click for folders)
        loadFolderContents(item.path);
        clearSelection();
      } else {
        // Double-click file = edit/preview it
        previewFile(item.path);
      }
    });

    // Icon container
    const iconContainer = document.createElement("div");
    iconContainer.className = "flex flex-col items-center";

    // Icon
    const icon = document.createElement("i");
    if (type === "folder") {
      icon.className =
        "fas fa-folder text-5xl text-yellow-500 dark:text-yellow-400 mb-4 group-hover:scale-110 transition-transform duration-300";
    } else {
      icon.className =
        getFileIcon(item.name).replace(/text-\w+/g, "text-5xl") +
        " mb-4 group-hover:scale-110 transition-transform duration-300";
    }
    iconContainer.appendChild(icon);

    // Name
    const name = document.createElement("div");
    name.className = "text-center w-full";
    const nameSpan = document.createElement("span");
    nameSpan.className =
      "text-sm font-medium text-gray-900 dark:text-white block truncate px-2";
    nameSpan.textContent = item.name;
    nameSpan.title = item.name; // Tooltip for full name
    name.appendChild(nameSpan);

    // Size (for files only)
    if (type === "file" && item.size) {
      const size = document.createElement("span");
      size.className = "text-xs text-gray-500 dark:text-gray-400 mt-1 block";
      size.textContent = formatFileSize(item.size);
      name.appendChild(size);
    }

    iconContainer.appendChild(name);
    card.appendChild(iconContainer);

    return card;
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
    // Update URL with file path (without action=edit)
    const url = new URL(window.location);
    url.searchParams.set('path', file.path);
    url.searchParams.delete('action'); // Remove action parameter
    window.history.pushState({ path: file.path }, '', url);

    // Highlight file in sidebar
    highlightCurrentPath(file.path);

    // Get container elements
    const contentEmpty = document.getElementById("contentEmpty");
    const contentLoading = document.getElementById("contentLoading");
    const listView = document.getElementById("listView");
    const gridView = document.getElementById("gridView");

    // Hide all other views
    if (contentLoading) contentLoading.classList.add("hidden");
    listView.classList.add("hidden");
    gridView.classList.add("hidden");

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
    const gridView = document.getElementById("gridView");

    if (contentLoading) contentLoading.classList.add("hidden");
    listView.classList.add("hidden");
    gridView.classList.add("hidden");

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
    const gridView = document.getElementById("gridView");

    if (contentLoading) contentLoading.classList.add("hidden");
    listView.classList.add("hidden");
    gridView.classList.add("hidden");

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
    checkboxCell.appendChild(checkbox);
    row.appendChild(checkboxCell);

    // Checkbox change handler
    checkbox.addEventListener("change", function (e) {
      e.stopPropagation();

      if (checkbox.checked) {
        // Uncheck all other checkboxes
        document.querySelectorAll('input[type="checkbox"]').forEach((cb) => {
          if (cb !== checkbox) cb.checked = false;
        });

        // Select this file
        const extension = item.name.split(".").pop().toLowerCase();
        selectItem(item.path, item.name, type, extension);

        // Remove selection from all other rows
        document.querySelectorAll(".file-row-selected").forEach((r) => {
          r.classList.remove(
            "file-row-selected",
            "bg-primary-50",
            "dark:bg-primary-900/20"
          );
        });

        // Add selection to this row
        row.classList.add(
          "file-row-selected",
          "bg-primary-50",
          "dark:bg-primary-900/20"
        );
      } else {
        // Deselect
        clearSelection();
        row.classList.remove(
          "file-row-selected",
          "bg-primary-50",
          "dark:bg-primary-900/20"
        );
      }
    });

    // Row click handler (single click)
    row.addEventListener("click", function (e) {
      // If clicking on the checkbox cell, let the checkbox handler deal with it
      if (e.target === checkbox || e.target === checkboxCell) {
        return;
      }

      if (type === "folder") {
        loadFolderContents(item.path);
        clearSelection();
      } else {
        // Toggle checkbox when clicking row
        checkbox.checked = !checkbox.checked;
        checkbox.dispatchEvent(new Event("change"));
      }
    });

    // Row double-click handler (professional behavior)
    row.addEventListener("dblclick", function (e) {
      // If clicking on the checkbox cell, ignore
      if (e.target === checkbox || e.target === checkboxCell) {
        return;
      }

      if (type === "folder") {
        // Double-click folder = open it (same as single click for folders)
        loadFolderContents(item.path);
        clearSelection();
      } else {
        // Double-click file = edit/preview it
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
   * Switch between grid and list view
   */
  function switchView(viewType) {
    const listView = document.getElementById("listView");
    const gridView = document.getElementById("gridView");
    const contentEmpty = document.getElementById("contentEmpty");
    const listBtn = document.getElementById("viewToggleList");
    const gridBtn = document.getElementById("viewToggleGrid");

    if (viewType === "list") {
      // Check if list view has content
      const listBody = document.getElementById("listViewBody");
      if (listBody && listBody.children.length > 0) {
        // Show list view
        listView.classList.remove("hidden");
        gridView.classList.add("hidden");
        if (contentEmpty) contentEmpty.classList.add("hidden");
      }

      // Update button states
      listBtn.className =
        "p-2 text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 rounded transition";
      gridBtn.className =
        "p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition";

      // Save preference
      localStorage.setItem("fileManagerView", "list");
    } else {
      // Check if grid view has content
      const eliteGrid = document.getElementById("eliteGrid");
      if (eliteGrid) {
        // Show grid view
        gridView.classList.remove("hidden");
        listView.classList.add("hidden");
        if (contentEmpty) contentEmpty.classList.add("hidden");
      }

      // Update button states
      gridBtn.className =
        "p-2 text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 rounded transition";
      listBtn.className =
        "p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition";

      // Save preference
      localStorage.setItem("fileManagerView", "grid");
    }
  }

  /**
   * Initialize view from localStorage
   */
  function initializeView() {
    const savedView = localStorage.getItem("fileManagerView") || "list";
    switchView(savedView);
  }

  /**
   * Setup view toggle buttons
   */
  const listToggleBtn = document.getElementById("viewToggleList");
  const gridToggleBtn = document.getElementById("viewToggleGrid");

  if (listToggleBtn) {
    listToggleBtn.addEventListener("click", function () {
      switchView("list");
    });
  }

  if (gridToggleBtn) {
    gridToggleBtn.addEventListener("click", function () {
      switchView("grid");
    });
  }

  // Initialize view on page load
  initializeView();

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
  function highlightCurrentPath(path, expandParents = true) {

    // If we need to expand parents, do it FIRST, then highlight
    if (expandParents) {
      expandParentFolders(path, () => {
        // After expanding, highlight with expandParents=false to avoid infinite loop
        highlightCurrentPath(path, false);
      });
      return; // Exit early, let the callback handle highlighting
    }

    // Remove previous highlights
    document.querySelectorAll('.sidebar-tree-item-active').forEach(el => {
      el.classList.remove('sidebar-tree-item-active', 'bg-blue-100', 'dark:bg-blue-900', 'text-blue-600', 'dark:text-blue-400');
    });

    // Find and highlight the current path
    const sidebarItems = document.querySelectorAll('[data-path]');

    let found = false;
    sidebarItems.forEach(item => {
      const itemPath = item.getAttribute('data-path');

      if (itemPath === path) {
        found = true;

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

    if (!found) {
    }
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

  /**
   * Handle browser back/forward buttons
   */
  window.addEventListener('popstate', function(event) {
    if (event.state && event.state.path) {
      if (event.state.action === 'edit') {
        // Reopen file in editor (don't update URL - we're navigating via browser buttons)
        if (typeof openIntegratedEditor === 'function') {
          openIntegratedEditor(event.state.path, false);
        }
      } else {
        // Check if it's a file or folder
        const isFile = event.state.path.includes('.') && !event.state.path.endsWith('/');

        if (isFile) {
          // Show file info
          const fileName = event.state.path.substring(event.state.path.lastIndexOf('/') + 1);
          const file = { name: fileName, path: event.state.path, type: 'file' };
          const folderPath = event.state.path.substring(0, event.state.path.lastIndexOf('/')) || '/';
          loadFolderContents(folderPath, false);
          setTimeout(() => {
            const urlBackup = window.location.href;
            displaySelectedFile(file);
            window.history.replaceState({ path: event.state.path }, '', urlBackup);
          }, 500);
        } else {
          // Reload folder without updating URL
          loadFolderContents(event.state.path, false);
        }
      }
    }
  });

  /**
   * Initialize from URL on page load
   */
  function initializeFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    const path = urlParams.get('path');
    const action = urlParams.get('action');


    if (path) {
      // Determine if path is a file or folder (check for extension)
      const isFile = path.includes('.') && !path.endsWith('/');

      if (isFile) {
        // It's a FILE - load parent folder WITHOUT updating URL
        const folderPath = path.substring(0, path.lastIndexOf('/')) || '/';

        // Load parent folder but DON'T update URL (file path stays in URL)
        loadFolderContents(folderPath, false, (data) => {
          if (!data || !data.success) {
            // Parent folder doesn't exist - expand tree as far as possible and show error
            highlightCurrentPath(path); // This will expand as far as it can
            displayPathNotFound(path);
            return;
          }

          // Parent folder exists - check if file exists
          const allFiles = data.files || [];
          const fileData = allFiles.find(f => f.path === path);

          if (fileData) {
            // File EXISTS! Show it normally
            highlightCurrentPath(path);

            if (action === 'edit') {
              if (typeof openIntegratedEditor === 'function') {
                openIntegratedEditor(path, false);
              }
            } else {
              displaySelectedFile(fileData);
            }
          } else {
            // File NOT found - but still expand the tree to show the path
            highlightCurrentPath(path); // Expand all parent folders
            displayFileNotFound(path);
          }
        });
      } else {
        // It's a FOLDER - just load it (let sidebar handle opening parent folders)

        loadFolderContents(path, true, (data) => {

          if (data && data.success) {
            // Folder exists
          } else {
            // Folder doesn't exist - expand tree as far as possible and show error
            highlightCurrentPath(path); // This will expand as far as it can
            displayPathNotFound(path);
          }
        });
      }
    }
  }

  // Note: initializeFromUrl is now called after tree loads (see line 487)

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

        // Update URL - this is the single source of truth!
        const url = new URL(window.location);
        url.searchParams.set('path', normalizedPath);
        window.history.pushState({ path: normalizedPath }, '', url);

        // Re-initialize from URL - this handles everything (folders, files, tree expansion, etc.)
        initializeFromUrl();
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
  document.getElementById("gridView").classList.add("hidden");
  document.getElementById("editorView").classList.add("hidden");

  // Switch toolbars - hide normal toolbar (like editor does)
  document.getElementById("fileManagerToolbar").classList.add("hidden");

  // Show image preview
  const imagePreviewView = document.getElementById("imagePreviewView");
  imagePreviewView.classList.remove("hidden");

  // Update image info
  document.getElementById("imagePreviewFileName").textContent = fileData.name;
  document.getElementById("imagePreviewInfo").textContent = `${formatFileSize(fileData.size)}`;

  // Load image
  const img = document.getElementById("imagePreviewImg");
  img.src = `/api/file/image?path=${encodeURIComponent(fileData.path)}`;
  img.alt = fileData.name;
  img.style.transform = "scale(1)";

  // Update image info with dimensions after loading
  img.onload = function() {
    const info = document.getElementById("imagePreviewInfo");
    info.textContent = `${formatFileSize(fileData.size)} • ${img.naturalWidth} × ${img.naturalHeight} px`;
  };
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
  // Clear URL parameters
  const url = new URL(window.location);
  url.searchParams.delete('action');
  const currentPath = currentImagePath ? currentImagePath.substring(0, currentImagePath.lastIndexOf('/')) || '/' : '/';
  url.searchParams.set('path', currentPath);
  window.history.pushState({ path: currentPath }, '', url);

  // Hide image preview
  document.getElementById("imagePreviewView").classList.add("hidden");

  // Switch toolbars back - show normal toolbar (like editor does)
  document.getElementById("fileManagerToolbar").classList.remove("hidden");

  // Show appropriate view
  const listView = document.getElementById("listView");
  const gridView = document.getElementById("gridView");

  if (listView && listView.querySelector("tbody tr")) {
    listView.classList.remove("hidden");
  } else if (gridView && gridView.querySelector(".group")) {
    gridView.classList.remove("hidden");
  } else {
    document.getElementById("contentEmpty").classList.remove("hidden");
  }

  currentImagePath = null;
}
