<?php

declare(strict_types=1);

namespace PivotPHP\Core\Performance;

use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;

/**
 * Simplified Performance Mode for PivotPHP
 *
 * This is a much simpler alternative to HighPerformanceMode that focuses on
 * essential optimizations without the complexity of distributed systems,
 * advanced monitoring, and over-engineered features.
 */
class SimplePerformanceMode
{
    /**
     * Performance profiles - simplified
     */
    public const PROFILE_DEVELOPMENT = 'development';
    public const PROFILE_PRODUCTION = 'production';
    public const PROFILE_TEST = 'test';

    /**
     * Current mode
     */
    private static ?string $currentMode = null;

    /**
     * Enable simple performance optimizations
     */
    public static function enable(string $profile = self::PROFILE_PRODUCTION): void
    {
        self::$currentMode = $profile;

        switch ($profile) {
            case self::PROFILE_DEVELOPMENT:
                // Minimal optimizations for development
                OptimizedHttpFactory::initialize(['enable_pooling' => false]);
                break;

            case self::PROFILE_TEST:
                // No optimizations for tests - they add overhead to small workloads
                OptimizedHttpFactory::initialize(['enable_pooling' => false]);
                break;

            case self::PROFILE_PRODUCTION:
                // Enable only proven optimizations for production
                OptimizedHttpFactory::initialize(
                    [
                        'enable_pooling' => true,
                        'initial_size' => 20,       // Smaller, more reasonable pool
                        'max_size' => 100,          // Don't over-allocate
                    ]
                );
                break;
        }
    }

    /**
     * Disable performance mode
     */
    public static function disable(): void
    {
        self::$currentMode = null;
        OptimizedHttpFactory::initialize(['enable_pooling' => false]);
    }

    /**
     * Get current mode
     */
    public static function getCurrentMode(): ?string
    {
        return self::$currentMode;
    }

    /**
     * Check if performance mode is enabled
     */
    public static function isEnabled(): bool
    {
        return self::$currentMode !== null;
    }

    /**
     * Get simple status
     */
    public static function getStatus(): array
    {
        return [
            'enabled' => self::isEnabled(),
            'mode' => self::$currentMode,
            'pool_enabled' => self::$currentMode === self::PROFILE_PRODUCTION,
        ];
    }
}
