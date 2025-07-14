<?php

declare(strict_types=1);

namespace PivotPHP\Core\Core;

/**
 * Environment - Centralized environment detection and management
 *
 * Provides consistent methods for detecting development mode, environment settings,
 * and debug state across the entire framework.
 */
class Environment
{
    private static ?bool $isDevelopmentCache = null;
    private static ?bool $isDebugCache = null;
    private static ?string $environmentCache = null;

    /**
     * Check if the application is running in development mode
     */
    public static function isDevelopment(): bool
    {
        if (self::$isDevelopmentCache !== null) {
            return self::$isDevelopmentCache;
        }

        self::$isDevelopmentCache = (
            self::getEnvironment() === 'development' ||
            self::isDebug() ||
            ini_get('display_errors') === '1' ||
            (defined('PIVOTPHP_DEBUG') && PIVOTPHP_DEBUG === true)
        );

        return self::$isDevelopmentCache;
    }

    /**
     * Check if debug mode is enabled
     */
    public static function isDebug(): bool
    {
        if (self::$isDebugCache !== null) {
            return self::$isDebugCache;
        }

        $debug = $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? null;

        if ($debug === null) {
            $debug = getenv('APP_DEBUG');
            if ($debug === false || $debug === '') {
                $debug = false;
            }
        }

        self::$isDebugCache = filter_var($debug, FILTER_VALIDATE_BOOLEAN) || $debug === '1' || $debug === 'true';

        return self::$isDebugCache;
    }

    /**
     * Check if the application is running in production mode
     */
    public static function isProduction(): bool
    {
        return self::getEnvironment() === 'production';
    }

    /**
     * Check if the application is running in testing mode
     */
    public static function isTesting(): bool
    {
        return self::getEnvironment() === 'testing';
    }

    /**
     * Get the current environment
     */
    public static function getEnvironment(): string
    {
        if (self::$environmentCache !== null) {
            return self::$environmentCache;
        }

        $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null;
        if ($env === null) {
            $envFromGetenv = getenv('APP_ENV');
            $env = ($envFromGetenv !== false && $envFromGetenv !== '') ? $envFromGetenv : 'production';
        }

        self::$environmentCache = (string) $env;

        return self::$environmentCache;
    }

    /**
     * Check if we're running in CLI mode
     */
    public static function isCli(): bool
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Check if we're running in a web context
     */
    public static function isWeb(): bool
    {
        return !self::isCli();
    }

    /**
     * Get environment variable with fallback
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;

        if ($value === null) {
            $value = getenv($key);
            if ($value === false || $value === '') {
                return $default;
            }
        }

        return (string) $value;
    }

    /**
     * Check if environment variable exists
     */
    public static function has(string $key): bool
    {
        return isset($_ENV[$key]) || isset($_SERVER[$key]) || getenv($key) !== false;
    }

    /**
     * Clear internal caches (useful for testing)
     */
    public static function clearCache(): void
    {
        self::$isDevelopmentCache = null;
        self::$isDebugCache = null;
        self::$environmentCache = null;
    }

    /**
     * Get all environment detection information for debugging
     */
    public static function getDebugInfo(): array
    {
        return [
            'environment' => self::getEnvironment(),
            'is_development' => self::isDevelopment(),
            'is_debug' => self::isDebug(),
            'is_production' => self::isProduction(),
            'is_testing' => self::isTesting(),
            'is_cli' => self::isCli(),
            'is_web' => self::isWeb(),
            'display_errors' => ini_get('display_errors'),
            'pivotphp_debug_defined' => defined('PIVOTPHP_DEBUG'),
            'pivotphp_debug_value' => defined('PIVOTPHP_DEBUG') ? PIVOTPHP_DEBUG : null,
        ];
    }
}
