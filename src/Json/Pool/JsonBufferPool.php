<?php

declare(strict_types=1);

namespace PivotPHP\Core\Json\Pool;

/**
 * High-performance JSON buffer pool
 *
 * Manages a pool of JsonBuffer objects for optimal memory usage
 * and performance in JSON operations.
 *
 * @package PivotPHP\Core\Json\Pool
 * @since 1.1.1
 */
class JsonBufferPool
{
    // JSON Size Estimation Constants
    private const STRING_OVERHEAD = 20;              // Quotes + escaping overhead
    private const EMPTY_ARRAY_SIZE = 2;              // []
    private const SMALL_ARRAY_SIZE = 512;            // Small array estimate (< 10 items)
    private const MEDIUM_ARRAY_SIZE = 2048;          // Medium array estimate (< 100 items)
    private const LARGE_ARRAY_SIZE = 8192;           // Large array estimate (< 1000 items)
    private const XLARGE_ARRAY_SIZE = 32768;         // XLarge array estimate (>= 1000 items)

    // Array size thresholds
    private const SMALL_ARRAY_THRESHOLD = 10;        // Threshold for small array
    private const MEDIUM_ARRAY_THRESHOLD = 100;      // Threshold for medium array
    private const LARGE_ARRAY_THRESHOLD = 1000;     // Threshold for large array

    // Object size estimation constants
    private const OBJECT_PROPERTY_OVERHEAD = 50;     // Bytes per object property
    private const OBJECT_BASE_SIZE = 100;            // Base size for objects

    // Primitive type size constants
    private const BOOLEAN_OR_NULL_SIZE = 10;         // Size for boolean/null values
    private const NUMERIC_SIZE = 20;                 // Size for numeric values
    private const DEFAULT_ESTIMATE = 100;            // Default fallback estimate

    // Buffer capacity constants
    private const MIN_LARGE_BUFFER_SIZE = 65536;     // Minimum size for very large buffers (64KB)
    private const BUFFER_SIZE_MULTIPLIER = 2;        // Multiplier for buffer size calculation

    /**
     * Buffer pools organized by capacity
     */
    private static array $pools = [];

    /**
     * Pool configuration
     */
    private static array $config = [
        'max_pool_size' => 50,
        'default_capacity' => 4096,
        'size_categories' => [
            'small' => 1024,      // 1KB
            'medium' => 4096,     // 4KB
            'large' => 16384,     // 16KB
            'xlarge' => 65536     // 64KB
        ]
    ];

    /**
     * Pool statistics
     */
    private static array $stats = [
        'allocations' => 0,
        'deallocations' => 0,
        'reuses' => 0,
        'peak_usage' => 0,
        'current_usage' => 0
    ];

    /**
     * Get a buffer from the pool or create new one
     */
    public static function getBuffer(?int $capacity = null): JsonBuffer
    {
        $capacity = $capacity ?? self::$config['default_capacity'];
        $poolKey = self::getPoolKey($capacity);

        if (!isset(self::$pools[$poolKey])) {
            self::$pools[$poolKey] = [];
        }

        // Try to reuse from pool
        if (!empty(self::$pools[$poolKey])) {
            $buffer = array_pop(self::$pools[$poolKey]);
            self::$stats['reuses']++;
            self::$stats['current_usage']++;

            // Reset buffer for reuse
            $buffer->reset();
            return $buffer;
        }

        // Create new buffer
        $buffer = new JsonBuffer($capacity);
        self::$stats['allocations']++;
        self::$stats['current_usage']++;

        // Update peak usage
        if (self::$stats['current_usage'] > self::$stats['peak_usage']) {
            self::$stats['peak_usage'] = self::$stats['current_usage'];
        }

        return $buffer;
    }

    /**
     * Return a buffer to the pool
     */
    public static function returnBuffer(JsonBuffer $buffer): void
    {
        $capacity = $buffer->getCapacity();
        $poolKey = self::getPoolKey($capacity);

        if (!isset(self::$pools[$poolKey])) {
            self::$pools[$poolKey] = [];
        }

        // Check if pool has space
        if (count(self::$pools[$poolKey]) < self::$config['max_pool_size']) {
            // Reset buffer before returning to pool
            $buffer->reset();
            self::$pools[$poolKey][] = $buffer;
            self::$stats['deallocations']++;
        }

        self::$stats['current_usage']--;
    }

