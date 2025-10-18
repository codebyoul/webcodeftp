<!-- Editor Content (inside main content area) -->
<!-- Hidden elements for backwards compatibility -->
<span id="editorFileName" class="hidden"></span>
<span id="editorModifiedBadge" class="hidden"></span>
<span id="editorFileIcon" class="hidden"></span>
<span id="editorFileType" class="hidden"></span>
<span id="editorFileSize" class="hidden"></span>
<button id="editorSaveBtn" class="hidden"></button>

<!-- Editor Container -->
<div id="editorContainer" class="flex-1 overflow-hidden">
    <!-- CodeMirror will be initialized here -->
</div>

<!-- Editor Status Bar -->
<div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-4 py-1 flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
    <div class="flex items-center gap-4">
        <span id="editorFileType">Plain Text</span>
        <span id="editorFileSize">0 B</span>
    </div>
    <div class="flex items-center gap-4">
        <span id="editorPosition">Ln 1, Col 1</span>
    </div>
</div>