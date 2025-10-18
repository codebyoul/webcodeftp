<!DOCTYPE html>
<html lang="<?= htmlspecialchars($language ?? 'en', ENT_QUOTES, 'UTF-8') ?>" class="<?= htmlspecialchars($theme ?? 'dark', ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($app_name ?? 'WebFTP', ENT_QUOTES, 'UTF-8') ?> - File Manager</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="alternate icon" type="image/x-icon" href="/favicon.ico">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Custom Styles -->
    <link rel="stylesheet" href="/css/custom.css">

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

    <!-- Pass PHP variables to JavaScript -->
    <script>
        // Global configuration from PHP
        window.FILE_ICON_CONFIG = <?= json_encode($file_icons) ?>;
        window.APP_CONFIG = {
            appName: <?= json_encode($app_name ?? 'WebFTP') ?>,
            ftpUsername: <?= json_encode($ftp_username ?? 'User') ?>,
            ftpHost: <?= json_encode($ftp_host ?? '') ?>,
            language: <?= json_encode($language ?? 'en') ?>,
            theme: <?= json_encode($theme ?? 'dark') ?>
        };
    </script>

    <!-- CodeMirror 6 Bundle (Compiled) -->
    <script src="/js/dist/codemirror-bundle.js"></script>

    <!-- CodeMirror Editor Module (Uses the bundle) -->
    <script src="/js/codemirror-editor.js"></script>
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
                            <button id="refreshBtn" onclick="refreshCurrentFolder()" class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="Refresh">
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

    <!-- Editor Panel (Slide-out from right) -->
    <div id="editorPanel" class="fixed inset-y-0 right-0 w-full md:w-11/12 lg:w-5/6 xl:w-4/5 bg-white dark:bg-gray-900 shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out z-50 flex flex-col border-l-2 border-gray-200 dark:border-gray-700">

        <!-- Panel Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
            <!-- File Info -->
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <i id="editorFileIcon" class="fas fa-file text-xl text-gray-500 dark:text-gray-400"></i>
                <div class="flex-1 min-w-0">
                    <h3 id="editorFileName" class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                        File Name
                    </h3>
                    <p id="editorFilePath" class="text-xs text-gray-500 dark:text-gray-400 truncate font-mono">
                        /path/to/file
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2 ml-4">
                <!-- Save Button (hidden for preview mode) -->
                <button id="editorSaveBtn" onclick="saveFile()" class="hidden px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 flex items-center gap-2">
                    <i class="fas fa-save"></i>
                    <span>Save</span>
                    <span class="text-xs opacity-75">(Ctrl+S)</span>
                </button>

                <!-- Close Button -->
                <button onclick="closeEditor()" class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors duration-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Panel Content -->
        <div class="flex-1 overflow-hidden relative">

            <!-- Loading State -->
            <div id="editorLoading" class="absolute inset-0 flex items-center justify-center bg-white dark:bg-gray-900">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-primary-500 mb-3"></i>
                    <p class="text-gray-600 dark:text-gray-400">Loading file...</p>
                </div>
            </div>

            <!-- Error State -->
            <div id="editorError" class="hidden absolute inset-0 flex items-center justify-center bg-white dark:bg-gray-900 p-8">
                <div class="text-center max-w-md">
                    <i class="fas fa-exclamation-triangle text-5xl text-red-500 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Failed to Load File</h3>
                    <p id="editorErrorMessage" class="text-gray-600 dark:text-gray-400 mb-6">
                        An error occurred while loading the file.
                    </p>
                    <button onclick="closeEditor()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                        Close
                    </button>
                </div>
            </div>

            <!-- Code Editor Container -->
            <div id="editorContainer" class="hidden h-full">
                <!-- CodeMirror will be initialized here -->
            </div>

            <!-- Image Preview Container -->
            <div id="imagePreviewContainer" class="hidden h-full overflow-auto bg-gray-100 dark:bg-gray-800 flex items-center justify-center p-8">
                <div class="max-w-full">
                    <img id="imagePreview" src="" alt="Image Preview" class="max-w-full max-h-full rounded-lg shadow-xl border border-gray-300 dark:border-gray-600">
                </div>
            </div>

            <!-- File Too Large Warning -->
            <div id="editorFileTooLarge" class="hidden absolute inset-0 flex items-center justify-center bg-white dark:bg-gray-900 p-8">
                <div class="text-center max-w-md">
                    <i class="fas fa-file-circle-exclamation text-5xl text-orange-500 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">File Too Large</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-2">
                        This file exceeds the maximum size limit for editing.
                    </p>
                    <p id="editorFileSizeInfo" class="text-sm text-gray-500 dark:text-gray-500 mb-6 font-mono">
                        File size: 0 MB / Max: 5 MB
                    </p>
                    <button onclick="closeEditor()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                        Close
                    </button>
                </div>
            </div>

        </div>

        <!-- Panel Footer (Status Bar) -->
        <div id="editorFooter" class="hidden px-6 py-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-between text-xs">
            <div class="flex items-center gap-4 text-gray-600 dark:text-gray-400">
                <span id="editorFileSize">Size: 0 B</span>
                <span id="editorFileExtension">Type: txt</span>
                <span id="editorModifiedStatus" class="hidden text-orange-500 dark:text-orange-400 font-medium">
                    <i class="fas fa-circle text-xs"></i> Modified
                </span>
            </div>
            <div class="text-gray-500 dark:text-gray-500">
                <span>Press <kbd class="px-1.5 py-0.5 bg-gray-200 dark:bg-gray-700 rounded text-xs">Esc</kbd> to close</span>
            </div>
        </div>
    </div>

    <!-- Editor Panel Overlay (Click to close) -->
    <div id="editorOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40 transition-opacity duration-300" onclick="closeEditor()"></div>

    <!-- Vanilla JavaScript for Interactivity -->
    <!-- File Manager JavaScript (External) -->
    <script src="/js/filemanager.js"></script>

    <!-- Theme & Language Persistence -->
    <script src="/js/theme-handler.js"></script>

</body>
</html>
