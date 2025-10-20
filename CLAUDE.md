# WebCodeFTP - Development Guidelines for Claude

This document contains all architectural rules, conventions, and best practices for the WebCodeFTP project. **ALL CHANGES must follow these guidelines.**

## 📋 Table of Contents
- [Project Overview](#project-overview)
- [Architecture Rules](#architecture-rules)
- [URL Source of Truth Architecture](#url-source-of-truth-architecture)
- [Event-Driven Architecture](#event-driven-architecture)
- [Security Requirements](#security-requirements)
- [Performance Requirements](#performance-requirements)
- [Internationalization (i18n)](#internationalization-i18n)
- [Configuration Management](#configuration-management)
- [Logging System](#logging-system)
- [UI/UX Guidelines](#uiux-guidelines)
- [Code Standards](#code-standards)
- [File Structure](#file-structure)

---

## 🎯 Project Overview

**WebCodeFTP** is a secure, modern web-based FTP client with integrated **CodeMirror 6 editor** designed for hosting providers and developers. Customers access their ISPConfig/cPanel FTP accounts through a clean web interface and can **edit code files directly in the browser**.

**Key Characteristics:**
- MVC architecture pattern
- PHP 8.0+ compatible (NO PHP 8.1+ exclusive features like `readonly`)
- Single-page file manager with integrated code editor
- **CodeMirror 6** for syntax highlighting and code editing (50+ languages)
- Zero backend dependencies (only TailwindCSS CDN for styling)
- No database - session-based storage only
- Enterprise-grade security
- Multi-language support (7 languages)
- Dark/Light theme support

---

## 🏗️ Architecture Rules

### MVC Pattern (Strict Separation)

1. **Models** (`src/Models/`)
   - Handle data and business logic
   - FTP connections, session management
   - NO HTML output, NO direct user interaction

2. **Views** (`src/Views/`)
   - PHP templates with HTML/CSS
   - Display data only, minimal logic
   - All text must be translatable (use `$translations` array)
   - NO business logic, NO database calls

3. **Controllers** (`src/Controllers/`)
   - Handle HTTP requests/responses
   - Coordinate between Models and Views
   - Validation, authentication checks
   - NO HTML output directly

### Core Components (`src/Core/`)
- `Router.php` - URL routing
- `Request.php` - HTTP request handling
- `Response.php` - HTTP response handling
- `SecurityManager.php` - Security utilities
- `CsrfToken.php` - CSRF protection
- `Language.php` - Translation system
- `ConfigValidator.php` - Configuration validation

---

## 🎯 URL Source of Truth Architecture

**CRITICAL CONCEPT: The browser URL is the single source of truth for application state.**

### Core Principle

The file manager uses a **URL-driven architecture** where the browser's URL always represents the current state. This means:

- ✅ **URL = Application State** - The URL always contains current path and action
- ✅ **No Internal State** - NEVER create variables like `currentPath` or `currentAction`
- ✅ **Read from URL** - All UI components read state from URL parameters
- ✅ **Write to URL** - All navigation updates the URL via `navigateTo()`
- ✅ **Browser Integration** - Back/forward buttons, bookmarks, refresh all work automatically

### URL Structure

```
https://ftp.hostinoya.com/filemanager?path=/web/css&action=edit
                                       └─────────┘ └──────────┘
                                       Current Path  Current Action
```

**URL Parameters:**
- `path` - Current folder or file path (e.g., `/`, `/web`, `/web/index.php`)
- `action` - Optional action (`edit` for editor, `preview` for image preview)

### Two Core Functions

#### 1. `navigateTo(path, action, skipExpand)` - WRITE to URL

**Location:** `filemanager.js` line ~489

**Purpose:** Update the URL and trigger state change

```javascript
function navigateTo(path, action = null, skipExpand = false) {
  const url = new URL(window.location);
  url.searchParams.set("path", path);

  if (action) {
    url.searchParams.set("action", action);
  } else {
    url.searchParams.delete("action");
  }

  window.history.pushState({ path, action }, "", url);
  window.dispatchEvent(new CustomEvent("urlchange", { detail: { skipExpand } }));
}
```

**Key Points:**
- Updates browser URL using `history.pushState`
- Dispatches custom `urlchange` event
- Does NOT directly load content - just updates URL
- `skipExpand` tells tree not to auto-expand (when user manually clicks folder in tree)

#### 2. `handleUrlChange(event)` - READ from URL

**Location:** `filemanager.js` line ~513

**Purpose:** React to URL changes and load appropriate content

```javascript
function handleUrlChange(event) {
  const urlParams = new URLSearchParams(window.location.search);
  const path = urlParams.get("path") || "/";
  const action = urlParams.get("action");

  // Clear selection, update UI
  clearSelection();
  updateParentButtonVisibility();

  // Sync tree (highlight current path)
  highlightCurrentPath(path, event?.detail?.skipExpand ? false : true);

  // Determine if file or folder
  const isFile = path.includes(".") && !path.endsWith("/");

  if (isFile) {
    if (action === "edit") {
      openIntegratedEditor(path);
    } else if (action === "preview") {
      // Load folder contents, then show image preview
    } else {
      // Show file info
    }
  } else {
    // Load folder contents
    loadFolderContents(path);
  }
}
```

**Key Points:**
- **Reads** URL parameters (never has internal state)
- Determines what to show based on URL
- Loads appropriate content
- Updates all UI components

### Event Listeners

**Location:** `filemanager.js` line ~2309

```javascript
// Listen to custom urlchange event (dispatched by navigateTo)
window.addEventListener("urlchange", handleUrlChange);

// Listen to popstate (browser back/forward buttons)
window.addEventListener("popstate", handleUrlChange);
```

**When `handleUrlChange` is Called:**
1. Custom `urlchange` event - When `navigateTo()` is called programmatically
2. `popstate` event - When user clicks browser back/forward buttons
3. Initial page load - Called after tree loads

### Complete Navigation Flow

```
USER ACTION (click, type, back button)
           ↓
    navigateTo(path, action)
           ↓
    1. Update URL (pushState)
    2. Dispatch "urlchange" event
           ↓
    Event Listener catches it
           ↓
    handleUrlChange() is called
           ↓
    Read URL params: path, action
           ↓
    ┌─────────────┬─────────────┐
    ↓             ↓             ↓
  FILE        FOLDER        ACTION
    ↓             ↓             ↓
  Show info   Load contents   Open editor/preview
```

### Practical Examples

#### Example 1: User Clicks Folder in Sidebar

```javascript
button.addEventListener("click", function (e) {
  toggleFolder(item.path, arrow, childrenContainer);  // Expand/collapse tree
  navigateTo(item.path, null, true);  // Update URL with skipExpand=true
});
```

Flow:
1. Toggle folder in tree (expand/collapse UI)
2. Call `navigateTo(path, null, true)` with skipExpand=true
3. URL updates: `?path=/web`
4. `handleUrlChange` is called
5. Tree is just highlighted (not re-expanded because skipExpand=true)
6. Folder contents load in main view

#### Example 2: User Clicks Edit Button

```javascript
function editSelected() {
  const file = window.selectedItems[0];
  const realPath = file.real_path || file.path;
  previewFile(realPath);  // Calls navigateTo(path, "edit")
}
```

Flow:
1. User clicks Edit button
2. `editSelected()` calls `previewFile()`
3. `previewFile()` calls `navigateTo(path, "edit")`
4. URL updates: `?path=/web/index.php&action=edit`
5. `handleUrlChange()` reads `action=edit`
6. Opens integrated editor

#### Example 3: Browser Back Button

Flow:
1. User was viewing `/web/css` (URL: `?path=/web/css`)
2. User navigated to `/web/js` (URL: `?path=/web/js`)
3. User clicks browser **Back** button
4. Browser changes URL to `?path=/web/css`
5. `popstate` event fires automatically
6. `handleUrlChange()` reads URL and loads `/web/css`

### Why This Architecture is Elite

✅ **Browser Integration** - Back/forward buttons work automatically
✅ **Bookmarkable URLs** - Can share direct links to files/folders
✅ **Refresh-Safe** - Page refresh preserves state (URL doesn't change)
✅ **Single Source of Truth** - No state synchronization issues
✅ **Simplicity** - No complex state management libraries needed
✅ **Debugging** - Can see current state in URL bar
✅ **Testing** - Can manually change URL to test different states

### Developer Rules (CRITICAL)

1. **ALWAYS use `navigateTo()` for navigation**
   - ❌ `window.location.href = "/filemanager?path=/web"`
   - ✅ `navigateTo("/web")`

2. **NEVER create internal state variables**
   - ❌ `let currentPath = "/web"; let currentAction = "edit";`
   - ✅ Read from URL: `const path = new URLSearchParams(window.location.search).get("path")`

3. **NEVER manually call `handleUrlChange()` from user actions**
   - ❌ `button.onclick = () => handleUrlChange()`
   - ✅ `button.onclick = () => navigateTo(path)`

4. **Use callbacks, not setTimeout, for async operations**
   - ❌ `navigateTo(path); setTimeout(() => highlightPath(), 500);`
   - ✅ `navigateTo(path); // handleUrlChange automatically called, which highlights`

5. **For tree refresh after operations, use existing callback pattern**
   - ❌ Adding internal refresh logic
   - ✅ Use existing `loadFolderTree()` callbacks in operation success handlers

### Key Implementation Files

- **filemanager.js** (line 489-622) - Core navigation functions
- **filemanager.js** (line 2309-2313) - Event listeners
- **filemanager.js** (line 1192-1194) - Initial page load
- **filemanager.js** (line 1834-1953) - Tree synchronization

---

## 🎭 Event-Driven Architecture

**CRITICAL PRINCIPLE: Always favor events over direct function calls for component communication.**

### Why Event-Driven Architecture?

WebFTP uses **event-driven architecture** for component communication to achieve:

- ✅ **Decoupling** - Components don't need to know about each other
- ✅ **Extensibility** - Multiple listeners can react to the same event
- ✅ **Maintainability** - Easy to add/remove features without breaking existing code
- ✅ **Testability** - Components can be tested in isolation
- ✅ **Scalability** - New features can listen to existing events

### When to Use Events vs Direct Calls

#### ✅ USE EVENTS When:

1. **Multiple components need to react** to the same action
2. **Components are independent** and shouldn't know about each other
3. **State changes** need to be broadcast to the system
4. **Async operations** complete and other parts need to know
5. **User actions** trigger updates in multiple places

#### ❌ USE DIRECT CALLS When:

1. **Single responsibility** - Only one component handles the action
2. **Parent-child** relationship - Parent directly controls child
3. **Synchronous flow** - Immediate response needed from specific function
4. **Utility functions** - Pure functions with no side effects (e.g., `formatFileSize()`)

### Core Events in WebFTP

#### 1. Navigation Events

**`urlchange`** - Dispatched when URL changes (navigation occurs)

**Location:** `filemanager.js` line ~1143

**When Dispatched:**
- User calls `navigateTo(path, action, skipExpand)`
- URL parameters change

**Event Detail:**
```javascript
{
  skipExpand: boolean  // true = don't auto-expand tree folders
}
```

**Who Listens:**
- `handleUrlChange()` - Updates UI based on new URL

**Example Usage:**
```javascript
// Dispatch (in navigateTo)
window.dispatchEvent(
  new CustomEvent("urlchange", { detail: { skipExpand: false } })
);

// Listen
window.addEventListener("urlchange", handleUrlChange);
```

---

**`popstate`** - Browser back/forward button clicked

**When Dispatched:**
- User clicks browser back/forward buttons
- Browser native event

**Who Listens:**
- `handleUrlChange()` - Syncs UI with URL after browser navigation

**Example Usage:**
```javascript
window.addEventListener("popstate", handleUrlChange);
```

---

#### 2. Loading State Events

**`loadingstart`** - Content loading begins

**Location:** `filemanager.js` line ~2000

**When Dispatched:**
- `loadFolderContents()` starts fetching data

**Event Detail:**
```javascript
{
  path: string  // Folder path being loaded
}
```

**Who Listens:**
- `ToolbarManager` - Hides toolbar during loading

**Example Usage:**
```javascript
// Dispatch
window.dispatchEvent(new CustomEvent("loadingstart", {
  detail: { path }
}));

// Listen (in ToolbarManager constructor)
window.addEventListener("loadingstart", () => this.hideToolbar());
```

---

**`loadingcomplete`** - Content loading finishes

**Location:** `filemanager.js` line ~2051

**When Dispatched:**
- `loadFolderContents()` completes (success or error)
- **IMPORTANT:** Dispatched AFTER callback executes (so toolbar context is set first)

**Event Detail:**
```javascript
{
  path: string,       // Folder path that was loaded
  success: boolean,   // true if load succeeded
  error?: string      // Error message if failed (optional)
}
```

**Who Listens:**
- `ToolbarManager` - Shows toolbar after loading

**Example Usage:**
```javascript
// Dispatch (AFTER callback to ensure context is set first)
if (onComplete) {
  onComplete(data);  // Sets toolbar context
}
window.dispatchEvent(new CustomEvent("loadingcomplete", {
  detail: { path, success: data.success }
}));

// Listen (in ToolbarManager constructor)
window.addEventListener("loadingcomplete", () => this.showToolbar());
```

**Critical Timing:**
```javascript
// ❌ WRONG - Toolbar shows before context is set (causes flash)
window.dispatchEvent(new CustomEvent("loadingcomplete", { ... }));
if (onComplete) {
  onComplete(data);  // Too late! Toolbar already visible
}

// ✅ CORRECT - Context set before toolbar shows
if (onComplete) {
  onComplete(data);  // Sets toolbar context first
}
window.dispatchEvent(new CustomEvent("loadingcomplete", { ... }));
```

---

#### 3. Content Modification Events

**`foldercontentsloaded`** - Folder contents were modified

**Location:** `filemanager.js` line ~1845

**When Dispatched:**
- **ONLY** when operations **MODIFY** folder contents:
  - `createNewFile()` - File created
  - `createNewFolder()` - Folder created
  - `deleteSelected()` - Items deleted
  - `renameSelected()` - Item renamed
  - File upload completes (when implemented)
  - File move completes (when implemented)

**NEVER Dispatched:**
- ❌ `loadFolderContents()` - Read-only operation
- ❌ `displayFileInfo()` - Just viewing file
- ❌ Navigation/browsing - No modifications

**Event Detail:**
```javascript
{
  path: string  // Folder path that was modified
}
```

**Who Listens:**
- Tree refresh listener - Reloads affected folder in sidebar tree

**Example Usage:**
```javascript
// Helper function to dispatch
function notifyFolderContentsChanged(path) {
  window.dispatchEvent(new CustomEvent("foldercontentsloaded", {
    detail: { path: path }
  }));
}

// Call ONLY after modification operations
if (data.success) {
  handleUrlChange();  // Refresh view
  notifyFolderContentsChanged(currentPath);  // Refresh tree
}

// Listen (tree refresh logic)
window.addEventListener("foldercontentsloaded", function(event) {
  const path = event.detail.path;
  // Debounce and refresh tree folder
  // ...
});
```

**Why Manual Dispatch?**

- Prevents unnecessary API calls on read-only operations
- Tree only refreshes when contents actually change
- Performance optimization - no duplicate /api/folder-tree calls

---

### Event Dispatching Patterns

#### Pattern 1: Simple Event (No Data)

```javascript
// Dispatch
window.dispatchEvent(new Event("eventname"));

// Listen
window.addEventListener("eventname", function() {
  // Handle event
});
```

#### Pattern 2: CustomEvent with Data

```javascript
// Dispatch
window.dispatchEvent(new CustomEvent("eventname", {
  detail: {
    key1: value1,
    key2: value2
  }
}));

// Listen
window.addEventListener("eventname", function(event) {
  const data = event.detail;
  console.log(data.key1, data.key2);
});
```

#### Pattern 3: Class-Based Event Listeners

```javascript
class MyManager {
  constructor() {
    // Bind event listeners in constructor
    window.addEventListener("eventname", () => this.handleEvent());
  }

  handleEvent() {
    // Use 'this' to access class properties/methods
    this.doSomething();
  }
}
```

---

### Creating New Events - Best Practices

When adding new functionality, ask yourself:

**Should this be an event?**

1. **Will multiple components care** about this action?
   - Yes → Use event
   - No → Direct function call is fine

2. **Is this a state change** that other parts of the app should know about?
   - Yes → Use event
   - No → Internal implementation detail

3. **Could future features** want to react to this?
   - Yes → Use event (for extensibility)
   - No → Direct call is simpler

**Example Decision Tree:**

```
User clicks "Upload File" button
  ↓
Q: Will upload affect multiple components?
A: Yes - file list, tree sidebar, status bar
  ↓
Q: Could future features react to uploads?
A: Yes - analytics, notifications, cloud sync
  ↓
DECISION: Use event "fileUploaded"
```

---

### Event Naming Conventions

1. **Use present tense** for state changes
   - ✅ `loadingstart`, `loadingcomplete`
   - ❌ `loadingStarted`, `loadingCompleted`

2. **Use past tense** for completed actions
   - ✅ `foldercontentsloaded`, `fileuploaded`
   - ❌ `foldercontentsload`, `fileupload`

3. **Be specific** but not verbose
   - ✅ `urlchange`
   - ❌ `applicationUrlParametersChanged`

4. **Lowercase, no separators** (JavaScript convention)
   - ✅ `loadingstart`
   - ❌ `loading-start`, `loading_start`, `LoadingStart`

---

### Event Documentation Template

When adding new events, document them in this section:

```markdown
**`eventname`** - Brief description

**Location:** `filename.js` line ~XXX

**When Dispatched:**
- Condition 1
- Condition 2

**Event Detail:**
\`\`\`javascript
{
  property1: type,  // Description
  property2: type   // Description
}
\`\`\`

**Who Listens:**
- Component 1 - What it does
- Component 2 - What it does

**Example Usage:**
\`\`\`javascript
// Dispatch example
// Listen example
\`\`\`
```

---

### Common Event Mistakes to Avoid

1. **❌ Using events for synchronous return values**
   ```javascript
   // WRONG
   window.dispatchEvent(new CustomEvent("getusername"));
   window.addEventListener("getusername", (e) => {
     e.detail.username = "john";  // Can't return data like this
   });

   // CORRECT
   function getUsername() {
     return "john";  // Direct function call
   }
   ```

2. **❌ Not providing event detail**
   ```javascript
   // WRONG - Listeners don't know what changed
   window.dispatchEvent(new Event("somethingchanged"));

   // CORRECT - Provide context
   window.dispatchEvent(new CustomEvent("somethingchanged", {
     detail: { what: "filename", value: "test.txt" }
   }));
   ```

3. **❌ Dispatching events inside loops**
   ```javascript
   // WRONG - Performance issue
   files.forEach(file => {
     window.dispatchEvent(new CustomEvent("fileuploaded", { detail: file }));
   });

   // CORRECT - Batch dispatch
   window.dispatchEvent(new CustomEvent("filesuploaded", {
     detail: { files: files }
   }));
   ```

4. **❌ Forgetting to dispatch event after error**
   ```javascript
   // WRONG - loadingcomplete never fires on error
   fetch('/api/data')
     .then(() => {
       window.dispatchEvent(new Event("loadingcomplete"));
     });

   // CORRECT - Always complete loading state
   fetch('/api/data')
     .then(() => {
       window.dispatchEvent(new CustomEvent("loadingcomplete", {
         detail: { success: true }
       }));
     })
     .catch(() => {
       window.dispatchEvent(new CustomEvent("loadingcomplete", {
         detail: { success: false }
       }));
     });
   ```

5. **❌ Dispatching modification events on read operations**
   ```javascript
   // WRONG - Tree refreshes on every folder view
   function loadFolderContents(path) {
     fetch('/api/folder-contents?path=' + path)
       .then(() => {
         window.dispatchEvent(new CustomEvent("foldercontentsloaded", {
           detail: { path }
         }));  // ❌ NO! This is just viewing, not modifying!
       });
   }

   // CORRECT - Only dispatch on modifications
   function createNewFile(path, name) {
     fetch('/api/file/create', { ... })
       .then(() => {
         window.dispatchEvent(new CustomEvent("foldercontentsloaded", {
           detail: { path }
         }));  // ✅ YES! Contents were modified
       });
   }
   ```

---

### Migration Guide: Direct Calls → Events

If you find code using direct function calls where events would be better:

**Before (Tightly Coupled):**
```javascript
function loadFolderContents(path) {
  fetch('/api/folder-contents?path=' + path)
    .then(data => {
      renderListView(data);

      // Direct calls - toolbar knows about loading internals
      window.toolbarManager.hideToolbar();  // ❌ Tight coupling

      // Later...
      window.toolbarManager.showToolbar();  // ❌ Tight coupling
    });
}
```

**After (Event-Driven):**
```javascript
function loadFolderContents(path) {
  // Dispatch event - anyone can listen
  window.dispatchEvent(new CustomEvent("loadingstart", {
    detail: { path }
  }));  // ✅ Decoupled

  fetch('/api/folder-contents?path=' + path)
    .then(data => {
      renderListView(data);

      window.dispatchEvent(new CustomEvent("loadingcomplete", {
        detail: { path, success: true }
      }));  // ✅ Decoupled
    });
}

// ToolbarManager listens independently
class ToolbarManager {
  constructor() {
    window.addEventListener("loadingstart", () => this.hideToolbar());
    window.addEventListener("loadingcomplete", () => this.showToolbar());
  }
}
```

**Benefits:**
- `loadFolderContents()` doesn't need to know about `ToolbarManager`
- Future components (status bar, breadcrumbs) can listen without modifying `loadFolderContents()`
- Easy to add analytics, logging, or other side effects

---

### Event Flow Diagram

```
USER ACTION
    ↓
navigateTo(path)
    ↓
dispatch "urlchange" event
    ↓
handleUrlChange() listens
    ↓
loadFolderContents(path)
    ↓
dispatch "loadingstart" event
    ↓
ToolbarManager hides toolbar
    ↓
Fetch API call
    ↓
Response received
    ↓
Callback executes (sets toolbar context)
    ↓
dispatch "loadingcomplete" event
    ↓
ToolbarManager shows toolbar
    ↓
UI UPDATED
```

---

### Quick Reference: Event Checklist

Before implementing any feature, check:

- [ ] Does this affect multiple components? → Use event
- [ ] Will future features need to know about this? → Use event
- [ ] Is this a state change the system should broadcast? → Use event
- [ ] Is this just a utility/helper function? → Direct call is fine
- [ ] Do I need a synchronous return value? → Direct call required
- [ ] Am I modifying data? → Consider modification event
- [ ] Am I just reading data? → No modification event needed

---

## 🔒 Security Requirements

**Security is CRITICAL. Never compromise security for convenience.**

### Mandatory Security Features

1. **CSRF Protection**
   - Every POST form MUST have CSRF token
   - Tokens are single-use, expire after 1 hour
   - Validate on server-side before processing

2. **Rate Limiting**
   - Max 5 failed login attempts
   - 15-minute lockout on exceeding attempts
   - Track by IP address

3. **Session Security**
   - Session fingerprinting (User-Agent, Accept-Language, Accept-Encoding)
   - IP address validation
   - Regenerate session ID on login
   - HttpOnly, Secure, SameSite=Strict cookies

4. **Input Validation**
   - Sanitize ALL user input
   - Use `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` for output
   - Validate file paths (no directory traversal)
   - Maximum input lengths enforced

5. **Security Headers**
   - X-Frame-Options: DENY
   - X-Content-Type-Options: nosniff
   - Content-Security-Policy (strict)
   - X-XSS-Protection
   - Referrer-Policy

6. **SSRF Prevention**
   - Single FTP server configured in `config.php`
   - Users CANNOT specify custom FTP hosts
   - Prevents Server-Side Request Forgery attacks

7. **Password Handling**
   - FTP credentials stored in encrypted session only
   - Never logged in plaintext
   - Session destroyed on logout

### What to REFUSE
- ❌ Disabling CSRF protection
- ❌ Allowing user-specified FTP hosts
- ❌ Storing passwords in cookies or localStorage
- ❌ Eval() or similar dangerous functions
- ❌ SQL injection (we don't use databases, but principle applies)
- ❌ Path traversal vulnerabilities

---

## ⚡ Performance Requirements

**Performance is CRITICAL. Optimize for speed.**

### Performance Rules

1. **Minimize HTTP Requests**
   - Use CDN for external resources (TailwindCSS, Font Awesome)
   - Single CSS/JS files where possible
   - No unnecessary API calls

2. **Efficient Code**
   - No N+1 queries
   - Cache when appropriate (session-based)
   - Lazy load when possible

3. **Asset Loading**
   - Load CSS in `<head>`
   - Load JS at end of `<body>` or use defer/async
   - Use CDN integrity hashes (SRI)

4. **Session Management**
   - Keep session data minimal
   - Destroy sessions properly on logout

5. **Compression**
   - Enable gzip compression (configured in `config.php`)

---

## 🌍 Internationalization (i18n)

**ALL text visible to users MUST be translatable.**

### Translation Rules

1. **Language Files** (`src/Languages/*.php`)
   - Each language has its own file (e.g., `en.php`, `fr.php`)
   - Return associative array of translations
   - Keys in English, snake_case format
   - Example:
     ```php
     return [
         'login_title' => 'Sign In',
         'username_label' => 'FTP Username',
     ];
     ```

2. **Available Languages**
   - Defined in `config.php` → `localization.available_languages`
   - **IMPORTANT: Only maintain English (en.php) and French (fr.php) language files**
   - Other language files (es, de, it, pt, ar) exist but are NOT maintained
   - All new translations must be added to BOTH en.php and fr.php

3. **Using Translations in Views**
   - **Use the global helper functions from ViewHelpers.php:**
   ```php
   <?= t('key_name', 'Default') ?>  // For translations
   <?= e($variable) ?>              // For escaping any value
   ```
   - Helper functions are automatically available in all views

4. **Language Storage**
   - Stored in **COOKIE** (not localStorage)
   - Cookie name: `config.php` → `localization.language_cookie_name`
   - Cookie lifetime: 1 year
   - PHP reads cookie automatically (server-side rendering)

5. **Adding New Translatable Text**
   - Add key to ALL language files (en.php, fr.php, etc.)
   - Use descriptive keys (e.g., `error_invalid_credentials`)
   - Never hardcode user-facing text

### Translation Loading
```php
use WebFTP\Core\Language;

$language = $_COOKIE['webftp_language'] ?? 'en';
$lang = new Language($language, $config);
$translations = $lang->all();
```

---

## ⚙️ Configuration Management

**ALL constants, settings, and configuration MUST be in `config/config.php`.**

### Configuration Sections

1. **Application Settings** (`app`)
   - App name, version, environment
   - Timezone, charset

2. **Localization** (`localization`)
   - Default language
   - Available languages array
   - Language cookie name and lifetime

3. **UI/Theming** (`ui`)
   - Default theme (light/dark)
   - Available themes
   - Theme cookie name and lifetime

4. **Security** (`security`)
   - Session configuration
   - CSRF settings
   - Rate limiting
   - Security headers
   - Input validation limits

5. **FTP Server** (`ftp`)
   - Single trusted FTP server (host, port, SSL, passive mode)
   - Connection timeouts
   - Operation timeouts

6. **Logging** (`logging`)
   - Enable/disable logging
   - Log level
   - Log file path
   - Log authentication attempts

7. **Performance** (`performance`)
   - Compression settings
   - Caching (future)

### Configuration Rules

1. **NEVER hardcode values** in code
   - ❌ `$availableLanguages = ['en', 'fr', 'es'];`
   - ✅ `$availableLanguages = $config['localization']['available_languages'];`

2. **Access config values**
   ```php
   $value = $config['section']['key'];
   ```

3. **Validate config on startup**
   - Use `ConfigValidator` to check critical settings
   - Fail fast if configuration is invalid

4. **Pass config to constructors**
   ```php
   public function __construct(array $config) {
       $this->config = $config;
   }
   ```

---

## 📝 Logging System

**ALL logging MUST use the centralized Logger class. Never create internal logging methods.**

### Logger Usage Rules

1. **ALWAYS Use the Global Logger Class**
   ```php
   use WebFTP\Core\Logger;
   ```
   - ❌ **NEVER** create private logging methods (`logDebug()`, `writeLog()`, etc.)
   - ❌ **NEVER** use `error_log()` directly (except within Logger class itself)
   - ✅ **ALWAYS** use the Logger class static methods

2. **Available Log Levels**
   ```php
   Logger::debug($message, $context);    // Development debugging
   Logger::info($message, $context);     // Informational messages
   Logger::warning($message, $context);  // Warning conditions
   Logger::error($message, $context);    // Error conditions
   Logger::critical($message, $context); // Critical conditions
   ```

3. **Specialized Logging Methods**

   **Authentication Logging:**
   ```php
   // Log authentication attempts
   Logger::auth($username, $action, $success, $ip);

   // Examples:
   Logger::auth($username, 'login', true, $clientIp);   // Successful login
   Logger::auth($username, 'login', false, $clientIp);  // Failed login
   Logger::auth($username, 'logout', true, $clientIp);  // Logout
   ```

   **FTP Operations Logging:**
   ```php
   // Log FTP operations
   Logger::ftp($operation, $details, $success);

   // Examples:
   Logger::ftp("connect", ['host' => $host, 'port' => $port], true);
   Logger::ftp("upload", ['file' => $filename, 'size' => $size], false);
   Logger::ftp("getFolderTree", ['path' => $path, 'depth' => $depth]);
   ```

4. **Context and Details**
   - Always provide relevant context in the second parameter
   - Context should be an associative array with descriptive keys
   ```php
   // Good - Provides context
   Logger::debug("User action performed", [
       'user' => $username,
       'action' => 'file_upload',
       'file' => $filename,
       'size' => $filesize
   ]);

   // Bad - No context
   Logger::debug("User action performed");
   ```

5. **Log Levels by Environment**
   - **Development**: All levels (DEBUG and above)
   - **Production**: ERROR and above only
   - Controlled by `config.php` → `logging.level`

6. **Performance Considerations**
   - Logger checks if logging is enabled before processing
   - Log level filtering happens automatically
   - File I/O is suppressed with `@` to prevent errors

7. **Log Rotation**
   ```php
   // Check and rotate logs if needed (10MB default)
   Logger::rotate();

   // Clear logs (admin function)
   Logger::clear();

   // Get log file size
   $size = Logger::getSize();
   ```

### What NOT to Log

1. **Sensitive Information**
   - ❌ Passwords (even hashed)
   - ❌ Full credit card numbers
   - ❌ Session tokens
   - ❌ API keys or secrets
   - ✅ Usernames (for audit trail)
   - ✅ IP addresses (for security)

2. **Excessive Logging**
   - ❌ Every loop iteration
   - ❌ Every variable assignment
   - ✅ Key decision points
   - ✅ Error conditions
   - ✅ Security events

### Migration from Internal Logging

If you find code with internal logging:

**Before (Wrong):**
```php
private function logDebug($message, $context = []) {
    error_log("[DEBUG] " . $message . json_encode($context));
}
$this->logDebug("Operation failed", ['error' => $error]);
```

**After (Correct):**
```php
use WebFTP\Core\Logger;

Logger::debug("Operation failed", ['error' => $error]);
```

### Logger Configuration

Configure in `config/config.php`:
```php
'logging' => [
    'enabled' => true,
    'level' => 'ERROR',  // DEBUG, INFO, WARNING, ERROR, CRITICAL
    'log_path' => __DIR__ . '/../logs/app.log',
    'log_auth_attempts' => true,
    'log_ftp_operations' => true,
],
```

---

## 🎨 UI/UX Guidelines

### Theme Support (Dark/Light Mode)

1. **Theme Storage**
   - Stored in **localStorage** (client-side only)
   - No server-side rendering needed for theme
   - Apply immediately to prevent flash

2. **Implementing Theme Support**
   - All elements MUST have both light and dark variants
   - Use Tailwind's `dark:` prefix
   - Example:
     ```html
     <div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
     ```

3. **Common Theme Classes**
   - Background: `bg-white dark:bg-gray-800`
   - Text: `text-gray-900 dark:text-white`
   - Borders: `border-gray-200 dark:border-gray-700`
   - Secondary text: `text-gray-600 dark:text-gray-400`

4. **Theme Switching**
   ```javascript
   function switchTheme(theme) {
       if (theme === 'dark') {
           document.documentElement.classList.add('dark');
       } else {
           document.documentElement.classList.remove('dark');
       }
       localStorage.setItem('theme', theme);
   }
   ```

5. **Immediate Theme Application**
   ```javascript
   (function() {
       const savedTheme = localStorage.getItem('theme') || 'dark';
       if (savedTheme === 'dark') {
           document.documentElement.classList.add('dark');
       }
   })();
   ```

### Icon Usage

1. **NEVER use inline SVGs**
   - ❌ `<svg class="w-5 h-5">...</svg>`
   - ✅ `<i class="fas fa-user"></i>`

2. **Font Awesome Only**
   - Use Font Awesome 6.5.1 CDN
   - Include integrity hash for security
   - Common icons:
     - Folder: `fa-folder`
     - File: `fa-file`
     - User: `fa-user`
     - Lock: `fa-lock`
     - Upload: `fa-upload`
     - Download: `fa-download`
     - Trash: `fa-trash`
     - Edit: `fa-pen`

3. **Icon Sizing**
   - Use Font Awesome sizes or Tailwind text sizes
   - `text-sm`, `text-lg`, `text-2xl`, `text-6xl`

### Styling

1. **TailwindCSS CDN Only**
   - No custom CSS files
   - Use utility classes only
   - Configure Tailwind in `<script>` tag

2. **No External JavaScript Libraries**
   - ❌ Alpine.js, jQuery, React, Vue
   - ✅ Vanilla JavaScript only
   - Keep JavaScript minimal and clean

3. **Responsive Design**
   - Mobile-first approach
   - Use Tailwind breakpoints: `sm:`, `md:`, `lg:`, `xl:`
   - Test on multiple screen sizes

---

## 💻 Code Standards

### PHP Standards

1. **Version Compatibility**
   - PHP 8.0+ compatible
   - NO PHP 8.1+ exclusive features:
     - ❌ `readonly` keyword
     - ❌ Enums
     - ❌ First-class callable syntax
   - ✅ Use PHP 8.0 features:
     - Constructor property promotion
     - Named arguments
     - Match expressions
     - Nullsafe operator

2. **Strict Types**
   ```php
   <?php
   declare(strict_types=1);
   ```
   - ALWAYS use strict types at top of every PHP file

3. **Type Hints**
   - Always type hint function parameters and return types
   ```php
   public function getUserData(string $username): array
   ```

4. **Namespaces**
   - Use PSR-4 autoloading
   - Namespace: `WebFTP\{Component}`
   - Example: `WebFTP\Controllers\AuthController`

5. **Error Handling**
   - Use try-catch blocks
   - Log errors appropriately
   - Never expose sensitive information in errors

6. **Security Functions**
   - Always use:
     - `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` for output
     - `password_hash()` / `password_verify()` for passwords (if needed)
     - `bin2hex(random_bytes())` for tokens

### JavaScript Standards

1. **Vanilla JavaScript Only**
   - No frameworks or libraries
   - Use modern ES6+ syntax
   - Keep code minimal

2. **Event Handling**
   ```javascript
   document.addEventListener('DOMContentLoaded', function() {
       // Your code
   });
   ```

3. **Storage**
   - Theme: `localStorage`
   - Language: `cookie` (accessible by PHP and JS)
   - Never store sensitive data in localStorage or cookies

4. **Cookie Management**
   ```javascript
   document.cookie = 'name=value; path=/; max-age=31536000; SameSite=Strict';
   ```

### HTML Standards

1. **Semantic HTML**
   - Use proper semantic tags: `<header>`, `<nav>`, `<main>`, `<footer>`
   - Use `<button>` for actions, `<a>` for navigation

2. **Accessibility**
   - Use proper ARIA labels when needed
   - Ensure keyboard navigation works
   - Maintain proper contrast ratios

3. **Form Standards**
   - Always include CSRF token in POST forms
   - Use proper input types (email, password, tel, etc.)
   - Include autocomplete attributes

---

## 📁 File Structure

```
webftp/
├── config/
│   └── config.php              # ALL configuration constants
├── public/
│   ├── index.php               # Entry point, routing
│   └── favicon.svg             # Favicon
├── src/
│   ├── Controllers/
│   │   ├── AuthController.php  # Login, logout, authentication
│   │   └── DashboardController.php  # File manager, theme, language
│   ├── Core/
│   │   ├── Router.php          # URL routing
│   │   ├── Request.php         # HTTP request handling
│   │   ├── Response.php        # HTTP response handling
│   │   ├── SecurityManager.php # Security utilities
│   │   ├── CsrfToken.php       # CSRF protection
│   │   ├── Language.php        # Translation system
│   │   └── ConfigValidator.php # Config validation
│   ├── Models/
│   │   ├── Session.php         # Session management
│   │   └── FtpConnection.php   # FTP operations
│   ├── Views/
│   │   ├── login.php           # Login page
│   │   └── filemanager.php     # File manager page
│   └── Languages/
│       ├── en.php              # English translations
│       ├── fr.php              # French translations
│       ├── es.php              # Spanish translations
│       ├── de.php              # German translations
│       ├── it.php              # Italian translations
│       ├── pt.php              # Portuguese translations
│       └── ar.php              # Arabic translations
├── logs/
│   └── app.log                 # Application logs
├── ssl/                        # SSL certificates (dev)
├── CLAUDE.md                   # This file - development guidelines
└── README.md                   # Project documentation
```

---

## 🚀 Development Workflow

### Adding New Features

1. **Check if it affects configuration**
   - Add constants to `config/config.php`

2. **Check if it has user-facing text**
   - Add translations to ALL language files

3. **Check if it needs styling**
   - Implement both light and dark mode variants

4. **Check security implications**
   - Validate input
   - Check for CSRF protection
   - Review for vulnerabilities

5. **Test thoroughly**
   - Test all languages
   - Test both themes
   - Test on multiple browsers
   - Test security features

### Adding New Language

1. Create `src/Languages/{code}.php`
   ```php
   <?php
   declare(strict_types=1);
   return [
       'login_title' => 'Translated Text',
       // ... all keys
   ];
   ```

2. Add to `config.php`:
   ```php
   'localization' => [
       'available_languages' => ['en', 'fr', 'es', 'de', 'it', 'pt', 'ar', 'NEW'],
   ],
   ```

3. Add flag emoji to language dropdown in `filemanager.php`

### Adding New Configuration

1. Add to appropriate section in `config/config.php`
2. Update `ConfigValidator` if critical
3. Update code to use config value (remove hardcoded values)
4. Document in this file

---

## 🔄 Asset Version Management

**CRITICAL: Always increment the asset version when modifying JavaScript or CSS files!**

### When to Update Asset Version

1. **ALWAYS increment** `config.php` → `app.asset_version` when:
   - Modifying any JavaScript file (`.js`)
   - Modifying any CSS file (`.css`)
   - Rebuilding CodeMirror bundle
   - Adding/removing script dependencies

2. **Version Format**: `major.minor.patch`
   - **Patch** (1.0.0 → 1.0.1): Bug fixes, small changes
   - **Minor** (1.0.0 → 1.1.0): New features, significant updates
   - **Major** (1.0.0 → 2.0.0): Breaking changes, major refactors

### How to Update

```php
// In config/config.php
'app' => [
    // ...
    'asset_version' => '1.0.1', // ← INCREMENT THIS
],
```

### Why This Matters

- **Production Performance**: Browsers cache JS/CSS files for faster loading
- **Cache Busting**: Version changes force browsers to download new versions
- **No time()**: Using `time()` forces re-download on EVERY page load (bad for performance)

### Workflow

1. Make changes to JS/CSS files
2. **BEFORE committing**: Update `asset_version` in `config.php`
3. Test changes (browser will load new version)
4. Document version change in commit message

### Example

```bash
# Wrong - Forgot to update version
✗ Modified integrated-editor.js
✗ User's browser uses cached old version
✗ Bug appears to not be fixed

# Correct - Updated version
✓ Modified integrated-editor.js
✓ Updated asset_version from 1.0.0 to 1.0.1
✓ User's browser downloads new version
✓ Bug is fixed for everyone
```

---

## ❌ Common Mistakes to Avoid

1. **DO NOT create internal logging methods**
   - ❌ `private function logDebug($msg) { error_log($msg); }`
   - ✅ `Logger::debug($msg, $context);`

2. **DO NOT use error_log() directly**
   - ❌ `error_log("Failed login from " . $ip);`
   - ✅ `Logger::auth($username, 'login', false, $ip);`

3. **DO NOT forget to update asset_version**
   - ❌ Modify JS/CSS without updating version
   - ✅ Always increment `asset_version` in `config.php`

4. **DO NOT use time() for cache busting**
   - ❌ `<script src="file.js?v=<?= time() ?>">`
   - ✅ `<script src="file.js?v=<?= $asset_version ?>">`

5. **DO NOT use setTimeout for async operations (CRITICAL UX)**
   - ❌ **NEVER** use `setTimeout()` to wait for async operations to complete
   - ❌ **NEVER** use arbitrary delays (100ms, 500ms, 800ms) - causes visible lag
   - ✅ **ALWAYS** use callbacks or promises for instant UX
   ```javascript
   // ❌ BAD - Visible delay, poor UX
   loadFolderContents(path);
   setTimeout(() => {
     highlightCurrentPath(path);
   }, 500); // User sees 500ms lag!

   // ✅ ELITE - Instant, professional UX
   loadFolderContents(path, false, (data) => {
     highlightCurrentPath(path); // Executes instantly when ready!
     displayFilePreview(data);   // Uses real loaded data!
   });
   ```
   - **Why this matters**:
     - `setTimeout()` creates artificial delays that frustrate users
     - Callbacks execute instantly when operation completes
     - No guessing if 500ms is enough (might be too short or too long)
     - Professional apps NEVER make users wait unnecessarily
   - **When to use callbacks**:
     - After API calls complete
     - After DOM operations finish
     - After file loads/saves
     - After tree expansion/collapse
   - **Function signature pattern**:
     ```javascript
     function asyncOperation(params, onComplete = null) {
       // ... do async work ...
       if (onComplete) {
         onComplete(result); // Call immediately when done
       }
     }
     ```

6. **DO NOT hardcode text**
   - ❌ `echo "Sign In";`
   - ✅ `echo htmlspecialchars($translations['login_title'], ENT_QUOTES, 'UTF-8');`

7. **DO NOT hardcode configuration**
   - ❌ `$languages = ['en', 'fr'];`
   - ✅ `$languages = $config['localization']['available_languages'];`

8. **DO NOT use PHP 8.1+ features**
   - ❌ `public readonly string $name;`
   - ✅ `private string $name;`

9. **DO NOT store language in localStorage**
   - ❌ `localStorage.setItem('language', 'fr');`
   - ✅ `document.cookie = 'webftp_language=fr; ...';`

10. **DO NOT forget dark mode**
   - ❌ `<div class="bg-white text-black">`
   - ✅ `<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white">`

11. **DO NOT use inline SVGs**
   - ❌ `<svg>...</svg>`
   - ✅ `<i class="fas fa-icon"></i>`

12. **DO NOT skip CSRF protection**
   - ❌ Form without token
   - ✅ Form with `<input type="hidden" name="_csrf_token" value="...">`

13. **DO NOT add external libraries without asking**
   - ❌ Adding jQuery, Alpine.js, etc.
   - ✅ Use vanilla JavaScript

---

## 📝 Code Review Checklist

Before submitting/completing any changes, verify:

- [ ] All configuration is in `config.php`
- [ ] All text is translatable (added to all language files)
- [ ] Dark and light mode both work
- [ ] No hardcoded values
- [ ] PHP 8.0+ compatible (no 8.1+ features)
- [ ] CSRF protection on all forms
- [ ] Input validation and sanitization
- [ ] No inline SVGs (Font Awesome icons used)
- [ ] No external JS libraries (vanilla JS only)
- [ ] Security headers configured correctly
- [ ] Code follows MVC pattern
- [ ] Performance optimized
- [ ] Responsive design works on mobile

---

## 🎓 Learning Resources

### Key Technologies
- **PHP 8.0**: [Official Documentation](https://www.php.net/manual/en/)
- **TailwindCSS**: [Official Docs](https://tailwindcss.com/docs)
- **Font Awesome**: [Icon Search](https://fontawesome.com/icons)
- **FTP Functions**: [PHP FTP Reference](https://www.php.net/manual/en/book.ftp.php)

### Security Resources
- **OWASP Top 10**: Security vulnerabilities to avoid
- **PHP Security**: Best practices for PHP security
- **Session Security**: Secure session management

---

## 📞 Support

For questions or clarifications about these guidelines:
1. Review this document thoroughly
2. Check existing code for examples
3. Follow the established patterns
4. When in doubt, prioritize security and simplicity

---

**Last Updated**: 2025-01-17
**Version**: 2.0.0
**Maintainer**: Claude (Anthropic AI Assistant)

---

## 🔄 Change Log

- **2025-01-20**: Added Event-Driven Architecture Documentation
  - Documented all core events (urlchange, loadingstart, loadingcomplete, foldercontentsloaded)
  - Added comprehensive guide: when to use events vs direct calls
  - Included event dispatching patterns and naming conventions
  - Added migration guide from direct calls to events
  - Common mistakes to avoid with events
  - Event flow diagrams and decision trees
  - Quick reference checklist for event usage
  - Event documentation template for future additions

- **2025-01-20**: Added URL Source of Truth Architecture Documentation
  - Documented the URL-driven architecture pattern used by file manager
  - Explained core functions: navigateTo() and handleUrlChange()
  - Added practical examples of URL navigation flow
  - Included developer rules for maintaining URL as single source of truth
  - Added to Architecture section for easy reference

- **2025-01-18**: Added Centralized Logger System
  - Created professional Logger class for application-wide debugging
  - Added comprehensive logging documentation
  - Replaced all internal logging methods with global Logger
  - Added specialized logging methods for auth and FTP operations
  - Updated all controllers and models to use Logger

- **2025-01-18**: Added Asset Version Management
  - Implemented proper cache busting with version control
  - Replaced time() with asset_version for better performance
  - Added rules for incrementing version when assets change
  - Updated Common Mistakes section

- **2025-01-17**: Initial creation of CLAUDE.md with comprehensive guidelines
  - Documented MVC architecture rules
  - Added security requirements
  - Added internationalization guidelines
  - Added configuration management rules
  - Added UI/UX guidelines for theming
  - Added code standards and best practices
  - Added file structure documentation