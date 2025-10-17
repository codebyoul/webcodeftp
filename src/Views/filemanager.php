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
            <aside id="sidebar" class="bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col" style="width: 256px; min-width: 180px; max-width: 600px;">
                <!-- Explorer Header -->
                <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Explorer</h3>
                </div>

                <!-- Folder Tree -->
                <div class="flex-1 overflow-y-auto p-2">
                    <!-- Loading State -->
                    <div id="treeLoading" class="flex items-center justify-center py-8">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin text-2xl text-gray-400 dark:text-gray-500 mb-2"></i>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Loading folders...</p>
                        </div>
                    </div>

                    <!-- Tree Container -->
                    <div id="folderTree" class="space-y-0.5 hidden"></div>

                    <!-- Error State -->
                    <div id="treeError" class="hidden px-3 py-4 text-center">
                        <i class="fas fa-exclamation-triangle text-yellow-500 text-xl mb-2"></i>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Unable to load folders</p>
                        <button id="retryLoadTree" class="mt-2 text-xs text-primary-600 dark:text-primary-400 hover:underline">Retry</button>
                    </div>
                </div>

                <!-- Status Bar -->
                <div class="px-3 py-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
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

                <!-- List View (Table) -->
                <div id="listView" class="h-full">
                    <table class="w-full text-sm">
                        <thead class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    <input type="checkbox" class="rounded border-gray-300 dark:border-gray-600">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Size</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Modified</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Owner</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Group</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">Permissions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- Sample Folders -->
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition">
                                <td class="px-4 py-3"><input type="checkbox" class="rounded border-gray-300 dark:border-gray-600"></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-folder text-yellow-500 mr-3"></i>
                                        <span class="text-gray-900 dark:text-white font-medium">composer</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">-</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">2025-10-16 22:58</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">5005</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">5005</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">750</td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition">
                                <td class="px-4 py-3"><input type="checkbox" class="rounded border-gray-300 dark:border-gray-600"></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-folder text-yellow-500 mr-3"></i>
                                        <span class="text-gray-900 dark:text-white font-medium">.ssh</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">-</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">2025-10-16 22:58</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">5005</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">5005</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">700</td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition">
                                <td class="px-4 py-3"><input type="checkbox" class="rounded border-gray-300 dark:border-gray-600"></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-folder text-yellow-500 mr-3"></i>
                                        <span class="text-gray-900 dark:text-white font-medium">backup</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">-</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">2025-10-16 22:58</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">5005</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">5005</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">755</td>
                            </tr>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition">
                                <td class="px-4 py-3"><input type="checkbox" class="rounded border-gray-300 dark:border-gray-600"></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-folder text-yellow-500 mr-3"></i>
                                        <span class="text-gray-900 dark:text-white font-medium">web</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">-</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">2025-10-17 16:12</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">5005</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">5005</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">710</td>
                            </tr>
                            <!-- Sample File -->
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition">
                                <td class="px-4 py-3"><input type="checkbox" class="rounded border-gray-300 dark:border-gray-600"></td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-file text-gray-400 dark:text-gray-500 mr-3"></i>
                                        <span class="text-gray-900 dark:text-white">document.pdf</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">2.4 MB</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">2025-10-17 15:22</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">5005</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">5005</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">644</td>
                            </tr>
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
             * Create folder element
             */
            function createFolderElement(item, level) {
                const div = document.createElement('div');
                div.dataset.path = item.path;
                div.dataset.level = level;

                // Create button
                const button = document.createElement('button');
                button.className = 'flex items-center w-full px-2 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition';
                button.style.paddingLeft = (level * 12 + 8) + 'px';

                // Arrow icon
                const arrow = document.createElement('i');
                arrow.className = 'fas fa-chevron-right text-xs text-gray-400 dark:text-gray-500 mr-1.5 transition-transform';
                button.appendChild(arrow);

                // Folder icon
                const folderIcon = document.createElement('i');
                folderIcon.className = level === 0 ? 'fas fa-folder text-primary-500 mr-2' : 'fas fa-folder text-yellow-500 mr-2';
                button.appendChild(folderIcon);

                // Folder name
                const span = document.createElement('span');
                span.textContent = item.name;
                button.appendChild(span);

                // Container for children
                const childrenContainer = document.createElement('div');
                childrenContainer.className = 'hidden';
                childrenContainer.dataset.childrenFor = item.path;

                // Click handler
                button.addEventListener('click', function() {
                    toggleFolder(item.path, arrow, childrenContainer, level);
                });

                div.appendChild(button);
                div.appendChild(childrenContainer);

                return div;
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
                loading.className = 'flex items-center px-2 py-1 text-xs text-gray-500 dark:text-gray-400';
                loading.style.paddingLeft = (level * 12 + 8) + 'px';
                loading.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
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
                            empty.className = 'px-2 py-1 text-xs text-gray-400 dark:text-gray-500 italic';
                            empty.style.paddingLeft = (level * 12 + 8) + 'px';
                            empty.textContent = 'Empty folder';
                            container.appendChild(empty);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading folder children:', error);
                        container.innerHTML = '';
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'px-2 py-1 text-xs text-red-500';
                        errorMsg.style.paddingLeft = (level * 12 + 8) + 'px';
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

            // Apply saved width from localStorage
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
