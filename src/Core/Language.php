<?php

declare(strict_types=1);

namespace WebFTP\Core;

/**
 * Language Helper
 *
 * Loads and manages translations for internationalization
 */
class Language
{
    private array $translations = [];
    private string $currentLanguage;
    private string $fallbackLanguage;
    private array $availableLanguages;

    /**
     * Load language translations
     *
     * @param string $language Language code
     * @param array $config Application configuration
     */
    public function __construct(string $language, array $config)
    {
        // Load language settings from config
        $this->fallbackLanguage = $config['localization']['default_language'] ?? 'en';
        $this->availableLanguages = $config['localization']['available_languages'] ?? ['en'];

        // Validate language
        if (!in_array($language, $this->availableLanguages, true)) {
            $language = $this->fallbackLanguage;
        }

        $this->currentLanguage = $language;

        // Load translations
        $this->loadTranslations($language);
    }

    /**
     * Load translation file
     *
     * @param string $language Language code
     */
    private function loadTranslations(string $language): void
    {
        $languageFile = __DIR__ . '/../Languages/' . $language . '.php';

        if (file_exists($languageFile)) {
            $this->translations = require $languageFile;
        } else {
            // Fallback to English
            $fallbackFile = __DIR__ . '/../Languages/' . $this->fallbackLanguage . '.php';
            if (file_exists($fallbackFile)) {
                $this->translations = require $fallbackFile;
            }
        }
    }

    /**
     * Get translation by key
     *
     * @param string $key Translation key
     * @param string $default Default value if key not found
     * @return string Translated text
     */
    public function get(string $key, string $default = ''): string
    {
        return $this->translations[$key] ?? $default;
    }

    /**
     * Get all translations
     *
     * @return array All translations
     */
    public function all(): array
    {
        return $this->translations;
    }

    /**
     * Get current language code
     *
     * @return string Current language code
     */
    public function getCurrentLanguage(): string
    {
        return $this->currentLanguage;
    }

    /**
     * Get available languages
     *
     * @return array Available language codes
     */
    public function getAvailableLanguages(): array
    {
        return $this->availableLanguages;
    }
}
