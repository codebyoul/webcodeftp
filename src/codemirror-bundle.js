// CodeMirror 6 Complete Bundle
// This file exports everything needed for the editor

import { EditorState } from '@codemirror/state';
import { EditorView, lineNumbers, highlightActiveLineGutter, keymap } from '@codemirror/view';
import { basicSetup } from 'codemirror';
import { defaultHighlightStyle, syntaxHighlighting, syntaxTree } from '@codemirror/language';
import { oneDark } from '@codemirror/theme-one-dark';
import { undo, redo, undoDepth, redoDepth } from '@codemirror/commands';

// Language modes
import { php } from '@codemirror/lang-php';
import { javascript } from '@codemirror/lang-javascript';
import { css } from '@codemirror/lang-css';
import { html } from '@codemirror/lang-html';
import { json } from '@codemirror/lang-json';
import { python } from '@codemirror/lang-python';
import { sql } from '@codemirror/lang-sql';
import { xml } from '@codemirror/lang-xml';
import { markdown } from '@codemirror/lang-markdown';
import { java } from '@codemirror/lang-java';
import { cpp } from '@codemirror/lang-cpp';
import { rust } from '@codemirror/lang-rust';
import { go } from '@codemirror/lang-go';
import { yaml } from '@codemirror/lang-yaml';
import { sass } from '@codemirror/lang-sass';
import { less } from '@codemirror/lang-less';
import { wast } from '@codemirror/lang-wast';

// Additional features
import { autocompletion } from '@codemirror/autocomplete';
import { linter, lintGutter } from '@codemirror/lint';

// Export everything as a single object
export default {
  // Core
  EditorState,
  EditorView,
  basicSetup,
  lineNumbers,
  highlightActiveLineGutter,
  keymap,

  // Commands
  undo,
  redo,
  undoDepth,
  redoDepth,

  // Language support
  defaultHighlightStyle,
  syntaxHighlighting,
  syntaxTree,

  // Theme
  oneDark,

  // Languages
  languages: {
    php, javascript, css, html, json, python, sql, xml,
    markdown, java, cpp, rust, go, yaml, sass, less, wast
  },

  // Additional features
  autocompletion,
  linter,
  lintGutter
};

console.log('CodeMirror Bundle loaded successfully');