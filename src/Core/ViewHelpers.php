<?php

declare(strict_types=1);

/**
 * View Helper Functions
 *
 * Minimal helper functions for views
 */

if (!function_exists('t')) {
    /**
     * Translation helper function
     * @param string $key Translation key
     * @param string $default Default value if key not found
     * @return string Escaped translation text
     */
    function t(string $key, string $default = ''): string {
        global $translations;

        // Check if translations array exists and has the key
        if (isset($translations) && is_array($translations) && isset($translations[$key])) {
            $value = $translations[$key];
        } else {
            $value = $default ?: $key;
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML output
     * @param mixed $value Value to escape
     * @return string Escaped value
     */
    function e($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}