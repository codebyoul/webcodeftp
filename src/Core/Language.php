<?php

declare(strict_types=1);

namespace WebCodeFTP\Core;

use WebCodeFTP\Core\Logger;

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

        Logger::debug('Language loading', [
            'language' => $language,
            'file_path' => $languageFile,
            'file_exists' => file_exists($languageFile)
        ]);

        if (file_exists($languageFile)) {
            $this->translations = require $languageFile;
            Logger::debug('Translations loaded', [
                'language' => $language,
                'count' => count($this->translations)
            ]);
        } else {
            // Fallback to English
            $fallbackFile = __DIR__ . '/../Languages/' . $this->fallbackLanguage . '.php';
            Logger::debug('Language fallback', [
                'fallback_language' => $this->fallbackLanguage,
                'fallback_file' => $fallbackFile,
                'fallback_exists' => file_exists($fallbackFile)
            ]);
            if (file_exists($fallbackFile)) {
                $this->translations = require $fallbackFile;
                Logger::debug('Fallback translations loaded', [
                    'language' => $this->fallbackLanguage,
                    'count' => count($this->translations)
                ]);
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
