<!-- Integrated Editor View (replaces file list when editing) -->
<div id="editorView" class="hidden h-full flex flex-col">

    <!-- Editor Header Bar -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <!-- File Info Bar -->
        <div class="px-4 py-2 flex items-center justify-between border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <i id="editorFileIcon" class="fas fa-file text-lg text-gray-500 dark:text-gray-400"></i>
                <div class="flex flex-col">
                    <span id="editorFileName" class="text-sm font-medium text-gray-900 dark:text-white">Untitled</span>
                    <span id="editorFilePath" class="text-xs text-gray-500 dark:text-gray-400 font-mono">/</span>
                </div>
                <span id="editorModifiedIndicator" class="hidden ml-2 px-2 py-0.5 bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-400 text-xs rounded-full">Modified</span>
            </div>

            <!-- File Status -->
            <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                <span id="editorFileSize">0 B</span>
                <span id="editorFileType">Plain Text</span>
                <span id="editorLineInfo">Line 1, Col 1</span>
            </div>
        </div>

        <!-- Editor Toolbar -->
        <div class="px-4 py-2 flex items-center justify-between">
            <!-- Left: Editor Actions -->
            <div class="flex items-center gap-1">
                <!-- Save -->
                <button id="editorSaveBtn" onclick="saveEditorFile()" class="px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-sm rounded-lg transition flex items-center gap-1.5" title="Save (Ctrl+S)">
                    <i class="fas fa-save"></i>
                    <span>Save</span>
                </button>

                <!-- Refresh from Server -->
                <button id="editorRefreshBtn" onclick="refreshEditorFile()" class="px-3 py-1.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 text-sm rounded-lg transition flex items-center gap-1.5" title="Reload from Server">
                    <i class="fas fa-sync-alt"></i>
                    <span>Refresh</span>
                </button>

                <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>

                <!-- Find -->
                <button onclick="openEditorSearch()" class="p-1.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="Find (Ctrl+F)">
                    <i class="fas fa-search"></i>
                </button>

                <!-- Replace -->
                <button onclick="openEditorReplace()" class="p-1.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="Replace (Ctrl+H)">
                    <i class="fas fa-exchange-alt"></i>
                </button>

                <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>

                <!-- Undo -->
                <button onclick="editorUndo()" class="p-1.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="Undo (Ctrl+Z)">
                    <i class="fas fa-undo"></i>
                </button>

                <!-- Redo -->
                <button onclick="editorRedo()" class="p-1.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="Redo (Ctrl+Y)">
                    <i class="fas fa-redo"></i>
                </button>

                <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>

                <!-- Format -->
                <button onclick="formatCode()" class="p-1.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="Format Code">
                    <i class="fas fa-indent"></i>
                </button>

                <!-- Toggle Comment -->
                <button onclick="toggleComment()" class="p-1.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="Toggle Comment (Ctrl+/)">
                    <i class="fas fa-comment-slash"></i>
                </button>
            </div>

            <!-- Right: View Actions -->
            <div class="flex items-center gap-2">
                <!-- Toggle Wrap -->
                <button onclick="toggleWordWrap()" class="p-1.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="Toggle Word Wrap">
                    <i class="fas fa-text-width"></i>
                </button>

                <!-- Toggle Minimap -->
                <button onclick="toggleMinimap()" class="p-1.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition" title="Toggle Minimap">
                    <i class="fas fa-map"></i>
                </button>

                <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>

                <!-- Close Editor -->
                <button onclick="closeIntegratedEditor()" class="px-3 py-1.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 text-sm rounded-lg transition flex items-center gap-1.5" title="Close Editor (Esc)">
                    <i class="fas fa-times"></i>
                    <span>Close</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Editor Container -->
    <div class="flex-1 flex overflow-hidden">
        <!-- Editor Sidebar (File Explorer) -->
        <div id="editorSidebar" class="w-64 bg-gray-50 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 flex flex-col">
            <!-- Sidebar Header -->
            <div class="px-3 py-2 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Explorer</span>
                <button onclick="toggleEditorSidebar()" class="p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition" title="Toggle Sidebar">
                    <i class="fas fa-angles-left text-xs"></i>
                </button>
            </div>

            <!-- File Tree -->
            <div class="flex-1 overflow-auto p-2">
                <!-- Current Directory -->
                <div class="mb-2">
                    <div class="flex items-center gap-1 px-2 py-1 text-xs text-gray-500 dark:text-gray-400">
                        <i class="fas fa-folder-open"></i>
                        <span id="editorCurrentDir" class="truncate">/current/path</span>
                    </div>
                </div>

                <!-- File List -->
                <div id="editorFileList" class="space-y-0.5">
                    <!-- Files will be populated here -->
                </div>
            </div>
        </div>

        <!-- Editor Main Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Tab Bar (for multiple files) -->
            <div id="editorTabs" class="hidden bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center px-2 gap-1 overflow-x-auto">
                <!-- Tabs will be added here dynamically -->
            </div>

            <!-- CodeMirror Container -->
            <div id="editorContainer" class="flex-1 overflow-hidden">
                <!-- CodeMirror will be initialized here -->
            </div>

            <!-- Editor Status Bar -->
            <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-4 py-1 flex items-center justify-between text-xs">
                <div class="flex items-center gap-4 text-gray-600 dark:text-gray-400">
                    <span id="editorLanguageMode">Plain Text</span>
                    <span id="editorEncoding">UTF-8</span>
                    <span id="editorLineEnding">LF</span>
                </div>
                <div class="flex items-center gap-4 text-gray-600 dark:text-gray-400">
                    <span id="editorCursorPosition">Ln 1, Col 1</span>
                    <span id="editorSelectionInfo"></span>
                    <span id="editorIndentInfo">Spaces: 4</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="editorLoadingOverlay" class="hidden absolute inset-0 bg-white/80 dark:bg-gray-900/80 flex items-center justify-center z-50">
        <div class="text-center">
            <i class="fas fa-spinner fa-spin text-3xl text-primary-500 mb-2"></i>
            <p class="text-sm text-gray-600 dark:text-gray-400">Loading file...</p>
        </div>
    </div>

    <!-- Error Overlay -->
    <div id="editorErrorOverlay" class="hidden absolute inset-0 bg-white dark:bg-gray-900 flex items-center justify-center z-50">
        <div class="text-center max-w-md">
            <i class="fas fa-exclamation-triangle text-5xl text-red-500 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Failed to Load File</h3>
            <p id="editorErrorMessage" class="text-gray-600 dark:text-gray-400 mb-4">An error occurred while loading the file.</p>
            <button onclick="closeIntegratedEditor()" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                Close Editor
            </button>
        </div>
    </div>
</div>