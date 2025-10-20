<!DOCTYPE html>
<html lang="<?= e($language ?? 'en') ?>" class="<?= e($theme ?? 'dark') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="<?= e($csrf_token) ?>">
    <title><?= e($app_name ?? 'WebFTP') ?> - File Manager</title>
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
    <!-- API Interceptor - MUST load FIRST to handle 401 responses -->
    <script src="/js/api-interceptor.js?v=<?= htmlspecialchars($asset_version ?? '1.0.0') ?>"></script>

    <script>
        // Global configuration from PHP
        window.FILE_ICON_CONFIG = <?= json_encode($file_icons) ?>;
        window.APP_CONFIG = {
            appName: <?= json_encode($app_name ?? 'WebFTP') ?>,
            ftpUsername: <?= json_encode($ftp_username ?? 'User') ?>,
            ftpHost: <?= json_encode($ftp_host ?? '') ?>,
            language: <?= json_encode($language ?? 'en') ?>,
            theme: <?= json_encode($theme ?? 'dark') ?>,
            sshEnabled: <?= json_encode($ssh_enabled ?? false) ?>
        };
    </script>

    <!-- CodeMirror 6 Bundle (Compiled) -->
    <script src="/js/dist/codemirror-bundle.js?v=<?= htmlspecialchars($asset_version ?? '1.0.0') ?>"></script>

    <!-- CodeMirror Editor Module (Uses the bundle) -->
    <script src="/js/codemirror-editor.js?v=<?= htmlspecialchars($asset_version ?? '1.0.0') ?>"></script>
