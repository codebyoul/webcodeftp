# WebFTP CodeMirror Installation Guide

## Prerequisites
- Node.js (v14 or higher)
- npm (comes with Node.js)

## Installation Steps

### 1. Install Dependencies
Navigate to the project root directory and run:

```bash
npm install
```

This will install all CodeMirror packages and webpack.

### 2. Build the Bundle
To create the production-ready CodeMirror bundle:

```bash
npm run build
```

This will create `/public/js/dist/codemirror-bundle.js`

For development (unminified):
```bash
npm run build:dev
```

For watching changes during development:
```bash
npm run watch
```

### 3. The bundle will be available at:
- `/public/js/dist/codemirror-bundle.js` - The bundled file

## What's Included
The bundle includes:
- CodeMirror 6 core editor
- Syntax highlighting for 17+ languages (PHP, JavaScript, Python, etc.)
- Auto-completion
- Linting support
- Dark theme (One Dark)
- Line numbers and active line highlighting

## Usage in Your Code

The bundle exposes a global `CodeMirrorBundle` object with all the necessary components:

```javascript
// Access CodeMirror components
const { EditorState, EditorView, basicSetup, languages } = window.CodeMirrorBundle;

// Create an editor
const editor = new EditorView({
  state: EditorState.create({
    doc: "// Your code here",
    extensions: [
      basicSetup,
      languages.javascript() // or languages.php(), etc.
    ]
  }),
  parent: document.getElementById('editor')
});
```

## Directory Structure
```
webftp/
├── package.json          # NPM dependencies
├── webpack.config.js     # Webpack bundler configuration
├── src/
│   └── codemirror-bundle.js  # Source file that imports all modules
└── public/
    └── js/
        └── dist/
            └── codemirror-bundle.js  # Built bundle (generated)
```

## Troubleshooting

If you encounter "multiple instances of @codemirror/state" errors:
1. Delete `node_modules` and `package-lock.json`
2. Run `npm install` again
3. Rebuild with `npm run build`

## Notes
- The bundle is about 1-2MB minified
- All dependencies are resolved at build time
- No CDN required - everything runs locally