# Security Policy

## Supported Versions

We release patches for security vulnerabilities. Currently supported versions:

| Version | Supported          |
| ------- | ------------------ |
| 2.0.x   | :white_check_mark: |
| < 2.0   | :x:                |

## Security Features

WebCodeFTP is built with security as a top priority and includes the following security features:

### Authentication & Session Security
- ✅ **Session Fingerprinting** - User-Agent, Accept-Language, Accept-Encoding validation
- ✅ **IP Address Validation** - Session tied to client IP
- ✅ **Session Regeneration** - New session ID on login to prevent session fixation
- ✅ **Secure Cookies** - HttpOnly, Secure, SameSite=Strict flags
- ✅ **Automatic Timeout** - 1-hour session lifetime (configurable)

### CSRF Protection
- ✅ **CSRF Tokens** - All POST requests require valid CSRF token
- ✅ **Single-Use Tokens** - Tokens expire after use or timeout
- ✅ **Token Validation** - Server-side validation before processing requests

### Rate Limiting
- ✅ **Brute Force Protection** - 5 failed login attempts = 15-minute lockout
- ✅ **IP-Based Tracking** - Rate limiting tracked per IP address

### Input Validation & Output Encoding
- ✅ **Input Sanitization** - All user input validated and sanitized
- ✅ **Output Encoding** - `htmlspecialchars()` with ENT_QUOTES on all output
- ✅ **Path Traversal Protection** - Directory escape attempts blocked
- ✅ **File Type Validation** - Only allowed file extensions accepted

### SSRF Prevention
- ✅ **Single FTP Server** - Only configured FTP server allowed (no user-specified hosts)
- ✅ **Configuration-Based** - FTP server defined in `config/config.php` only

### Security Headers
- ✅ **X-Frame-Options: DENY** - Prevents clickjacking
- ✅ **X-Content-Type-Options: nosniff** - Prevents MIME sniffing
- ✅ **X-XSS-Protection: 1; mode=block** - XSS filter enabled
- ✅ **Content-Security-Policy** - Restricts resource loading
- ✅ **Referrer-Policy** - Strict origin policy
- ✅ **Permissions-Policy** - Disables unnecessary browser features

### Credential Protection
- ✅ **No Storage** - FTP passwords never logged or stored permanently
- ✅ **Session-Only** - Credentials encrypted in session, destroyed on logout
- ✅ **No Console Logging** - Sensitive data never logged to console

## Reporting a Vulnerability

**Please do NOT report security vulnerabilities through public GitHub issues.**

If you discover a security vulnerability in WebCodeFTP, please report it by emailing:

**aoulmderat@gmail.com**

Please include the following information:

- Type of vulnerability
- Full paths of source file(s) related to the manifestation of the vulnerability
- The location of the affected source code (tag/branch/commit or direct URL)
- Any special configuration required to reproduce the issue
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit it

### What to Expect

- **Acknowledgment**: We will acknowledge receipt of your vulnerability report within 48 hours
- **Updates**: We will send you regular updates on our progress
- **Verification**: We will verify the vulnerability and determine its severity
- **Fix**: We will work on a fix and release it as soon as possible
- **Credit**: We will credit you for the discovery (if you wish) in the release notes

### Safe Harbor

We support responsible disclosure and will not take legal action against researchers who:

- Make a good faith effort to avoid privacy violations, data destruction, and service interruption
- Only interact with accounts you own or with explicit permission of the account holder
- Do not exploit a vulnerability beyond what is necessary to demonstrate it
- Report vulnerabilities promptly
- Give us reasonable time to fix the vulnerability before public disclosure

## Security Best Practices for Users

### Installation
1. **HTTPS Only** - Always use HTTPS in production (set `cookie_secure => true`)
2. **Secure Permissions** - Set proper file permissions:
   ```bash
   chmod 755 logs/
   chmod 644 config/config.php
   ```
3. **Outside Web Root** - Keep `config/config.php` outside public directory if possible
4. **Environment Variables** - Consider using environment variables for sensitive config

### Configuration
1. **Change Session Name** - Use a custom session name in `config/config.php`
2. **Strong Session Lifetime** - Keep session lifetime reasonable (default: 1 hour)
3. **Enable Rate Limiting** - Keep rate limiting enabled (default: 5 attempts, 15-min lockout)
4. **Disable Display Errors** - Set `environment => 'production'` in production

### FTP/SSH Credentials
1. **Strong Passwords** - Use strong, unique passwords for FTP/SSH
2. **SSH Keys** - Prefer SSH key authentication over passwords when possible
3. **Limited Access** - Use FTP/SSH accounts with minimal required permissions
4. **Regular Rotation** - Rotate credentials regularly

### Server Hardening
1. **Update PHP** - Keep PHP updated (8.0+ required, latest recommended)
2. **Disable Functions** - Disable dangerous PHP functions if not needed:
   ```ini
   disable_functions = exec,passthru,shell_exec,system,proc_open,popen
   ```
3. **PHP Extensions** - Only enable required extensions (`ftp`, `session`, `zlib`, `ssh2`)
4. **Firewall** - Use firewall to restrict access to FTP/SSH ports
5. **Fail2ban** - Consider using fail2ban for additional brute force protection

### Monitoring
1. **Check Logs** - Regularly review `logs/app.log` for suspicious activity
2. **Failed Logins** - Monitor failed login attempts
3. **Session Anomalies** - Watch for unusual session behavior

### Updates
1. **Stay Updated** - Keep WebCodeFTP updated to latest version
2. **Review Changelogs** - Read security notes in release changelogs
3. **Test Updates** - Test updates in staging before production deployment

## Known Security Considerations

### PHP Extensions
- **php-ssh2** - Optional but provides better performance for advanced operations
- **php-ftp** - Required for FTP connections
- Ensure extensions are from trusted sources and kept updated

### Third-Party Assets
- **TailwindCSS CDN** - Loaded from official CDN with SRI hash
- **CodeMirror** - Loaded from esm.sh with version pinning
- **Font Awesome** - Loaded from official CDN with SRI hash
- All CDN resources use Subresource Integrity (SRI) verification

### Browser Support
- Modern browsers with JavaScript enabled required
- CSP headers may require configuration for custom setups
- Cookies and session storage must be enabled

## Security Disclosure History

No security vulnerabilities have been publicly disclosed at this time.

## Contact

For security-related inquiries:

- **Email**: aoulmderat@gmail.com
- **Response Time**: Within 48 hours
- **PGP Key**: Available upon request

For general questions, use GitHub Issues (non-security only).

---

**Last Updated**: 2025-01-20
**Version**: 2.0.0
