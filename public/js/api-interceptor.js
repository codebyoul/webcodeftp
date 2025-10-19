// WebFTP API Interceptor
// Global fetch wrapper that handles 401 responses and redirects to login
// MUST be loaded FIRST before any other JavaScript files

/**
 * ============================================================================
 * GLOBAL FETCH INTERCEPTOR - 401 Auto-Redirect
 * ============================================================================
 *
 * This intercepts ALL fetch() calls in the application and checks for 401
 * Unauthorized responses. When a 401 is detected, it automatically redirects
 * to the login page, preventing users from staying on protected pages with
 * expired sessions.
 *
 * Usage: Just include this file first - all fetch calls will be intercepted.
 */

(function() {
  // Store original fetch function
  const originalFetch = window.fetch;

  // Override global fetch with interceptor
  window.fetch = function(...args) {
    return originalFetch.apply(this, args)
      .then(response => {
        // Check if it's a 401 Unauthorized response
        if (response.status === 401) {
          // Session expired or user not authenticated
          console.warn('401 Unauthorized - Session expired. Redirecting to login...');

          // Redirect to login page
          window.location.href = '/';

          // Return a rejected promise to prevent further processing
          return Promise.reject(new Error('Session expired - redirecting to login'));
        }

        // Return the response for normal processing
        return response;
      })
      .catch(error => {
        // If it's our session expired error, don't log it (already handled)
        if (error.message !== 'Session expired - redirecting to login') {
          // Let other errors propagate normally
          throw error;
        }
        // For session expired, return a rejected promise
        return Promise.reject(error);
      });
  };

  console.log('API Interceptor loaded - All fetch calls will be monitored for 401 responses');
})();
