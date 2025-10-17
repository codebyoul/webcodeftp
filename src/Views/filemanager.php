<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language ?? 'en', ENT_QUOTES, 'UTF-8') ?>" class="<?= htmlspecialchars($theme ?? 'dark', ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= htmlspecialchars($app_name ?? 'WebFTP', ENT_QUOTES, 'UTF-8') ?> - File Manager</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" type="image/x-icon" href="/favicon.ico">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-screen overflow-hidden bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-200">

    <!-- Main Container -->
    <div class="flex flex-col h-screen">

        <!-- Top Toolbar -->
        <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="flex items-center justify-between px-4 py-3">

                <!-- Left Section: Logo & Breadcrumb -->
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-folder text-2xl text-primary-600 dark:text-primary-400"></i>
                        <h1 class="text-xl font-bold text-gray-900 dark:text-white hidden sm:block"><?= htmlspecialchars($app_name ?? 'WebFTP', ENT_QUOTES, 'UTF-8') ?></h1>
                    </div>

                    <!-- Breadcrumb -->
                    <nav class="flex items-center space-x-1 text-sm">
                        <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition">
                            <i class="fas fa-house"></i>
                        </a>
                        <span class="text-gray-400 dark:text-gray-600">/</span>
                        <span class="text-gray-700 dark:text-gray-300 font-medium">Home</span>
                    </nav>
                </div>

                <!-- Right Section: Actions & Profile -->
                <div class="flex items-center space-x-3">

                    <!-- Action Buttons -->
                    <div class="hidden md:flex items-center space-x-2">
                        <button class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition" title="Upload">
                            <i class="fas fa-upload"></i>
                        </button>
                        <button class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition" title="New Folder">
                            <i class="fas fa-folder-plus"></i>
                        </button>
                    </div>

                    <!-- Profile Dropdown -->
                    <div class="relative">
                        <button id="profileButton" class="flex items-center space-x-2 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                            <div class="w-8 h-8 bg-primary-600 dark:bg-primary-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                <?= strtoupper(substr($ftp_username ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="hidden sm:block text-left">
                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($ftp_username ?? 'User', ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?= htmlspecialchars($ftp_host ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <i class="fas fa-chevron-down text-gray-500"></i>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-72 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-2 z-50">

                            <!-- User Info -->
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($ftp_username ?? 'User', ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Connected to: <?= htmlspecialchars($ftp_host ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                            </div>

                            <!-- Theme Toggle -->
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Theme</div>
                                <div class="flex items-center space-x-2">
                                    <button onclick="switchTheme('light')" class="flex-1 px-3 py-2 text-sm rounded-md transition <?= ($theme ?? 'dark') === 'light' ? 'bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                                        <i class="fas fa-sun mr-1"></i>
                                        Light
                                    </button>
                                    <button onclick="switchTheme('dark')" class="flex-1 px-3 py-2 text-sm rounded-md transition <?= ($theme ?? 'dark') === 'dark' ? 'bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                                        <i class="fas fa-moon mr-1"></i>
                                        Dark
                                    </button>
                                </div>
                            </div>

                            <!-- Language Selection -->
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Language</div>
                                <select onchange="switchLanguage(this.value)" class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-md text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                    <option value="en" <?= ($language ?? 'en') === 'en' ? 'selected' : '' ?>>🇬🇧 English</option>
                                    <option value="fr" <?= ($language ?? 'en') === 'fr' ? 'selected' : '' ?>>🇫🇷 Français</option>
                                    <option value="es" <?= ($language ?? 'en') === 'es' ? 'selected' : '' ?>>🇪🇸 Español</option>
                                    <option value="de" <?= ($language ?? 'en') === 'de' ? 'selected' : '' ?>>🇩🇪 Deutsch</option>
                                    <option value="it" <?= ($language ?? 'en') === 'it' ? 'selected' : '' ?>>🇮🇹 Italiano</option>
                                    <option value="pt" <?= ($language ?? 'en') === 'pt' ? 'selected' : '' ?>>🇵🇹 Português</option>
                                    <option value="ar" <?= ($language ?? 'en') === 'ar' ? 'selected' : '' ?>>🇸🇦 العربية</option>
                                </select>
                            </div>

                            <!-- Logout -->
                            <div class="px-2 py-2">
                                <a href="/logout" class="flex items-center w-full px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition">
                                    <i class="fas fa-right-from-bracket mr-2"></i>
                                    Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <div class="flex flex-1 overflow-hidden">

            <!-- Left Sidebar: Folder Tree -->
            <aside id="sidebar" class="bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col" style="width: 260px; min-width: 200px; max-width: 600px;">
                <!-- Explorer Header -->
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Explorer</h3>
                </div>

                <!-- Folder Tree -->
                <div class="flex-1 overflow-y-auto p-3">
                    <!-- Loading State -->
                    <div id="treeLoading" class="flex items-center justify-center py-10">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin text-3xl text-gray-400 dark:text-gray-500 mb-3"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Loading folders...</p>
                        </div>
                    </div>

                    <!-- Tree Container -->
                    <div id="folderTree" class="space-y-1 hidden"></div>

                    <!-- Error State -->
                    <div id="treeError" class="hidden px-4 py-6 text-center">
                        <i class="fas fa-exclamation-triangle text-yellow-500 text-2xl mb-3"></i>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Unable to load folders</p>
                        <button id="retryLoadTree" class="mt-3 text-sm text-primary-600 dark:text-primary-400 hover:underline font-medium">Retry</button>
                    </div>
                </div>

                <!-- Status Bar -->
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">
                        <span id="selectedCount">0</span> items selected
                    </p>
                </div>
            </aside>

            <!-- Resize Handle -->
            <div id="resizeHandle" class="w-1 bg-gray-200 dark:bg-gray-700 hover:bg-primary-500 dark:hover:bg-primary-500 cursor-col-resize transition-colors flex-shrink-0" title="Drag to resize"></div>

            <!-- Right Content Area -->
            <main class="flex-1 flex flex-col overflow-hidden">

                <!-- Action Toolbar -->
                <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                    <div class="flex items-center justify-between gap-4">

                        <!-- Left: Action Icons -->
                        <div class="flex items-center gap-1">
                            <button class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="Upload File">
                                <i class="fas fa-upload"></i>
                            </button>
                            <button class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="Download">
                                <i class="fas fa-download"></i>
                            </button>
                            <button class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="New File">
                                <i class="fas fa-file-circle-plus"></i>
                            </button>
                            <button class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="New Folder">
                                <i class="fas fa-folder-plus"></i>
                            </button>
                            <button class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                            <button class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="Rename">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="Extract/Unzip">
                                <i class="fas fa-file-zipper"></i>
                            </button>
                            <button class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="Refresh">
                                <i class="fas fa-arrows-rotate"></i>
                            </button>

                            <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>

                            <!-- View Toggle -->
                            <button id="viewToggleList" class="p-2 text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 rounded transition" title="List View">
                                <i class="fas fa-list"></i>
                            </button>
                            <button id="viewToggleGrid" class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="Grid View">
                                <i class="fas fa-grip"></i>
                            </button>
                        </div>

                        <!-- Right: Path Navigation -->
                        <div class="flex-1 max-w-2xl">
                            <div class="relative">
                                <i class="fas fa-folder-open absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500"></i>
                                <input
                                    type="text"
                                    value="/"
                                    id="pathInput"
                                    class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    placeholder="Enter path..."
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- File Content Area -->
                <div class="flex-1 overflow-auto bg-gray-50 dark:bg-gray-900">
                    <!-- Loading State -->
                    <div id="contentLoading" class="hidden flex items-center justify-center h-full">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin text-4xl text-primary-500 mb-4"></i>
                            <p class="text-gray-600 dark:text-gray-400">Loading...</p>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div id="contentEmpty" class="flex items-center justify-center h-full">
                        <div class="text-center px-6">
                            <i class="fas fa-folder-open text-gray-300 dark:text-gray-600 text-6xl mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">Select a Folder</h3>
                            <p class="text-gray-500 dark:text-gray-400">Click on a folder in the sidebar to view its contents</p>
                        </div>
                    </div>

                <!-- List View (Table) -->
                <div id="listView" class="h-full hidden overflow-auto">
                    <table class="w-full text-sm">
                        <colgroup>
                            <col style="width: 3%;">
                            <col style="width: 45%;">
                            <col style="width: 13%;">
                            <col style="width: 25%;">
                            <col style="width: 14%;">
                        </colgroup>
                        <thead class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    <i class="fas fa-check-square text-base"></i>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-primary-500 dark:hover:text-primary-400 transition" onclick="sortListView('name')">
                                    <div class="flex items-center gap-2">
                                        Name
                                        <i class="fas fa-sort text-xs opacity-50"></i>
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-primary-500 dark:hover:text-primary-400 transition" onclick="sortListView('size')">
                                    <div class="flex items-center justify-end gap-2">
                                        Size
                                        <i class="fas fa-sort text-xs opacity-50"></i>
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-primary-500 dark:hover:text-primary-400 transition" onclick="sortListView('modified')">
                                    <div class="flex items-center gap-2">
                                        Modified
                                        <i class="fas fa-sort text-xs opacity-50"></i>
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-primary-500 dark:hover:text-primary-400 transition" onclick="sortListView('permissions')">
                                    <div class="flex items-center justify-center gap-2">
                                        Permissions
                                        <i class="fas fa-sort text-xs opacity-50"></i>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="listViewBody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- Dynamic content will be inserted here -->
                        </tbody>
                    </table>
                </div>

                <!-- Grid View -->
                <div id="gridView" class="hidden p-6">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 2xl:grid-cols-8 gap-4">
                        <!-- Sample Folder -->
                        <div class="group p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition cursor-pointer">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-folder text-6xl text-yellow-500 mb-2"></i>
                                <span class="text-sm font-medium text-gray-900 dark:text-white text-center truncate w-full">composer</span>
                            </div>
                        </div>
                        <div class="group p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition cursor-pointer">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-folder text-6xl text-yellow-500 mb-2"></i>
                                <span class="text-sm font-medium text-gray-900 dark:text-white text-center truncate w-full">.ssh</span>
                            </div>
                        </div>
                        <div class="group p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition cursor-pointer">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-folder text-6xl text-yellow-500 mb-2"></i>
                                <span class="text-sm font-medium text-gray-900 dark:text-white text-center truncate w-full">backup</span>
                            </div>
                        </div>
                        <div class="group p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition cursor-pointer">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-folder text-6xl text-yellow-500 mb-2"></i>
                                <span class="text-sm font-medium text-gray-900 dark:text-white text-center truncate w-full">web</span>
                            </div>
                        </div>
                        <div class="group p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition cursor-pointer">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-file-pdf text-6xl text-red-500 mb-2"></i>
                                <span class="text-sm font-medium text-gray-900 dark:text-white text-center truncate w-full">document.pdf</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        </div>
    </div>

    <!-- Vanilla JavaScript for Interactivity -->
    <script>
        // File Icon Configuration (from config.php)
        const FILE_ICON_CONFIG = <?= json_encode($file_icons) ?>;

        document.addEventListener('DOMContentLoaded', function() {
            // Profile Dropdown Toggle
            const profileButton = document.getElementById('profileButton');
            const profileDropdown = document.getElementById('profileDropdown');

            profileButton.addEventListener('click', function(e) {
                e.stopPropagation();
                profileDropdown.classList.toggle('hidden');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!profileDropdown.contains(e.target) && !profileButton.contains(e.target)) {
                    profileDropdown.classList.add('hidden');
                }
            });

            // Dynamic Folder Tree Management
            const folderTree = document.getElementById('folderTree');
            const treeLoading = document.getElementById('treeLoading');
            const treeError = document.getElementById('treeError');
            const retryLoadTree = document.getElementById('retryLoadTree');

            // Track expanded folders
            const expandedFolders = new Set();

            /**
             * Load folder tree from API
             */
            function loadFolderTree(path = '/') {
                // Show loading
                treeLoading.classList.remove('hidden');
                folderTree.classList.add('hidden');
                treeError.classList.add('hidden');

                fetch('/api/folder-tree?path=' + encodeURIComponent(path))
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Hide loading
                            treeLoading.classList.add('hidden');
                            folderTree.classList.remove('hidden');

                            // Render tree
                            if (path === '/') {
                                // Initial load - render root
                                renderTree(data.tree);
                            }
                        } else {
                            throw new Error(data.message || 'Failed to load tree');
                        }
                    })
                    .catch(error => {
                        console.error('Error loading folder tree:', error);
                        treeLoading.classList.add('hidden');
                        treeError.classList.remove('hidden');
                    });
            }

            /**
             * Render folder tree
             */
            function renderTree(tree, parentElement = folderTree, level = 0) {
                tree.forEach(item => {
                    const folderElement = createFolderElement(item, level);
                    parentElement.appendChild(folderElement);
                });
            }

            /**
             * Create folder or file element
             */
            function createFolderElement(item, level) {
                const div = document.createElement('div');
                div.dataset.path = item.path;
                div.dataset.level = level;
                div.dataset.type = item.type;

                // Create button
                const button = document.createElement('button');
                button.className = 'flex items-center w-full px-2 py-2 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition';
                button.style.paddingLeft = (level * 12 + 8) + 'px';

                if (item.type === 'directory') {
                    // Arrow icon for folders
                    const arrow = document.createElement('i');
                    arrow.className = 'fas fa-chevron-right text-xs text-gray-400 dark:text-gray-500 mr-2 transition-transform';
                    button.appendChild(arrow);

                    // Folder icon
                    const folderIcon = document.createElement('i');
                    folderIcon.className = level === 0 ? 'fas fa-folder text-base text-primary-500 mr-2' : 'fas fa-folder text-base text-yellow-500 mr-2';
                    button.appendChild(folderIcon);

                    // Folder name
                    const span = document.createElement('span');
                    span.className = 'text-sm font-medium';
                    span.textContent = item.name;
                    button.appendChild(span);

                    // Container for children
                    const childrenContainer = document.createElement('div');
                    childrenContainer.className = 'hidden';
                    childrenContainer.dataset.childrenFor = item.path;

                    // Click handler for folders
                    button.addEventListener('click', function(e) {
                        // Load folder contents in main area
                        loadFolderContents(item.path);

                        // Toggle folder expand/collapse
                        toggleFolder(item.path, arrow, childrenContainer, level);

                        e.stopPropagation();
                    });

                    div.appendChild(button);
                    div.appendChild(childrenContainer);
                } else {
                    // File - no arrow, just icon and name

                    // Spacer to align with folders (same width as arrow)
                    const spacer = document.createElement('span');
                    spacer.className = 'inline-block w-4 mr-2';
                    button.appendChild(spacer);

                    // File icon based on extension
                    const fileIcon = document.createElement('i');
                    fileIcon.className = getFileIcon(item.name).replace(/text-\w+/g, 'text-base') + ' mr-2';
                    button.appendChild(fileIcon);

                    // File name
                    const span = document.createElement('span');
                    span.textContent = item.name;
                    span.className = 'text-sm';
                    button.appendChild(span);

                    // Click handler for files - show file info in main content
                    button.addEventListener('click', function(e) {
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
                const ext = filename.split('.').pop().toLowerCase();

                // Search through all configured file icon categories
                for (const category in FILE_ICON_CONFIG) {
                    const config = FILE_ICON_CONFIG[category];

                    // Check if this extension matches this category
                    if (config.extensions && config.extensions.includes(ext)) {
                        return config.icon;
                    }
                }

                // Return default icon if no match found
                return FILE_ICON_CONFIG.default?.icon || 'fas fa-file text-gray-400 dark:text-gray-500';
            }

            /**
             * Toggle folder expand/collapse
             */
            function toggleFolder(path, arrow, childrenContainer, level) {
                const isExpanded = expandedFolders.has(path);

                if (isExpanded) {
                    // Collapse
                    arrow.classList.remove('rotate-90');
                    childrenContainer.classList.add('hidden');
                    expandedFolders.delete(path);
                } else {
                    // Expand
                    arrow.classList.add('rotate-90');
                    childrenContainer.classList.remove('hidden');
                    expandedFolders.add(path);

                    // Load children if not loaded yet
                    if (childrenContainer.children.length === 0) {
                        loadFolderChildren(path, childrenContainer, level + 1);
                    }
                }
            }

            /**
             * Load children for a folder
             */
            function loadFolderChildren(path, container, level) {
                // Show loading indicator
                const loading = document.createElement('div');
                loading.className = 'flex items-center px-3 py-2 text-sm text-gray-500 dark:text-gray-400';
                loading.style.paddingLeft = (level * 16 + 12) + 'px';
                loading.innerHTML = '<i class="fas fa-spinner fa-spin mr-2 text-base"></i>Loading...';
                container.appendChild(loading);

                fetch('/api/folder-tree?path=' + encodeURIComponent(path))
                    .then(response => response.json())
                    .then(data => {
                        // Remove loading indicator
                        container.innerHTML = '';

                        if (data.success && data.tree.length > 0) {
                            renderTree(data.tree, container, level);
                        } else if (data.tree.length === 0) {
                            // Empty folder
                            const empty = document.createElement('div');
                            empty.className = 'px-3 py-2 text-sm text-gray-400 dark:text-gray-500 italic';
                            empty.style.paddingLeft = (level * 16 + 12) + 'px';
                            empty.textContent = 'Empty';
                            container.appendChild(empty);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading folder children:', error);
                        container.innerHTML = '';
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'px-3 py-2 text-sm text-red-500';
                        errorMsg.style.paddingLeft = (level * 16 + 12) + 'px';
                        errorMsg.textContent = 'Error loading';
                        container.appendChild(errorMsg);
                    });
            }

            // Retry button
            retryLoadTree.addEventListener('click', function() {
                loadFolderTree();
            });

            // Initial load
            loadFolderTree();

            // View Toggle (List/Grid)
            const viewToggleList = document.getElementById('viewToggleList');
            const viewToggleGrid = document.getElementById('viewToggleGrid');
            const listView = document.getElementById('listView');
            const gridView = document.getElementById('gridView');

            viewToggleList.addEventListener('click', function() {
                // Show list view
                listView.classList.remove('hidden');
                gridView.classList.add('hidden');

                // Update button states
                viewToggleList.className = 'p-2 text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 rounded transition';
                viewToggleGrid.className = 'p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition';
            });

            viewToggleGrid.addEventListener('click', function() {
                // Show grid view
                listView.classList.add('hidden');
                gridView.classList.remove('hidden');

                // Update button states
                viewToggleGrid.className = 'p-2 text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 rounded transition';
                viewToggleList.className = 'p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition';
            });

            // Sidebar Resize Functionality
            const sidebar = document.getElementById('sidebar');
            const resizeHandle = document.getElementById('resizeHandle');
            let isResizing = false;
            let startX = 0;
            let startWidth = 0;

            // Apply saved width from localStorage (default is 280px now)
            const savedWidth = localStorage.getItem('sidebarWidth');
            if (savedWidth) {
                sidebar.style.width = savedWidth + 'px';
            }

            // Mouse down on resize handle
            resizeHandle.addEventListener('mousedown', function(e) {
                isResizing = true;
                startX = e.clientX;
                startWidth = sidebar.offsetWidth;

                // Prevent text selection during resize
                document.body.style.userSelect = 'none';
                document.body.style.cursor = 'col-resize';

                e.preventDefault();
            });

            // Mouse move - resize sidebar
            document.addEventListener('mousemove', function(e) {
                if (!isResizing) return;

                const deltaX = e.clientX - startX;
                const newWidth = startWidth + deltaX;

                // Get min and max width from inline styles
                const minWidth = parseInt(getComputedStyle(sidebar).minWidth);
                const maxWidth = parseInt(getComputedStyle(sidebar).maxWidth);

                // Apply new width within constraints
                if (newWidth >= minWidth && newWidth <= maxWidth) {
                    sidebar.style.width = newWidth + 'px';
                }
            });

            // Mouse up - stop resizing
            document.addEventListener('mouseup', function() {
                if (isResizing) {
                    isResizing = false;
                    document.body.style.userSelect = '';
                    document.body.style.cursor = '';

                    // Save width to localStorage
                    localStorage.setItem('sidebarWidth', sidebar.offsetWidth);
                }
            });

            /**
             * Load folder contents in main content area
             */
            function loadFolderContents(path) {
                // Get container elements
                const contentEmpty = document.getElementById('contentEmpty');
                const contentLoading = document.getElementById('contentLoading');
                const listView = document.getElementById('listView');
                const gridView = document.getElementById('gridView');

                // Show loading state
                if (contentEmpty) contentEmpty.classList.add('hidden');
                if (contentLoading) contentLoading.classList.remove('hidden');
                listView.classList.add('hidden');
                gridView.classList.add('hidden');

                // Update path input
                const pathInput = document.getElementById('pathInput');
                if (pathInput) {
                    pathInput.value = path;
                }

                // Fetch folder contents
                fetch('/api/folder-contents?path=' + encodeURIComponent(path))
                    .then(response => response.json())
                    .then(data => {
                        // Hide loading
                        if (contentLoading) contentLoading.classList.add('hidden');

                        if (data.success) {
                            // Render contents in BOTH views
                            renderEliteGrid(data.folders, data.files, path);
                            renderListView(data.folders, data.files, path);

                            // Show the appropriate view based on user preference
                            const currentView = localStorage.getItem('fileManagerView') || 'list';
                            if (currentView === 'list') {
                                listView.classList.remove('hidden');
                                gridView.classList.add('hidden');
                            } else {
                                gridView.classList.remove('hidden');
                                listView.classList.add('hidden');
                            }
                        } else {
                            // Show error
                            if (contentEmpty) {
                                contentEmpty.classList.remove('hidden');
                                contentEmpty.innerHTML = '<div class="text-center px-6"><i class="fas fa-exclamation-triangle text-red-500 text-6xl mb-4"></i><h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">Error Loading Folder</h3><p class="text-gray-500 dark:text-gray-400">' + (data.message || 'Unable to load folder contents') + '</p></div>';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error loading folder contents:', error);
                        if (contentLoading) contentLoading.classList.add('hidden');
                        if (contentEmpty) {
                            contentEmpty.classList.remove('hidden');
                            contentEmpty.innerHTML = '<div class="text-center px-6"><i class="fas fa-exclamation-triangle text-red-500 text-6xl mb-4"></i><h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">Connection Error</h3><p class="text-gray-500 dark:text-gray-400">Unable to connect to server</p></div>';
                        }
                    });
            }

            /**
             * Render folder/file contents in elite grid design
             */
            function renderEliteGrid(folders, files, currentPath) {
                const gridView = document.getElementById('gridView');

                // Create elite grid container
                gridView.innerHTML = '<div class="p-6"><div id="eliteGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-5"></div></div>';

                const eliteGrid = document.getElementById('eliteGrid');

                // Render folders first
                folders.forEach(folder => {
                    const card = createEliteCard(folder, 'folder', currentPath);
                    eliteGrid.appendChild(card);
                });

                // Render files
                files.forEach(file => {
                    const card = createEliteCard(file, 'file', currentPath);
                    eliteGrid.appendChild(card);
                });

                // If empty
                if (folders.length === 0 && files.length === 0) {
                    eliteGrid.innerHTML = '<div class="col-span-full text-center py-16"><i class="fas fa-folder-open text-gray-300 dark:text-gray-600 text-6xl mb-4"></i><p class="text-gray-500 dark:text-gray-400">This folder is empty</p></div>';
                }
            }

            /**
             * Create elite card for folder or file
             */
            function createEliteCard(item, type, currentPath) {
                const card = document.createElement('div');
                card.className = 'group relative bg-white dark:bg-gray-800 rounded-xl p-5 border-2 border-gray-200 dark:border-gray-700 hover:border-primary-500 dark:hover:border-primary-500 hover:shadow-2xl hover:scale-105 transform transition-all duration-300 cursor-pointer';

                // Add click handler
                card.addEventListener('click', function() {
                    if (type === 'folder') {
                        loadFolderContents(item.path);
                    } else {
                        // Show file details
                        displaySelectedFile(item);
                    }
                });

                // Icon container
                const iconContainer = document.createElement('div');
                iconContainer.className = 'flex flex-col items-center';

                // Icon
                const icon = document.createElement('i');
                if (type === 'folder') {
                    icon.className = 'fas fa-folder text-5xl text-yellow-500 dark:text-yellow-400 mb-4 group-hover:scale-110 transition-transform duration-300';
                } else {
                    icon.className = getFileIcon(item.name).replace(/text-\w+/g, 'text-5xl') + ' mb-4 group-hover:scale-110 transition-transform duration-300';
                }
                iconContainer.appendChild(icon);

                // Name
                const name = document.createElement('div');
                name.className = 'text-center w-full';
                const nameSpan = document.createElement('span');
                nameSpan.className = 'text-sm font-medium text-gray-900 dark:text-white block truncate px-2';
                nameSpan.textContent = item.name;
                nameSpan.title = item.name; // Tooltip for full name
                name.appendChild(nameSpan);

                // Size (for files only)
                if (type === 'file' && item.size) {
                    const size = document.createElement('span');
                    size.className = 'text-xs text-gray-500 dark:text-gray-400 mt-1 block';
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
                if (bytes === 0) return '0 B';
                const k = 1024;
                const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            /**
             * Convert Unix permissions to octal format
             * Example: "drwxr-xr-x" -> "755"
             * Example: "-rw-r--r--" -> "644"
             */
            function convertPermissionsToOctal(permissions) {
                if (!permissions || permissions.length < 10) {
                    return '-';
                }

                // Remove the first character (file type: d, -, l, etc.)
                const perms = permissions.substring(1);

                // Calculate octal for each triplet (owner, group, others)
                let octal = '';
                for (let i = 0; i < 9; i += 3) {
                    let value = 0;
                    if (perms[i] === 'r') value += 4;
                    if (perms[i + 1] === 'w') value += 2;
                    if (perms[i + 2] === 'x' || perms[i + 2] === 's' || perms[i + 2] === 't') value += 1;
                    octal += value;
                }

                return octal;
            }

            /**
             * Display selected file in main content area
             */
            function displaySelectedFile(file) {
                // Get container elements
                const contentEmpty = document.getElementById('contentEmpty');
                const contentLoading = document.getElementById('contentLoading');
                const listView = document.getElementById('listView');
                const gridView = document.getElementById('gridView');

                // Hide all other views
                if (contentLoading) contentLoading.classList.add('hidden');
                listView.classList.add('hidden');
                gridView.classList.add('hidden');

                // Update path input with file path
                const pathInput = document.getElementById('pathInput');
                if (pathInput) {
                    pathInput.value = file.path;
                }

                // Get file icon without size classes and make it huge
                const iconClasses = getFileIcon(file.name).replace(/text-\w+/g, '').trim();

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
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3 break-all">${escapeHtml(file.name)}</h2>

                            <!-- File Details -->
                            <div class="flex items-center justify-center gap-6 text-gray-600 dark:text-gray-400 mb-8">
                                ${file.size ? `
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-file-lines text-base"></i>
                                        <span class="text-base font-medium">${formatFileSize(file.size)}</span>
                                    </div>
                                ` : ''}
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-shield-halved text-base"></i>
                                    <span class="text-base font-mono">${convertPermissionsToOctal(file.permissions)}</span>
                                </div>
                                ${file.modified ? `
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-clock text-base"></i>
                                        <span class="text-base">${file.modified}</span>
                                    </div>
                                ` : ''}
                            </div>

                            <!-- Preview Button (Small & Elegant) -->
                            <div class="flex items-center justify-center">
                                <button onclick="previewFile('${escapeHtml(file.path)}')" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 bg-primary-50 dark:bg-primary-900/20 hover:bg-primary-100 dark:hover:bg-primary-900/30 rounded-lg border border-primary-200 dark:border-primary-800 transition-all duration-200">
                                    <i class="fas fa-eye text-sm"></i>
                                    <span>Preview File</span>
                                </button>
                            </div>

                            <!-- File Path -->
                            <div class="mt-8 p-4 bg-gray-100 dark:bg-gray-900 rounded-lg">
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-mono break-all">
                                    <i class="fas fa-folder-tree mr-2"></i>${escapeHtml(file.path)}
                                </p>
                            </div>
                        </div>
                    </div>
                `;

                // Show the file display
                if (contentEmpty) {
                    contentEmpty.innerHTML = fileDisplay;
                    contentEmpty.classList.remove('hidden');
                }
            }

            /**
             * Render folder/file contents in list view (table format)
             */
            function renderListView(folders, files, currentPath) {
                const listViewBody = document.getElementById('listViewBody');

                // Clear existing content
                listViewBody.innerHTML = '';

                // Render folders first
                folders.forEach(folder => {
                    const row = createListRow(folder, 'folder', currentPath);
                    listViewBody.appendChild(row);
                });

                // Render files
                files.forEach(file => {
                    const row = createListRow(file, 'file', currentPath);
                    listViewBody.appendChild(row);
                });

                // If empty
                if (folders.length === 0 && files.length === 0) {
                    const emptyRow = document.createElement('tr');
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
                    sortColumn: 'name',
                    sortDirection: 'asc'
                };
            }

            /**
             * Create a list row for folder or file
             */
            function createListRow(item, type, currentPath) {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition';

                // Add click handler
                row.addEventListener('click', function() {
                    if (type === 'folder') {
                        loadFolderContents(item.path);
                    } else {
                        displaySelectedFile(item);
                    }
                });

                // Checkbox cell
                const checkboxCell = document.createElement('td');
                checkboxCell.className = 'px-4 py-3';
                checkboxCell.innerHTML = '<input type="checkbox" class="rounded border-gray-300 dark:border-gray-600" onclick="event.stopPropagation()">';
                row.appendChild(checkboxCell);

                // Name cell
                const nameCell = document.createElement('td');
                nameCell.className = 'px-4 py-3';
                const nameDiv = document.createElement('div');
                nameDiv.className = 'flex items-center';

                const icon = document.createElement('i');
                if (type === 'folder') {
                    icon.className = 'fas fa-folder text-base text-yellow-500 dark:text-yellow-400 mr-3';
                } else {
                    icon.className = getFileIcon(item.name) + ' mr-3';
                }
                nameDiv.appendChild(icon);

                const nameSpan = document.createElement('span');
                nameSpan.className = 'text-gray-900 dark:text-white text-sm' + (type === 'folder' ? ' font-medium' : '');
                nameSpan.textContent = item.name;
                nameDiv.appendChild(nameSpan);

                nameCell.appendChild(nameDiv);
                row.appendChild(nameCell);

                // Size cell
                const sizeCell = document.createElement('td');
                sizeCell.className = 'px-4 py-3 text-gray-600 dark:text-gray-400 text-sm text-right';
                sizeCell.textContent = type === 'folder' ? '-' : (item.size ? formatFileSize(item.size) : '-');
                sizeCell.dataset.size = item.size || 0;
                row.appendChild(sizeCell);

                // Modified date cell
                const modifiedCell = document.createElement('td');
                modifiedCell.className = 'px-4 py-3 text-gray-600 dark:text-gray-400 text-sm';
                modifiedCell.textContent = item.modified || '-';
                modifiedCell.dataset.modified = item.modified || '';
                row.appendChild(modifiedCell);

                // Permissions cell (octal format)
                const permCell = document.createElement('td');
                permCell.className = 'px-4 py-3 text-gray-600 dark:text-gray-400 text-sm font-mono text-center';
                const octalPerm = convertPermissionsToOctal(item.permissions);
                permCell.textContent = octalPerm;
                permCell.dataset.permissions = octalPerm;
                row.appendChild(permCell);

                return row;
            }

            /**
             * Sort list view by column
             */
            function sortListView(column) {
                if (!window.currentListData) return;

                const data = window.currentListData;

                // Toggle sort direction if clicking the same column
                if (data.sortColumn === column) {
                    data.sortDirection = data.sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    data.sortColumn = column;
                    data.sortDirection = 'asc';
                }

                // Combine folders and files for sorting
                let allItems = [
                    ...data.folders.map(f => ({...f, type: 'folder'})),
                    ...data.files.map(f => ({...f, type: 'file'}))
                ];

                // Sort based on column
                allItems.sort((a, b) => {
                    let aVal, bVal;

                    switch(column) {
                        case 'name':
                            aVal = a.name.toLowerCase();
                            bVal = b.name.toLowerCase();
                            break;
                        case 'size':
                            aVal = a.size || 0;
                            bVal = b.size || 0;
                            break;
                        case 'modified':
                            aVal = a.modified || '';
                            bVal = b.modified || '';
                            break;
                        case 'permissions':
                            aVal = convertPermissionsToOctal(a.permissions);
                            bVal = convertPermissionsToOctal(b.permissions);
                            break;
                        default:
                            return 0;
                    }

                    if (aVal < bVal) return data.sortDirection === 'asc' ? -1 : 1;
                    if (aVal > bVal) return data.sortDirection === 'asc' ? 1 : -1;
                    return 0;
                });

                // Re-render the list
                const listViewBody = document.getElementById('listViewBody');
                listViewBody.innerHTML = '';

                allItems.forEach(item => {
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
                const headers = document.querySelectorAll('#listView th[onclick]');
                headers.forEach(header => {
                    const icon = header.querySelector('i');
                    const columnName = header.getAttribute('onclick').match(/sortListView\('(\w+)'\)/)[1];

                    if (columnName === activeColumn) {
                        icon.className = direction === 'asc'
                            ? 'fas fa-sort-up text-xs text-primary-500 dark:text-primary-400'
                            : 'fas fa-sort-down text-xs text-primary-500 dark:text-primary-400';
                    } else {
                        icon.className = 'fas fa-sort text-xs opacity-50';
                    }
                });
            }

            /**
             * Switch between grid and list view
             */
            function switchView(viewType) {
                const listView = document.getElementById('listView');
                const gridView = document.getElementById('gridView');
                const contentEmpty = document.getElementById('contentEmpty');
                const listBtn = document.getElementById('viewToggleList');
                const gridBtn = document.getElementById('viewToggleGrid');

                if (viewType === 'list') {
                    // Check if list view has content
                    const listBody = document.getElementById('listViewBody');
                    if (listBody && listBody.children.length > 0) {
                        // Show list view
                        listView.classList.remove('hidden');
                        gridView.classList.add('hidden');
                        if (contentEmpty) contentEmpty.classList.add('hidden');
                    }

                    // Update button states
                    listBtn.className = 'p-2 text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 rounded transition';
                    gridBtn.className = 'p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition';

                    // Save preference
                    localStorage.setItem('fileManagerView', 'list');
                } else {
                    // Check if grid view has content
                    const eliteGrid = document.getElementById('eliteGrid');
                    if (eliteGrid) {
                        // Show grid view
                        gridView.classList.remove('hidden');
                        listView.classList.add('hidden');
                        if (contentEmpty) contentEmpty.classList.add('hidden');
                    }

                    // Update button states
                    gridBtn.className = 'p-2 text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 rounded transition';
                    listBtn.className = 'p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition';

                    // Save preference
                    localStorage.setItem('fileManagerView', 'grid');
                }
            }

            /**
             * Initialize view from localStorage
             */
            function initializeView() {
                const savedView = localStorage.getItem('fileManagerView') || 'list';
                switchView(savedView);
            }

            /**
             * Setup view toggle buttons
             */
            const listToggleBtn = document.getElementById('viewToggleList');
            const gridToggleBtn = document.getElementById('viewToggleGrid');

            if (listToggleBtn) {
                listToggleBtn.addEventListener('click', function() {
                    switchView('list');
                });
            }

            if (gridToggleBtn) {
                gridToggleBtn.addEventListener('click', function() {
                    switchView('grid');
                });
            }

            // Initialize view on page load
            initializeView();

            /**
             * Escape HTML to prevent XSS
             */
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            /**
             * Preview file (placeholder for future implementation)
             */
            function previewFile(path) {
                console.log('Preview file:', path);
                // TODO: Implement preview functionality
                alert('Preview functionality will be implemented soon!');
            }
        });
    </script>

    <!-- Theme & Language Persistence (Best Practice: localStorage) -->
    <script>
        // Apply theme IMMEDIATELY (before page renders - prevents flash)
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();

        // Theme Switcher
        function switchTheme(theme) {
            // Update DOM
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }

            // Save to localStorage (persists across sessions)
            localStorage.setItem('theme', theme);

            // Sync to server session (for server-side rendering)
            fetch('/api/theme', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'theme=' + theme
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      // Update button states
                      updateThemeButtons(theme);
                  }
              });
        }

        // Language Switcher
        function switchLanguage(language) {
            const cookieName = '<?= $language_cookie_name ?>';
            const cookieLifetime = <?= $language_cookie_lifetime ?>;

            // Save to cookie (works on both server and client)
            document.cookie = cookieName + '=' + language + '; path=/; max-age=' + cookieLifetime + '; SameSite=Strict';

            // Sync to server session
            fetch('/api/language', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'language=' + language
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      // Reload page to apply language changes
                      location.reload();
                  }
              });
        }

        // Update theme button states
        function updateThemeButtons(theme) {
            const lightBtn = document.querySelector('[onclick*="switchTheme(\'light\')"]');
            const darkBtn = document.querySelector('[onclick*="switchTheme(\'dark\')"]');

            const activeClasses = 'bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 font-medium';
            const inactiveClasses = 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700';

            if (theme === 'light') {
                lightBtn.className = 'flex-1 px-3 py-2 text-sm rounded-md transition ' + activeClasses;
                darkBtn.className = 'flex-1 px-3 py-2 text-sm rounded-md transition ' + inactiveClasses;
            } else {
                lightBtn.className = 'flex-1 px-3 py-2 text-sm rounded-md transition ' + inactiveClasses;
                darkBtn.className = 'flex-1 px-3 py-2 text-sm rounded-md transition ' + activeClasses;
            }
        }

        // Sync theme from localStorage to server on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'dark';
            const serverTheme = '<?= $theme ?? 'dark' ?>';

            // Sync theme to server if different from PHP session
            if (savedTheme !== serverTheme) {
                fetch('/api/theme', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'theme=' + savedTheme
                });
            }

            // Update button states to match current theme
            updateThemeButtons(savedTheme);
        });
    </script>

</body>
</html>
