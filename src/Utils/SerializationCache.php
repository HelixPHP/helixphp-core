<?php

declare(strict_types=1);

namespace PivotPHP\Core\Utils;

/**
 * Serialization Cache
 *
 * Simple and effective serialization caching for the microframework.
 * Provides basic caching functionality without unnecessary complexity.
 *
 * Following 'Simplicidade sobre Otimização Prematura' principle.
 */
class SerializationCache
{
    /**
     * Cache storage
     */
    private static array $cache = [];

    /**
     * Cache an item
     */
    public static function set(string $key, mixed $value): void
    {
        self::$cache[$key] = serialize($value);
    }

    /**
     * Get cached item
     */
    public static function get(string $key): mixed
    {
        if (!isset(self::$cache[$key])) {
            return null;
        }

        return unserialize(self::$cache[$key]);
    }

    /**
     * Check if item exists in cache
     */
    public static function has(string $key): bool
    {
        return isset(self::$cache[$key]);
    }

    /**
     * Remove item from cache
     */
    public static function remove(string $key): void
    {
        unset(self::$cache[$key]);
    }

    /**
     * Clear all cache
     */
    public static function clear(): void
    {
        self::$cache = [];
    }

    /**
     * Get cache size
     */
    public static function size(): int
    {
        return count(self::$cache);
    }

    /**
     * Get all cache keys
     */
    public static function keys(): array
    {
        return array_keys(self::$cache);
    }

    /**
     * Get serialized size of cached item or data
     */
    public static function getSerializedSize(mixed $keyOrData, ?string $key = null): int
    {
        // If called with data and key (legacy usage)
        if ($key !== null) {
            return strlen(serialize($keyOrData));
        }

        // If called with just a key (simple usage)
        if (is_string($keyOrData) && isset(self::$cache[$keyOrData])) {
            return strlen(self::$cache[$keyOrData]);
        }

        // If called with data directly
        if (!is_string($keyOrData)) {
            return strlen(serialize($keyOrData));
        }

        return 0;
    }
}
