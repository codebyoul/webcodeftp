// WebFTP API Interceptor
// Global fetch wrapper that handles API errors with elite toast notifications
// MUST be loaded AFTER toast-notifications.js

/**
 * ============================================================================
 * GLOBAL FETCH INTERCEPTOR - Elite Error Handling
 * ============================================================================
 *
 * This intercepts ALL fetch() calls in the application and handles:
 * - 401: Auto-redirect to login (session expired)
 * - 4xx: Client errors (validation, permissions, etc.) - Show toast
 * - 5xx: Server errors - Show toast
 * - Network errors: Offline, timeout, DNS - Show toast
 *
 * Usage: Just include this file - all fetch calls will be intercepted.
 */

(function() {
  'use strict';

  // Store original fetch function
  const originalFetch = window.fetch;

  // Helper: Parse error message from API response
  async function parseErrorMessage(response) {
    try {
      const contentType = response.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
        const data = await response.json();
        return data.message || data.error || 'Unknown error';
      }
      return await response.text() || response.statusText || 'Unknown error';
    } catch (e) {
      return response.statusText || 'Unknown error';
    }
  }

  // Helper: Get user-friendly title for error type
  function getErrorTitle(status) {
    if (status >= 400 && status < 500) {
      // Client errors
      if (status === 400) return 'Invalid Request';
      if (status === 403) return 'Access Denied';
      if (status === 404) return 'Not Found';
      if (status === 422) return 'Validation Error';
      return 'Request Failed';
    } else if (status >= 500) {
      // Server errors
      if (status === 500) return 'Server Error';
      if (status === 502) return 'Bad Gateway';
      if (status === 503) return 'Service Unavailable';
      if (status === 504) return 'Gateway Timeout';
      return 'Server Error';
    }
    return 'Request Failed';
  }

  // Override global fetch with interceptor
  window.fetch = function(...args) {
    return originalFetch.apply(this, args)
      .then(async response => {
        // Check if it's a 401 Unauthorized response
        if (response.status === 401) {
          // Session expired or user not authenticated
          console.warn('401 Unauthorized - Session expired. Redirecting to login...');

          // Redirect to login page
          window.location.href = '/';

          // Return a rejected promise to prevent further processing
          return Promise.reject(new Error('Session expired - redirecting to login'));
        }

        // Check for HTTP error status codes (4xx, 5xx)
        if (!response.ok && response.status !== 401) {
          const responseClone = response.clone();
          const errorMessage = await parseErrorMessage(responseClone);
          const errorTitle = getErrorTitle(response.status);

          const duration = response.status >= 500 ? 6000 : 5000;

          if (window.showToast) {
            window.showToast.error(errorTitle, errorMessage, { duration });
          }
        }

        // Check for API errors in JSON body (status 200 with success:false)
        if (response.ok && response.status === 200) {
          const contentType = response.headers.get('content-type');
          if (contentType && contentType.includes('application/json')) {
            const responseClone = response.clone();

            try {
              const data = await responseClone.json();

              if (data.success === false) {
                const errorMessage = data.message || 'Unknown error';
                const errorTitle = 'Request Failed';

                if (window.showToast) {
                  window.showToast.error(errorTitle, errorMessage, { duration: 5000 });
                }
              }
            } catch (e) {
              // Not JSON or parsing error - ignore
            }
          }
        }

        // Return the response for normal processing
        return response;
      })
      .catch(error => {
        // If it's our session expired error, don't log it (already handled)
        if (error.message === 'Session expired - redirecting to login') {
          return Promise.reject(error);
        }

        // Network errors (offline, timeout, DNS failure, etc.)
        console.error('Network Error:', error.message);

        // Show network error toast with longer duration
        if (window.showToast) {
          window.showToast.error(
            'Network Error',
            error.message || 'Unable to connect to server',
            { duration: 7000 }
          );
        }

        // Let the error propagate
        throw error;
      });
  };

  console.log('API Interceptor loaded - All fetch calls monitored with toast notifications');
})();
