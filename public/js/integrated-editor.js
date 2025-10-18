// Simple Integrated Editor
// Replaces the popup editor with an integrated one in the content area

let currentEditingFile = null;
let originalContent = null;

/**
 * Open file in integrated editor (replaces content area)
 */
function openIntegratedEditor(path) {
    console.log('Opening file in integrated editor:', path);

    // Store current file
    currentEditingFile = path;

    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // Hide all content views
    document.getElementById('contentEmpty').classList.add('hidden');
    document.getElementById('listView').classList.add('hidden');
    document.getElementById('gridView').classList.add('hidden');

    // Show editor view
    document.getElementById('editorView').classList.remove('hidden');

    // Switch toolbars - hide normal toolbar, show editor toolbar
    document.getElementById('fileManagerToolbar').classList.add('hidden');
    document.getElementById('editorToolbar').classList.remove('hidden');

    // Fetch file content
    fetch('/api/file/read?path=' + encodeURIComponent(path))
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update file info
            const fileName = path.split('/').pop();
            const icon = getFileIcon(path);
            const extension = fileName.split('.').pop().toLowerCase();

            // Update top toolbar file info
            document.getElementById('editorToolbarFileName').textContent = fileName;
            document.getElementById('editorToolbarFileIcon').className = icon;
            document.getElementById('editorToolbarFileType').textContent = getFileTypeName(extension);
            document.getElementById('editorToolbarFileSize').textContent = formatFileSize(data.size || 0);

            // Update editor view file info (if these elements exist)
            if (document.getElementById('editorFileName')) {
                document.getElementById('editorFileName').textContent = fileName;
            }
            if (document.getElementById('editorFileIcon')) {
                document.getElementById('editorFileIcon').className = icon;
            }
            if (document.getElementById('editorFileType')) {
                document.getElementById('editorFileType').textContent = getFileTypeName(extension);
            }
            if (document.getElementById('editorFileSize')) {
                document.getElementById('editorFileSize').textContent = formatFileSize(data.size || 0);
            }

            if (data.isEditable) {
                // Initialize CodeMirror editor
                if (window.codeMirrorEditor) {
                    window.codeMirrorEditor.initialize(data.content, extension, false);
                    originalContent = data.content;
                    window.codeMirrorEditor.setOriginalContent(data.content);
                    window.codeMirrorEditor.setCurrentFilePath(path);
                    window.codeMirrorEditor.setModified(false);
                    // Update undo/redo button states after loading file
                    setTimeout(updateUndoRedoButtons, 100);
                }

                // Enable save buttons
                document.getElementById('editorSaveBtn').disabled = false;
                document.getElementById('editorToolbarSaveBtn').disabled = false;
            } else {
                // Show message for non-editable files
                document.getElementById('editorContainer').innerHTML = '<div class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">This file type cannot be edited</div>';
                document.getElementById('editorSaveBtn').disabled = true;
                document.getElementById('editorToolbarSaveBtn').disabled = true;
            }
        } else {
            alert('Failed to open file: ' + (data.message || 'Unknown error'));
            closeEditor();
        }
    })
    .catch(error => {
        console.error('Error opening file:', error);
        alert('Failed to open file');
        closeEditor();
    });
}

/**
 * Save file
 */
function saveFile() {
    if (!window.codeMirrorEditor || !currentEditingFile) return;

    const content = window.codeMirrorEditor.getContent();
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // Show saving state
    const saveBtn = document.getElementById('editorSaveBtn');
    const topSaveBtn = document.getElementById('editorToolbarSaveBtn');
    const originalHtml = saveBtn.innerHTML;
    const topOriginalHtml = topSaveBtn.innerHTML;

    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Saving...</span>';
    saveBtn.disabled = true;
    topSaveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Saving...</span>';
    topSaveBtn.disabled = true;

    fetch('/api/file/write', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            path: currentEditingFile,
            content: content,
            _csrf_token: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update original content
            originalContent = content;
            window.codeMirrorEditor.setOriginalContent(content);
            window.codeMirrorEditor.setModified(false);

            // Hide modified badges
            document.getElementById('editorModifiedBadge').classList.add('hidden');
            document.getElementById('editorToolbarModifiedBadge').classList.add('hidden');

            // Show success
            saveBtn.innerHTML = '<i class="fas fa-check"></i> <span>Saved</span>';
            topSaveBtn.innerHTML = '<i class="fas fa-check"></i> <span>Saved</span>';
            setTimeout(() => {
                saveBtn.innerHTML = originalHtml;
                saveBtn.disabled = false;
                topSaveBtn.innerHTML = topOriginalHtml;
                topSaveBtn.disabled = false;
            }, 2000);
        } else {
            alert('Failed to save: ' + (data.message || 'Unknown error'));
            saveBtn.innerHTML = originalHtml;
            saveBtn.disabled = false;
            topSaveBtn.innerHTML = topOriginalHtml;
            topSaveBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error saving file:', error);
        alert('Failed to save file');
        saveBtn.innerHTML = originalHtml;
        saveBtn.disabled = false;
        topSaveBtn.innerHTML = topOriginalHtml;
        topSaveBtn.disabled = false;
    });
}