</head>
<body class="h-screen overflow-hidden bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-200">

    <!-- Main Container -->
    <div class="flex flex-col h-screen">

        <!-- Top Toolbar -->
        <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="flex items-center justify-between px-4 py-3">

                <!-- Left Section: Logo Only -->
                <div class="flex items-center space-x-2">
                    <i class="fas fa-folder text-2xl text-primary-600 dark:text-primary-400"></i>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white"><?= e($app_name ?? 'WebFTP') ?></h1>
                </div>

                <!-- Right Section: Profile Only -->
                <div class="flex items-center">

                    <!-- Profile Dropdown -->
                    <div class="relative">
                        <button id="profileButton" class="flex items-center space-x-2 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                            <div class="w-8 h-8 bg-primary-600 dark:bg-primary-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                <?= strtoupper(substr($ftp_username ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="hidden sm:block text-left">
                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?= e($ftp_username ?? 'User') ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?= e($ftp_host) ?></div>
                            </div>
                            <i class="fas fa-chevron-down text-gray-500"></i>
                        </button>

                        <!-- Dropdown Menu -->
                        <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-72 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-2 z-50">

                            <!-- User Info -->
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?= e($ftp_username ?? 'User') ?></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?= t('connected_to', 'Connected to') ?>: <?= e($ftp_host) ?></div>
                            </div>

                            <!-- Theme Toggle -->
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2"><?= t('theme', 'Theme') ?></div>
                                <div class="flex items-center space-x-2">
                                    <button onclick="switchTheme('light')" class="flex-1 px-3 py-2 text-sm rounded-md transition <?= ($theme ?? 'dark') === 'light' ? 'bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                                        <i class="fas fa-sun mr-1"></i>
                                        <?= t('light', 'Light') ?>
                                    </button>
                                    <button onclick="switchTheme('dark')" class="flex-1 px-3 py-2 text-sm rounded-md transition <?= ($theme ?? 'dark') === 'dark' ? 'bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' ?>">
                                        <i class="fas fa-moon mr-1"></i>
                                        <?= t('dark', 'Dark') ?>
                                    </button>
                                </div>
                            </div>

                            <!-- Language Selection -->
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2"><?= t('language', 'Language') ?></div>
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
                                    <?= t('logout', 'Logout') ?>
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
                <div id="statusBar" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
                    <p id="statusBarText" class="text-sm text-gray-500 dark:text-gray-400 font-medium transition-colors duration-200">
                        <span id="selectedCount">0</span> <?= t('items_selected', 'items selected') ?>
                    </p>
                </div>
            </aside>

            <!-- Resize Handle -->
            <div id="resizeHandle" class="w-1 bg-gray-200 dark:bg-gray-700 hover:bg-primary-500 dark:hover:bg-primary-500 cursor-col-resize transition-colors flex-shrink-0" title="<?= t('drag_to_resize', 'Drag to resize') ?>"></div>

            <!-- Right Content Area -->
            <main class="flex-1 flex flex-col overflow-hidden">

                <!-- Normal File Manager Toolbar -->
                <div id="fileManagerToolbar" class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                    <div class="flex items-center justify-between gap-4">

                        <!-- Left: Action Icons -->
                        <div class="flex items-center gap-1">
                            <button class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="<?= t('upload_file', 'Upload File') ?>">
                                <i class="fas fa-upload"></i>
                            </button>
                            <button onclick="downloadSelected()" class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="<?= t('download', 'Download') ?>">
                                <i class="fas fa-download"></i>
                            </button>
                            <button onclick="createNewFile()" class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="<?= t('new_file', 'New File') ?>">
                                <i class="fas fa-file-circle-plus"></i>
                            </button>
                            <button onclick="createNewFolder()" class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="<?= t('new_folder', 'New Folder') ?>">
                                <i class="fas fa-folder-plus"></i>
                            </button>
                            <button id="parentFolderBtn" onclick="navigateToParent()" class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition hidden" title="<?= t('parent_folder', 'Parent Folder') ?>">
                                <i class="fas fa-level-up-alt"></i>
                            </button>
                            <button onclick="deleteSelected()" class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="<?= t('delete', 'Delete') ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                            <button onclick="renameSelected()" class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="<?= t('rename', 'Rename') ?>">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button id="refreshBtn" onclick="refreshCurrentFolder()" class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="<?= t('refresh', 'Refresh') ?>">
                                <i class="fas fa-arrows-rotate"></i>
                            </button>

                            <!-- SSH-based Operations (shown only when SSH is enabled) -->
                            <?php if ($ssh_enabled ?? false): ?>
                            <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>

                            <button id="zipBtn" onclick="zipSelectedFiles()" class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="<?= t('zip_files', 'Zip Selected Files') ?>">
                                <i class="fas fa-file-zipper"></i>
                            </button>
                            <button id="unzipBtn" onclick="unzipSelectedFile()" class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition opacity-50 cursor-not-allowed" title="<?= t('unzip_file', 'Unzip File') ?>" disabled>
                                <i class="fas fa-file-archive"></i>
                            </button>
                            <button id="moveBtn" onclick="moveSelectedFiles()" class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="<?= t('move_files', 'Move Selected Files') ?>">
                                <i class="fas fa-arrows-turn-to-dots"></i>
                            </button>
                            <?php endif; ?>
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

                <!-- Editor Toolbar (shown when editor is active) -->
                <div id="editorToolbar" class="hidden bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                    <div class="flex items-center justify-between">
                        <!-- Left: File Info & Actions -->
                        <div class="flex items-center gap-3">
                            <!-- File Icon and Name -->
                            <div class="flex items-center gap-2">
                                <i id="editorToolbarFileIcon" class="fas fa-file text-lg text-gray-500 dark:text-gray-400"></i>
                                <span id="editorToolbarFileName" class="text-sm font-medium text-gray-900 dark:text-white">Untitled</span>
                                <span id="editorToolbarModifiedBadge" class="hidden px-2 py-0.5 bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-400 text-xs rounded"><?= t('modified_indicator', 'Modified') ?></span>
                            </div>

                            <div class="w-px h-6 bg-gray-300 dark:bg-gray-600"></div>

                            <!-- Editor Actions -->
                            <div class="flex items-center gap-1">
                                <button id="editorToolbarSaveBtn" onclick="saveFile()" class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition opacity-50 cursor-not-allowed" title="<?= t('save', 'Save') ?> (Ctrl+S)" disabled>
                                    <i class="fas fa-save"></i>
                                </button>
                                <button onclick="refreshFile()" class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="<?= t('refresh', 'Refresh') ?>">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                <button onclick="searchInFile()" class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="<?= t('find', 'Find') ?> (Ctrl+F)">
                                    <i class="fas fa-search"></i>
                                </button>

                                <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>

                                <button onclick="editorUndo()" class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition opacity-50 cursor-not-allowed" title="<?= t('undo', 'Undo') ?> (Ctrl+Z)" disabled>
                                    <i class="fas fa-undo"></i>
                                </button>
                                <button onclick="editorRedo()" class="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition opacity-50 cursor-not-allowed" title="<?= t('redo', 'Redo') ?> (Ctrl+Y)" disabled>
                                    <i class="fas fa-redo"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Right: Status and Close -->
                        <div class="flex items-center gap-3">
                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                <span id="editorToolbarFileType">Plain Text</span>
                                <span class="mx-2">•</span>
                                <span id="editorToolbarFileSize">0 B</span>
                            </div>

                            <button onclick="closeEditor()" class="p-2 text-gray-600 dark:text-gray-400 hover:bg-red-100 dark:hover:bg-red-900 hover:text-red-600 dark:hover:text-red-400 rounded transition" title="<?= t('close', 'Close') ?> (Esc)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- File Content Area -->
                <div id="mainContentArea" class="flex-1 overflow-auto bg-gray-50 dark:bg-gray-900">
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
                            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2"><?= t('select_folder', 'Select a Folder') ?></h3>
                            <p class="text-gray-500 dark:text-gray-400"><?= t('select_folder_message', 'Click on a folder in the sidebar to view its contents') ?></p>
                        </div>
                    </div>

                    <!-- Integrated Editor View -->
                    <div id="editorView" class="hidden h-full flex flex-col">
                        <?php include __DIR__ . '/filemanager_editor_content.php'; ?>
                    </div>

                    <!-- Image Preview View -->
                    <div id="imagePreviewView" class="hidden h-full flex flex-col bg-gray-50 dark:bg-gray-900">
                        <!-- Image Toolbar -->
                        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 py-3 flex items-center justify-between">
                            <!-- Left: Image Info -->
                            <div class="flex items-center gap-4">
                                <i id="imagePreviewIcon" class="fas fa-image text-2xl text-blue-500 dark:text-blue-400"></i>
                                <div>
                                    <h3 id="imagePreviewFileName" class="text-sm font-semibold text-gray-900 dark:text-white">Image</h3>
                                    <p id="imagePreviewInfo" class="text-xs text-gray-500 dark:text-gray-400"></p>
                                </div>
                            </div>

                            <!-- Right: Controls -->
                            <div class="flex items-center gap-2">
                                <button onclick="zoomImageOut()" class="px-3 py-1.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="Zoom Out">
                                    <i class="fas fa-search-minus"></i>
                                </button>
                                <button onclick="resetImageZoom()" class="px-3 py-1.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="Reset Zoom">
                                    <i class="fas fa-compress"></i>
                                </button>
                                <button onclick="zoomImageIn()" class="px-3 py-1.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="Zoom In">
                                    <i class="fas fa-search-plus"></i>
                                </button>
                                <div class="w-px h-6 bg-gray-300 dark:bg-gray-600"></div>
                                <button onclick="downloadImage()" class="px-3 py-1.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition flex items-center gap-1.5" title="Download">
                                    <i class="fas fa-download"></i>
                                    <span>Download</span>
                                </button>
                                <button onclick="closeImagePreview()" class="px-3 py-1.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition flex items-center gap-1.5" title="Close">
                                    <i class="fas fa-times"></i>
                                    <span>Close</span>
                                </button>
                            </div>
                        </div>

                        <!-- Image Container -->
                        <div class="flex-1 overflow-auto flex items-center justify-center p-8">
                            <div class="relative">
                                <img id="imagePreviewImg" src="" alt="Preview" class="max-w-full h-auto shadow-2xl rounded-lg transition-transform duration-200" style="transform-origin: center center;">
                            </div>
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
                                    <input type="checkbox" id="selectAllCheckbox" class="rounded border-gray-300 dark:border-gray-600 cursor-pointer" title="<?= t('select_all', 'Select All') ?>">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-primary-500 dark:hover:text-primary-400 transition" onclick="sortListView('name')">
                                    <div class="flex items-center gap-2">
                                        <?= t('name', 'Name') ?>
                                        <i class="fas fa-sort text-xs opacity-50"></i>
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-primary-500 dark:hover:text-primary-400 transition" onclick="sortListView('size')">
                                    <div class="flex items-center justify-end gap-2">
                                        <?= t('size', 'Size') ?>
                                        <i class="fas fa-sort text-xs opacity-50"></i>
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-primary-500 dark:hover:text-primary-400 transition" onclick="sortListView('modified')">
                                    <div class="flex items-center gap-2">
                                        <?= t('modified', 'Modified') ?>
                                        <i class="fas fa-sort text-xs opacity-50"></i>
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:text-primary-500 dark:hover:text-primary-400 transition" onclick="sortListView('permissions')">
                                    <div class="flex items-center justify-center gap-2">
                                        <?= t('permissions', 'Permissions') ?>
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

            </div>
        </main>
        </div>
    </div>

    <!-- Custom Dialog Modal -->
    <div id="customDialog" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 transition-opacity duration-200">
        <div id="customDialogBox" class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl max-w-md w-full mx-4 transform transition-transform duration-200 scale-95">
            <!-- Dialog Header -->
            <div id="customDialogHeader" class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 id="customDialogTitle" class="text-lg font-semibold text-gray-900 dark:text-white"></h3>
            </div>

            <!-- Dialog Body -->
            <div id="customDialogBody" class="px-6 py-4">
                <p id="customDialogMessage" class="text-gray-700 dark:text-gray-300 whitespace-pre-line"></p>
                <input id="customDialogInput" type="text" class="mt-4 w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent hidden">
            </div>

            <!-- Dialog Footer -->
            <div id="customDialogFooter" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                <button id="customDialogCancel" class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition hidden">
                    Cancel
                </button>
                <button id="customDialogConfirm" class="px-4 py-2 text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                    OK
                </button>
            </div>
        </div>
    </div>

    <!-- Vanilla JavaScript for Interactivity -->
    <!-- File Manager JavaScript (External) -->
    <script src="/js/filemanager.js?v=<?= htmlspecialchars($asset_version ?? '1.0.0') ?>"></script>

    <!-- Integrated Editor JavaScript -->
    <script src="/js/integrated-editor.js?v=<?= htmlspecialchars($asset_version ?? '1.0.0') ?>"></script>

    <!-- Theme & Language Persistence -->
    <script src="/js/theme-handler.js?v=<?= htmlspecialchars($asset_version ?? '1.0.0') ?>"></script>

</body>
</html>
