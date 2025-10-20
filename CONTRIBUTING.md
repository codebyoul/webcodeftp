# Contributing to WebCodeFTP

Thank you for considering contributing to WebCodeFTP! We welcome contributions from the community to help improve this project.

## Code of Conduct

This project adheres to a [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code.

## How to Contribute

### Reporting Bugs

Before creating bug reports, please check existing issues to avoid duplicates. When creating a bug report, include:

- Clear and descriptive title
- Detailed steps to reproduce
- Expected vs actual behavior
- PHP version, web server, and browser information
- Error logs from `logs/app.log` or browser console
- Screenshots if applicable

Use the [Bug Report template](.github/ISSUE_TEMPLATE/bug_report.yml) when creating issues.

### Suggesting Features

Feature requests are welcome! When suggesting features, please:

- Use the [Feature Request template](.github/ISSUE_TEMPLATE/feature_request.yml)
- Provide clear use cases
- Explain why this feature would be useful
- Consider alternative approaches

### Pull Requests

1. **Fork the repository** and create a new branch from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Follow the development guidelines**:
   - PHP 8.0+ compatible (no PHP 8.1+ exclusive features)
   - Follow PSR-12 coding standards
   - Use strict types (`declare(strict_types=1)`)
   - All text must be translatable (add to language files)
   - Implement both dark and light mode support
   - No external JavaScript libraries (vanilla JS only)
   - Use Font Awesome icons (no inline SVGs)

3. **Code formatting**:
   ```bash
   # Install PHP-CS-Fixer if not already installed
   composer global require friendsofphp/php-cs-fixer

   # Format your code
   php-cs-fixer fix
   ```

4. **Test your changes**:
   - Test on PHP 8.0, 8.1, 8.2, 8.3
   - Test on multiple browsers (Chrome, Firefox, Safari, Edge)
   - Test both dark and light themes
   - Test all language translations
   - Ensure no console errors

5. **Update asset version** if you modified JS/CSS files:
   ```php
   // In config/config.php
   'app' => [
       'asset_version' => '1.0.1', // Increment this
   ],
   ```

6. **Commit your changes**:
   ```bash
   git add .
   git commit -m "feat: Add your feature description"
   ```

   Use conventional commit messages:
   - `feat:` New feature
   - `fix:` Bug fix
   - `docs:` Documentation changes
   - `style:` Code formatting
   - `refactor:` Code refactoring
   - `perf:` Performance improvements
   - `test:` Adding tests
   - `chore:` Maintenance tasks

7. **Push to your fork**:
   ```bash
   git push origin feature/your-feature-name
   ```

8. **Create a Pull Request** using the [PR template](.github/PULL_REQUEST_TEMPLATE.md)

## Development Setup

### Requirements

- PHP 8.0+ with extensions: `ftp`, `session`, `zlib`, `ssh2` (optional)
- Composer
- Web server (Apache or Nginx)

### Installation

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/webcodeftp.git
cd webcodeftp

# Install dependencies
composer install

# Configure FTP settings
cp config/config.php.example config/config.php
nano config/config.php

# Set permissions
chmod 755 logs/
chmod 644 config/config.php

# Point your web server to public/ directory
```

### Running Code Quality Checks

```bash
# PHP syntax check
find . -path ./vendor -prune -o -name "*.php" -print0 | xargs -0 -n1 php -l

# PHP-CS-Fixer (check)
php-cs-fixer fix --dry-run --diff

# PHP-CS-Fixer (fix)
php-cs-fixer fix

# Composer security audit
composer audit
```

## Architecture Guidelines

WebCodeFTP follows a strict MVC architecture with these principles:

### MVC Pattern

- **Models** (`src/Models/`): Handle FTP connections, session management, business logic
- **Views** (`src/Views/`): PHP templates with HTML/CSS, minimal logic
- **Controllers** (`src/Controllers/`): Handle requests, coordinate Models and Views

### Security First

- **CSRF protection**: All POST forms must include CSRF token
- **Input validation**: Sanitize and validate all user input
- **Rate limiting**: Implement brute force protection
- **Session security**: Use fingerprinting and IP validation
- **No SSRF**: Only configured FTP server allowed
- **Security headers**: CSP, X-Frame-Options, X-XSS-Protection

### Configuration

- **All constants in `config/config.php`**: Never hardcode values
- **No hardcoded text**: Use translation system
- **Theme support**: Implement both light and dark modes

### Performance

- **Minimize HTTP requests**: Use CDN for external resources
- **Efficient code**: No N+1 queries, cache when appropriate
- **Compression**: Enable gzip in production

### Internationalization

- **Translatable text**: All user-facing text in language files
- **Currently maintained**: English (en.php) and French (fr.php)
- **Language files**: `src/Languages/*.php`
- **Cookie storage**: Language stored in cookie (not localStorage)

## Code Style

### PHP

```php
<?php

declare(strict_types=1);

namespace WebCodeFTP\Controllers;

use WebCodeFTP\Core\Request;
use WebCodeFTP\Core\Response;

class ExampleController
{
    public function __construct(
        private array $config,
        private Request $request,
        private Response $response
    ) {
    }

    public function index(): void
    {
        // Implementation
    }
}
```

### JavaScript

```javascript
// Use vanilla JavaScript only
function exampleFunction(param) {
  const element = document.getElementById('example');
  element.addEventListener('click', () => {
    // Handle click
  });
}

// DOM ready
document.addEventListener('DOMContentLoaded', function() {
  exampleFunction('value');
});
```

### HTML/CSS

```html
<!-- Use Tailwind CSS utility classes -->
<!-- Implement both dark and light mode -->
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
  <!-- Use Font Awesome icons -->
  <i class="fas fa-file text-blue-500 dark:text-blue-400"></i>

  <!-- Translatable text -->
  <?= htmlspecialchars($translations['key_name'], ENT_QUOTES, 'UTF-8') ?>
</div>
```

## Documentation

- Update [README.md](README.md) for user-facing changes
- Add comments for complex logic
- Update translation files for new text

## Translation Guidelines

When adding new user-facing text:

1. **Add to English** (`src/Languages/en.php`):
   ```php
   'new_key' => 'English text',
   ```

2. **Add to French** (`src/Languages/fr.php`):
   ```php
   'new_key' => 'Texte français',
   ```

3. **Use in views**:
   ```php
   <?= htmlspecialchars($translations['new_key'], ENT_QUOTES, 'UTF-8') ?>
   ```

## Questions?

- Check existing code for examples
- Open a discussion on GitHub
- Email: aoulmderat@gmail.com

## Support the Project

If you find this project useful, consider:

- ⭐ Starring the repository
- ☕ [Ko-fi](https://ko-fi.com/codebyoul) - Buy me a coffee
- 💖 [GitHub Sponsors](https://github.com/sponsors/codebyoul) - Become a sponsor
- 💳 [PayPal](https://www.paypal.com/donate/?hosted_button_id=5AX5S82LDZQ8N) - One-time donation

Thank you for contributing to WebCodeFTP!
