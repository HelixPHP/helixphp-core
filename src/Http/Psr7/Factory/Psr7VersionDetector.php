<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Psr7\Factory;

use ReflectionMethod;
use Psr\Http\Message\MessageInterface;

/**
 * Detects the installed PSR-7 version
 */
class Psr7VersionDetector
{
    private static ?string $version = null;

    /**
     * Detect the installed PSR-7 version
     *
     * @return string "1.x" or "2.x"
     */
    public static function getVersion(): string
    {
        if (self::$version !== null) {
            return self::$version;
        }

        // Check if MessageInterface has return type declarations
        try {
            $reflection = new ReflectionMethod(MessageInterface::class, 'getProtocolVersion');
            $returnType = $reflection->getReturnType();

            // PSR-7 v2.x has return type declarations
            self::$version = $returnType !== null ? '2.x' : '1.x';
        } catch (\ReflectionException $e) {
            // Default to 1.x if reflection fails
            self::$version = '1.x';
        }

        return self::$version;
    }

    /**
     * Check if we're using PSR-7 v1.x
     */
    public static function isV1(): bool
    {
        return self::getVersion() === '1.x';
    }

    /**
     * Check if we're using PSR-7 v2.x
     */
    public static function isV2(): bool
    {
        return self::getVersion() === '2.x';
    }

    /**
     * Reset the cached version (mainly for testing)
     */
    public static function reset(): void
    {
        self::$version = null;
    }
}
