// CodeMirror 6 Editor - Using Local Bundle
// This loads the bundled CodeMirror from /js/dist/codemirror-bundle.js

// Wait for the bundle to load
window.addEventListener("DOMContentLoaded", function () {
  // Check if CodeMirrorBundle is available
  if (typeof window.CodeMirrorBundle === "undefined") {
    console.error(
      "CodeMirror bundle not loaded! Make sure codemirror-bundle.js is included before this script."
    );
    return;
  }

  // Extract what we need from the bundle
  const {
    EditorState,
    EditorView,
    basicSetup,
    oneDark,
    languages,
    autocompletion,
    linter,
    lintGutter,
  } = window.CodeMirrorBundle;

  // Global editor instance
  let editorView = null;
  let currentFilePath = null;
  let originalContent = null;
  let isModified = false;

  // Language mode mapping
  const languageModes = {
    // JavaScript
    js: () => languages.javascript(),
    mjs: () => languages.javascript(),
    jsx: () => languages.javascript(),
    ts: () => languages.javascript(),
    tsx: () => languages.javascript(),

    // Python
    py: () => languages.python(),
    pyw: () => languages.python(),

    // PHP
    php: () => languages.php(),
    phtml: () => languages.php(),

    // HTML/Templates
    html: () => languages.html(),
    htm: () => languages.html(),

    // CSS
    css: () => languages.css(),
    scss: () => languages.sass(),
    sass: () => languages.sass(),
    less: () => languages.less(),

    // Data formats
    json: () => languages.json(),
    xml: () => languages.xml(),
    yml: () => languages.yaml(),
    yaml: () => languages.yaml(),

    // SQL
    sql: () => languages.sql(),

    // Markdown
    md: () => languages.markdown(),
    markdown: () => languages.markdown(),

    // Other languages
    java: () => languages.java(),
    cpp: () => languages.cpp(),
    c: () => languages.cpp(),
    rs: () => languages.rust(),
    go: () => languages.go(),

    // Default - no syntax highlighting
    txt: () => [],
    log: () => [],
  };

  // Get language extension for file
  function getLanguageExtension(extension) {
    if (!extension) return [];

    const mode = languageModes[extension.toLowerCase()];
    if (mode && typeof mode === "function") {
      try {
        const result = mode();
        return result;
      } catch (error) {
        return [];
      }
    }
    return [];
  }

  // Linter for PHP
  const phpLinter = linter((view) => {
    const diagnostics = [];
    const content = view.state.doc.toString();
    const lines = content.split("\n");

    lines.forEach((line, index) => {
      // Check for common PHP issues
      if (line.includes("var ")) {
        diagnostics.push({
          from: view.state.doc.line(index + 1).from + line.indexOf("var"),
          to: view.state.doc.line(index + 1).from + line.indexOf("var") + 3,
          severity: "warning",
          message: "Use public, private, or protected instead of var",
        });
      }
    });

    return diagnostics;
  });

  // Initialize CodeMirror editor
  function initializeEditor(content, extension, isReadOnly = false) {
    const container = document.getElementById("editorContainer");

    // Destroy existing editor if any
    if (editorView) {
      editorView.destroy();
      editorView = null;
    }

    // Get current theme
    const isDarkMode = document.documentElement.classList.contains("dark");

    // Get language support
    const languageExtension = getLanguageExtension(extension);

    // Build extension set
    const extensions = [
      basicSetup,

      // Custom autocompletion
      autocompletion(),

      // Linting support
      lintGutter(),

      // Editable state
      EditorView.editable.of(!isReadOnly),

      // Document change listener
      EditorView.updateListener.of((update) => {
        if (update.docChanged && !isReadOnly) {
          if (!isModified) {
            isModified = true;
            document
              .getElementById("editorModifiedStatus")
              ?.classList.remove("hidden");
          }
          // Update all editor button states when document changes
          if (window.updateEditorButtons) {
            setTimeout(() => window.updateEditorButtons(), 10);
          }
        }
      }),

      // Editor styling
      EditorView.theme({
        "&": {
          height: "100%",
          fontSize: "14px",
          fontFamily: "'Consolas', 'Monaco', 'Courier New', monospace",
        },
        ".cm-scroller": {
          overflow: "auto",
          fontFamily: "'Consolas', 'Monaco', 'Courier New', monospace",
        },
        ".cm-content": {
          paddingTop: "8px",
          paddingBottom: "8px",
        },
      }),
    ];

    // Add language extension if it exists
    if (languageExtension) {
      if (Array.isArray(languageExtension)) {
        if (languageExtension.length > 0) {
          extensions.push(...languageExtension);
        }
      } else {
        extensions.push(languageExtension);
      }
    }

    // Add PHP linting if PHP file
    if (extension === "php") {
      extensions.push(phpLinter);
    }

    // Add dark theme if needed
    if (isDarkMode) {
      extensions.push(oneDark);
    }

    // Create editor state
    const state = EditorState.create({
      doc: content,
      extensions: extensions,
    });

    // Create editor view
    editorView = new EditorView({
      state: state,
      parent: container,
    });

    return editorView;
  }

  // Get current editor content
  function getEditorContent() {
    if (!editorView) return null;
    return editorView.state.doc.toString();
  }

  // Update editor theme
  function updateEditorTheme() {
    if (!editorView) return;

    const currentContent = getEditorContent();
    const extension = document
      .getElementById("editorFileExtension")
      ?.textContent.replace("Type: ", "");

    // Reinitialize editor with new theme
    initializeEditor(currentContent, extension, false);
  }

  // Export functions to global scope
  window.codeMirrorEditor = {
    initialize: initializeEditor,
    getContent: getEditorContent,
    updateTheme: updateEditorTheme,
    setModified: (value) => {
      isModified = value;
    },
    isModified: () => isModified,
    setCurrentFilePath: (path) => {
      currentFilePath = path;
    },
    getCurrentFilePath: () => currentFilePath,
    setOriginalContent: (content) => {
      originalContent = content;
    },
    getOriginalContent: () => originalContent,
    // Add undo/redo methods for CodeMirror 6
    undo: () => {
      if (editorView && window.CodeMirrorBundle) {
        const { undo } = window.CodeMirrorBundle;
        if (undo) {
          // In CodeMirror 6, commands return a boolean indicating success
          return undo(editorView);
        }
      }
      return false;
    },
    redo: () => {
      if (editorView && window.CodeMirrorBundle) {
        const { redo } = window.CodeMirrorBundle;
        if (redo) {
          // In CodeMirror 6, commands return a boolean indicating success
          return redo(editorView);
        }
      }
      return false;
    },
    // Check if undo/redo are available
    canUndo: () => {
      if (editorView && window.CodeMirrorBundle) {
        const { undoDepth } = window.CodeMirrorBundle;
        if (undoDepth) {
          return undoDepth(editorView.state) > 0;
        }
      }
      return false;
    },
    canRedo: () => {
      if (editorView && window.CodeMirrorBundle) {
        const { redoDepth } = window.CodeMirrorBundle;
        if (redoDepth) {
          return redoDepth(editorView.state) > 0;
        }
      }
      return false;
    },
    // Expose the editor view for advanced operations
    getEditorView: () => editorView,
  };
});