/**
 * Refresh file from server
 */
function refreshFile() {
    if (!currentEditingFile) return;

    if (window.codeMirrorEditor && window.codeMirrorEditor.isModified()) {
        if (!confirm('You have unsaved changes. Refreshing will discard them. Continue?')) {
            return;
        }
    }

    openIntegratedEditor(currentEditingFile);
}

/**
 * Search in file
 */
function searchInFile() {
    // CodeMirror has built-in search - trigger it
    if (window.codeMirrorEditor) {
        // This would trigger CodeMirror's search dialog
        // For now, we can use browser's find
        alert('Use Ctrl+F or Cmd+F to search in the editor');
    }
}

/**
 * Close editor and return to file list
 */
function closeEditor() {
    // Check for unsaved changes
    if (window.codeMirrorEditor && window.codeMirrorEditor.isModified()) {
        if (!confirm('You have unsaved changes. Are you sure you want to close?')) {
            return;
        }
    }

    // Hide editor
    document.getElementById('editorView').classList.add('hidden');

    // Switch toolbars back - show normal toolbar, hide editor toolbar
    document.getElementById('fileManagerToolbar').classList.remove('hidden');
    document.getElementById('editorToolbar').classList.add('hidden');

    // Show appropriate view
    const listView = document.getElementById('listView');
    const gridView = document.getElementById('gridView');

    if (listView && listView.querySelector('tbody tr')) {
        listView.classList.remove('hidden');
    } else if (gridView && gridView.querySelector('.group')) {
        gridView.classList.remove('hidden');
    } else {
        document.getElementById('contentEmpty').classList.remove('hidden');
    }

    currentEditingFile = null;
}

/**
 * Get file type name
 */
function getFileTypeName(extension) {
    const types = {
        php: 'PHP',
        js: 'JavaScript',
        css: 'CSS',
        html: 'HTML',
        json: 'JSON',
        xml: 'XML',
        sql: 'SQL',
        py: 'Python',
        java: 'Java',
        cpp: 'C++',
        c: 'C',
        go: 'Go',
        rs: 'Rust',
        md: 'Markdown',
        txt: 'Plain Text',
    };
    return types[extension] || extension.toUpperCase();
}

/**
 * Monitor for changes
 */
document.addEventListener('DOMContentLoaded', function() {
    // Monitor for modified state
    setInterval(() => {
        if (window.codeMirrorEditor && currentEditingFile) {
            const badge = document.getElementById('editorModifiedBadge');
            const topBadge = document.getElementById('editorToolbarModifiedBadge');

            if (window.codeMirrorEditor.isModified()) {
                if (badge) badge.classList.remove('hidden');
                if (topBadge) topBadge.classList.remove('hidden');
            } else {
                if (badge) badge.classList.add('hidden');
                if (topBadge) topBadge.classList.add('hidden');
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
        updateUndoRedoButtons();
    }
}

/**
 * Redo last undone change
 */
function editorRedo() {
    if (window.codeMirrorEditor && window.codeMirrorEditor.redo) {
        window.codeMirrorEditor.redo();
        updateUndoRedoButtons();
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
    const canUndo = window.codeMirrorEditor.canUndo ? window.codeMirrorEditor.canUndo() : false;
    const canRedo = window.codeMirrorEditor.canRedo ? window.codeMirrorEditor.canRedo() : false;

    // Update button states
    if (canUndo) {
        undoBtn.disabled = false;
        undoBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        undoBtn.classList.add('hover:bg-gray-100', 'dark:hover:bg-gray-700');
    } else {
        undoBtn.disabled = true;
        undoBtn.classList.add('opacity-50', 'cursor-not-allowed');
        undoBtn.classList.remove('hover:bg-gray-100', 'dark:hover:bg-gray-700');
    }

    if (canRedo) {
        redoBtn.disabled = false;
        redoBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        redoBtn.classList.add('hover:bg-gray-100', 'dark:hover:bg-gray-700');
    } else {
        redoBtn.disabled = true;
        redoBtn.classList.add('opacity-50', 'cursor-not-allowed');
        redoBtn.classList.remove('hover:bg-gray-100', 'dark:hover:bg-gray-700');
    }
}

/**
 * Keyboard shortcuts
 */
document.addEventListener('keydown', function(e) {
    if (!currentEditingFile) return;

    // Ctrl+S / Cmd+S - Save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        saveFile();
    }

    // Escape - Close editor
    if (e.key === 'Escape') {
        e.preventDefault();
        closeEditor();
    }
});