    /**
     * Encode data using pooled buffer
     */
    public static function encodeWithPool(
        mixed $data,
        int $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ): string {
        $estimatedSize = self::estimateJsonSize($data);
        $buffer = self::getBuffer($estimatedSize);

        try {
            $buffer->appendJson($data, $flags);
            return $buffer->finalize();
        } finally {
            self::returnBuffer($buffer);
        }
    }

    /**
     * Get pool statistics
     */
    public static function getStatistics(): array
    {
        $totalOperations = (int)self::$stats['allocations'] + (int)self::$stats['reuses'];
        $reuseRate = $totalOperations > 0 ? ((int)self::$stats['reuses'] / $totalOperations) * 100 : 0;

        $poolSizes = [];
        foreach (self::$pools as $key => $pool) {
            $poolSizes[$key] = count($pool);
        }

        return [
            'reuse_rate' => round($reuseRate, 2),
            'total_operations' => $totalOperations,
            'current_usage' => self::$stats['current_usage'],
            'peak_usage' => self::$stats['peak_usage'],
            'pool_sizes' => $poolSizes,
            'detailed_stats' => self::$stats
        ];
    }

    /**
     * Clear all pools (useful for testing)
     */
    public static function clearPools(): void
    {
        self::$pools = [];
        self::$stats = [
            'allocations' => 0,
            'deallocations' => 0,
            'reuses' => 0,
            'peak_usage' => 0,
            'current_usage' => 0
        ];
    }

    /**
     * Configure pool settings
     */
    public static function configure(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Get pool key for given capacity
     */
    private static function getPoolKey(int $capacity): string
    {
        // Normalize to power of 2 for efficient pooling
        $normalizedCapacity = 1;
        while ($normalizedCapacity < $capacity) {
            $normalizedCapacity <<= 1;
        }

        return "buffer_{$normalizedCapacity}";
    }

    /**
     * Estimate JSON size for data
     */
    private static function estimateJsonSize(mixed $data): int
    {
        if (is_string($data)) {
            return strlen($data) + self::STRING_OVERHEAD;
        }

        if (is_array($data)) {
            $count = count($data);
            if ($count === 0) {
                return self::EMPTY_ARRAY_SIZE;
            }

            // Estimate based on array size
            if ($count < self::SMALL_ARRAY_THRESHOLD) {
                return self::SMALL_ARRAY_SIZE;
            } elseif ($count < self::MEDIUM_ARRAY_THRESHOLD) {
                return self::MEDIUM_ARRAY_SIZE;
            } elseif ($count < self::LARGE_ARRAY_THRESHOLD) {
                return self::LARGE_ARRAY_SIZE;
            } else {
                return self::XLARGE_ARRAY_SIZE;
            }
        }

        if (is_object($data)) {
            $vars = get_object_vars($data);
            return $vars
                ? count($vars) * self::OBJECT_PROPERTY_OVERHEAD + self::OBJECT_BASE_SIZE
                : self::OBJECT_BASE_SIZE;
        }

        if (is_bool($data) || is_null($data)) {
            return self::BOOLEAN_OR_NULL_SIZE;
        }

        if (is_numeric($data)) {
            return self::NUMERIC_SIZE;
        }

        return self::DEFAULT_ESTIMATE;
    }

    /**
     * Get optimal buffer capacity for data
     */
    public static function getOptimalCapacity(mixed $data): int
    {
        $estimatedSize = self::estimateJsonSize($data);

        // Find the smallest size category that fits
        foreach (self::$config['size_categories'] as $name => $capacity) {
            if ($estimatedSize <= $capacity) {
                return $capacity;
            }
        }

        // For very large data, calculate based on estimate
        return max($estimatedSize * self::BUFFER_SIZE_MULTIPLIER, self::MIN_LARGE_BUFFER_SIZE);
    }
}
