// CodeMirror 6 Complete Bundle
// This file exports everything needed for the editor

import { redo, redoDepth, undo, undoDepth } from "@codemirror/commands";
import {
  defaultHighlightStyle,
  syntaxHighlighting,
  syntaxTree,
} from "@codemirror/language";
import { EditorState } from "@codemirror/state";
import { oneDark } from "@codemirror/theme-one-dark";
import {
  EditorView,
  highlightActiveLineGutter,
  keymap,
  lineNumbers,
} from "@codemirror/view";
import { basicSetup } from "codemirror";

// Language modes
import { cpp } from "@codemirror/lang-cpp";
import { css } from "@codemirror/lang-css";
import { go } from "@codemirror/lang-go";
import { html } from "@codemirror/lang-html";
import { java } from "@codemirror/lang-java";
import { javascript } from "@codemirror/lang-javascript";
import { json } from "@codemirror/lang-json";
import { less } from "@codemirror/lang-less";
import { markdown } from "@codemirror/lang-markdown";
import { php } from "@codemirror/lang-php";
import { python } from "@codemirror/lang-python";
import { rust } from "@codemirror/lang-rust";
import { sass } from "@codemirror/lang-sass";
import { sql } from "@codemirror/lang-sql";
import { wast } from "@codemirror/lang-wast";
import { xml } from "@codemirror/lang-xml";
import { yaml } from "@codemirror/lang-yaml";
import { openSearchPanel, search } from "@codemirror/search";

// Additional features
import { autocompletion } from "@codemirror/autocomplete";
import { linter, lintGutter } from "@codemirror/lint";

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

  //search
  search,
  openSearchPanel,

  // Language support
  defaultHighlightStyle,
  syntaxHighlighting,
  syntaxTree,

  // Theme
  oneDark,

  // Languages
  languages: {
    php,
    javascript,
    css,
    html,
    json,
    python,
    sql,
    xml,
    markdown,
    java,
    cpp,
    rust,
    go,
    yaml,
    sass,
    less,
    wast,
  },

  // Additional features
  autocompletion,
  linter,
  lintGutter,
};

console.log("CodeMirror Bundle loaded successfully");
